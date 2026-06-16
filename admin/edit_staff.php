<?php
// This section prepares the admin edit staff page.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

$sid = intval($_GET['sid'] ?? 0);
if ($sid === 0) die("Error: NULL pointer reference for Staff ID.");

$stmt = $conn->prepare("SELECT * FROM staff WHERE sid = ? LIMIT 1");
$stmt->bind_param("i", $sid);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();

if (!$staff) die("Anomaly: Staff object not found.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | Edit Staff</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config =
        { theme:
        { extend:
        { colors:
        { cstyle:
        { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="../assets/css/admin_css.css?v=2.0">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">

        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0">
            <?php
            $topbar_content = '
            <div class="flex items-center">
                <a href="staff_directory.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Staff Management / Edit Inspector</h2>
            </div>';
            include('../includes/admin_topbar.php');
            ?>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth flex justify-center">
            <div class="w-full max-w-2xl">

                <div class="mb-8 flex justify-between items-end">
                    <div>
                        <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Edit Inspector Node</h1>
                        <p class="text-sm text-slate-500 mt-1">Update field operational personnel details.</p>
                    </div>
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full text-[10px] font-black uppercase tracking-widest border border-indigo-100 shadow-sm">
                        SID: <?php echo $sid; ?>
                    </span>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <form action="../actions/process_personnel.php" method="POST" class="p-8 space-y-6">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="target_table" value="staff">
                        <input type="hidden" name="entity_id" value="<?php echo $sid; ?>">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wide">Full Name</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($staff['staff_name']); ?>" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition-all">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wide">Email Address</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($staff['email']); ?>" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition-all">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wide">Phone Number</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($staff['phone_num']); ?>" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm font-mono transition-all">
                            </div>

                            <div class="md:col-span-2 border-t border-slate-100 pt-6 mt-2">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wide">New Password Vector <span class="text-[10px] lowercase font-normal opacity-60">(Leave blank to retain current)</span></label>
                                <input type="password" name="password" placeholder="••••••••" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm font-mono transition-all">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-wide">System Role</label>
                                <select name="access_level" required class="w-full px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none text-sm text-emerald-800 font-bold transition-all">
                                    <option value="inspector" <?php if($staff['position']=='inspector') echo 'selected'; ?>>Venue Inspector</option>
                                    <option value="admin" <?php if($staff['position']=='admin') echo 'selected'; ?>>Admin (Operations)</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-slate-100 flex justify-end space-x-3">
                            <a href="staff_directory.php" class="px-6 py-3 text-sm font-bold text-slate-400 hover:bg-slate-50 rounded-xl transition">Discard</a>
                            <button type="submit" class="px-8 py-3 bg-indigo-600 text-white text-sm font-bold rounded-xl shadow-md hover:bg-indigo-700 transition transform active:scale-[0.98] flex items-center">
                                <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i> Update Inspector
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
        function toggleSidebar()
        { document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed'); }
    </script>
</body>
</html>
