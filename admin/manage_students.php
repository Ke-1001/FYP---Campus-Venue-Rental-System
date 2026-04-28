<?php
// File: admin/manage_students.php

session_start();
require_once("../config/db.php");
require_once('../includes/admin_auth.php'); 

$students = [];
// 💡 1. 適配新資料庫架構：表格改為 `user`，主鍵改為 `uid`，名稱改為 `username`，新增 `phone_num`
$sql_students = "
    SELECT 
        uid AS raw_id,
        uid AS id, /* uid 現在是 varchar(10)，直接顯示即可 */
        username AS name, 
        email, 
        phone_num, /* 新增電話號碼欄位 */
        'System Default' AS faculty, 
        'General Designation' AS course, 
        'Active' AS status 
    FROM user 
    ORDER BY created_at DESC"; /* 根據創建時間降序排列 */

$result = $conn->query($sql_students);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Student Directory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b', accent: '#38bdf8' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0">
            <?php 
            $topbar_content = '
            <div class="flex items-center text-slate-500 bg-white px-4 py-2 rounded-lg border border-slate-200 focus-within:border-mmu-blue shadow-sm transition-all">
                <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                <input type="text" placeholder="Search system assets..." class="bg-transparent border-none outline-none w-64 text-sm focus:ring-0">
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Student Directory</h1>
                    <p class="text-sm text-slate-500 mt-1">Comprehensive registry of all student entities within the system.</p>
                </div>
                <a href="add_student.php" class="px-4 py-2 bg-mmu-blue text-white font-bold rounded-lg shadow flex items-center hover:bg-blue-700 transition">
                    <i data-lucide="user-plus" class="w-4 h-4 mr-2"></i> Register Student
                </a>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-widest">
                            <th class="px-6 py-4 border-b border-slate-200">Student ID</th>
                            <th class="px-6 py-4 border-b border-slate-200">Entity Details</th>
                            <th class="px-6 py-4 border-b border-slate-200">Contact / Info</th>
                            <th class="px-6 py-4 border-b border-slate-200">System State</th>
                            <th class="px-6 py-4 border-b border-slate-200 text-right">Execution</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-700 divide-y divide-slate-100">
                        <?php foreach($students as $stu): ?>
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-6 py-4 font-mono text-xs font-bold text-mmu-blue"><?php echo htmlspecialchars($stu['id']); ?></td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-800"><?php echo htmlspecialchars($stu['name']); ?></span>
                                    <span class="text-[10px] font-mono text-slate-400 truncate max-w-[200px]"><?php echo htmlspecialchars($stu['email']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-600 text-xs uppercase"><?php echo htmlspecialchars($stu['phone_num']); ?></span>
                                    <span class="text-[10px] text-slate-400 font-medium"><?php echo $stu['course']; ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php 
                                    $statusClass = "bg-slate-100 text-slate-600 border-slate-200";
                                    if($stu['status'] === 'Active') $statusClass = "bg-emerald-50 text-emerald-600 border-emerald-200";
                                    if($stu['status'] === 'Graduated') $statusClass = "bg-blue-50 text-blue-600 border-blue-200";
                                    if($stu['status'] === 'Suspended') $statusClass = "bg-red-50 text-red-600 border-red-200";
                                ?>
                                <span class="px-2 py-0.5 border <?php echo $statusClass; ?> rounded text-[10px] font-black uppercase tracking-tighter">
                                    <?php echo $stu['status']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end space-x-2">
                                    <button onclick="openStudentModal(this)" 
                                            data-id="<?php echo htmlspecialchars($stu['raw_id']); ?>"
                                            data-name="<?php echo htmlspecialchars($stu['name']); ?>"
                                            data-email="<?php echo htmlspecialchars($stu['email']); ?>"
                                            data-phone="<?php echo htmlspecialchars($stu['phone_num']); ?>"
                                            class="p-1.5 text-slate-400 hover:text-mmu-blue hover:bg-blue-50 border border-transparent hover:border-blue-200 rounded transition tooltip" title="Edit Properties">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    <a href="../actions/process_student.php?action=delete&uid=<?php echo urlencode($stu['raw_id']); ?>" 
                                       onclick="triggerCustomConfirm(event, 'CRITICAL WARNING: Terminate student record? This will forcefully cascade and delete all associated bookings.', this.href);"
                                       class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 border border-transparent hover:border-red-200 rounded transition tooltip" title="Revoke Record">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
        </div>
    </main>

    <div id="student-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-200">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="text-lg font-extrabold text-slate-800">Configure Student Properties</h3>
                <button type="button" onclick="closeStudentModal()" class="text-slate-400 hover:text-slate-600 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="../actions/process_student.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="edit">
                
                <input type="hidden" name="uid" id="modal-user-id" value="">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Username / Full Name</label>
                    <input type="text" name="username" id="modal-name" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-mmu-blue focus:ring-1 focus:ring-mmu-blue text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Institutional Email</label>
                    <input type="email" name="email" id="modal-email" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-mmu-blue focus:ring-1 focus:ring-mmu-blue text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Contact Number</label>
                    <input type="text" name="phone_num" id="modal-phone" required placeholder="e.g. 0123456789" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-mmu-blue focus:ring-1 focus:ring-mmu-blue text-sm font-mono">
                </div>
                
                <div class="mt-6 flex justify-end space-x-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeStudentModal()" class="px-4 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100 rounded-lg transition">Cancel</button>
                    <button type="submit" class="px-6 py-2 text-sm font-bold text-white bg-mmu-blue hover:bg-blue-700 rounded-lg transition shadow">Deploy Update</button>
                </div>
            </form>
        </div>
    </div>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();

        function toggleSidebar() {
            document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed');
        }

        // 💡 升級版 JS State Machine 包含電話號碼
        function openStudentModal(btn) {
            document.querySelector('#student-modal form').reset();
            
            document.getElementById('modal-user-id').value = btn.getAttribute('data-id');
            document.getElementById('modal-name').value = btn.getAttribute('data-name');
            document.getElementById('modal-email').value = btn.getAttribute('data-email');
            document.getElementById('modal-phone').value = btn.getAttribute('data-phone');
            
            document.getElementById('student-modal').classList.remove('hidden');
        }

        function closeStudentModal() {
            document.getElementById('student-modal').classList.add('hidden');
        }
    </script>
</body>
</html>