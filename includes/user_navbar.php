<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$is_homepage = ($current_page === 'homepage.php');
$is_dashboard = ($current_page === 'user_dashboard.php');
$is_logged_in = isset($_SESSION['uid']);
$username = $_SESSION['username'] ?? 'Student';

$is_light_user_page = (!$is_homepage && !$is_dashboard);

if (!function_exists('user_nav_class')) {
    function user_nav_class($page, $current_page, $is_light_user_page = false) {
        if ($is_light_user_page) {
            return $current_page === $page
                ? 'text-white bg-blue-600 border-blue-600 font-black shadow-sm'
                : 'text-black hover:text-black hover:bg-gray-300 border-transparent font-bold';
        }

        return $current_page === $page
            ? 'text-white bg-blue-600 border-blue-500 font-black shadow-sm'
            : 'text-white hover:text-white hover:bg-slate-700 border-transparent font-bold';
    }
}

if ($is_homepage) {
    $nav_position_class = 'fixed top-0 left-0 right-0 bg-slate-800 border-b border-slate-700 shadow-lg shadow-black/20';
} elseif ($is_dashboard) {
    $nav_position_class = 'sticky top-0 bg-slate-800 border-b border-slate-700 shadow-lg shadow-black/20';
} else {
    $nav_position_class = 'sticky top-0 bg-gray-300 border-b border-gray-400 shadow-sm';
}

$brand_text_class = $is_light_user_page ? 'text-black' : 'text-white';
$profile_link_class = $is_light_user_page
    ? 'bg-white py-2 px-5 rounded-full border border-gray-400 hover:bg-gray-100 transition-all'
    : 'bg-slate-900 py-2 px-5 rounded-full border border-slate-700 hover:bg-slate-800 transition-all';
$profile_text_class = $is_light_user_page ? 'text-black' : 'text-white';
$login_link_class = $is_light_user_page
    ? 'text-black hover:text-black font-black text-base transition-colors'
    : 'text-slate-300 hover:text-white font-black text-base transition-colors';
?>

<?php $nav_theme_class = $is_light_user_page ? 'user-nav-light' : 'user-nav-dark'; ?>
<nav class="user-site-nav <?php echo $nav_theme_class; ?> <?php echo $nav_position_class; ?> w-full z-50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20 items-center">

            <div class="flex-shrink-0 flex items-center gap-2 cursor-pointer">
                <a href="homepage.php" class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-mmu-core rounded-xl flex items-center justify-center text-white font-black shadow-sm">
                        C
                    </div>
                    <span class="font-black text-2xl tracking-tight <?php echo $brand_text_class; ?>">CVBMS</span>
                </a>
            </div>

            <div class="hidden md:flex items-center space-x-3">
                <a href="homepage.php" class="px-5 py-2.5 rounded-xl border text-lg transition-all <?php echo user_nav_class('homepage.php', $current_page, $is_light_user_page); ?>">
                    Home
                </a>

                <?php if ($is_logged_in): ?>
                    <a href="user_dashboard.php" class="px-5 py-2.5 rounded-xl border text-lg transition-all <?php echo user_nav_class('user_dashboard.php', $current_page, $is_light_user_page); ?>">
                        Dashboard
                    </a>
                <?php endif; ?>

                <a href="venues.php" class="px-5 py-2.5 rounded-xl border text-lg transition-all <?php echo user_nav_class('venues.php', $current_page, $is_light_user_page); ?>">
                    Venues
                </a>

                <a href="my_bookings.php" class="px-5 py-2.5 rounded-xl border text-lg transition-all <?php echo user_nav_class('my_bookings.php', $current_page, $is_light_user_page); ?>">
                    My Bookings
                </a>
            </div>

            <div class="flex items-center gap-3">
                <?php if ($is_logged_in): ?>
                    <a href="profile.php" class="flex items-center gap-2 <?php echo $profile_link_class; ?>" title="View Profile">
                        <i data-lucide="user" class="w-4 h-4 text-mmu-glow"></i>
                        <span class="text-base font-black <?php echo $profile_text_class; ?> max-w-[140px] truncate">
                            <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </a>


                    <a href="user_logout.php" class="text-red-400 hover:text-red-300 transition-colors" title="Logout">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </a>
                <?php else: ?>
                    <a href="user_login.php" class="<?php echo $login_link_class; ?>">
                        Login
                    </a>

                    <a href="user_register.php" class="bg-mmu-core hover:bg-blue-800 text-white px-5 py-2 rounded-xl text-base font-black shadow-sm transition-all">
                        Sign Up
                    </a>
                <?php endif; ?>
            </div>


        </div>
    </div>
</nav>


<?php
if ($is_homepage) {
    $main_class = 'w-full min-h-screen';
} elseif ($is_dashboard) {
    $main_class = 'w-full min-h-[calc(100vh-4rem)] bg-transparent text-white relative z-10';
} else {
    $main_class = 'w-full min-h-[calc(100vh-4rem)] bg-white text-slate-900 relative z-10';
}
?>
<main class="<?php echo $main_class; ?>">