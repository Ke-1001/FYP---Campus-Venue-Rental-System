<?php
// templates/layout.php
// 获取传入的 $title 和 $content_body
$title = $title ?? 'System Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Admin | <?php echo $title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { cstyle: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
    <link rel="stylesheet" href="../assets/css/layout.css?v=1.2">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex">
    <?php include('../includes/admin_sidebar.php'); ?>
    
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <?php include('../includes/admin_topbar.php'); ?>
        <div class="flex-1 overflow-y-auto p-8">
            <?php echo $content_body; ?>
        </div>
    </main>
    <script>lucide.createIcons();</script>
</body>
</html>