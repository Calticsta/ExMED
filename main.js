// Global variables
let currentUser = null;
let authMode = 'login';
let chatLanguage = 'en';
let currentChatWith = null;
let subscriptionPlans = [
    { id: 'basic', name: 'Basic Plan', icon: 'fas fa-star', color: 'info', price: '10,000', frequency: '/month', trialDays: 7, features: ['AI chat support', 'Doctor consultations', 'Medical records access', 'Appointment booking'], description: 'Essential healthcare access' },
    { id: 'premium', name: 'Premium Plan', icon: 'fas fa-crown', color: 'warning', price: '17,000', trialDays: 7, popular: true, features: ['Unlimited AI chat support', 'Video consultations', 'Download medical records', 'Priority support', '24/7 Emergency access'], description: 'Complete healthcare' }
];

// OTP Storage
let otpStorage = {};

// ==================== API HELPER FUNCTIONS ====================
async function apiCall(action, data = {}) {
    try {
        const response = await fetch(`api_handler.php?action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: 'Network error' };
    }
}

// ==================== AUTHENTICATION FUNCTIONS ====================
function goToAuth(mode = 'login') {
    authMode = mode;
    document.getElementById('landing').classList.add('d-none');
    document.getElementById('dashboard').classList.add('d-none');
    document.getElementById('forgotPassword').classList.add('d-none');
    const auth = document.getElementById('auth');
    if (!auth) return;
    auth.classList.remove('d-none');
    const title = document.getElementById('authTitle');
    const fullName = document.getElementById('fullName');
    const phone = document.getElementById('phone');
    const role = document.getElementById('role');
    const verifyPanel = document.getElementById('verifyPanel');
    if (mode === 'register') {
        if (title) title.textContent = 'Create Account';
        if (fullName) fullName.classList.remove('d-none');
        if (phone) phone.classList.remove('d-none');
        if (role) role.classList.remove('d-none');
        if (verifyPanel) verifyPanel.classList.add('d-none');
        document.getElementById('toggleLink').textContent = 'Already have an account? Sign in';
    } else {
        if (title) title.textContent = 'Sign In';
        if (fullName) fullName.classList.add('d-none');
        if (phone) phone.classList.add('d-none');
        if (role) role.classList.add('d-none');
        if (verifyPanel) verifyPanel.classList.add('d-none');
        document.getElementById('toggleLink').textContent = 'No account? Create one';
    }
}

function toggleAuth() {
    goToAuth(authMode === 'register' ? 'login' : 'register');
}

function generateOtp(length = 6) {
    return Math.random().toString().substring(2, 2 + length).padStart(length, '0');
}

function sendOtp(email) {
    const otp = generateOtp(6);
    const expiry = Date.now() + 5 * 60 * 1000;
    otpStorage[email] = { code: otp, expiry: expiry };
    console.log(`OTP for ${email}: ${otp}`);
    displayOtpModal(otp);
}

function displayOtpModal(otp) {
    const modal = document.getElementById('otpModal');
    if (!modal) return;
    const display = document.getElementById('otpCodeDisplay');
    if (display) display.textContent = otp;
    modal.classList.remove('d-none');
}

function closeOtpModal() {
    const modal = document.getElementById('otpModal');
    if (modal) modal.classList.add('d-none');
}

function generateSimpleOtp() {
    const otp = generateOtp(6);
    const email = document.getElementById('email')?.value || 'user@example.com';
    otpStorage[email] = { code: otp, expiry: Date.now() + 5 * 60 * 1000 };
    const display = document.getElementById('generatedOtpDisplay');
    const otpSpan = document.getElementById('displayedOtp');
    if (display && otpSpan) {
        otpSpan.textContent = otp;
        display.classList.remove('d-none');
    }
}

function verifyOtp(email, enteredOtp) {
    const stored = otpStorage[email];
    if (!stored) return { valid: false, error: 'No OTP found. Request again.' };
    if (Date.now() > stored.expiry) return { valid: false, error: 'OTP expired. Request again.' };
    if (stored.code !== enteredOtp.trim()) return { valid: false, error: 'Incorrect OTP.' };
    delete otpStorage[email];
    return { valid: true };
}

function resendOtp() {
    const email = document.getElementById('email')?.value || '';
    if (!email) { alert('Enter email first'); return; }
    sendOtp(email);
}

async function verifyRegistrationOtp() {
    const email = document.getElementById('email')?.value || '';
    const enteredOtp = document.getElementById('regOtp')?.value || '';
    const result = verifyOtp(email, enteredOtp);
    if (!result.valid) { alert(result.error); return; }
    
    const fullName = document.getElementById('fullName')?.value || email.split('@')[0];
    const phone = document.getElementById('phone')?.value || '';
    const role = document.getElementById('role')?.value || 'patient';
    const password = document.getElementById('password')?.value || '';
    
    const signupResult = await apiCall('signup', { name: fullName, email, phone, password, role });
    if (signupResult.success) {
        alert('Account created! Please login.');
        goToAuth('login');
    } else {
        alert(signupResult.message);
    }
}

async function handleAuth() {
    const email = document.getElementById('email')?.value || '';
    const password = document.getElementById('password')?.value || '';
    
    if (authMode === 'register') {
        if (!email || !email.includes('@')) { alert('Please enter a valid email'); return; }
        const verifyPanel = document.getElementById('verifyPanel');
        const regOtpInput = document.getElementById('regOtp');
        if (verifyPanel && !verifyPanel.classList.contains('d-none')) {
            const entered = regOtpInput?.value || '';
            if (!entered.trim()) { alert('Enter the OTP'); return; }
            verifyRegistrationOtp();
            return;
        }
        document.getElementById('verifyPanel').classList.remove('d-none');
        sendOtp(email);
        setTimeout(() => { const r = document.getElementById('regOtp'); if (r) r.focus(); }, 200);
        return;
    }
    
    // Login
    const result = await apiCall('login', { email, password });
    if (result.success) {
        currentUser = result.user;
        const rememberMe = document.getElementById('rememberMe')?.checked;
        if (rememberMe) {
            localStorage.setItem('exmed_remembered_user', email);
        }
        document.getElementById('auth').classList.add('d-none');
        await showDashboard();
    } else {
        alert(result.message || 'Invalid email or password');
    }
}

function showForgotPassword() {
    document.getElementById('auth').classList.add('d-none');
    document.getElementById('forgotPassword').classList.remove('d-none');
}

function showForgotPasswordBack() {
    document.getElementById('forgotPassword').classList.add('d-none');
    goToAuth('login');
}

function handleForgotPassword() {
    const email = document.getElementById('forgotEmail')?.value || '';
    if (!email) { alert('Enter email'); return; }
    document.getElementById('resetPanel').classList.remove('d-none');
    sendOtp(email);
}

function resendResetOtp() {
    const email = document.getElementById('forgotEmail')?.value || '';
    if (!email) { alert('Enter email'); return; }
    sendOtp(email);
}

function verifyResetOtp() {
    const email = document.getElementById('forgotEmail')?.value || '';
    const enteredOtp = document.getElementById('resetOtpInput')?.value || '';
    const result = verifyOtp(email, enteredOtp);
    if (!result.valid) { alert(result.error); return; }
    document.getElementById('setNewPasswordPanel').classList.remove('d-none');
}

function setNewPassword() {
    const newPwd = document.getElementById('newPassword')?.value || '';
    const confirmPwd = document.getElementById('confirmNewPassword')?.value || '';
    if (newPwd !== confirmPwd) { alert('Passwords do not match'); return; }
    if (newPwd.length < 3) { alert('Password too short'); return; }
    alert('Password updated successfully! Please login with your new password.');
    showForgotPasswordBack();
}

async function logout() {
    await apiCall('logout');
    currentUser = null;
    document.getElementById('dashboard').classList.add('d-none');
    document.getElementById('landing').classList.remove('d-none');
}

// ==================== DASHBOARD FUNCTIONS ====================
async function showDashboard() {
    const result = await apiCall('get_current_user');
    if (!result.success || !result.user) {
        document.getElementById('landing').classList.remove('d-none');
        document.getElementById('dashboard').classList.add('d-none');
        return;
    }
    
    currentUser = result.user;
    document.getElementById('landing').classList.add('d-none');
    document.getElementById('dashboard').classList.remove('d-none');
    document.getElementById('userName').textContent = currentUser.name;
    
    // Show role-specific navigation
    document.getElementById('patientNav').classList.add('d-none');
    document.getElementById('doctorNav').classList.add('d-none');
    document.getElementById('nurseNav').classList.add('d-none');
    document.getElementById('adminNav').classList.add('d-none');
    
    if (currentUser.role === 'patient') {
        document.getElementById('patientNav').classList.remove('d-none');
        initializePatientDashboard();
    } else if (currentUser.role === 'doctor') {
        document.getElementById('doctorNav').classList.remove('d-none');
        initializeDoctorDashboard();
    } else if (currentUser.role === 'nurse') {
        document.getElementById('nurseNav').classList.remove('d-none');
        initializeNurseDashboard();
    } else if (currentUser.role === 'admin') {
        document.getElementById('adminNav').classList.remove('d-none');
        initializeAdminDashboard();
    }
    
    showTab('home');
}

function showTab(tabName) {
    document.querySelectorAll('[id^="tab-"]').forEach(el => el.classList.add('d-none'));
    const tab = document.getElementById('tab-' + tabName);
    if (tab) tab.classList.remove('d-none');
    
    if (tabName === 'appointments') {
        renderAppointmentsList();
        renderDepartments();
    } else if (tabName === 'prescriptions') {
        renderPrescriptions();
    } else if (tabName === 'records') {
        renderMedicalRecords();
    } else if (tabName === 'profile') {
        renderProfile();
    } else if (tabName === 'subscription') {
        initSubscriptionPage();
        checkSubscriptionStatus();
        populateSubscriptionHistory();
        populatePaymentHistory();
    } else if (tabName === 'insurance') {
        initInsuranceTab();
    } else if (tabName === 'chatbot') {
        // Chatbot already initialized
    } else if (tabName === 'doctor-patients') {
        populateDoctorPatients();
    } else if (tabName === 'doctor-appointments') {
        renderDoctorAppointments();
    } else if (tabName === 'doctor-prescribe') {
        populatePrescribeSelects();
    } else if (tabName === 'doctor-diagnose') {
        populateDiagnoseSelects();
    } else if (tabName === 'doctor-messages') {
        loadConversations();
    } else if (tabName === 'nurse') {
        populateVitalPatientSelect();
    } else if (tabName === 'admin') {
        renderAdminPanel();
    } else if (tabName === 'admin-home') {
        updateAdminDashboard();
    } else if (tabName === 'admin-reports') {
        renderStaffReports();
    }
}

// ==================== PATIENT DASHBOARD FUNCTIONS ====================
function initializePatientDashboard() {
    console.log("Initializing Patient Dashboard for:", currentUser.name);
    populateDoctorSelect();
    renderMedicalRecords();
    renderAppointmentsList();
    renderDepartments();
    renderPrescriptions();
    setTimeout(() => {
        try {
            initSubscriptionPage();
            renderHomeSubscriptionPlans();
            initInsuranceTab();
            checkSubscriptionStatus();
        } catch (e) {
            console.warn('Error initializing subscription/insurance:', e);
        }
    }, 100);
}

async function populateDoctorSelect() {
    const select = document.getElementById('apptDoctor');
    if (!select) return;
    const result = await apiCall('get_doctors');
    const doctors = result.doctors || [];
    select.innerHTML = '<option value="">-- Choose a doctor --</option>' + 
        doctors.map(d => `<option value="${d.email}" data-dept="${d.department || 'General'}">Dr. ${d.name}${d.specialization ? ' - ' + d.specialization : ''}</option>`).join('');
}

function updateDoctorDetails() {
    const select = document.getElementById('apptDoctor');
    const deptInput = document.getElementById('apptDepartment');
    const infoDiv = document.getElementById('doctorInfoDisplay');
    if (!select || !select.value) {
        if (deptInput) deptInput.value = '';
        if (infoDiv) infoDiv.textContent = '';
        return;
    }
    const option = select.selectedOptions[0];
    const dept = option.getAttribute('data-dept');
    if (deptInput) deptInput.value = dept;
    if (infoDiv) infoDiv.innerHTML = '<i class="fas fa-check-circle text-success"></i> Doctor selected - Ready for appointment';
}

async function scheduleAppointment() {
    const doctorEmail = document.getElementById('apptDoctor').value;
    const date = document.getElementById('apptDate').value;
    const time = document.getElementById('apptTime').value;
    const reason = document.getElementById('apptReason').value;
    const type = document.getElementById('apptType').value;
    
    if (!doctorEmail || !date || !time || !reason) {
        alert('Please fill all fields');
        return;
    }
    
    const doctorSelect = document.getElementById('apptDoctor');
    const doctorName = doctorSelect.options[doctorSelect.selectedIndex]?.textContent || '';
    
    const result = await apiCall('book_appointment', {
        patient_email: currentUser.email,
        patient_name: currentUser.name,
        doctor_email: doctorEmail,
        doctor_name: doctorName,
        date: date,
        time: time,
        reason: reason,
        type: type
    });
    
    if (result.success) {
        alert('Appointment booked successfully!');
        document.getElementById('apptDate').value = '';
        document.getElementById('apptTime').value = '';
        document.getElementById('apptReason').value = '';
        renderAppointmentsList();
    } else {
        alert('Failed to book appointment: ' + (result.message || 'Unknown error'));
    }
}

async function renderAppointmentsList() {
    const appointmentsDiv = document.getElementById('appointmentsList');
    if (!appointmentsDiv) return;
    
    const result = await apiCall('get_appointments', { email: currentUser.email, role: currentUser.role });
    const appointments = result.appointments || [];
    
    if (appointments.length === 0) {
        appointmentsDiv.innerHTML = '<div class="col-12"><p class="text-muted text-center">No appointments scheduled yet.</p></div>';
        return;
    }
    
    appointmentsDiv.innerHTML = appointments.map(appt => `
        <div class="col-md-6">
            <div class="card appointment-card p-3">
                <h6 class="mb-1"><i class="fas fa-user-md"></i> Dr. ${appt.doctor_name || 'Unknown'}</h6>
                <small class="text-muted">${appt.doctor_specialization || 'General Practice'}</small>
                <hr class="my-2">
                <p class="small mb-1"><strong>📅 Date:</strong> ${appt.appointment_date}</p>
                <p class="small mb-1"><strong>⏰ Time:</strong> ${appt.appointment_time}</p>
                <p class="small mb-1"><strong>📝 Reason:</strong> ${appt.reason || 'Not specified'}</p>
                <span class="badge bg-${appt.status === 'confirmed' ? 'success' : appt.status === 'completed' ? 'info' : 'warning'} mt-2">${appt.status || 'pending'}</span>
            </div>
        </div>
    `).join('');
}

// Department Functions
const departmentsData = [
    { id: 1, name: "Cardiology", description: "Heart & Cardiovascular Medicine", phone: "+256701111111", floor: "3rd Floor", hours: "8 AM - 5 PM" },
    { id: 2, name: "Infectious Diseases", description: "TB, HIV/AIDS, Malaria Treatment", phone: "+256702222222", floor: "2nd Floor", hours: "8 AM - 5 PM" },
    { id: 3, name: "General Medicine", description: "Primary Healthcare Services", phone: "+256703333333", floor: "1st Floor", hours: "7 AM - 6 PM" },
    { id: 4, name: "Neurology", description: "Nervous System Disorders", phone: "+256704444444", floor: "4th Floor", hours: "9 AM - 4 PM" },
    { id: 5, name: "Pediatrics", description: "Children's Health & Vaccination", phone: "+256705555555", floor: "2nd Floor", hours: "8 AM - 5 PM" },
    { id: 6, name: "Emergency", description: "Emergency & Trauma Care", phone: "+256706666666", floor: "Ground Floor", hours: "24/7" },
    { id: 7, name: "Orthopedics", description: "Bone & Joint Surgery", phone: "+256707777777", floor: "3rd Floor", hours: "8 AM - 5 PM" },
    { id: 8, name: "Obstetrics & Gynecology", description: "Maternal & Women's Health", phone: "+256708888888", floor: "4th Floor", hours: "8 AM - 5 PM" }
];

function renderDepartments() {
    const deptDiv = document.getElementById('departmentsList');
    if (!deptDiv) return;
    deptDiv.innerHTML = departmentsData.map(dept => `
        <div class="dept-card p-3 border rounded cursor-pointer" onclick="selectDepartmentForAppointment('${dept.name}')">
            <div class="dept-header mb-2">${dept.name}</div>
            <p class="small text-muted mb-1">${dept.description}</p>
            <div class="small"><p class="mb-1"><i class="fas fa-phone-alt"></i> ${dept.phone}</p><p class="mb-1"><i class="fas fa-map-marker-alt"></i> ${dept.floor}</p><p class="mb-0"><i class="fas fa-clock"></i> ${dept.hours}</p></div>
        </div>
    `).join('');
}

function filterDepartments() {
    const searchVal = document.getElementById('deptSearch')?.value || '';
    const depts = document.querySelectorAll('.dept-card');
    depts.forEach(dept => {
        const text = dept.textContent.toLowerCase();
        dept.style.display = text.includes(searchVal.toLowerCase()) ? '' : 'none';
    });
}

function selectDepartmentForAppointment(deptName) {
    const apptDept = document.getElementById('apptDepartment');
    if (apptDept) apptDept.value = deptName;
    showTab('appointments');
}

async function renderPrescriptions() {
    const presDiv = document.getElementById('prescriptionsList');
    if (!presDiv) return;
    
    const result = await apiCall('get_prescriptions', { email: currentUser.email });
    const prescriptions = result.prescriptions || [];
    const empty = document.getElementById('prescsEmptyMsg');
    if (empty) empty.style.display = prescriptions.length === 0 ? '' : 'none';
    
    if (prescriptions.length === 0) {
        presDiv.innerHTML = '';
        return;
    }
    
    presDiv.innerHTML = prescriptions.map(presc => `
        <div class="col-md-6">
            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0"><i class="fas fa-pills text-success"></i> ${presc.medication_name}</h6>
                    <span class="prescription-badge">${presc.status === 'active' ? 'Active' : 'Completed'}</span>
                </div>
                <p class="small mb-1"><strong>Prescribed by:</strong> Dr. ${presc.doctor_name}</p>
                <p class="small mb-1"><strong>Dosage:</strong> ${presc.dosage || 'N/A'}</p>
                <p class="small mb-1"><strong>Frequency:</strong> ${presc.frequency || 'N/A'}</p>
                <p class="small mb-2"><strong>Date:</strong> ${new Date(presc.prescription_date).toLocaleDateString()}</p>
                ${presc.instructions ? `<p class="small mb-2"><strong>Instructions:</strong> ${presc.instructions}</p>` : ''}
                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-sm btn-outline-primary flex-fill" onclick="viewPrescription('${presc.id}')"><i class="fas fa-eye"></i> View</button>
                    <button class="btn btn-sm btn-success flex-fill" onclick="downloadPrescription('${presc.id}')"><i class="fas fa-download"></i> Download</button>
                </div>
            </div>
        </div>
    `).join('');
}

function viewPrescription(prescId) {
    alert('Prescription details:\nThis is a secure medical document.\nFor full details, please contact your healthcare provider.');
}

function downloadPrescription(prescId) {
    alert('Prescription is being downloaded as PDF...');
}

async function renderMedicalRecords() {
    const recordsDiv = document.getElementById('medicalRecordsList');
    if (!recordsDiv) return;
    
    const result = await apiCall('get_medical_records', { email: currentUser.email });
    const records = result.records || [];
    
    if (records.length === 0) {
        recordsDiv.innerHTML = '<div class="col-12"><p class="text-muted">No medical records found.</p></div>';
        return;
    }
    
    recordsDiv.innerHTML = records.map(record => `
        <div class="col-md-6 mb-3">
            <div class="card record-item h-100 p-3 border-start border-4 border-primary">
                <h6 class="mb-1"><i class="fas fa-file-medical text-primary"></i> ${record.title}</h6>
                <small class="text-muted d-block mb-2">${new Date(record.date).toLocaleDateString()}</small>
                <p class="small mb-2">${record.content?.substring(0, 100) || 'No description'}${record.content?.length > 100 ? '...' : ''}</p>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-info">${record.type || 'Medical Record'}</span>
                    <button class="btn btn-sm btn-primary btn-download-doc" onclick="downloadRecord('${record.id}')"><i class="fas fa-download"></i> Download</button>
                </div>
            </div>
        </div>
    `).join('');
}

function downloadRecord(recordId) {
    alert('Downloading medical record...');
}

function renderProfile() {
    const profileContent = document.getElementById('profileContent');
    if (!profileContent) return;
    profileContent.innerHTML = `
        <div class="row g-3">
            <div class="col-md-6"><div class="card p-3"><h6><i class="fas fa-user"></i> User Information</h6><p><strong>Name:</strong> ${currentUser.name}</p><p><strong>Email:</strong> ${currentUser.email}</p><p><strong>Phone:</strong> ${currentUser.phone || 'Not provided'}</p><p><strong>Member Since:</strong> ${new Date(currentUser.created_at).toLocaleDateString()}</p></div></div>
            <div class="col-md-6"><div class="card p-3"><h6><i class="fas fa-briefcase"></i> Role & Permissions</h6><p><strong>Role:</strong> <span class="badge bg-primary">${currentUser.role.toUpperCase()}</span></p><p><strong>Subscription:</strong> <span class="badge bg-${currentUser.subscription === 'premium' ? 'success' : 'secondary'}">${currentUser.subscription || 'Basic'}</span></p></div></div>
        </div>
    `;
}

// ==================== SUBSCRIPTION FUNCTIONS ====================
function initSubscriptionPage() {
    const plansDiv = document.getElementById('subscriptionPlans');
    if (!plansDiv) return;
    
    let plansHTML = '<div class="row g-4">';
    subscriptionPlans.forEach(plan => {
        plansHTML += `
            <div class="col-md-6">
                <div class="card h-100 shadow-sm ${plan.popular ? 'border-success border-3' : 'border'}">
                    ${plan.popular ? '<div class="badge bg-success position-absolute top-0 start-50 translate-middle-x mt-3">⭐ Most Popular</div>' : ''}
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><i class="${plan.icon} text-${plan.color} me-2"></i> ${plan.name}</h5>
                        <h3 class="card-text text-${plan.color}">UGX ${plan.price}</h3>
                        <small class="text-muted d-block mb-3">${plan.frequency}</small>
                        <ul class="list-unstyled small">${plan.features.map(f => `<li><i class="fas fa-check text-success me-1"></i> ${f}</li>`).join('')}</ul>
                        <button class="btn btn-${plan.color} mt-auto" onclick="selectPaymentMethod('${plan.id}', 'free-trial')"><i class="fas fa-gift me-1"></i> Start 7-Day Free Trial</button>
                    </div>
                </div>
            </div>
        `;
    });
    plansHTML += '</div>';
    plansDiv.innerHTML = plansHTML;
}

function renderHomeSubscriptionPlans() {
    const plansDiv = document.getElementById('homeSubscriptionPlans');
    if (!plansDiv) return;
    plansDiv.innerHTML = subscriptionPlans.map(plan => `
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5><i class="${plan.icon} text-${plan.color} me-2"></i> ${plan.name}</h5>
                    <h3>UGX ${plan.price}</h3>
                    <p class="small text-muted">${plan.description}</p>
                    <button class="btn btn-${plan.color} w-100" onclick="selectPaymentMethod('${plan.id}', 'free-trial')">Start Free Trial</button>
                </div>
            </div>
        </div>
    `).join('');
}

async function checkSubscriptionStatus() {
    const result = await apiCall('check_subscription', { user_email: currentUser.email });
    const statusDiv = document.getElementById('subscriptionStatus');
    if (!statusDiv) return;
    
    if (result.is_active) {
        const expiryDate = result.expiry ? new Date(result.expiry) : null;
        const daysLeft = expiryDate ? Math.ceil((expiryDate - new Date()) / (1000 * 60 * 60 * 24)) : 0;
        
        let statusHtml = `
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                <strong>Active ${result.plan?.toUpperCase() || 'PREMIUM'} Plan</strong>
                <p class="mb-0 mt-2 small">Expires: ${expiryDate ? expiryDate.toLocaleDateString() : 'N/A'} (${daysLeft} days left)</p>
                ${result.status === 'trial' ? '<p class="mb-0 mt-1 small text-warning">🎁 7-day free trial active!</p>' : ''}
            </div>
        `;
        statusDiv.innerHTML = statusHtml;
        return true;
    } else {
        statusDiv.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i> 
                <strong>No Active Subscription</strong>
                <p class="mb-0 mt-2 small">Subscribe today to access all premium features!</p>
            </div>
        `;
        return false;
    }
}

function selectPaymentMethod(planId, method) {
    const plan = subscriptionPlans.find(p => p.id === planId);
    if (!plan) return;
    
    document.getElementById('selectedPlanId').value = planId;
    document.getElementById('selectedPaymentMethod').value = method;
    document.getElementById('confirmPlanName').textContent = plan.name;
    document.getElementById('confirmPlanPrice').textContent = `UGX ${plan.price}`;
    document.getElementById('confirmPaymentMethod').textContent = method === 'free-trial' ? '7-Day Free Trial' : method;
    document.getElementById('confirmTrialDays').textContent = '7';
    
    const modal = new bootstrap.Modal(document.getElementById('subscriptionConfirmationModal'));
    modal.show();
}

async function confirmSubscription() {
    const agreeTerms = document.getElementById('agreeTerms')?.checked;
    if (!agreeTerms) {
        alert('Please agree to the terms and conditions');
        return;
    }
    
    const planId = document.getElementById('selectedPlanId')?.value;
    const paymentMethod = document.getElementById('selectedPaymentMethod')?.value;
    const plan = subscriptionPlans.find(p => p.id === planId);
    
    if (!plan) return;
    
    const amount = parseInt(plan.price.replace(/,/g, ''));
    
    const result = await apiCall('activate_subscription', {
        user_email: currentUser.email,
        plan_type: planId,
        payment_method: paymentMethod,
        amount: amount,
        is_trial: true
    });
    
    if (result.success) {
        alert(`✅ Subscription activated! 7-day free trial started! Expires: ${new Date(result.end_date).toLocaleDateString()}`);
        bootstrap.Modal.getInstance(document.getElementById('subscriptionConfirmationModal')).hide();
        checkSubscriptionStatus();
        initSubscriptionPage();
    } else {
        alert('Failed to activate subscription: ' + (result.message || 'Unknown error'));
    }
}

function populateSubscriptionHistory() {
    const historyTable = document.getElementById('subscriptionHistoryTable');
    if (historyTable) historyTable.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No subscription history</td></tr>';
}

function populatePaymentHistory() {
    const paymentTable = document.getElementById('paymentHistoryTable');
    if (paymentTable) paymentTable.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No payment history</td></tr>';
}

function initInsuranceTab() {
    const insurancePanel = document.getElementById('insurancePanel');
    if (!insurancePanel) return;
    insurancePanel.innerHTML = `
        <div class="alert alert-info">
            <h6><i class="fas fa-shield-alt"></i> Your Insurance Coverage</h6>
            <p>NHIF Coverage: Active<br>Policy Number: NHIF-2024-${Math.random().toString().slice(2, 8)}<br>Valid Until: 2025-12-31</p>
        </div>
    `;
    const docsList = document.getElementById('insuranceDocsList');
    if (docsList) {
        docsList.innerHTML = `<a href="#" class="list-group-item list-group-item-action" onclick="event.preventDefault(); alert('Downloading insurance document...')"><div class="d-flex justify-content-between"><div><h6 class="mb-1">NHIF Certificate</h6><small class="text-muted">National Health Insurance</small></div><i class="fas fa-download text-primary"></i></div></a>`;
    }
}

// ==================== DOCTOR DASHBOARD FUNCTIONS ====================
function initializeDoctorDashboard() {
    console.log("Initializing Doctor Dashboard for:", currentUser.name);
    populateDoctorPatients();
    renderDoctorAppointments();
    populatePrescribeSelects();
    populateDiagnoseSelects();
    populateLabSelects();
}

async function populateDoctorPatients() {
    const list = document.getElementById('doctorPatientsList');
    if (!list) return;
    
    const result = await apiCall('get_doctor_patients', { email: currentUser.email });
    const patients = result.patients || [];
    
    if (patients.length === 0) {
        list.innerHTML = '<div class="col-12"><p class="text-muted">No patients assigned yet.</p></div>';
        return;
    }
    
    list.innerHTML = patients.map(p => `
        <div class="col-md-6">
            <div class="card p-3 record-item">
                <h6 class="mb-1">${p.full_name}</h6>
                <p class="small text-muted mb-1">${p.email}</p>
                <p class="small mb-2">Visits: ${p.visit_count || 0}</p>
                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-sm btn-outline-primary" onclick="openPatientDetail('${p.email}')">View Records</button>
                    <button class="btn btn-sm btn-outline-success" onclick="messagePatient('${p.email}')">Message</button>
                </div>
            </div>
        </div>
    `).join('');
}

function filterDoctorPatients() {
    const q = document.getElementById('doctorPatientSearch')?.value || '';
    const items = document.querySelectorAll('#doctorPatientsList .col-md-6');
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(q.toLowerCase()) ? '' : 'none';
    });
}

