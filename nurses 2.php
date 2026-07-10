<?php
// nurses.php - Nurse dashboard content
require_once 'config.php';

$currentUser = isset($_SESSION['user']) ? $_SESSION['user'] : null;
?>
<div id="nurse-dashboard">
    <!-- NURSE NAVIGATION -->
    <ul class="nav nav-pills justify-content-center flex-wrap mb-4 gap-2" id="nurseNav">
        <li class="nav-item"><a class="nav-link active" href="#" onclick="event.preventDefault(); showTab('nurse-home')"><i class="fas fa-home"></i> Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('nurse')"><i class="fas fa-heartbeat"></i> Nursing Station</a></li>
        <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('nurse-admissions')"><i class="fas fa-bed"></i> Admissions</a></li>
        <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('profile')"><i class="fas fa-user"></i> Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('staff-report')"><i class="fas fa-pen-fancy"></i> Weekly Report</a></li>
    </ul>

    <!-- NURSE HOME TAB -->
    <div id="tab-nurse-home" class="row g-4"><div class="col-12"><div class="card p-4"><h4><i class="fas fa-heartbeat"></i> Nursing Station</h4><p class="text-muted">Welcome to the nursing station. Manage patient admissions, record vital signs, and submit reports.</p></div></div></div>

    <!-- NURSE NURSING STATION TAB -->
    <div id="tab-nurse" class="d-none card p-4">
        <h4><i class="fas fa-heartbeat"></i> Vital Signs & Patient Care</h4>
        <div class="card p-3 mb-4">
            <label class="form-label">Select Patient</label>
            <select id="vitalPatientSelect" class="form-select mb-3" onchange="loadVitalPatientInfo()"><option value="">-- Choose Patient --</option></select>
            <div id="vitalPatientInfo" class="small text-muted mb-3"></div>
            <div class="row g-2">
                <div class="col-md-3"><label class="form-label">Temperature (°C)</label><input type="number" id="tempInput" class="form-control" step="0.1" min="35" max="42" placeholder="37.5"></div>
                <div class="col-md-3"><label class="form-label">Blood Pressure (mmHg)</label><input type="text" id="bpInput" class="form-control" placeholder="120/80"></div>
                <div class="col-md-3"><label class="form-label">Heart Rate (bpm)</label><input type="number" id="hrInput" class="form-control" min="40" max="200" placeholder="72"></div>
                <div class="col-md-3"><label class="form-label">O₂ Saturation (%)</label><input type="number" id="o2Input" class="form-control" min="0" max="100" placeholder="98"></div>
            </div>
            <div class="row g-2 mt-2">
                <div class="col-md-4"><label class="form-label">Weight (kg)</label><input type="number" id="weightInput" class="form-control" step="0.1" placeholder="70"></div>
                <div class="col-md-4"><label class="form-label">Height (cm)</label><input type="number" id="heightInput" class="form-control" step="0.1" placeholder="170"></div>
                <div class="col-md-4"><label class="form-label">BMI</label><input type="text" id="bmiDisplay" class="form-control" readonly placeholder="Auto-calculated"></div>
            </div>
            <button class="btn btn-success mt-3 w-100" onclick="recordVitals()">Record Vital Signs</button>
        </div>
    </div>

    <!-- NURSE ADMISSIONS TAB -->
    <div id="tab-nurse-admissions" class="d-none card p-4">
        <h4><i class="fas fa-bed"></i> Patient Admissions & Discharges</h4>
        <div class="card p-3 mb-4">
            <h6>New Patient Admission</h6>
            <div class="row g-2"><div class="col-md-4"><input type="text" id="admitName" class="form-control" placeholder="Patient Full Name"></div><div class="col-md-4"><input type="email" id="admitEmail" class="form-control" placeholder="Email"></div><div class="col-md-4"><input type="tel" id="admitPhone" class="form-control" placeholder="Phone"></div></div>
            <div class="row g-2 mt-2"><div class="col-md-6"><select id="admitReason" class="form-select"><option>-- Reason for Admission --</option><option>Emergency</option><option>Scheduled Case</option><option>Follow-up</option><option>Observation</option></select></div><div class="col-md-6"><input type="date" id="admitDate" class="form-control"></div></div>
            <button class="btn btn-primary mt-3 w-100" onclick="registerAdmission()">Register Admission</button>
        </div>
        <div id="admissionsList" class="row g-3"></div>
    </div>
</div>