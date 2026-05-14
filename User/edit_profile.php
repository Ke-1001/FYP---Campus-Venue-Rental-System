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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .glass-panel { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.15); }
        .input-glass { background: rgba(255, 255, 255, 0.95); border: 2px solid transparent; transition: 0.3s; color: #1e293b; }
        .input-glass:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 15px rgba(59, 130, 246, 0.3); }
    </style>
</head>
<body class="bg-slate-950 min-h-screen flex items-center justify-center px-4">

<div class="fixed inset-0 z-0">
    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80" class="w-full h-full object-cover opacity-20">
</div>

<div class="relative z-10 w-full max-w-md">
    <div class="glass-panel rounded-3xl p-8 shadow-2xl">
        <h2 class="text-2xl font-black text-white mb-6 flex items-center gap-2">
            <i data-lucide="user-cog" class="text-blue-400"></i> Update Profile
        </h2>

        <form action="update_profile_process.php" method="POST" class="space-y-5">
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Full Name</label>
                <input type="text" name="username" value="<?php echo $user['username']; ?>" class="input-glass w-full px-4 py-3 rounded-xl font-bold">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Phone Number</label>
                <input type="text" name="phone_num" value="<?php echo $user['phone_num']; ?>" class="input-glass w-full px-4 py-3 rounded-xl font-bold">
            </div>

            <div class="pt-4 flex gap-3">
                <a href="profile.php" class="flex-1 text-center py-4 text-slate-400 font-bold hover:text-white transition">Cancel</a>
                <button type="submit" class="flex-[2] bg-blue-600 hover:bg-blue-500 text-white font-bold py-4 rounded-2xl shadow-xl transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
<script>lucide.createIcons();</script>
</body>
</html>