<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['uid'])) {
    header("Location: user_login.php");
    exit();
}

$uid = $_SESSION['uid'];
$display_name = isset($_SESSION['username']) ? $_SESSION['username'] : $uid;

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

<style>
    .dashboard-bg { position: fixed; inset: 0; z-index: -1; background: linear-gradient(to bottom, rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.95)), url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80'); background-size: cover; background-position: center; }
    .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 2.5rem; }
    .text-mmu-glow { color: #00d4ff; text-shadow: 0 0 15px rgba(0, 212, 255, 0.5); }
</style>

<div class="dashboard-bg"></div>

<div class="relative z-10 w-full pt-32 pb-20 px-6">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-5xl font-black text-white mb-14">Welcome back, <span class="text-mmu-glow"><?php echo htmlspecialchars($display_name); ?>!</span></h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <div class="glass-card p-10 text-center"><p class="text-white opacity-70">Total Bookings</p><h3 class="text-4xl font-bold text-white"><?php echo $stats['total']; ?></h3></div>
            <div class="glass-card p-10 text-center"><p class="text-white opacity-70">Approved</p><h3 class="text-4xl font-bold text-white"><?php echo $stats['approved']; ?></h3></div>
            <div class="glass-card p-10 text-center"><p class="text-white opacity-70">Pending</p><h3 class="text-4xl font-bold text-white"><?php echo $stats['pending']; ?></h3></div>
        </div>
        
        <div class="glass-card p-8 text-white">
            <h3 class="text-2xl font-bold mb-6">Recent Activity</h3>
            <table class="w-full text-left">
                <thead><tr class="text-gray-400 border-b border-white/10"><th class="pb-4">Venue</th><th class="pb-4">Date</th><th class="pb-4">Status</th></tr></thead>
                <tbody>
                    <?php while($row = $history_result->fetch_assoc()): ?>
                    <tr class="border-b border-white/5"><td class="py-4"><?php echo htmlspecialchars($row['vname']); ?></td><td class="py-4"><?php echo htmlspecialchars($row['date_booked']); ?></td><td class="py-4 uppercase text-sm"><?php echo htmlspecialchars($row['status']); ?></td></tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("../includes/user_footer.php"); ?>