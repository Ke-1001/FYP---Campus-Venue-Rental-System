<?php
session_start();
require_once '../config/db.php';

// 提取核心数据
$sql = "SELECT vid, vname, category, description, pic FROM venue LIMIT 3";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CVBMS | Campus Venue Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        mmu: { 
                            core: '#004aad',   // 品牌深蓝 (Mass Color)
                            glow: '#3b82f6'    // 发光亮蓝 (Emissive Color)
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap');
        html { scroll-behavior: smooth; }
        
        /* 核心毛玻璃面板 */
        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        /* 导航栏专属的轻量毛玻璃 */
        .glass-nav {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* 输入框交互态 */
        .input-glass {
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .input-glass:focus {
            border-color: #3b82f6; 
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25);
            outline: none;
        }

        /* 卡片悬停微交互 */
        .hover-lift { transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.4s ease, border-color 0.4s ease; }
        .hover-lift:hover { 
            transform: translateY(-6px); 
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.7);
            border-color: rgba(59, 130, 246, 0.5); /* 悬停时边框泛蓝光 */
        }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.3); }
    </style>
</head>
<body class="font-sans antialiased selection:bg-mmu-glow selection:text-white min-h-screen relative overflow-x-hidden text-slate-200">

<!-- 沉浸式固定背景 (Fixed Immersive Background) -->
<div class="fixed inset-0 z-0">
    <!-- 统一使用校园暗调背景图 -->
    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80" alt="Campus" class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-slate-900/85 mix-blend-multiply"></div>
    <!-- 注入微妙的光晕渲染引擎 -->
    <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-mmu-core/20 rounded-full filter blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-0 right-1/4 w-[400px] h-[400px] bg-mmu-glow/10 rounded-full filter blur-[100px] pointer-events-none"></div>
</div>

