<?php
// File path: user/venues.php
session_start();
$page_title = "Available Venues";
include("../includes/user_header.php");
include("../includes/user_navbar.php");
require_once("../config/db.php");

// 💡 提取 Available 與 Maintenance 狀態的場地
$sql = "SELECT vid, vname, category, max_cap, deposit, status
        FROM venue
        WHERE status IN ('available', 'maintenance')
        ORDER BY category ASC, vname ASC";

$result = $conn->query($sql);
?>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b' } } } } }
</script>

<div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-6xl mx-auto">
        
        <div class="mb-10 text-center">
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Available Venues</h1>
            <p class="text-sm text-slate-500 mt-2">Select a venue below to view details and proceed with your booking.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($venue = $result->fetch_assoc()): ?>
                    <div class="bg-white rounded-2xl shadow-sm border <?php echo $venue['status'] === 'maintenance' ? 'border-amber-300 bg-amber-50' : 'border-slate-200 hover:shadow-md'; ?> overflow-hidden transition-all flex flex-col">
                        
                        <div class="p-6 flex-1">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-slate-800"><?php echo htmlspecialchars($venue["vname"]); ?></h3>
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider"><?php echo htmlspecialchars($venue["category"]); ?></span>
                                </div>
                                <?php if ($venue['status'] === 'maintenance'): ?>
                                    <span class="px-2 py-1 bg-amber-100 text-amber-700 border border-amber-200 rounded text-[10px] font-black uppercase tracking-widest flex items-center">
                                        <i data-lucide="wrench" class="w-3 h-3 mr-1"></i> Maintenance
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="space-y-2 mb-6">
                                <div class="flex items-center text-sm text-slate-600">
                                    <i data-lucide="users" class="w-4 h-4 mr-2 text-slate-400"></i>
                                    <span>Capacity: <strong><?php echo (int)$venue["max_cap"]; ?> Pax</strong></span>
                                </div>
                                <div class="flex items-center text-sm text-slate-600">
                                    <i data-lucide="banknote" class="w-4 h-4 mr-2 text-slate-400"></i>
                                    <span>Deposit: <strong class="text-emerald-600 font-mono">RM <?php echo number_format((float)$venue["deposit"], 2); ?></strong></span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 border-t <?php echo $venue['status'] === 'maintenance' ? 'border-amber-200 bg-amber-100/50' : 'border-slate-100 bg-slate-50'; ?>">
                            <a href="venue_details.php?vid=<?php echo urlencode($venue["vid"]); ?>" 
                               class="block w-full py-2.5 text-center text-sm font-bold rounded-lg transition-colors <?php echo $venue['status'] === 'maintenance' ? 'bg-amber-600 hover:bg-amber-700 text-white shadow' : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow'; ?>">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full py-12 text-center text-slate-500 bg-white rounded-2xl border border-slate-200">
                    <i data-lucide="inbox" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>
                    <p class="font-medium">There are currently no venues available.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>lucide.createIcons();</script>
<?php include("../includes/user_footer.php"); ?>