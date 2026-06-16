<?php
// This section checks whether an email is already registered.
session_start();


$target_email = $_SESSION['recovery_email'] ?? 'your configured address';
unset($_SESSION['recovery_email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CVBMS System | Check Your Inbox</title>
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
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex items-center justify-center overflow-hidden">

    <div class="max-w-md w-full mx-4">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-black text-indigo-700 tracking-tight">CVBMS Management</h1>
            <p class="text-sm font-bold text-slate-500 uppercase tracking-widest mt-2">Reset Password</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden text-center p-8">

            <div class="w-20 h-20 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-6 border border-indigo-100 shadow-sm">
                <i data-lucide="mail-check" class="w-10 h-10 text-indigo-600"></i>
            </div>

            <h2 class="text-2xl font-black text-slate-800 mb-3">Check Your Inbox</h2>

            <p class="text-sm text-slate-500 font-medium leading-relaxed mb-6">
                If <strong class="text-slate-700"><?php echo htmlspecialchars($target_email); ?></strong> aligns with an active entity in our registry, a password reset link has been successfully dispatched. </p>  <div class="space-y-3 mb-8"> <a href="https://mail.google.com/" target="_blank" class="w-full py-3 bg-white border border-slate-200 text-slate-700 text-sm font-bold rounded-lg hover:bg-slate-50 transition shadow-sm flex items-center justify-center group"> <img src="https://www.gstatic.com/images/branding/product/1x/gmail_32dp.png" alt="Gmail" class="w-5 h-5 mr-3 grayscale group-hover:grayscale-0 transition-all"> Open Gmail Workspace </a>  <a href="https://outlook.office.com/mail/" target="_blank" class="w-full py-3 bg-white border border-slate-200 text-slate-700 text-sm font-bold rounded-lg hover:bg-slate-50 transition shadow-sm flex items-center justify-center group"> <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/d/df/Microsoft_Office_Outlook_%282018%E2%80%93present%29.svg/512px-Microsoft_Office_Outlook_%282018%E2%80%93present%29.svg.png" alt="Outlook" class="w-5 h-5 mr-3 grayscale group-hover:grayscale-0 transition-all"> Open Microsoft Outlook </a> </div> </div>  <script> lucide.createIcons();
    </script>
</body>
</html>