async function openPatientDetail(patientEmail) {
    const result = await apiCall('get_patient_history', { patient_email: patientEmail });
    if (!result.success) {
        alert('Failed to load patient records');
        return;
    }
    
    const content = document.getElementById('patientDetailContent');
    if (!content) return;
    
    content.innerHTML = `
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card p-3">
                    <h5><i class="fas fa-notes-medical"></i> Medical Records</h5>
                    ${result.records && result.records.length > 0 ? result.records.map(r => `
                        <div class="border-bottom py-2">
                            <strong>${r.record_title}</strong><br>
                            <small class="text-muted">${new Date(r.created_at).toLocaleDateString()}</small>
                            <p class="mt-1">${r.record_description || 'No description'}</p>
                        </div>
                    `).join('') : '<p class="text-muted">No medical records</p>'}
                </div>
            </div>
            <div class="col-12 mb-4">
                <div class="card p-3">
                    <h5><i class="fas fa-pills"></i> Prescriptions</h5>
                    ${result.prescriptions && result.prescriptions.length > 0 ? result.prescriptions.map(p => `
                        <div class="border-bottom py-2">
                            <strong>${p.medication_name}</strong> - ${p.dosage || 'N/A'}<br>
                            <small>Prescribed: ${new Date(p.created_at).toLocaleDateString()}</small>
                            <p class="mt-1">${p.instructions || 'No instructions'}</p>
                        </div>
                    `).join('') : '<p class="text-muted">No prescriptions</p>'}
                </div>
            </div>
            <div class="col-12">
                <div class="card p-3">
                    <h5><i class="fas fa-heartbeat"></i> Recent Vital Signs</h5>
                    ${result.vitals && result.vitals.length > 0 ? result.vitals.map(v => `
                        <div class="border-bottom py-2">
                            <small>${new Date(v.recorded_at).toLocaleDateString()}</small><br>
                            Temp: ${v.temperature}°C | BP: ${v.blood_pressure_systolic}/${v.blood_pressure_diastolic} | HR: ${v.heart_rate} | O2: ${v.oxygen_saturation}%
                        </div>
                    `).join('') : '<p class="text-muted">No vital signs recorded</p>'}
                </div>
            </div>
        </div>
        <div class="mt-3">
            <button class="btn btn-primary" onclick="showTab('doctor-prescribe')">Write Prescription</button>
            <button class="btn btn-info" onclick="showTab('doctor-diagnose')">Add Diagnosis</button>
            <button class="btn btn-success" onclick="showTab('doctor-orders')">Order Tests</button>
        </div>
    `;
    
    showTab('doctor-patient-detail');
}

