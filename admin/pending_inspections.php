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

$sql = "SELECT 
            b.bid, 
            b.date_booked, 
            b.time_start,
            b.time_end,
            b.status AS booking_status,
            u.uid AS student_id,
            u.username AS student_name, 
            v.vname AS venue_name, 
            v.category AS venue_category,
            s.staff_name AS inspector_name
        FROM inspection i
        JOIN booking b ON i.bid = b.bid
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        JOIN staff s ON i.sid = s.sid
        WHERE i.ins_status = 'pending'";

if (!empty($filter_bid)) $sql .= " AND b.bid LIKE '%" . $conn->real_escape_string($filter_bid) . "%'";
if (!empty($filter_student)) $sql .= " AND (u.username LIKE '%" . $conn->real_escape_string($filter_student) . "%' OR u.uid LIKE '%" . $conn->real_escape_string($filter_student) . "%')";
if (!empty($filter_venue)) $sql .= " AND (v.vname LIKE '%" . $conn->real_escape_string($filter_venue) . "%' OR v.category LIKE '%" . $conn->real_escape_string($filter_venue) . "%')";
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
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        
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

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-6 flex justify-between items-end">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Pending Inspections</h1>
                    <p class="text-xs text-slate-500 mt-1">Review upcoming physical assessments.</p>
                </div>
            </div>

            <!-- 💡 多維度過濾矩陣 -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Booking ID</label>
                        <input type="text" name="f_bid" value="<?php echo htmlspecialchars($filter_bid); ?>" placeholder="Search ID..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Student Entity</label>
                        <input type="text" name="f_student" value="<?php echo htmlspecialchars($filter_student); ?>" placeholder="Name or Student ID..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Asset</label>
                        <input type="text" name="f_venue" value="<?php echo htmlspecialchars($filter_venue); ?>" placeholder="Venue or Category..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Date</label>
                        <input type="date" name="f_date" value="<?php echo htmlspecialchars($filter_date); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none text-slate-600">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Personnel</label>
                        <input type="text" name="f_inspector" value="<?php echo htmlspecialchars($filter_inspector); ?>" placeholder="Inspector Name..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none">
                    </div>

                    <div class="flex space-x-2">
                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition shadow-sm flex items-center justify-center">
                            <i data-lucide="filter" class="w-4 h-4 mr-1"></i> Apply
                        </button>
                        <a href="pending_inspections.php" class="px-4 py-2 bg-slate-100 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-200 transition flex items-center justify-center" title="Reset Filters">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </a>
                    </div>

                </form>
            </div>

            <!-- 💡 擴展資料表格 -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="text-[11px] font-black text-slate-500 uppercase tracking-widest">Inspection Queue (<?php echo $result->num_rows; ?>)</h3>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white text-[10px] text-slate-400 font-black uppercase tracking-widest border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3">Reference</th>
                            <th class="px-6 py-3">Student Context</th>
                            <th class="px-6 py-3">Asset Node & Time</th>
                            <th class="px-6 py-3">Assigned Inspector</th>
                            <th class="px-6 py-3 text-right">Execution State</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-50">
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
                                        $lock_css = "bg-amber-50 text-amber-600 border-amber-200";
                                        $lock_icon = "play-circle";
                                    } else {
                                        // 💡 修復未開始預約的 Bug，徹底上鎖
                                        $lock_text = "Awaiting";
                                        $lock_css = "bg-slate-50 text-slate-400 border-slate-200";
                                        $lock_icon = "clock";
                                    }
                                }
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-mono text-xs font-bold px-2 py-1 rounded border <?php echo $is_ready ? 'bg-indigo-50 text-indigo-600 border-indigo-100' : 'bg-slate-50 text-slate-400 border-slate-100'; ?>">
                                        <?php echo htmlspecialchars($row['bid']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold block <?php echo $is_ready ? 'text-slate-800' : 'text-slate-500'; ?>"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                    <span class="text-[10px] font-mono text-slate-400 block"><?php echo htmlspecialchars($row['student_id']); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center mb-1">
                                        <span class="font-bold mr-2 <?php echo $is_ready ? 'text-slate-700' : 'text-slate-500'; ?>"><?php echo htmlspecialchars($row['venue_name']); ?></span>
                                        <span class="px-1.5 py-0.5 bg-slate-100 text-slate-500 text-[9px] font-bold uppercase tracking-wider rounded border border-slate-200"><?php echo htmlspecialchars(strtoupper($row['venue_category'])); ?></span>
                                    </div>
                                    <span class="text-xs font-mono <?php echo $is_ready ? 'text-slate-500' : 'text-slate-400'; ?>"><i data-lucide="calendar" class="w-3 h-3 inline pb-0.5"></i> <?php echo htmlspecialchars($row['date_booked']); ?> | <?php echo $time_range; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-600 flex items-center">
                                        <i data-lucide="user-check" class="w-3.5 h-3.5 mr-1.5 text-slate-400"></i>
                                        <?php echo htmlspecialchars($row['inspector_name']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <?php if ($is_ready): ?>
                                        <button onclick="window.location.href='execute_inspection.php?bid=<?php echo urlencode($row['bid']); ?>'" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition shadow-sm transform active:scale-95 ml-auto">
                                            Execute <i data-lucide="chevron-right" class="w-3 h-3 ml-1.5"></i>
                                        </button>
                                    <?php else: ?>
                                        <div class="inline-flex items-center px-3 py-1.5 border rounded-lg text-[10px] font-black uppercase tracking-widest cursor-not-allowed <?php echo $lock_css; ?>" title="Inspection locked. Venue is <?php echo strtolower($lock_text); ?>.">
                                            <i data-lucide="<?php echo $lock_icon; ?>" class="w-3 h-3 mr-1.5"></i> <?php echo $lock_text; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500 font-medium">
                                    <i data-lucide="search-x" class="w-12 h-12 mx-auto text-slate-300 mb-3 opacity-50"></i>
                                    No pending inspections match criteria.
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