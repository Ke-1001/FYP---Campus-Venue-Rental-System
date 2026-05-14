<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['uid'])) {
    header("Location: user_login.php");
    exit();
}

$page_title = "Dashboard | CVBMS";
include("../includes/user_header.php");
include("../includes/user_navbar.php");
?>

<style>
    .dashboard-bg {
        position: fixed;
        inset: 0;
        z-index: -1;
        background: linear-gradient(to bottom, rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.95)), 
                    url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80');
        background-size: cover;
        background-position: center;
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 2.5rem;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-main {
        background: linear-gradient(135deg, #004aad 0%, #00357a 100%);
        border: 1px solid rgba(0, 212, 255, 0.4);
    }

    .btn-main:hover {
        transform: translateY(-10px);
        border-color: #00d4ff;
        box-shadow: 0 15px 30px rgba(0, 74, 173, 0.6);
    }

    .text-mmu-glow {
        color: #00d4ff;
        text-shadow: 0 0 15px rgba(0, 212, 255, 0.5);
    }
</style>

<div class="dashboard-bg"></div>

<div class="relative z-10 w-full pt-32 pb-20 px-6">
    <div class="max-w-7xl mx-auto">
        
        <div class="mb-14">
            <h1 class="text-5xl font-black text-white mb-4 tracking-tight">
                Welcome back, <span class="text-mmu-glow"><?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            </h1>
            <p class="text-slate-400 text-xl font-medium max-w-2xl leading-relaxed">
                What would you like to do today?
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <a href="venues.php" class="glass-card btn-main p-10 flex flex-col items-center text-center group">
                <div class="w-20 h-20 bg-white/10 rounded-3xl flex items-center justify-center mb-6 group-hover:bg-white/20 transition-all">
                    <i data-lucide="search" class="w-10 h-10 text-white"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-3 tracking-tight">Browse Venues</h3>
                <p class="text-blue-100/70 text-sm leading-relaxed">View all available halls, labs, and discussion rooms.</p>
            </a>

            <a href="venues.php" class="glass-card btn-main p-10 flex flex-col items-center text-center group">
                <div class="w-20 h-20 bg-white/10 rounded-3xl flex items-center justify-center mb-6 group-hover:bg-white/20 transition-all">
                    <i data-lucide="calendar-plus" class="w-10 h-10 text-white"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-3 tracking-tight">New Booking</h3>
                <p class="text-blue-100/70 text-sm leading-relaxed">Submit a fast request for your next event or session.</p>
            </a>

            <a href="my_bookings.php" class="glass-card btn-main p-10 flex flex-col items-center text-center group">
                <div class="w-20 h-20 bg-white/10 rounded-3xl flex items-center justify-center mb-6 group-hover:bg-white/20 transition-all">
                    <i data-lucide="clipboard-list" class="w-10 h-10 text-white"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-3 tracking-tight">Booking History</h3>
                <p class="text-blue-100/70 text-sm leading-relaxed">Monitor your status and manage past reservations.</p>
            </a>

        </div>
    </div>
</div>

<?php include("../includes/user_footer.php"); ?>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
</script>