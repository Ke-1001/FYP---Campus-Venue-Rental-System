<?php

// This section prepares the admin profile page.
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

$aid = $_SESSION['aid'] ?? $_SESSION['user_id'];

$sql = "SELECT admin_name, email, phone_num, role FROM admin WHERE aid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $aid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0)
{
    die("Critical Error: Entity payload not found in registry.");
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
        tailwind.config =
{ theme:
{ extend:
{ colors:
{ fiori:
{ text: '#1d2d3e', label: '#6b7280', blue: '#0a6ed1' } } } } }
    </script>
    <link rel="stylesheet" href="../assets/css/admin_css.css?v=2.0">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">

        <header class="h-16 glass-panel border-b border-slate-200 flex items-center justify-between px-6 z-10 shrink-0 bg-white">
            <?php
            $topbar_content = '<div class="flex items-center">
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">System / Entity Profile</h2>
            </div>';
            include('../includes/admin_topbar.php');
            ?>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-8 flex justify-center items-start">
            <div class="w-full max-w-3xl space-y-6">

                <div class="mb-4">
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Profile Configuration</h1>
                    <p class="text-xs text-slate-500 mt-1">Review your account details and manage password.</p>
                </div>

                <div class="fiori-form-container shadow-sm border border-slate-200 bg-white rounded-xl overflow-hidden">
                    <div class="fiori-section-header bg-slate-50 px-6 py-4 border-b border-slate-100">
                        <h2 class="text-base font-bold text-fiori-text">Identity Properties</h2>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-fiori-label uppercase tracking-wide mb-2">User ID</label>
                            <input type="text" value="<?php echo htmlspecialchars($aid); ?>" readonly class="fiori-input fiori-readonly w-full font-mono text-slate-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-fiori-label uppercase tracking-wide mb-2">Authorization Level</label>
                            <?php
                            $role_css = ($admin_data['role'] === 'super_admin') ? 'text-purple-700 bg-purple-50 border-purple-200' : 'text-indigo-700 bg-indigo-50 border-indigo-200';
                            $role_label = ($admin_data['role'] === 'super_admin') ? 'Super Administrator' : 'Standard Administrator';
                            ?>
                            <div class="h-[42px] flex items-center px-4 border <?php echo $role_css; ?> rounded-lg">
                                <span class="text-xs font-black uppercase tracking-widest"><?php echo $role_label; ?></span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-fiori-label uppercase tracking-wide mb-2">Full Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($admin_data['admin_name']); ?>" readonly class="fiori-input fiori-readonly w-full">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-fiori-label uppercase tracking-wide mb-2">Contact Number</label>
                            <input type="text" value="<?php echo htmlspecialchars($admin_data['phone_num']); ?>" readonly class="fiori-input fiori-readonly w-full font-mono">
                        </div>
                    </div>
                </div>

                <div class="fiori-form-container shadow-sm border border-slate-200 bg-white rounded-xl overflow-hidden mt-6">
                    <div class="fiori-section-header bg-slate-50 px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-base font-bold text-slate-800"><i data-lucide="shield" class="w-4 h-4 inline mr-1 text-indigo-600"></i> User Status</h2>
                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded text-[9px] font-black uppercase tracking-widest">Active</span>
                    </div>

                    <div class="p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-white">
                        <div class="flex-1">
                            <h3 class="text-sm font-extrabold text-slate-800">Password Reconfiguration</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed font-medium">
                                A time-limited link will be dispatched to <strong class="text-slate-700"><?php echo htmlspecialchars($admin_data['email']); ?></strong> to set a new password   .
                            </p>
                        </div>

                        <form action="../actions/process_forgot_password.php" method="POST" id="resetPasswordForm" class="shrink-0 w-full md:w-auto">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($admin_data['email']); ?>">

                            <button type="button" onclick="triggerCustomModal(this.closest('form'), 'Dispatch a secure password reset token to <?php echo htmlspecialchars($admin_data['email']); ?>?')" class="w-full md:w-auto px-5 py-2.5 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-100 hover:bg-indigo-100 transition shadow-sm flex items-center justify-center">
                                <i data-lucide="key" class="w-4 h-4 mr-2"></i> Issue Reset Password Email
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <div id="custom-modal" class="fixed inset-0 z-[100] hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center transition-opacity opacity-0">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform scale-95 transition-transform" id="modal-panel">
            <div class="p-6">
                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center mb-4"><i data-lucide="mail-warning" class="w-6 h-6 text-indigo-600"></i></div>
                <h3 class="text-lg font-extrabold text-slate-800 mb-2">Security Override</h3>
                <p id="modal-msg" class="text-sm text-slate-500 font-medium leading-relaxed"></p>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end space-x-2">
                <button type="button" onclick="closeCustomModal()" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-200 rounded-lg transition-colors">Cancel</button>
                <button type="button" id="confirm-btn" class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">Dispatch Token</button>
            </div>
        </div>
    </div>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();
        function toggleSidebar()
{ document.getElementById('system-sidebar').classList.toggle('sidebar-collapsed'); }

        let activeForm = null;
        function triggerCustomModal(form, msg)
        {
            activeForm = form;
            document.getElementById('modal-msg').innerText = msg;
            const m = document.getElementById('custom-modal');
            const p = document.getElementById('modal-panel');
            m.classList.remove('hidden');
            setTimeout(() =>
{ m.classList.remove('opacity-0'); p.classList.remove('scale-95'); }, 10);
        }
        function closeCustomModal()
        {
            const m = document.getElementById('custom-modal');
            const p = document.getElementById('modal-panel');
            m.classList.add('opacity-0'); p.classList.add('scale-95');
            setTimeout(() =>
{ m.classList.add('hidden'); activeForm = null; }, 200);
        }
        document.getElementById('confirm-btn').addEventListener('click', () =>
        {
            if(activeForm)
            {
                document.getElementById('confirm-btn').innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-1"></i> Executing...';
                document.getElementById('confirm-btn').classList.add('opacity-70', 'cursor-not-allowed');
                lucide.createIcons();
                activeForm.submit();
            }
        });
    </script>
</body>
</html>
