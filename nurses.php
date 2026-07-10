<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Nurse Dashboard - ExMed</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
  body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
  .dash-header { background: linear-gradient(135deg, #17a2b8, #0f6674); color: white; padding: 25px; border-radius: 16px 16px 0 0; }
  .nav-link { color: #17a2b8 !important; font-weight: 600; padding: 10px 20px !important; }
  .nav-link.active { background: #17a2b8 !important; color: white !important; border-radius: 12px; }
  .stats-card { transition: transform 0.3s ease; cursor: pointer; }
  .stats-card:hover { transform: translateY(-5px); }
  .vital-sign { font-size: 1.5rem; font-weight: bold; color: #17a2b8; }
  .patient-card { cursor: pointer; transition: all 0.3s ease; border-left: 4px solid #17a2b8; }
  .patient-card:hover { background-color: #e3f2fd; transform: translateX(5px); }
  .appointment-card { border-left: 4px solid #ffc107; }
  .appointment-card.confirmed { border-left-color: #28a745; }
  .appointment-card.pending { border-left-color: #ffc107; }
  .appointment-card.completed { border-left-color: #17a2b8; }
  .vital-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
  .quick-action-btn { margin: 5px; transition: all 0.3s ease; }
  .quick-action-btn:hover { transform: scale(1.05); }
  .message-sent { background: #17a2b8; color: white; border-radius: 18px 18px 4px 18px; padding: 10px 15px; display: inline-block; max-width: 80%; }
  .message-received { background: #e9ecef; color: #333; border-radius: 18px 18px 18px 4px; padding: 10px 15px; display: inline-block; max-width: 80%; }
  .conversation-item { cursor: pointer; transition: all 0.2s ease; }
  .conversation-item:hover { background-color: #e3f2fd; transform: translateX(3px); }
  .conversation-active { background-color: #e3f2fd; border-left: 3px solid #17a2b8; }
  .unread-badge { background: #dc3545; color: white; border-radius: 10px; padding: 2px 8px; font-size: 0.7rem; margin-left: 5px; }
  .patient-select-card { cursor: pointer; transition: all 0.2s ease; }
  .patient-select-card:hover { background-color: #e3f2fd; transform: translateX(5px); }
  .btn-pdf { background: #dc3545; color: white; border: none; }
  .btn-pdf:hover { background: #bb2d3b; color: white; }
</style>
</head>
<body>

<div class="container-fluid mt-3">
  <div class="dash-header text-center mb-4">
    <h2><i class="fas fa-user-nurse"></i> ExMed Nurse Portal</h2>
    <p>Welcome, Nurse <?php echo htmlspecialchars($currentUser['name']); ?> | <a href="logout.php" class="text-white text-decoration-underline">Logout</a></p>
  </div>

  <!-- Nurse Navigation -->
  <ul class="nav nav-pills justify-content-center flex-wrap mb-4 gap-2" id="nurseNav">
    <li class="nav-item"><a class="nav-link active" href="#" onclick="event.preventDefault(); showTab('home')"><i class="fas fa-home"></i> Home</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('patients')"><i class="fas fa-users"></i> Patients</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('appointments')"><i class="fas fa-calendar-check"></i> Appointments</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('vitals')"><i class="fas fa-heartbeat"></i> Vital Signs</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('records')"><i class="fas fa-file-medical"></i> Records</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('messages')"><i class="fas fa-comments"></i> Messages <span id="unreadMsgBadge" class="ms-1"></span></a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('report')"><i class="fas fa-pen-fancy"></i> Report</a></li>
  </ul>

  <!-- Home Tab - Dashboard -->
  <div id="tab-home" class="row g-4">
    <div class="col-12">
      <div class="row g-4">
        <div class="col-md-3">
          <div class="card stats-card text-center p-3" onclick="showTab('patients')">
            <i class="fas fa-users fa-3x text-info mb-2"></i>
            <h3 id="totalPatients">0</h3>
            <p class="text-muted">Total Patients</p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card stats-card text-center p-3" onclick="showTab('appointments')">
            <i class="fas fa-calendar-day fa-3x text-warning mb-2"></i>
            <h3 id="todayAppointments">0</h3>
            <p class="text-muted">Today's Appointments</p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card stats-card text-center p-3" onclick="showTab('vitals')">
            <i class="fas fa-heartbeat fa-3x text-danger mb-2"></i>
            <h3 id="vitalsToday">0</h3>
            <p class="text-muted">Vitals Recorded Today</p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card stats-card text-center p-3" onclick="showTab('report')">
            <i class="fas fa-chart-line fa-3x text-success mb-2"></i>
            <h3 id="pendingReports">0</h3>
            <p class="text-muted">Pending Reports</p>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-md-6">
      <div class="card p-3">
        <h5><i class="fas fa-chart-line"></i> Patient Vitals Overview</h5>
        <canvas id="vitalsChart" height="200"></canvas>
      </div>
    </div>
    
    <div class="col-md-6">
      <div class="card p-3">
        <h5><i class="fas fa-clock"></i> Recent Activities</h5>
        <div id="recentActivities" class="list-group"></div>
      </div>
    </div>
    
    <div class="col-12">
      <div class="card p-3">
        <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
        <div class="row g-2">
          <div class="col-md-2">
            <button class="btn btn-info w-100 quick-action-btn" onclick="showTab('vitals')">
              <i class="fas fa-heartbeat"></i> Record Vitals
            </button>
          </div>
          <div class="col-md-2">
            <button class="btn btn-success w-100 quick-action-btn" onclick="showTab('appointments')">
              <i class="fas fa-calendar-check"></i> View Schedule
            </button>
          </div>
          <div class="col-md-2">
            <button class="btn btn-warning w-100 quick-action-btn" onclick="showTab('patients')">
              <i class="fas fa-users"></i> Find Patient
            </button>
          </div>
          <div class="col-md-2">
            <button class="btn btn-primary w-100 quick-action-btn" onclick="showTab('report')">
              <i class="fas fa-file-alt"></i> Submit Report
            </button>
          </div>
          <div class="col-md-2">
            <button class="btn btn-pdf w-100 quick-action-btn" onclick="exportDashboardPDF()">
              <i class="fas fa-file-pdf"></i> Export PDF
            </button>
          </div>
          <div class="col-md-2">
            <button class="btn btn-secondary w-100 quick-action-btn" onclick="exportAllDataPDF()">
              <i class="fas fa-database"></i> Full Report
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Patients Tab -->
  <div id="tab-patients" class="d-none">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-users"></i> Patient Management</h4>
        <button class="btn btn-pdf" onclick="exportPatientsPDF()">
          <i class="fas fa-file-pdf"></i> Export Patients PDF
        </button>
      </div>
      <div class="row mb-3">
        <div class="col-md-6">
          <input type="text" id="patientSearch" class="form-control" placeholder="Search patients by name, email, or phone..." onkeyup="searchPatients()">
        </div>
        <div class="col-md-3">
          <select id="patientFilter" class="form-select" onchange="filterPatientsByDepartment()">
            <option value="">All Departments</option>
            <option value="Cardiology">Cardiology</option>
            <option value="Pediatrics">Pediatrics</option>
            <option value="Emergency">Emergency</option>
            <option value="General">General Medicine</option>
          </select>
        </div>
        <div class="col-md-3">
          <button class="btn btn-info w-100" onclick="refreshPatients()">
            <i class="fas fa-sync-alt"></i> Refresh
          </button>
        </div>
      </div>
      <div id="patientsList" class="row g-3"></div>
    </div>
  </div>

  <!-- Appointments Tab -->
  <div id="tab-appointments" class="d-none">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-calendar-check"></i> Today's Appointments</h4>
        <button class="btn btn-pdf" onclick="exportAppointmentsPDF()">
          <i class="fas fa-file-pdf"></i> Export Appointments PDF
        </button>
      </div>
      <div class="mb-3">
        <input type="date" id="appointmentDateFilter" class="form-control w-auto" onchange="loadAppointments()">
      </div>
      <div id="appointmentsList" class="row g-3"></div>
    </div>
  </div>

  <!-- Vitals Tab -->
  <div id="tab-vitals" class="d-none">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-heartbeat"></i> Record Patient Vitals</h4>
        <button class="btn btn-pdf" onclick="exportVitalsPDF()">
          <i class="fas fa-file-pdf"></i> Export Vitals PDF
        </button>
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="card p-3 mb-3">
            <h6>Select Patient</h6>
            <select id="vitalsPatient" class="form-select mb-3" onchange="loadPatientVitalsHistory()">
              <option value="">-- Select Patient --</option>
            </select>
            <div id="patientInfo" class="alert alert-info d-none"></div>
          </div>
          
          <div class="card p-3">
            <h6>Vital Signs Recording</h6>
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">Temperature (¡ÆC)</label>
                <input type="number" id="temperature" class="form-control" step="0.1" placeholder="e.g., 36.5">
              </div>
              <div class="col-md-6">
                <label class="form-label">Blood Pressure</label>
                <input type="text" id="bloodPressure" class="form-control" placeholder="e.g., 120/80">
              </div>
              <div class="col-md-6">
                <label class="form-label">Heart Rate (bpm)</label>
                <input type="number" id="heartRate" class="form-control" placeholder="e.g., 72">
              </div>
              <div class="col-md-6">
                <label class="form-label">Oxygen Saturation (%)</label>
                <input type="number" id="oxygenSaturation" class="form-control" placeholder="e.g., 98">
              </div>
              <div class="col-md-6">
                <label class="form-label">Weight (kg)</label>
                <input type="number" id="weight" class="form-control" step="0.1" placeholder="e.g., 70.5">
              </div>
              <div class="col-md-6">
                <label class="form-label">Height (cm)</label>
                <input type="number" id="height" class="form-control" placeholder="e.g., 170">
              </div>
              <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea id="vitalsNotes" class="form-control" rows="2" placeholder="Additional observations..."></textarea>
              </div>
              <div class="col-12">
                <button class="btn btn-primary w-100" onclick="saveVitals()">
                  <i class="fas fa-save"></i> Save Vital Signs
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-6">
          <div class="card p-3">
            <h6>Vital Signs History</h6>
            <div id="vitalsHistory" class="vitals-history" style="max-height: 500px; overflow-y: auto;">
              <p class="text-muted text-center">Select a patient to view vitals history</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Records Tab -->
  <div id="tab-records" class="d-none">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-file-medical"></i> Medical Records</h4>
        <button class="btn btn-pdf" onclick="exportMedicalRecordsPDF()">
          <i class="fas fa-file-pdf"></i> Export Records PDF
        </button>
      </div>
      <div class="row mb-3">
        <div class="col-md-4">
          <select id="recordsPatient" class="form-select" onchange="loadPatientRecords()">
            <option value="">-- Select Patient --</option>
          </select>
        </div>
        <div class="col-md-8">
          <button class="btn btn-success" onclick="showAddRecordModal()">
            <i class="fas fa-plus"></i> Add Medical Record
          </button>
        </div>
      </div>
      <div id="medicalRecordsList" class="list-group"></div>
    </div>
  </div>

  <!-- Messages Tab -->
  <div id="tab-messages" class="d-none">
    <div class="card p-4">
      <h4 class="mb-3"><i class="fas fa-comments text-info me-2"></i>Messages with Patients</h4>
      
      <!-- Patient Selection Dropdown -->
      <div class="mb-3">
        <label class="form-label fw-bold"><i class="fas fa-user me-1"></i>Select Patient to Chat With:</label>
        <div class="input-group">
          <select id="patientChatSelect" class="form-select" onchange="selectPatientToChat()">
            <option value="">-- Select a patient --</option>
          </select>
          <button class="btn btn-info" onclick="startNewNurseChat()" id="startChatBtn" disabled>
            <i class="fas fa-comment-dots"></i> Start Chat
          </button>
        </div>
        <small class="text-muted">Select a patient from the list to start or continue a conversation</small>
      </div>
      
      <hr>
      
      <div class="row">
        <div class="col-md-4 border-end">
          <h6 class="mb-3"><i class="fas fa-history"></i> Recent Conversations</h6>
          <div id="conversationsList" class="list-group" style="max-height: 400px; overflow-y: auto;">
            <div class="text-center text-muted p-3">Loading conversations...</div>
          </div>
        </div>
        <div class="col-md-8">
          <div id="currentChatPatient" class="alert alert-info mb-2 d-none">
            <i class="fas fa-user-circle me-2"></i>Chatting with: <strong id="currentChatPatientName"></strong> (<span id="currentChatPatientEmail"></span>)
          </div>
          <div id="messageThread" class="border rounded p-3" style="height: 400px; overflow-y: auto; background-color: #f8f9fa;">
            <div class="text-center text-muted p-5">Select a patient from the dropdown or click on a conversation to start messaging</div>
          </div>
          <div class="input-group mt-3">
            <input type="text" id="messageInput" class="form-control" placeholder="Type your message..." disabled onkeypress="if(event.key==='Enter') sendNurseMessage()">
            <button class="btn btn-info" onclick="sendNurseMessage()" disabled id="sendMsgBtn">
              <i class="fas fa-paper-plane"></i> Send
            </button>
          </div>
          <div class="small text-muted mt-2">
            <i class="fas fa-lock"></i> Your messages are secure and encrypted
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Report Tab -->
  <div id="tab-report" class="d-none">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-pen-fancy"></i> Weekly Report</h4>
        <button class="btn btn-pdf" onclick="exportReportsPDF()">
          <i class="fas fa-file-pdf"></i> Export Reports PDF
        </button>
      </div>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Week Starting</label>
          <input type="date" id="reportWeek" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Patients Attended</label>
          <input type="number" id="patientsAttended" class="form-control">
        </div>
        <div class="col-12">
          <label class="form-label">Activities Performed</label>
          <textarea id="activities" class="form-control" rows="4" placeholder="Describe your activities this week..."></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Challenges Faced</label>
          <textarea id="challenges" class="form-control" rows="3" placeholder="Any challenges or issues..."></textarea>
        </div>
        <div class="col-12">
          <button class="btn btn-primary w-100" onclick="submitReport()">
            <i class="fas fa-paper-plane"></i> Submit Report
          </button>
        </div>
      </div>
      <hr>
      <h6>Previous Reports</h6>
      <div id="previousReports" class="list-group"></div>
    </div>
  </div>
</div>

<!-- Add Medical Record Modal -->
<div class="modal fade" id="addRecordModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="fas fa-file-medical"></i> Add Medical Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Patient</label>
          <select id="recordPatient" class="form-select" required>
            <option value="">Select Patient</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Record Title</label>
          <input type="text" id="recordTitle" class="form-control" placeholder="e.g., Physical Examination">
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea id="recordDescription" class="form-control" rows="4" placeholder="Detailed notes..."></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Diagnosis (if any)</label>
          <input type="text" id="recordDiagnosis" class="form-control" placeholder="e.g., Hypertension">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" onclick="saveMedicalRecord()">Save Record</button>
      </div>
    </div>
  </div>
</div>

<!-- View Patient Details Modal -->
<div class="modal fade" id="patientDetailsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title"><i class="fas fa-user"></i> Patient Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="patientDetailsBody">
        <div class="text-center"><div class="spinner-border"></div> Loading...</div>
      </div>
    </div>
  </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>