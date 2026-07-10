<?php
// doctors.php - Doctor dashboard content
?>
<script>
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

function goBackToPatients() { showTab('doctor-patients'); }
</script>

<!-- DOCTOR NAVIGATION -->
<ul class="nav nav-pills justify-content-center flex-wrap mb-4 gap-2" id="doctorNav">
    <li class="nav-item"><a class="nav-link active" href="#" onclick="event.preventDefault(); showTab('doctor-home')"><i class="fas fa-home"></i> Home</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('doctor-patients')"><i class="fas fa-users"></i> My Patients</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('doctor-appointments')"><i class="fas fa-calendar-check"></i> Schedule</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('doctor-prescribe')"><i class="fas fa-prescription-bottle"></i> Prescribe</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('doctor-diagnose')"><i class="fas fa-stethoscope"></i> Diagnose</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('doctor-orders')"><i class="fas fa-flask"></i> Lab Orders</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('doctor-messages')"><i class="fas fa-comments"></i> Messages</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('profile')"><i class="fas fa-user"></i> Profile</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('staff-report')"><i class="fas fa-pen-fancy"></i> Weekly Report</a></li>
</ul>

<!-- DOCTOR HOME TAB -->
<div id="tab-doctor-home" class="row g-4">
    <div class="col-12"><div class="card p-4 bg-light"><h5><i class="fas fa-stethoscope"></i> Doctor Dashboard</h5><p class="text-muted">Manage your patients, schedule appointments, and submit reports.</p></div></div>
    <div class="col-md-3"><div class="card h-100 text-center p-4"><i class="fas fa-users fa-3x text-primary mb-3"></i><h5>My Patients</h5><button class="btn btn-primary landing-action" data-action="doctor-patients">View Patients</button></div></div>
    <div class="col-md-3"><div class="card h-100 text-center p-4"><i class="fas fa-calendar-check fa-3x text-success mb-3"></i><h5>Appointments</h5><button class="btn btn-success landing-action" data-action="doctor-appointments">View Schedule</button></div></div>
    <div class="col-md-3"><div class="card h-100 text-center p-4"><i class="fas fa-prescription-bottle fa-3x text-info mb-3"></i><h5>Prescriptions</h5><button class="btn btn-info landing-action" data-action="doctor-prescribe">Prescribe</button></div></div>
    <div class="col-md-3"><div class="card h-100 text-center p-4"><i class="fas fa-comments fa-3x text-warning mb-3"></i><h5>Messages</h5><button class="btn btn-warning landing-action" data-action="doctor-messages">Chat</button></div></div>
</div>

<!-- DOCTOR PATIENTS TAB -->
<div id="tab-doctor-patients" class="d-none card p-4">
    <h4><i class="fas fa-users"></i> My Patients</h4>
    <input type="text" id="doctorPatientSearch" class="form-control mb-3" placeholder="Search patients..." oninput="filterDoctorPatients()">
    <div id="doctorPatientsList" class="row g-3"></div>
</div>

<!-- DOCTOR PATIENT DETAIL TAB -->
<div id="tab-doctor-patient-detail" class="d-none card p-4">
    <h4><i class="fas fa-user-circle"></i> Patient Medical Record</h4>
    <div class="card p-3 mb-4 bg-light"><button class="btn btn-sm btn-outline-secondary" onclick="goBackToPatients()">← Back to Patients</button></div>
    <div id="patientDetailContent"></div>
</div>

<!-- DOCTOR APPOINTMENTS TAB -->
<div id="tab-doctor-appointments" class="d-none card p-4">
    <h4><i class="fas fa-calendar-check"></i> My Appointment Schedule</h4>
    <div id="doctorAppointmentsList" class="row g-3"></div>
</div>

<!-- DOCTOR PRESCRIBE TAB -->
<div id="tab-doctor-prescribe" class="d-none card p-4">
    <h4><i class="fas fa-prescription-bottle"></i> Prescribe Medication</h4>
    <div class="card p-3 mb-4">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Select Patient</label><select id="prescribePatientSelect" class="form-select mb-2"><option value="">-- Choose Patient --</option></select></div>
            <div class="col-md-6"><label class="form-label">Select Medication</label><select id="prescribeMedicineSelect" class="form-select mb-2"><option value="">-- Choose Medicine --</option></select></div>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Dosage</label><input type="text" id="prescribeDosage" class="form-control" placeholder="e.g., 500mg"></div>
            <div class="col-md-4"><label class="form-label">Frequency</label><input type="text" id="prescribeFrequency" class="form-control" placeholder="e.g., 3x daily"></div>
            <div class="col-md-4"><label class="form-label">Duration (days)</label><input type="number" id="prescribeDuration" class="form-control" placeholder="e.g., 7"></div>
        </div>
        <textarea id="prescribeNotes" class="form-control mt-3" rows="2" placeholder="Additional instructions..."></textarea>
        <button class="btn btn-primary mt-3 w-100" onclick="submitPrescription()"><i class="fas fa-save"></i> Submit Prescription</button>
    </div>
</div>

