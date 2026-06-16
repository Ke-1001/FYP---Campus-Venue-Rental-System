<?php
// This section provides shared user header logic or layout.
if (session_status() === PHP_SESSION_NONE)
{
    session_start();
}

if (!isset($page_title))
{
    $page_title = "CVBMS | Campus Venue Booking";
}

$current_page = basename($_SERVER['PHP_SELF']);
$is_homepage = ($current_page === 'homepage.php');
$is_dashboard = ($current_page === 'user_dashboard.php');
$is_light_user_page = (!$is_homepage && !$is_dashboard);
$body_theme_class = $is_light_user_page
    ? 'bg-white text-slate-900 user-light-theme'
    : 'bg-slate-950 text-slate-100 user-dark-theme';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
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
                        mmu:
                        {
                            core: '#004aad',
                            glow: '#3b82f6'
                        },
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
    <link rel="stylesheet" href="../assets/css/user_css.css?v=2.8">
</head>
<body class="font-sans antialiased selection:bg-mmu-glow selection:text-white min-h-screen relative overflow-x-hidden <?php echo $body_theme_class; ?>">
