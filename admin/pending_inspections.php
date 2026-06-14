<?php
// File: admin/pending_inspections.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../core/components/datagrid.php'; 

// ∴ 引入核心組件與倉儲依賴
require_once __DIR__ . '/../core/components/FilterBuilder.php';
require_once __DIR__ . '/../core/repositories/InspectionRepository.php';

use Core\Components\FilterBuilder;
use Core\Repositories\InspectionRepository;

/*
|--------------------------------------------------------------------------
| I: Repository Initialization & System Protocol
|--------------------------------------------------------------------------
*/
// ∴ 1. 實例化檢驗倉儲
$inspectionRepo = new InspectionRepository($conn);

// ∴ 2. 建構過濾器矩陣 (Filter Topology)
$filterBuilder = new FilterBuilder('pending_inspections.php', true);
$filterBuilder
    ->addField('text', 'f_bid', 'Ref ID', [], 'Search BID...', 'b.bid', 'LIKE')
    ->addField('text', 'f_student', 'Student Entity', [], 'Name or ID...', 'CONCAT(u.uid, " ", u.username)', 'LIKE')
    ->addField('text', 'f_venue', 'Asset', [], 'Name or Category...', 'CONCAT(v.vname, " ", vc.category)', 'LIKE')
    ->addField('date', 'f_date', 'Date', [], '', 'b.date_booked', '=')
    ->addField('text', 'f_inspector', 'Personnel', [], 'Inspector Name...', 's.staff_name', 'LIKE');

/*
|--------------------------------------------------------------------------
| D: Abstracted Data Execution & View Reshaping
|--------------------------------------------------------------------------
*/
// ∴ 3. 委託倉儲獲取結果集
$result = $inspectionRepo->getPendingInspections($filterBuilder);

