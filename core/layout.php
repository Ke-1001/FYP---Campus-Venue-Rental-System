<?php
// This section provides shared layout logic.
if (!isset($page_title))
{
    $page_title = "MMU Admin";
}

if (!isset($page_description))
{
    $page_description = "";
}

if (!isset($topbar_content))
{
    $topbar_content = "";
}

if (!isset($page_content))
{
    $page_content = "";
}

if (!isset($extra_css))
{
    $extra_css = [];
}

if (!isset($extra_js))
{
    $extra_js = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $page_title; ?></title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        tailwind.config =
        {
            theme:
            {
                extend:
                {
                    colors:
                    {
                        cstyle:
                        {
                            blue: '#004aad',
                            dark: '#1e293b',
                            accent: '#38bdf8'
                        }
                    },
                    fontFamily:
                    {
                        sans: ['Century Gothic', 'CenturyGothic', 'Century', 'Arial', 'sans-serif']
                    }
                }
            }
        }
    </script>

    <link rel="stylesheet" href="../assets/css/admin_css.css?v=2.0">
</head>

<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    <?php include('../includes/admin_sidebar.php'); ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">

        <?php include('../includes/admin_topbar.php'); ?>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">

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

            <?php echo $page_content; ?>

        </div>
    </main>

    <?php include('../includes/ui_components.php'); ?>

    <script>
        lucide.createIcons();

        function toggleSidebar()
        {
            document
                .getElementById('system-sidebar')
                .classList.toggle('sidebar-collapsed');
        }
    </script>

    <?php foreach ($extra_js as $js): ?>
        <script src="<?php echo $js; ?>"></script>
    <?php endforeach; ?>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // 使用 requestIdleCallback 確保 SLA 觸發不會佔用主渲染執行緒，維持系統 60fps 效能
        const triggerSLA = () => {
            fetch('/FYP/cron/scheduler_sla_check.php?token=a7b8c9d0-f1e2-4g5h-8i9j-klmnopqrstuv', { 
                method: 'GET', 
                keepalive: true,
                cache: 'no-store' 
            }).catch(err => console.error('SLA Pulse Error:', err));
        };

        if ('requestIdleCallback' in window) {
            requestIdleCallback(triggerSLA, { timeout: 2000 });
        } else {
            setTimeout(triggerSLA, 2000);
        }
    });
    </script>
</body>
</html>