function messagePatient(patientEmail) {
    currentChatWith = patientEmail;
    showTab('doctor-messages');
    setTimeout(() => loadMessageThread(patientEmail), 100);
}

async function renderDoctorAppointments() {
    const el = document.getElementById('doctorAppointmentsList');
    if (!el) return;
    
    const result = await apiCall('get_appointments', { email: currentUser.email, role: 'doctor' });
    const appointments = result.appointments || [];
    
    if (appointments.length === 0) {
        el.innerHTML = '<div class="col-12"><p class="text-muted">No appointments scheduled.</p></div>';
        return;
    }
    
    el.innerHTML = appointments.map(a => `
        <div class="col-md-6">
            <div class="card p-3">
                <h6 class="mb-1">${a.patient_name}</h6>
                <p class="small mb-1"><strong>Date:</strong> ${a.appointment_date}</p>
                <p class="small mb-1"><strong>Time:</strong> ${a.appointment_time}</p>
                <p class="small mb-1"><strong>Type:</strong> ${a.type === 'tele' ? 'Teleconsult' : 'Clinic Visit'}</p>
                <p class="small mb-1"><strong>Reason:</strong> ${a.reason || 'Not specified'}</p>
                <div class="d-flex gap-2 mt-2">
                    <span class="badge bg-${a.status === 'confirmed' ? 'success' : a.status === 'completed' ? 'info' : 'warning'}">${a.status || 'pending'}</span>
                    ${a.status === 'pending' ? `<button class="btn btn-sm btn-success" onclick="confirmAppointment('${a.id}')">Confirm</button>` : ''}
                    ${a.status === 'confirmed' ? `<button class="btn btn-sm btn-info" onclick="completeAppointment('${a.id}')">Complete</button>` : ''}
                </div>
            </div>
        </div>
    `).join('');
}

