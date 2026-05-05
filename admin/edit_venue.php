<?php
// File: admin/edit_venue.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

$vid = intval($_GET['vid'] ?? 0);

if ($vid === 0) {
    die("Error: NULL pointer reference for Venue ID.");
}

$stmt = $conn->prepare("SELECT * FROM venue WHERE vid = ? LIMIT 1");
$stmt->bind_param("i", $vid);
$stmt->execute();
$venue = $stmt->get_result()->fetch_assoc();

if (!$venue) {
    die("Anomaly: Venue object not found in persistent storage.");
}

// 💡 戰術回滾：使用 DISTINCT 從現有場地中投影出類別集合
$cat_sql = "SELECT DISTINCT category FROM venue WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
$categories_result = $conn->query($cat_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | Edit Venue</title>
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
                <a href="venue_directory.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Asset Management / Edit Node</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth flex justify-center">
            <div class="w-full max-w-2xl">
                
                <div class="mb-8 flex justify-between items-end">
                    <div>
                        <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Edit Asset: <?php echo $vid; ?></h1>
                        <p class="text-sm text-slate-500 mt-1">Modify the state vector of the selected venue node.</p>
                    </div>
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full text-[10px] font-black uppercase tracking-widest border border-indigo-100 shadow-sm">
                        Node ID: <?php echo $vid; ?>
                    </span>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-8 py-4 bg-slate-50 border-b border-slate-100">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Mutation Details</h3>
                    </div>

                    <form action="../actions/process_venue.php" method="POST" class="p-8 space-y-6">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="vid" value="<?php echo $vid; ?>">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wide">Venue Name</label>
                                <input type="text" name="vname" value="<?php echo htmlspecialchars($venue['vname']); ?>" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition-all">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wide">Category</label>
                                <input list="category-options" name="category" value="<?php echo htmlspecialchars($venue['category']); ?>" required placeholder="Select or type category..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm bg-white font-medium transition-all">
                                <datalist id="category-options">
                                    <?php 
                                    if ($categories_result && $categories_result->num_rows > 0) {
                                        while ($cat_row = $categories_result->fetch_assoc()) {
                                            echo '<option value="' . htmlspecialchars($cat_row['category']) . '"></option>';
                                        }
                                    }
                                    ?>
                                </datalist>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wide">Max Capacity</label>
                                <input type="number" name="max_cap" value="<?php echo $venue['max_cap']; ?>" min="1" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm font-mono transition-all">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wide">Required Deposit (RM)</label>
                                <input type="number" name="deposit" value="<?php echo $venue['deposit']; ?>" step="0.01" min="0" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm font-mono transition-all">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wide">Current Status</label>
                                <select name="status" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm bg-white font-bold">
                                    <option value="available" <?php if($venue['status']=='available') echo 'selected'; ?> class="text-emerald-600">Available</option>
                                    <option value="maintenance" <?php if($venue['status']=='maintenance') echo 'selected'; ?> class="text-red-500">Maintenance</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-slate-100 flex justify-between items-center">
                            <span class="text-[10px] text-slate-400 font-mono italic">Last Data Update Trace: [O(1) Access]</span>
                            <div class="flex space-x-3">
                                <a href="venue_directory.php" class="px-6 py-3 text-sm font-bold text-slate-400 hover:bg-slate-50 rounded-xl transition">Discard</a>
                                <button type="submit" class="px-8 py-3 bg-indigo-600 text-white text-sm font-bold rounded-xl shadow-md hover:bg-indigo-700 transition transform active:scale-[0.98] flex items-center">
                                    <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i> Update Vector
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
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