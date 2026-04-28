<?php
// File: admin/manage_venues.php
session_start();
require_once("../config/db.php");
require_once('../includes/admin_auth.php'); 

$venues = [];
// 💡 適配 v3.1 新架構：使用 venue 表與純數字 vid
$sql_venues = "SELECT vid, vname, category, max_cap, deposit, status FROM venue ORDER BY category ASC, vname ASC";
$result = $conn->query($sql_venues);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $venues[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Manage Venues</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b', accent: '#38bdf8' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0">
            <?php 
            $topbar_content = '
            <div class="flex items-center text-slate-500 bg-white px-4 py-2 rounded-lg border border-slate-200 focus-within:border-mmu-blue shadow-sm transition-all">
                <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                <input type="text" placeholder="Search venues..." class="bg-transparent border-none outline-none w-64 text-sm focus:ring-0">
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Manage Venues</h1>
                    <p class="text-sm text-slate-500 mt-1">Configure campus spaces, capacities, and booking status.</p>
                </div>
                <button onclick="openVenueModal('add')" class="px-4 py-2 bg-mmu-dark text-white font-bold rounded-lg shadow flex items-center hover:bg-slate-800 transition">
                    <i data-lucide="plus-circle" class="w-4 h-4 mr-2 text-mmu-accent"></i> Add New Venue
                </button>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-widest">
                            <th class="px-6 py-4 border-b border-slate-200">Venue ID</th>
                            <th class="px-6 py-4 border-b border-slate-200">Venue Details</th>
                            <th class="px-6 py-4 border-b border-slate-200">Capacity</th>
                            <th class="px-6 py-4 border-b border-slate-200">Deposit</th>
                            <th class="px-6 py-4 border-b border-slate-200">Status</th>
                            <th class="px-6 py-4 border-b border-slate-200 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-700 divide-y divide-slate-100">
                        <?php foreach($venues as $v): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs font-bold text-mmu-blue"><?php echo htmlspecialchars($v['vid']); ?></td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-800 block"><?php echo htmlspecialchars($v['vname']); ?></span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"><?php echo htmlspecialchars($v['category']); ?></span>
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-600">
                                <?php echo (int)$v['max_cap']; ?> Pax
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-emerald-600">
                                RM <?php echo number_format((float)$v['deposit'], 2); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php 
                                    $statusClass = "bg-slate-100 text-slate-600 border-slate-200";
                                    if($v['status'] === 'available') $statusClass = "bg-emerald-50 text-emerald-600 border-emerald-200";
                                    if($v['status'] === 'maintenance') $statusClass = "bg-amber-50 text-amber-600 border-amber-200";
                                    if($v['status'] === 'booked') $statusClass = "bg-blue-50 text-blue-600 border-blue-200";
                                ?>
                                <span class="px-2 py-0.5 border <?php echo $statusClass; ?> rounded text-[10px] font-black uppercase tracking-widest">
                                    <?php echo htmlspecialchars($v['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end space-x-2">
                                    <button onclick='openVenueModal("edit", <?php echo json_encode($v); ?>)'
                                            class="p-1.5 text-slate-400 hover:text-mmu-blue hover:bg-blue-50 border border-transparent hover:border-blue-200 rounded transition tooltip" title="Edit Venue">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    <a href="../actions/process_venue.php?action=delete&vid=<?php echo urlencode($v['vid']); ?>" 
                                       onclick="triggerCustomConfirm(event, 'Delete this venue? Active bookings will prevent deletion.', this.href);"
                                       class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 border border-transparent hover:border-red-200 rounded transition tooltip" title="Delete Venue">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($venues)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400 font-medium">No venues configured in the system.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="venue-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-200">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="text-lg font-extrabold text-slate-800" id="modal-title">Configure Venue</h3>
                <button type="button" onclick="closeVenueModal()" class="text-slate-400 hover:text-slate-600 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="../actions/process_venue.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" id="modal-action" value="add">
                <input type="hidden" name="vid" id="modal-vid" value="">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Venue Name</label>
                    <input type="text" name="vname" id="modal-vname" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-mmu-blue focus:ring-1 focus:ring-mmu-blue text-sm">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Category</label>
                        <select name="category" id="modal-category" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-mmu-blue focus:ring-1 focus:ring-mmu-blue text-sm bg-white">
                            <option value="Discussion Room">Discussion Room</option>
                            <option value="Lecture Hall">Lecture Hall</option>
                            <option value="Lab">Lab</option>
                            <option value="Sports">Sports</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Status</label>
                        <select name="status" id="modal-status" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-mmu-blue focus:ring-1 focus:ring-mmu-blue text-sm bg-white">
                            <option value="available">Available</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Capacity (Pax)</label>
                        <input type="number" name="max_cap" id="modal-cap" required min="1" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-mmu-blue focus:ring-1 focus:ring-mmu-blue text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Deposit (RM)</label>
                        <input type="number" name="deposit" id="modal-deposit" required min="0" step="0.01" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-mmu-blue focus:ring-1 focus:ring-mmu-blue text-sm font-mono">
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeVenueModal()" class="px-4 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100 rounded-lg transition">Cancel</button>
                    <button type="submit" id="modal-submit-btn" class="px-6 py-2 text-sm font-bold text-white bg-mmu-dark hover:bg-slate-800 rounded-lg transition shadow">Save Venue</button>
                </div>
            </form>
        </div>
    </div>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();

        function toggleSidebar() {
            document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed');
        }

        function openVenueModal(mode, data = null) {
            const form = document.querySelector('#venue-modal form');
            form.reset();
            
            document.getElementById('modal-action').value = mode;
            
            if (mode === 'edit' && data) {
                document.getElementById('modal-title').innerText = 'Edit Venue';
                document.getElementById('modal-submit-btn').innerText = 'Save Changes';
                
                document.getElementById('modal-vid').value = data.vid;
                document.getElementById('modal-vname').value = data.vname;
                document.getElementById('modal-category').value = data.category;
                document.getElementById('modal-status').value = data.status;
                document.getElementById('modal-cap').value = data.max_cap;
                document.getElementById('modal-deposit').value = data.deposit;
            } else {
                document.getElementById('modal-title').innerText = 'Add New Venue';
                document.getElementById('modal-submit-btn').innerText = 'Create Venue';
                document.getElementById('modal-status').value = 'available';
            }
            
            document.getElementById('venue-modal').classList.remove('hidden');
        }

        function closeVenueModal() {
            document.getElementById('venue-modal').classList.add('hidden');
        }
    </script>
</body>
</html>