async function confirmAppointment(appointmentId) {
    const result = await apiCall('confirm_appointment', { appointment_id: appointmentId });
    if (result.success) {
        alert('Appointment confirmed!');
        renderDoctorAppointments();
    } else {
        alert('Failed to confirm appointment');
    }
}

async function completeAppointment(appointmentId) {
    const result = await apiCall('complete_appointment', { appointment_id: appointmentId });
    if (result.success) {
        alert('Appointment marked as completed!');
        renderDoctorAppointments();
    } else {
        alert('Failed to complete appointment');
    }
}

async function populatePrescribeSelects() {
    const medSelect = document.getElementById('prescribeMedicineSelect');
    const patientSelect = document.getElementById('prescribePatientSelect');
    
    const medications = [
        { id: 'PARACETAMOL', name: "Paracetamol (Panadol) - 500mg" },
        { id: 'ARTEMETHER', name: "Artemether/Lumefantrine - 20/120mg" },
        { id: 'QUININE', name: "Quinine Sulfate - 300mg" },
        { id: 'AMPICILLIN', name: "Ampicillin - 500mg" },
        { id: 'AMOXICILLIN', name: "Amoxicillin - 500mg" },
        { id: 'METFORMIN', name: "Metformin - 500mg" },
        { id: 'ATENOLOL', name: "Atenolol - 50mg" },
        { id: 'OMEPRAZOLE', name: "Omeprazole - 20mg" }
    ];
    
    if (medSelect) medSelect.innerHTML = '<option value="">-- Choose Medicine --</option>' + medications.map(p => `<option value="${p.name}">${p.name}</option>`).join('');
    
    const patientsResult = await apiCall('get_patients');
    const patients = patientsResult.patients || [];
    if (patientSelect) patientSelect.innerHTML = '<option value="">-- Choose Patient --</option>' + patients.map(p => `<option value="${p.email}" data-name="${p.name}">${p.name} (${p.email})</option>`).join('');
}

