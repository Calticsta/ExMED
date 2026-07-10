<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header('Location: index.php');
    exit();
}

$currentUser = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ExMed - Patient Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/crypto-js@4.2.0/crypto-js.min.js"></script>
<link rel="manifest" href="manifest.json">

<style>
  body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; }
  .logo { width: 80px; margin-bottom: 20px; }
  .dash-header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 25px; border-radius: 16px 16px 0 0; }
  .nav-link { color: #007bff !important; font-weight: 600; padding: 10px 20px !important; }
  .nav-link.active { background: #007bff !important; color: white !important; border-radius: 12px; }
  .record-item { cursor: pointer; transition: all 0.3s ease; border-left: 4px solid transparent; }
  .record-item:hover { background-color: #e7f3ff; border-left-color: #007bff; }
  .appointment-card { border-left: 4px solid #007bff; }
  .appointment-card.completed { border-left-color: #28a745; }
  .appointment-card.cancelled { border-left-color: #dc3545; opacity: 0.7; }
  .subscription-badge { background: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; }
  .btn-download-doc { background: #28a745; }
  .btn-download-doc:hover { background: #218838; }
  .dept-card { border: 1px solid #ddd; border-radius: 8px; padding: 12px; margin: 8px 0; cursor: pointer; transition: all 0.2s; }
  .dept-card:hover { background: #e7f3ff; border-color: #007bff; }
  .dept-header { font-weight: 600; color: #007bff; }
  .prescription-badge { background: #e8f5e9; color: #2e7d32; padding: 6px 12px; border-radius: 6px; font-size: 0.9rem; }
  .home-dashboard-card { cursor: pointer; transition: all 0.3s ease; }
  .home-dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
  .home-dashboard-card .btn { transition: all 0.3s ease; }
  .home-dashboard-card:hover .btn { transform: scale(1.05); }
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="container-fluid mt-3">
  <div class="dash-header text-center mb-4">
    <h2>ExMed</h2>
    <p><span id="userName"><?php echo htmlspecialchars($currentUser['name']); ?></span> | <a href="logout.php" class="text-white text-decoration-underline">Logout</a></p>
  </div>

  <!-- PATIENT NAVIGATION - ORIGINAL -->
  <ul class="nav nav-pills justify-content-center flex-wrap mb-4 gap-2" id="patientNav">
    <li class="nav-item"><a class="nav-link active" href="#" onclick="event.preventDefault(); showTab('home')"><i class="fas fa-home"></i> Home</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('appointments')"><i class="fas fa-calendar-check"></i> Appointments</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('prescriptions')"><i class="fas fa-pills"></i> Prescriptions</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('chatbot')"><i class="fas fa-robot"></i> AI Chat</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('subscription')"><i class="fas fa-credit-card"></i> Subscription<br><small class="text-muted">Basic: 10K | Premium: 17K<br>7-Day Free Trial</small></a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('records')"><i class="fas fa-file-medical"></i> Records</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('insurance')"><i class="fas fa-shield-alt"></i> Insurance</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('profile')"><i class="fas fa-user"></i> Profile</a></li>
  </ul>

  <!-- Home Tab - ORIGINAL -->
  <div id="tab-home" class="row g-4">
    <div id="patientHome" class="col-12 row g-4">
      <div class="col-md-3"><div class="card h-100 text-center p-4 home-dashboard-card" style="cursor:pointer;" onclick="showTab('appointments')"><i class="fas fa-calendar-check fa-3x text-primary mb-3"></i><h5>Book Appointment</h5><button class="btn btn-primary" onclick="event.stopPropagation(); showTab('appointments')">Book Now</button></div></div>
      <div class="col-md-3"><div class="card h-100 text-center p-4 home-dashboard-card" style="cursor:pointer;" onclick="showTab('prescriptions')"><i class="fas fa-pills fa-3x text-success mb-3"></i><h5>My Prescriptions</h5><button class="btn btn-success" onclick="event.stopPropagation(); showTab('prescriptions')">View</button></div></div>
      <div class="col-md-3"><div class="card h-100 text-center p-4 home-dashboard-card" style="cursor:pointer;" onclick="showTab('records')"><i class="fas fa-file-medical fa-3x text-warning mb-3"></i><h5>My Records</h5><button class="btn btn-warning" onclick="event.stopPropagation(); showTab('records')">View</button></div></div>
      <div class="col-md-3"><div class="card h-100 text-center p-4 home-dashboard-card" style="cursor:pointer;" onclick="showTab('chatbot')"><i class="fas fa-robot fa-3x text-info mb-3"></i><h5>AI Assistant</h5><button class="btn btn-info" onclick="event.stopPropagation(); showTab('chatbot')">Chat Now</button></div></div>
    </div>
    <div class="col-12 mt-4">
      <div class="card p-4">
        <h4 class="mb-4"><i class="fas fa-credit-card text-primary me-2"></i> Subscription Plans</h4>
        <p class="text-muted small mb-4">Choose a plan to access premium healthcare features. All prices in Ugandan Shillings (UGX). Start with a 7-day free trial!</p>
        <div class="row g-4" id="homeSubscriptionPlans"></div>
      </div>
    </div>
  </div>

  <!-- Appointments Tab - ORIGINAL -->
  <div id="tab-appointments" class="d-none card p-4">
    <h4>Manage Appointments</h4>
    <div class="card p-3 mb-4 bg-light">
      <h6>📅 Schedule New Appointment</h6>
      <div class="row g-2">
        <div class="col-md-6">
          <label class="form-label">Select Doctor</label>
          <select id="apptDoctor" class="form-select mb-2" onchange="updateDoctorDetails()">
            <option value="">-- Choose a doctor --</option>
          </select>
          <div id="doctorInfoDisplay" class="small text-muted mt-1"></div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Department</label>
          <input type="text" id="apptDepartment" class="form-control" readonly placeholder="Auto-filled">
        </div>
      </div>
      <div class="row g-2 mt-2">
        <div class="col-md-4"><label class="form-label">Date</label><input type="date" id="apptDate" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Time</label><input type="time" id="apptTime" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Type</label><select id="apptType" class="form-select"><option value="clinic">Clinic Visit</option><option value="tele">Teleconsult</option></select></div>
      </div>
      <div class="mt-2"><label class="form-label">Reason for Visit</label><textarea id="apptReason" class="form-control" rows="2" placeholder="Describe your symptoms..."></textarea></div>
      <button class="btn btn-primary mt-3" onclick="scheduleAppointment()"><i class="fas fa-calendar-plus"></i> Schedule Appointment</button>
    </div>
    <div class="card p-3 mb-4">
      <h6><i class="fas fa-building"></i> Hospital Department Directory</h6>
      <input type="text" id="deptSearch" class="form-control mb-3" placeholder="Search department..." oninput="filterDepartments()">
      <div id="departmentsList"></div>
    </div>
    <h6 class="mt-4">📋 Your Appointments</h6>
    <div id="appointmentsList" class="row g-3"></div>
  </div>

  <!-- Prescriptions Tab - ORIGINAL -->
  <div id="tab-prescriptions" class="d-none card p-4">
    <h4><i class="fas fa-pills"></i> My Prescriptions</h4>
    <div class="mb-4">
      <p class="text-muted">View and download your current and past prescriptions. All medications prescribed by your doctors.</p>
      <div class="alert alert-info" id="prescsEmptyMsg" style="display:none;">
        <i class="fas fa-info-circle"></i> No prescriptions available yet. Prescriptions from your doctors will appear here.
      </div>
    </div>
    <div id="prescriptionsList" class="row g-3"></div>
  </div>

  <!-- Profile Tab - ORIGINAL -->
  <div id="tab-profile" class="d-none card p-4">
    <h4>My Profile</h4>
    <div id="profileContent"></div>
  </div>

  <!-- AI Chatbot Tab - ORIGINAL -->
  <div id="tab-chatbot" class="d-none">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-robot"></i> AI Hospital Assistant</h4>
        <select id="chatLangSelect" class="form-select w-auto" onchange="setChatLang(this.value)">
          <option value="en">English</option>
          <option value="lug">Luganda</option>
          <option value="sw">Swahili</option>
          <option value="ate">Ateso</option>
        </select>
      </div>
      <p class="text-muted mb-3">Ask our AI assistant any health-related questions. Available 24/7.</p>
      <div id="chatWindow" class="border rounded p-3 mb-3" style="height: 400px; overflow-y: auto; background-color: #f8f9fa;">
        <div class="mb-3">
          <div class="alert alert-info d-inline-block" style="max-width: 80%;">
            Hi! I'm your AI Hospital Assistant. How can I help you today? Ask me about symptoms, medications, or appointment scheduling.
          </div>
        </div>
      </div>
      <div class="input-group">
        <input type="text" id="chatInput" class="form-control" placeholder="Type your question..." onkeypress="if(event.key==='Enter') sendChatMessage()">
        <button class="btn btn-primary" onclick="sendChatMessage()"><i class="fas fa-paper-plane"></i> Send</button>
      </div>
      <div class="mt-3 small text-muted">
        <p><strong>Quick Tips:</strong></p>
        <button class="btn btn-sm btn-outline-secondary me-2 mb-2" onclick="quickChat('symptoms')">🤒 Report Symptoms</button>
        <button class="btn btn-sm btn-outline-secondary me-2 mb-2" onclick="quickChat('appointment')">📅 Book Appointment</button>
        <button class="btn btn-sm btn-outline-secondary me-2 mb-2" onclick="quickChat('medicine')">💊 Medicine Info</button>
        <button class="btn btn-sm btn-outline-secondary me-2 mb-2" onclick="quickChat('emergency')">🚨 Emergency</button>
      </div>
    </div>
  </div>

  <!-- Subscription Tab - ORIGINAL -->
  <div id="tab-subscription" class="d-none card p-4">
    <h4><i class="fas fa-credit-card"></i> Subscription Plans</h4>
    <p class="text-muted mb-4">Choose the perfect plan for your healthcare needs. All plans include 24/7 AI support and access to medical records.<br><strong>🎁 NEW: Get 7 days free trial with any plan!</strong></p>
    
    <ul class="nav nav-tabs mb-3">
      <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#availablePlansTab" onclick="setTimeout(() => initSubscriptionPage(), 100)">Available Plans</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#mySubscriptionTab" onclick="setTimeout(() => populateSubscriptionHistory(), 100)">My Subscription</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#paymentHistoryTab" onclick="setTimeout(() => populatePaymentHistory(), 100)">Payment History</a></li>
    </ul>

    <div class="tab-content">
      <div id="availablePlansTab" class="tab-pane fade show active">
        <div id="subscriptionStatus" class="mb-4"></div>
        <div id="planSection" class="mb-4">
          <h5>Available Plans</h5>
          <p class="text-muted small">All prices in Ugandan Shillings (UGX). Click a plan to start your 7-day free trial.</p>
          <div class="row g-4 mb-4" id="subscriptionPlans"></div>
          <div id="trialInfoBox" class="alert alert-success mb-4 d-none">
            <i class="fas fa-gift"></i> <strong>7-Day Free Trial Active!</strong> Your trial ends on <span id="trialEndDate"></span>. After the trial, your selected plan will be charged at the regular monthly rate.
          </div>
        </div>
        <div id="paymentMethodsSection" class="d-none mb-4">
          <h5><i class="fas fa-wallet"></i> Payment Methods</h5>
          <p class="text-muted">Choose your preferred payment method to complete your subscription.</p>
          <div class="row g-3" id="paymentMethods"></div>
        </div>
      </div>
      <div id="mySubscriptionTab" class="tab-pane fade">
        <div class="card p-3 mb-3">
          <h6>Current Subscription Details</h6>
          <div id="currentSubDetails"></div>
        </div>
        <div class="card p-3">
          <h6>Subscription History</h6>
          <table class="table table-sm">
            <thead><tr><th>Date</th><th>Plan</th><th>Amount</th><th>Payment Method</th><th>Status</th><th>Expiry</th></tr></thead>
            <tbody id="subscriptionHistoryTable"></tbody>
          </table>
        </div>
      </div>
      <div id="paymentHistoryTab" class="tab-pane fade">
        <div class="card p-3 mb-3">
          <h6>Payment Summary</h6>
          <div class="row">
            <div class="col-md-3"><strong>Total Spent:</strong> <span id="totalSpentDisplay">UGX 0</span></div>
            <div class="col-md-3"><strong>Active Subscriptions:</strong> <span id="activeSubCount">0</span></div>
            <div class="col-md-3"><strong>Pending Payments:</strong> <span id="pendingPayCount">0</span></div>
            <div class="col-md-3"><strong>Expired Subscriptions:</strong> <span id="expiredSubCount">0</span></div>
          </div>
        </div>
        <table class="table table-sm">
          <thead><tr><th>Date</th><th>Plan</th><th>Amount</th><th>Payment Method</th><th>Status</th></tr></thead>
          <tbody id="paymentHistoryTable"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Records Tab - ORIGINAL -->
  <div id="tab-records" class="d-none card p-4">
    <h4><i class="fas fa-file-medical"></i> My Medical Records</h4>
    <p class="text-muted">Download and access your medical records.</p>
    <div class="row g-3 mb-4" id="medicalRecordsList"></div>
  </div>

  <!-- Insurance Tab - ORIGINAL -->
  <div id="tab-insurance" class="d-none card p-4">
    <h4><i class="fas fa-shield-alt"></i> Insurance & Documents</h4>
    <div class="mb-4">
      <h5>Your Coverage</h5>
      <div id="insurancePanel" class="mb-4"></div>
    </div>
    <div>
      <h5>Insurance Documents</h5>
      <p class="text-muted">Download your insurance documents and coverage details.</p>
      <div id="insuranceDocsList" class="list-group"></div>
    </div>
  </div>

  <!-- Subscription Selection Modal -->
  <div class="modal fade" id="subscriptionConfirmationModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-gift"></i> Confirm 7-Day Free Trial</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-success mb-3"><h6><i class="fas fa-check-circle"></i> You're getting 7 days free!</h6><p class="mb-0 small">No credit card charge during the trial. After 7 days, we'll start charging UGX <span id="confirmPlanPrice"></span>/month</p></div>
          <div class="card p-3 mb-3" style="background-color: #f8f9fa;"><h6 class="mb-2">Subscription Summary</h6><p class="mb-2"><strong>Plan:</strong> <span id="confirmPlanName"></span></p><p class="mb-2"><strong>Monthly Price:</strong> <span id="confirmPlanPrice"></span></p><p class="mb-2"><strong>Payment Method:</strong> <span id="confirmPaymentMethod"></span></p><p class="mb-0"><strong>Trial Duration:</strong> <span id="confirmTrialDays"></span> days - Free!</p></div>
          <div class="alert alert-info"><small><i class="fas fa-info-circle"></i> You can upgrade, downgrade, or cancel anytime. No hidden fees.</small></div>
          <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="agreeTerms" required><label class="form-check-label" for="agreeTerms">I agree to the terms and conditions. I understand that I will be charged after the 7-day trial ends.</label></div>
          <input type="hidden" id="selectedPlanId"><input type="hidden" id="selectedPaymentMethod">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-success" onclick="confirmSubscription()" id="confirmSubscriptionBtn"><i class="fas fa-check"></i> Start Free Trial</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const currentUser = <?php echo json_encode($currentUser); ?>;
let subscriptionPlans = [
    { id: 'basic', name: 'Basic Plan', icon: 'fas fa-star', color: 'info', price: '10,000', frequency: '/month', trialDays: 7, features: ['AI chat support', 'Doctor consultations', 'Medical records access', 'Appointment booking'], description: 'Essential healthcare access' },
    { id: 'premium', name: 'Premium Plan', icon: 'fas fa-crown', color: 'warning', price: '17,000', trialDays: 7, popular: true, features: ['Unlimited AI chat support', 'Video consultations', 'Download medical records', 'Priority support', '24/7 Emergency access'], description: 'Complete healthcare' }
];

// Department Data
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

// API calls
async function apiCall(action, data = {}) {
    try {
        const response = await fetch(`api_handler.php?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(data)
        });
        return await response.json();
    } catch (error) {
        return { success: false, message: 'Network error' };
    }
}

function showTab(tabName) {
    document.querySelectorAll('[id^="tab-"]').forEach(el => el.classList.add('d-none'));
    const tab = document.getElementById('tab-' + tabName);
    if (tab) tab.classList.remove('d-none');
    
    if (tabName === 'appointments') { renderAppointmentsList(); renderDepartments(); populateDoctorSelect(); }
    else if (tabName === 'prescriptions') renderPrescriptions();
    else if (tabName === 'records') renderMedicalRecords();
    else if (tabName === 'profile') renderProfile();
    else if (tabName === 'subscription') { initSubscriptionPage(); checkSubscriptionStatus(); populateSubscriptionHistory(); populatePaymentHistory(); }
    else if (tabName === 'insurance') initInsuranceTab();
}

async function populateDoctorSelect() {
    const result = await apiCall('get_doctors');
    const select = document.getElementById('apptDoctor');
    select.innerHTML = '<option value="">-- Choose a doctor --</option>' + 
        (result.doctors || []).map(d => `<option value="${d.email}" data-dept="${d.department || 'General'}">Dr. ${d.name}${d.specialization ? ' - ' + d.specialization : ''}</option>`).join('');
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
    
    const result = await apiCall('book_appointment', {
        patient_email: currentUser.email,
        doctor_email: doctorEmail,
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
    
    const result = await apiCall('get_appointments', { email: currentUser.email, role: 'patient' });
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
    
    // Home subscription plans
    const homePlansDiv = document.getElementById('homeSubscriptionPlans');
    if (homePlansDiv) {
        homePlansDiv.innerHTML = subscriptionPlans.map(plan => `
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

// Chatbot Functions
let chatLanguage = 'en';
const chatResponses = {
    en: { default: "I'm here to help! How can I assist you today?", symptoms: "Please describe your symptoms in detail so I can provide better guidance.", appointment: "I can help you book an appointment. Please go to the Appointments section.", medicine: "Please tell me the name of the medication you have questions about.", emergency: "🚨 This appears to be an emergency. Please call the emergency hotline immediately: +256701111111" },
    lug: { default: "Nze nno okukunnyonnyeza! Kiki ekikumusesamu?", symptoms: "Njogera ku bubonero bwo, nkusabwe otegeeze ebirumasa.", appointment: "Nsobola okukuyambako okusabawo olulayo. Genda mu Appointments.", medicine: "Njogera ku ddagala ki?", emergency: "🚨 Kino kya mangu! Kuba ku namba: +256701111111" },
    sw: { default: "Niko hapa kukusaidia! Ninafanya nini?", symptoms: "Tafadhali elezea dalili zako kwa undani.", appointment: "Naweza kukusaidia kupanga miadi. Nenda kwenye sehemu ya Appointments.", medicine: "Tafadhali niambie jina la dawa.", emergency: "🚨 Hili ni dharura! Piga simu: +256701111111" },
    ate: { default: "Asoru akonyo! Kiki ekikumusesamu?", symptoms: "Itesite ikak orokori yok, ikak ipak.", appointment: "Aiang akonyo ikak ekwa. Itesite ikak 'Appointments'.", medicine: "Itesite ikak ecik ngaria.", emergency: "🚨 Kiyare! Ikak ikut: +256701111111" }
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
    if (lowerMsg.includes('symptom') || lowerMsg.includes('fever') || lowerMsg.includes('pain')) reply = responses.symptoms;
    else if (lowerMsg.includes('appointment') || lowerMsg.includes('book')) reply = responses.appointment;
    else if (lowerMsg.includes('medicine') || lowerMsg.includes('drug') || lowerMsg.includes('pill')) reply = responses.medicine;
    else if (lowerMsg.includes('emergency') || lowerMsg.includes('urgent') || lowerMsg.includes('help')) reply = responses.emergency;
    
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

// Initialize
showTab('home');
</script>
</body>
</html>