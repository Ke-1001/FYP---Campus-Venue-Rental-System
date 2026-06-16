<?php
// This section prepares the user booking form page.
session_start();
$page_title = "Book Venue";
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['uid']))
{
    header("Location: ../user/user_login.php?error=access_denied");
    exit();
}

require_once __DIR__ . '/../includes/user_header.php';
require_once __DIR__ . '/../includes/user_navbar.php';

$vid = $_GET['vid'] ?? '';

if (!preg_match('/^[A-Za-z0-9_-]+$/', $vid))
{
    die(
        "<div class='min-h-screen flex items-center justify-center bg-slate-50 text-xl font-bold text-red-600'>
            Invalid Venue ID: Cannot proceed with the booking. </div>" );
}

$sql = "SELECT v.vid, v.vname, vc.category, v.max_cap, v.deposit, v.status
        FROM venue v
        JOIN vcategory vc ON v.vcid = vc.vcid
        WHERE v.vid = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $vid);
$stmt->execute();
$result = $stmt->get_result();
$venue = $result->fetch_assoc();

if (!$venue)
{
    die("Venue not found.");
}

if (strtolower($venue['status']) !== 'available')
{
    $_SESSION['error'] = "This venue is currently not available for booking.";
    header("Location: venue_details.php?vid=" . urlencode($venue['vid']));
    exit;
}

if (!$venue)
{
    die("<div class='min-h-screen flex items-center justify-center bg-slate-50 text-xl font-bold text-slate-800'>Error: Venue is offline or not available for booking.</div>");
}
?>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    tailwind.config =
    { theme:
    { extend:
    { colors:
    { cstyle:
    { blue: '#004aad', dark: '#1e293b' } } } } }
</script>

<div class="min-h-screen bg-transparent py-12 px-4 sm:px-6 lg:px-8 font-sans">
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

                    <form id="asyncBookingForm" method="POST" action="../actions/process_booking.php" class="space-y-6 pt-6 border-t border-slate-100 hidden">
                        <input type="hidden" name="vid" id="payload_venue_id" value="<?php echo htmlspecialchars($venue["vid"]); ?>">
                        <input type="hidden" name="date_booked" id="payload_date" value="">
                        <input type="hidden" name="time_start" id="payload_start" value="">
                        <input type="hidden" name="time_end" id="payload_end" value="">

                        <div class="bg-indigo-50 p-4 rounded-lg flex justify-between items-center border border-indigo-100">
                            <span class="text-sm font-bold text-indigo-800 uppercase tracking-wider">Selected Time</span>
                            <span id="vector-display" class="font-mono font-black text-indigo-600 text-lg">--:-- to --:--</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Booking Purpose</label>
                            <input type="text" name="purpose" placeholder="e.g., Project Meeting..." required
                                   class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-sm bg-white">
                        </div>


                        <div class="mb-4 p-4 bg-slate-50 border border-slate-200 rounded-xl">
                            <div class="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    id="agreeRules"
                                    name="agree_rules"
                                    value="1"
                                    disabled
                                    class="mt-1 w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 cursor-not-allowed"
                                >

                                <div class="flex-1">
                                    <label for="agreeRules" class="text-sm font-semibold text-slate-700">
                                        I have read and agree to the
                                        <button
                                            type="button"
                                            onclick="openRulesModal()"
                                            class="text-indigo-600 hover:text-indigo-800 font-bold underline"
                                        >
                                            Rules and Regulations
                                        </button>
                                        before requesting this booking.
                                    </label>

                                    <p class="text-xs text-slate-400 mt-1">
                                        Please open and read the rules before ticking this agreement.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <button
                            type="submit"
                            id="requestBookingBtn"
                            disabled
                            class="w-full py-3 text-sm font-bold text-white bg-slate-400 rounded-lg cursor-not-allowed transition flex items-center justify-center"
                        >
                            Request Booking
                            <i data-lucide="send" class="w-4 h-4 ml-2"></i>
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>


<div
    id="rulesModal"
    class="fixed inset-0 bg-black/70 z-50 hidden items-center justify-center px-4"
