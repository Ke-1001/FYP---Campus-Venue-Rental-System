<?php
$page_title = "Available Venues";
include("../includes/user_header.php");
include("../includes/user_navbar.php");
include("../config/db.php");

// Get search, filter and sort values from URL
$search = trim($_GET['search'] ?? '');
$category_filter = trim($_GET['category'] ?? '');
$sort = trim($_GET['sort'] ?? '');

// Get all categories for filter dropdown
$category_result = $conn->query("
    SELECT vcid, category
    FROM vcategory
    ORDER BY category ASC
");

// Main query
$sql = "
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
    WHERE v.status IN ('available', 'maintenance')
";

$params = [];
$types = "";

// Search by venue name or venue ID
if ($search !== '') {
    $sql .= " AND (v.vname LIKE ? OR v.vid LIKE ?)";
    $search_like = "%" . $search . "%";
    $params[] = $search_like;
    $params[] = $search_like;
    $types .= "ss";
}

// Filter by category
if ($category_filter !== '') {
    $sql .= " AND v.vcid = ?";
    $params[] = (int)$category_filter;
    $types .= "i";
}

// Sorting
switch ($sort) {
    case 'capacity_asc':
        $sql .= " ORDER BY v.max_cap ASC";
        break;

    case 'capacity_desc':
        $sql .= " ORDER BY v.max_cap DESC";
        break;

    case 'deposit_asc':
        $sql .= " ORDER BY v.deposit ASC";
        break;

    case 'deposit_desc':
        $sql .= " ORDER BY v.deposit DESC";
        break;

    default:
        $sql .= " ORDER BY v.vname ASC";
        break;
}

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
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

        <!-- Search, Filter and Sort Section -->
        <form method="GET" action="venues.php" id="venueFilterForm" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <!-- Search -->
                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">
                        Search Venue
                    </label>
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                        <input 
                            type="text" 
                            name="search"
                            id="venueSearchInput"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search by name or venue ID..."
                            class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                    </div>
                </div>

                <!-- Category Filter -->
                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">
                        Filter by Category
                    </label>
                    <select 
                        name="category"
                        class="auto-submit w-full px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">All Categories</option>

                        <?php if ($category_result && $category_result->num_rows > 0): ?>
                            <?php while ($cat = $category_result->fetch_assoc()): ?>
                                <option 
                                    value="<?php echo htmlspecialchars($cat['vcid']); ?>"
                                    <?php echo ($category_filter == $cat['vcid']) ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Sort -->
                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">
                        Sort by
                    </label>
                    <select 
                        name="sort"
                        class="auto-submit w-full px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="" <?php echo ($sort === '') ? 'selected' : ''; ?>>Default</option>
                        <option value="capacity_asc" <?php echo ($sort === 'capacity_asc') ? 'selected' : ''; ?>>Capacity: Low to High</option>
                        <option value="capacity_desc" <?php echo ($sort === 'capacity_desc') ? 'selected' : ''; ?>>Capacity: High to Low</option>
                        <option value="deposit_asc" <?php echo ($sort === 'deposit_asc') ? 'selected' : ''; ?>>Deposit: Low to High</option>
                        <option value="deposit_desc" <?php echo ($sort === 'deposit_desc') ? 'selected' : ''; ?>>Deposit: High to Low</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between mt-5">
                <p class="w-4 h-4 mr-2"></p>

                <?php if ($search !== '' || $category_filter !== '' || $sort !== ''): ?>
                    <a 
                        href="venues.php"
                        class="inline-flex items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition-colors"
                    >
                        <i data-lucide="rotate-ccw" class="w-4 h-4 mr-2"></i>
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($venue = $result->fetch_assoc()): ?>

                    <?php
                        $image_path = "";
                        if (!empty($venue["main_pic"])) {
                            $image_path = "../uploads/venues/" . $venue["main_pic"];
                        }

                        $is_maintenance = strtolower($venue["status"]) === "maintenance";
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

                                <?php if ($is_maintenance): ?>
                                    <span class="px-2 py-1 bg-amber-100 text-amber-700 border border-amber-200 rounded text-[10px] font-black uppercase tracking-widest flex items-center">
                                        <i data-lucide="wrench" class="w-3 h-3 mr-1"></i> Maintenance
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-emerald-100 text-emerald-700 border border-emerald-200 rounded text-[10px] font-black uppercase tracking-widest flex items-center">
                                        <i data-lucide="check-circle" class="w-3 h-3 mr-1"></i> Available
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

                        <div class="p-4 border-t border-slate-100 bg-slate-50">
                            <a href="venue_details.php?vid=<?php echo urlencode($venue["vid"]); ?>" 
                            class="block w-full py-2.5 text-center text-sm font-bold rounded-lg transition-colors 
                            <?php echo $is_maintenance 
                                    ? 'bg-slate-500 hover:bg-slate-600 text-white' 
                                    : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow'; ?>">
                                <?php echo $is_maintenance ? 'View Details Only' : 'View Details'; ?>
                            </a>

                            <?php if ($is_maintenance): ?>
                                <p class="text-xs text-amber-600 font-semibold text-center mt-2">
                                    This venue is under maintenance and cannot be booked.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>

            <?php else: ?>
                <div class="col-span-full py-12 text-center text-slate-500 bg-white rounded-2xl border border-slate-200">
                    <i data-lucide="inbox" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>
                    <p class="font-medium">No venues matched your search, filter or sorting option.</p>
                    <p class="text-sm text-slate-400 mt-1">Try changing the keyword or category filter.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('venueFilterForm');
        const searchInput = document.getElementById('venueSearchInput');
        const autoSubmitFields = document.querySelectorAll('.auto-submit');

        let searchTimer;

        // Auto apply when category or sort is changed
        autoSubmitFields.forEach(function (field) {
            field.addEventListener('change', function () {
                form.submit();
            });
        });

        // Auto apply search after user stops typing
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);

            searchTimer = setTimeout(function () {
                form.submit();
            }, 500);
        });
    });
</script>

<?php include("../includes/user_footer.php"); ?>

</body>
</html>