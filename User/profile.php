<?php
session_start();
require_once __DIR__ . '/../config/db.php';
if (!isset($_SESSION['uid'])) { header("Location: user_login.php"); exit(); }

$uid = $_SESSION['uid'];
$stmt = $conn->prepare("SELECT * FROM user WHERE uid = ?");
$stmt->bind_param("s", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

function resolve_user_profile_pic($stored_pic) {
    $stored_pic = trim((string)$stored_pic);
    if ($stored_pic === '') {
        return ['', ''];
    }

    $stored_pic = str_replace('\\', '/', $stored_pic);
    $stored_pic = ltrim($stored_pic, '/');

    $candidates = [];

    if (strpos($stored_pic, 'uploads/user/') === 0) {
        $relative = '../' . $stored_pic;
        $candidates[] = [$relative, __DIR__ . '/../' . $stored_pic];
    } elseif (strpos($stored_pic, 'uploads/') === 0) {
        $relative = '../' . $stored_pic;
        $candidates[] = [$relative, __DIR__ . '/../' . $stored_pic];
    } else {
        $candidates[] = ['../uploads/user/' . $stored_pic, __DIR__ . '/../uploads/user/' . $stored_pic];
        $candidates[] = ['../uploads/' . $stored_pic, __DIR__ . '/../uploads/' . $stored_pic];
    }

    foreach ($candidates as [$url, $path]) {
        if (file_exists($path)) {
            return [$url, $path];
        }
    }

    return ['', ''];
}

[$profile_pic_url, $profile_pic_path] = resolve_user_profile_pic($user['profile_pic'] ?? '');

$page_title = "My Profile | CVBMS";
require_once __DIR__ . '/../includes/user_header.php';
require_once __DIR__ . '/../includes/user_navbar.php';
?>

<div class="bg-[#f6f8fb] min-h-screen py-10 px-4">
    <div class="max-w-2xl mx-auto">
        <a href="homepage.php" class="inline-flex items-center gap-2 text-slate-500 hover:text-mmu-core mb-8 transition font-medium">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Homepage
        </a>

        <div class="bg-white border border-slate-200 rounded-3xl p-8 shadow-sm">
            <div class="flex flex-col items-center mb-10">
                <div class="w-24 h-24 rounded-full overflow-hidden mb-4 shadow-md border-4 border-white ring-1 ring-slate-200 bg-slate-100">
                    <?php if (!empty($profile_pic_url)): ?>
                        <img src="<?php echo htmlspecialchars($profile_pic_url, ENT_QUOTES, 'UTF-8'); ?>" class="w-full h-full object-cover" alt="Profile Picture">
                    <?php else: ?>
                        <div class="w-full h-full bg-mmu-core flex items-center justify-center text-3xl font-black text-white">
                            <?php echo htmlspecialchars(substr($user['username'] ?? 'U', 0, 1), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <h1 class="text-3xl font-extrabold text-slate-900"><?php echo htmlspecialchars($user['username']); ?></h1>
                <p class="text-mmu-core font-bold tracking-widest text-xs uppercase mt-1">Authorized Student Account</p>
            </div>

            <div id="profileView" class="space-y-6">
                <div class="grid grid-cols-2 gap-6">
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Student ID</p>
                        <p class="text-slate-900 font-semibold"><?php echo htmlspecialchars($user['uid']); ?></p>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Phone Number</p>
                        <p class="text-slate-900 font-semibold"><?php echo htmlspecialchars($user['phone_num']); ?></p>
                    </div>
                </div>
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Academic Email</p>
                    <p class="text-slate-900 font-semibold"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div class="space-y-3">
                    <button onclick="document.getElementById('profileView').classList.add('hidden'); document.getElementById('editView').classList.remove('hidden');" 
                            class="w-full bg-mmu-core text-white font-bold py-4 rounded-2xl hover:bg-blue-800 transition">
                        Edit Identity Details
                    </button>
                    <a href="change_password.php" 
                       class="w-full block text-center bg-white text-mmu-core border border-blue-100 font-bold py-4 rounded-2xl hover:bg-blue-50 transition">
                        Change Password
                    </a>
                </div>
            </div>

            <div id="editView" class="hidden space-y-4">
                <form id="updateForm" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] text-slate-500 uppercase font-bold">Full Name (Locked)</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" readonly class="w-full p-3 rounded-xl bg-slate-100 border border-slate-200 text-slate-500 input-locked mb-4">
                        </div>
                        <div>
                            <label class="text-[10px] text-slate-500 uppercase font-bold">Student ID (Locked)</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['uid']); ?>" readonly class="w-full p-3 rounded-xl bg-slate-100 border border-slate-200 text-slate-500 input-locked mb-4">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] text-slate-500 uppercase font-bold">Academic Email (Locked)</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['email']); ?>" readonly class="w-full p-3 rounded-xl bg-slate-100 border border-slate-200 text-slate-500 input-locked mb-4">
                    </div>
                    <div class="relative">
                        <label class="text-[10px] text-slate-500 uppercase font-bold">Phone Number</label>
                        <div id="phoneToast" class="hidden absolute -top-1 right-0 bg-red-600 text-white text-[12px] px-3 py-1 rounded-lg z-50">Only numbers allowed!</div>
                        <input type="tel" name="phone_num" value="<?php echo htmlspecialchars($user['phone_num']); ?>" oninput="validatePhone(this)" class="w-full p-3 rounded-xl bg-white text-slate-900 font-bold mb-4 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div class="mb-4">
                        <label class="text-[10px] text-slate-500 uppercase font-bold mb-1 block">Profile Picture</label>
                        <input type="file" id="fileInput" name="profile_pic" accept="image/*" class="hidden" onchange="document.getElementById('fileName').textContent = this.files.length ? this.files[0].name : 'Choose New Picture'">
                        <label for="fileInput" class="w-full block text-center bg-mmu-core text-white font-bold py-4 rounded-2xl hover:bg-blue-800 transition shadow-sm cursor-pointer">
                            <span id="fileName">Choose New Picture</span>
                        </label>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="location.reload()" class="flex-1 py-4 text-slate-500 font-bold hover:text-slate-900 transition">Cancel</button>
                        <button type="submit" class="flex-[2] bg-mmu-core text-white font-bold py-4 rounded-2xl hover:bg-blue-800 transition shadow-sm">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="toast" class="hidden fixed bottom-10 right-10 bg-emerald-600 text-white px-8 py-4 rounded-2xl shadow-2xl font-bold">Profile Updated!</div>
<script>
    if (window.lucide) lucide.createIcons();

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
        if (res.ok) {
            document.getElementById('toast').classList.remove('hidden');
            setTimeout(() => location.reload(), 1500);
        } else {
            alert('Profile update failed. Please try again.');
        }
    };
</script>

<?php include("../includes/user_footer.php"); ?>
