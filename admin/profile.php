<?php
// File: admin/profile.php
session_start();
require_once '../config/db.php';
require_once '../includes/admin_auth.php'; 

// 💡 1. 識別碼相容性：使用新的 aid，並以字串型態查詢
$aid = $_SESSION['aid'] ?? $_SESSION['user_id'];

// 💡 2. 適配新架構：查詢 admin 表，加入 phone_num 欄位
$sql = "SELECT admin_name, email, phone_num, role FROM admin WHERE aid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $aid); // "s" for string since aid is VARCHAR
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Critical Error: Entity payload not found in admin registry.");
}
$admin_data = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Admin | Entity Configuration</title>
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
        
        <?php 
        $topbar_content = '<h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Identity Governance / Entity Configuration</h2>';
        include('../includes/admin_topbar.php'); 
        ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="max-w-4xl mx-auto">
                <div class="mb-8">
                    <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Entity Configuration</h1>
                    <p class="text-sm text-slate-500 mt-1">Manage personal identifiers and secure cryptographic keys.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-2 space-y-8">
                        
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center">
                                <i data-lucide="user" class="w-5 h-5 mr-2 text-mmu-blue"></i>
                                <h3 class="text-lg font-extrabold text-slate-800">Identity Parameters</h3>
                            </div>
                            <form action="../actions/process_profile.php" method="POST" class="p-6 space-y-6">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Entity Full Name</label>
                                        <input type="text" name="admin_name" value="<?php echo htmlspecialchars($admin_data['admin_name']); ?>" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-mmu-blue outline-none text-sm transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Institutional Email</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($admin_data['email']); ?>" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-mmu-blue outline-none text-sm font-mono transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Contact Number</label>
                                        <input type="text" name="phone_num" value="<?php echo htmlspecialchars($admin_data['phone_num']); ?>" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-mmu-blue outline-none text-sm font-mono transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Access Level (Immutable)</label>
                                        <div class="w-full px-4 py-3 bg-slate-100 border border-slate-200 rounded-lg text-sm font-bold text-slate-500 cursor-not-allowed flex items-center">
                                            <i data-lucide="shield" class="w-4 h-4 mr-2 <?php echo $admin_data['role'] === 'super_admin' ? 'text-purple-600' : 'text-mmu-blue'; ?>"></i> 
                                            <?php echo ucwords(str_replace('_', ' ', $admin_data['role'])); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end pt-4 border-t border-slate-100">
                                    <button type="submit" class="px-6 py-3 text-sm font-bold text-white bg-mmu-blue hover:bg-blue-700 rounded-lg transition-all shadow-md flex items-center">
                                        <i data-lucide="save" class="w-4 h-4 mr-2"></i> Save Parameters
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center">
                                <i data-lucide="key" class="w-5 h-5 mr-2 text-indigo-600"></i>
                                <h3 class="text-lg font-extrabold text-slate-800">Cryptographic Key Configuration</h3>
                            </div>
                            <form action="../actions/process_profile.php" method="POST" id="passwordForm" class="p-6 space-y-6">
                                <input type="hidden" name="action" value="update_password">
                                
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Current Key</label>
                                    <input type="password" name="current_password" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm font-mono tracking-widest transition-all">
                                </div>

                                <div class="border-t border-slate-100 pt-6">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">New Cryptographic Key</label>
                                    <input type="password" name="new_password" id="new_password" required onkeyup="validateCryptographicMatrix()" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm font-mono tracking-widest transition-all">
                                    
                                    <div class="mt-4 p-4 bg-slate-50 border border-slate-200 rounded-lg grid grid-cols-1 md:grid-cols-2 gap-2 text-xs font-bold text-slate-400">
                                        <div id="rule-length" class="flex items-center transition-colors"><span class="icon-slot"></span> Minimum 8 Characters</div>
                                        <div id="rule-upper" class="flex items-center transition-colors"><span class="icon-slot"></span> 1 Uppercase (A-Z)</div>
                                        <div id="rule-lower" class="flex items-center transition-colors"><span class="icon-slot"></span> 1 Lowercase (a-z)</div>
                                        <div id="rule-number" class="flex items-center transition-colors"><span class="icon-slot"></span> 1 Numeric (0-9)</div>
                                        <div id="rule-special" class="flex items-center transition-colors col-span-1 md:col-span-2"><span class="icon-slot"></span> 1 Symbol (@$!%*?&)</div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Verify New Key</label>
                                    <input type="password" name="confirm_password" id="confirm_password" required onkeyup="validateCryptographicMatrix()" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm font-mono tracking-widest transition-all">
                                    <p id="match-feedback" class="text-[10px] font-bold text-red-500 mt-1 hidden tracking-wide uppercase">Keys do not match</p>
                                </div>

                                <div class="flex justify-end pt-4 border-t border-slate-100">
                                    <button type="submit" id="btn-pwd-submit" disabled class="px-6 py-3 text-sm font-bold text-white bg-slate-300 rounded-lg transition-all flex items-center cursor-not-allowed">
                                        <i data-lucide="shield" class="w-4 h-4 mr-2"></i> Enforce New Key
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="lg:col-span-1">
                        <div class="bg-slate-900 rounded-2xl shadow-lg border border-slate-800 p-6 text-white sticky top-8">
                            <div class="flex items-center justify-center w-20 h-20 bg-mmu-blue/20 rounded-full border-2 border-mmu-blue mx-auto mb-4">
                                <i data-lucide="shield-check" class="w-10 h-10 text-mmu-accent"></i>
                            </div>
                            <h3 class="text-center text-xl font-extrabold tracking-wide mb-1"><?php echo htmlspecialchars($admin_data['admin_name']); ?></h3>
                            <p class="text-center text-xs text-slate-400 font-mono tracking-widest uppercase mb-6"><?php echo ucwords(str_replace('_', ' ', $admin_data['role'])); ?></p>
                            
                            <div class="space-y-4">
                                <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Session Node</p>
                                    <p class="text-sm font-mono text-emerald-400 flex items-center"><i data-lucide="activity" class="w-3 h-3 mr-1"></i> Active & Secured</p>
                                </div>
                                <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">System Privilege</p>
                                    <p class="text-sm text-slate-300"><?php echo $admin_data['role'] === 'super_admin' ? 'Root access granted. Can configure infrastructure and identity nodes.' : 'Standard operational access. Restricted identity governance.'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();

        function toggleSidebar() {
            document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed');
        }

        // Raw SVG injected directly to bypass Lucide DOM destruction on keyup
        const checkSVG = `<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
        const crossSVG = `<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;

        window.onload = () => {
            const rules = ['rule-length', 'rule-upper', 'rule-lower', 'rule-number', 'rule-special'];
            rules.forEach(id => {
                document.getElementById(id).querySelector('.icon-slot').innerHTML = crossSVG;
            });
        };

        function validateCryptographicMatrix() {
            const pwd = document.getElementById('new_password').value;
            const confirmPwd = document.getElementById('confirm_password').value;
            const feedback = document.getElementById('match-feedback');
            const confirmInput = document.getElementById('confirm_password');
            const btn = document.getElementById('btn-pwd-submit');
            
            const reqs = {
                length: pwd.length >= 8,
                upper: /[A-Z]/.test(pwd),
                lower: /[a-z]/.test(pwd),
                number: /\d/.test(pwd),
                special: /[@$!%*?&]/.test(pwd)
            };

            const toggleRule = (id, isValid) => {
                const el = document.getElementById(id);
                const iconSlot = el.querySelector('.icon-slot');
                if (isValid) {
                    el.className = 'flex items-center transition-colors text-emerald-600';
                    iconSlot.innerHTML = checkSVG;
                } else {
                    el.className = 'flex items-center transition-colors text-slate-400';
                    iconSlot.innerHTML = crossSVG;
                }
            };

            toggleRule('rule-length', reqs.length);
            toggleRule('rule-upper', reqs.upper);
            toggleRule('rule-lower', reqs.lower);
            toggleRule('rule-number', reqs.number);
            toggleRule('rule-special', reqs.special);

            const isSecure = Object.values(reqs).every(val => val === true);
            const isMatch = pwd === confirmPwd && pwd.length > 0;

            if (confirmPwd.length > 0) {
                if (isMatch) {
                    confirmInput.classList.replace('border-red-400', 'border-slate-200');
                    feedback.classList.add('hidden');
                } else {
                    confirmInput.classList.replace('border-slate-200', 'border-red-400');
                    feedback.classList.remove('hidden');
                }
            } else {
                confirmInput.classList.replace('border-red-400', 'border-slate-200');
                feedback.classList.add('hidden');
            }

            if (isSecure && isMatch) {
                btn.disabled = false;
                btn.className = "px-6 py-3 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md rounded-lg transition-all flex items-center cursor-pointer";
            } else {
                btn.disabled = true;
                btn.className = "px-6 py-3 text-sm font-bold text-white bg-slate-300 rounded-lg transition-all flex items-center cursor-not-allowed";
            }
        }
    </script>
</body>
</html>