<?php
// File: admin/semester_management.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

// 💡 1. 提取多维度过滤参数
$f_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : 'all';
$f_search = isset($_GET['filter_search']) ? trim($_GET['filter_search']) : '';

// 💡 2. 构建动态过滤查询
$sql = "SELECT * FROM semester_config WHERE 1=1";

if ($f_status === 'active') {
    $sql .= " AND is_active = 1";
} elseif ($f_status === 'inactive') {
    $sql .= " AND is_active = 0";
}

if (!empty($f_search)) {
    $sql .= " AND sem_name LIKE '%" . $conn->real_escape_string($f_search) . "%'";
}

$sql .= " ORDER BY start_date DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Semester Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { 
            theme: { 
                extend: { 
                    colors: { 
                        cstyle: { blue: '#004aad', dark: '#1e293b' }
                    } 
                } 
            } 
        }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/table.css">
    <link rel="stylesheet" href="../assets/css/fiori_forms.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <?php 
        $topbar_content = '<div class="flex items-center">
            <a href="academic.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
            </a>
            <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Academic / Semester Management</h2>
        </div>';
        include('../includes/admin_topbar.php'); 
        ?>

        <div class="px-8 pt-6 pb-2 bg-slate-50 shrink-0">
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Semester Management</h1>
            <p class="text-xs text-slate-500 mt-1">Manage academic terms, dates, and status settings.</p>
        </div>

        <div class="px-8 py-3 bg-slate-50 shrink-0">
            <form method="GET" action="semester_management.php" id="filterForm" class="bg-white border border-slate-200 rounded-md p-3 shadow-sm flex flex-wrap items-center gap-4">
                <div class="flex items-center space-x-2">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Search:</label>
                    <input type="text" name="filter_search" value="<?php echo htmlspecialchars($f_search); ?>" placeholder="e.g., Term 1" class="border border-slate-200 rounded px-3 py-1.5 text-xs font-semibold focus:outline-none focus:border-[#004aad] w-48 bg-white">
                </div>

                <div class="flex items-center space-x-2">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Status:</label>
                    <select name="filter_status" onchange="this.form.submit()" class="border border-slate-200 rounded px-3 py-1.5 text-xs font-bold text-slate-700 focus:outline-none focus:border-[#004aad] cursor-pointer bg-white">
                        <option value="all" <?php echo $f_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="active" <?php echo $f_status === 'active' ? 'selected' : ''; ?>>Active Only</option>
                        <option value="inactive" <?php echo $f_status === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                    </select>
                </div>

                <a href="semester_management.php" class="px-4 py-2 bg-transparent text-slate-600 text-sm font-semibold rounded-md hover:bg-slate-100 border border-transparent transition flex items-center justify-center ml-auto">
                    Reset
                </a>
            </form>
        </div>

        <div class="mx-8 mb-4 bg-white p-3 rounded-md border border-slate-200 shadow-[0_1px_2px_0_rgba(0,0,0,0.05)] shrink-0 flex justify-between items-center">
            <div class="text-xs font-bold text-slate-500 pl-2">
                <span id="cb-counter">0</span> selected
            </div>
            
            <div class="flex space-x-2">
                <button onclick="window.location.href='add_semester.php'" class="px-4 py-2 text-xs font-semibold text-white bg-[#004aad] hover:bg-[#003882] rounded-md shadow-sm transition border border-[#004aad]">
                    <i data-lucide="plus" class="w-3.5 h-3.5 inline mr-1"></i> Create Semester
                </button>
                <button id="btn-edit" disabled onclick="executeAction('edit')" class="px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200">
                    <i data-lucide="edit-3" class="w-3.5 h-3.5 inline mr-1"></i> Edit
                </button>
                <button id="btn-delete" disabled onclick="executeAction('delete')" class="px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5 inline mr-1"></i> Delete
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-8 pb-8">
            <form id="bulkActionForm" action="../actions/process_semester.php" method="POST" class="w-full">
                <input type="hidden" name="action" id="bulk_action_type" value="">
                
                <div class="custom-table-container">
                    <table class="custom-table w-full">
                        <thead>
                            <tr>
                                <th class="w-12 text-center">
                                    <input type="checkbox" id="selectAll" onclick="toggleAll(this)" class="w-4 h-4 rounded border-slate-300 text-[#004aad] focus:ring-[#004aad] cursor-pointer">
                                </th>
                                <th class="w-32">Semester ID</th>
                                <th>Semester Name</th>
                                <th class="w-48">Start Date</th>
                                <th class="w-48">End Date</th>
                                <th class="w-32 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr ondblclick="window.location.href='add_semester.php?id=<?php echo $row['sem_id']; ?>'">
                                    <td class="text-center" onclick="event.stopPropagation();">
                                        <input type="checkbox" name="selected_ids[]" value="<?php echo $row['sem_id']; ?>" onclick="updateButtonStates()" class="row-cb w-4 h-4 rounded border-slate-300 text-[#004aad] focus:ring-[#004aad] cursor-pointer">
                                    </td>
                                    <td><span class="td-text-mono text-slate-400">#<?php echo $row['sem_id']; ?></span></td>
                                    <td>
                                        <a href="add_semester.php?id=<?php echo $row['sem_id']; ?>" class="td-text-mono text-slate-900">
                                            <?php echo htmlspecialchars($row['sem_name']); ?>
                                        </a>
                                    </td>
                                    <td><span class="td-text-mono font-mono text-slate-600"><?php echo $row['start_date']; ?></span></td>
                                    <td><span class="td-text-mono font-mono text-slate-600"><?php echo $row['end_date']; ?></span></td>
                                    <td class="text-center">
                                        <?php if ($row['is_active'] == 1): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-[10px] font-bold uppercase bg-slate-100 text-slate-600 border border-slate-200">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="py-16 text-center text-slate-500 border-none hover:bg-transparent cursor-default">
                                        <span class="text-sm font-medium tracking-wide">No semesters found.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </main>

    <div id="custom-delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm opacity-0 transition-opacity duration-200">
        <div id="custom-modal-panel" class="bg-white rounded-md shadow-lg w-full max-w-sm p-6 transform scale-95 transition-transform duration-200 border border-slate-200">
            <h3 class="text-base font-bold text-slate-900 mb-2">Delete Semester</h3>
            <p class="text-sm text-slate-600 mb-6" id="custom-modal-msg">Are you sure you want to delete the selected semester(s)?</p>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeCustomModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-transparent hover:bg-slate-100 rounded-md transition-colors">Cancel</button>
                <button type="button" id="custom-modal-confirm-btn" class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-md shadow-sm transition-colors border border-red-600">Delete</button>
            </div>
        </div>
    </div>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();
        function toggleSidebar() { document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed'); }

        // ∴ Checkbox 状态机核心交互逻辑
        function toggleAll(source) {
            const checkboxes = document.querySelectorAll('.row-cb');
            for(let i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
            updateButtonStates();
        }

        function updateButtonStates() {
            const selected = document.querySelectorAll('.row-cb:checked');
            const btnEdit = document.getElementById('btn-edit');
            const btnDelete = document.getElementById('btn-delete');
            const counter = document.getElementById('cb-counter');
            
            counter.innerText = selected.length;

            if (selected.length === 1) {
                btnEdit.disabled = false; 
                btnEdit.className = "px-4 py-2 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-md shadow-sm transition cursor-pointer";
                btnDelete.disabled = false; 
                btnDelete.className = "px-4 py-2 text-xs font-semibold text-[#dc2626] bg-white hover:bg-red-50 border border-red-200 rounded-md shadow-sm transition cursor-pointer";
            } else if (selected.length > 1) {
                btnEdit.disabled = true; 
                btnEdit.className = "px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200";
                btnDelete.disabled = false; 
                btnDelete.className = "px-4 py-2 text-xs font-semibold text-[#dc2626] bg-white hover:bg-red-50 border border-red-200 rounded-md shadow-sm transition cursor-pointer";
            } else {
                btnEdit.disabled = true; 
                btnEdit.className = "px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200";
                btnDelete.disabled = true; 
                btnDelete.className = "px-4 py-2 text-xs font-semibold text-slate-400 bg-slate-100 rounded-md transition cursor-not-allowed border border-slate-200";
            }
        }

        // ∴ 路由路由控制逻辑与 P_6 自定义弹窗激活
        function executeAction(type) {
            const form = document.getElementById('bulkActionForm');
            const selected = document.querySelectorAll('.row-cb:checked');
            if (selected.length === 0) return;

            if (type === 'edit') {
                if (selected.length !== 1) return;
                window.location.href = `add_semester.php?id=${selected[0].value}`;
            } else if (type === 'delete') {
                // 触发自修定 Fiori 模态框，彻底阻断 window.confirm() 的 localhost 提示
                document.getElementById('custom-modal-msg').innerText = `Are you sure you want to permanently delete the ${selected.length} selected semester(s)?`;
                openCustomModal();
            }
        }

        function openCustomModal() {
            const modal = document.getElementById('custom-delete-modal');
            const panel = document.getElementById('custom-modal-panel');
            modal.classList.remove('hidden');
            void modal.offsetWidth; // 强制 Reflow 保证动画顺畅
            modal.classList.remove('opacity-0');
            panel.classList.remove('scale-95');
        }

        function closeCustomModal() {
            const modal = document.getElementById('custom-delete-modal');
            const panel = document.getElementById('custom-modal-panel');
            modal.classList.add('opacity-0');
            panel.classList.add('scale-95');
            setTimeout(() => { modal.classList.add('hidden'); }, 200);
        }

        // 绑定自修定模态框的确认提交事件
        document.getElementById('custom-modal-confirm-btn').addEventListener('click', function() {
            const form = document.getElementById('bulkActionForm');
            document.getElementById('bulk_action_type').value = 'delete';
            this.innerHTML = 'Deleting...';
            this.disabled = true;
            form.submit();
        });
    </script>
</body>
</html>