<?php
// File: admin/add_staff.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

/*
|--------------------------------------------------------------------------
| I: Repository Initialization & Mode Detection
|--------------------------------------------------------------------------
*/
// Registration page only, no data repository needed (Zero-Data Dependency)

/*
|--------------------------------------------------------------------------
| C: Configuration Definitions
|--------------------------------------------------------------------------
*/
$page_title = "Register Personnel";
$page_description = "Provision new administrative and inspector entities.";
$topbar_content = '
<div class="flex items-center">
    <a href="staff_directory.php" class="text-sm font-bold text-[#004aad] hover:text-[#003882] flex items-center mr-4 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back
    </a>
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider border-l border-slate-300 pl-4">Identity Management / Register Personnel</h2>
</div>';

$extra_css = [];

// Load form builder
require_once __DIR__ . '/../core/components/FioriFormBuilder.php';
use Core\Components\FioriFormBuilder as FB;

/*
|--------------------------------------------------------------------------
| V: View Rendering (State Binding via Builder)
|--------------------------------------------------------------------------
*/
ob_start();
?>

<form action="../actions/process_add_staff.php" method="POST" id="addStaffForm" class="bg-white rounded-lg shadow-sm border border-slate-200 relative mb-20">
    <div class="p-0">
        <div class="fiori-form-container">
            <div class="fiori-section-header">
                <h2 class="text-base font-bold text-[#1d2d3e]">Basic Personnel Data</h2>
            </div>

            <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <div class="lg:col-span-6 space-y-4">
                    <h3 class="text-sm font-bold text-[#1d2d3e] mb-4">Identity Credentials</h3>
                    
                    <?php 
                    echo FB::select('access_level', 'Authorization Level', [
                        'admin' => 'Standard Administrator',
                        'inspector' => 'Venue Inspector'
                    ], null, [
                        'required' => true,
                        'placeholder' => 'Select Role Assignment',
                        'extra_css' => 'font-bold text-indigo-700'
                    ]);

                    echo FB::input('text', 'full_name', 'Full Name', '', [
                        'required' => true, 
                        'placeholder' => 'e.g., Siti Nurhaliza'
                    ]);
                    ?>
                </div>

                <div class="lg:col-span-6 space-y-4">
                    <h3 class="text-sm font-bold text-[#1d2d3e] mb-4">Contact Parameters</h3>
                    
                    <div class="space-y-1">
                        <?php 
                        echo FB::input('email', 'email', 'Email Address', '', [
                            'id' => 'email',
                            'required' => true, 
                            'placeholder' => 'name@mmu.edu.my',
                            'oninput' => 'validateFormState()'
                        ]);
                        ?>
                        <p id="email-feedback" class="text-xs text-red-600 mt-1 hidden pl-32">Invalid email format</p>
                    </div>

                    <div class="space-y-1">
                        <?php
                        echo FB::input('text', 'phone_num', 'Contact Number', '', [
 'id' => 'phone_num', // Force DOM ID binding
                            'required' => true, 
                            'placeholder' => '0123456789',
                            'maxlength' => '11',               
                            'inputmode' => 'numeric',
                            'extra_css' => 'font-mono',
                            'oninput' => 'validateFormState()' 
                        ]);
                        ?>
                        <p id="phone-feedback" class="text-xs text-red-600 mt-1 hidden pl-32">Invalid length. Must be 10 or 11 digits.</p>
                    </div>
                    
                    <div class="pt-4 border-t border-slate-100 mt-4">
                        <p class="text-xs text-indigo-600 font-bold bg-indigo-50 border border-indigo-100 p-3 rounded-lg flex items-start">
                            <i data-lucide="info" class="w-4 h-4 mr-2 shrink-0 mt-0.5"></i>
                            Upon registration, an automated email containing a cryptographic token will be dispatched to the provided email address, allowing the personnel to configure their own credential vector.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="fixed bottom-0 right-0 w-full lg:w-[calc(100%-16rem)] bg-slate-50 border-t border-slate-200 p-4 px-6 flex justify-end space-x-3 z-50 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.1)]">
        <button type="button" onclick="window.location.href='staff_directory.php'" class="px-5 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 transition-colors">
            Cancel
        </button>
        <button type="submit" id="submitBtn" disabled class="px-5 py-2 text-sm font-semibold text-white bg-[#004aad] rounded-md hover:bg-[#003882] shadow-sm transition-colors flex items-center border border-[#004aad] disabled:opacity-50 disabled:cursor-not-allowed">
            <i data-lucide="send" class="w-4 h-4 mr-2"></i> Register Entity & Dispatch Email
        </button>
    </div>