async function submitPrescription() {
    const patientEmail = document.getElementById('prescribePatientSelect').value;
    const medication = document.getElementById('prescribeMedicineSelect').value;
    const dosage = document.getElementById('prescribeDosage').value;
    const frequency = document.getElementById('prescribeFrequency').value;
    const duration = document.getElementById('prescribeDuration').value;
    const notes = document.getElementById('prescribeNotes').value;
    
    if (!patientEmail || !medication) {
        alert('Please select patient and medicine');
        return;
    }
    
    const patientSelect = document.getElementById('prescribePatientSelect');
    const patientName = patientSelect.options[patientSelect.selectedIndex]?.getAttribute('data-name') || '';
    
    const result = await apiCall('submit_prescription', {
        patient_email: patientEmail,
        patient_name: patientName,
        doctor_email: currentUser.email,
        doctor_name: currentUser.name,
        medication: medication,
        dosage: dosage,
        frequency: frequency,
        duration: duration,
        instructions: notes
    });
    
    if (result.success) {
        alert('Prescription submitted successfully!');
        document.getElementById('prescribeDosage').value = '';
        document.getElementById('prescribeFrequency').value = '';
        document.getElementById('prescribeDuration').value = '';
        document.getElementById('prescribeNotes').value = '';
    } else {
        alert('Failed to submit prescription');
    }
}

function populateDiagnoseSelects() {
    const diagSelect = document.getElementById('diagnosisSelect');
    const diseases = [
        { id: 'MALARIA', name: "Malaria" },
        { id: 'TB', name: "Tuberculosis" },
        { id: 'HYPERTENSION', name: "Hypertension" },
        { id: 'DIABETES', name: "Diabetes Mellitus" },
        { id: 'PNEUMONIA', name: "Pneumonia" },
        { id: 'TYPHOID', name: "Typhoid Fever" },
        { id: 'UTI', name: "Urinary Tract Infection" },
        { id: 'ASTHMA', name: "Asthma" }
    ];
    if (diagSelect) diagSelect.innerHTML = '<option value="">-- Choose Condition --</option>' + diseases.map(d => `<option value="${d.name}">${d.name}</option>`).join('');
    
    const patientSelect = document.getElementById('diagnosePatientSelect');
    if (patientSelect) {
        apiCall('get_patients').then(result => {
            const patients = result.patients || [];
            patientSelect.innerHTML = '<option value="">-- Choose Patient --</option>' + patients.map(p => `<option value="${p.email}" data-name="${p.name}">${p.name}</option>`).join('');
        });
    }
}

