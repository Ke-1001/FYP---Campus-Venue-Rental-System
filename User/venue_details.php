<?php
$page_title = "Venue Details";
include("../includes/user_header.php");
include("../includes/user_navbar.php");
include("../config/db.php");

if (!isset($_GET['vid']) || !is_numeric($_GET['vid'])) {
    die("Invalid venue");
}

$venue_id = $_GET['vid'];

$stmt = $conn->prepare("SELECT * FROM venue WHERE vid=?");
$stmt->bind_param("i", $venue_id);
$stmt->execute();
$result = $stmt->get_result();

$row = $result->fetch_assoc();
?>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-3xl mx-auto">
        
        <?php if ($row): ?>
            <div class="mb-6 flex items-center">
                <a href="venues.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center transition">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back to Venues
                </a>
            </div>

            <div class="bg-white rounded-2xl shadow-md border border-slate-200 overflow-hidden">
                <div class="p-8">
                    <div class="flex justify-between items-start mb-6 border-b border-slate-100 pb-6">
                        <div>
                            <h2 class="text-3xl font-extrabold text-slate-800"><?php echo htmlspecialchars($row["vname"]); ?></h2>
                            <p class="text-sm font-bold text-slate-400 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($row["category"]); ?></p>
                        </div>
                        <?php if ($row['status'] === 'maintenance'): ?>
                            <span class="px-3 py-1 bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-black uppercase tracking-widest flex items-center">
                                <i data-lucide="wrench" class="w-4 h-4 mr-2"></i> Maintenance
                            </span>
                        <?php else: ?>
                            <span class="px-3 py-1 bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-black uppercase tracking-widest flex items-center">
                                <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i> Available
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 flex items-center">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm mr-4 text-indigo-600"><i data-lucide="users" class="w-5 h-5"></i></div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Maximum Capacity</p>
                                <p class="text-lg font-bold text-slate-800"><?php echo (int)$row["max_cap"]; ?> Pax</p>
                            </div>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 flex items-center">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm mr-4 text-emerald-600"><i data-lucide="banknote" class="w-5 h-5"></i></div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Required Deposit</p>
                                <p class="text-lg font-mono font-bold text-slate-800">RM <?php echo number_format((float)$row["deposit"], 2); ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if(!empty($row['description'])): ?>
                    <div class="mb-8">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Description</h4>
                        <p class="text-sm text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-lg border border-slate-100">
                            <?php echo nl2br(htmlspecialchars($row['description'])); ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <div class="flex justify-end pt-6 border-t border-slate-100">
                        <?php if ($row['status'] === 'available'): ?>
                            <a href="booking_form.php?vid=<?php echo urlencode($row["vid"]); ?>" class="px-8 py-3 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-md transition flex items-center">
                                Proceed to Book <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                            </a>
                        <?php else: ?>
                            <button disabled class="px-8 py-3 text-sm font-bold text-slate-400 bg-slate-100 border border-slate-200 rounded-lg cursor-not-allowed flex items-center">
                                <i data-lucide="lock" class="w-4 h-4 mr-2"></i> Unavailable for Booking
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white p-12 rounded-2xl shadow-sm border border-slate-200 text-center">
                <i data-lucide="search-X" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
                <h2 class="text-2xl font-bold text-slate-800 mb-2">Venue Not Found</h2>
                <p class="text-slate-500 mb-6">The selected venue does not exist or has been removed from the system.</p>
                <a href="venues.php" class="px-6 py-2 bg-slate-800 text-white font-bold rounded-lg hover:bg-slate-700 transition">Back to Venues</a>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>lucide.createIcons();</script>
<?php include("../includes/user_footer.php"); ?>

</body>
</html>