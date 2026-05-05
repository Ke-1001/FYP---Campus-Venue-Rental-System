<?php
// File: admin/manage_students.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

// 💡 1. 提取多維度過濾參數
$filter_query = trim($_GET['f_query'] ?? '');
$filter_sort = trim($_GET['f_sort'] ?? 'newest');

// 💡 2. 建構查詢引擎 (Query Engine for User Table)
$sql = "SELECT uid, username, email, phone_num, created_at FROM user WHERE 1=1";

// 動態過濾：聯集匹配學號、姓名或電子郵件
if (!empty($filter_query)) {
    $sql .= " AND (username LIKE '%" . $conn->real_escape_string($filter_query) . "%' 
                OR uid LIKE '%" . $conn->real_escape_string($filter_query) . "%' 
                OR email LIKE '%" . $conn->real_escape_string($filter_query) . "%')";
}

// 動態排序
if ($filter_sort === 'oldest') {
    $sql .= " ORDER BY created_at ASC";
} elseif ($filter_sort === 'name_asc') {
    $sql .= " ORDER BY username ASC";
} else {
    $sql .= " ORDER BY created_at DESC"; // Default: Newest first
}

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | Student Directory</title>
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
            $topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Identity Management / Student Directory</h2>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-6 flex justify-between items-end">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Student Directory</h1>
                    <p class="text-sm text-slate-500 mt-1">Manage registered student accounts and review their contact vectors.</p>
                </div>
                <div>
                    <a href="add_student.php" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl shadow-sm hover:bg-indigo-700 transition flex items-center transform active:scale-95">
                        <i data-lucide="user-plus" class="w-4 h-4 mr-2"></i> Register Student
                    </a>
                </div>
            </div>

            <!-- 💡 多維度過濾矩陣 (Multi-dimensional Filter Matrix) -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Identity Query</label>
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                            <input type="text" name="f_query" value="<?php echo htmlspecialchars($filter_query); ?>" placeholder="Search UID, Name, or Email..." class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none transition-colors">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Temporal Sorting</label>
                        <select name="f_sort" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none bg-white font-medium text-slate-700 transition-colors">
                            <option value="newest" <?php if($filter_sort==='newest') echo 'selected'; ?>>Newest Registered</option>
                            <option value="oldest" <?php if($filter_sort==='oldest') echo 'selected'; ?>>Oldest Registered</option>
                            <option value="name_asc" <?php if($filter_sort==='name_asc') echo 'selected'; ?>>Alphabetical (A-Z)</option>
                        </select>
                    </div>

                    <div class="flex space-x-2">
                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition shadow-sm flex items-center justify-center">
                            <i data-lucide="filter" class="w-4 h-4 mr-1"></i> Apply
                        </button>
                        <a href="manage_students.php" class="px-4 py-2 bg-slate-100 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-200 transition flex items-center justify-center" title="Reset Filters">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- 💡 資料名錄 -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                    <h3 class="text-[11px] font-black text-slate-500 uppercase tracking-widest">Student Population Index (<?php echo $result->num_rows; ?>)</h3>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white text-[10px] text-slate-400 font-black uppercase tracking-widest border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3">Reference (UID)</th>
                            <th class="px-6 py-3">Student Profile</th>
                            <th class="px-6 py-3">Contact Vectors</th>
                            <th class="px-6 py-3">Account State</th>
                            <th class="px-6 py-3 text-right">Execution</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-50">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-mono font-bold text-indigo-600"><?php echo htmlspecialchars($row['uid']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-800 block"><?php echo htmlspecialchars($row['username']); ?></span>
                                    <span class="text-[9px] text-slate-400 font-mono uppercase mt-1 block">Joined: <?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="text-xs text-slate-600 hover:text-indigo-600 transition-colors block font-medium">
                                        <i data-lucide="mail" class="w-3 h-3 inline pb-0.5"></i> <?php echo htmlspecialchars($row['email']); ?>
                                    </a>
                                    <span class="text-xs text-slate-500 font-mono mt-1 block">
                                        <i data-lucide="phone" class="w-3 h-3 inline pb-0.5"></i> <?php echo htmlspecialchars($row['phone_num']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded text-[10px] font-black uppercase tracking-widest flex items-center w-max">
                                        <i data-lucide="check-circle-2" class="w-3 h-3 mr-1.5"></i> Active
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="edit_student.php?uid=<?php echo urlencode($row['uid']); ?>" class="p-2 text-indigo-500 hover:bg-indigo-50 rounded-lg transition" title="Modify Record">
                                            <i data-lucide="settings-2" class="w-4 h-4"></i>
                                        </a>
                                        <a href="delete_student.php?uid=<?php echo urlencode($row['uid']); ?>" class="p-2 text-red-400 hover:bg-red-50 hover:text-red-600 rounded-lg transition" title="Purge Record" onclick="return confirm('CRITICAL WARNING: This will permanently purge the student and ALL their associated booking/inspection records. Proceed?');">
                                            <i data-lucide="user-minus" class="w-4 h-4"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium">
                                    <i data-lucide="users" class="w-8 h-8 mx-auto text-slate-300 mb-3 opacity-50"></i>
                                    Query returned zero vectors. No students match the criteria.
                                </td>
                            </tr>
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