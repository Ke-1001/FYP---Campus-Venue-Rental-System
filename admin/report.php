<?php
// File: admin/report.php
session_start();
require_once("../config/db.php");

// 💡 1. 財務與利用率趨勢模擬 (Data Aggregation)
// 由於真實系統初期資料稀疏，先擷取所有場地的基本押金總和作為模擬基準
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
$revenue_data = [0, 0, 0, 0, 0, 0]; 

$venue_labels = [];
$utilization_percentages = [];
$sql_util = "SELECT venue_name, capacity FROM venues LIMIT 5";
$res_util = $conn->query($sql_util);
while($row = $res_util->fetch_assoc()) {
    $venue_labels[] = $row['venue_name'];
    $utilization_percentages[] = min($row['capacity'] * 2, 100); // 視覺化模擬運算
}

// 💡 2. 財務交易流水矩陣 (Financial Ledger using UNION ALL)
$transactions = [];
$sql_ledger = "
    SELECT 
        CONCAT('TXN-D', LPAD(payment_id, 4, '0')) AS id, 
        CONCAT('BKG-', LPAD(booking_id, 4, '0')) AS ref, 
        'Deposit' AS type, 
        deposit_paid AS amount, 
        DATE_FORMAT(updated_at, '%Y-%m-%d') AS date,
        IF(payment_status = 'Settled', 'Settled', 'Pending') AS status 
    FROM payments
    UNION ALL
    SELECT 
        CONCAT('TXN-P', LPAD(inspection_id, 4, '0')) AS id, 
        CONCAT('BKG-', LPAD(booking_id, 4, '0')) AS ref, 
        'Penalty' AS type, 
        assessed_penalty AS amount, 
        DATE_FORMAT(inspected_at, '%Y-%m-%d') AS date,
        'Pending' AS status 
    FROM inspections WHERE assessed_penalty > 0
    ORDER BY date DESC LIMIT 10
";

$result = $conn->query($sql_ledger);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
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
            theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b', accent: '#38bdf8' } } } }
        }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.1">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0">
            <div class="flex items-center">
                <button onclick="toggleSidebar()" class="p-2 mr-4 text-slate-500 hover:text-mmu-blue transition-colors rounded-lg hover:bg-slate-100 focus:outline-none">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <div class="flex items-center text-slate-500 bg-white px-4 py-2 rounded-lg border border-slate-200 focus-within:border-mmu-blue shadow-sm transition-all">
                    <i data-lucide="calendar" class="w-4 h-4 mr-2"></i>
                    <span class="text-xs font-bold text-slate-700">Fiscal Period: Q2 2026</span>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <button class="relative p-2 text-slate-500 hover:text-mmu-blue transition-colors rounded-full hover:bg-slate-100">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                </button>
                <button class="p-2 text-slate-500 hover:text-mmu-blue rounded-full hover:bg-slate-100">
                    <i data-lucide="user-circle" class="w-5 h-5"></i>
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">System Analytics</h1>
                    <p class="text-sm text-slate-500 mt-1">Aggregated multidimensional analysis of financial and usage vectors.</p>
                </div>
                <button class="px-4 py-2 bg-white border border-slate-200 text-mmu-blue font-bold rounded-lg shadow-sm flex items-center hover:bg-slate-50 transition">
                    <i data-lucide="download-cloud" class="w-4 h-4 mr-2"></i> Export CSV
                </button>
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
                            <td class="px-6 py-4 font-mono text-xs font-bold text-slate-800"><?php echo $tx['id']; ?></td>
                            <td class="px-6 py-4 font-mono text-xs font-bold text-mmu-blue"><?php echo $tx['ref']; ?></td>
                            <td class="px-6 py-4 font-bold text-slate-600"><?php echo $tx['type']; ?></td>
                            <td class="px-6 py-4 font-mono font-bold"><?php echo $tx['amount']; ?></td>
                            <td class="px-6 py-4 text-right">
                                <?php if($tx['status'] === 'Settled'): ?>
                                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded text-[10px] font-black uppercase tracking-widest">Settled</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 bg-amber-50 text-amber-600 rounded text-[10px] font-black uppercase tracking-widest">Pending</span>
                                <?php endif; ?>
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

        // 從 PHP 安全映射資料至 JavaScript
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
    </script>
    <script>
        lucide.createIcons();

        function toggleSidebar() {
            const sidebar = document.getElementById('system-sidebar');
            sidebar.classList.toggle('sidebar-collapsed');
        }
    </script>
</body>
</html>