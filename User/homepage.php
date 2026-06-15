<?php
require_once __DIR__ . '/../config/db.php';

$page_title = "CVBMS | Campus Venue Booking";

$sql = "SELECT v.vid, v.vname, v.vcid, v.description, 
               vc.category, 
               MIN(vp.pic) AS pic
        FROM venue v 
        LEFT JOIN vcategory vc ON v.vcid = vc.vcid 
        LEFT JOIN vpic vp ON v.vid = vp.vid 
        GROUP BY v.vid, v.vname, v.vcid, v.description, vc.category
        LIMIT 3";
$result = $conn->query($sql);

require_once __DIR__ . '/../includes/user_header.php';
require_once __DIR__ . '/../includes/user_navbar.php';
?>

<section class="homepage-hero relative overflow-hidden text-slate-100">
    <div class="absolute inset-0 z-0">
        <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80" alt="Campus" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-slate-900/30"></div>
        <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-mmu-core/10 rounded-full filter blur-[120px] pointer-events-none"></div>
        <div class="absolute bottom-0 right-1/4 w-[400px] h-[400px] bg-mmu-glow/10 rounded-full filter blur-[100px] pointer-events-none"></div>
    </div>

    <div class="relative z-10 max-w-4xl mx-auto text-center px-4 py-28 md:py-32">
        <h1 class="text-5xl md:text-6xl font-extrabold text-white tracking-tight mb-6 drop-shadow-md">
            Find & Book Your <br class="md:hidden"><span class="text-mmu-glow drop-shadow-[0_0_20px_rgba(59,130,246,0.5)]">Ideal Space.</span>
        </h1>
        <p class="text-slate-100 text-base md:text-lg mb-12 max-w-2xl mx-auto font-medium drop-shadow">
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

        <div class="flex flex-wrap justify-center items-center gap-x-8 gap-y-4 text-slate-100 text-sm font-semibold drop-shadow">
            <div class="flex items-center gap-2"><i data-lucide="zap" class="w-4 h-4 text-mmu-glow"></i> Instant Confirmation</div>
            <div class="hidden sm:block w-1.5 h-1.5 rounded-full bg-slate-300"></div>
            <div class="flex items-center gap-2"><i data-lucide="layers" class="w-4 h-4 text-mmu-glow"></i> Various Room Types</div>
            <div class="hidden sm:block w-1.5 h-1.5 rounded-full bg-slate-300"></div>
            <div class="flex items-center gap-2"><i data-lucide="shield-check" class="w-4 h-4 text-mmu-glow"></i> Guaranteed Availability</div>
        </div>
    </div>
</section>

<section class="bg-[#f6f8fb] py-16 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-end mb-8 pl-4 border-l-4 border-mmu-core">
            <div>
                <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Popular Venues</h2>
                <p class="text-slate-500 text-sm mt-1">Frequently booked spaces across the campus.</p>
            </div>
            <a href="venues.php" class="text-mmu-core font-bold hover:text-blue-800 transition-colors flex items-center gap-1">
                View Directory <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="venue-card bg-white border border-slate-200 rounded-3xl overflow-hidden hover-lift flex flex-col group shadow-sm">
                        <div class="h-56 bg-slate-100 relative overflow-hidden flex items-center justify-center">
                            <?php if(!empty($row['pic'])): ?>
                                <img src="../uploads/venues/<?php echo htmlspecialchars($row['pic']); ?>" alt="Venue" class="w-full h-full object-cover opacity-100 group-hover:scale-105 transition-all duration-700">
                            <?php else: ?>
                                <i data-lucide="image" class="w-12 h-12 text-slate-400"></i>
                            <?php endif; ?>
                            <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-black/5 to-transparent"></div>
                            <div class="absolute top-4 left-4 bg-white/90 backdrop-blur-sm border border-white/70 text-[11px] font-bold px-3 py-1.5 rounded-lg text-slate-700 uppercase tracking-wider shadow-sm">
                                <?php echo htmlspecialchars($row['category'] ?? 'Standard'); ?>
                            </div>
                        </div>
                        
                        <div class="p-7 flex-1 flex flex-col relative z-10">
                            <h3 class="text-2xl font-bold text-slate-900 mb-3 truncate group-hover:text-mmu-core transition-colors">
                                <?php echo htmlspecialchars($row['vname']); ?>
                            </h3>
                            <p class="text-slate-500 text-sm mb-8 flex-1 line-clamp-2 leading-relaxed">
                                <?php echo htmlspecialchars($row['description'] ?? 'No topological description provided for this structural asset.'); ?>
                            </p>
                            
                            <a href="venue_details.php?vid=<?php echo urlencode($row['vid']); ?>" 
                               class="block w-full text-center py-3.5 bg-mmu-core hover:bg-blue-800 text-white text-sm font-bold rounded-xl transition-all shadow-sm">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-3 bg-white border border-slate-200 rounded-3xl p-16 text-center shadow-sm">
                    <i data-lucide="database" class="w-16 h-16 text-slate-400 mx-auto mb-4"></i>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">No Venues Found</h3>
                    <p class="text-slate-500">There are currently no structural assets listed in the database.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include("../includes/user_footer.php"); ?>
