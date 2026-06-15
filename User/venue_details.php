<?php
$page_title = "Venue Details";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/user_header.php';
require_once __DIR__ . '/../includes/user_navbar.php';

if (!isset($_GET['vid']) || !preg_match('/^[A-Za-z0-9]+$/', $_GET['vid'])) {
    die("Invalid venue");
}

$venue_id = $_GET['vid'];

$stmt = $conn->prepare("
    SELECT v.*, vc.category 
    FROM venue v 
    JOIN vcategory vc ON v.vcid = vc.vcid 
    WHERE v.vid = ?
");
$stmt->bind_param("s", $venue_id);
$stmt->execute();
$result = $stmt->get_result();

$row = $result->fetch_assoc();

$pics = [];

if ($row) {
    $pic_stmt = $conn->prepare("
        SELECT pic_id, pic, description
        FROM vpic
        WHERE vid = ?
        ORDER BY pic_id ASC
    ");
    $pic_stmt->bind_param("s", $venue_id);
    $pic_stmt->execute();
    $pic_result = $pic_stmt->get_result();

    while ($pic = $pic_result->fetch_assoc()) {
        $pics[] = $pic;
    }

    $pic_stmt->close();
}

$main_pic = "";
if (!empty($pics)) {
    $main_pic = "../uploads/venues/" . $pics[0]["pic"];
}

$is_maintenance = $row && strtolower($row['status']) === 'maintenance';
$is_available = $row && strtolower($row['status']) === 'available';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-4xl mx-auto">
        
        <?php if ($row): ?>
            <div class="mb-6 flex items-center">
                <a href="venues.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center transition">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back to Venues
                </a>
            </div>

            <div class="bg-white rounded-2xl shadow-md border border-slate-200 overflow-hidden">

                <!-- Main Venue Image -->
                <div class="h-72 bg-slate-100 overflow-hidden relative">
                    <?php if (!empty($main_pic)): ?>
                        <img 
                            src="<?php echo htmlspecialchars($main_pic); ?>" 
                            alt="<?php echo htmlspecialchars($row["vname"]); ?>"
                            class="w-full h-full object-cover"
                        >
                    <?php else: ?>
                        <div class="w-full h-full flex flex-col items-center justify-center text-slate-400">
                            <i data-lucide="image" class="w-16 h-16 mb-3"></i>
                            <p class="text-sm font-bold uppercase tracking-widest">No Venue Image</p>
                        </div>
                    <?php endif; ?>

                    <div class="absolute top-4 left-4">
                        <span class="px-3 py-1 bg-white/90 backdrop-blur text-slate-700 border border-white rounded-lg text-xs font-black uppercase tracking-widest">
                            <?php echo htmlspecialchars($row["category"]); ?>
                        </span>
                    </div>
                </div>

                <div class="p-8">
                    <div class="flex justify-between items-start mb-6 border-b border-slate-100 pb-6">
                        <div>
                            <h2 class="text-3xl font-extrabold text-slate-800">
                                <?php echo htmlspecialchars($row["vname"]); ?>
                            </h2>

                            <p class="text-sm font-bold text-slate-400 uppercase tracking-widest mt-1">
                                <?php echo htmlspecialchars($row["vid"]); ?> • <?php echo htmlspecialchars($row["category"]); ?>
                            </p>
                        </div>

                        <?php if ($is_maintenance): ?>
                            <span class="px-3 py-1 bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-black uppercase tracking-widest flex items-center">
                                <i data-lucide="wrench" class="w-4 h-4 mr-2"></i> Maintenance
                            </span>
                        <?php elseif ($is_available): ?>
                            <span class="px-3 py-1 bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-black uppercase tracking-widest flex items-center">
                                <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i> Available
                            </span>
                        <?php else: ?>
                            <span class="px-3 py-1 bg-slate-100 text-slate-600 border border-slate-200 rounded-lg text-xs font-black uppercase tracking-widest flex items-center">
                                <i data-lucide="circle-off" class="w-4 h-4 mr-2"></i> Unavailable
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 flex items-center">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm mr-4 text-indigo-600">
                                <i data-lucide="users" class="w-5 h-5"></i>
                            </div>

                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">
                                    Maximum Capacity
                                </p>
                                <p class="text-lg font-bold text-slate-800">
                                    <?php echo (int)$row["max_cap"]; ?> Pax
                                </p>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 flex items-center">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm mr-4 text-emerald-600">
                                <i data-lucide="banknote" class="w-5 h-5"></i>
                            </div>

                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">
                                    Required Deposit
                                </p>
                                <p class="text-lg font-mono font-bold text-slate-800">
                                    RM <?php echo number_format((float)$row["deposit"], 2); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <?php if ($is_maintenance): ?>
                        <div class="mb-8 bg-amber-50 border border-amber-200 rounded-xl p-5 flex items-start">
                            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center mr-4 text-amber-700 flex-shrink-0">
                                <i data-lucide="wrench" class="w-5 h-5"></i>
                            </div>

                            <div>
                                <h4 class="text-sm font-black text-amber-800 uppercase tracking-wider mb-1">
                                    Venue Under Maintenance
                                </h4>

                                <p class="text-sm text-amber-700 leading-relaxed">
                                    This venue is currently under maintenance. You may view the venue details, but booking is temporarily not allowed.
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($pics)): ?>
                        <div class="mb-8">
                            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
                                Venue Gallery
                            </h4>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <?php foreach ($pics as $pic): ?>
                                    <?php $gallery_pic_path = "../uploads/venues/" . $pic["pic"]; ?>

                                    <div class="h-28 bg-slate-100 rounded-xl overflow-hidden border border-slate-200">
                                        <img 
                                            src="<?php echo htmlspecialchars($gallery_pic_path); ?>" 
                                            alt="<?php echo htmlspecialchars($row["vname"]); ?>"
                                            class="w-full h-full object-cover cursor-zoom-in hover:scale-105 transition-transform duration-300"
                                            onclick="openImageModal('<?php echo htmlspecialchars($gallery_pic_path); ?>')"
                                        >
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($row['description'])): ?>
                        <div class="mb-8">
                            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                                Description
                            </h4>

                            <p class="text-sm text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-lg border border-slate-100">
                                <?php echo nl2br(htmlspecialchars($row['description'])); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="flex justify-end pt-6 border-t border-slate-100">
                        <?php if ($is_available): ?>
                            <a href="booking_form.php?vid=<?php echo urlencode($row["vid"]); ?>" 
                            class="px-8 py-3 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-md transition flex items-center">
                                Proceed to Book 
                                <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                            </a>
                        <?php elseif ($is_maintenance): ?>
                            <button disabled 
                                    class="px-8 py-3 text-sm font-bold text-amber-700 bg-amber-100 border border-amber-200 rounded-lg cursor-not-allowed flex items-center">
                                <i data-lucide="wrench" class="w-4 h-4 mr-2"></i> 
                                Booking Closed for Maintenance
                            </button>
                        <?php else: ?>
                            <button disabled 
                                    class="px-8 py-3 text-sm font-bold text-slate-400 bg-slate-100 border border-slate-200 rounded-lg cursor-not-allowed flex items-center">
                                <i data-lucide="lock" class="w-4 h-4 mr-2"></i> 
                                Unavailable for Booking
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="bg-white p-12 rounded-2xl shadow-sm border border-slate-200 text-center">
                <i data-lucide="search-x" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>

                <h2 class="text-2xl font-bold text-slate-800 mb-2">
                    Venue Not Found
                </h2>

                <p class="text-slate-500 mb-6">
                    The selected venue does not exist or has been removed from the system.
                </p>

                <a href="venues.php" class="px-6 py-2 bg-slate-800 text-white font-bold rounded-lg hover:bg-slate-700 transition">
                    Back to Venues
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- Image Preview Modal -->
<div 
    id="imageModal" 
    class="fixed inset-0 bg-black/80 z-50 hidden items-center justify-center px-4"
    onclick="closeImageModal()"
>
    <button 
        type="button"
        onclick="closeImageModal()"
        class="absolute top-6 right-6 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition"
        aria-label="Close image preview"
    >
        <i data-lucide="x" class="w-6 h-6"></i>
    </button>

    <img 
        id="modalImage"
        src=""
        alt="Venue image preview"
        class="max-w-full max-h-[85vh] rounded-xl shadow-2xl object-contain"
        onclick="event.stopPropagation()"
    >
</div>

<script>
lucide.createIcons();

function openImageModal(imageSrc) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');

    modalImage.src = imageSrc;
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    document.body.classList.add('overflow-hidden');
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');

    modal.classList.add('hidden');
    modal.classList.remove('flex');

    modalImage.src = '';
    document.body.classList.remove('overflow-hidden');
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
});
</script>

<?php include("../includes/user_footer.php"); ?>

</body>
</html>