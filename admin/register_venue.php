<?php
// File: admin/register_venue.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

// 💡 提取正規化的類別清單
$cat_res = $conn->query("SELECT * FROM vcategory ORDER BY category ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Register Asset</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { cstyle: { blue: '#004aad', dark: '#1e293b' }, fiori: { bg: '#f4f4f4', text: '#1d2d3e', blue: '#0a6ed1', label: '#6b7280' } } } } }
    </script>
    <link rel="stylesheet" href="layout.css?v=1.2">
    <link rel="stylesheet" href="../assets/css/fiori_forms.css">
</head>
<body class="bg-[#f4f4f4] text-slate-800 font-sans antialiased h-screen flex overflow-hidden">
    
    <?php include('../includes/admin_sidebar.php'); ?>
    
    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-[#f4f4f4] relative">
        
        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0 bg-white">
            <?php 
            $topbar_content = '
            <div class="flex items-center">
                <a href="venue_directory.php" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center mr-4 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
                </a>
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Asset Management / Register Venue</h2>
            </div>';
            include('../includes/admin_topbar.php'); 
            ?>
        </header>

        <form action="../actions/process_venue.php" method="POST" enctype="multipart/form-data" id="registerVenueForm" class="flex-1 flex flex-col overflow-hidden">
            <input type="hidden" name="action" value="create">

            <div class="flex-1 overflow-y-auto p-4 md:p-8 space-y-6">
                
                <div class="fiori-form-container">
                    <div class="fiori-section-header">
                        <h2 class="text-base font-bold text-fiori-text">Basic Data</h2>
                    </div>

                    <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">
                        
                        <div class="lg:col-span-6 space-y-4">
                            <h3 class="text-sm font-bold text-fiori-text mb-4">Identification Data</h3>
                            
                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Venue ID:</label>
                                <div class="col-span-2">
                                    <input type="text" name="vid" maxlength="10" required placeholder="e.g. MNBR2002" class="fiori-input font-mono uppercase">
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Display Name:</label>
                                <div class="col-span-2">
                                    <input type="text" name="vname" required placeholder="Official Venue Name" class="fiori-input">
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Classification:</label>
                                <div class="col-span-2 relative">
                                    <select name="vcid" required class="fiori-input appearance-none pr-8 bg-white cursor-pointer transition-colors">
                                        <?php while($c = $cat_res->fetch_assoc()): ?>
                                            <option value="<?php echo $c['vcid']; ?>"><?php echo htmlspecialchars($c['category']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-fiori-label absolute right-2 top-2 pointer-events-none"></i>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-6 space-y-4">
                            <h3 class="text-sm font-bold text-fiori-text mb-4">Operational Parameters</h3>
                            
                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Max Capacity:</label>
                                <div class="col-span-2 relative">
                                    <input type="number" name="max_cap" min="1" required class="fiori-input font-mono pr-12">
                                    <span class="absolute right-3 top-2 text-xs text-slate-400 font-bold">PAX</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-center">
                                <label class="col-span-1 text-sm text-fiori-label">Security Deposit:</label>
                                <div class="col-span-2 relative">
                                    <span class="absolute left-3 top-2 text-xs text-slate-400 font-bold">RM</span>
                                    <input type="number" name="deposit" step="0.01" min="0" required class="fiori-input font-mono pl-10 text-emerald-700 font-bold">
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 items-center border-t border-slate-100 pt-4 mt-2">
                                <label class="col-span-1 text-sm text-fiori-label">Initial State:</label>
                                <div class="col-span-2 relative">
                                    <select name="status" class="fiori-input appearance-none pr-8 bg-white cursor-pointer font-bold text-emerald-600">
                                        <option value="available">Available</option>
                                        <option value="maintenance" class="text-red-500">Maintenance</option>
                                    </select>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-fiori-label absolute right-2 top-2 pointer-events-none"></i>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="fiori-form-container">
                    <div class="fiori-section-header">
                        <h2 class="text-base font-bold text-fiori-text">Physical Assets</h2>
                    </div>
                    <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">
                        
                        <div class="lg:col-span-6 space-y-4">
                            <h3 class="text-sm font-bold text-fiori-text mb-2">Venue Description</h3>
                            <textarea name="description" rows="5" required placeholder="Provide physical details and available equipment..." class="fiori-input w-full resize-none p-3"></textarea>
                        </div>

                        <div class="lg:col-span-6 space-y-4">
                            <h3 class="text-sm font-bold text-fiori-text mb-2">Image Gallery</h3>
                            <div class="w-full border-2 border-dashed border-slate-300 rounded-lg bg-slate-50 hover:bg-slate-100 transition-colors p-6 flex flex-col items-center justify-center relative cursor-pointer" onclick="document.getElementById('venue_pics').click()">
                                <i data-lucide="image-plus" class="w-8 h-8 text-slate-400 mb-2"></i>
                                <span class="text-sm font-bold text-slate-600">Click to Browse Images</span>
                                <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-widest">JPG, PNG Supported</p>
                                <input type="file" name="venue_pics[]" id="venue_pics" multiple accept="image/*" class="hidden">
                            </div>
                            <div id="file-list" class="text-xs text-slate-500 font-mono mt-2 empty:hidden"></div>
                        </div>

                    </div>
                </div>

            </div>

            <div class="fiori-footer-toolbar">
                <button type="button" onclick="window.location.href='venue_directory.php'" class="fiori-btn-cancel">
                    Cancel
                </button>
                <button type="submit" id="submitBtn" class="fiori-btn-primary">
                    Save Venue Record
                </button>
            </div>

        </form>
    </main>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();
        function toggleSidebar() { document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed'); }

        // 前端選取檔案反饋
        document.getElementById('venue_pics').addEventListener('change', function(e) {
            const fileList = document.getElementById('file-list');
            if(this.files.length > 0) {
                fileList.innerHTML = `<i data-lucide="check-circle" class="w-3 h-3 inline text-emerald-500 mr-1"></i> ${this.files.length} file(s) selected for upload.`;
                lucide.createIcons();
            } else {
                fileList.innerHTML = '';
            }
        });

        // 💡 防止重複提交與視覺反饋
        document.getElementById('registerVenueForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2 inline"></i> Processing...';
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            btn.style.pointerEvents = 'none'; // 徹底阻斷二次點擊
            lucide.createIcons();
        });
    </script>
</body>
</html>