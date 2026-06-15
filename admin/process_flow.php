<?php
// File: admin/process_flow.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

$bid = intval($_GET['bid'] ?? 0);

if ($bid === 0) {
    die("Execution Fault: Invalid or Null Booking ID Reference.");
}

// 💡 1. 關聯查詢升級：精準適配 rental_venue (4).sql 的 vcategory 正規化
$sql = "SELECT 
            b.*, u.username, u.uid as student_id, u.phone_num as student_phone, u.email as student_email,
            v.vname, v.deposit, vc.category AS venue_category,
            i.ins_status, i.damage_desc, i.penalty,
            s.staff_name AS inspector_name,
            r.refund_status
        FROM booking b
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        JOIN vcategory vc ON v.vcid = vc.vcid
        LEFT JOIN inspection i ON b.bid = i.bid
        LEFT JOIN staff s ON i.sid = s.sid
        LEFT JOIN report r ON i.ins_id = r.ins_id
        WHERE b.bid = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bid);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Database Anomaly: Target booking entity not found.");
}

$status = strtolower((string)$data['status']);
$payment_status = strtolower((string)$data['payment_status']);

$is_rejected = ($status === 'rejected');
$is_cancelled = ($status === 'cancelled');
$is_completed = ($status === 'completed');

// 💡 2. 狀態機引擎
$flow_states = [
    'Request'  => true,
    'Payment'  => ($payment_status === 'paid' || $payment_status === 'refunded'),
    'Approval' => ($status === 'approved' || $status === 'completed'),
    'Assign'   => (!$is_cancelled && !$is_rejected && $data['inspector_name'] !== null),
    'Inspect'  => (!$is_cancelled && !$is_rejected && $data['ins_status'] !== null && $data['ins_status'] !== 'pending'),
    'Settle'   => (!$is_cancelled && !$is_rejected && $status === 'completed' && ($data['ins_status'] === 'passed' || $data['ins_status'] === 'failed'))
];

