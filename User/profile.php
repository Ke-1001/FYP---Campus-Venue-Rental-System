<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['uid'])) { header("Location: user_login.php"); exit(); }
$uid = $_SESSION['uid'];

$stmt = $conn->prepare("SELECT * FROM user WHERE uid = ?");
$stmt->bind_param("s", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | CVBMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
        .glass-panel { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="bg-slate-900 font-sans text-slate-200 min-h-screen relative overflow-hidden">

<div class="fixed inset-0 z-0">
    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80" class="w-full h-full object-cover opacity-30">
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/50 to-slate-950"></div>
</div>

<div class="relative z-10 max-w-2xl mx-auto pt-20 px-4">
    <a href="homepage.php" class="inline-flex items-center gap-2 text-slate-400 hover:text-white mb-8 transition">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Campus
    </a>

    <div class="glass-panel rounded-3xl p-8 shadow-2xl">
        <div class="flex flex-col items-center mb-10">
            <div class="w-24 h-24 bg-blue-600 rounded-full flex items-center justify-center text-3xl font-black text-white shadow-[0_0_30px_rgba(37,99,235,0.4)] mb-4">
                <?php echo substr($user['username'], 0, 1); ?>
            </div>
            <h1 class="text-3xl font-extrabold text-white"><?php echo htmlspecialchars($user['username']); ?></h1>
            <p class="text-blue-400 font-bold tracking-widest text-xs uppercase mt-1">Authorized Student Account</p>
        </div>

        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white/5 p-4 rounded-2xl border border-white/5">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Student ID</p>
                    <p class="text-white font-semibold"><?php echo htmlspecialchars($user['uid']); ?></p>
                </div>
                <div class="bg-white/5 p-4 rounded-2xl border border-white/5">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Phone Number</p>
                    <p class="text-white font-semibold"><?php echo htmlspecialchars($user['phone_num']); ?></p>
                </div>
            </div>

            <div class="bg-white/5 p-4 rounded-2xl border border-white/5">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Academic Email</p>
                <p class="text-white font-semibold"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </div>

        <div class="mt-10 flex flex-col gap-3">
            <a href="edit_profile.php" class="w-full bg-white text-slate-900 font-bold py-4 rounded-2xl text-center hover:bg-blue-50 transition shadow-lg">
                Edit Identity Details
            </a>
            <a href="change_password.php" class="w-full bg-white/5 text-white font-bold py-4 rounded-2xl text-center border border-white/10 hover:bg-white/10 transition">
                Modify Password
            </a>
        </div>
    </div>
</div>

<script>lucide.createIcons();</script>
</body>
</html>