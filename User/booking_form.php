<?php
// File: user/booking_form.php
session_start();
$page_title = "Book Venue";
include("../includes/user_header.php");
include("../includes/user_navbar.php");
require_once("../config/db.php");

if (!isset($_SESSION['uid'])) {
    header("Location: ../user/user_login.php?error=access_denied");
    exit();
}

$vid = intval($_GET["vid"] ?? 0);

if ($vid === 0) {
    die("<div class='min-h-screen flex items-center justify-center bg-slate-50 text-xl font-bold text-slate-800'>Anomaly Detected: Invalid Venue Identifier.</div>");
}

$sql = "SELECT vid, vname, category, max_cap, deposit, status
        FROM venue
        WHERE vid = ? AND status = 'available'
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $vid);
$stmt->execute();
$result = $stmt->get_result();
$venue = $result->fetch_assoc();

if (!$venue) {
    die("<div class='min-h-screen flex items-center justify-center bg-slate-50 text-xl font-bold text-slate-800'>Error: Venue is offline or not available for booking.</div>");
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
                <div class="bg-slate-800 px-6 py-5 border-b-4 border-indigo-500 flex items-center">
                    <i data-lucide="map-pin" class="w-5 h-5 text-indigo-400 mr-2"></i>
                    <h2 class="text-lg font-extrabold text-white tracking-wide">Selected Venue</h2> 
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Name</p>
                        <p class="text-lg font-bold text-slate-800"><?php echo htmlspecialchars($venue["vname"]); ?></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Category</p>
                        <p class="text-sm font-medium text-slate-700"><?php echo htmlspecialchars($venue["category"]); ?></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Capacity</p>
                        <p class="text-sm font-medium text-slate-700"><?php echo (int)$venue["max_cap"]; ?> Pax</p>
                    </div>
                    <div class="pt-4 border-t border-slate-100">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Deposit Required</p>
                        <p class="text-2xl font-black text-emerald-600 font-mono">RM <?php echo number_format((float)$venue["deposit"], 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden p-8">
                
                <div id="calendar-container" class="block">
                    <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4">
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
                    <div class="flex items-center mb-6 border-b border-slate-100 pb-4">
                        <button onclick="returnToCalendar()" class="mr-4 p-2 rounded hover:bg-slate-100 text-slate-500 transition">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </button>
                        <h3 class="text-xl font-extrabold text-slate-800">Select Time: <span id="selected-date-display" class="text-indigo-600"></span></h3>
                    </div>

                    <div class="flex flex-wrap gap-4 mb-6 text-xs font-bold text-slate-500 uppercase">
                        <div class="flex items-center"><div class="w-3 h-3 bg-white border-2 border-slate-200 rounded mr-2"></div> Available</div>
                        <div class="flex items-center"><div class="w-3 h-3 bg-slate-200 rounded mr-2"></div> Unavailable</div>
                        <div class="flex items-center"><div class="w-3 h-3 bg-indigo-600 rounded mr-2"></div> Selected</div>
                    </div>

                    <div id="time-grid" class="grid grid-cols-4 sm:grid-cols-6 gap-2 mb-8 max-h-64 overflow-y-auto p-2 border border-slate-100 rounded-xl bg-slate-50"></div>

                    <form id="asyncBookingForm" class="space-y-6 pt-6 border-t border-slate-100 hidden">
                        <input type="hidden" name="venue_id" id="payload_venue_id" value="<?php echo htmlspecialchars($venue["vid"]); ?>">
                        <input type="hidden" name="booking_date" id="payload_date" value="">
                        <input type="hidden" name="start_time" id="payload_start" value="">
                        <input type="hidden" name="end_time" id="payload_end" value="">

                        <div class="bg-indigo-50 p-4 rounded-lg flex justify-between items-center border border-indigo-100">
                            <span class="text-sm font-bold text-indigo-800 uppercase tracking-wider">Temporal Vector</span>
                            <span id="vector-display" class="font-mono font-black text-indigo-600 text-lg">--:-- to --:--</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Booking Purpose</label>
                            <input type="text" name="purpose" placeholder="e.g., Project Meeting..." required 
                                   class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-sm bg-white">
                        </div>

                        <button type="submit" id="submitBtn" class="w-full py-4 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-md flex items-center justify-center">
                            <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i> Confirm Configuration & Proceed
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

    const venueId = document.getElementById('payload_venue_id').value;
    const today = new Date();
    let currentMonth = today.getMonth();
    let currentYear = today.getFullYear();
    
    let selectedDateStr = "";
    let selectionState = { start: null, end: null };
    let blockedVectors = []; 

    // 💡 時間數學運算元 (確保 $t_2$ 能正確 +30 mins 閉環)
    function addMinutes(timeStr, mins) {
        let [h, m] = timeStr.split(':').map(Number);
        let d = new Date();
        d.setHours(h, m + mins, 0, 0);
        return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    function renderCalendar() {
        const header = document.getElementById('calendar-header');
        const grid = document.getElementById('calendar-grid');
        const btnPrev = document.getElementById('btn-prev-month');

        btnPrev.disabled = (currentYear === today.getFullYear() && currentMonth === today.getMonth());
        const date = new Date(currentYear, currentMonth, 1);
        header.innerText = date.toLocaleString('default', { month: 'long', year: 'numeric' });
        grid.innerHTML = '';
        
        const firstDayIndex = date.getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

        for (let i = 0; i < firstDayIndex; i++) grid.innerHTML += `<div class="p-4"></div>`;

        for (let i = 1; i <= daysInMonth; i++) {
            const checkDate = new Date(currentYear, currentMonth, i);
            const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
            
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
                    alert("System Fault: Unable to synchronize schedule matrices.");
                }
            });
    }

    function returnToCalendar() {
        document.getElementById('calendar-container').classList.remove('hidden');
        document.getElementById('timeslot-container').classList.add('hidden');
    }

    // 💡 替換：套用 Model α 延遲鎖定演算法
    function renderTimeGrid() {
        const timeGrid = document.getElementById('time-grid');
        timeGrid.innerHTML = '';

        const dynamicNow = new Date(); 
        const currentYearStr = dynamicNow.getFullYear();
        const currentMonthStr = String(dynamicNow.getMonth() + 1).padStart(2, '0');
        const currentDayStr = String(dynamicNow.getDate()).padStart(2, '0');
        const dynamicTodayStr = `${currentYearStr}-${currentMonthStr}-${currentDayStr}`;
        
        const isToday = (selectedDateStr === dynamicTodayStr);
        const currentTimeStr = `${String(dynamicNow.getHours()).padStart(2, '0')}:${String(dynamicNow.getMinutes()).padStart(2, '0')}`;

        // 💡 定義容差常數 k = 25 分鐘
        const k = 25;

        for (let h = 0; h < 24; h++) {
            for (let m = 0; m < 60; m += 30) {
                const timeStr = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                
                // 💡 計算鎖定閥值：t_lock = t_slot_start + k
                const lockTimeStr = addMinutes(timeStr, k);
                
                // 狀態評估：只有當前時間超越鎖定閥值時，才判定為 isBlocked = true
                let isBlocked = (isToday && currentTimeStr >= lockTimeStr);

                // 疊加伺服器端傳回的已佔用時間向量
                if (!isBlocked) {
                    for (let block of blockedVectors) {
                        if (timeStr >= block.start && timeStr < block.end) {
                            isBlocked = true;
                            break;
                        }
                    }
                }

                // 矩陣節點渲染
                if (isBlocked) {
                    timeGrid.innerHTML += `<div class="p-2 text-center text-xs font-mono font-bold bg-slate-200 text-slate-400 rounded cursor-not-allowed">${timeStr}</div>`;
                } else {
                    timeGrid.innerHTML += `<button type="button" onclick="handleSlotClick('${timeStr}', this)" class="time-slot-btn p-2 text-center text-xs font-mono font-bold bg-white border-2 border-slate-200 hover:border-indigo-400 text-slate-600 rounded transition">${timeStr}</button>`;
                }
            }
        }
    }

    // 💡 矩陣選擇邏輯重構
    function handleSlotClick(timeStr, btnElement) {
        if (!selectionState.start) {
            setStartSlot(timeStr, btnElement);
        } else if (!selectionState.end) {
            if (timeStr === selectionState.start) {
                selectionState.start = null;
                resetSlotStyles();
                document.getElementById('asyncBookingForm').classList.add('hidden');
                return;
            }
            if (timeStr < selectionState.start) {
                setStartSlot(timeStr, btnElement);
                return;
            }

            // 校驗區間衝突
            let rangeValid = true;
            for (let block of blockedVectors) {
                if (block.start >= selectionState.start && block.start <= timeStr) {
                    rangeValid = false;
                    break;
                }
            }

            if (!rangeValid) {
                alert("Vector Conflict: Selected temporal range intersects with locked slots.");
                return;
            }

            selectionState.end = timeStr;
            finalizeSelection();
        } else {
            setStartSlot(timeStr, btnElement);
        }
    }

    function setStartSlot(timeStr, btnElement) {
        selectionState.start = timeStr;
        selectionState.end = null;
        resetSlotStyles();
        btnElement.classList.replace('bg-white', 'bg-indigo-600');
        btnElement.classList.replace('text-slate-600', 'text-white');
        btnElement.classList.replace('border-slate-200', 'border-indigo-600');
        finalizeSelection(true); // 預設單一時塊 (30 mins)
    }

    function resetSlotStyles() {
        document.querySelectorAll('.time-slot-btn').forEach(btn => {
            btn.className = "time-slot-btn p-2 text-center text-xs font-mono font-bold bg-white border-2 border-slate-200 hover:border-indigo-400 text-slate-600 rounded transition";
        });
    }

    function finalizeSelection(isSingle = false) {
        let actualStart = selectionState.start;
        // 💡 將介面點擊的最後一個時塊加上 30 分鐘，形成數學上的真實驗證端點
        let actualEnd = isSingle ? addMinutes(selectionState.start, 30) : addMinutes(selectionState.end, 30);

        document.querySelectorAll('.time-slot-btn').forEach(btn => {
            const slotTime = btn.innerText;
            if (slotTime >= selectionState.start && slotTime <= (selectionState.end || selectionState.start)) {
                btn.classList.replace('bg-white', 'bg-indigo-600');
                btn.classList.replace('text-slate-600', 'text-white');
                btn.classList.replace('border-slate-200', 'border-indigo-600');
            }
        });

        document.getElementById('payload_start').value = actualStart;
        document.getElementById('payload_end').value = actualEnd;
        document.getElementById('vector-display').innerText = `${actualStart} to ${actualEnd}`;
        document.getElementById('asyncBookingForm').classList.remove('hidden');
    }

    document.getElementById('asyncBookingForm').addEventListener('submit', function(e) {
        e.preventDefault(); 
        
        const submitBtn = document.getElementById('submitBtn');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 mr-2 animate-spin"></i> Processing Matrix...';
        submitBtn.disabled = true;
        submitBtn.classList.replace('bg-indigo-600', 'bg-slate-400');
        lucide.createIcons();

        // 💡 這裡會自動擷取表單內的所有隱藏 input (包含已更新的 payload_end)
        const formData = new FormData(this);

        fetch('../actions/process_booking.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error('Network Protocol Error.');
            return response.json(); 
        })
        .then(data => {
            if (data.status === 'success') {
                window.location.href = data.redirect_url;
            } else {
                alert("Execution Halted: " + (data.message || "Unknown anomaly."));
                resetFormButton(submitBtn, originalBtnText);
            }
        })
        .catch(error => {
            console.error('Core Diagnostics:', error);
            // 若仍報錯，必定是後端 PHP 在解析時發生 Fatal Error
            alert("Connection Error: Server returned an invalid execution payload. Please check backend logs.");
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