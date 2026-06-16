<?php
// This section prepares the admin damage reports page.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
$filter_bid = trim($_GET['f_bid'] ?? '');
$filter_student = trim($_GET['f_student'] ?? '');
$filter_venue = trim($_GET['f_venue'] ?? '');
$filter_status = trim($_GET['f_status'] ?? '');
$sql = "
    SELECT
        dr.report_id,
        dr.bid,
        dr.uid,
        u.username,
        dr.vid,
        v.vname,
        vc.category AS venue_category,
        dr.damage_description,
        dr.damage_photo,
        dr.report_status,
        dr.admin_remark,
        dr.created_at,
        b.date_booked,
        b.time_start,
        b.time_end,
        b.status AS booking_status
    FROM damage_report dr
    JOIN booking b ON dr.bid = b.bid
    JOIN user u ON dr.uid = u.uid
    JOIN venue v ON dr.vid = v.vid
    JOIN vcategory vc ON v.vcid = vc.vcid
    WHERE 1=1
";
$params = [];
$types = '';
if ($filter_bid !== '')
{
    $sql .= " AND dr.bid LIKE ?";
    $params[] = "%{$filter_bid}%";
    $types .= 's';
}
if ($filter_student !== '')
{
    $sql .= " AND CONCAT(dr.uid, ' ', u.username) LIKE ?";
    $params[] = "%{$filter_student}%";
    $types .= 's';
}
if ($filter_venue !== '')
{
    $sql .= " AND CONCAT(dr.vid, ' ', v.vname, ' ', vc.category) LIKE ?";
    $params[] = "%{$filter_venue}%";
    $types .= 's';
}
if ($filter_status !== '')
{
    $sql .= " AND dr.report_status = ?";
    $params[] = $filter_status;
    $types .= 's';
}
$sql .= " ORDER BY dr.created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($params))
{
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$reports = [];
while ($row = $result->fetch_assoc())
{
    $reports[] = $row;
}
$stmt->close();
$page_title = "Damage Reports";
$page_description = "View damage reports submitted by users during their booking time.";
$current_role = $_SESSION['role'] ?? '';
$back_url = ($current_role === 'staff') ? 'inspections.php' : 'dashboard.php';
$can_resolve_damage = ($current_role !== 'staff');
$topbar_content = '
<div class="flex items-center">
    <a href="' . $back_url . '" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Operations / Damage Reports</h2>
</div>';
ob_start();
?>
<form method="GET" class="bg-white border border-slate-200 rounded-xl shadow-sm p-5 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-black text-slate-500 uppercase mb-2">Booking ID</label>
            <input type="text" name="f_bid" value="<?php echo htmlspecialchars($filter_bid); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#004aad]" placeholder="Search booking...">
        </div>
        <div>
            <label class="block text-xs font-black text-slate-500 uppercase mb-2">Student</label>
            <input type="text" name="f_student" value="<?php echo htmlspecialchars($filter_student); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#004aad]" placeholder="Name or ID...">
        </div>
        <div>
            <label class="block text-xs font-black text-slate-500 uppercase mb-2">Venue</label>
            <input type="text" name="f_venue" value="<?php echo htmlspecialchars($filter_venue); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#004aad]" placeholder="Venue name...">
        </div>
        <div>
            <label class="block text-xs font-black text-slate-500 uppercase mb-2">Report Status</label>
            <select name="f_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#004aad]">
                <option value="">All</option>
                <option value="submitted" <?php echo $filter_status === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                <option value="reviewed" <?php echo $filter_status === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
            </select>
        </div>
    </div>
    <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-slate-100">
        <a href="damage_reports.php" class="px-4 py-2 text-sm font-bold text-slate-500 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">Reset</a>
        <button type="submit" class="px-5 py-2 text-sm font-bold text-white bg-[#004aad] rounded-lg hover:bg-[#003882]">Apply Filter</button>
    </div>
</form>
<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
        <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest">Submitted Reports (<?php echo count($reports); ?>)</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-widest">
                    <th class="px-5 py-4 border-b border-slate-200">Report</th>
                    <th class="px-5 py-4 border-b border-slate-200">Booking</th>
                    <th class="px-5 py-4 border-b border-slate-200">Student</th>
                    <th class="px-5 py-4 border-b border-slate-200">Venue</th>
                    <th class="px-5 py-4 border-b border-slate-200">Description</th>
                    <th class="px-5 py-4 border-b border-slate-200">Photo</th>
                    <th class="px-5 py-4 border-b border-slate-200 text-right">Status</th>
                </tr>
            </thead>
            <tbody class="text-sm text-slate-700 divide-y divide-slate-100">
                <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-slate-400 font-semibold">No damage reports found.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($reports as $report): ?>
                    <tr class="hover:bg-slate-50 align-top">
                        <td class="px-5 py-4">
                            <div class="font-mono text-xs font-black text-slate-800">#<?php echo htmlspecialchars($report['report_id']); ?></div>
                            <div class="text-xs text-slate-400 mt-1"><?php echo date('d M Y H:i', strtotime($report['created_at'])); ?></div>
                        </td>
                        <td class="px-5 py-4">
                            <a href="process_flow.php?bid=<?php echo urlencode($report['bid']); ?>" class="font-mono text-xs font-black text-[#004aad] hover:underline">
                                <?php echo htmlspecialchars($report['bid']); ?>
                            </a>
                            <div class="text-xs text-slate-400 mt-1">
                                <?php echo date('d M Y', strtotime($report['date_booked'])); ?>,
                                <?php echo substr($report['time_start'], 0, 5); ?> - <?php echo substr($report['time_end'], 0, 5); ?>
                            </div>
                            <?php
                                $b_status_color = ($report['booking_status'] === 'cancelled') ? 'text-red-600' : 'text-emerald-600';
                            ?>
                            <div class="text-[10px] uppercase font-black <?php echo $b_status_color; ?> mt-1">
                                <?php echo htmlspecialchars($report['booking_status']); ?>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <div class="font-bold text-slate-800"><?php echo htmlspecialchars($report['username']); ?></div>
                            <div class="font-mono text-xs text-slate-400"><?php echo htmlspecialchars($report['uid']); ?></div>
                        </td>
                        <td class="px-5 py-4">
                            <div class="font-bold text-slate-800"><?php echo htmlspecialchars($report['vname']); ?></div>
                            <div class="text-xs text-slate-400"><?php echo htmlspecialchars($report['venue_category']); ?></div>
                        </td>
                        <td class="px-5 py-4 max-w-md">
                            <p class="text-sm leading-relaxed text-slate-600 whitespace-pre-wrap"><?php echo htmlspecialchars($report['damage_description']); ?></p>
                            <?php if (!empty($report['admin_remark'])): ?>
                                <div class="mt-3 p-2 bg-blue-50 border border-blue-100 rounded-lg text-xs text-blue-800">
                                    <span class="font-bold uppercase tracking-wider text-[10px] text-blue-500 block mb-1">Admin Remark</span>
                                    <?php echo htmlspecialchars($report['admin_remark']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4">
                            <?php if (!empty($report['damage_photo'])): ?>
                                <a href="../uploads/damage_reports/<?php echo rawurlencode($report['damage_photo']); ?>" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100">
                                    <i data-lucide="image" class="w-3 h-3 mr-1"></i> View Photo
                                </a>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">No photo</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <?php if ($report['report_status'] === 'submitted' && $can_resolve_damage): ?>
                                <form action="../actions/process_damage.php" method="POST" class="flex flex-col items-end gap-2">
                                    <input type="hidden" name="report_id" value="<?php echo htmlspecialchars($report['report_id']); ?>">
                                    <textarea name="admin_remark" placeholder="Enter remark (optional)..." class="w-48 px-2 py-1.5 text-xs border border-slate-200 rounded focus:outline-none focus:ring-1 focus:ring-[#004aad] resize-none" rows="2"></textarea>
                                    <button type="submit" class="px-3 py-1.5 text-xs font-bold text-white bg-[#004aad] hover:bg-[#003882] rounded shadow-sm transition-colors">
                                        Resolve & Acknowledge
                                    </button>
                                </form>
                            <?php elseif ($report['report_status'] === 'submitted'): ?>
                                <span class="px-2 py-0.5 border bg-amber-50 text-amber-700 border-amber-200 rounded text-[10px] font-black uppercase tracking-widest inline-block">
                                    SUBMITTED
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 border bg-emerald-50 text-emerald-600 border-emerald-200 rounded text-[10px] font-black uppercase tracking-widest inline-block">
                                    REVIEWED
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$page_content = ob_get_clean();
include __DIR__ . '/../core/layout.php';
?>
