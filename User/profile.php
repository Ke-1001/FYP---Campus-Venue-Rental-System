<?php
session_start();
require_once __DIR__ . '/../config/db.php';
if (!isset($_SESSION['uid'])) { header("Location: user_login.php"); exit(); }
$uid = $_SESSION['uid'];
$stmt = $conn->prepare("SELECT * FROM user WHERE uid = ?");
$stmt->bind_param("s", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$profile_pic_url = '';
if (!empty($user['profile_pic'])) {
    $stored_pic = trim($user['profile_pic']);
    $stored_pic = str_replace('\\', '/', $stored_pic);
    $stored_pic = ltrim($stored_pic, '/');

    $candidate_paths = [];
    $candidate_urls = [];

    // Old records saved by User/update_profile_process.php, for example: uploads/1781073919_0.jpg
    $candidate_paths[] = __DIR__ . '/' . $stored_pic;
    $candidate_urls[] = $stored_pic;

    // New preferred records saved under project root, for example: uploads/profile_pic/user/file.jpg
    $candidate_paths[] = __DIR__ . '/../' . $stored_pic;
    $candidate_urls[] = '../' . $stored_pic;

    // Legacy compatibility: if database stores only a filename.
    $candidate_paths[] = __DIR__ . '/../uploads/profile_pic/user/' . basename($stored_pic);
    $candidate_urls[] = '../uploads/profile_pic/user/' . basename($stored_pic);

    foreach ($candidate_paths as $idx => $path) {
        if (is_file($path)) {
            $profile_pic_url = $candidate_urls[$idx];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | CVBMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/user_css.css?v=2.8">
</head>
<body class="profile-dark-theme bg-slate-900 font-sans text-slate-200 min-h-screen">
<div class="profile-page-bg" aria-hidden="true"></div>
<div class="relative z-10 max-w-2xl mx-auto pt-20 px-4">
    <a href="homepage.php" class="inline-flex items-center gap-2 text-slate-400 hover:text-white mb-8 transition font-medium">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Homepage
    </a>
    <div class="glass-panel rounded-3xl p-8 shadow-2xl">
        <div class="flex flex-col items-center mb-10">
            <div class="w-24 h-24 rounded-full overflow-hidden mb-4 shadow-[0_0_30px_rgba(37,99,235,0.4)] border-2 border-white/10">
                <?php if (!empty($profile_pic_url)): ?>
                    <img src="<?php echo htmlspecialchars($profile_pic_url, ENT_QUOTES, 'UTF-8'); ?>" class="w-full h-full object-cover" alt="Profile picture">
                <?php else: ?>
                    <div class="w-full h-full bg-blue-600 flex items-center justify-center text-3xl font-black text-white">
                        <?php echo substr($user['username'], 0, 1); ?>
                    </div>
                <?php endif; ?>
            </div>
            <h1 class="text-3xl font-extrabold text-white"><?php echo htmlspecialchars($user['username']); ?></h1>
            <p class="text-blue-400 font-bold tracking-widest text-xs uppercase mt-1">Authorized Student Account</p>
        </div>
        <div id="profileView" class="space-y-6">
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
            <div class="space-y-3">
                <button onclick="document.getElementById('profileView').classList.add('hidden'); document.getElementById('editView').classList.remove('hidden');" 
                        class="w-full bg-white text-slate-900 font-bold py-4 rounded-2xl hover:bg-blue-50 transition">
                    Edit Identity Details
                </button>
                <a href="change_password.php" 
                   class="w-full block text-center bg-white text-slate-900 font-bold py-4 rounded-2xl hover:bg-blue-50 transition">
                    Change Password
                </a>
            </div>
        </div>
        <div id="editView" class="hidden space-y-4">
            <form id="updateForm" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="text-[10px] text-slate-500 uppercase font-bold">Full Name (Locked)</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" readonly class="w-full p-3 rounded-xl bg-black/30 border border-white/5 text-slate-500 input-locked mb-4"></div>
                    <div><label class="text-[10px] text-slate-500 uppercase font-bold">Student ID (Locked)</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['uid']); ?>" readonly class="w-full p-3 rounded-xl bg-black/30 border border-white/5 text-slate-500 input-locked mb-4"></div>
                </div>
                <div><label class="text-[10px] text-slate-500 uppercase font-bold">Academic Email (Locked)</label>
                <input type="text" value="<?php echo htmlspecialchars($user['email']); ?>" readonly class="w-full p-3 rounded-xl bg-black/30 border border-white/5 text-slate-500 input-locked mb-4"></div>
                <div class="relative">
                    <label class="text-[10px] text-slate-500 uppercase font-bold">Phone Number</label>
                    <div id="phoneToast" class="hidden absolute -top-1 right-0 bg-red-600 text-white text-[12px] px-3 py-1 rounded-lg z-50">Only numbers allowed!</div>
                    <input type="tel" name="phone_num" value="<?php echo htmlspecialchars($user['phone_num']); ?>" oninput="validatePhone(this)" class="w-full p-3 rounded-xl bg-white/10 text-white font-bold mb-4 border border-blue-500/50">
                </div>
                <div class="mb-4">
                    <label class="text-[10px] text-slate-500 uppercase font-bold mb-1 block">Profile Picture</label>
                    <input type="file" id="fileInput" name="profile_pic" accept="image/*" class="hidden" onchange="document.getElementById('fileName').textContent = this.files[0].name">
                    <label for="fileInput" class="w-full block text-center bg-blue-600 text-white font-bold py-4 rounded-2xl hover:bg-blue-500 transition shadow-lg cursor-pointer">
                        <span id="fileName">Choose New Picture</span>
                    </label>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="location.reload()" class="flex-1 py-4 text-slate-400 font-bold hover:text-white transition">Cancel</button>
                    <button type="submit" class="flex-[2] bg-blue-600 text-white font-bold py-4 rounded-2xl hover:bg-blue-500 transition shadow-lg">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div id="toast" class="hidden fixed bottom-10 right-10 bg-emerald-600 text-white px-8 py-4 rounded-2xl shadow-2xl font-bold">Profile Updated!</div>
<script>
    lucide.createIcons();
    function validatePhone(input) {
        if (/[^0-9]/.test(input.value)) {
            input.value = input.value.replace(/[^0-9]/g, '');
            const toast = document.getElementById('phoneToast');
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 2000);
        }
    }
    document.getElementById('updateForm').onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const res = await fetch('update_profile_process.php', { method: 'POST', body: formData });
        if(res.ok) {
            document.getElementById('toast').classList.remove('hidden');
            setTimeout(() => location.reload(), 1500);
        }
    };
</script>
</body>
</html>