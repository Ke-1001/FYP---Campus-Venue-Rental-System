<?php
// File: admin/venue_directory.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

// 💡 1. 接收多維度過濾參數
$filter_name = trim($_GET['f_name'] ?? '');
$filter_cat = trim($_GET['f_cat'] ?? '');
$filter_status = trim($_GET['f_status'] ?? '');

// 💡 2. 動態提取類別字典 (預測性輸入引擎)
$cat_dict_sql = "SELECT DISTINCT UPPER(TRIM(category)) AS category_name 
                 FROM venue 
                 WHERE category IS NOT NULL AND category != '' 
                 ORDER BY category_name ASC";
$cat_dict_result = $conn->query($cat_dict_sql);

// 💡 3. 建構多維度查詢引擎 (Multi-dimensional Query Engine)
$sql = "SELECT * FROM venue WHERE 1=1";

if (!empty($filter_name)) {
    $sql .= " AND vname LIKE '%" . $conn->real_escape_string($filter_name) . "%'";
}
if (!empty($filter_cat)) {
    $sanitized_cat = strtoupper($filter_cat);
    $sql .= " AND UPPER(category) LIKE '%" . $conn->real_escape_string($sanitized_cat) . "%'";
}
if (!empty($filter_status)) {
    $sql .= " AND status = '" . $conn->real_escape_string($filter_status) . "'";
}

$sql .= " ORDER BY vname ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | Venue Directory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori-tile.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0">
            <?php 
            $topbar_content = '
            <div class="flex items-center">
                <a href="manage_venues.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Asset Management / Directory</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-6">
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Venue Registry Directory</h1>
                <p class="text-xs text-slate-500 mt-1">Search, filter, and manage existing venues.</p>
            </div>

            <!-- 💡 標準化多維度過濾矩陣 (Standardized Filter Matrix) -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Venue </label>
                        <input type="text" name="f_name" value="<?php echo htmlspecialchars($filter_name); ?>" placeholder="Search venue name..." class="w-full px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none transition-all">
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Category</label>
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                            <input list="category-suggestions" name="f_cat" value="<?php echo htmlspecialchars($filter_cat); ?>" oninput="this.value = this.value.toUpperCase()" placeholder="Type or select keyword..." class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none transition-all uppercase font-medium text-indigo-700">
                            <datalist id="category-suggestions">
                                <?php 
                                if ($cat_dict_result && $cat_dict_result->num_rows > 0) {
                                    while ($dict = $cat_dict_result->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($dict['category_name']) . '"></option>';
                                    }
                                }
                                ?>
                            </datalist>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Venue State</label>
                        <select name="f_status" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none bg-white font-medium text-slate-700">
                            <option value="">All States</option>
                            <option value="available" <?php if($filter_status==='available') echo 'selected'; ?>>Available</option>
                            <option value="maintenance" <?php if($filter_status==='maintenance') echo 'selected'; ?>>Maintenance</option>
                            <option value="booked" <?php if($filter_status==='booked') echo 'selected'; ?>>Booked</option>
                        </select>
                    </div>

                    <div class="flex space-x-2">
                        <button type="submit" class="w-full px-4 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition shadow-sm flex items-center justify-center">
                            <i data-lucide="filter" class="w-4 h-4 mr-2"></i> Apply
                        </button>
                        <a href="venue_directory.php" class="px-4 py-2.5 bg-slate-100 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-200 transition flex items-center justify-center" title="Reset Filters">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </a>
                    </div>

                </form>
            </div>

            <!-- 💡 名錄矩陣 -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="text-[11px] font-black text-slate-500 uppercase tracking-widest">Venue Index (<?php echo $result->num_rows; ?>)</h3>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white text-[10px] text-slate-400 font-black uppercase tracking-widest border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4">Venue</th>
                            <th class="px-6 py-4">Category</th>
                            <th class="px-6 py-4">Capacity</th>
                            <th class="px-6 py-4">Deposit</th>
                            <th class="px-6 py-4">Current State</th>
                            <th class="px-6 py-4 text-right">Execution</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-50">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-800"><?php echo htmlspecialchars($row['vname']); ?></td>
                                <td class="px-6 py-4 font-mono font-bold text-indigo-600 text-xs tracking-wider">
                                    <?php echo htmlspecialchars(strtoupper($row['category'])); ?>
                                </td>
                                <td class="px-6 py-4 font-mono text-slate-600"><?php echo $row['max_cap']; ?> <span class="text-[10px] uppercase tracking-widest text-slate-400 ml-1">Pax</span></td>
                                <td class="px-6 py-4 font-mono font-bold text-emerald-600">RM <?php echo number_format($row['deposit'], 2); ?></td>
                                <td class="px-6 py-4">
                                    <?php 
                                    $status_css = match($row['status']) {
                                        'available' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                        'maintenance' => 'bg-red-50 text-red-600 border-red-100',
                                        'booked' => 'bg-blue-50 text-blue-600 border-blue-100',
                                        default => 'bg-slate-50 text-slate-600 border-slate-200'
                                    };
                                    ?>
                                    <span class="px-2 py-1 rounded text-[10px] font-black uppercase tracking-widest border <?php echo $status_css; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="edit_venue.php?vid=<?php echo $row['vid']; ?>" class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-indigo-600 text-xs font-bold rounded-lg hover:bg-indigo-50 hover:border-indigo-200 transition-all shadow-sm">
                                        <i data-lucide="edit-3" class="w-3 h-3 mr-1.5"></i> Modify
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-medium">
                                    <i data-lucide="search-x" class="w-8 h-8 mx-auto text-slate-300 mb-3 opacity-50"></i>
                                    No venues match the specified parameters.
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