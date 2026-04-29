<?php
// File: admin/manage_categories.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

// 處理新增或刪除請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $new_cat = trim($_POST['new_category']);
        if (!empty($new_cat)) {
            $stmt = $conn->prepare("INSERT IGNORE INTO venue_category (category_name) VALUES (?)");
            $stmt->bind_param("s", $new_cat);
            $stmt->execute();
            $stmt->close();
            $_SESSION['toast'] = ['type' => 'success', 'msg' => "Category '$new_cat' persisted to system."];
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $del_cat = $_POST['category_name'];
        // 防護邏輯：若該類別下仍有場地，則拒絕刪除
        $check = $conn->query("SELECT COUNT(*) FROM venue WHERE category = '" . $conn->real_escape_string($del_cat) . "'")->fetch_row()[0];
        if ($check > 0) {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => "Constraint Violation: Category is currently in use by $check venue(s)."];
        } else {
            $stmt = $conn->prepare("DELETE FROM venue_category WHERE category_name = ?");
            $stmt->bind_param("s", $del_cat);
            $stmt->execute();
            $stmt->close();
            $_SESSION['toast'] = ['type' => 'success', 'msg' => "Category '$del_cat' purged from system."];
        }
    }
    header("Location: manage_categories.php");
    exit;
}

$result = $conn->query("SELECT * FROM venue_category ORDER BY category_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | Manage Categories</title>
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
                <a href="manage_venues.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Asset Management / Classifications</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth flex justify-center">
            <div class="w-full max-w-3xl">
                
                <div class="mb-8">
                    <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">System Categories</h1>
                    <p class="text-sm text-slate-500 mt-1">Govern the persistent classification dictionary used by all venue entities.</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 mb-6">
                    <form method="POST" class="flex gap-4 items-end">
                        <input type="hidden" name="action" value="add">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Inject New Category</label>
                            <input type="text" name="new_category" required placeholder="e.g. Auditorium, Sports Court" class="w-full px-4 py-3 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 outline-none">
                        </div>
                        <button type="submit" class="px-6 py-3 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition shadow-sm flex items-center">
                            <i data-lucide="plus" class="w-4 h-4 mr-1"></i> Append
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 text-[10px] text-slate-400 font-black uppercase tracking-widest border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-4">Category Nomenclature</th>
                                <th class="px-6 py-4 text-right">Execution</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-50">
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-700"><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td class="px-6 py-4 text-right">
                                    <form method="POST" onsubmit="return confirm('Execute deletion protocol for this category?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="category_name" value="<?php echo htmlspecialchars($row['category_name']); ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-xs uppercase tracking-wider transition">
                                            <i data-lucide="trash-2" class="w-4 h-4 inline pb-0.5"></i> Purge
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

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