<?php
// File: admin/process_flow.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

$bid = intval($_GET['bid'] ?? 0);

if ($bid === 0) {
    die("Invalid Booking ID.");
}

$sql = "SELECT 
            b.*, u.username, u.uid as student_id, u.phone_num as student_phone, u.email as student_email,
            v.vname, v.deposit, v.category,
            i.ins_status, i.damage_desc, i.penalty,
            s.staff_name AS inspector_name,
            r.refund_status
        FROM booking b
        JOIN user u ON b.uid = u.uid
        JOIN venue v ON b.vid = v.vid
        LEFT JOIN inspection i ON b.bid = i.bid
        LEFT JOIN staff s ON i.sid = s.sid
        LEFT JOIN report r ON i.ins_id = r.ins_id
        WHERE b.bid = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bid);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Booking record not found.");
}

$is_rejected = ($data['status'] === 'rejected');

$flow_states = [
    'Request'  => true,
    'Payment'  => ($data['payment_status'] === 'paid' || $data['payment_status'] === 'refunded'),
    'Approval' => ($data['status'] === 'approved' || $data['status'] === 'completed'),
    'Assign'   => ($data['inspector_name'] !== null),
    'Inspect'  => ($data['ins_status'] !== null && $data['ins_status'] !== 'pending'),
    'Settle'   => ($data['status'] === 'completed' && ($data['ins_status'] === 'passed' || $data['ins_status'] === 'failed'))
];
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
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <style>
        .step-line { content: ''; position: absolute; top: 12px; left: 50%; width: 100%; height: 2px; z-index: -1; }
        .step-active .step-line { background-color: #4f46e5; }
        .step-inactive .step-line { background-color: #e2e8f0; }
        .step-item:last-child .step-line { display: none; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0">
            <?php 
            $topbar_content = '
            <div class="flex items-center">
                <a href="track_bookings.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Reporting / Process Flow</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            <div class="max-w-6xl mx-auto w-full">
                
                <div class="mb-8 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
                    <div>
                        <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Booking: <?php echo $bid; ?></h1>
                        <p class="text-sm text-slate-500 font-medium mt-1">Requested by <span class="font-bold text-slate-700"><?php echo htmlspecialchars($data['username']); ?></span> on <?php echo date('M d, Y', strtotime($data['created_at'])); ?></p>
                    </div>
                    <div>
                        <?php if($is_rejected): ?>
                            <span class="px-4 py-2 bg-red-100 text-red-700 rounded-lg text-xs font-black uppercase tracking-widest border border-red-200 shadow-sm">Rejected</span>
                        <?php else: ?>
                            <span class="px-4 py-2 bg-indigo-100 text-indigo-700 rounded-lg text-xs font-black uppercase tracking-widest border border-indigo-200 shadow-sm">Active Flow</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200 mb-8 overflow-x-auto">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-8">Process Flow</h3>
                    
                    <?php if($is_rejected): ?>
                        <div class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm font-bold flex items-center">
                            <i data-lucide="x-circle" class="w-5 h-5 mr-2"></i> Process Terminated: Request was rejected. Deposit refunded.
                        </div>
                    <?php else: ?>
                    <div class="flex justify-between relative z-10 w-full min-w-[600px]">
                        <?php 
                        $steps = ['Request', 'Payment', 'Approval', 'Assign', 'Inspect', 'Settle'];
                        foreach($steps as $index => $label): 
                            
                            $isActive = $flow_states[$label];
                            
                            $nextLabel = $steps[$index + 1] ?? null;
                            $isNextActive = $nextLabel ? $flow_states[$nextLabel] : false;
                            
                            $nodeClass = $isActive ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-300 border-slate-300';
                            $lineClass = ($isActive && $isNextActive) ? 'step-active' : 'step-inactive';
                            $labelClass = $isActive ? 'text-indigo-600 font-bold' : 'text-slate-400';
                        ?>
                        <div class="flex-1 text-center relative step-item <?php echo $lineClass; ?>">
                            <div class="w-6 h-6 mx-auto rounded-full border-2 flex items-center justify-center text-[10px] font-bold shadow-sm <?php echo $nodeClass; ?>">
                                <?php echo $isActive ? '<i data-lucide="check" class="w-3 h-3"></i>' : ($index + 1); ?>
                            </div>
                            <p class="mt-3 text-[11px] uppercase tracking-wider <?php echo $labelClass; ?>"><?php echo $label; ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex flex-col">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 border-b border-slate-100 pb-2">Entity & Venue</h3>
                        <div class="space-y-4 text-sm flex-1">
                            <div><p class="text-[10px] text-slate-500 font-bold uppercase mb-0.5">Student Name</p><p class="font-bold text-slate-700"><?php echo htmlspecialchars($data['username']); ?></p></div>
                            <div><p class="text-[10px] text-slate-500 font-bold uppercase mb-0.5">Contact</p><p class="font-mono text-slate-600"><?php echo htmlspecialchars($data['student_phone']); ?></p></div>
                            <div class="pt-2"><p class="text-[10px] text-slate-500 font-bold uppercase mb-0.5">Venue Allocated</p><p class="font-bold text-indigo-600"><?php echo htmlspecialchars($data['vname']); ?></p></div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex flex-col">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 border-b border-slate-100 pb-2">Schedule Data</h3>
                        <div class="space-y-4 text-sm flex-1">
                            <div><p class="text-[10px] text-slate-500 font-bold uppercase mb-0.5">Usage Date</p><p class="font-bold text-slate-700"><?php echo $data['date_booked']; ?></p></div>
                            <div>
                                <p class="text-[10px] text-slate-500 font-bold uppercase mb-0.5">Time Frame</p>
                                <p class="font-mono text-slate-600">
                                    <?php echo date('H:i', strtotime($data['time_start'])) . ' - ' . date('H:i', strtotime($data['time_end'])); ?>
                                </p>
                            </div>
                            <div class="pt-2"><p class="text-[10px] text-slate-500 font-bold uppercase mb-0.5">Purpose</p><p class="italic text-slate-600">"<?php echo htmlspecialchars($data['purpose']); ?>"</p></div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex flex-col">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 border-b border-slate-100 pb-2">Execution & Finance</h3>
                        <div class="space-y-4 text-sm flex-1">
                            <div class="flex justify-between">
                                <p class="text-[10px] text-slate-500 font-bold uppercase mb-0.5">Base Deposit</p>
                                <p class="font-mono font-bold text-emerald-600">RM <?php echo number_format($data['deposit'], 2); ?></p>
                            </div>
                            <div class="flex justify-between">
                                <p class="text-[10px] text-slate-500 font-bold uppercase mb-0.5">Inspector</p>
                                <p class="font-bold text-slate-700"><?php echo $data['inspector_name'] ?? '<span class="text-slate-300 italic">Pending Assignment</span>'; ?></p>
                            </div>
                            
                            <?php if($data['ins_status']): ?>
                            <div class="pt-2 border-t border-slate-100">
                                <p class="text-[10px] text-slate-500 font-bold uppercase mb-0.5">Inspection Result</p>
                                <p class="font-bold <?php echo ($data['ins_status'] === 'passed') ? 'text-emerald-600' : 'text-red-600'; ?>">
                                    <?php echo strtoupper($data['ins_status']); ?>
                                </p>
                                <?php if($data['ins_status'] === 'failed'): ?>
                                    <p class="text-xs text-red-500 mt-1 italic"><?php echo htmlspecialchars($data['damage_desc']); ?></p>
                                    <div class="flex justify-between mt-2 text-red-600 font-bold">
                                        <span class="text-[10px] uppercase">Penalty Applied</span>
                                        <span class="font-mono">- RM <?php echo number_format($data['penalty'], 2); ?></span>
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