<?php
// File: admin/add_exclusion.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

$sch_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mode = ($sch_id > 0) ? 'Update' : 'Add'; 
$exclusion = null;

if ($mode === 'Update') {
    $stmt = $conn->prepare("SELECT * FROM academic_schedule WHERE sch_id = ?");
    $stmt->bind_param("i", $sch_id);
    $stmt->execute();
    $exclusion = $stmt->get_result()->fetch_assoc();
    if (!$exclusion) die("Error: Schedule not found.");
}

$venues = $conn->query("SELECT vid, vname FROM venue ORDER BY vid ASC");
$semesters = $conn->query("SELECT sem_id, sem_name, is_active FROM semester_config ORDER BY start_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | <?php echo $mode; ?> Schedule</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { 
            theme: { 
                extend: { 
                    colors: { 
                        fiori: { text: '#1d2d3e', label: '#64748b', blue: '#004aad' } 
                    } 
                } 
            } 
        }
    </script>
    <link rel="stylesheet" href="../assets/css/layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori_forms.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <?php 
        $topbar_content = '
        <div class="flex items-center">
            <a href="academic_schedule.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
            </a>
            <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Academic / ' . $mode . ' Schedule</h2>
        </div>';
        include('../includes/admin_topbar.php'); 
        ?>

        <div class="px-8 pt-6 pb-2 bg-slate-50 shrink-0">
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight"><?php echo $mode; ?> Class Schedule</h1>
            <p class="text-xs text-slate-500 mt-1">Configure structural boundaries for spatial resource locking.</p>
        </div>

        <div class="flex-1 overflow-y-auto px-8 py-4 flex justify-start">
            <div class="w-full max-w-4xl space-y-8">

                <form action="../actions/process_schedule.php" method="POST" id="exclusionForm" class="fiori-form-container shadow-sm border border-slate-200">
                    
                    <?php 
                        $action_val = ($mode === 'Add') ? 'create' : 'update';
                    ?>
                    <input type="hidden" name="action" value="<?php echo $action_val; ?>">

                    <?php if ($mode === 'Update'): ?><input type="hidden" name="sch_id" value="<?php echo $sch_id; ?>"><?php endif; ?>

                    <div class="fiori-section-header">
                        <h2 class="text-base font-bold text-fiori-text">Basic Info (Single Entry)</h2>
                    </div>
                    
                    <div class="p-6 flex flex-col gap-y-6">
                        <div class="w-full">
                            <label class="block text-xs font-bold text-fiori-label uppercase tracking-wide mb-2">Subject Name</label>
                            <input type="text" name="subject" value="<?php echo $exclusion ? htmlspecialchars($exclusion['subject_name']) : ''; ?>" required placeholder="e.g., Data Structures" class="fiori-input w-full max-w-xl">
                        </div>

                        <div class="w-full border-t border-slate-100 pt-4">
                            <label class="block text-xs font-bold text-fiori-label uppercase tracking-wide mb-2">Semester</label>
                            <div class="relative w-full max-w-md">
                                <select name="sem_id" required class="fiori-input w-full appearance-none pr-8 bg-white cursor-pointer font-bold text-[#004aad]">
                                    <option value="" disabled selected>Select Semester</option>
                                    <?php while($sem = $semesters->fetch_assoc()): ?>
                                        <option value="<?php echo $sem['sem_id']; ?>" <?php echo (($exclusion && $exclusion['sem_id'] == $sem['sem_id']) || (!$exclusion && $sem['is_active'])) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($sem['sem_name']); ?> <?php echo $sem['is_active'] ? '(Active)' : ''; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 absolute right-3 top-3 pointer-events-none"></i>
                            </div>
                        </div>

                        <div class="w-full border-t border-slate-100 pt-4 flex flex-wrap gap-x-6 gap-y-4">
                            <div class="flex-1 min-w-[300px] max-w-md">
                                <label class="block text-xs font-bold text-fiori-label uppercase tracking-wide mb-2">Venue</label>
                                <div class="relative">
                                    <select name="vid" required class="fiori-input w-full appearance-none pr-8 bg-white cursor-pointer font-bold text-slate-700">
                                        <option value="" disabled selected>Select Venue</option>
                                        <?php while($v = $venues->fetch_assoc()): ?>
                                            <option value="<?php echo $v['vid']; ?>" <?php echo ($exclusion && $exclusion['vid'] == $v['vid']) ? 'selected' : ''; ?>>[<?php echo $v['vid']; ?>] <?php echo htmlspecialchars($v['vname']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 absolute right-3 top-3 pointer-events-none"></i>
                                </div>
                            </div>

                            <div class="flex-1 min-w-[200px] max-w-xs">
                                <label class="block text-xs font-bold text-fiori-label uppercase tracking-wide mb-2">Day</label>
                                <div class="relative">
                                    <select name="day_of_week" required class="fiori-input w-full appearance-none pr-8 bg-white cursor-pointer font-bold text-slate-700">
                                        <?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day): ?>
                                            <option value="<?php echo $day; ?>" <?php echo ($exclusion && $exclusion['day_of_week'] == $day) ? 'selected' : ''; ?>><?php echo $day; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i data-lucide="calendar" class="w-4 h-4 text-slate-400 absolute right-3 top-3 pointer-events-none"></i>
                                </div>
                            </div>
                        </div>

                        <div class="w-full border-t border-slate-100 pt-4 flex flex-wrap gap-x-6 gap-y-4">
                            <div class="w-40">
                                <label class="block text-xs font-bold text-fiori-label uppercase tracking-wide mb-2">Start Time</label>
                                <input type="time" name="start_time" value="<?php echo $exclusion ? $exclusion['start_time'] : '08:00'; ?>" required class="fiori-input w-full font-mono font-bold text-[#059669]">
                            </div>
                            
                            <div class="w-40">
                                <label class="block text-xs font-bold text-fiori-label uppercase tracking-wide mb-2">End Time</label>
                                <input type="time" name="end_time" value="<?php echo $exclusion ? $exclusion['end_time'] : '10:00'; ?>" required class="fiori-input w-full font-mono font-bold text-[#dc2626]">
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                        <button type="button" onclick="window.location.href='academic_schedule.php'" class="px-5 py-2 text-sm font-bold text-indigo-600 hover:bg-indigo-50 rounded transition-colors mr-2">Cancel</button>
                        <button type="submit" id="submitBtn" class="px-6 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">
                            <i data-lucide="save" class="w-4 h-4 mr-2 inline"></i> <?php echo ($mode === 'Update') ? 'Save Changes' : 'Add Single Entry'; ?>
                        </button>
                    </div>
                </form>

                <?php if ($mode === 'Add'): ?>
                <form action="../actions/process_schedule.php" method="POST" enctype="multipart/form-data" id="csvSubmitForm" class="fiori-form-container shadow-sm border border-slate-200">
                    <input type="hidden" name="action" value="batch_import">
                    
                    <div class="fiori-section-header flex justify-between items-center bg-slate-50">
                        <h2 class="text-base font-bold text-fiori-text">Batch Import</h2>
                        <span class="text-[10px] font-black bg-emerald-100 text-emerald-700 px-2 py-1 rounded uppercase tracking-widest border border-emerald-200">Mass Upload</span>
                    </div>

                    <div class="p-6 flex flex-col gap-y-6">
                        <div class="w-full">
                            <label class="block text-xs font-bold text-fiori-label uppercase tracking-wide mb-2">Target Semester</label>
                            <div class="relative w-full max-w-md">
                                <select name="sem_id" required class="fiori-input w-full appearance-none pr-8 bg-white cursor-pointer font-bold text-slate-700">
                                    <option value="" disabled selected>Select Destination Semester</option>
                                    <?php 
                                    $semesters->data_seek(0); // 重置指針
                                    while($sem = $semesters->fetch_assoc()): ?>
                                        <option value="<?php echo $sem['sem_id']; ?>" <?php echo $sem['is_active'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($sem['sem_name']); ?> <?php echo $sem['is_active'] ? '(Active)' : ''; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 absolute right-3 top-3 pointer-events-none"></i>
                            </div>
                        </div>

                        <div class="w-full border-t border-slate-100 pt-4">
                            <label class="block text-xs font-bold text-fiori-label uppercase tracking-wide mb-2">CSV Data File</label>
                            <div class="flex items-center space-x-4 w-full max-w-3xl">
                                <div class="flex-1 relative">
                                    <input type="file" name="csv_file" accept=".csv" required class="absolute inset-0 opacity-0 cursor-pointer z-10 w-full h-full" onchange="document.getElementById('csv-name-display').innerText = this.files[0].name">
                                    <div class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-sm text-slate-500 font-mono flex justify-between items-center hover:border-emerald-500 transition-colors">
                                        <span id="csv-name-display" class="truncate pr-2">Choose CSV file...</span>
                                        <i data-lucide="upload" class="w-4 h-4 text-slate-400 shrink-0"></i>
                                    </div>
                                </div>
                                <button type="submit" id="btnCsvSubmit" class="px-6 py-3 bg-emerald-600 text-white text-sm font-bold rounded-lg shadow-sm hover:bg-emerald-700 transition-colors flex items-center justify-center min-w-[140px]">
                                    <i data-lucide="file-spreadsheet" class="w-4 h-4 mr-2"></i> Import Data
                                </button>
                            </div>
                            <p class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-widest">* Format strictly requires: Venue ID, Day, Start Time, End Time, Subject Name.</p>
                        </div>
                    </div>
                </form>
                <?php endif; ?>

            </div>
        </div>
        
    </main>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();

        
        // 單筆表單提交防護
        document.getElementById('exclusionForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2 inline"></i> Saving...';
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            btn.style.pointerEvents = 'none';
            lucide.createIcons();
        });

        

        // 批量匯入表單提交防護
        const csvForm = document.getElementById('csvSubmitForm');
        if (csvForm) {
            csvForm.addEventListener('submit', function() {
                const btn = document.getElementById('btnCsvSubmit');
                btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2 inline"></i> Importing...';
                btn.classList.add('opacity-70', 'cursor-not-allowed');
                btn.style.pointerEvents = 'none';
                lucide.createIcons();
            });
        }
    </script>
</body>
</html>