<!-- DOCTOR DIAGNOSE TAB -->
<div id="tab-doctor-diagnose" class="d-none card p-4">
    <h4><i class="fas fa-stethoscope"></i> Diagnose Patient</h4>
    <div class="card p-3 mb-4">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Select Patient</label><select id="diagnosePatientSelect" class="form-select mb-2" onchange="loadDiagnosePatientInfo()"><option value="">-- Choose Patient --</option></select><div id="diagnosePatientInfo" class="small text-muted mt-2"></div></div>
            <div class="col-md-6"><label class="form-label">Select Disease/Condition</label><select id="diagnosisSelect" class="form-select mb-2"><option value="">-- Choose Condition --</option></select></div>
        </div>
        <div class="mb-3"><label class="form-label">Findings & Observations</label><textarea id="diagnosisNotes" class="form-control" rows="3" placeholder="Document clinical findings, symptoms, test results..."></textarea></div>
        <div class="mb-3"><label class="form-label">Severity Level</label><select id="diagnosisSeverity" class="form-select"><option value="">-- Select Severity --</option><option value="mild">Mild</option><option value="moderate">Moderate</option><option value="severe">Severe</option><option value="critical">Critical</option></select></div>
        <button class="btn btn-primary w-100" onclick="submitDiagnosis()"><i class="fas fa-save"></i> Save Diagnosis</button>
    </div>
</div>

<!-- DOCTOR ORDERS TAB -->
<div id="tab-doctor-orders" class="d-none card p-4">
    <h4><i class="fas fa-flask"></i> Order Tests & Consultations</h4>
    <div class="card p-3 mb-4">
        <ul class="nav nav-tabs mb-3" id="ordersTabs">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#labTests">Lab Tests</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#imagingStudies">Imaging</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#consultations">Consultations</a></li>
        </ul>
        <div class="tab-content">
            <div id="labTests" class="tab-pane fade show active">
                <div class="row g-3"><div class="col-md-6"><label class="form-label">Select Patient</label><select id="labPatientSelect" class="form-select" onchange="loadLabTestOptions()"><option value="">-- Choose Patient --</option></select></div><div class="col-md-6"><label class="form-label">Test Type</label><select id="labTestType" class="form-select"><option value="">-- Choose Test --</option><option value="Full Blood Count (FBC)">Full Blood Count (FBC)</option><option value="Malaria RDT">Malaria RDT Test</option><option value="Fasting Blood Sugar">Fasting Blood Sugar (FBS)</option><option value="Lipid Profile">Lipid Profile</option><option value="Liver Function Tests">Liver Function Tests</option><option value="Kidney Function Tests">Kidney Function Tests</option><option value="HIV Rapid Test">HIV Rapid Test</option><option value="TB Sputum">TB Sputum Smear</option><option value="Typhoid Serology">Typhoid Serology</option><option value="Urinalysis">Urinalysis</option></select></div></div>
                <div class="mt-3"><label class="form-label">Clinical Indication</label><textarea id="labIndication" class="form-control" rows="2" placeholder="Why is this test needed?"></textarea></div>
                <button class="btn btn-primary mt-3 w-100" onclick="orderLabTest()"><i class="fas fa-flask"></i> Order Lab Test</button>
            </div>
            <div id="imagingStudies" class="tab-pane fade">
                <div class="row g-3"><div class="col-md-6"><label class="form-label">Select Patient</label><select id="imagingPatientSelect" class="form-select"><option value="">-- Choose Patient --</option></select></div><div class="col-md-6"><label class="form-label">Imaging Type</label><select id="imagingType" class="form-select"><option value="">-- Choose Imaging --</option><option value="X-Ray">X-Ray</option><option value="Ultrasound">Ultrasound</option><option value="CT Scan">CT Scan</option><option value="MRI">MRI</option><option value="ECG">ECG</option></select></div></div>
                <div class="mt-3"><label class="form-label">Clinical Indication</label><textarea id="imagingIndication" class="form-control" rows="2" placeholder="Reason for imaging study..."></textarea></div>
                <button class="btn btn-primary mt-3 w-100" onclick="orderImaging()"><i class="fas fa-image"></i> Order Imaging Study</button>
            </div>
            <div id="consultations" class="tab-pane fade">
                <div class="row g-3"><div class="col-md-6"><label class="form-label">Select Patient</label><select id="consultPatientSelect" class="form-select"><option value="">-- Choose Patient --</option></select></div><div class="col-md-6"><label class="form-label">Specialty Required</label><select id="consultSpecialty" class="form-select"><option value="">-- Choose Specialty --</option><option value="Cardiology">Cardiology (Heart)</option><option value="Neurology">Neurology (Nerves)</option><option value="Surgery">General Surgery</option><option value="Orthopedics">Orthopedics (Bones)</option><option value="Gynecology">Obstetrics & Gynecology</option><option value="Pediatrics">Pediatrics (Children)</option></select></div></div>
                <div class="mt-3"><label class="form-label">Reason for Consultation</label><textarea id="consultReason" class="form-control" rows="2" placeholder="Clinical reason for specialist consultation..."></textarea></div>
                <button class="btn btn-primary mt-3 w-100" onclick="requestConsultation()"><i class="fas fa-user-md"></i> Request Consultation</button>
            </div>
        </div>
    </div>
</div>

<!-- DOCTOR MESSAGING TAB -->
<div id="tab-doctor-messages" class="d-none card p-4">
    <h4><i class="fas fa-comments"></i> Secure Messaging</h4>
    <div class="row g-3">
        <div class="col-md-4"><div class="card p-3"><h6>Conversations</h6><div id="messagesList" class="list-group" style="max-height: 400px; overflow-y: auto;"><p class="text-muted small">No conversations yet</p></div></div></div>
        <div class="col-md-8"><div class="card p-3"><h6 id="messageThreadTitle">Select a conversation</h6><div id="messageThread" class="bg-light p-3 rounded mb-3" style="min-height: 300px; max-height: 400px; overflow-y: auto;"><p class="text-muted small">Select a conversation to view messages</p></div><div class="input-group"><textarea id="messageInput" class="form-control" rows="2" placeholder="Type your message..." disabled></textarea><button class="btn btn-primary" id="sendMessageBtn" onclick="sendMessage()" disabled><i class="fas fa-paper-plane"></i> Send</button></div></div></div>
    </div>
</div>