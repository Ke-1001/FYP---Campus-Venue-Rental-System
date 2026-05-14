<?php
// File: admin/assign_inspector.php
session_start();
require_once '../config/db.php';
require_once('../includes/admin_auth.php');

// 💡 1. 接收多維度過濾參數
$filter_bid = trim($_GET['f_bid'] ?? '');
$filter_student = trim($_GET['f_student'] ?? '');
$filter_venue = trim($_GET['f_venue'] ?? '');
$filter_date = trim($_GET['f_date'] ?? '');

// 💡 2. 構建聚合查詢 (適配最新的 Schema: 引入 vcategory)
$sql = "SELECT 
            b.bid, 
            b.date_booked, 
            b.time_start,
            b.time_end,
            b.payment_status,
            u.uid AS student_id,
            u.username AS student_name,
            u.phone_num,
            u.email,
            v.vid AS venue_id,
            v.vname AS venue_name,
            vc.category AS venue_category
        FROM booking b
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        JOIN vcategory vc ON v.vcid = vc.vcid
        LEFT JOIN inspection i ON b.bid = i.bid
        WHERE b.status IN ('approved', 'completed') 
          AND b.payment_status = 'paid'
          AND i.ins_id IS NULL";

// 💡 3. 動態注入過濾條件
if (!empty($filter_bid)) {
    $sql .= " AND b.bid LIKE '%" . $conn->real_escape_string($filter_bid) . "%'";
}
if (!empty($filter_student)) {
    $sql .= " AND (u.username LIKE '%" . $conn->real_escape_string($filter_student) . "%' OR u.uid LIKE '%" . $conn->real_escape_string($filter_student) . "%')";
}
if (!empty($filter_venue)) {
    // 修正對 vc.category 的檢索
    $sql .= " AND (v.vname LIKE '%" . $conn->real_escape_string($filter_venue) . "%' OR vc.category LIKE '%" . $conn->real_escape_string($filter_venue) . "%')";
}
if (!empty($filter_date)) {
    $sql .= " AND b.date_booked = '" . $conn->real_escape_string($filter_date) . "'";
}

// 根據時間排序，確保最急迫的檢驗任務排在最前
$sql .= " ORDER BY b.date_booked ASC, b.time_start ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Assign Inspector</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { cstyle: { blue: '#004aad', dark: '#1e293b', accent: '#38bdf8' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori_forms.css">
    <link rel="stylesheet" href="../assets/css/table.css">
</head>
<body class="bg-[#f4f4f4] text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">

            <?php 
            $topbar_content = '
            <div class="flex items-center">
                <a href="manage_bookings.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Bookings / Assign Inspector</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-6">
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Assign Inspector</h1>
                <p class="text-sm text-slate-600 mt-1">Delegate post-event venue inspections to designated personnel.</p>
            </div>

            <div class="bg-white p-5 rounded-lg shadow-sm border border-slate-200 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Booking ID</label>
                        <input type="text" name="f_bid" value="<?php echo htmlspecialchars($filter_bid); ?>" placeholder="Search ID..." class="fiori-input w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Student Entity</label>
                        <input type="text" name="f_student" value="<?php echo htmlspecialchars($filter_student); ?>" placeholder="Name or ID..." class="fiori-input w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Asset</label>
                        <input type="text" name="f_venue" value="<?php echo htmlspecialchars($filter_venue); ?>" placeholder="Name or Category..." class="fiori-input w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Date</label>
                        <input type="date" name="f_date" value="<?php echo htmlspecialchars($filter_date); ?>" class="fiori-input w-full">
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" class="w-full py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition shadow-sm">
                            Apply Filter
                        </button>
                        <a href="assign_inspector.php" class="px-4 py-2 bg-slate-100 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-200 transition flex items-center justify-center" title="Reset Filters">
                            <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="custom-table-container">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
                    <h3 class="text-xs font-black text-slate-600 uppercase tracking-widest">Pending Assignment (<?php echo $result->num_rows; ?>)</h3>
                </div>
                
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th class="w-28 text-center">Reference</th>
                            <th class="w-28">Student ID</th>
                            <th class="w-36">Student Name</th>
                            <th class="w-28">Venue ID</th>
                            <th class="w-40">Venue</th>
                            <th class="w-32">Category</th>
                            <th class="w-32 text-center">Reserved Slot</th>
                            <th class="text-center w-24">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $formatted_date = date('Y/m/d', strtotime($row['date_booked']));
                                $time_range = date('H:i', strtotime($row['time_start'])) . ' - ' . date('H:i', strtotime($row['time_end']));
                            ?>
                            <tr ondblclick="window.location.href='assign_inspector_detail.php?bid=<?php echo urlencode($row['bid']); ?>'">
                                <td class="text-center">
                                    <a href="process_flow.php?bid=<?php echo urlencode($row['bid']); ?>" class="td-text-mono">
                                        <?php echo htmlspecialchars($row['bid']); ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="edit_student.php?uid=<?php echo urlencode($row['student_id']); ?>" class="td-text-mono">
                                        <?php echo htmlspecialchars($row['student_id']); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="td-text-mono"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                </td>
                                <td>
                                    <a href="edit_venue.php?vid=<?php echo urlencode($row['venue_id']); ?>" class="td-text-mono">
                                        <?php echo htmlspecialchars($row['venue_id']); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="td-text-mono"><?php echo htmlspecialchars($row['venue_name']); ?></span>
                                </td>
                                <td>
                                    <span class="td-text-mono" style="text-transform: uppercase;"><?php echo htmlspecialchars($row['venue_category']); ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="td-text-mono" style="text-transform: uppercase;">
                                        <span><?php echo $formatted_date; ?></span>
                                        <span><?php echo $time_range; ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="assign_inspector_detail.php?bid=<?php echo urlencode($row['bid']); ?>" class="td-action-btn">
                                        &gt;
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-16 text-center text-slate-500 font-medium border-none hover:bg-transparent cursor-default">
                                    <span class="block text-4xl mb-3">📋</span>
                                    No pending assignments match the criteria.
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
        function toggleSidebar() {
            document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed');
        }
    </script>
</body>
</html>