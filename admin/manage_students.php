<?php
// File: admin/manage_students.php
session_start();
require_once("../config/db.php");

$students = [];
$sql_students = "
    SELECT 
        CONCAT('STU-', LPAD(user_id, 4, '0')) AS id, 
        full_name AS name, 
        email, 
        'System Default' AS faculty, 
        'General Designation' AS course, 
        'Active' AS status 
    FROM users 
    WHERE role = 'User' OR role = '' 
    ORDER BY user_id ASC";

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
                    <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                    <input type="text" placeholder="Search by Student ID, Name or Faculty..." class="bg-transparent border-none outline-none w-80 text-sm focus:ring-0">
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
                    <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Student Directory</h1>
                    <p class="text-sm text-slate-500 mt-1">Comprehensive registry of all student entities within the system.</p>
                </div>
                <button class="px-4 py-2 bg-mmu-blue text-white font-bold rounded-lg shadow flex items-center hover:bg-blue-700 transition">
                    <i data-lucide="user-plus" class="w-4 h-4 mr-2"></i> Register Student
                </button>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-widest">
                            <th class="px-6 py-4 border-b border-slate-200">Student ID</th>
                            <th class="px-6 py-4 border-b border-slate-200">Full Name</th>
                            <th class="px-6 py-4 border-b border-slate-200">Faculty / Course</th>
                            <th class="px-6 py-4 border-b border-slate-200">System State</th>
                            <th class="px-6 py-4 border-b border-slate-200 text-right">Execution</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-700 divide-y divide-slate-100">
                        <?php foreach($students as $stu): ?>
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-6 py-4 font-mono text-xs font-bold text-mmu-blue"><?php echo $stu['id']; ?></td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-800"><?php echo htmlspecialchars($stu['name']); ?></span>
                                    <span class="text-[10px] font-mono text-slate-400 truncate max-w-[200px]"><?php echo htmlspecialchars($stu['email']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-600 text-xs uppercase"><?php echo $stu['faculty']; ?></span>
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
                                    <button class="p-1.5 text-slate-400 hover:text-mmu-blue hover:bg-blue-50 border border-transparent hover:border-blue-200 rounded transition">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    <button class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 border border-transparent hover:border-red-200 rounded transition">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-between items-center text-xs font-bold text-slate-400 uppercase tracking-widest">
                <span>Total Active Student Records: <?php echo count($students); ?></span>
            </div>

        </div>
    </main>

    <script>
        lucide.createIcons();

        function toggleSidebar() {
            const sidebar = document.getElementById('system-sidebar');
            sidebar.classList.toggle('sidebar-collapsed');
        }
    </script>
</body>
</html>