>
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden">

        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <div>
                <h3 class="text-lg font-black text-slate-800">
                    Venue Booking Rules and Regulations
                </h3>
                <p class="text-xs text-slate-400 mt-1">
                    Please read carefully before requesting your booking.
                </p>
            </div>

            <button
                type="button"
                onclick="closeRulesModal()"
                class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center"
            >
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="p-6 max-h-[60vh] overflow-y-auto text-sm text-slate-600 leading-relaxed space-y-4">
            <div>
                <h4 class="font-bold text-slate-800 mb-1">1. Booking Responsibility</h4>
                <p>
                    The user is responsible for using the venue properly during the approved booking period.
                </p>
            </div>

            <div>
                <h4 class="font-bold text-slate-800 mb-1">2. Venue Condition</h4>
                <p>
                    The user should check the venue condition before use. Any existing damage should be reported before the activity starts.
                </p>
            </div>

            <div>
                <h4 class="font-bold text-slate-800 mb-1">3. Damage and Cleanliness</h4>
                <p>
                    The user may be held responsible for any damage, missing equipment, or serious cleanliness issue caused during the booking period.
                </p>
            </div>

            <div>
                <h4 class="font-bold text-slate-800 mb-1">4. Time Usage</h4>
                <p>
                    The venue must only be used within the approved booking time. The system may reserve additional time after the booking for inspection purposes.
                </p>
            </div>

            <div>
                <h4 class="font-bold text-slate-800 mb-1">5. Prohibited Activities</h4>
                <p>
                    Users are not allowed to conduct illegal, unsafe, or unauthorized activities inside the venue.
                </p>
            </div>

            <div>
                <h4 class="font-bold text-slate-800 mb-1">6. Booking Approval</h4>
                <p>
                    Submitting a booking request does not mean the booking is approved. The booking will only be confirmed after admin approval.
                </p>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-3">
            <button
                type="button"
                onclick="closeRulesModal()"
                class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-100"
            >
                Close
            </button>

            <button
                type="button"
                onclick="confirmRulesRead()"
                class="px-5 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow"
            >
                I Have Read the Rules
            </button>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    const venueID = "<?php echo htmlspecialchars($venue['vid'], ENT_QUOTES); ?>";
    const today = new Date();
    let currentMonth = today.getMonth();
    let currentYear = today.getFullYear();

    let selectedDateStr = "";
    let selectionState =
    { start: null, end: null };
    let blockedVectors = [];


    function addMinutes(timeStr, mins)
    {
        let [h, m] = timeStr.split(':').map(Number);
        let d = new Date();
        d.setHours(h, m + mins, 0, 0);
        return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    function renderCalendar()
    {
        const header = document.getElementById('calendar-header');
        const grid = document.getElementById('calendar-grid');
        const btnPrev = document.getElementById('btn-prev-month');

        btnPrev.disabled = (currentYear === today.getFullYear() && currentMonth === today.getMonth());
        const date = new Date(currentYear, currentMonth, 1);
        header.innerText = date.toLocaleString('default',
        { month: 'long', year: 'numeric' });
        grid.innerHTML = '';

        const firstDayIndex = date.getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

        for (let i = 0; i < firstDayIndex; i++) grid.innerHTML += `<div class="p-4"></div>`;

        for (let i = 1; i <= daysInMonth; i++)
        {
            const checkDate = new Date(currentYear, currentMonth, i);
            const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;

            if (checkDate.setHours(0,0,0,0) < today.setHours(0,0,0,0))
            {
                grid.innerHTML += `<div class="p-3 text-slate-300 bg-slate-50 rounded-lg cursor-not-allowed flex justify-center items-center">${i}</div>`;
            } else
            {
                grid.innerHTML += `<button onclick="initiateDaySelect('${dateStr}')" class="p-3 bg-white border-2 border-transparent hover:border-indigo-500 hover:text-indigo-600 rounded-lg transition font-bold text-slate-700 flex justify-center items-center shadow-sm">${i}</button>`;
            }
        }
    }

    document.getElementById('btn-prev-month').addEventListener('click', () =>
    {
        currentMonth--;
        if (currentMonth < 0)
        { currentMonth = 11; currentYear--; } renderCalendar(); });  document.getElementById('btn-next-month').addEventListener('click', () =>
        {
        currentMonth++;
        if (currentMonth > 11)
        { currentMonth = 0; currentYear++; } renderCalendar(); });  function initiateDaySelect(dateStr)
        {
        selectedDateStr = dateStr;
        document.getElementById('selected-date-display').innerText = dateStr;
        document.getElementById('payload_date').value = dateStr;

        document.getElementById('calendar-container').classList.add('hidden');
        document.getElementById('timeslot-container').classList.remove('hidden');

        selectionState =
        { start: null, end: null };
        document.getElementById('asyncBookingForm').classList.add('hidden');

        fetch(`../api/api_fetch_slots.php?venue_id=${encodeURIComponent(venueID)}&date=${encodeURIComponent(dateStr)}`)
            .then(async res =>
            {
                const text = await res.text();
                try
                {
                    return JSON.parse(text);
                } catch (e)
                {
                    console.error("Slot API returned non-JSON:", text);
                    throw new Error("Slot API returned non-JSON.");
                }
            })
            .then(data =>
            {
                if (data.status === 'success')
                {
                    blockedVectors = data.blocked_vectors || [];
                    renderTimeGrid();
                } else
                {
                    alert(data.message || "Unable to load available time slots.");
                }
            })
            .catch(error =>
            {
                console.error("Slot Fetch Error:", error);
                alert("Unable to load available time slots. Please check api_fetch_slots.php.");
            });
    }

    function returnToCalendar()
    {
        document.getElementById('calendar-container').classList.remove('hidden');
        document.getElementById('timeslot-container').classList.add('hidden');
    }


    function renderTimeGrid()
    {
        const timeGrid = document.getElementById('time-grid');
        timeGrid.innerHTML = '';

        const dynamicNow = new Date();
        const currentYearStr = dynamicNow.getFullYear();
        const currentMonthStr = String(dynamicNow.getMonth() + 1).padStart(2, '0');
        const currentDayStr = String(dynamicNow.getDate()).padStart(2, '0');
        const dynamicTodayStr = `${currentYearStr}-${currentMonthStr}-${currentDayStr}`;

        const isToday = (selectedDateStr === dynamicTodayStr);
        const currentTimeStr = `${String(dynamicNow.getHours()).padStart(2, '0')}:${String(dynamicNow.getMinutes()).padStart(2, '0')}`;


        const k = 25;

        for (let h = 0; h < 24; h++)
        {
            for (let m = 0; m < 60; m += 30)
            {
                const timeStr = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;


                const lockTimeStr = addMinutes(timeStr, k);


                let isBlocked = (isToday && currentTimeStr >= lockTimeStr);


                if (!isBlocked)
                {
                    for (let block of blockedVectors)
                    {
                        if (timeStr >= block.start && timeStr < block.end)
                        {
                            isBlocked = true;
                            break;
                        }
                    }
                }


                if (isBlocked)
                {
                    timeGrid.innerHTML += `<div class="p-2 text-center text-xs font-mono font-bold bg-slate-200 text-slate-400 rounded cursor-not-allowed">${timeStr}</div>`;
                } else
                {
                    timeGrid.innerHTML += `<button type="button" data-time="${timeStr}" onclick="handleSlotClick('${timeStr}', this)" class="time-slot-btn p-2 text-center text-xs font-mono font-bold bg-white border-2 border-slate-200 hover:border-indigo-400 text-slate-600 rounded transition">${timeStr}</button>`;
                }
            }
        }
    }


    function handleSlotClick(timeStr, btnElement)
    {
        if (!selectionState.start)
        {
            setStartSlot(timeStr, btnElement);
        } else if (!selectionState.end)
        {
            if (timeStr === selectionState.start)
            {
                selectionState.start = null;
                resetSlotStyles();
                document.getElementById('asyncBookingForm').classList.add('hidden');
                return;
            }
            if (timeStr < selectionState.start)
            {
                setStartSlot(timeStr, btnElement);
                return;
            }


            let rangeValid = true;

            const selectedStart = selectionState.start;
            const selectedEndWithInspection = addMinutes(timeStr, 30);

            for (let block of blockedVectors)
            {
                if (selectedStart < block.end && selectedEndWithInspection > block.start)
                {
                    rangeValid = false;
                    break;
                }
            }

            if (!rangeValid)
            {
                alert("This selected range conflicts with an existing booking or inspection time.");
                return;
            }

            selectionState.end = timeStr;
            paintSelectionRange();
            finalizeSelection();
        } else
        {
            setStartSlot(timeStr, btnElement);
        }
    }

    function setStartSlot(timeStr, btnElement)
    {
        selectionState.start = timeStr;
        selectionState.end = null;

        paintSelectionRange();

        document.getElementById('payload_start').value = '';
        document.getElementById('payload_end').value = '';
        document.getElementById('vector-display').innerText = `${timeStr} to --:--`;


        document.getElementById('asyncBookingForm').classList.add('hidden');
    }

    function resetSlotStyles()
    {
        document.querySelectorAll('.time-slot-btn').forEach(btn =>
        {
            btn.className = "time-slot-btn p-2 text-center text-xs font-mono font-bold bg-white border-2 border-slate-200 hover:border-indigo-400 text-slate-600 rounded transition";
        });
    }

    function paintSelectionRange()
    {
        resetSlotStyles();

        if (!selectionState.start) return;

        document.querySelectorAll('.time-slot-btn').forEach(btn =>
        {
            const btnTime = btn.dataset.time;

            if (!selectionState.end)
            {
                if (btnTime === selectionState.start)
                {
                    btn.className = "time-slot-btn p-2 text-center text-xs font-mono font-bold bg-indigo-600 border-2 border-indigo-600 text-white rounded transition";
                }
                return;
            }

            if (btnTime >= selectionState.start && btnTime <= selectionState.end)
            {
                btn.className = "time-slot-btn p-2 text-center text-xs font-mono font-bold bg-indigo-600 border-2 border-indigo-600 text-white rounded transition";
            }
        });
    }

    function finalizeSelection()
    {
        let actualStart = selectionState.start;
        let actualEnd = selectionState.end;

        if (!actualStart || !actualEnd)
        {
            document.getElementById('asyncBookingForm').classList.add('hidden');
            return;
        }

        document.getElementById('payload_start').value = actualStart;
        document.getElementById('payload_end').value = actualEnd;
        document.getElementById('vector-display').innerText = `${actualStart} to ${actualEnd}`;
        document.getElementById('asyncBookingForm').classList.remove('hidden');
    }

    document.getElementById('asyncBookingForm').addEventListener('submit', function(e)
    {
        e.preventDefault();

        const form = this;
        const submitBtn = document.getElementById('requestBookingBtn');
        const originalBtnText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 mr-2 animate-spin"></i> Processing...';
        submitBtn.disabled = true;
        submitBtn.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
        submitBtn.classList.add('bg-slate-400', 'cursor-not-allowed');
        lucide.createIcons();

        const formData = new FormData(form);

        fetch('../actions/process_booking.php',
        {
            method: 'POST',
            body: formData
        })
        .then(async response =>
        {
            const text = await response.text();

            try
            {
                return JSON.parse(text);
            } catch (error)
            {
                console.error("process_booking.php returned non-JSON:", text);
                throw new Error("process_booking.php returned invalid JSON.");
            }
        })
        .then(data =>
        {
            if (data.status === 'success')
            {
                window.location.href = data.redirect_url;
            } else
            {
                alert(data.message || "Booking failed.");
                resetFormButton(submitBtn, originalBtnText);
            }
        })
        .catch(error =>
        {
            console.error('Booking Error:', error);
            alert("Booking failed because the server returned an invalid response. Please check process_booking.php.");
            resetFormButton(submitBtn, originalBtnText);
        });
    });

    function resetFormButton(btn, originalText)
    {
        const checkbox = document.getElementById('agreeRules');

        btn.innerHTML = originalText;

        if (checkbox && checkbox.checked)
        {
            btn.disabled = false;
            btn.classList.remove('bg-slate-400', 'cursor-not-allowed');
            btn.classList.add('bg-indigo-600', 'hover:bg-indigo-700', 'shadow');
        } else
        {
            btn.disabled = true;
            btn.classList.remove('bg-indigo-600', 'hover:bg-indigo-700', 'shadow');
            btn.classList.add('bg-slate-400', 'cursor-not-allowed');
        }

        lucide.createIcons();
    }

    renderCalendar();

    function openRulesModal()
    {
        const modal = document.getElementById('rulesModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeRulesModal()
    {
        const modal = document.getElementById('rulesModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    function confirmRulesRead()
    {
        const checkbox = document.getElementById('agreeRules');

        checkbox.disabled = false;
        checkbox.classList.remove('cursor-not-allowed');

        closeRulesModal();
    }

    document.addEventListener('DOMContentLoaded', function ()
    {
        const checkbox = document.getElementById('agreeRules');
        const requestBtn = document.getElementById('requestBookingBtn');

        if (checkbox && requestBtn)
        {
            checkbox.addEventListener('change', function ()
            {
                if (checkbox.checked)
                {
                    requestBtn.disabled = false;
                    requestBtn.classList.remove('bg-slate-400', 'cursor-not-allowed');
                    requestBtn.classList.add('bg-indigo-600', 'hover:bg-indigo-700', 'shadow');
                } else
                {
                    requestBtn.disabled = true;
                    requestBtn.classList.remove('bg-indigo-600', 'hover:bg-indigo-700', 'shadow');
                    requestBtn.classList.add('bg-slate-400', 'cursor-not-allowed');
                }
            });
        }

        document.addEventListener('keydown', function (event)
        {
            if (event.key === 'Escape')
            {
                closeRulesModal();
            }
        });
    });
</script>

<?php include("../includes/user_footer.php"); ?>
