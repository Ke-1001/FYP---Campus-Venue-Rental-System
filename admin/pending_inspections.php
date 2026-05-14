<?php
// File: admin/pending_inspections.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

$filter_bid = trim($_GET['f_bid'] ?? '');
$filter_student = trim($_GET['f_student'] ?? '');
$filter_venue = trim($_GET['f_venue'] ?? '');
$filter_date = trim($_GET['f_date'] ?? '');
$filter_inspector = trim($_GET['f_inspector'] ?? '');

// 💡 1. 關聯查詢升級：引入 vcategory
$sql = "SELECT 
            b.bid, 
            b.date_booked, 
            b.time_start,
            b.time_end,
            b.status AS booking_status,
            u.uid AS student_id,
            u.username AS student_name, 
            v.vname AS venue_name, 
            vc.category AS venue_category,
            s.staff_name AS inspector_name
        FROM inspection i
        JOIN booking b ON i.bid = b.bid
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        JOIN vcategory vc ON v.vcid = vc.vcid
        JOIN staff s ON i.sid = s.sid
        WHERE i.ins_status = 'pending'";

// 💡 2. 過濾條件修正：適配 vc.category
if (!empty($filter_bid)) $sql .= " AND b.bid LIKE '%" . $conn->real_escape_string($filter_bid) . "%'";
if (!empty($filter_student)) $sql .= " AND (u.username LIKE '%" . $conn->real_escape_string($filter_student) . "%' OR u.uid LIKE '%" . $conn->real_escape_string($filter_student) . "%')";
if (!empty($filter_venue)) $sql .= " AND (v.vname LIKE '%" . $conn->real_escape_string($filter_venue) . "%' OR vc.category LIKE '%" . $conn->real_escape_string($filter_venue) . "%')";
if (!empty($filter_date)) $sql .= " AND b.date_booked = '" . $conn->real_escape_string($filter_date) . "'";
if (!empty($filter_inspector)) $sql .= " AND s.staff_name LIKE '%" . $conn->real_escape_string($filter_inspector) . "%'";

$sql .= " ORDER BY b.date_booked ASC, b.time_start ASC";
$result = $conn->query($sql);

// 💡 初始化大馬時區供嚴格比對
$tz = new DateTimeZone('Asia/Kuala_Lumpur');
$now = new DateTime('now', $tz);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Pending Inspections</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { cstyle: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori_forms.css">
</head>
<body class="bg-[#f4f4f4] text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0 bg-white">
            <?php 
            $topbar_content = '
            <div class="flex items-center">
                <a href="inspections.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Operations / Pending Inspections</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-6 flex justify-between items-end">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Pending Inspections</h1>
                    <p class="text-sm text-slate-600 mt-1">Review upcoming physical assessments for post-event venues.</p>
                </div>
            </div>

            <div class="bg-white p-5 rounded-lg shadow-sm border border-slate-200 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Ref ID</label>
                        <input type="text" name="f_bid" value="<?php echo htmlspecialchars($filter_bid); ?>" placeholder="Search BID..." class="fiori-input w-full">
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

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Personnel</label>
                        <input type="text" name="f_inspector" value="<?php echo htmlspecialchars($filter_inspector); ?>" placeholder="Inspector Name..." class="fiori-input w-full">
                    </div>

                    <div class="flex space-x-2">
                        <button type="submit" class="w-full py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition shadow-sm">
                            Apply
                        </button>
                        <a href="pending_inspections.php" class="px-4 py-2 bg-slate-100 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-200 transition flex items-center justify-center" title="Reset">
                            <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
                    <h3 class="text-xs font-black text-slate-600 uppercase tracking-widest">Inspection Queue (<?php echo $result->num_rows; ?>)</h3>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white text-xs text-slate-500 font-black uppercase tracking-widest border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 w-32 text-center">Reference</th>
                            <th class="px-6 py-4 w-48">Student Profile</th>
                            <th class="px-6 py-4 w-64">Asset & Reserved Slot</th>
                            <th class="px-6 py-4 w-40">Assigned Inspector</th>
                            <th class="px-6 py-4 text-right w-40">Execution State</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $time_range = date('H:i', strtotime($row['time_start'])) . ' - ' . date('H:i', strtotime($row['time_end']));
                                
                                // 💡 嚴格的時空防呆 (Strict Spatiotemporal Validation)
                                $start_dt = new DateTime($row['date_booked'] . ' ' . $row['time_start'], $tz);
                                $end_dt = new DateTime($row['date_booked'] . ' ' . $row['time_end'], $tz);
                                
                                $is_ready = false;
                                if ($now >= $end_dt || $row['booking_status'] === 'completed') {
                                    $is_ready = true;
                                } else {
                                    if ($now >= $start_dt && $now < $end_dt) {
                                        $lock_text = "In Use";
                                        $lock_css = "bg-amber-50 text-amber-700 border-amber-200";
                                        $lock_icon = "play-circle";
                                    } else {
                                        $lock_text = "Awaiting Event";
                                        $lock_css = "bg-slate-50 text-slate-500 border-slate-200";
                                        $lock_icon = "clock";
                                    }
                                }
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors align-top">
                                <td class="px-6 py-5">
                                    <span class="font-mono text-sm font-bold block text-center <?php echo $is_ready ? 'text-indigo-700 bg-indigo-50 border border-indigo-200' : 'text-slate-400 bg-slate-50 border border-slate-100'; ?> px-2 py-1 rounded">
                                        <?php echo htmlspecialchars($row['bid']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="font-extrabold text-base block mb-1 <?php echo $is_ready ? 'text-slate-800' : 'text-slate-500'; ?>"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                    <span class="text-xs font-mono font-bold text-slate-500 block"><?php echo htmlspecialchars($row['student_id']); ?></span>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="font-extrabold text-base block mb-1 <?php echo $is_ready ? 'text-slate-800' : 'text-slate-500'; ?>"><?php echo htmlspecialchars($row['venue_name']); ?></span>
                                    <span class="px-2 py-1 bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-wider rounded border border-slate-200 inline-block mb-3"><?php echo htmlspecialchars($row['venue_category']); ?></span>
                                    
                                    <div class="<?php echo $is_ready ? 'bg-blue-50/50 border-blue-100 text-blue-800' : 'bg-slate-50 border-slate-200 text-slate-500'; ?> border p-2 rounded text-xs font-mono font-bold w-fit">
                                        <span class="block mb-1 font-sans text-[10px] uppercase opacity-70">Event Schedule:</span>
                                        <?php echo htmlspecialchars($row['date_booked']); ?> | <?php echo $time_range; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="font-bold text-slate-700 block">
                                        <?php echo htmlspecialchars($row['inspector_name']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <?php if ($is_ready): ?>
                                        <a href="execute_inspection.php?bid=<?php echo urlencode($row['bid']); ?>" class="inline-flex items-center justify-center w-full px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm text-center">
                                            Execute <i data-lucide="chevron-right" class="w-4 h-4 ml-1.5"></i>
                                        </a>
                                    <?php else: ?>
                                        <div class="inline-flex items-center justify-center w-full px-3 py-2 border rounded-lg text-xs font-black uppercase tracking-widest cursor-not-allowed <?php echo $lock_css; ?>" title="Inspection locked. Venue is <?php echo strtolower($lock_text); ?>.">
                                            <i data-lucide="<?php echo $lock_icon; ?>" class="w-4 h-4 mr-1.5"></i> <?php echo $lock_text; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center text-slate-500 font-medium">
                                    <span class="block text-4xl mb-3">🔍</span>
                                    No pending inspections match the criteria.
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