<?php
// File: admin/assign_inspector_detail.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

$bid = intval($_GET['bid'] ?? 0);

if ($bid === 0) {
    die("Invalid Booking ID.");
}

// 💡 提取該預約的完整明細
$sql_b = "SELECT b.*, u.username, u.email, v.vname, v.deposit 
          FROM booking b 
          JOIN user u ON b.uid = u.uid 
          JOIN venue v ON b.vid = v.vid 
          WHERE b.bid = ?";
$stmt = $conn->prepare($sql_b);
$stmt->bind_param("i", $bid);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    die("Booking record not found.");
}

// 💡 提取職位為 inspector 的工作人員
$inspectors = $conn->query("SELECT sid, staff_name FROM staff WHERE position = 'inspector' ORDER BY staff_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Execution: Assign Inspector</title>
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
        
            <?php 
            $topbar_content = '
            <div class="flex items-center">
                <a href="assign_inspector.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">BOOKINGS / ASSIGN INSPECTOR / Inspector Details</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            <div class="max-w-4xl mx-auto w-full">
                
                <div class="mb-8">
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Assign Inspector for BID: <?php echo $bid; ?></h1>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    
                    <div class="md:col-span-1">
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 border-l-4 border-l-indigo-500">
                            <h3 class="text-xs font-black text-slate-400 uppercase mb-4 tracking-widest flex items-center">
                                <i data-lucide="info" class="w-3.5 h-3.5 mr-1.5"></i> Booking Details
                            </h3>
                            <div class="space-y-4 text-sm">
                                <div>
                                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-tight">Student</p>
                                    <p class="font-bold text-slate-800"><?php echo htmlspecialchars($booking['username']); ?></p>
                                </div>
                                <div>
                                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-tight">Venue</p>
                                    <p class="font-bold text-indigo-600"><?php echo htmlspecialchars($booking['vname']); ?></p>
                                </div>
                                <div>
                                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-tight">Financial Block</p>
                                    <p class="font-mono font-bold text-emerald-600">RM <?php echo number_format($booking['deposit'], 2); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
                            <form action="../actions/process_assign_inspector.php" method="POST" class="space-y-6">
                                <input type="hidden" name="bid" value="<?php echo $bid; ?>">
                                
                                <div>
                                    <label class="block text-xs font-black text-slate-500 uppercase mb-3 tracking-widest">Select Allocation Strategy</label>
                                    <div class="relative">
                                        <select name="sid" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none transition-all appearance-none">
                                            <option value="" disabled selected>-- Choose Personnel --</option>
                                            <option value="RA01" class="text-indigo-600 font-black">RA01 - Random Assignment (System Auto)</option>
                                            <optgroup label="Available Inspectors">
                                                <?php while($ins = $inspectors->fetch_assoc()): ?>
                                                    <option value="<?php echo $ins['sid']; ?>"><?php echo htmlspecialchars($ins['staff_name']); ?> (ID: <?php echo $ins['sid']; ?>)</option>
                                                <?php endwhile; ?>
                                            </optgroup>
                                        </select>
                                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                            <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4 bg-indigo-50 rounded-xl border border-indigo-100 flex items-start">
                                    <i data-lucide="shield-info" class="w-5 h-5 text-indigo-600 mr-3 shrink-0"></i>
                                    <p class="text-xs text-indigo-700 leading-relaxed font-medium">
                                        Confirmed assignment will immediately update the <strong>Process Flow</strong> for this booking. The selected inspector will be authorized to conduct the post-event assessment.
                                    </p>
                                </div>

                                <button type="submit" class="w-full py-4 bg-indigo-600 text-white font-black rounded-xl shadow-md hover:bg-indigo-700 transition transform active:scale-[0.98] flex items-center justify-center">
                                    <i data-lucide="user-check" class="w-5 h-5 mr-2"></i> Confirm Assignment
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();
        function toggleSidebar() {
            document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed');
        }
    </script>
</body>
</html>