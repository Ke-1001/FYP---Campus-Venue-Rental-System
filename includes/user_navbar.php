<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$is_homepage = ($current_page === 'homepage.php');
$is_logged_in = isset($_SESSION['uid']);
$username = $_SESSION['username'] ?? 'Student';

if (!function_exists('user_nav_class')) {
    function user_nav_class($page, $current_page) {
        return $current_page === $page
            ? 'text-white bg-blue-600 border-blue-500 font-black shadow-sm'
            : 'text-slate-300 hover:text-white hover:bg-slate-800 border-transparent font-bold';
    }
}

$nav_position_class = $is_homepage
    ? 'fixed top-0 left-0 right-0 bg-slate-950 border-b border-slate-800 shadow-lg shadow-black/30'
    : 'sticky top-0 bg-slate-950 border-b border-slate-800 shadow-lg shadow-black/30';
?>

<nav class="user-site-nav <?php echo $nav_position_class; ?> w-full z-50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">

            <div class="flex-shrink-0 flex items-center gap-2 cursor-pointer">
                <a href="homepage.php" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-mmu-core rounded-lg flex items-center justify-center text-white font-black shadow-sm">
                        C
                    </div>
                    <span class="font-black text-xl tracking-tight text-white">CVBMS</span>
                </a>
            </div>

            <div class="hidden md:flex items-center space-x-3">
                <a href="homepage.php" class="px-4 py-2 rounded-xl border text-base transition-all <?php echo user_nav_class('homepage.php', $current_page); ?>">
                    Home
                </a>

                <?php if ($is_logged_in): ?>
                    <a href="user_dashboard.php" class="px-4 py-2 rounded-xl border text-base transition-all <?php echo user_nav_class('user_dashboard.php', $current_page); ?>">
                        Dashboard
                    </a>
                <?php endif; ?>

                <a href="venues.php" class="px-4 py-2 rounded-xl border text-base transition-all <?php echo user_nav_class('venues.php', $current_page); ?>">
                    Venues
                </a>

                <a href="my_bookings.php" class="px-4 py-2 rounded-xl border text-base transition-all <?php echo user_nav_class('my_bookings.php', $current_page); ?>">
                    My Bookings
                </a>
            </div>

            <div class="flex items-center gap-3">
                <?php if ($is_logged_in): ?>
                    <a href="profile.php" class="flex items-center gap-2 bg-slate-900 py-1.5 px-4 rounded-full border border-slate-700 hover:bg-slate-800 transition-all" title="View Profile">
                        <i data-lucide="user" class="w-4 h-4 text-mmu-glow"></i>
                        <span class="text-sm font-black text-white max-w-[120px] truncate">
                            <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </a>

                    <a href="user_logout.php" class="text-red-400 hover:text-red-300 transition-colors" title="Logout">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </a>
                <?php else: ?>
                    <a href="user_login.php" class="text-slate-300 hover:text-white font-black text-sm transition-colors">
                        Login
                    </a>

                    <a href="user_register.php" class="bg-mmu-core hover:bg-blue-800 text-white px-5 py-2 rounded-xl text-sm font-black shadow-sm transition-all">
                        Sign Up
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</nav>

<main class="w-full min-h-[calc(100vh-4rem)]">