<?php
// File: admin/staff_directory.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

$filter_query = trim($_GET['f_query'] ?? '');
$filter_role = trim($_GET['f_role'] ?? '');

$base_sql = "
    SELECT * FROM (
        SELECT 
            'Admin' AS entity_type,
            aid AS entity_id,
            admin_name AS name,
            email,
            phone_num,
            role AS access_level,
            created_at,
            status
        FROM admin
        
        UNION ALL
        
        SELECT 
            'Staff' AS entity_type,
            sid AS entity_id,
            staff_name AS name,
            email,
            phone_num,
            position AS access_level,
            created_at,
            status
        FROM staff
    ) AS unified_directory
    WHERE 1=1
";

if (!empty($filter_query)) {
    $safe_query = $conn->real_escape_string($filter_query);
    $base_sql .= " AND (name LIKE '%$safe_query%' OR entity_id LIKE '%$safe_query%' OR email LIKE '%$safe_query%')";
}
if (!empty($filter_role)) {
    $base_sql .= " AND access_level = '" . $conn->real_escape_string($filter_role) . "'";
}

$base_sql .= " ORDER BY entity_type ASC, access_level ASC, name ASC";
$result = $conn->query($base_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | Unified Staff Directory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { cstyle: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="../assets/css/layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori_forms.css">
</head>
<body class="bg-[#f4f4f4] text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0 bg-white">
            <?php 
            $topbar_content = '
            <div class="flex items-center">
                <a href="manage_admins.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Identity Management / Staff Directory</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-6 flex justify-between items-end">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Manage Existing Staff</h1>
                    <p class="text-xs text-slate-500 mt-1">Manage existing staff details and status.</p>
                </div>
                <a href="add_staff.php" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-lg shadow-sm hover:bg-indigo-700 transition flex items-center">
                    Register New Staff
                </a>
            </div>

            <div class="bg-white p-5 rounded-lg shadow-sm border border-slate-200 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Identity</label>
                        <input type="text" name="f_query" value="<?php echo htmlspecialchars($filter_query); ?>" placeholder="Search ID, Name, or Email..." class="fiori-input w-full">
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Access Privilege</label>
                        <div class="relative">
                            <select name="f_role" class="fiori-input w-full appearance-none pr-8 bg-white cursor-pointer font-bold text-slate-700">
                                <option value="">All Roles</option>
                                <optgroup label="System Core">
                                    <option value="super_admin" <?php if($filter_role==='super_admin') echo 'selected'; ?>>Super Admin</option>
                                    <option value="admin" <?php if($filter_role==='admin') echo 'selected'; ?>>Standard Admin</option>
                                </optgroup>
                                <optgroup label="Operations">
                                    <option value="inspector" <?php if($filter_role==='inspector') echo 'selected'; ?>>Inspector</option>
                                </optgroup>
                            </select>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 absolute right-3 top-2.5 pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="flex space-x-2">
                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition flex items-center justify-center shadow-sm">
                            Apply
                        </button>
                        <a href="staff_directory.php" class="px-4 py-2 bg-slate-100 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-200 transition flex items-center justify-center" title="Reset Filters">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="text-[11px] font-black text-slate-500 uppercase tracking-widest">Personnel (<?php echo $result->num_rows; ?>)</h3>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white text-[10px] text-slate-400 font-black uppercase tracking-widest border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3">Reference ID</th>
                            <th class="px-6 py-3">Personnel Profile</th>
                            <th class="px-6 py-3">Contact Vectors</th>
                            <th class="px-6 py-3">Authorization Level</th>
                            <th class="px-6 py-3 text-right">Execution</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-50">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $is_admin = ($row['entity_type'] === 'Admin');
                                $id_prefix = $is_admin ? 'aid=' : 'sid=';
                                $edit_target = $is_admin ? 'edit_admin.php' : 'edit_staff.php';
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors <?php echo ($row['status'] === 'inactive') ? 'bg-slate-50/50 opacity-60' : ''; ?>">
                                <td class="px-6 py-4 font-mono font-bold text-slate-500"><?php echo htmlspecialchars($row['entity_id']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-800 block"><?php echo htmlspecialchars($row['name']); ?></span>
                                    <span class="text-[9px] text-slate-400 font-mono uppercase mt-1 block">Joined: <?php echo date('M Y', strtotime($row['created_at'])); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="text-xs text-indigo-600 hover:underline block font-medium">
                                        <?php echo htmlspecialchars($row['email']); ?>
                                    </a>
                                    <span class="text-xs text-slate-500 font-mono mt-1 block">
                                        <?php echo htmlspecialchars($row['phone_num']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php 
                                    $role_ui = match($row['access_level']) {
                                        'super_admin' => ['bg-purple-50 text-purple-700 border-purple-200', 'Super Admin'],
                                        'admin'       => ['bg-indigo-50 text-indigo-700 border-indigo-200', 'Admin'],
                                        'inspector'   => ['bg-amber-50 text-amber-700 border-amber-200', 'Inspector'],
                                        default       => ['bg-slate-100 text-slate-600 border-slate-200', 'Unknown']
                                    };
                                    ?>
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2.5 py-1 border <?php echo $role_ui[0]; ?> rounded text-[10px] font-black uppercase tracking-widest">
                                            <?php echo $role_ui[1]; ?>
                                        </span>
                                        
                                        <?php if ($row['status'] === 'inactive') :?>
                                            <span class="px-2 py-1 bg-slate-200 text-slate-500 border border-slate-300 rounded text-[9px] font-black uppercase tracking-widest">
                                                Inactive
                                            </span>
                                        <?php endif;?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="<?php echo $edit_target . '?' . $id_prefix . $row['entity_id']; ?>" class="p-2 text-indigo-500 hover:bg-indigo-50 rounded transition" title="Modify Privilege">
                                            <i data-lucide="settings-2" class="w-4 h-4"></i>
                                        </a>
                                        
                                        <?php if ($row['status'] === 'active'): ?>
                                            <a href="../actions/process_personnel_deletion.php?type=<?php echo strtolower($row['entity_type']); ?>&id=<?php echo $row['entity_id']; ?>" 
                                                class="p-2 text-red-400 hover:bg-red-50 hover:text-red-600 rounded transition" 
                                                onclick="return confirm('CRITICAL WARNING: Executing conditional deletion. If traces exist, entity will be deactivated. Proceed?');" title="Deactivate / Purge">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </a>
                                        <?php else: ?>
                                            <button disabled class="p-2 text-slate-300 cursor-not-allowed" title="Account already inactive">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium"><i data-lucide="users" class="w-8 h-8 mx-auto text-slate-300 mb-3 opacity-50"></i>No personnel match the selected criteria.</td></tr>
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