<?php
// File: admin/add_semester.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

$sem_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mode = ($sem_id > 0) ? 'Update' : 'Create'; 
$semester = null;

if ($mode === 'Update') {
    $stmt = $conn->prepare("SELECT * FROM semester_config WHERE sem_id = ?");
    $stmt->bind_param("i", $sem_id);
    $stmt->execute();
    $semester = $stmt->get_result()->fetch_assoc();
    if (!$semester) die("Error: Semester vector not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | <?php echo $mode; ?> Semester</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>tailwind.config = { theme: { extend: { colors: { fiori: { text: '#1d2d3e', label: '#64748b', blue: '#004aad' } } } } }</script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori_forms.css">
</head>
<body class="bg-[#f4f4f4] text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0 bg-white">
            <div class="flex items-center">
                <a href="semester_management.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors"><i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back</a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Academic / <?php echo $mode; ?> Semester</h2>
            </div>
        </header>

        <form action="../actions/process_semester.php" method="POST" id="semesterForm" class="flex-1 flex flex-col overflow-hidden">
            <input type="hidden" name="action" value="<?php echo strtolower($mode); ?>">

            <div class="flex-1 overflow-y-auto p-4 md:p-8 flex justify-center">
                <div class="w-full max-w-3xl space-y-6">
                    
                    <div class="mb-4">
                        <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight"><?php echo $mode; ?> Semester Node</h1>
                        <p class="text-xs text-slate-500 mt-1">Configure global temporal boundaries for academic operations.</p>
                    </div>

                    <div class="fiori-form-container">
                        <div class="fiori-section-header"><h2 class="text-base font-bold text-fiori-text">Temporal Configuration</h2></div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-fiori-label uppercase tracking-wide mb-2">Semester Code (4 Digits)</label>
                                <?php if ($mode === 'Update'): ?>
                                    <input type="number" name="sem_id" value="<?php echo $sem_id; ?>" readonly class="fiori-input fiori-readonly w-full max-w-xs font-mono font-bold text-indigo-700">
                                    <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-widest">* Primary Key is locked to prevent cascade failure.</p>
                                <?php else: ?>
                                    <input type="number" name="sem_id" required min="1000" max="9999" placeholder="e.g., 2610" class="fiori-input w-full max-w-xs font-mono font-bold">
                                <?php endif; ?>
                            </div>

                            <div class="md:col-span-2 border-t border-slate-100 pt-6 mt-2">
                                <label class="block text-xs font-bold text-fiori-label uppercase tracking-wide mb-2">Semester Nomenclature</label>
                                <input type="text" name="sem_name" value="<?php echo $semester ? htmlspecialchars($semester['sem_name']) : ''; ?>" required placeholder="e.g., 2026 Trimester 1" class="fiori-input w-full">
                            </div>

                            <div class="w-full">
                                <label class="block text-xs font-bold text-fiori-label uppercase tracking-wide mb-2">Start Boundary</label>
                                <div class="relative">
                                    <input type="date" name="start_date" value="<?php echo $semester ? $semester['start_date'] : ''; ?>" required class="fiori-input w-full font-mono text-emerald-700 font-bold">
                                </div>
                            </div>
                            
                            <div class="w-full">
                                <label class="block text-xs font-bold text-fiori-label uppercase tracking-wide mb-2">End Boundary</label>
                                <div class="relative">
                                    <input type="date" name="end_date" value="<?php echo $semester ? $semester['end_date'] : ''; ?>" required class="fiori-input w-full font-mono text-red-600 font-bold">
                                </div>
                            </div>

                            <div class="md:col-span-2 border-t border-slate-100 pt-6 mt-2">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" <?php echo ($semester && $semester['is_active'] == 1) ? 'checked' : ''; ?> class="w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                    <div>
                                        <span class="block text-sm font-bold text-slate-800">Set as Active Semester</span>
                                        <span class="block text-[10px] font-medium text-slate-500 uppercase tracking-widest mt-0.5">Warning: This will dynamically deactivate all other semesters.</span>
                                    </div>
                                </label>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="fiori-footer-toolbar shrink-0 z-20 shadow-[0_-2px_10px_rgba(0,0,0,0.05)]">
                <button type="button" onclick="window.location.href='semester_management.php'" class="fiori-btn-cancel">Discard</button>
                <button type="submit" id="submitBtn" class="fiori-btn-primary">
                    <i data-lucide="save" class="w-4 h-4 mr-2 inline"></i> <?php echo ($mode === 'Update') ? 'Commit Update' : 'Deploy Semester'; ?>
                </button>
            </div>
        </form>
    </main>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();
        document.getElementById('semesterForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2 inline"></i> Executing...';
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            btn.style.pointerEvents = 'none';
            lucide.createIcons();
        });
    </script>
</body>
</html>