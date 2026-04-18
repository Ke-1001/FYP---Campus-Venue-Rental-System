<?php
// File: admin/manage_admins.php
session_start();
require_once("../config/db.php");

$admins = [];
$sql_admins = "
    SELECT 
        CONCAT('EMP-', LPAD(user_id, 4, '0')) AS id, 
        full_name AS name, 
        email, 
        role, 
        'Active' AS status 
    FROM users 
    WHERE role IN ('Normal_Admin', 'Super_Admin') 
    ORDER BY FIELD(role, 'Super_Admin', 'Normal_Admin'), user_id ASC";

$result = $conn->query($sql_admins);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Identity Governance</title>
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
                    <input type="text" placeholder="Search staff ID or name..." class="bg-transparent border-none outline-none w-64 text-sm focus:ring-0">
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

        <div class="flex-1 overflow-y-auto p-8">
            
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Identity Governance</h1>
                    <p class="text-sm text-slate-500 mt-1">Manage administrative credentials and role-based access levels.</p>
                </div>
                <button class="px-4 py-2 bg-mmu-dark text-white font-bold rounded-lg shadow flex items-center hover:bg-slate-800 transition">
                    <i data-lucide="user-plus" class="w-4 h-4 mr-2 text-mmu-accent"></i> Provision New Staff
                </button>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-widest">
                            <th class="px-6 py-4 border-b border-slate-200">Identifier</th>
                            <th class="px-6 py-4 border-b border-slate-200">Full Name</th>
                            <th class="px-6 py-4 border-b border-slate-200">System Role</th>
                            <th class="px-6 py-4 border-b border-slate-200">Account State</th>
                            <th class="px-6 py-4 border-b border-slate-200 text-right">Revocation / Edit</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-700 divide-y divide-slate-100">
                        <?php foreach($admins as $admin): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs font-bold text-mmu-blue"><?php echo $admin['id']; ?></td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-800"><?php echo htmlspecialchars($admin['name']); ?></span>
                                    <span class="text-[10px] font-mono text-slate-400"><?php echo htmlspecialchars($admin['email']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php if($admin['role'] === 'Super_Admin'): ?>
                                    <span class="px-2 py-1 bg-purple-50 text-purple-700 border border-purple-200 rounded text-[10px] font-black tracking-widest uppercase inline-flex items-center">
                                        <i data-lucide="shield-alert" class="w-3 h-3 mr-1"></i>
                                        Super_Admin
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-slate-100 text-slate-600 border border-slate-200 rounded text-[10px] font-bold tracking-widest uppercase">
                                        Normal_Admin
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if($admin['status'] === 'Active'): ?>
                                    <span class="text-emerald-600 font-bold flex items-center text-xs">
                                        <i data-lucide="check" class="w-3.5 h-3.5 mr-1"></i> ACTIVE
                                    </span>
                                <?php else: ?>
                                    <span class="text-red-500 font-bold flex items-center text-xs">
                                        <i data-lucide="lock" class="w-3.5 h-3.5 mr-1"></i> SUSPENDED
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?php if($admin['role'] !== 'Super_Admin'): ?>
                                    <div class="flex justify-end space-x-2">
                                        <button class="p-1.5 text-slate-400 hover:text-mmu-blue hover:bg-blue-50 border border-transparent hover:border-blue-200 rounded transition">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>
                                        <button class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 border border-transparent hover:border-red-200 rounded transition">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="text-[10px] font-mono text-slate-400 uppercase tracking-widest">Root Immutable</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-8 p-4 bg-amber-50 border border-amber-200 rounded-lg flex items-start">
                <i data-lucide="shield-info" class="w-5 h-5 text-amber-600 mt-0.5 shrink-0"></i>
                <div class="ml-3">
                    <p class="text-xs font-bold text-amber-800 uppercase tracking-wider">Security Directive</p>
                    <p class="text-xs text-amber-700 mt-1">Super Admin accounts cannot be deleted or downgraded through this interface to prevent system lockouts. Email validation is enforced for all new personnel records.</p>
                </div>
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