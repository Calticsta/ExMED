<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    header('Location: index.php');
    exit();
}

$currentUser = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Dashboard - ExMed</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
  body { background: #f8f9fa; }
  .dash-header { background: linear-gradient(135deg, #28a745, #1e7e34); color: white; padding: 25px; border-radius: 16px 16px 0 0; }
  .nav-link { color: #28a745 !important; font-weight: 600; }
  .nav-link.active { background: #28a745 !important; color: white !important; border-radius: 12px; }
  .record-item { cursor: pointer; transition: all 0.3s ease; }
  .record-item:hover { background-color: #e8f5e9; }
</style>
</head>
<body>

<div class="container-fluid mt-3">
  <div class="dash-header text-center mb-4">
    <h2>ExMed Doctor Portal</h2>
    <p>Welcome, Dr. <?php echo htmlspecialchars($currentUser['name']); ?> | <a href="logout.php" class="text-white text-decoration-underline">Logout</a></p>
  </div>

  <!-- Doctor Navigation -->
  <ul class="nav nav-pills justify-content-center flex-wrap mb-4 gap-2">
    <li class="nav-item"><a class="nav-link active" href="#" onclick="event.preventDefault(); showTab('home')"><i class="fas fa-home"></i> Home</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('patients')"><i class="fas fa-users"></i> My Patients</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('appointments')"><i class="fas fa-calendar-check"></i> Schedule</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('prescribe')"><i class="fas fa-prescription-bottle"></i> Prescribe</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('diagnose')"><i class="fas fa-stethoscope"></i> Diagnose</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('orders')"><i class="fas fa-flask"></i> Lab Orders</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('messages')"><i class="fas fa-comments"></i> Messages</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('report')"><i class="fas fa-pen-fancy"></i> Weekly Report</a></li>
  </ul>

  <!-- Home Tab -->
  <div id="tab-home" class="row g-4">
    <div class="col-md-3"><div class="card text-center p-4"><i class="fas fa-users fa-3x text-primary mb-3"></i><h5>My Patients</h5><button class="btn btn-primary" onclick="showTab('patients')">View</button></div></div>
    <div class="col-md-3"><div class="card text-center p-4"><i class="fas fa-calendar-check fa-3x text-success mb-3"></i><h5>Appointments</h5><button class="btn btn-success" onclick="showTab('appointments')">Schedule</button></div></div>
    <div class="col-md-3"><div class="card text-center p-4"><i class="fas fa-prescription-bottle fa-3x text-info mb-3"></i><h5>Prescribe</h5><button class="btn btn-info" onclick="showTab('prescribe')">Write</button></div></div>
    <div class="col-md-3"><div class="card text-center p-4"><i class="fas fa-comments fa-3x text-warning mb-3"></i><h5>Messages</h5><button class="btn btn-warning" onclick="showTab('messages')">Chat</button></div></div>
  </div>

  <!-- Patients Tab -->
  <div id="tab-patients" class="d-none card p-4">
    <h4><i class="fas fa-users"></i> My Patients</h4>
    <input type="text" id="patientSearch" class="form-control mb-3" placeholder="Search patients..." oninput="filterPatients()">
    <div id="patientsList" class="row g-3"></div>
  </div>

  <!-- Appointments Tab -->
  <div id="tab-appointments" class="d-none card p-4">
    <h4><i class="fas fa-calendar-check"></i> My Schedule</h4>
    <div id="appointmentsList" class="row g-3"></div>
  </div>

  <!-- Prescribe Tab -->
  <div id="tab-prescribe" class="d-none card p-4">
    <h4><i class="fas fa-prescription-bottle"></i> Prescribe Medication</h4>
    <div class="row g-3">
      <div class="col-md-6"><label>Select Patient</label><select id="prescribePatient" class="form-select"></select></div>
      <div class="col-md-6"><label>Medication</label><select id="prescribeMedicine" class="form-select"></select></div>
      <div class="col-md-4"><label>Dosage</label><input type="text" id="dosage" class="form-control" placeholder="e.g., 500mg"></div>
      <div class="col-md-4"><label>Frequency</label><input type="text" id="frequency" class="form-control" placeholder="e.g., 3x daily"></div>
      <div class="col-md-4"><label>Duration (days)</label><input type="number" id="duration" class="form-control"></div>
      <div class="col-12"><label>Instructions</label><textarea id="instructions" class="form-control" rows="2"></textarea></div>
      <div class="col-12"><button class="btn btn-primary w-100" onclick="submitPrescription()">Submit Prescription</button></div>
    </div>
  </div>

  <!-- Diagnose Tab -->
  <div id="tab-diagnose" class="d-none card p-4">
    <h4><i class="fas fa-stethoscope"></i> Diagnose Patient</h4>
    <div class="row g-3">
      <div class="col-md-6"><label>Select Patient</label><select id="diagnosePatient" class="form-select"></select></div>
      <div class="col-md-6"><label>Diagnosis</label><select id="diagnosis" class="form-select"></select></div>
      <div class="col-12"><label>Notes</label><textarea id="diagnosisNotes" class="form-control" rows="3"></textarea></div>
      <div class="col-md-6"><label>Severity</label><select id="severity" class="form-select"><option>Mild</option><option>Moderate</option><option>Severe</option></select></div>
      <div class="col-12"><button class="btn btn-primary w-100" onclick="submitDiagnosis()">Save Diagnosis</button></div>
    </div>
  </div>

  <!-- Orders Tab -->
  <div id="tab-orders" class="d-none card p-4">
    <h4><i class="fas fa-flask"></i> Order Lab Tests</h4>
    <div class="row g-3">
      <div class="col-md-6"><label>Select Patient</label><select id="orderPatient" class="form-select"></select></div>
      <div class="col-md-6"><label>Test Type</label><select id="testType" class="form-select"><option>Full Blood Count</option><option>Malaria Test</option><option>Blood Sugar</option><option>Urinalysis</option></select></div>
      <div class="col-12"><label>Clinical Indication</label><textarea id="indication" class="form-control" rows="2"></textarea></div>
      <div class="col-12"><button class="btn btn-primary w-100" onclick="orderLabTest()">Order Test</button></div>
    </div>
  </div>

  <!-- Messages Tab -->
  <div id="tab-messages" class="d-none card p-4">
    <h4><i class="fas fa-comments"></i> Messages</h4>
    <div class="row">
      <div class="col-md-4"><div id="conversationsList" class="list-group"></div></div>
      <div class="col-md-8"><div id="messageThread" class="border rounded p-3" style="height: 400px; overflow-y: auto;"></div><div class="input-group mt-2"><input type="text" id="messageInput" class="form-control" placeholder="Type message..."><button class="btn btn-primary" onclick="sendMessage()">Send</button></div></div>
    </div>
  </div>

  <!-- Report Tab -->
  <div id="tab-report" class="d-none card p-4">
    <h4><i class="fas fa-pen-fancy"></i> Weekly Report</h4>
    <div class="row g-3">
      <div class="col-12"><label>Week Starting</label><input type="date" id="reportWeek" class="form-control"></div>
      <div class="col-12"><label>Activities</label><textarea id="activities" class="form-control" rows="3"></textarea></div>
      <div class="col-md-6"><label>Patients Attended</label><input type="number" id="patientsAttended" class="form-control"></div>
      <div class="col-md-6"><label>Challenges</label><input type="text" id="challenges" class="form-control"></div>
      <div class="col-12"><button class="btn btn-primary w-100" onclick="submitReport()">Submit Report</button></div>
    </div>
  </div>
</div>

<script>
const currentUser = <?php echo json_encode($currentUser); ?>;

async function apiCall(action, data = {}) {
    const response = await fetch(`api_handler.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(data)
    });
    return await response.json();
}

function showTab(tabName) {
    document.querySelectorAll('[id^="tab-"]').forEach(el => el.classList.add('d-none'));
    document.getElementById('tab-' + tabName).classList.remove('d-none');
    if (tabName === 'patients') loadPatients();
    else if (tabName === 'appointments') loadAppointments();
    else if (tabName === 'prescribe') loadPrescribeData();
    else if (tabName === 'diagnose') loadDiagnoseData();
    else if (tabName === 'orders') loadOrdersData();
    else if (tabName === 'messages') loadConversations();
}

async function loadPatients() {
    const result = await apiCall('get_doctor_patients', { email: currentUser.email });
    const patients = result.patients || [];
    const div = document.getElementById('patientsList');
    if (patients.length === 0) div.innerHTML = '<p class="text-muted">No patients yet.</p>';
    else div.innerHTML = patients.map(p => `
        <div class="col-md-6">
            <div class="card p-3 record-item" onclick="viewPatient('${p.email}')">
                <h6>${p.full_name}</h6>
                <small>${p.email} | Visits: ${p.visit_count || 0}</small>
            </div>
        </div>
    `).join('');
}

async function loadAppointments() {
    const result = await apiCall('get_appointments', { email: currentUser.email, role: 'doctor' });
    const appointments = result.appointments || [];
    const div = document.getElementById('appointmentsList');
    if (appointments.length === 0) div.innerHTML = '<p class="text-muted">No appointments.</p>';
    else div.innerHTML = appointments.map(a => `
        <div class="col-md-6">
            <div class="card p-3">
                <h6>${a.patient_name}</h6>
                <p>${a.appointment_date} at ${a.appointment_time}</p>
                <p class="small">${a.reason || 'No reason'}</p>
                <span class="badge bg-${a.status === 'pending' ? 'warning' : 'success'}">${a.status}</span>
                ${a.status === 'pending' ? `<button class="btn btn-sm btn-success mt-2" onclick="confirmAppt('${a.id}')">Confirm</button>` : ''}
            </div>
        </div>
    `).join('');
}

async function confirmAppt(id) {
    await apiCall('confirm_appointment', { appointment_id: id });
    loadAppointments();
}

async function loadPrescribeData() {
    const patients = await apiCall('get_patients');
    const select = document.getElementById('prescribePatient');
    select.innerHTML = '<option value="">Select Patient</option>' + (patients.patients || []).map(p => `<option value="${p.email}">${p.name}</option>`).join('');
    
    const meds = ['Paracetamol 500mg', 'Amoxicillin 500mg', 'Artemether/Lumefantrine', 'Metformin 500mg', 'Atenolol 50mg'];
    document.getElementById('prescribeMedicine').innerHTML = meds.map(m => `<option value="${m}">${m}</option>`).join('');
}

async function submitPrescription() {
    const result = await apiCall('submit_prescription', {
        patient_email: document.getElementById('prescribePatient').value,
        doctor_email: currentUser.email,
        medication: document.getElementById('prescribeMedicine').value,
        dosage: document.getElementById('dosage').value,
        frequency: document.getElementById('frequency').value,
        duration: document.getElementById('duration').value,
        instructions: document.getElementById('instructions').value
    });
    alert(result.success ? 'Prescription submitted!' : 'Failed to submit');
}

async function loadDiagnoseData() {
    const patients = await apiCall('get_patients');
    document.getElementById('diagnosePatient').innerHTML = '<option value="">Select Patient</option>' + (patients.patients || []).map(p => `<option value="${p.email}">${p.name}</option>`).join('');
    
    const diseases = ['Malaria', 'Typhoid', 'Hypertension', 'Diabetes', 'Pneumonia', 'UTI'];
    document.getElementById('diagnosis').innerHTML = diseases.map(d => `<option value="${d}">${d}</option>`).join('');
}

async function submitDiagnosis() {
    const result = await apiCall('submit_diagnosis', {
        patient_email: document.getElementById('diagnosePatient').value,
        doctor_email: currentUser.email,
        diagnosis: document.getElementById('diagnosis').value,
        notes: document.getElementById('diagnosisNotes').value,
        severity: document.getElementById('severity').value
    });
    alert(result.success ? 'Diagnosis saved!' : 'Failed to save');
}

async function loadOrdersData() {
    const patients = await apiCall('get_patients');
    document.getElementById('orderPatient').innerHTML = '<option value="">Select Patient</option>' + (patients.patients || []).map(p => `<option value="${p.email}">${p.name}</option>`).join('');
}

async function orderLabTest() {
    const result = await apiCall('order_lab_test', {
        patient_email: document.getElementById('orderPatient').value,
        doctor_email: currentUser.email,
        test_type: document.getElementById('testType').value,
        indication: document.getElementById('indication').value
    });
    alert(result.success ? 'Test ordered!' : 'Failed to order');
}

let currentChatWith = null;
async function loadConversations() {
    const result = await apiCall('get_messages', { email: currentUser.email });
    const convos = result.messages || [];
    const div = document.getElementById('conversationsList');
    div.innerHTML = convos.map(c => `<a href="#" class="list-group-item list-group-item-action" onclick="loadChat('${c.other_user_email}')">${c.other_user_name}<br><small>${c.last_message?.substring(0, 30)}</small></a>`).join('');
}

async function loadChat(email) {
    currentChatWith = email;
    const result = await apiCall('get_messages', { email: currentUser.email, with_email: email });
    const messages = result.messages || [];
    const thread = document.getElementById('messageThread');
    thread.innerHTML = messages.map(m => `
        <div class="mb-2 text-${m.sender_email === currentUser.email ? 'end' : 'start'}">
            <div class="d-inline-block p-2 rounded bg-${m.sender_email === currentUser.email ? 'primary text-white' : 'secondary'}">
                ${m.message}<br><small class="opacity-75">${new Date(m.created_at).toLocaleTimeString()}</small>
            </div>
        </div>
    `).join('');
    document.getElementById('messageInput').disabled = false;
    thread.scrollTop = thread.scrollHeight;
}

async function sendMessage() {
    const msg = document.getElementById('messageInput').value;
    if (!msg || !currentChatWith) return;
    await apiCall('send_message', { sender_email: currentUser.email, receiver_email: currentChatWith, message: msg });
    document.getElementById('messageInput').value = '';
    loadChat(currentChatWith);
}

async function submitReport() {
    const result = await apiCall('submit_staff_report', {
        user_email: currentUser.email,
        week_starting: document.getElementById('reportWeek').value,
        activities: document.getElementById('activities').value,
        patients_attended: document.getElementById('patientsAttended').value,
        challenges: document.getElementById('challenges').value
    });
    alert(result.success ? 'Report submitted!' : 'Failed to submit');
}

function viewPatient(email) {
    alert('Patient details feature coming soon. Email: ' + email);
}

function filterPatients() {
    const search = document.getElementById('patientSearch').value.toLowerCase();
    document.querySelectorAll('#patientsList .col-md-6').forEach(card => {
        card.style.display = card.textContent.toLowerCase().includes(search) ? '' : 'none';
    });
}

// Set default week date
document.getElementById('reportWeek').value = new Date().toISOString().split('T')[0];
showTab('home');
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>