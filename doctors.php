<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Dashboard - ExMed</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
  body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
  .dash-header { background: linear-gradient(135deg, #28a745, #1e7e34); color: white; padding: 25px; border-radius: 16px 16px 0 0; }
  .nav-link { color: #28a745 !important; font-weight: 600; padding: 10px 20px !important; transition: all 0.3s ease; }
  .nav-link:hover { transform: translateY(-2px); }
  .nav-link.active { background: #28a745 !important; color: white !important; border-radius: 12px; }
  .record-item { cursor: pointer; transition: all 0.3s ease; border-left: 4px solid transparent; }
  .record-item:hover { background-color: #e8f5e9; border-left-color: #28a745; transform: translateX(5px); }
  .appointment-card { border-left: 4px solid #28a745; transition: all 0.3s ease; }
  .appointment-card:hover { transform: translateY(-3px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
  .patient-card { cursor: pointer; transition: all 0.3s ease; }
  .patient-card:hover { transform: translateY(-3px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); background-color: #f8f9fa; }
  .message-bubble-sent { background: #28a745; color: white; border-radius: 18px 18px 4px 18px; padding: 10px 15px; display: inline-block; max-width: 80%; }
  .message-bubble-received { background: #e9ecef; color: #333; border-radius: 18px 18px 18px 4px; padding: 10px 15px; display: inline-block; max-width: 80%; }
  .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
  .status-pending { background: #ffc107; color: #856404; }
  .status-confirmed { background: #28a745; color: white; }
  .status-completed { background: #17a2b8; color: white; }
  .status-cancelled { background: #dc3545; color: white; }
  .home-card { cursor: pointer; transition: all 0.3s ease; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
  .home-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
  .home-card .btn { transition: all 0.3s ease; }
  .home-card:hover .btn { transform: scale(1.05); }
  .modal-content { border-radius: 16px; }
  .btn-action { margin: 2px; }
  .loading { text-align: center; padding: 20px; color: #6c757d; }
  .search-box { position: relative; }
  .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6c757d; }
  .search-box input { padding-left: 35px; }
  .list-card { margin-bottom: 20px; border-radius: 12px; overflow: hidden; }
  .list-card-header { background: linear-gradient(135deg, #28a745, #1e7e34); color: white; padding: 12px 20px; font-weight: 600; }
  .list-card-body { max-height: 400px; overflow-y: auto; }
  .list-item { border-left: 3px solid #28a745; margin-bottom: 10px; transition: all 0.2s ease; }
  .list-item:hover { background-color: #f8f9fa; transform: translateX(3px); }
  .conversation-item { cursor: pointer; transition: all 0.2s ease; border-left: 3px solid transparent; }
  .conversation-item:hover { background-color: #e8f5e9; border-left-color: #28a745; transform: translateX(3px); }
  .conversation-active { background-color: #e8f5e9; border-left-color: #28a745; }
  .unread-badge { background: #dc3545; color: white; border-radius: 10px; padding: 2px 8px; font-size: 0.7rem; margin-left: 5px; }
  .patient-select-card { cursor: pointer; transition: all 0.2s ease; }
  .patient-select-card:hover { background-color: #e8f5e9; transform: translateX(5px); }
  .btn-pdf { background: #dc3545; color: white; border: none; }
  .btn-pdf:hover { background: #bb2d3b; color: white; }
</style>
</head>
<body>

<div class="container-fluid mt-3">
  <div class="dash-header text-center mb-4">
    <h2><i class="fas fa-stethoscope me-2"></i>ExMed Doctor Portal</h2>
    <p class="mb-0">Welcome, Dr. <?php echo htmlspecialchars($currentUser['name']); ?> | 
    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($currentUser['email']); ?> | 
    <a href="logout.php" class="text-white text-decoration-underline"><i class="fas fa-sign-out-alt"></i> Logout</a></p>
  </div>

  <!-- Doctor Navigation -->
  <ul class="nav nav-pills justify-content-center flex-wrap mb-4 gap-2" id="doctorNav">
    <li class="nav-item"><a class="nav-link active" href="#" onclick="event.preventDefault(); showTab('home')"><i class="fas fa-home"></i> Home</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('patients')"><i class="fas fa-users"></i> My Patients</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('appointments')"><i class="fas fa-calendar-check"></i> Appointments</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('prescribe')"><i class="fas fa-prescription-bottle"></i> Prescribe</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('diagnose')"><i class="fas fa-stethoscope"></i> Diagnose</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('orders')"><i class="fas fa-flask"></i> Lab Orders</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('myLists')"><i class="fas fa-list-ul"></i> My Lists</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('messages')"><i class="fas fa-comments"></i> Messages <span id="unreadMsgBadge" class="ms-1"></span></a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('report')"><i class="fas fa-pen-fancy"></i> Weekly Report</a></li>
  </ul>

  <!-- Home Tab -->
  <div id="tab-home">
    <div class="row g-4">
      <div class="col-md-3">
        <div class="card home-card text-center p-4" onclick="showTab('patients')">
          <i class="fas fa-users fa-4x text-primary mb-3"></i>
          <h5>My Patients</h5>
          <p class="text-muted small">View and manage your patients</p>
          <button class="btn btn-primary" onclick="event.stopPropagation(); showTab('patients')">View Patients</button>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card home-card text-center p-4" onclick="showTab('appointments')">
          <i class="fas fa-calendar-check fa-4x text-success mb-3"></i>
          <h5>Appointments</h5>
          <p class="text-muted small">Manage your schedule</p>
          <button class="btn btn-success" onclick="event.stopPropagation(); showTab('appointments')">View Schedule</button>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card home-card text-center p-4" onclick="showTab('prescribe')">
          <i class="fas fa-prescription-bottle fa-4x text-info mb-3"></i>
          <h5>Prescribe</h5>
          <p class="text-muted small">Write prescriptions</p>
          <button class="btn btn-info" onclick="event.stopPropagation(); showTab('prescribe')">Write Prescription</button>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card home-card text-center p-4" onclick="showTab('myLists')">
          <i class="fas fa-list-ul fa-4x text-warning mb-3"></i>
          <h5>My Lists</h5>
          <p class="text-muted small">View all prescriptions, diagnoses & lab orders</p>
          <button class="btn btn-warning" onclick="event.stopPropagation(); showTab('myLists')">View Lists</button>
        </div>
      </div>
    </div>
    <div class="row mt-4">
      <div class="col-md-4">
        <div class="card home-card text-center p-4" onclick="showTab('diagnose')">
          <i class="fas fa-stethoscope fa-4x text-danger mb-3"></i>
          <h5>Diagnose</h5>
          <p class="text-muted small">Record diagnoses</p>
          <button class="btn btn-danger" onclick="event.stopPropagation(); showTab('diagnose')">Add Diagnosis</button>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card home-card text-center p-4" onclick="showTab('orders')">
          <i class="fas fa-flask fa-4x text-secondary mb-3"></i>
          <h5>Lab Orders</h5>
          <p class="text-muted small">Order laboratory tests</p>
          <button class="btn btn-secondary" onclick="event.stopPropagation(); showTab('orders')">Order Test</button>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card home-card text-center p-4" onclick="showTab('messages')">
          <i class="fas fa-comments fa-4x text-primary mb-3"></i>
          <h5>Messages</h5>
          <p class="text-muted small">Communicate with patients</p>
          <button class="btn btn-primary" onclick="event.stopPropagation(); showTab('messages')">Open Chat</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Patients Tab -->
  <div id="tab-patients" class="d-none">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-users text-primary me-2"></i>My Patients</h4>
        <button class="btn btn-pdf" onclick="exportPatientsListPDF()">
          <i class="fas fa-file-pdf"></i> Export Patients PDF
        </button>
      </div>
      <div class="search-box mb-3">
        <i class="fas fa-search"></i>
        <input type="text" id="patientSearch" class="form-control" placeholder="Search by name or email..." oninput="filterPatients()">
      </div>
      <div id="patientsList" class="row g-3"></div>
    </div>
  </div>

  <!-- Appointments Tab -->
  <div id="tab-appointments" class="d-none">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-calendar-check text-success me-2"></i>My Appointments</h4>
        <button class="btn btn-pdf" onclick="exportAppointmentsPDF()">
          <i class="fas fa-file-pdf"></i> Export Appointments PDF
        </button>
      </div>
      <div id="appointmentsList" class="row g-3"></div>
    </div>
  </div>

  <!-- Prescribe Tab -->
  <div id="tab-prescribe" class="d-none">
    <div class="card p-4">
      <h4 class="mb-3"><i class="fas fa-prescription-bottle text-info me-2"></i>Prescribe Medication</h4>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-bold">Select Patient *</label>
          <select id="prescribePatient" class="form-select" onchange="loadPatientInfo(this.value)">
            <option value="">-- Choose a patient --</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-bold">Medication *</label>
          <select id="prescribeMedicine" class="form-select">
            <option value="">-- Select medication --</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-bold">Dosage</label>
          <input type="text" id="dosage" class="form-control" placeholder="e.g., 500mg">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-bold">Frequency</label>
          <input type="text" id="frequency" class="form-control" placeholder="e.g., 3x daily">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-bold">Duration (days)</label>
          <input type="number" id="duration" class="form-control" placeholder="Number of days">
        </div>
        <div class="col-12">
          <label class="form-label fw-bold">Instructions</label>
          <textarea id="instructions" class="form-control" rows="3" placeholder="Additional instructions for the patient..."></textarea>
        </div>
        <div class="col-12">
          <button class="btn btn-success w-100" onclick="submitPrescription()">
            <i class="fas fa-prescription-bottle me-2"></i>Submit Prescription
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Diagnose Tab -->
  <div id="tab-diagnose" class="d-none">
    <div class="card p-4">
      <h4 class="mb-3"><i class="fas fa-stethoscope text-danger me-2"></i>Diagnose Patient</h4>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-bold">Select Patient *</label>
          <select id="diagnosePatient" class="form-select">
            <option value="">-- Choose a patient --</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-bold">Diagnosis *</label>
          <select id="diagnosis" class="form-select">
            <option value="">-- Select diagnosis --</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label fw-bold">Notes / Treatment Plan</label>
          <textarea id="diagnosisNotes" class="form-control" rows="3" placeholder="Detailed notes about diagnosis and treatment..."></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-bold">Severity</label>
          <select id="severity" class="form-select">
            <option>Mild</option>
            <option>Moderate</option>
            <option>Severe</option>
            <option>Critical</option>
          </select>
        </div>
        <div class="col-12">
          <button class="btn btn-danger w-100" onclick="submitDiagnosis()">
            <i class="fas fa-save me-2"></i>Save Diagnosis
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Orders Tab -->
  <div id="tab-orders" class="d-none">
    <div class="card p-4">
      <h4 class="mb-3"><i class="fas fa-flask text-secondary me-2"></i>Order Laboratory Tests</h4>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-bold">Select Patient *</label>
          <select id="orderPatient" class="form-select">
            <option value="">-- Choose a patient --</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-bold">Test Type *</label>
          <select id="testType" class="form-select">
            <option value="">-- Select test --</option>
            <option>Complete Blood Count (CBC)</option>
            <option>Malaria Test (RDT)</option>
            <option>Blood Glucose Test</option>
            <option>Urinalysis</option>
            <option>Liver Function Test</option>
            <option>Kidney Function Test</option>
            <option>Thyroid Function Test</option>
            <option>COVID-19 Test</option>
            <option>Typhoid Test</option>
            <option>HIV Test</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label fw-bold">Clinical Indication</label>
          <textarea id="indication" class="form-control" rows="3" placeholder="Reason for ordering this test..."></textarea>
        </div>
        <div class="col-12">
          <button class="btn btn-secondary w-100" onclick="orderLabTest()">
            <i class="fas fa-flask me-2"></i>Order Test
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- My Lists Tab -->
  <div id="tab-myLists" class="d-none">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card list-card">
          <div class="list-card-header">
            <i class="fas fa-prescription-bottle me-2"></i>My Prescriptions
            <span class="badge bg-light text-dark ms-2" id="prescriptionCount">0</span>
            <button class="btn btn-sm btn-light float-end" onclick="exportPrescriptionsPDF()" style="font-size: 12px;"><i class="fas fa-file-pdf text-danger"></i> PDF</button>
          </div>
          <div class="list-card-body" id="prescriptionsList">
            <div class="text-center p-3 text-muted">Loading prescriptions...</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card list-card">
          <div class="list-card-header">
            <i class="fas fa-stethoscope me-2"></i>My Diagnoses
            <span class="badge bg-light text-dark ms-2" id="diagnosisCount">0</span>
            <button class="btn btn-sm btn-light float-end" onclick="exportDiagnosesPDF()" style="font-size: 12px;"><i class="fas fa-file-pdf text-danger"></i> PDF</button>
          </div>
          <div class="list-card-body" id="diagnosesList">
            <div class="text-center p-3 text-muted">Loading diagnoses...</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card list-card">
          <div class="list-card-header">
            <i class="fas fa-flask me-2"></i>My Lab Orders
            <span class="badge bg-light text-dark ms-2" id="labOrderCount">0</span>
            <button class="btn btn-sm btn-light float-end" onclick="exportLabOrdersPDF()" style="font-size: 12px;"><i class="fas fa-file-pdf text-danger"></i> PDF</button>
          </div>
          <div class="list-card-body" id="labOrdersList">
            <div class="text-center p-3 text-muted">Loading lab orders...</div>
          </div>
        </div>
      </div>
    </div>
    <div class="card mt-4 p-3">
      <div class="row">
        <div class="col-md-6">
          <label class="form-label fw-bold">Filter by Patient</label>
          <select id="listPatientFilter" class="form-select" onchange="loadMyLists()">
            <option value="">All Patients</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-bold">Filter by Date</label>
          <input type="date" id="listDateFilter" class="form-control" onchange="loadMyLists()">
        </div>
      </div>
    </div>
  </div>

  <!-- Messages Tab -->
  <div id="tab-messages" class="d-none">
    <div class="card p-4">
      <h4 class="mb-3"><i class="fas fa-comments text-warning me-2"></i>Messages with Patients</h4>
      
      <!-- Patient Selection Dropdown -->
      <div class="mb-3">
        <label class="form-label fw-bold"><i class="fas fa-user-md me-1"></i>Select Patient to Chat With:</label>
        <div class="input-group">
          <select id="patientChatSelect" class="form-select" onchange="selectPatientToChat()">
            <option value="">-- Select a patient --</option>
          </select>
          <button class="btn btn-success" onclick="startNewChat()" id="startChatBtn" disabled>
            <i class="fas fa-comment-dots"></i> Start Chat
          </button>
        </div>
        <small class="text-muted">Select a patient from your list to start or continue a conversation</small>
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
            <input type="text" id="messageInput" class="form-control" placeholder="Type your message..." disabled onkeypress="if(event.key==='Enter') sendDoctorMessage()">
            <button class="btn btn-warning" onclick="sendDoctorMessage()" disabled id="sendMsgBtn">
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
      <h4 class="mb-3"><i class="fas fa-pen-fancy text-primary me-2"></i>Weekly Report</h4>
      <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Submit your weekly activities report for review by the admin.
      </div>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-bold">Week Starting *</label>
          <input type="date" id="reportWeek" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-bold">Patients Attended *</label>
          <input type="number" id="patientsAttended" class="form-control" placeholder="Number of patients">
        </div>
        <div class="col-12">
          <label class="form-label fw-bold">Activities Performed</label>
          <textarea id="activities" class="form-control" rows="4" placeholder="Describe your activities this week..."></textarea>
        </div>
        <div class="col-12">
          <label class="form-label fw-bold">Challenges Faced</label>
          <input type="text" id="challenges" class="form-control" placeholder="Any challenges or issues?">
        </div>
        <div class="col-12">
          <button class="btn btn-primary w-100" onclick="submitReport()">
            <i class="fas fa-paper-plane me-2"></i>Submit Report
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Patient Details Modal -->
<div class="modal fade" id="patientModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-user-circle me-2"></i>Patient Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="patientModalBody">
        Loading...
      </div>
    </div>
  </div>
</div>

</body>
</html>