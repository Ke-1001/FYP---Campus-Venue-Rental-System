<?php
// File: actions/process_venue.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

$status_type = 'info';
$status_msg = '';
$redirect_target = '../admin/venue_directory.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $action = $_POST['action'] ?? '';
    $vname = htmlspecialchars(trim($_POST['vname'] ?? ''));
    
    // 💡 絕對資料清洗：強制大寫與去空白 (Data Sanitization)
    $raw_category = $_POST['category'] ?? '';
    $category = strtoupper(trim($raw_category));
    
    $max_cap = intval($_POST['max_cap'] ?? 0);
    $deposit = floatval($_POST['deposit'] ?? 0.00);
    $status = $_POST['status'] ?? 'available';

    if (empty($category)) {
        $status_type = 'error';
        $status_msg = "Constraint Violation: Category vector cannot be null.";
        goto RENDER_REPORT; // 發生例外直接跳轉渲染
    }

    $conn->begin_transaction();

    try {
        if ($action === 'create') {
            $sql = "INSERT INTO venue (vname, category, max_cap, deposit, status) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssids", $vname, $category, $max_cap, $deposit, $status);
            
            if ($stmt->execute()) {
                $status_type = 'success';
                $status_msg = "Asset Creation Success: Venue [{$vname}] initialized under category [{$category}].";
            } else {
                throw new Exception("SQL Execution Fault: " . $stmt->error);
            }
            $stmt->close();

        } elseif ($action === 'update') {
            $vid = intval($_POST['vid'] ?? 0);
            if ($vid === 0) throw new Exception("Invalid Entity Identifier.");

            $sql = "UPDATE venue SET vname = ?, category = ?, max_cap = ?, deposit = ?, status = ? WHERE vid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssidsi", $vname, $category, $max_cap, $deposit, $status, $vid);
            
            if ($stmt->execute()) {
                $status_type = 'success';
                $status_msg = "Vector Update Success: Node [ID: {$vid}] reconfigured under category [{$category}].";
            } else {
                throw new Exception("SQL Mutation Fault: " . $stmt->error);
            }
            $stmt->close();
        }
        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        $status_type = 'error';
        $status_msg = "Transaction Aborted: " . $e->getMessage();
    }
} else {
    header("Location: ../admin/manage_venues.php");
    exit;
}

RENDER_REPORT:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | Execution Status</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="../admin/layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori-tile.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">
    <?php include('../includes/admin_sidebar.php'); ?>
    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0">
            <?php 
            $topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">System / Execution Report</h2>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>
        <div class="flex-1 overflow-y-auto p-8 flex items-center justify-center">
            <div class="max-w-md w-full bg-white rounded-3xl shadow-xl border border-slate-200 p-10 text-center">
                <?php if ($status_type === 'success'): ?>
                    <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="check-circle" class="w-10 h-10"></i>
                    </div>
                    <h2 class="text-2xl font-black text-slate-800 mb-2">Operation Finalized</h2>
                <?php else: ?>
                    <div class="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="alert-triangle" class="w-10 h-10"></i>
                    </div>
                    <h2 class="text-2xl font-black text-slate-800 mb-2">Execution Fault</h2>
                <?php endif; ?>
                <p class="text-sm text-slate-500 mb-8 leading-relaxed font-medium"><?php echo $status_msg; ?></p>
                <div class="space-y-3">
                    <a href="<?php echo $redirect_target; ?>" class="block w-full py-3 bg-indigo-600 text-white text-sm font-bold rounded-xl shadow-md hover:bg-indigo-700 transition transform active:scale-95">
                        Return to Directory
                    </a>
                    <p class="text-[10px] text-slate-400 font-mono uppercase tracking-tighter">Automatic re-routing in <span id="timer">5</span>s...</p>
                </div>
            </div>
        </div>
    </main>
    <script>
        lucide.createIcons();
        function toggleSidebar() { document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed'); }
        let count = 5;
        const timer = setInterval(() => {
            count--; document.getElementById('timer').innerText = count;
            if (count <= 0) { clearInterval(timer); window.location.href = "<?php echo $redirect_target; ?>"; }
        }, 1000);
    </script>
</body>
</html>