<?php
// File: admin/venue_directory.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

$filter_name = $_GET['f_name'] ?? '';
$filter_cat = $_GET['f_cat'] ?? '';

$sql = "SELECT * FROM venue WHERE 1=1";
if ($filter_name) $sql .= " AND vname LIKE '%" . $conn->real_escape_string($filter_name) . "%'";
if ($filter_cat) $sql .= " AND category = '" . $conn->real_escape_string($filter_cat) . "'";
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
        
            <?php 
            $topbar_content = '
            <div class="flex items-center">
                <a href="manage_venues.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Asset Management / Directory</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-6">
                <form method="GET" class="flex flex-wrap md:flex-nowrap gap-4 items-end">
                    <div class="w-full md:w-1/3">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Venue Name</label>
                        <input type="text" name="f_name" value="<?php echo htmlspecialchars($filter_name); ?>" placeholder="Search name..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none">
                    </div>
                    <div class="w-full md:w-1/3">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Category</label>
                        <select name="f_cat" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none bg-white">
                            <option value="">All Categories</option>
                            <option value="Hall" <?php if($filter_cat==='Hall') echo 'selected'; ?>>Hall</option>
                            <option value="Lab" <?php if($filter_cat==='Lab') echo 'selected'; ?>>Lab</option>
                            <option value="Classroom" <?php if($filter_cat==='Classroom') echo 'selected'; ?>>Classroom</option>
                        </select>
                    </div>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition shadow-sm">Go</button>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 text-[10px] text-slate-400 font-black uppercase tracking-widest border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4">Venue Name</th>
                            <th class="px-6 py-4">Category</th>
                            <th class="px-6 py-4">Capacity</th>
                            <th class="px-6 py-4">Deposit</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-50">
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-700"><?php echo htmlspecialchars($row['vname']); ?></td>
                            <td class="px-6 py-4 text-slate-500"><?php echo htmlspecialchars($row['category']); ?></td>
                            <td class="px-6 py-4 font-mono"><?php echo $row['max_cap']; ?> Pax</td>
                            <td class="px-6 py-4 font-mono font-bold text-emerald-600">RM <?php echo number_format($row['deposit'], 2); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded text-[10px] font-black uppercase tracking-wider border <?php echo ($row['status']==='available') ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-red-50 text-red-600 border-red-100'; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="edit_venue.php?vid=<?php echo $row['vid']; ?>" class="inline-flex items-center text-indigo-600 font-bold hover:underline">
                                    <i data-lucide="edit-3" class="w-4 h-4 mr-1"></i> Edit
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
        function toggleSidebar() {
            document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed');
        }
    </script>
</body>
</html>