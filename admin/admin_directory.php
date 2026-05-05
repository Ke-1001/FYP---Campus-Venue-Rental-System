<?php
// File: admin/admin_directory.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

// 💡 提取多維度過濾參數
$filter_query = trim($_GET['f_query'] ?? '');
$filter_role = trim($_GET['f_role'] ?? '');

// 💡 建構查詢引擎
$sql = "SELECT aid, admin_name, email, phone_num, role, created_at FROM admin WHERE 1=1";

if (!empty($filter_query)) {
    $sql .= " AND (admin_name LIKE '%" . $conn->real_escape_string($filter_query) . "%' OR aid LIKE '%" . $conn->real_escape_string($filter_query) . "%' OR email LIKE '%" . $conn->real_escape_string($filter_query) . "%')";
}
if (!empty($filter_role)) {
    $sql .= " AND role = '" . $conn->real_escape_string($filter_role) . "'";
}

$sql .= " ORDER BY role DESC, admin_name ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | Admin Directory</title>
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
        
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0">
            <?php 
            $topbar_content = '
            <div class="flex items-center">
                <a href="manage_admins.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Personnel Management / Admin Directory</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-6 flex justify-between items-end">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">System Administrators</h1>
                    <p class="text-xs text-slate-500 mt-1">Manage core system personnel and access privileges.</p>
                </div>
            </div>

            <!-- 💡 多維度過濾矩陣 -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Identity Query</label>
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                            <input type="text" name="f_query" value="<?php echo htmlspecialchars($filter_query); ?>" placeholder="Search AID, Name, or Email..." class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Access Level</label>
                        <select name="f_role" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none bg-white font-medium text-slate-700">
                            <option value="">All Roles</option>
                            <option value="super_admin" <?php if($filter_role==='super_admin') echo 'selected'; ?>>Super Admin</option>
                            <option value="admin" <?php if($filter_role==='admin') echo 'selected'; ?>>Standard Admin</option>
                        </select>
                    </div>

                    <div class="flex space-x-2">
                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition flex items-center justify-center">
                            <i data-lucide="filter" class="w-4 h-4 mr-1"></i> Apply
                        </button>
                        <a href="admin_directory.php" class="px-4 py-2 bg-slate-100 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-200 transition flex items-center justify-center" title="Reset Filters">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- 💡 資料名錄 -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                    <h3 class="text-[11px] font-black text-slate-500 uppercase tracking-widest">Administrator Index (<?php echo $result->num_rows; ?>)</h3>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white text-[10px] text-slate-400 font-black uppercase tracking-widest border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3">Reference (AID)</th>
                            <th class="px-6 py-3">Personnel Profile</th>
                            <th class="px-6 py-3">Contact Vectors</th>
                            <th class="px-6 py-3">Privilege Level</th>
                            <th class="px-6 py-3 text-right">Execution</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-50">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-mono font-bold text-slate-600"><?php echo htmlspecialchars($row['aid']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-800 block"><?php echo htmlspecialchars($row['admin_name']); ?></span>
                                    <span class="text-[9px] text-slate-400 font-mono uppercase mt-1">Joined: <?php echo date('M Y', strtotime($row['created_at'])); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="text-xs text-indigo-600 hover:underline block font-medium">
                                        <i data-lucide="mail" class="w-3 h-3 inline pb-0.5"></i> <?php echo htmlspecialchars($row['email']); ?>
                                    </a>
                                    <span class="text-xs text-slate-500 font-mono mt-1 block">
                                        <i data-lucide="phone" class="w-3 h-3 inline pb-0.5"></i> <?php echo htmlspecialchars($row['phone_num']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($row['role'] === 'super_admin'): ?>
                                        <span class="px-2 py-1 bg-purple-50 text-purple-700 border border-purple-200 rounded text-[10px] font-black uppercase tracking-widest flex items-center w-max">
                                            <i data-lucide="key" class="w-3 h-3 mr-1.5"></i> Super Admin
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-slate-100 text-slate-600 border border-slate-200 rounded text-[10px] font-black uppercase tracking-widest flex items-center w-max">
                                            Admin
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="edit_admin.php?aid=<?php echo $row['aid']; ?>" class="p-2 text-indigo-500 hover:bg-indigo-50 rounded transition" title="Modify Privilege">
                                            <i data-lucide="settings-2" class="w-4 h-4"></i>
                                        </a>
                                        <a href="delete_admin.php?aid=<?php echo $row['aid']; ?>" class="p-2 text-red-400 hover:bg-red-50 hover:text-red-600 rounded transition" title="Revoke Access" onclick="return confirm('CRITICAL WARNING: Revoking access for this administrator. Proceed?');">
                                            <i data-lucide="user-minus" class="w-4 h-4"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium"><i data-lucide="search-x" class="w-8 h-8 mx-auto text-slate-300 mb-3 opacity-50"></i>No administrators match criteria.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <script>
        lucide.createIcons();
        function toggleSidebar() { document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed'); }
    </script>
</body>
</html>