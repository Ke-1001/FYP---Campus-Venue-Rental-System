<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['uid'])) {
    header("Location: user_login.php");
    exit();
}

$uid = $_SESSION['uid'];
$display_name = isset($_SESSION['username']) ? $_SESSION['username'] : $uid;

$damage_booking_stmt = $conn->prepare("
    SELECT 
        b.bid,
        b.date_booked,
        b.time_start,
        b.time_end,
        b.status,
        v.vname,
        v.vid,
        dr.report_id,
        dr.report_status,
        dr.created_at AS report_created_at
    FROM booking b
    JOIN venue v ON b.vid = v.vid
    LEFT JOIN damage_report dr ON b.bid = dr.bid AND b.uid = dr.uid
    WHERE b.uid = ?
      AND b.status = 'approved'
      AND NOW() BETWEEN TIMESTAMP(b.date_booked, b.time_start) AND TIMESTAMP(b.date_booked, b.time_end)
    ORDER BY b.date_booked ASC, b.time_start ASC
    LIMIT 5
");

$damage_booking_stmt->bind_param("s", $uid);
$damage_booking_stmt->execute();
$damage_booking_result = $damage_booking_stmt->get_result();

// 1. FIXED: Query Stats with correct column references (no single quotes around column names)
$stats_stmt = $conn->prepare("SELECT 
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
    COUNT(*) as total 
    FROM booking WHERE uid = ?");
$stats_stmt->bind_param("s", $uid);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// 2. Query Activity
$history_stmt = $conn->prepare("SELECT v.vname, b.date_booked, b.status 
    FROM booking b
    JOIN venue v ON b.vid = v.vid
    WHERE b.uid = ?
    ORDER BY b.date_booked DESC LIMIT 5");
$history_stmt->bind_param("s", $uid);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

$page_title = "Dashboard | CVBMS";
include("../includes/user_header.php");
include("../includes/user_navbar.php");
?>

<div class="user-bg"></div>

<div class="relative z-10 w-full pt-16 pb-20 px-6">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-5xl font-black text-slate-900 mb-14">Welcome back, <span class="text-mmu-core"><?php echo htmlspecialchars($display_name); ?>!</span></h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <div class="glass-card p-10 text-center"><p class="text-slate-500">Total Bookings</p><h3 class="text-4xl font-bold text-slate-900"><?php echo $stats['total']; ?></h3></div>
            <div class="glass-card p-10 text-center"><p class="text-slate-500">Approved</p><h3 class="text-4xl font-bold text-slate-900"><?php echo $stats['approved']; ?></h3></div>
            <div class="glass-card p-10 text-center"><p class="text-slate-500">Pending</p><h3 class="text-4xl font-bold text-slate-900"><?php echo $stats['pending']; ?></h3></div>
        </div>

        <?php if ($damage_booking_result && $damage_booking_result->num_rows > 0): ?>
        <!-- Report Existing Damage Quick Access -->
        <div class="mt-8 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200">
                <h2 class="text-xl font-black text-slate-900 flex items-center">
                    <i data-lucide="triangle-alert" class="w-5 h-5 mr-2 text-amber-400"></i>
                    Report Existing Damage
                </h2>

                <p class="text-sm text-slate-500 mt-1">
                    Report existing damage during your active booking time to avoid being charged for damage you did not cause.
                </p>
            </div>

            <div class="p-6">
                <?php if ($damage_booking_result && $damage_booking_result->num_rows > 0): ?>
                    <div class="space-y-3">
                        <?php while ($damage_booking = $damage_booking_result->fetch_assoc()): ?>
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-slate-50 border border-slate-200 rounded-xl p-4">
                                <div>
                                    <p class="text-slate-900 font-bold">
                                        <?php echo htmlspecialchars($damage_booking['vname']); ?>
                                    </p>

                                    <p class="text-sm text-slate-500 mt-1">
                                        <?php echo date("d M Y", strtotime($damage_booking['date_booked'])); ?>
                                        ·
                                        <?php echo substr($damage_booking['time_start'], 0, 5); ?>
                                        -
                                        <?php echo substr($damage_booking['time_end'], 0, 5); ?>
                                    </p>
                                </div>

                                <div class="flex-shrink-0">
                                    <?php if (!empty($damage_booking['report_id'])): ?>
                                        <a 
                                            href="booking_details.php?bid=<?php echo urlencode($damage_booking['bid']); ?>"
                                            class="inline-flex items-center justify-center px-4 py-2 bg-emerald-100 text-emerald-700 text-sm font-bold rounded-lg border border-emerald-200"
                                        >
                                            <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i>
                                            Report Submitted
                                        </a>
                                    <?php else: ?>
                                        <a 
                                            href="user_report_damage.php?bid=<?php echo urlencode($damage_booking['bid']); ?>"
                                            class="inline-flex items-center justify-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-lg shadow-sm transition"
                                        >
                                            <i data-lucide="camera" class="w-4 h-4 mr-2"></i>
                                            Report Damage
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>        
        <div class="glass-card p-8 text-slate-700">
            <h3 class="text-2xl font-bold mb-6 text-slate-900">Recent Activity</h3>
            <table class="w-full text-left">
                <thead><tr class="text-slate-500 border-b border-slate-200"><th class="pb-4">Venue</th><th class="pb-4">Date</th><th class="pb-4">Status</th></tr></thead>
                <tbody>
                    <?php while($row = $history_result->fetch_assoc()): ?>
                    <tr class="border-b border-slate-100"><td class="py-4"><?php echo htmlspecialchars($row['vname']); ?></td><td class="py-4"><?php echo htmlspecialchars($row['date_booked']); ?></td><td class="py-4 uppercase text-sm"><?php echo htmlspecialchars($row['status']); ?></td></tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
lucide.createIcons();
</script>

<?php include("../includes/user_footer.php"); ?>