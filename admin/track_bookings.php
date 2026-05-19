<?php
// File: admin/track_bookings.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

$filter_bid = trim($_GET['f_bid'] ?? '');
$filter_student = trim($_GET['f_student'] ?? '');
$filter_venue = trim($_GET['f_venue'] ?? '');
$filter_date = trim($_GET['f_date'] ?? '');
$filter_status = trim($_GET['f_status'] ?? '');

$sql = "SELECT 
            b.bid, 
            b.date_booked, 
            b.time_start, 
            b.time_end, 
            b.status, 
            b.payment_status, 
            b.created_at,
            u.uid AS student_id,
            u.username, 
            u.email,
            v.vid AS venue_id, 
            v.vname, 
            vc.category AS venue_category,
            v.deposit
        FROM booking b
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        JOIN vcategory vc ON v.vcid = vc.vcid
        WHERE 1=1";

if (!empty($filter_bid)) {
    $sql .= " AND b.bid LIKE '%" . $conn->real_escape_string($filter_bid) . "%'";
}
if (!empty($filter_student)) {
    $sql .= " AND (u.username LIKE '%" . $conn->real_escape_string($filter_student) . "%' OR u.uid LIKE '%" . $conn->real_escape_string($filter_student) . "%')";
}
if (!empty($filter_venue)) {
    $sql .= " AND (v.vname LIKE '%" . $conn->real_escape_string($filter_venue) . "%' OR vc.category LIKE '%" . $conn->real_escape_string($filter_venue) . "%')";
}
if (!empty($filter_date)) {
    $sql .= " AND b.date_booked = '" . $conn->real_escape_string($filter_date) . "'";
}
if (!empty($filter_status)) {
    $sql .= " AND b.status = '" . $conn->real_escape_string($filter_status) . "'";
}

