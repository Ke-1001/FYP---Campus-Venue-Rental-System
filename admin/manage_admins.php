<?php
// File: admin/manage_admins.php

session_start();
require_once("../config/db.php");
require_once('../includes/admin_auth.php'); // 💡 注入安全閘道器 (已內建 session_start)
require_once("../config/db.php");

$admins = [];
$sql_admins = "
    SELECT 
        user_id AS raw_id,
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

        <div class="flex-1 overflow-y-auto p-8">
            
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Identity Governance</h1>
                    <p class="text-sm text-slate-500 mt-1">Manage administrative credentials and role-based access levels.</p>
                </div>
                <a href="add_admin.php" class="px-4 py-2 bg-mmu-dark text-white font-bold rounded-lg shadow flex items-center hover:bg-slate-800 transition">
                    <i data-lucide="user-plus" class="w-4 h-4 mr-2 text-mmu-accent"></i> Provision New Staff
                </a>
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
                                        <i data-lucide="shield-alert" class="w-3 h-3 mr-1"></i> Super_Admin
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-slate-100 text-slate-600 border border-slate-200 rounded text-[10px] font-bold tracking-widest uppercase">
                                        Normal_Admin
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-emerald-600 font-bold flex items-center text-xs">
                                    <i data-lucide="check" class="w-3.5 h-3.5 mr-1"></i> ACTIVE
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?php if($admin['role'] !== 'Super_Admin'): ?>
                                    <div class="flex justify-end space-x-2">
                                        <button onclick="openAdminModal(this)"
                                                data-id="<?php echo $admin['raw_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($admin['name']); ?>"
                                                data-email="<?php echo htmlspecialchars($admin['email']); ?>"
                                                class="p-1.5 text-slate-400 hover:text-mmu-blue hover:bg-blue-50 border border-transparent hover:border-blue-200 rounded transition tooltip" title="Configure Profile">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>
                                        <a href="../actions/process_admin.php?action=delete&id=<?php echo $admin['raw_id']; ?>" 
                                           onclick="triggerCustomConfirm(event, 'CRITICAL WARNING: Revoke administrative privileges? This action cannot be undone.', this.href);"
                                           class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 border border-transparent hover:border-red-200 rounded transition tooltip" title="Revoke Privilege">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </a>
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

    <div id="admin-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-200">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="text-lg font-extrabold text-slate-800">Configure Identity</h3>
                <button type="button" onclick="closeAdminModal()" class="text-slate-400 hover:text-slate-600 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="../actions/process_admin.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="modal-admin-id" value="">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Entity Full Name</label>
                    <input type="text" name="full_name" id="modal-admin-name" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-mmu-blue focus:ring-1 focus:ring-mmu-blue text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Institutional Email</label>
                    <input type="email" name="email" id="modal-admin-email" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-mmu-blue focus:ring-1 focus:ring-mmu-blue text-sm font-mono">
                </div>
                
                <div class="mt-6 flex justify-end space-x-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeAdminModal()" class="px-4 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100 rounded-lg transition">Abort</button>
                    <button type="submit" class="px-6 py-2 text-sm font-bold text-white bg-mmu-dark hover:bg-slate-800 rounded-lg transition shadow">Update Profile</button>
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

        function openAdminModal(btn) {
            document.querySelector('#admin-modal form').reset();
            document.getElementById('modal-admin-id').value = btn.getAttribute('data-id');
            document.getElementById('modal-admin-name').value = btn.getAttribute('data-name');
            document.getElementById('modal-admin-email').value = btn.getAttribute('data-email');
            
            document.getElementById('admin-modal').classList.remove('hidden');
        }

        function closeAdminModal() {
            document.getElementById('admin-modal').classList.add('hidden');
        }
    </script>
</body>
</html>