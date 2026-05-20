<?php
$page_title = "Available Venues";
include("../includes/user_header.php");
include("../includes/user_navbar.php");
include("../config/db.php");

$result = $conn->query("
    SELECT 
        v.*, 
        vc.category,
        vp.pic AS main_pic
    FROM venue v
    JOIN vcategory vc ON v.vcid = vc.vcid
    LEFT JOIN (
        SELECT vid, MIN(pic_id) AS first_pic_id
        FROM vpic
        GROUP BY vid
    ) first_vpic ON v.vid = first_vpic.vid
    LEFT JOIN vpic vp ON first_vpic.first_pic_id = vp.pic_id
    WHERE v.status = 'available'
    ORDER BY v.vname ASC
");
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

                    <?php
                        $image_path = "";
                        if (!empty($venue["main_pic"])) {
                            $image_path = "../uploads/venues/" . $venue["main_pic"];
                        }
                    ?>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 hover:shadow-md overflow-hidden transition-all flex flex-col">
                        
                        <!-- Venue Image -->
                        <div class="h-48 bg-slate-100 overflow-hidden relative">
                            <?php if (!empty($image_path)): ?>
                                <img 
                                    src="<?php echo htmlspecialchars($image_path); ?>" 
                                    alt="<?php echo htmlspecialchars($venue["vname"]); ?>"
                                    class="w-full h-full object-cover"
                                >
                            <?php else: ?>
                                <div class="w-full h-full flex flex-col items-center justify-center text-slate-400">
                                    <i data-lucide="image" class="w-12 h-12 mb-2"></i>
                                    <p class="text-xs font-bold uppercase tracking-widest">No Image</p>
                                </div>
                            <?php endif; ?>

                            <div class="absolute top-3 left-3">
                                <span class="px-2.5 py-1 bg-white/90 backdrop-blur text-slate-700 border border-white rounded-lg text-[10px] font-black uppercase tracking-widest">
                                    <?php echo htmlspecialchars($venue["category"]); ?>
                                </span>
                            </div>
                        </div>

                        <div class="p-6 flex-1">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-slate-800">
                                        <?php echo htmlspecialchars($venue["vname"]); ?>
                                    </h3>

                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                                        <?php echo htmlspecialchars($venue["vid"]); ?>
                                    </span>
                                </div>

                                <span class="px-2 py-1 bg-emerald-100 text-emerald-700 border border-emerald-200 rounded text-[10px] font-black uppercase tracking-widest flex items-center">
                                    <i data-lucide="check-circle" class="w-3 h-3 mr-1"></i> Available
                                </span>
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

                        <div class="p-4 border-t border-slate-100 bg-slate-50">
                            <a href="venue_details.php?vid=<?php echo urlencode($venue["vid"]); ?>" 
                               class="block w-full py-2.5 text-center text-sm font-bold rounded-lg transition-colors bg-indigo-600 hover:bg-indigo-700 text-white shadow">
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

</body>
</html>