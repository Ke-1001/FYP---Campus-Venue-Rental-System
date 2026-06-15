<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$sql = "SELECT v.vid, v.vname, v.vcid, v.description, 
               vc.category, 
               MIN(vp.pic) AS pic
        FROM venue v 
        LEFT JOIN vcategory vc ON v.vcid = vc.vcid 
        LEFT JOIN vpic vp ON v.vid = vp.vid 
        GROUP BY v.vid, v.vname, v.vcid, v.description, vc.category
        LIMIT 3";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CVBMS | Campus Venue Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        mmu: { 
                            core: '#004aad',   // Mass Color
                            glow: '#3b82f6'    // Emissive Color
                        }
                    },
                    fontFamily: {
                        sans: ['Century Gothic', 'CenturyGothic', 'Century', 'Arial', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="../assets/css/user_css.css?v=1.2">
</head>
<body class="font-sans antialiased selection:bg-mmu-glow selection:text-white min-h-screen relative overflow-x-hidden text-slate-200">

<div class="fixed inset-0 z-0">
    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80" alt="Campus" class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-slate-900/45"></div>
    <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-mmu-core/12 rounded-full filter blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-0 right-1/4 w-[400px] h-[400px] bg-mmu-glow/8 rounded-full filter blur-[100px] pointer-events-none"></div>
</div>

<nav class="glass-nav fixed w-full z-50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            
            <div class="flex-shrink-0 flex items-center gap-2 cursor-pointer">
                <div class="w-8 h-8 bg-mmu-core rounded-lg flex items-center justify-center text-white font-bold shadow-lg">C</div>
                <span class="font-extrabold text-xl tracking-tight text-white">CVBMS</span>
            </div>

            <div class="hidden md:flex space-x-8">
                <a href="homepage.php" class="text-white font-semibold px-2 py-2 text-lg drop-shadow-[0_0_8px_rgba(255,255,255,0.5)]">Home</a>
                <a href="venues.php" class="text-slate-400 hover:text-white transition-colors px-2 py-2 text-lg font-medium">Venues</a>
                <a href="my_bookings.php" class="text-slate-400 hover:text-white transition-colors px-2 py-2 text-lg font-medium">My Bookings</a>
                
                <?php if (isset($_SESSION['uid'])): ?>
                    <a href="/FYP/User/user_dashboard.php" class="text-slate-400 hover:text-white transition-colors px-2 py-2 text-lg font-medium">Dashboard</a>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-4">
                <?php if (isset($_SESSION['uid'])): ?>
                    <a href="profile.php" class="flex items-center gap-2 bg-white/10 py-1.5 px-4 rounded-full border border-white/10 backdrop-blur-md hover:bg-white/20 transition-all" title="View Profile">
                        <i data-lucide="user" class="w-4 h-4 text-mmu-glow"></i>
                        <span class="text-sm font-semibold text-white max-w-[100px] truncate">
                            <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </a>

                    <a href="../User/user_logout.php" class="text-red-400 hover:text-red-300 transition-colors" title="Logout">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </a>
                <?php else: ?>
                    <a href="../user/user_login.php" class="text-slate-300 hover:text-white font-semibold text-sm transition-colors">Login</a>
                    <a href="../user/user_register.php" class="bg-mmu-core hover:bg-blue-800 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-lg transition-all">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="relative z-10 w-full h-full overflow-y-auto pt-32 pb-20">
    <div class="max-w-4xl mx-auto text-center px-4 mb-24">
        <h1 class="text-5xl md:text-6xl font-extrabold text-white tracking-tight mb-6 drop-shadow-md">
            Find & Book Your <br class="md:hidden"><span class="text-mmu-glow drop-shadow-[0_0_20px_rgba(59,130,246,0.5)]">Ideal Space.</span>
        </h1>
        <p class="text-slate-300 text-base md:text-lg mb-12 max-w-2xl mx-auto font-medium">
            Reserve discussion rooms, halls, and labs across the MMU campus in seconds. Simple, fast, and guaranteed.
        </p>

        <form action="venues.php" method="GET" class="max-w-2xl mx-auto relative group mb-10">
            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                <i data-lucide="search" class="w-5 h-5 text-slate-400"></i>
            </div>
            <input type="text" name="search" placeholder="Search for a room, hall, or facility..." 
                   class="input-glass w-full block pl-12 pr-36 py-4 rounded-2xl text-slate-900 placeholder-slate-400 font-semibold shadow-2xl text-lg">
            <button type="submit" class="absolute inset-y-2 right-2 px-8 bg-mmu-core hover:bg-blue-800 text-white font-bold rounded-xl shadow-[0_4px_14px_0_rgba(0,74,173,0.39)] transition-all text-sm flex items-center gap-2 group-hover:shadow-[0_6px_20px_rgba(0,74,173,0.23)]">
                Explore <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
        </form>

        <div class="flex flex-wrap justify-center items-center gap-x-8 gap-y-4 text-slate-300 text-sm font-semibold">
            <div class="flex items-center gap-2"><i data-lucide="zap" class="w-4 h-4 text-mmu-glow drop-shadow-[0_0_14px_rgba(59,130,246,0.8)]"></i> Instant Confirmation</div>
            <div class="hidden sm:block w-1.5 h-1.5 rounded-full bg-slate-600"></div>
            <div class="flex items-center gap-2"><i data-lucide="layers" class="w-4 h-4 text-mmu-glow drop-shadow-[0_0_14px_rgba(59,130,246,0.8)]"></i> Various Room Types</div>
            <div class="hidden sm:block w-1.5 h-1.5 rounded-full bg-slate-600"></div>
            <div class="flex items-center gap-2"><i data-lucide="shield-check" class="w-4 h-4 text-mmu-glow drop-shadow-[0_0_14px_rgba(59,130,246,0.8)]"></i> Guaranteed Availability</div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-end mb-8 pl-4 border-l-4 border-mmu-glow">
            <div>
                <h2 class="text-3xl font-extrabold text-white tracking-tight">Popular Venues</h2>
                <p class="text-slate-400 text-sm mt-1">Frequently booked spaces across the campus.</p>
            </div>
            <a href="venues.php" class="text-mmu-glow font-bold hover:text-white transition-colors flex items-center gap-1">
                View Directory <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="glass-panel venue-card rounded-3xl overflow-hidden hover-lift flex flex-col group">
                    <div class="h-56 bg-slate-100/20 relative overflow-hidden flex items-center justify-center">
                     <?php if(!empty($row['pic'])): ?>
                     <img src="../uploads/venues/<?php echo htmlspecialchars($row['pic']); ?>" alt="Venue" class="w-full h-full object-cover opacity-95 group-hover:opacity-100 group-hover:scale-105 transition-all duration-700">
                     <?php else: ?>
                     <i data-lucide="image" class="w-12 h-12 text-slate-600"></i>
                     <?php endif; ?>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/35 via-black/10 to-transparent"></div>
                    <div class="absolute top-4 left-4 bg-black/35 backdrop-blur-sm border border-white/10 text-[11px] font-bold px-3 py-1.5 rounded-lg text-white uppercase tracking-wider shadow">
                    <?php echo htmlspecialchars($row['category'] ?? 'Standard'); ?>
                    </div>
                </div>
                        
                        <div class="p-8 flex-1 flex flex-col relative z-10 -mt-6">
                            <h3 class="text-2xl font-bold text-white mb-3 truncate group-hover:text-mmu-glow transition-colors drop-shadow-md">
                                <?php echo htmlspecialchars($row['vname']); ?>
                            </h3>
                            <p class="text-slate-300 text-sm mb-8 flex-1 line-clamp-2 leading-relaxed">
                                <?php echo htmlspecialchars($row['description'] ?? 'No topological description provided for this structural asset.'); ?>
                            </p>
                            
                            <a href="venue_details.php?vid=<?php echo $row['vid']; ?>" 
                            class="block w-full text-center py-3.5 bg-white/10 hover:bg-mmu-core text-white text-sm font-bold rounded-xl border border-white/10 hover:border-mmu-core transition-all shadow-lg hover:shadow-[0_4px_20px_rgba(0,74,173,0.5)]">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-3 glass-panel rounded-3xl p-16 text-center">
                    <i data-lucide="database" class="w-16 h-16 text-slate-500 mx-auto mb-4"></i>
                    <h3 class="text-xl font-bold text-white mb-2">No Venues Found</h3>
                    <p class="text-slate-400">There are currently no structural assets listed in the database.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer style="position: relative; z-index: 50; background-color: #020617; border-top: 1px solid rgba(255, 255, 255, 0.1); padding: 40px 24px; margin-top: 80px; font-family: 'Century Gothic', CenturyGothic, Century, Arial, sans-serif;">
    <div style="max-width: 1280px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px;">
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 32px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding-bottom: 24px;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <div style="background-color: #2563eb; padding: 6px 12px; border-radius: 8px; font-weight: 900; color: #ffffff; font-size: 14px;">C</div>
                    <span style="font-size: 20px; font-weight: 900; color: #00d4ff; letter-spacing: -0.05em;">CVBMS</span>
                </div>
                <p style="color: #94a3b8; font-size: 14px; margin: 0; max-width: 360px; line-height: 1.5;">
                    Campus Venue Booking Management System. Engineered for optimal student resource allocation.
                </p>
            </div>
            <div style="min-width: 250px;">
                <h4 style="color: #ffffff; font-size: 16px; font-weight: 700; margin: 0 0 12px 0;">Need Assistance? Contact Helpdesk</h4>
                <div style="display: flex; flex-direction: column; gap: 8px; font-size: 14px;">
                    <a href="mailto:support@mmu.edu.my" style="color: #00d4ff; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                        <span>📧</span> support@mmu.edu.my
                    </a>
                    <span style="color: #cbd5e1; display: flex; align-items: center; gap: 8px;">
                        <span>📞</span> IT Helpdesk: +6011-234-5678
                    </span>
                    <span style="color: #64748b; font-size: 12px; padding-left: 24px;">
                        Mon - Fri (9:00 AM - 5:00 PM)
                    </span>
                </div>
            </div>
        </div>
        <div style="text-align: center; color: #64748b; font-size: 12px;">
            &copy; 2026 Campus Venue Booking Management System. All rights reserved.
        </div>
    </div>
</footer>

<script>
    lucide.createIcons();
</script>
</body>
</html>