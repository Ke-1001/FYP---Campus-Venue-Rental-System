<?php
// File: admin/report.php

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php'; 

// Real monthly deposit collection for the last 6 months
$month_map = [];
$months = [];
$revenue_data = [];

for ($i = 5; $i >= 0; $i--) {
    $month_key = date('Y-m', strtotime("-$i months"));
    $month_map[$month_key] = 0.00;
}

$sql_revenue = "
    SELECT 
        DATE_FORMAT(COALESCE(b.paid_at, b.created_at), '%Y-%m') AS month_key,
        SUM(v.deposit) AS total_revenue
    FROM booking b
    JOIN venue v ON b.vid = v.vid
    WHERE b.payment_status = 'paid'
      AND COALESCE(b.paid_at, b.created_at) >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
    GROUP BY month_key
";

$res_revenue = $conn->query($sql_revenue);
if ($res_revenue) {
    while ($row = $res_revenue->fetch_assoc()) {
        if (isset($month_map[$row['month_key']])) {
            $month_map[$row['month_key']] = (float)$row['total_revenue'];
        }
    }
}

foreach ($month_map as $month_key => $amount) {
    $months[] = date('M', strtotime($month_key . '-01'));
    $revenue_data[] = $amount;
}

// Real venue utilization for current month
$venue_labels = [];
$utilization_percentages = [];

$sql_util = "
    SELECT 
        v.vname,
        COALESCE(
            ROUND(
                (
                    SUM(
                        CASE 
                            WHEN b.bid IS NOT NULL 
                            THEN TIME_TO_SEC(TIMEDIFF(b.time_end, b.time_start)) / 60
                            ELSE 0
                        END
                    ) / (DAY(LAST_DAY(CURDATE())) * 24 * 60)
                ) * 100,
                2
            ),
            0
        ) AS utilization_percent
    FROM venue v
    LEFT JOIN booking b 
        ON v.vid = b.vid
       AND b.date_booked BETWEEN DATE_FORMAT(CURDATE(), '%Y-%m-01') AND LAST_DAY(CURDATE())
       AND b.status IN ('approved', 'completed')
    GROUP BY v.vid, v.vname
    ORDER BY utilization_percent DESC
    LIMIT 5
";

$res_util = $conn->query($sql_util);
if ($res_util) {
    while ($row = $res_util->fetch_assoc()) {
        $venue_labels[] = $row['vname'];
        $utilization_percentages[] = (float)$row['utilization_percent'];
    }
}

// 2. Financial transaction listmatrix (Financial Ledger using UNION ALL)
$transactions = [];
// New structure: combine booking (deposit) and report/inspection (fine)
$sql_ledger = "
    SELECT 
        COALESCE(b.transaction_ref, 'TXN-PENDING') AS id, 
        b.bid AS ref, 
        'Deposit' AS type, 
        v.deposit AS amount, 
        DATE_FORMAT(b.created_at, '%Y-%m-%d') AS date,
        b.payment_status AS status 
    FROM booking b
    JOIN venue v ON b.vid = v.vid
    WHERE b.payment_status IN ('paid', 'refunded')
    
    UNION ALL
    
    SELECT 
        r.rid AS id, 
        i.bid AS ref, 
        'Penalty' AS type, 
        i.penalty AS amount, 
        DATE_FORMAT(r.created_at, '%Y-%m-%d') AS date,
        r.penalty_status AS status 
    FROM inspection i
    JOIN report r ON i.ins_id = r.ins_id
    WHERE i.penalty > 0
    
    ORDER BY date DESC LIMIT 10
";

$result = $conn->query($sql_ledger);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
}

