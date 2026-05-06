<?php
include("../includes/user_auth.php");
include("../includes/user_header.php");
include("../includes/user_navbar.php");
include("../config/db.php");

$user_id = $_SESSION['uid'];

$stmt = $conn->prepare("
    SELECT b.bid, b.date_booked, b.status,
           v.vname, v.category, v.max_cap
    FROM booking b
    JOIN venue v ON b.vid = v.vid
    WHERE b.uid = ?
    ORDER BY b.date_booked DESC
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-6xl mx-auto">

        <!-- 标题 -->
        <div class="mb-10 text-center">
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">My Bookings</h1>
            <p class="text-sm text-slate-500 mt-2">View and manage all your booking records.</p>
        </div>

        <!-- Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 hover:shadow-md transition-all flex flex-col">

                        <!-- 内容 -->
                        <div class="p-6 flex-1">

                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-slate-800">
                                        <?php echo htmlspecialchars($row["vname"]); ?>
                                    </h3>
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                                        <?php echo htmlspecialchars($row["category"]); ?>
                                    </span>
                                </div>

                                <!-- 状态 badge -->
                                <?php
                                    $status = $row['status'];
                                    $badge = "bg-yellow-100 text-yellow-700";

                                    if ($status === "Approved") {
                                        $badge = "bg-emerald-100 text-emerald-700";
                                    } elseif ($status === "Rejected") {
                                        $badge = "bg-red-100 text-red-700";
                                    }
                                ?>

                                <span class="px-2 py-1 text-[10px] font-black uppercase tracking-widest rounded <?php echo $badge; ?>">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>
                            </div>

                            <!-- 信息 -->
                            <div class="space-y-2 mb-6">

                                <div class="flex items-center text-sm text-slate-600">
                                    <i data-lucide="calendar" class="w-4 h-4 mr-2 text-slate-400"></i>
                                    <span>Date: <strong><?php echo htmlspecialchars($row["date_booked"]); ?></strong></span>
                                </div>

                                <div class="flex items-center text-sm text-slate-600">
                                    <i data-lucide="users" class="w-4 h-4 mr-2 text-slate-400"></i>
                                    <span>Capacity: <strong><?php echo (int)$row["max_cap"]; ?> Pax</strong></span>
                                </div>

                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="p-4 border-t border-slate-100 bg-slate-50">

                            <a href="booking_details.php?id=<?php echo urlencode($row["bid"]); ?>"
                               class="block w-full py-2.5 text-center text-sm font-bold rounded-lg transition-colors bg-indigo-600 hover:bg-indigo-700 text-white shadow">
                                View Details
                            </a>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <!-- 空状态 -->
                <div class="col-span-full py-12 text-center text-slate-500 bg-white rounded-2xl border border-slate-200">
                    <i data-lucide="inbox" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>
                    <p class="font-medium">You have no bookings yet.</p>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<script>
lucide.createIcons();
</script>