<?php
// File: user_booking_test.php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; 
    $_SESSION['role'] = 'User';
}

$venues_query = "SELECT venue_id, venue_name, category, base_deposit FROM venues WHERE status = 'Available'";
$venues_result = $conn->query($venues_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal | Venue Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { mmu: { blue: '#004aad', dark: '#1e293b' } } } } }
    </script>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-6 font-sans">

    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden border border-slate-100">
        <div class="bg-mmu-dark p-6 text-center">
            <h2 class="text-2xl font-extrabold text-white tracking-wide">Venue Request Form</h2>
            <p class="text-slate-300 text-sm mt-1">Student Portal Sandbox Environment</p>
        </div>

        <form action="actions/process_booking.php" method="POST" id="bookingForm" class="p-8 space-y-5">
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Target Infrastructure (Venue)</label>
                <select name="venue_id" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-mmu-blue focus:outline-none text-slate-700 bg-slate-50">
                    <option value="">-- Select an available node --</option>
                    <?php
                    if ($venues_result && $venues_result->num_rows > 0) {
                        while ($row = $venues_result->fetch_assoc()) {
                            echo "<option value='" . $row['venue_id'] . "'>" . htmlspecialchars($row['venue_name']) . " (" . $row['category'] . ") - RM " . $row['base_deposit'] . "</option>";
                        }
                    } else {
                        echo "<option value='' disabled>No nodes available at this time</option>";
                    }
                    ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Temporal Vector (Date)</label>
                <input type="date" name="booking_date" min="<?php echo date('Y-m-d'); ?>" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-mmu-blue focus:outline-none text-slate-700 bg-slate-50">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Start Time</label>
                    <input type="time" name="start_time" id="start_time" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-mmu-blue focus:outline-none text-slate-700 bg-slate-50">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">End Time</label>
                    <input type="time" name="end_time" id="end_time" required onchange="validateTime()" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-mmu-blue focus:outline-none text-slate-700 bg-slate-50">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Purpose Definition</label>
                <textarea name="purpose" placeholder="Define the event scope..." required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-mmu-blue focus:outline-none text-slate-700 bg-slate-50 resize-none h-24"></textarea>
            </div>

            <button type="submit" id="submitBtn" class="w-full py-3.5 bg-mmu-blue hover:bg-blue-800 text-white font-bold rounded-lg transition shadow-md flex justify-center items-center">
                <i data-lucide="send" class="w-4 h-4 mr-2"></i> Submit Request
            </button>
        </form>
    </div>

    <script>
        lucide.createIcons();
        function validateTime() {
            const start = document.getElementById('start_time').value;
            const end = document.getElementById('end_time').value;
            const btn = document.getElementById('submitBtn');

            if (start && end) {
                if (end <= start) {
                    alert('Logical Error: End time must succeed start time.');
                    btn.disabled = true;
                    btn.classList.replace('bg-mmu-blue', 'bg-slate-400');
                } else {
                    btn.disabled = false;
                    btn.classList.replace('bg-slate-400', 'bg-mmu-blue');
                }
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>