// Export the same ledger data as CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'financial_transaction_ledger_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Transaction ID', 'Booking Ref', 'Type', 'Amount (RM)', 'Date', 'Settlement State']);

    foreach ($transactions as $tx) {
        fputcsv($output, [
            $tx['id'],
            $tx['ref'],
            $tx['type'],
            number_format((float)$tx['amount'], 2, '.', ''),
            $tx['date'],
            $tx['status']
        ]);
    }

    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Statistical Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { cstyle: { blue: '#004aad', dark: '#1e293b', accent: '#38bdf8' } } } }
        }
    </script>
    <link rel="stylesheet" href="../assets/css/admin_css.css?v=2.0">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        
            <?php 
            $topbar_content = '
            <div class="flex items-center text-slate-500 bg-white px-4 py-2 rounded-lg border border-slate-200 focus-within:border-mmu-blue shadow-sm transition-all">
                <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                <input type="text" placeholder="Search system assets..." class="bg-transparent border-none outline-none w-64 text-sm focus:ring-0">
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">System Analytics</h1>
                    <p class="text-sm text-slate-500 mt-1">Aggregated multidimensional analysis of financial and usage vectors.</p>
                </div>
                <div class="flex items-center text-slate-500 bg-white px-4 py-2 rounded-lg border border-slate-200 focus-within:border-mmu-blue shadow-sm transition-all">
                    <i data-lucide="calendar" class="w-4 h-4 mr-2"></i>
                    <span class="text-xs font-bold text-slate-700">Fiscal Period: Q2 2026</span>
                </div>
                <a href="report.php?export=csv" class="px-4 py-2 bg-white border border-slate-200 text-mmu-blue font-bold rounded-lg shadow-sm flex items-center hover:bg-slate-50 transition">
                    <i data-lucide="download-cloud" class="w-4 h-4 mr-2"></i> Export CSV
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col h-[400px]">
                    <h2 class="text-lg font-extrabold text-slate-800 mb-6 flex items-center">
                        <i data-lucide="trending-up" class="w-5 h-5 mr-2 text-mmu-blue"></i> Monthly Revenue Vector (RM)
                    </h2>
                    <div class="flex-1 min-h-0">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col h-[400px]">
                    <h2 class="text-lg font-extrabold text-slate-800 mb-6 flex items-center">
                        <i data-lucide="bar-chart-3" class="w-5 h-5 mr-2 text-emerald-500"></i> Venue Utilization Matrix (%)
                    </h2>
                    <div class="flex-1 min-h-0">
                        <canvas id="utilizationChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
                <div class="px-6 py-5 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                    <h2 class="text-lg font-extrabold text-slate-800 flex items-center">
                        <i data-lucide="history" class="w-5 h-5 mr-2 text-slate-600"></i> Financial Transaction Ledger
                    </h2>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-widest">
                            <th class="px-6 py-4 border-b border-slate-200">Transaction ID</th>
                            <th class="px-6 py-4 border-b border-slate-200">Booking Ref</th>
                            <th class="px-6 py-4 border-b border-slate-200">Type</th>
                            <th class="px-6 py-4 border-b border-slate-200">Amount (RM)</th>
                            <th class="px-6 py-4 border-b border-slate-200 text-right">Settlement State</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-700 divide-y divide-slate-100">
                        <?php foreach($transactions as $tx): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs font-bold text-slate-800"><?php echo htmlspecialchars($tx['id']); ?></td>
                            <td class="px-6 py-4 font-mono text-xs font-bold text-mmu-blue"><?php echo htmlspecialchars($tx['ref']); ?></td>
                            <td class="px-6 py-4 font-bold text-slate-600"><?php echo htmlspecialchars($tx['type']); ?></td>
                            <td class="px-6 py-4 font-mono font-bold"><?php echo number_format((float)$tx['amount'], 2); ?></td>
                            <td class="px-6 py-4 text-right">
                                <?php 
 // Dynamic color badge parser (match new lowercase enum)
                                    $status_class = "bg-slate-50 text-slate-600 border-slate-200";
                                    $status_label = strtoupper(str_replace('_', ' ', $tx['status']));
                                    
                                    if($tx['status'] === 'paid' || $tx['status'] === 'processed') {
                                        $status_class = "bg-emerald-50 text-emerald-600 border-emerald-200";
                                    } elseif($tx['status'] === 'refunded') {
                                        $status_class = "bg-blue-50 text-blue-600 border-blue-200";
                                    } elseif($tx['status'] === 'unpaid' || $tx['status'] === 'none') {
                                        $status_class = "bg-red-50 text-red-600 border-red-200";
                                    } elseif($tx['status'] === 'pending') {
                                        $status_class = "bg-amber-50 text-amber-600 border-amber-200";
                                    }
                                ?>
                                <span class="px-2 py-0.5 border <?php echo $status_class; ?> rounded text-[10px] font-black uppercase tracking-widest">
                                    <?php echo htmlspecialchars($status_label); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <script>
        lucide.createIcons();

        const months = <?php echo json_encode($months); ?>;
        const revenueData = <?php echo json_encode($revenue_data); ?>;
        const venueLabels = <?php echo json_encode($venue_labels); ?>;
        const utilizationData = <?php echo json_encode($utilization_percentages); ?>;

        // 1. Revenue Line Chart
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Revenue',
                    data: revenueData,
                    borderColor: '#004aad',
                    backgroundColor: 'rgba(0, 74, 173, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 2. Utilization Bar Chart
        new Chart(document.getElementById('utilizationChart'), {
            type: 'bar',
            data: {
                labels: venueLabels,
                datasets: [{
                    label: 'Utilization %',
                    data: utilizationData,
                    backgroundColor: '#10b981',
                    borderRadius: 6,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, max: 100, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });

        function toggleSidebar() {
            const sidebar = document.getElementById('system-sidebar');
            sidebar.classList.toggle('sidebar-collapsed');
        }
    </script>
</body>
</html>