$sql .= " ORDER BY b.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Track Bookings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { cstyle: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori_forms.css">
    <link rel="stylesheet" href="../assets/css/table.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
            <?php 
            // ∴ P_4: Simplified breadcrumb
            $topbar_content = '
            <div class="flex items-center">
                <a href="manage_bookings.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Bookings / History</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-6">
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Booking History</h1>
                <p class="text-sm text-slate-600 mt-1">View past and current venue bookings.</p>
            </div>

            <div class="bg-white p-5 rounded-md border border-slate-200 shadow-[0_1px_2px_0_rgba(0,0,0,0.05)] hover:border-slate-400 transition-colors mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Booking ID</label>
                        <input type="text" name="f_bid" value="<?php echo htmlspecialchars($filter_bid); ?>" placeholder="Search ID..." class="fiori-input w-full">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Student</label>
                        <input type="text" name="f_student" value="<?php echo htmlspecialchars($filter_student); ?>" placeholder="Name or ID..." class="fiori-input w-full">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Venue</label>
                        <input type="text" name="f_venue" value="<?php echo htmlspecialchars($filter_venue); ?>" placeholder="Venue Name..." class="fiori-input w-full">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Date</label>
                        <input type="date" name="f_date" value="<?php echo htmlspecialchars($filter_date); ?>" class="fiori-input w-full">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Status</label>
                        <select name="f_status" class="fiori-input w-full bg-white font-bold text-slate-700 cursor-pointer">
                            <option value="">All</option>
                            <option value="pending" <?php if($filter_status==='pending') echo 'selected'; ?>>Pending</option>
                            <option value="approved" <?php if($filter_status==='approved') echo 'selected'; ?>>Approved</option>
                            <option value="rejected" <?php if($filter_status==='rejected') echo 'selected'; ?>>Rejected</option>
                            <option value="completed" <?php if($filter_status==='completed') echo 'selected'; ?>>Completed</option>
                        </select>
                    </div>

                    <div class="flex space-x-2">
                        <button type="submit" class="w-full py-2 bg-[#004aad] text-white text-sm font-semibold rounded-md hover:bg-[#003882] transition shadow-sm border border-[#004aad]">
                            Apply
                        </button>
                        <a href="track_bookings.php" class="px-4 py-2 bg-transparent text-slate-600 text-sm font-semibold rounded-md hover:bg-slate-100 border border-transparent transition flex items-center justify-center" title="Reset Filters">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="custom-table-container">
                <div class="px-4 py-3 border-b border-slate-200 bg-[#f8fafc] flex justify-between items-center">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Records (<?php echo $result->num_rows; ?>)</h3>
                </div>
                
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th class="w-28 text-center">ID</th>
                            <th class="w-28">Student ID</th>
                            <th class="w-36">Student Name</th>
                            <th class="w-28">Venue ID</th>
                            <th class="w-40">Venue Name</th>
                            <th class="w-32">Category</th>
                            <th class="w-36 text-center">Booked Time</th>
                            <th class="w-32 text-right">Status</th>
                            <th class="text-center w-16">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $formatted_date = date('Y/m/d', strtotime($row['date_booked']));
                                $time_range = date('H:i', strtotime($row['time_start'])) . ' - ' . date('H:i', strtotime($row['time_end']));
                            ?>
                            <tr ondblclick="window.location.href='process_flow.php?bid=<?php echo urlencode($row['bid']); ?>'">
                                <td class="text-center">
                                    <a href="process_flow.php?bid=<?php echo urlencode($row['bid']); ?>" class="td-text-mono">
                                        <?php echo htmlspecialchars($row['bid']); ?>
                                    </a>
                                </td>
                                <td><span class="td-text-mono"><?php echo htmlspecialchars($row['student_id']); ?></span></td>
                                <td><span class="font-semibold text-slate-800 text-sm block"><?php echo htmlspecialchars($row['username']); ?></span></td>
                                <td><span class="td-text-mono"><?php echo htmlspecialchars($row['venue_id']); ?></span></td>
                                <td><span class="font-semibold text-slate-800 text-sm block"><?php echo htmlspecialchars($row['vname']); ?></span></td>
                                <td><span class="px-2 py-0.5 bg-[#f1f5f9] text-slate-600 text-[10px] font-bold uppercase tracking-wider rounded-sm border border-[#e2e8f0] inline-block"><?php echo htmlspecialchars($row['venue_category']); ?></span></td>
                                <td class="text-center">
                                    <div class="td-text-mono">
                                        <span class="block mb-0.5"><?php echo $formatted_date; ?></span>
                                        <span class="block text-slate-500 font-bold"><?php echo $time_range; ?></span>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="space-y-1.5 flex flex-col items-end">
                                        <?php 
                                        $c = match($row['status']) {
                                            'pending' => 'bg-[#fffbeb] text-[#b45309] border-[#fde68a]',
                                            'approved' => 'bg-[#eff6ff] text-[#1d4ed8] border-[#bfdbfe]',
                                            'completed' => 'bg-[#ecfdf5] text-[#059669] border-[#a7f3d0]',
                                            'rejected' => 'bg-[#fef2f2] text-[#dc2626] border-[#fecaca]',
                                            default => 'bg-[#f1f5f9] text-slate-600 border-[#e2e8f0]'
                                        };
                                        ?>
                                        <span class="inline-block px-2 py-0.5 border <?php echo $c; ?> rounded-sm text-[10px] font-bold uppercase tracking-widest text-center min-w-[85px]">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                        
                                        <span class="inline-block text-[10px] font-bold uppercase tracking-widest <?php echo ($row['payment_status'] === 'paid') ? 'text-[#059669]' : 'text-slate-400'; ?>">
                                            Pay: <?php echo htmlspecialchars($row['payment_status']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <a href="process_flow.php?bid=<?php echo urlencode($row['bid']); ?>" class="td-action-btn flex justify-center w-full">&gt;</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="py-16 text-center text-slate-500 border-none hover:bg-transparent cursor-default">
                                    <span class="text-sm font-medium tracking-wide">No records found.</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();
        function toggleSidebar() { document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed'); }
    </script>
</body>
</html>