function loadDiagnosePatientInfo() {
    const select = document.getElementById('diagnosePatientSelect');
    const infoDiv = document.getElementById('diagnosePatientInfo');
    if (!select || !select.value || !infoDiv) return;
    const option = select.options[select.selectedIndex];
    const patientName = option.getAttribute('data-name') || '';
    infoDiv.innerHTML = `<strong>${patientName}</strong> - Ready for diagnosis`;
}

async function submitDiagnosis() {
    const patientEmail = document.getElementById('diagnosePatientSelect')?.value;
    const diagnosis = document.getElementById('diagnosisSelect')?.value;
    const notes = document.getElementById('diagnosisNotes')?.value;
    const severity = document.getElementById('diagnosisSeverity')?.value;
    
    if (!patientEmail || !diagnosis) {
        alert('Please select patient and diagnosis');
        return;
    }
    
    const patientSelect = document.getElementById('diagnosePatientSelect');
    const patientName = patientSelect.options[patientSelect.selectedIndex]?.getAttribute('data-name') || '';
    
    const result = await apiCall('submit_diagnosis', {
        patient_email: patientEmail,
        patient_name: patientName,
        doctor_email: currentUser.email,
        doctor_name: currentUser.name,
        diagnosis: diagnosis,
        notes: notes,
        severity: severity
    });
    
    if (result.success) {
        alert('Diagnosis saved successfully!');
        document.getElementById('diagnosisNotes').value = '';
        document.getElementById('diagnosisSeverity').value = '';
        showTab('doctor-patients');
    } else {
        alert('Failed to save diagnosis');
    }
}

function populateLabSelects() {
    const labPatientSelect = document.getElementById('labPatientSelect');
    const imagingPatientSelect = document.getElementById('imagingPatientSelect');
    const consultPatientSelect = document.getElementById('consultPatientSelect');
    
    apiCall('get_patients').then(result => {
        const patients = result.patients || [];
        const options = '<option value="">-- Choose Patient --</option>' + patients.map(p => `<option value="${p.email}" data-name="${p.name}">${p.name}</option>`).join('');
        if (labPatientSelect) labPatientSelect.innerHTML = options;
        if (imagingPatientSelect) imagingPatientSelect.innerHTML = options;
        if (consultPatientSelect) consultPatientSelect.innerHTML = options;
    });
}

function loadLabTestOptions() {
    // Placeholder for loading test options based on patient
}

async function orderLabTest() {
    const patientEmail = document.getElementById('labPatientSelect')?.value;
    const testType = document.getElementById('labTestType')?.value;
    const indication = document.getElementById('labIndication')?.value;
    
    if (!patientEmail || !testType) {
        alert('Please select patient and test type');
        return;
    }
    
    const result = await apiCall('order_lab_test', {
        patient_email: patientEmail,
        doctor_email: currentUser.email,
        test_type: testType,
        indication: indication
    });
    
    if (result.success) {
        alert('Lab test ordered successfully!');
        document.getElementById('labIndication').value = '';
    } else {
        alert('Failed to order test');
    }
}

function orderImaging() {
    alert('Imaging study ordered successfully!');
}

function requestConsultation() {
    alert('Consultation requested successfully!');
}

// ==================== MESSAGING FUNCTIONS ====================
async function loadConversations() {
    const result = await apiCall('get_messages', { email: currentUser.email });
    const messages = result.messages || [];
    const listDiv = document.getElementById('messagesList');
    
    if (!listDiv) return;
    
    if (messages.length === 0) {
        listDiv.innerHTML = '<p class="text-muted small">No conversations yet</p>';
        return;
    }
    
    listDiv.innerHTML = messages.map(conv => `
        <a href="#" class="list-group-item list-group-item-action" onclick="loadMessageThread('${conv.other_user_email}'); return false;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>${conv.other_user_name}</strong><br>
                    <small class="text-muted">${conv.last_message?.substring(0, 50) || 'No messages'}</small>
                </div>
                <small class="text-muted">${conv.last_message_time ? new Date(conv.last_message_time).toLocaleTimeString() : ''}</small>
            </div>
        </a>
    `).join('');
}

async function loadMessageThread(withEmail) {
    const result = await apiCall('get_messages', { 
        email: currentUser.email,
        with_email: withEmail
    });
    
    const messages = result.messages || [];
    const threadDiv = document.getElementById('messageThread');
    const titleSpan = document.getElementById('messageThreadTitle');
    
    if (threadDiv && messages.length > 0) {
        const otherUser = messages[0].sender_email === currentUser.email ? 
            messages[0].receiver_name : messages[0].sender_name;
        
        if (titleSpan) titleSpan.textContent = `Chat with ${otherUser}`;
        
        threadDiv.innerHTML = messages.map(msg => `
            <div class="message-${msg.sender_email === currentUser.email ? 'sent' : 'received'} mb-2">
                <div class="bubble">
                    ${escapeHtml(msg.message)}<br>
                    <small class="opacity-75">${new Date(msg.created_at).toLocaleTimeString()}</small>
                </div>
            </div>
        `).join('');
        
        document.getElementById('messageInput').disabled = false;
        document.getElementById('sendMessageBtn').disabled = false;
        currentChatWith = withEmail;
        threadDiv.scrollTop = threadDiv.scrollHeight;
    }
}

async function sendMessage() {
    const message = document.getElementById('messageInput')?.value;
    if (!message || !currentChatWith) return;
    
    const result = await apiCall('send_message', {
        sender_email: currentUser.email,
        receiver_email: currentChatWith,
        message: message
    });
    
    if (result.success) {
        document.getElementById('messageInput').value = '';
        loadMessageThread(currentChatWith);
    } else {
        alert('Failed to send message');
    }
}

// ==================== NURSE DASHBOARD FUNCTIONS ====================
function initializeNurseDashboard() {
    console.log("Initializing Nurse Dashboard for:", currentUser.name);
    populateVitalPatientSelect();
}

async function populateVitalPatientSelect() {
    const select = document.getElementById('vitalPatientSelect');
    if (!select) return;
    const result = await apiCall('get_patients');
    const patients = result.patients || [];
    select.innerHTML = '<option value="">-- Choose Patient --</option>' + patients.map(p => `<option value="${p.email}" data-name="${p.name}">${p.name} (${p.email})</option>`).join('');
}

function loadVitalPatientInfo() {
    const select = document.getElementById('vitalPatientSelect');
    const infoDiv = document.getElementById('vitalPatientInfo');
    if (!select || !select.value || !infoDiv) return;
    const option = select.options[select.selectedIndex];
    const patientName = option.getAttribute('data-name') || '';
    infoDiv.innerHTML = `<strong>${patientName}</strong> - Ready for vital signs recording`;
    
    // Auto-calculate BMI when weight/height change
    const weightInput = document.getElementById('weightInput');
    const heightInput = document.getElementById('heightInput');
    const bmiDisplay = document.getElementById('bmiDisplay');
    
    if (weightInput && heightInput && bmiDisplay) {
        const calculateBMI = () => {
            const weight = parseFloat(weightInput.value);
            const height = parseFloat(heightInput.value) / 100;
            if (weight && height && height > 0) {
                const bmi = (weight / (height * height)).toFixed(1);
                bmiDisplay.value = bmi;
            }
        };
        weightInput.oninput = calculateBMI;
        heightInput.oninput = calculateBMI;
    }
}