</form>

<script>
 // Create status map (State Dictionary) track each field validity
    const formValidity = {
        email: false,
        phone: false
    };

    /**
 * Main control: check overall form status (Evaluate Aggregate State)
     * ∀ state ∈ formValidity, state ≡ true ⇒ enable Submit
     */
    function validateFormState() {
        validateEmailField();
        validatePhoneField();

        const btn = document.getElementById('submitBtn');
 // Logical AND check (Logical AND)
        const isFormValid = formValidity.email && formValidity.phone;
        btn.disabled = !isFormValid;
    }

    /**
 * V_{email}: Email validation function (MMU domain restriction)
 * Rule: only allow @mmu.edu.my or its subdomain ( @student.mmu.edu.my)
     */
    function validateEmailField() {
        const emailInput = document.getElementById('email');
        const emailFeedback = document.getElementById('email-feedback');
        
 // Strict domain regex
        const emailRegex = /^[a-zA-Z0-9._%+-]+@([a-zA-Z0-9.-]+\.)?mmu\.edu\.my$/;
        
        if (emailInput.value.length > 0) {
            if (emailRegex.test(emailInput.value)) {
 // If valid, restore default Fiori style
                emailInput.style.borderColor = '#d9d9d9';
                emailFeedback.classList.add('hidden');
                formValidity.email = true;
            } else {
 // If invalid, show highlight warning
                emailInput.style.borderColor = '#ee0000';
                emailFeedback.textContent = "Invalid identity vector. Only MMU institutional domains are permitted.";
                emailFeedback.classList.remove('hidden');
                formValidity.email = false;
            }
        } else {
            emailInput.style.borderColor = '#d9d9d9';
            emailFeedback.classList.add('hidden');
            formValidity.email = false;
        }
    }

    /**
 * V_{phone}: Phone number validation function
 * Process: Sanitize -> Measure -> Evaluate
     */
    function validatePhoneField() {
        const phoneInput = document.getElementById('phone_num');
        const phoneFeedback = document.getElementById('phone-feedback');
        
 // Rule 2: live filtering(Sanitization)
 // Use regex \D to remove non-numeric characters
        phoneInput.value = phoneInput.value.replace(/\D/g, '');
        
        const len = phoneInput.value.length;

        if (len > 0) {
 // Rule 3 and 4: length check (10 <= L <= 11)
 // L > 11 already limited by HTML maxlength="11"
            if (len >= 10 && len <= 11) {
                phoneInput.style.borderColor = '#d9d9d9';
                phoneFeedback.classList.add('hidden');
                formValidity.phone = true;
            } else {
                phoneInput.style.borderColor = '#ee0000';
                phoneFeedback.classList.remove('hidden');
                formValidity.phone = false;
            }
        } else {
            phoneInput.style.borderColor = '#d9d9d9';
            phoneFeedback.classList.add('hidden');
            formValidity.phone = false;
        }
    }

 // Prevent repeated submit in UI (Debounce / State lock)
    document.getElementById('addStaffForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mr-2 inline"></i> Provisioning...';
        btn.classList.add('opacity-70', 'cursor-not-allowed');
        btn.style.pointerEvents = 'none';
 // If lucide.js is used, render newly added icons again
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php
$page_content = ob_get_clean();

/*
|--------------------------------------------------------------------------
| L: Global Layout Engine
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../core/layout.php';
?>