// 💡 [NEW] 系統代碼字典映射矩陣 (具備防碰撞宣告機制)
if (!function_exists('translateSystemText')) {
    function translateSystemText($text) {
        if (empty($text)) return '';
        
        $dictionary = [
            'SYS_TIMEOUT_ADMIN' => 'Admin failed to approve the request within the functional timeframe.',
            'SYS_TIMEOUT_24H_RELEASE' => 'SLA Absolute Timeout: Auto-released after 24 hours of inactivity without consecutive bookings.',
            'SYS_TIMEOUT_30M_LOCK' => 'SLA Violation: Inspection delayed. Deposit temporarily frozen for maximum 24h.',
            '[SYSTEM ABSORBED]' => 'Chain of Custody Fault: Damage was detected, but a preceding SLA violation exists for this venue.'
        ];

        foreach ($dictionary as $code => $translation) {
            if (stripos($text, $code) !== false) {
                return str_ireplace($code, $translation, $text);
            }
        }
        return $text;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Process Flow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { cstyle: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="../assets/css/admin_css.css?v=2.0">
</head>
<body class="bg-[#f4f4f4] text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
            <?php 
            $topbar_content = '
            <div class="flex items-center">
                <a href="track_bookings.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Bookings / Track Bookings / Process Flow</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            <div class="max-w-6xl mx-auto w-full">
                
                <div class="mb-8 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
                    <div>
                        <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Booking: <?php echo $bid; ?></h1>
                        <p class="text-sm text-slate-500 font-medium mt-1">Requested by <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['username']); ?></span> on <?php echo date('M d, Y', strtotime($data['created_at'])); ?></p>
                    </div>
                    <div>
                        <?php if($is_cancelled): ?>
                            <span class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-xs font-black uppercase tracking-widest border border-slate-300 shadow-sm block text-center">
                                Cancelled
                            </span>
                        <?php elseif($is_rejected): ?>
                            <span class="px-4 py-2 bg-red-100 text-red-700 rounded-lg text-xs font-black uppercase tracking-widest border border-red-200 shadow-sm block text-center">
                                Execution Terminated
                            </span>
                        <?php elseif($is_completed): ?>
                            <span class="px-4 py-2 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-black uppercase tracking-widest border border-emerald-200 shadow-sm block text-center">
                                Completed
                            </span>
                        <?php else: ?>
                            <span class="px-4 py-2 bg-indigo-100 text-indigo-700 rounded-lg text-xs font-black uppercase tracking-widest border border-indigo-200 shadow-sm block text-center">
                                Active Pipeline
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-lg shadow-sm border border-slate-200 mb-8 overflow-x-auto">
                    <h3 class="text-xs font-bold text-slate-800 mb-8">Process Pipeline Tracker</h3>
                    
                    
                    <div class="flex justify-between relative z-10 w-full min-w-[600px]">
                        <?php 
                        $steps = ['Request', 'Payment', 'Approval', 'Assign', 'Inspect', 'Settle'];
                        $interrupt_found = false; // 邏輯閂鎖：用於鎖定第一個失敗的節點
                        
                        foreach($steps as $index => $label): 
                            
                            $isActive = $flow_states[$label];
                            $nextLabel = $steps[$index + 1] ?? null;
                            $isNextActive = $nextLabel ? $flow_states[$nextLabel] : false;
                            
                            // 異常中斷點偵測邏輯 (Interrupt Detection)
                            $isFailedNode = false;
                            if (!$isActive && !$interrupt_found && ($is_cancelled || $is_rejected)) {
                                $isFailedNode = true;
                                $interrupt_found = true; // 鎖定，確保只有一個節點顯示為錯誤
                            }
                            
                            // 視覺狀態映射矩陣
                            if ($isFailedNode) {
                                $nodeClass = 'bg-red-50 text-red-600 border-red-600';
                                $lineClass = 'step-inactive';
                                $labelClass = 'text-red-600 font-bold';
                                $iconHtml = '<i data-lucide="x" class="w-3 h-3 text-red-600"></i>';
                            } elseif ($isActive) {
                                $nodeClass = 'bg-indigo-600 text-white border-indigo-600';
                                $lineClass = ($isActive && $isNextActive) ? 'step-active' : 'step-inactive';
                                $labelClass = 'text-indigo-600 font-bold';
                                $iconHtml = '<i data-lucide="check" class="w-3 h-3 text-white"></i>';
                            } else {
                                $nodeClass = 'bg-white text-slate-300 border-slate-300';
                                $lineClass = 'step-inactive';
                                $labelClass = 'text-slate-400 font-medium';
                                $iconHtml = ($index + 1);
                            }
                        ?>
                        <div class="flex-1 text-center relative step-item <?php echo $lineClass; ?>">
                            <div class="w-6 h-6 mx-auto rounded-full border-2 flex items-center justify-center text-[10px] font-bold <?php echo $nodeClass; ?>">
                                <?php echo $iconHtml; ?>
                            </div>
                            <p class="mt-3 text-[11px] uppercase tracking-wider <?php echo $labelClass; ?>"><?php echo $label; ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 flex flex-col">
                        <h3 class="text-xs font-bold text-slate-800 border-b border-slate-200 pb-3 mb-4">Entity & Venue</h3>
                        <div class="space-y-5 text-sm flex-1">
                            <div>
                                <p class="text-[10px] text-slate-500 font-bold uppercase mb-1">Student Context</p>
                                <p class="font-bold text-slate-800"><?php echo htmlspecialchars($data['username']); ?></p>
                                <p class="font-mono text-xs text-slate-600 mt-0.5"><?php echo htmlspecialchars($data['student_id']); ?></p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 font-bold uppercase mb-1">Contact Vectors</p>
                                <p class="font-mono text-xs text-slate-600"><?php echo htmlspecialchars($data['student_phone']); ?></p>
                                <a href="mailto:<?php echo htmlspecialchars($data['student_email']); ?>" class="text-indigo-600 hover:underline text-xs mt-0.5 inline-block font-bold">Email Communication</a>
                            </div>
                            <div class="pt-2 border-t border-slate-100">
                                <p class="text-[10px] text-slate-500 font-bold uppercase mb-1">Allocated Asset</p>
                                <p class="font-bold text-slate-800"><?php echo htmlspecialchars($data['vname']); ?></p>
                                <p class="text-[10px] font-black uppercase text-slate-500 mt-1"><?php echo htmlspecialchars($data['venue_category']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 flex flex-col">
                        <h3 class="text-xs font-bold text-slate-800 border-b border-slate-200 pb-3 mb-4">Temporal Parameters</h3>
                        <div class="space-y-5 text-sm flex-1">
                            <div>
                                <p class="text-[10px] text-slate-500 font-bold uppercase mb-1">Reserved Date</p>
                                <p class="font-bold text-slate-800"><?php echo $data['date_booked']; ?></p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 font-bold uppercase mb-1">Time Window</p>
                                <div class="bg-blue-50 border border-blue-100 px-3 py-2 rounded text-xs font-mono font-bold text-blue-800 w-fit">
                                    <?php echo date('H:i', strtotime($data['time_start'])) . ' - ' . date('H:i', strtotime($data['time_end'])); ?>
                                </div>
                            </div>
                            <div class="pt-2 border-t border-slate-100">
                                <p class="text-[10px] text-slate-500 font-bold uppercase mb-1">Declared Purpose</p>
                                <p class="italic text-slate-600 text-xs leading-relaxed">"<?php echo nl2br(htmlspecialchars($data['purpose'])); ?>"</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 flex flex-col">
                        <h3 class="text-xs font-bold text-slate-800 border-b border-slate-200 pb-3 mb-4">Execution & Finance</h3>
                        <div class="space-y-5 text-sm flex-1">
                            <div>
                                <p class="text-[10px] text-slate-500 font-bold uppercase mb-1">Base Deposit</p>
                                <p class="font-mono font-bold text-emerald-600">RM <?php echo number_format($data['deposit'], 2); ?></p>
                            </div>

                            <?php if($is_cancelled): ?>
                                <div class="pt-4 border-t border-slate-100">
                                    <p class="text-[10px] text-slate-500 font-bold uppercase mb-1">
                                        Cancellation Status
                                    </p>

                                    <p class="font-black text-slate-700 uppercase tracking-widest text-xs">
                                        Cancelled
                                    </p>

                                    <div class="mt-3 space-y-2 text-xs text-slate-600">
                                        <p>
                                            <span class="font-bold text-slate-500 uppercase">Cancelled At:</span>
                                            <?php echo !empty($data['cancelled_at']) ? date('Y-m-d H:i', strtotime($data['cancelled_at'])) : '-'; ?>
                                        </p>

                                        <p>
                                            <span class="font-bold text-slate-500 uppercase">Payment Due:</span>
                                            <?php echo !empty($data['payment_due_at']) ? date('Y-m-d H:i', strtotime($data['payment_due_at'])) : '-'; ?>
                                        </p>

                                        <p class="text-red-600 font-semibold">
                                            <span class="font-bold uppercase">Reason:</span>
                                            <?php 
                                                $reason = $data['cancel_reason'] ?? 'Payment deadline expired';
                                                echo htmlspecialchars(translateSystemText($reason)); 
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div>
                                <p class="text-[10px] text-slate-500 font-bold uppercase mb-1">Delegated Inspector</p>
                                <p class="font-bold text-slate-800">
                                    <?php 
                                        if ($is_cancelled) {
                                            echo '<span class="text-slate-400 font-bold text-xs uppercase tracking-widest">Not Required</span>';
                                        } else {
                                            echo $data['inspector_name'] ?? '<span class="text-amber-500 font-bold text-xs uppercase tracking-widest">Pending Assignment</span>';
                                        }
                                    ?>
                                </p>
                            </div>
                            
                            <?php if($data['ins_status']): ?>
                            <div class="pt-4 border-t border-slate-100">
                                <p class="text-[10px] text-slate-500 font-bold uppercase mb-1">Inspection Result</p>
                                <?php if($data['ins_status'] === 'passed'): ?>
                                    <p class="font-black text-emerald-600 uppercase tracking-widest text-xs">PASSED</p>
                                <?php else: ?>
                                    <p class="font-black text-red-600 uppercase tracking-widest text-xs mb-1">FAILED</p>
                                    <p class="text-xs text-slate-600 bg-red-50 border border-red-100 p-2 rounded italic">
                                        <?php echo nl2br(htmlspecialchars(translateSystemText($data['damage_desc']))); ?>
                                    </p>
                                    <div class="flex justify-between items-center mt-3 bg-slate-50 p-2 rounded">
                                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Penalty Applied</span>
                                        <span class="font-mono font-bold text-red-600">- RM <?php echo number_format($data['penalty'], 2); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>

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