async function recordVitals() {
    const patientEmail = document.getElementById('vitalPatientSelect').value;
    const temp = document.getElementById('tempInput').value;
    const bp = document.getElementById('bpInput').value;
    const hr = document.getElementById('hrInput').value;
    const o2 = document.getElementById('o2Input').value;
    const weight = document.getElementById('weightInput')?.value || '';
    const height = document.getElementById('heightInput')?.value || '';
    
    if (!patientEmail) {
        alert('Please select a patient');
        return;
    }
    if (!temp || !bp || !hr || !o2) {
        alert('Please fill all vital signs fields');
        return;
    }
    
    const patientSelect = document.getElementById('vitalPatientSelect');
    const patientName = patientSelect.options[patientSelect.selectedIndex]?.getAttribute('data-name') || '';
    
    const result = await apiCall('save_vitals', {
        patient_email: patientEmail,
        patient_name: patientName,
        temp: temp,
        bp: bp,
        hr: hr,
        o2: o2,
        weight: weight,
        height: height,
        recorded_by: currentUser.name,
        recorded_by_role: currentUser.role
    });
    
    if (result.success) {
        alert('Vital signs recorded successfully!');
        document.getElementById('tempInput').value = '';
        document.getElementById('bpInput').value = '';
        document.getElementById('hrInput').value = '';
        document.getElementById('o2Input').value = '';
        if (document.getElementById('weightInput')) document.getElementById('weightInput').value = '';
        if (document.getElementById('heightInput')) document.getElementById('heightInput').value = '';
        if (document.getElementById('bmiDisplay')) document.getElementById('bmiDisplay').value = '';
    } else {
        alert('Failed to record vital signs');
    }
}

function registerAdmission() {
    const name = document.getElementById('admitName')?.value;
    const email = document.getElementById('admitEmail')?.value;
    const phone = document.getElementById('admitPhone')?.value;
    const reason = document.getElementById('admitReason')?.value;
    const date = document.getElementById('admitDate')?.value;
    
    if (!name || !email) {
        alert('Please fill patient name and email');
        return;
    }
    
    alert(`Patient ${name} has been admitted successfully!`);
    document.getElementById('admitName').value = '';
    document.getElementById('admitEmail').value = '';
    document.getElementById('admitPhone').value = '';
    document.getElementById('admitDate').value = '';
}

// ==================== ADMIN DASHBOARD FUNCTIONS ====================
function initializeAdminDashboard() {
    console.log("Initializing Admin Dashboard for:", currentUser.name);
    renderAdminPanel();
    updateAdminDashboard();
}

async function renderAdminPanel() {
    const usersTable = document.getElementById('usersTableBody');
    if (!usersTable) return;
    
    const result = await apiCall('get_users');
    const users = result.users || [];
    
    usersTable.innerHTML = users.map(user => `
        <tr>
            <td>${user.name}</td>
            <td><span class="badge bg-${user.role === 'admin' ? 'danger' : user.role === 'doctor' ? 'primary' : user.role === 'nurse' ? 'info' : 'secondary'}">${user.role}</span></td>
            <td>${user.email}</td>
            <td>${user.role !== 'admin' ? `<button class="btn btn-sm btn-danger" onclick="deleteUser('${user.email}')">Delete</button>` : '<span class="text-muted">Protected</span>'}</td>
        </tr>
    `).join('');
}

async function deleteUser(email) {
    if (confirm('Are you sure you want to delete this user?')) {
        const result = await apiCall('delete_user', { email: email });
        if (result.success) {
            alert('User deleted successfully');
            renderAdminPanel();
        } else {
            alert('Failed to delete user');
        }
    }
}

function addStaffMember() {
    const name = document.getElementById('staffName')?.value;
    const email = document.getElementById('staffEmail')?.value;
    const role = document.getElementById('staffRole')?.value;
    const phone = document.getElementById('staffPhone')?.value;
    
    if (!name || !email || !role) {
        alert('Please fill all required fields');
        return;
    }
    
    alert(`Staff member ${name} (${role}) has been added. They will receive an email with login instructions.`);
    document.getElementById('staffName').value = '';
    document.getElementById('staffEmail').value = '';
    document.getElementById('staffPhone').value = '';
}

async function updateAdminDashboard() {
    const statsResult = await apiCall('get_stats');
    const stats = statsResult.stats || {};
    
    const usersResult = await apiCall('get_users');
    const users = usersResult.users || [];
    const patients = users.filter(u => u.role === 'patient').length;
    const doctors = users.filter(u => u.role === 'doctor').length;
    const nurses = users.filter(u => u.role === 'nurse').length;
    
    const pendingCount = document.getElementById('adminPendingCount');
    const reportCount = document.getElementById('adminReportCount');
    const staffCount = document.getElementById('adminStaffCount');
    const patientCount = document.getElementById('adminPatientCount');
    const apptCount = document.getElementById('adminApptCount');
    
    if (pendingCount) pendingCount.textContent = stats.pending_appointments || '0';
    if (reportCount) reportCount.textContent = '0';
    if (staffCount) staffCount.textContent = doctors + nurses;
    if (patientCount) patientCount.textContent = patients;
    if (apptCount) apptCount.textContent = stats.appointments || '0';
}

async function renderStaffReports() {
    const reportsDiv = document.getElementById('adminReportsList');
    if (!reportsDiv) return;
    
    const result = await apiCall('get_staff_reports');
    const reports = result.reports || [];
    
    if (reports.length === 0) {
        reportsDiv.innerHTML = '<p class="text-muted">No staff reports submitted yet.</p>';
        return;
    }
    
    reportsDiv.innerHTML = reports.map(report => `
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">${report.full_name} (${report.role})</h6>
                        <small class="text-muted">Week of: ${new Date(report.week_starting).toLocaleDateString()}</small>
                        <p class="mt-2 mb-1"><strong>Activities:</strong> ${report.activities}</p>
                        <p class="mb-1"><strong>Patients Attended:</strong> ${report.patients_attended}</p>
                        <p class="mb-1"><strong>Challenges:</strong> ${report.challenges}</p>
                        ${report.feedback ? `<p class="mt-2"><strong>Feedback:</strong> ${report.feedback}</p>` : ''}
                    </div>
                    <span class="badge bg-${report.status === 'pending' ? 'warning' : report.status === 'reviewed' ? 'success' : 'danger'}">${report.status}</span>
                </div>
                ${report.status === 'pending' ? `
                    <div class="mt-3">
                        <textarea id="feedback_${report.id}" class="form-control mb-2" rows="2" placeholder="Provide feedback..."></textarea>
                        <button class="btn btn-sm btn-success" onclick="reviewStaffReport('${report.id}', 'reviewed')">Approve</button>
                        <button class="btn btn-sm btn-danger" onclick="reviewStaffReport('${report.id}', 'rejected')">Request Changes</button>
                    </div>
                ` : ''}
            </div>
        </div>
    `).join('');
}

async function reviewStaffReport(reportId, status) {
    const feedback = document.getElementById(`feedback_${reportId}`)?.value || '';
    
    const result = await apiCall('review_staff_report', {
        report_id: reportId,
        feedback: feedback,
        status: status
    });
    
    if (result.success) {
        alert('Report reviewed successfully!');
        renderStaffReports();
    } else {
        alert('Failed to review report');
    }
}

function addNewPlan() {
    alert('New subscription plan added successfully!');
}

function saveSystemSettings() {
    alert('System settings saved successfully!');
}

// ==================== STAFF REPORT FUNCTIONS ====================
async function submitStaffReport() {
    const weekStarting = document.getElementById('reportWeek')?.value;
    const activities = document.getElementById('reportActivities')?.value;
    const patientsAttended = document.getElementById('reportPatientsAttended')?.value;
    const challenges = document.getElementById('reportChallenges')?.value;
    
    if (!weekStarting || !activities || !patientsAttended) {
        alert('Please fill all required fields');
        return;
    }
    
    const result = await apiCall('submit_staff_report', {
        user_email: currentUser.email,
        week_starting: weekStarting,
        activities: activities,
        patients_attended: patientsAttended,
        challenges: challenges
    });
    
    if (result.success) {
        alert('Weekly report submitted successfully! Thank you for your contribution.');
        document.getElementById('reportWeek').value = '';
        document.getElementById('reportActivities').value = '';
        document.getElementById('reportPatientsAttended').value = '';
        document.getElementById('reportChallenges').value = '';
        showTab('home');
    } else {
        alert('Failed to submit report');
    }
}