<!-- 导航栏 (Dark Glass Navigation) -->
<nav class="glass-nav fixed w-full z-50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            
            <div class="flex-shrink-0 flex items-center gap-2 cursor-pointer">
                <div class="w-8 h-8 bg-mmu-core rounded-lg flex items-center justify-center text-white font-bold shadow-lg">C</div>
                <span class="font-extrabold text-xl tracking-tight text-white">CVBMS</span>
            </div>

            <div class="hidden md:flex space-x-8">
                <a href="homepage.php" class="text-white font-semibold px-2 py-2 text-lg drop-shadow-[0_0_8px_rgba(255,255,255,0.5)]">Home</a>
                <a href="venues.php" class="text-slate-400 hover:text-white transition-colors px-2 py-2 text-lg font-medium">Venues</a>
                <a href="my_bookings.php" class="text-slate-400 hover:text-white transition-colors px-2 py-2 text-lg font-medium">My Bookings</a>
            </div>

            <div class="flex items-center gap-4">
                <?php if (isset($_SESSION['uid'])): ?>
                    <div id="userBtn" class="flex items-center gap-2 bg-white/10 py-1.5 px-4 rounded-full border border-white/10 backdrop-blur-md cursor-pointer">
                        <i data-lucide="user" class="w-4 h-4 text-mmu-glow"></i>
                            <span class="text-sm font-semibold text-white max-w-[100px] truncate">
                                <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>

                        <!-- 下拉资料卡 -->
                        <div id="userDropdown" class="hidden absolute top-10 right-30 mt-2 w-72 bg-white rounded-xl shadow-lg p-5 text-black z-50">
                            <p class="font-medium mb-2">
                                Username: <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?><br>
                                Student ID: <?php echo htmlspecialchars($_SESSION['uid'], ENT_QUOTES, 'UTF-8'); ?><br>
                                Email: <?php echo htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8'); ?><br>
                                Phone: <?php echo htmlspecialchars($_SESSION['phone_num'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                    </div>
                    <a href="profile.php" class="text-slate-400 hover:text-white transition-colors"><i data-lucide="settings" class="w-5 h-5"></i></a>
                    <a href="../User/user_logout.php" class="text-red-400 hover:text-red-300 transition-colors"><i data-lucide="log-out" class="w-5 h-5"></i></a>
                <?php else: ?>
                    <a href="../user/user_login.php" class="text-slate-300 hover:text-white font-semibold text-sm transition-colors">Login</a>
                    <a href="../user/user_register.php" class="bg-mmu-core hover:bg-blue-800 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-[0_4px_14px_0_rgba(0,74,173,0.39)] transition-all">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- 主内容滚动视口 -->
<div class="relative z-10 w-full h-full overflow-y-auto pt-32 pb-20">
    
    <!-- 英雄搜索区 -->
    <div class="max-w-4xl mx-auto text-center px-4 mb-24">
        <h1 class="text-5xl md:text-6xl font-extrabold text-white tracking-tight mb-6 drop-shadow-md">
            Find & Book Your <br class="md:hidden"><span class="text-mmu-glow drop-shadow-[0_0_20px_rgba(59,130,246,0.5)]">Ideal Space.</span>
        </h1>
        <p class="text-slate-300 text-base md:text-lg mb-12 max-w-2xl mx-auto font-medium">
            Reserve discussion rooms, halls, and labs across the MMU campus in seconds. Simple, fast, and guaranteed.
        </p>

        <!-- 中心聚光灯搜索栏 -->
        <form action="venues.php" method="GET" class="max-w-2xl mx-auto relative group mb-10">
            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                <i data-lucide="search" class="w-5 h-5 text-slate-400"></i>
            </div>
            <input type="text" name="search" placeholder="Search for a room, hall, or facility..." 
                   class="input-glass w-full block pl-12 pr-36 py-4 rounded-2xl text-slate-900 placeholder-slate-400 font-semibold shadow-2xl text-lg">
            <button type="submit" class="absolute inset-y-2 right-2 px-8 bg-mmu-core hover:bg-blue-800 text-white font-bold rounded-xl shadow-[0_4px_14px_0_rgba(0,74,173,0.39)] transition-all text-sm flex items-center gap-2 group-hover:shadow-[0_6px_20px_rgba(0,74,173,0.23)]">
                Explore <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
        </form>

        <!-- 发光色信任带 -->
        <div class="flex flex-wrap justify-center items-center gap-x-8 gap-y-4 text-slate-300 text-sm font-semibold">
            <div class="flex items-center gap-2"><i data-lucide="zap" class="w-4 h-4 text-mmu-glow drop-shadow-[0_0_8px_rgba(59,130,246,0.8)]"></i> Instant Confirmation</div>
            <div class="hidden sm:block w-1.5 h-1.5 rounded-full bg-slate-600"></div>
            <div class="flex items-center gap-2"><i data-lucide="layers" class="w-4 h-4 text-mmu-glow drop-shadow-[0_0_8px_rgba(59,130,246,0.8)]"></i> Various Room Types</div>
            <div class="hidden sm:block w-1.5 h-1.5 rounded-full bg-slate-600"></div>
            <div class="flex items-center gap-2"><i data-lucide="shield-check" class="w-4 h-4 text-mmu-glow drop-shadow-[0_0_8px_rgba(59,130,246,0.8)]"></i> Guaranteed Availability</div>
        </div>
    </div>

    <!-- 场馆卡片矩阵 (Glassmorphism Cards) -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-end mb-8 pl-4 border-l-4 border-mmu-glow">
            <div>
                <h2 class="text-3xl font-extrabold text-white tracking-tight">Popular Venues</h2>
                <p class="text-slate-400 text-sm mt-1">Frequently booked spaces across the campus.</p>
            </div>
            <a href="venues.php" class="text-mmu-glow font-bold hover:text-white transition-colors flex items-center gap-1">
                View Directory <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <!-- 毛玻璃卡片实体 -->
                    <div class="glass-panel rounded-3xl overflow-hidden hover-lift flex flex-col group">
                        
                        <!-- 图像占位区 (带内部暗角渐变以融合边界) -->
                        <div class="h-56 bg-black/40 relative overflow-hidden flex items-center justify-center">
                            <?php if(!empty($row['pic'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($row['pic']); ?>" alt="Venue" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 group-hover:scale-105 transition-all duration-700">
                            <?php else: ?>
                                <i data-lucide="image" class="w-12 h-12 text-slate-600"></i>
                            <?php endif; ?>
                            <!-- 底部渐变蒙版，让图片无缝融入卡片 -->
                            <div class="absolute inset-0 bg-gradient-to-t from-[#131d30] to-transparent"></div>
                            
                            <div class="absolute top-4 left-4 bg-black/50 backdrop-blur-md border border-white/10 text-[11px] font-bold px-3 py-1.5 rounded-lg text-white uppercase tracking-wider shadow-lg">
                                <?php echo htmlspecialchars($row['category'] ?? 'Standard'); ?>
                            </div>
                        </div>
                        
                        <div class="p-8 flex-1 flex flex-col relative z-10 -mt-6">
                            <h3 class="text-2xl font-bold text-white mb-3 truncate group-hover:text-mmu-glow transition-colors drop-shadow-md">
                                <?php echo htmlspecialchars($row['vname']); ?>
                            </h3>
                            <p class="text-slate-300 text-sm mb-8 flex-1 line-clamp-2 leading-relaxed">
                                <?php echo htmlspecialchars($row['description'] ?? 'No topological description provided for this structural asset.'); ?>
                            </p>
                            
                            <a href="venue_details.php?id=<?php echo $row['vid']; ?>" 
                               class="block w-full text-center py-3.5 bg-white/10 hover:bg-mmu-core text-white text-sm font-bold rounded-xl border border-white/10 hover:border-mmu-core transition-all shadow-lg hover:shadow-[0_4px_20px_rgba(0,74,173,0.5)]">
                                Inspect Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-3 glass-panel rounded-3xl p-16 text-center">
                    <i data-lucide="database" class="w-16 h-16 text-slate-500 mx-auto mb-4"></i>
                    <h3 class="text-xl font-bold text-white mb-2">No Venues Found</h3>
                    <p class="text-slate-400">There are currently no structural assets listed in the database.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 悬浮页脚 (Floating Footer) -->
<footer class="relative z-10 border-t border-white/10 bg-black/40 backdrop-blur-md">
    <div class="max-w-7xl mx-auto px-4 py-8 text-center">
        <p class="text-slate-500 text-sm font-medium tracking-wide">
            &copy; <?php echo date("Y"); ?> MMU Campus Venue Booking System. Engineered for optimal resource allocation.
        </p>
    </div>
</footer>

<script>
    lucide.createIcons();

    const btn = document.getElementById("userBtn");
    const dropdown = document.getElementById("userDropdown");

    // 点击头像 → toggle
    btn.addEventListener("click", function (e) {
        e.stopPropagation();
        dropdown.classList.toggle("hidden");
    });

    // 点击页面其他地方 → 关闭
    document.addEventListener("click", function () {
        dropdown.classList.add("hidden");
    });
</script>

</body>
</html>