// ∴ 4. 提取資料列並執行 Spatiotemporal Validation (時空推演)
$tz = new DateTimeZone('Asia/Kuala_Lumpur');
$now = new DateTime('now', $tz);
$records = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $start_dt = new DateTime($row['date_booked'] . ' ' . $row['time_start'], $tz);
        $end_dt = new DateTime($row['date_booked'] . ' ' . $row['time_end'], $tz);
        
        // ∴ 計算 $\mathcal{T}(t)$ 狀態向量並注入虛擬欄位 execution_state
        if ($now >= $end_dt || $row['booking_status'] === 'completed') {
            $row['execution_state'] = 'ready';
        } else {
            if ($now >= $start_dt && $now < $end_dt) {
                $row['execution_state'] = 'in_use';
            } else {
                $row['execution_state'] = 'awaiting';
            }
        }
        $records[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| C: Configuration & Schema Definitions
|--------------------------------------------------------------------------
*/
$page_title = "Pending Inspections";
$page_description = "Review upcoming physical assessments for post-event venues.";
$topbar_content = '
<div class="flex items-center">
    <a href="inspections.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Operations / Pending Inspections</h2>
</div>';
$extra_css = ["../assets/css/fiori_forms.css", "../assets/css/table.css"];

// ∴ 核心拓撲：DataGrid Schema 配置字典
// 藉由 map_badge 將複雜的 IF/ELSE 渲染邏輯抽象為聲明式配置
$datagrid_schema = [
    'enable_checkbox' => false,
    'primary_key' => 'bid',
    'row_action_url' => 'execute_inspection.php?bid=%s',
    'columns' => [
        ['key' => 'bid', 'label' => 'Reference', 'type' => 'link', 'url_format' => 'execute_inspection.php?bid=%s', 'width' => 'w-28 text-center'],
        ['key' => 'student_id', 'label' => 'Student ID', 'type' => 'text_mono', 'width' => 'w-28'],
        ['key' => 'student_name', 'label' => 'Student Name', 'type' => 'text_bold', 'width' => 'w-36'],
        ['key' => 'venue_name', 'label' => 'Asset', 'type' => 'text_bold', 'width' => 'w-40'],
        ['key' => 'venue_category', 'label' => 'Category', 'type' => 'badge', 'width' => 'w-32'],
        ['key' => 'date_booked', 'label' => 'Date', 'type' => 'text_mono', 'width' => 'w-28 text-center'],
        ['type' => 'time_range', 'label' => 'Reserved Slot', 'start_key' => 'time_start', 'end_key' => 'time_end', 'width' => 'w-32'],
        ['key' => 'inspector_name', 'label' => 'Inspector', 'type' => 'text_bold', 'width' => 'w-36'],
        ['key' => 'execution_state', 'label' => 'State', 'type' => 'map_badge', 'width' => 'w-32 text-center', 'map' => [
            'ready' => ['label' => 'Ready', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
            'in_use' => ['label' => 'In Use', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
            'awaiting' => ['label' => 'Awaiting', 'class' => 'bg-slate-50 text-slate-500 border-slate-200']
        ]]
    ]
];

/*
|--------------------------------------------------------------------------
| V: View Rendering (Output Buffer)
|--------------------------------------------------------------------------
*/
ob_start();
?>

<?php echo $filterBuilder->render(); ?>

<div class="custom-table-container flex-1 overflow-hidden flex flex-col">
    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center shrink-0">
        <h3 class="text-xs font-black text-slate-600 uppercase tracking-widest">
            Inspection Queue (<?php echo count($records); ?>)
        </h3>
    </div>
    
    <div class="flex-1 overflow-y-auto" id="datagrid-wrapper">
        <?php echo render_datagrid($datagrid_schema, $records); ?>
    </div>
</div>

<div id="temporal-fault-modal" class="fixed inset-0 z-[100] hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center transition-all opacity-0 pointer-events-none">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform scale-95 transition-transform duration-300 border border-slate-200" id="temporal-fault-panel">
        <div class="p-8 text-center">
            <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-5 border-4 border-white shadow-sm">
                <i data-lucide="shield-alert" class="w-8 h-8 text-red-600"></i>
            </div>
            <h3 class="text-xl font-extrabold text-slate-800 mb-3 tracking-tight">Access Denied</h3>
            <p id="temporal-fault-msg" class="text-sm text-slate-600 font-medium leading-relaxed px-2"></p>
        </div>
        <div class="px-8 py-5 bg-slate-50 border-t border-slate-100 flex justify-center">
            <button type="button" onclick="closeTemporalModal()" class="w-full py-3.5 text-sm font-bold text-white bg-slate-800 hover:bg-slate-900 rounded-xl transition-all shadow-md active:scale-95 flex items-center justify-center">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Return to Queue
            </button>
        </div>
    </div>
</div>

<script>
    // ∴ Modal 控制邏輯
    function showTemporalModal(message) {
        const modal = document.getElementById('temporal-fault-modal');
        const panel = document.getElementById('temporal-fault-panel');
        document.getElementById('temporal-fault-msg').textContent = message;
        
        modal.classList.remove('hidden');
        // Trigger reflow
        void modal.offsetWidth; 
        
        modal.classList.remove('opacity-0', 'pointer-events-none');
        panel.classList.remove('scale-95');
        panel.classList.add('scale-100');
    }

    function closeTemporalModal() {
        const modal = document.getElementById('temporal-fault-modal');
        const panel = document.getElementById('temporal-fault-panel');
        
        modal.classList.add('opacity-0', 'pointer-events-none');
        panel.classList.remove('scale-100');
        panel.classList.add('scale-95');
        
        setTimeout(() => { modal.classList.add('hidden'); }, 300);
    }

    // ∴ 核心防禦: 事件委派攔截器 (Event Delegation Interceptor)
    document.addEventListener('DOMContentLoaded', () => {
        const wrapper = document.getElementById('datagrid-wrapper');
        
        wrapper.addEventListener('click', function(e) {
            // 尋找被點擊的目標是否為進入評估的連結
            const targetLink = e.target.closest('a[href*="execute_inspection.php"]');
            if (!targetLink) return;

            // 回溯尋找該行 (Table Row)
            const row = targetLink.closest('tr');
            if (!row) return;

            // 提取該行的狀態徽章 (偵測 In Use 或 Awaiting 樣式)
            const invalidBadge = row.querySelector('.bg-amber-50, .bg-slate-50'); 
            
            if (invalidBadge) {
                // 阻斷預設跳轉行為
                e.preventDefault(); 
                const stateText = invalidBadge.textContent.trim().toLowerCase();
                const errMsg = `Warning: Booking is currently [${stateText}]. Assessment cannot commence until the reserved slot has concluded.`;
                showTemporalModal(errMsg);
            }
        });

        // 偵測是否由伺服器端強制重定向退回 (處理惡意修改 URL 的邊界情況)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('err') && urlParams.get('err') === 'temporal') {
            const state = urlParams.get('state') || 'unauthorized';
            showTemporalModal(`Warning: Assessment cannot commence. Booking state: [${state}].`);
            // 清理 URL 參數以維持純淨狀態
            window.history.replaceState({}, document.title, "pending_inspections.php");
        }
    });
</script>

<?php
$page_content = ob_get_clean();

/*
|--------------------------------------------------------------------------
| L: Global Layout Engine
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../core/layout.php';
?>