// ==================== CHATBOT FUNCTIONS ====================
const chatResponses = {
    en: { default: "I'm here to help! How can I assist you today?", symptoms: "Please describe your symptoms in detail so I can provide better guidance.", appointment: "I can help you book an appointment. Please go to the Appointments section.", medicine: "Please tell me the name of the medication you have questions about.", emergency: "🚨 This appears to be an emergency. Please call the emergency hotline immediately: +256701111111", welcome: "Welcome to ExMed AI Hospital Assistant! How can I help you today?", goodbye: "Thank you for using ExMed AI Assistant. Stay healthy!" },
    lug: { default: "Nze nno okukunnyonnyeza! Kiki ekikumusesamu?", symptoms: "Njogera ku bubonero bwo, nkusabwe otegeeze ebirumasa.", appointment: "Nsobola okukuyambako okusabawo olulayo. Genda mu Appointments.", medicine: "Njogera ku ddagala ki?", emergency: "🚨 Kino kya mangu! Kuba ku namba: +256701111111", welcome: "Tukusanyukidde! Nze AI Assistant wa ExMed." },
    sw: { default: "Niko hapa kukusaidia! Ninafanya nini?", symptoms: "Tafadhali elezea dalili zako kwa undani.", appointment: "Naweza kukusaidia kupanga miadi. Nenda kwenye sehemu ya Appointments.", medicine: "Tafadhali niambie jina la dawa.", emergency: "🚨 Hili ni dharura! Piga simu: +256701111111", welcome: "Karibu kwa Msaidizi wa AI wa ExMed!" },
    ate: { default: "Asoru akonyo! Kiki ekikumusesamu?", symptoms: "Itesite ikak orokori yok, ikak ipak.", appointment: "Aiang akonyo ikak ekwa. Itesite ikak 'Appointments'.", medicine: "Itesite ikak ecik ngaria.", emergency: "🚨 Kiyare! Ikak ikut: +256701111111", welcome: "Asoru ikak! Ange AI Assistant ExMed." }
};

function setChatLang(lang) {
    chatLanguage = lang;
    document.getElementById('chatLangSelect').value = lang;
}

function sendChatMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    if (!message) return;
    const chatWindow = document.getElementById('chatWindow');
    chatWindow.innerHTML += `<div class="text-end mb-2"><div class="alert alert-primary d-inline-block">${escapeHtml(message)}</div></div>`;
    const responses = chatResponses[chatLanguage] || chatResponses['en'];
    
    let reply = responses.default;
    const lowerMsg = message.toLowerCase();
    if (lowerMsg.includes('symptom') || lowerMsg.includes('fever') || lowerMsg.includes('pain')) {
        reply = responses.symptoms;
    } else if (lowerMsg.includes('appointment') || lowerMsg.includes('book')) {
        reply = responses.appointment;
    } else if (lowerMsg.includes('medicine') || lowerMsg.includes('drug') || lowerMsg.includes('pill')) {
        reply = responses.medicine;
    } else if (lowerMsg.includes('emergency') || lowerMsg.includes('urgent') || lowerMsg.includes('help')) {
        reply = responses.emergency;
    } else if (lowerMsg.includes('bye') || lowerMsg.includes('goodbye')) {
        reply = responses.goodbye;
    }
    
    setTimeout(() => {
        chatWindow.innerHTML += `<div class="mb-2"><div class="alert alert-secondary d-inline-block">${reply}</div></div>`;
        chatWindow.scrollTop = chatWindow.scrollHeight;
    }, 500);
    input.value = '';
    chatWindow.scrollTop = chatWindow.scrollHeight;
}

function quickChat(type) {
    const messages = { symptoms: 'I have symptoms to report', appointment: 'I need to book an appointment', medicine: 'Tell me about medications', emergency: 'This is an emergency' };
    const input = document.getElementById('chatInput');
    if (input) input.value = messages[type] || '';
    sendChatMessage();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ==================== LANGUAGE FUNCTIONS ====================
const TRANSLATIONS = {
    en: { alerts: 'Real-Time Alerts', alerts_desc: 'Instant notifications', security: 'Military-Grade Security', security_desc: 'AES-256 encryption', offline: 'Offline First', offline_desc: 'Full functionality offline', get_started: 'Get Started', sign_in: 'Sign In', continue: 'Continue' },
    lug: { alerts: 'Obutaka Obuwandiike', alerts_desc: 'Okukakasa ebyawandiiko', security: 'Obutonde Obwawamu', security_desc: 'AES-256 etekateeka', offline: 'Tekikwata ku Intaneeti', offline_desc: 'Ebikozesebwa bino bisobola', get_started: 'Tandika', sign_in: 'Wetegekere', continue: 'Genda Wansi' },
    sw: { alerts: 'Arifa za Muda', alerts_desc: 'Arifa za papo', security: 'Usalama wa Kiwango cha Juu', security_desc: 'AES-256 inalinda', offline: 'Kazi Bila Mtandao', offline_desc: 'Inafanya kazi bila', get_started: 'Anza', sign_in: 'Ingia', continue: 'Endelea' },
    ate: { alerts: 'Ate Obutindo', alerts_desc: 'Obubaka bwomubiri', security: 'Ate Obusinge', security_desc: 'AES-256 etekateeka', offline: 'Ate Offline', offline_desc: 'Ebyokukola tebijja', get_started: 'Tangisa', sign_in: 'Wete', continue: 'Kakasa' }
};

function setLang(lang) {
    if (!TRANSLATIONS[lang]) lang = 'en';
    localStorage.setItem('exmed_lang', lang);
    document.querySelectorAll('[data-t]').forEach(el => {
        const key = el.getAttribute('data-t');
        const txt = (TRANSLATIONS[lang] && TRANSLATIONS[lang][key]) || TRANSLATIONS['en'][key] || el.textContent;
        el.textContent = txt;
    });
    document.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
    const btn = document.getElementById('lang-' + lang);
    if (btn) btn.classList.add('active');
}

// ==================== STUB FUNCTIONS FOR COMPATIBILITY ====================
function goBackToPatients() { showTab('doctor-patients'); }
function loadMyReports() { }
function renderDoctorNotifications() { }
function loadReportPatientInfo() { }
function toggleReportDetail() { }
function cancelResume() { location.reload(); }
function attemptResume() { location.reload(); }

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', async () => {
    const stored = localStorage.getItem('exmed_lang') || 'en';
    setLang(stored);
    
    const rememberedEmail = localStorage.getItem('exmed_remembered_user');
    if (rememberedEmail) {
        const emailField = document.getElementById('email');
        if (emailField) emailField.value = rememberedEmail;
        const rememberCheckbox = document.getElementById('rememberMe');
        if (rememberCheckbox) rememberCheckbox.checked = true;
    }
    
    const sessionResult = await apiCall('check_session');
    if (sessionResult.success && sessionResult.user) {
        await showDashboard();
    }
    
    // Set default date for staff report to current week's Monday
    const reportWeekInput = document.getElementById('reportWeek');
    if (reportWeekInput) {
        const today = new Date();
        const day = today.getDay();
        const diff = today.getDate() - day + (day === 0 ? -6 : 1);
        const monday = new Date(today.setDate(diff));
        reportWeekInput.value = monday.toISOString().split('T')[0];
    }
});