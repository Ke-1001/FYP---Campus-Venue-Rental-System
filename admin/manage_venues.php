<?php
// File: admin/manage_venues.php

session_start();
require_once("../config/db.php");
require_once('../includes/admin_auth.php'); // 💡 注入安全閘道器 (已內建 session_start)
require_once("../config/db.php");

$venues = [];
$sql_venues = "
    SELECT 
        venue_id AS raw_id, /* 💡 用於對接 process_venue.php */
        CONCAT('VEN-', LPAD(venue_id, 3, '0')) AS id, 
        venue_name AS name, 
        category, 
        capacity, /* 💡 新增容量欄位 */
        base_deposit AS deposit, 
        status 
    FROM venues 
    ORDER BY venue_id ASC";

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
    <title>MMU Admin | Venue Registry</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b', accent: '#38bdf8' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.1">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0">
            <?php 
        $topbar_content = '
        <div class="flex items-center text-slate-500 bg-white px-4 py-2 rounded-lg border border-slate-200 focus-within:border-mmu-blue shadow-sm transition-all">
            <i data-lucide="search" class="w-4 h-4 mr-2"></i>
            <input type="text" placeholder="Search system assets..." class="bg-transparent border-none outline-none w-64 text-sm focus:ring-0">
        </div>';
        
        include('../includes/admin_topbar.php'); 
        ?>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Venue Infrastructure</h1>
                    <p class="text-sm text-slate-500 mt-1">Control operational state and financial baseline parameters.</p>
                </div>

                <button onclick="openVenueModal('add')" class="px-4 py-2 bg-mmu-blue text-white font-bold rounded-lg shadow flex items-center hover:bg-blue-700 transition">
                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Register Node
                </button>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-widest">
                            <th class="px-6 py-4 border-b border-slate-200">Venue ID</th>
                            <th class="px-6 py-4 border-b border-slate-200">Designation</th>
                            <th class="px-6 py-4 border-b border-slate-200">Classification</th>
                            <th class="px-6 py-4 border-b border-slate-200">Base Deposit (RM)</th>
                            <th class="px-6 py-4 border-b border-slate-200">State Machine</th>
                            <th class="px-6 py-4 border-b border-slate-200 text-right">Configuration</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-700 divide-y divide-slate-100">
                        <?php foreach($venues as $venue): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs font-bold text-mmu-blue"><?php echo $venue['id']; ?></td>
                            <td class="px-6 py-4 font-bold text-slate-800"><?php echo htmlspecialchars($venue['name']); ?></td>
                            <td class="px-6 py-4 font-medium text-slate-500"><?php echo htmlspecialchars($venue['category']); ?></td>
                            <td class="px-6 py-4 font-mono font-bold text-slate-700"><?php echo number_format((float)$venue['deposit'], 2); ?></td>
                            <td class="px-6 py-4">
                                <?php 
                                    // Status Vector Processing
                                    $statusClass = "bg-slate-100 text-slate-600 border-slate-200";
                                    $icon = "minus";
                                    
                                    if($venue['status'] === 'Available') { 
                                        $statusClass = "bg-emerald-50 text-emerald-600 border-emerald-200"; 
                                        $icon = "check-circle-2"; 
                                    } elseif($venue['status'] === 'Maintenance') { 
                                        $statusClass = "bg-amber-50 text-amber-600 border-amber-200"; 
                                        $icon = "wrench"; 
                                    } elseif($venue['status'] === 'Closed') { 
                                        $statusClass = "bg-red-50 text-red-600 border-red-200"; 
                                        $icon = "slash"; 
                                    }
                                ?>
                                <span class="px-2 py-1 border <?php echo $statusClass; ?> rounded text-[10px] font-black uppercase tracking-widest inline-flex items-center">
                                    <i data-lucide="<?php echo $icon; ?>" class="w-3 h-3 mr-1"></i>
                                    <?php echo $venue['status']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button onclick="openVenueModal('edit', this)" 
                                        data-id="<?php echo $venue['raw_id']; ?>"
                                        data-name="<?php echo htmlspecialchars($venue['name']); ?>"
                                        data-category="<?php echo htmlspecialchars($venue['category']); ?>"
                                        data-capacity="<?php echo $venue['capacity']; ?>"
                                        data-deposit="<?php echo $venue['deposit']; ?>"
                                        data-status="<?php echo $venue['status']; ?>"
                                        class="p-2 text-slate-500 hover:text-mmu-blue hover:bg-blue-50 border border-transparent hover:border-blue-200 rounded-lg transition tooltip" 
                                        title="Modify Asset Properties">
                                    <i data-lucide="settings-2" class="w-4 h-4"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 flex justify-between items-center text-xs font-bold text-slate-400 uppercase tracking-widest">
                <span>Total Registered Nodes: <?php echo count($venues); ?></span>
            </div>

        </div>
    </main>
    <div id="venue-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center transition-opacity">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200">
        
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 id="modal-title" class="text-lg font-extrabold text-slate-800">Register Infrastructure Node</h3>
            <button type="button" onclick="closeVenueModal()" class="text-slate-400 hover:text-slate-600 transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form action="../actions/process_venue.php" method="POST" class="p-6">
            <input type="hidden" name="action" id="modal-action" value="add">
            <input type="hidden" name="venue_id" id="modal-venue-id" value="">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Venue Designation (Name)</label>
                    <input type="text" name="venue_name" id="modal-name" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-mmu-blue focus:ring-1 focus:ring-mmu-blue text-sm">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Classification</label>
                        <select name="category" id="modal-category" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-mmu-blue focus:ring-1 focus:ring-mmu-blue text-sm bg-white">
                            <option value="Discussion Room">Discussion Room</option>
                            <option value="Sports Court">Sports Court</option>
                            <option value="Event Hall">Event Hall</option>
                            <option value="Meeting Room">Meeting Room</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Capacity (Pax)</label>
                        <input type="number" name="capacity" id="modal-capacity" required min="1" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-mmu-blue focus:ring-1 focus:ring-mmu-blue text-sm">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Base Deposit (RM)</label>
                        <input type="number" step="0.01" name="base_deposit" id="modal-deposit" required min="0" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-mmu-blue focus:ring-1 focus:ring-mmu-blue text-sm">
                    </div>
                    <div id="status-container" class="hidden">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Operational State</label>
                        <select name="status" id="modal-status" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-mmu-blue focus:ring-1 focus:ring-mmu-blue text-sm bg-white">
                            <option value="Available">Available</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 flex justify-between items-center border-t border-slate-100 pt-5">
                <a href="#" id="modal-delete-btn" 
                    onclick="triggerCustomConfirm(event, 'CRITICAL WARNING: Are you sure you want to permanently delete this venue node?', this.href);" 
                    class="hidden text-xs font-bold text-red-500 hover:text-red-700 transition flex items-center uppercase tracking-widest">
                        <i data-lucide="trash-2" class="w-4 h-4 mr-1"></i> Terminate Node
                </a>
                
                <div class="flex-1 flex justify-end space-x-3">
                    <button type="button" onclick="closeVenueModal()" class="px-4 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100 rounded-lg transition">Cancel</button>
                    <button type="submit" class="px-6 py-2 text-sm font-bold text-white bg-mmu-blue hover:bg-blue-700 rounded-lg transition shadow">Deploy Configuration</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php include('../includes/ui_components.php'); ?>
    <script>
        lucide.createIcons();

        function toggleSidebar() {
            const sidebar = document.getElementById('system-sidebar');
            sidebar.classList.toggle('sidebar-collapsed');
        }

                // 場地 Modal 狀態機與資料注入邏輯
        function openVenueModal(mode, btn = null) {
            const modal = document.getElementById('venue-modal');
            const formAction = document.getElementById('modal-action');
            const title = document.getElementById('modal-title');
            const statusContainer = document.getElementById('status-container');
            const deleteBtn = document.getElementById('modal-delete-btn');
            
            // 清空殘留表單數據
            document.querySelector('#venue-modal form').reset();
            
            if (mode === 'add') {
                formAction.value = 'add';
                title.innerText = 'Register Infrastructure Node';
                statusContainer.classList.add('hidden');
                deleteBtn.classList.add('hidden'); // 新增時不可刪除
            } else if (mode === 'edit' && btn) {
                formAction.value = 'edit';
                title.innerText = 'Configure Infrastructure Node';
                statusContainer.classList.remove('hidden');
                deleteBtn.classList.remove('hidden');
                
                // 解析資料屬性並注入表單 (Data Hydration)
                document.getElementById('modal-venue-id').value = btn.getAttribute('data-id');
                document.getElementById('modal-name').value = btn.getAttribute('data-name');
                document.getElementById('modal-category').value = btn.getAttribute('data-category');
                document.getElementById('modal-capacity').value = btn.getAttribute('data-capacity');
                document.getElementById('modal-deposit').value = btn.getAttribute('data-deposit');
                document.getElementById('modal-status').value = btn.getAttribute('data-status');
                
                // 動態生成刪除按鈕的 GET 路由
                deleteBtn.href = `../actions/process_venue.php?action=delete&id=${btn.getAttribute('data-id')}`;
            }
            
            modal.classList.remove('hidden');
        }

        function closeVenueModal() {
            document.getElementById('venue-modal').classList.add('hidden');
        }
    </script>

</body>
</html>