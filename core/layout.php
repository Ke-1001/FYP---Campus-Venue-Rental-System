<?php
if (!isset($page_title)) {
    $page_title = "MMU Admin";
}

if (!isset($page_description)) {
    $page_description = "";
}

if (!isset($topbar_content)) {
    $topbar_content = "";
}

if (!isset($page_content)) {
    $page_content = "";
}

if (!isset($extra_css)) {
    $extra_css = [];
}

if (!isset($extra_js)) {
    $extra_js = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $page_title; ?></title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Lucide -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cstyle: {
                            blue: '#004aad',
                            dark: '#1e293b',
                            accent: '#38bdf8'
                        }
                    },
                    fontFamily: {
                        sans: ['Century Gothic', 'CenturyGothic', 'Century', 'Arial', 'sans-serif']
                    }
                }
            }
        }
    </script>

    <!-- Global Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin_css.css?v=2.0">
</head>

<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <!-- Sidebar -->
    <?php include('../includes/admin_sidebar.php'); ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">

        <!-- Topbar -->
        <?php include('../includes/admin_topbar.php'); ?>

        <!-- Page Content -->
        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">

            <!-- Page Header -->
            <div class="mb-8 border-b border-slate-200 pb-4">
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">
                    <?php echo $page_title; ?>
                </h1>

                <?php if (!empty($page_description)): ?>
                    <p class="text-sm text-slate-500 mt-1">
                        <?php echo $page_description; ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Injected Content -->
            <?php echo $page_content; ?>

        </div>
    </main>

    <!-- Global UI Components -->
    <?php include('../includes/ui_components.php'); ?>

    <!-- Global Scripts -->
    <script>
        lucide.createIcons();

        function toggleSidebar() {
            document
                .getElementById('system-sidebar')
                .classList.toggle('sidebar-collapsed');
        }
    </script>

    <!-- Dynamic JS -->
    <?php foreach ($extra_js as $js): ?>
        <script src="<?php echo $js; ?>"></script>
    <?php endforeach; ?>

</body>
</html>