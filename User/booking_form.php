<?php
// File: user/booking_form.php
session_start();
$page_title = "Infrastructure Scheduling Matrix";
include("../includes/user_header.php");
include("../includes/user_navbar.php");
require_once("../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/user_login.php");
    exit();
}

$venue_id = isset($_GET["venue_id"]) ? (int)$_GET["venue_id"] : 0;

$sql = "SELECT venue_id, venue_name, category, capacity, base_deposit, status
        FROM venues
        WHERE venue_id = ? AND status = 'Available'
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $venue_id);
$stmt->execute();
$result = $stmt->get_result();
$venue = $result->fetch_assoc();

if (!$venue) {
    die("<div class='min-h-screen flex items-center justify-center bg-slate-50 text-xl font-bold text-slate-800'>Anomaly Detected: Node offline or non-existent.</div>");
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b' } } } } }
</script>

<div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden sticky top-8">
                <div class="bg-slate-900 px-6 py-5 border-b-4 border-indigo-500">
                    <h2 class="text-xl font-extrabold text-white tracking-wide">Target Node</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Designation</p>
                        <p class="text-lg font-bold text-slate-800"><?php echo htmlspecialchars($venue["venue_name"]); ?></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Classification</p>
                        <p class="text-sm font-medium text-slate-700"><?php echo htmlspecialchars($venue["category"]); ?></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Capacity Limit</p>
                        <p class="text-sm font-medium text-slate-700"><?php echo (int)$venue["capacity"]; ?> Pax</p>
                    </div>
                    <div class="pt-4 border-t border-slate-100">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Financial Requirement</p>
                        <p class="text-2xl font-black text-emerald-600 font-mono">RM <?php echo number_format((float)$venue["base_deposit"], 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden p-8">
                
                <div id="calendar-container" class="block">
                    <div class="flex justify-between items-center mb-6">
                        <h3 id="calendar-header" class="text-2xl font-extrabold text-slate-800"></h3>
                        <div class="flex space-x-2">
                            <button id="btn-prev-month" class="p-2 rounded hover:bg-slate-100 text-slate-500 transition disabled:opacity-30 disabled:cursor-not-allowed">
                                <i data-lucide="chevron-left" class="w-5 h-5"></i>
                            </button>
                            <button id="btn-next-month" class="p-2 rounded hover:bg-slate-100 text-slate-500 transition">
                                <i data-lucide="chevron-right" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-7 gap-2 mb-2 text-center text-xs font-bold text-slate-400 uppercase tracking-widest">
                        <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                    </div>
                    
                    <div id="calendar-grid" class="grid grid-cols-7 gap-2 text-sm font-medium"></div>
                </div>

                <div id="timeslot-container" class="hidden">
                    <div class="flex items-center mb-6">
                        <button onclick="returnToCalendar()" class="mr-4 p-2 rounded hover:bg-slate-100 text-slate-500 transition">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </button>
                        <h3 class="text-xl font-extrabold text-slate-800">Configure Timeslot: <span id="selected-date-display" class="text-indigo-600"></span></h3>
                    </div>

                    <div class="flex space-x-4 mb-6 text-xs font-bold text-slate-500 uppercase">
                        <div class="flex items-center"><div class="w-3 h-3 bg-white border-2 border-slate-200 rounded mr-2"></div> Available</div>
                        <div class="flex items-center"><div class="w-3 h-3 bg-slate-200 rounded mr-2"></div> Blocked (Inc. Buffer)</div>
                        <div class="flex items-center"><div class="w-3 h-3 bg-indigo-600 rounded mr-2"></div> Selected Vector</div>
                    </div>

                    <div id="time-grid" class="grid grid-cols-4 sm:grid-cols-6 gap-2 mb-8 max-h-64 overflow-y-auto p-2 border border-slate-100 rounded-xl bg-slate-50"></div>

                    <form id="asyncBookingForm" class="space-y-6 pt-6 border-t border-slate-100 hidden">
                        <input type="hidden" name="venue_id" id="payload_venue_id" value="<?php echo (int)$venue["venue_id"]; ?>">
                        <input type="hidden" name="booking_date" id="payload_date" value="">
                        <input type="hidden" name="start_time" id="payload_start" value="">
                        <input type="hidden" name="end_time" id="payload_end" value="">

                        <div class="bg-indigo-50 p-4 rounded-lg flex justify-between items-center border border-indigo-100">
                            <span class="text-sm font-bold text-indigo-800 uppercase tracking-wider">Selected Temporal Vector</span>
                            <span id="vector-display" class="font-mono font-black text-indigo-600 text-lg">--:-- to --:--</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Operational Purpose</label>
                            <input type="text" name="purpose" placeholder="Define the primary objective..." required 
                                   class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-sm bg-white">
                        </div>

                        <button type="submit" id="submitBtn" class="w-full py-4 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-md flex items-center justify-center">
                            <i data-lucide="shield-check" class="w-4 h-4 mr-2"></i> Lock Vector & Proceed to Checkout
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../includes/user_footer.php"); ?>

<script>
    lucide.createIcons();

    // ==========================================
    // System State Variables
    // ==========================================
    const venueId = document.getElementById('payload_venue_id').value;
    const today = new Date();
    let currentMonth = today.getMonth();
    let currentYear = today.getFullYear();
    
    let selectedDateStr = "";
    let selectionState = { start: null, end: null };
    let blockedVectors = []; // Fetched from API

    // ==========================================
    // Stage 1: Calendar Matrix Generator
    // ==========================================
    function renderCalendar() {
        const header = document.getElementById('calendar-header');
        const grid = document.getElementById('calendar-grid');
        const btnPrev = document.getElementById('btn-prev-month');

        // Temporal boundary constraint: Cannot navigate prior to current month
        if (currentYear === today.getFullYear() && currentMonth === today.getMonth()) {
            btnPrev.disabled = true;
        } else {
            btnPrev.disabled = false;
        }

        const date = new Date(currentYear, currentMonth, 1);
        header.innerText = date.toLocaleString('default', { month: 'long', year: 'numeric' });
        
        grid.innerHTML = '';
        
        const firstDayIndex = date.getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

        // Inject empty offset blocks
        for (let i = 0; i < firstDayIndex; i++) {
            grid.innerHTML += `<div class="p-4"></div>`;
        }

        // Inject computable days
        for (let i = 1; i <= daysInMonth; i++) {
            const checkDate = new Date(currentYear, currentMonth, i);
            const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
            
            // Disable past days
            if (checkDate.setHours(0,0,0,0) < today.setHours(0,0,0,0)) {
                grid.innerHTML += `<div class="p-3 text-slate-300 bg-slate-50 rounded-lg cursor-not-allowed flex justify-center items-center">${i}</div>`;
            } else {
                grid.innerHTML += `<button onclick="initiateDaySelect('${dateStr}')" class="p-3 bg-white border-2 border-transparent hover:border-indigo-500 hover:text-indigo-600 rounded-lg transition font-bold text-slate-700 flex justify-center items-center shadow-sm">${i}</button>`;
            }
        }
    }

    document.getElementById('btn-prev-month').addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 0) { currentMonth = 11; currentYear--; }
        renderCalendar();
    });

    document.getElementById('btn-next-month').addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 11) { currentMonth = 0; currentYear++; }
        renderCalendar();
    });

    // (保留上方原有的 lucide 宣告與渲染日曆的 renderCalendar 函數)

    // ==========================================
    // Stage 2: Temporal Grid & API Integration
    // ==========================================
    function initiateDaySelect(dateStr) {
        selectedDateStr = dateStr;
        document.getElementById('selected-date-display').innerText = dateStr;
        document.getElementById('payload_date').value = dateStr;
        
        document.getElementById('calendar-container').classList.add('hidden');
        document.getElementById('timeslot-container').classList.remove('hidden');
        
        selectionState = { start: null, end: null };
        document.getElementById('asyncBookingForm').classList.add('hidden');

        fetch(`../actions/api_fetch_slots.php?venue_id=${venueId}&date=${dateStr}`)
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    blockedVectors = data.blocked_vectors;
                    renderTimeGrid();
                } else {
                    alert("Anomaly fetching temporal data.");
                }
            });
    }

    function returnToCalendar() {
        document.getElementById('calendar-container').classList.remove('hidden');
        document.getElementById('timeslot-container').classList.add('hidden');
    }

    // Mathematically generate 48 intervals (30 min blocks) with Real-Time Validation
    function renderTimeGrid() {
        const timeGrid = document.getElementById('time-grid');
        timeGrid.innerHTML = '';

        // 💡 CRITICAL FIX: Instantiate a dynamic temporal node.
        // Prevents the global 'today' variable from suffering State Stagnation.
        const dynamicNow = new Date(); 
        
        // Strict String Parsing for Date Comparison
        const currentYearStr = dynamicNow.getFullYear();
        const currentMonthStr = String(dynamicNow.getMonth() + 1).padStart(2, '0');
        const currentDayStr = String(dynamicNow.getDate()).padStart(2, '0');
        const dynamicTodayStr = `${currentYearStr}-${currentMonthStr}-${currentDayStr}`;
        
        const isToday = (selectedDateStr === dynamicTodayStr);
        
        // Real-time HH:MM extraction
        const currentHour = dynamicNow.getHours();
        const currentMinute = dynamicNow.getMinutes();
        const currentTimeStr = `${String(currentHour).padStart(2, '0')}:${String(currentMinute).padStart(2, '0')}`;

        for (let h = 0; h < 24; h++) {
            for (let m = 0; m < 60; m += 30) {
                const timeStr = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                
                let isBlocked = false;

                // Temporal validation: Lock past vectors strictly on the current active day
                if (isToday && timeStr <= currentTimeStr) {
                    isBlocked = true;
                }

                // Check against API blocked vectors (Existing bookings + Buffer)
                for (let block of blockedVectors) {
                    if (timeStr >= block.start && timeStr < block.end) {
                        isBlocked = true;
                        break;
                    }
                }

                if (isBlocked) {
                    timeGrid.innerHTML += `<div class="p-2 text-center text-xs font-mono font-bold bg-slate-200 text-slate-400 rounded cursor-not-allowed">${timeStr}</div>`;
                } else {
                    timeGrid.innerHTML += `<button type="button" onclick="handleSlotClick('${timeStr}', this)" class="time-slot-btn p-2 text-center text-xs font-mono font-bold bg-white border-2 border-slate-200 hover:border-indigo-400 text-slate-600 rounded transition">${timeStr}</button>`;
                }
            }
        }
    }

    // 💡 UX Fix: 智慧型動態狀態機 (Intelligent State Machine)
    function handleSlotClick(timeStr, btnElement) {
        // Case A: 尚未選擇起點，或是使用者已經選完一組後，重新點擊來重置
        if (!selectionState.start || (selectionState.start && selectionState.end)) {
            setStartSlot(timeStr, btnElement);
        } 
        // Case B: 已選擇起點，正在等待選擇終點
        else {
            // 操作 1: 點擊同一個按鈕兩次 $\Rightarrow$ 取消選擇
            if (timeStr === selectionState.start) {
                selectionState.start = null;
                selectionState.end = null;
                resetSlotStyles();
                document.getElementById('asyncBookingForm').classList.add('hidden');
                return;
            }

            // 💡 操作 2: 點擊的時間「早於」起點 $\Rightarrow$ 智慧重新賦予起點
            if (timeStr < selectionState.start) {
                setStartSlot(timeStr, btnElement);
                return;
            }

            // 操作 3: 點擊的時間「晚於」起點 $\Rightarrow$ 驗證這段區間是否有被 Blocked 的時段
            let rangeValid = true;
            for (let block of blockedVectors) {
                if ((block.start >= selectionState.start && block.start < timeStr) || 
                    (block.end > selectionState.start && block.end <= timeStr)) {
                    rangeValid = false;
                    break;
                }
            }

            if (!rangeValid) {
                alert("Temporal Conflict: The selected range intersects with a blocked or buffered slot.");
                return;
            }

            // 成功：鎖定選取範圍
            selectionState.end = timeStr;
            paintSelectedRange();
            
            document.getElementById('payload_start').value = selectionState.start;
            document.getElementById('payload_end').value = selectionState.end;
            document.getElementById('vector-display').innerText = `${selectionState.start} to ${selectionState.end}`;
            
            document.getElementById('asyncBookingForm').classList.remove('hidden');
        }
    }

    // 輔助函數：設定起點狀態
    function setStartSlot(timeStr, btnElement) {
        selectionState.start = timeStr;
        selectionState.end = null;
        resetSlotStyles();
        btnElement.classList.replace('bg-white', 'bg-indigo-600');
        btnElement.classList.replace('text-slate-600', 'text-white');
        btnElement.classList.replace('border-slate-200', 'border-indigo-600');
        document.getElementById('asyncBookingForm').classList.add('hidden');
    }

    function resetSlotStyles() {
        document.querySelectorAll('.time-slot-btn').forEach(btn => {
            btn.className = "time-slot-btn p-2 text-center text-xs font-mono font-bold bg-white border-2 border-slate-200 hover:border-indigo-400 text-slate-600 rounded transition";
        });
    }

    function paintSelectedRange() {
        document.querySelectorAll('.time-slot-btn').forEach(btn => {
            const slotTime = btn.innerText;
            if (slotTime >= selectionState.start && slotTime <= selectionState.end) {
                btn.classList.replace('bg-white', 'bg-indigo-600');
                btn.classList.replace('text-slate-600', 'text-white');
                btn.classList.replace('border-slate-200', 'border-indigo-600');
            }
        });
    }

    // ==========================================
    // Stage 3: Asynchronous Payload Submission
    // ==========================================
    document.getElementById('asyncBookingForm').addEventListener('submit', function(e) {
        e.preventDefault(); 
        
        const submitBtn = document.getElementById('submitBtn');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 mr-2 animate-spin"></i> Processing...';
        submitBtn.disabled = true;
        submitBtn.classList.replace('bg-indigo-600', 'bg-slate-400');
        lucide.createIcons();

        const formData = new FormData(this);
        formData.append('is_ajax', 'true'); // 💡 CRITICAL FIX: Explicit flag to force Backend to return JSON

        fetch('../actions/process_booking.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error('HTTP Fault.');
            return response.json(); // Now perfectly safe since Backend guarantees JSON
        })
        .then(data => {
            if (data.status === 'success') {
                window.location.href = data.redirect_url;
            } else {
                alert("Execution Failed: " + (data.message || "Unknown anomaly."));
                resetFormButton(submitBtn, originalBtnText);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("Fatal Network Anomaly: Backend response violation.");
            resetFormButton(submitBtn, originalBtnText);
        });
    });

    function resetFormButton(btn, originalText) {
        btn.innerHTML = originalText;
        btn.disabled = false;
        btn.classList.replace('bg-slate-400', 'bg-indigo-600');
        lucide.createIcons();
    }

    renderCalendar();
</script>