<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = isset($_SESSION['uid']);
$username = $_SESSION['username'] ?? 'Student';

if (!function_exists('user_nav_class')) {
    function user_nav_class($page, $current_page) {
        return $current_page === $page
            ? 'text-white font-semibold px-2 py-2 text-lg drop-shadow-[0_0_8px_rgba(255,255,255,0.5)]'
            : 'text-slate-400 hover:text-white transition-colors px-2 py-2 text-lg font-medium';
    }
}
?>
<nav class="glass-nav fixed w-full z-50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <div class="flex-shrink-0 flex items-center gap-2 cursor-pointer">
                <a href="homepage.php" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-mmu-core rounded-lg flex items-center justify-center text-white font-bold shadow-lg">C</div>
                    <span class="font-extrabold text-xl tracking-tight text-white">CVBMS</span>
                </a>
            </div>

            <div class="hidden md:flex space-x-8">
                <a href="homepage.php" class="<?php echo user_nav_class('homepage.php', $current_page); ?>">Home</a>
                <a href="venues.php" class="<?php echo user_nav_class('venues.php', $current_page); ?>">Venues</a>
                <a href="my_bookings.php" class="<?php echo user_nav_class('my_bookings.php', $current_page); ?>">My Bookings</a>
                <?php if ($is_logged_in): ?>
                    <a href="user_dashboard.php" class="<?php echo user_nav_class('user_dashboard.php', $current_page); ?>">Dashboard</a>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-4">
                <?php if ($is_logged_in): ?>
                    <a href="profile.php" class="flex items-center gap-2 bg-white/10 py-1.5 px-4 rounded-full border border-white/10 backdrop-blur-md hover:bg-white/20 transition-all" title="View Profile">
                        <i data-lucide="user" class="w-4 h-4 text-mmu-glow"></i>
                        <span class="text-sm font-semibold text-white max-w-[100px] truncate">
                            <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </a>
                    <a href="user_logout.php" class="text-red-400 hover:text-red-300 transition-colors" title="Logout">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </a>
                <?php else: ?>
                    <a href="user_login.php" class="text-slate-300 hover:text-white font-semibold text-sm transition-colors">Login</a>
                    <a href="user_register.php" class="bg-mmu-core hover:bg-blue-800 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-lg transition-all">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<main class="container mx-auto mt-24 px-4">
