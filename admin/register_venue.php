<?php
// File: admin/register_venue.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

// 💡 唯一性投影：提取不重複且正規化的現有類別
$cat_sql = "SELECT DISTINCT UPPER(TRIM(category)) AS category_name 
            FROM venue WHERE category IS NOT NULL AND category != '' 
            ORDER BY category_name ASC";
$categories_result = $conn->query($cat_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | Register Venue</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b' } } } } }</script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori-tile.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">
    <?php include('../includes/admin_sidebar.php'); ?>
    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
            <?php 
            $topbar_content = '
            <div class="flex items-center">
                <a href="manage_venues.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Asset Management / Register Venue</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>
        <div class="flex-1 overflow-y-auto p-8 scroll-smooth flex justify-center">
            <div class="w-full max-w-2xl">
                <div class="mb-8">
                    <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Register New Asset</h1>
                    <p class="text-sm text-slate-500 mt-1">Initialize a new physical venue node within the system architecture.</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-8 py-4 bg-slate-50 border-b border-slate-100">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Entity Attributes</h3>
                    </div>
                    <form action="../actions/process_venue.php" method="POST" class="p-8 space-y-6">
                        <input type="hidden" name="action" value="create">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wide">Venue Name ($V_{name}$)</label>
                                <input type="text" name="vname" required placeholder="e.g. Grand Hall, Lab 101" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition-all">
                            </div>
                            
                            <!-- 💡 混合輸入器：結合前端即時大寫轉換 (oninput) -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wide">Category</label>
                                <input list="category-options" name="category" required oninput="this.value = this.value.toUpperCase()" placeholder="Select or type category..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm bg-white font-mono font-bold text-indigo-700 transition-all uppercase">
                                <datalist id="category-options">
                                    <?php 
                                    if ($categories_result && $categories_result->num_rows > 0) {
                                        while ($cat_row = $categories_result->fetch_assoc()) {
                                            echo '<option value="' . htmlspecialchars($cat_row['category_name']) . '"></option>';
                                        }
                                    }
                                    ?>
                                </datalist>
                                <p class="text-[9px] text-slate-400 mt-1 italic">Input will be strictly normalized to uppercase.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wide">Max Capacity ($C_{max}$)</label>
                                <input type="number" name="max_cap" min="1" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm font-mono transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wide">Required Deposit ($D$)</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-bold">RM</span>
                                    <input type="number" name="deposit" step="0.01" min="0" required class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm font-mono transition-all">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wide">Initial Status</label>
                                <select name="status" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm bg-white font-bold text-emerald-600">
                                    <option value="available" selected>Available</option>
                                    <option value="maintenance" class="text-red-500">Maintenance</option>
                                </select>
                            </div>
                        </div>
                        <div class="pt-6 border-t border-slate-100 flex justify-end">
                            <button type="submit" class="px-8 py-3 bg-indigo-600 text-white text-sm font-bold rounded-xl shadow-md hover:bg-indigo-700 transition transform active:scale-[0.98] flex items-center">
                                <i data-lucide="save" class="w-4 h-4 mr-2"></i> Register Asset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script>
        lucide.createIcons();
        function toggleSidebar() { document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed'); }
    </script>
</body>
</html>