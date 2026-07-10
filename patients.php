<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ExMed - Patient Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/crypto-js@4.2.0/crypto-js.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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
  .message-sent { background: #007bff; color: white; border-radius: 18px 18px 4px 18px; padding: 10px 15px; display: inline-block; max-width: 80%; }
  .message-received { background: #e9ecef; color: #333; border-radius: 18px 18px 18px 4px; padding: 10px 15px; display: inline-block; max-width: 80%; }
  .chat-user { background: #007bff; color: white; border-radius: 18px 18px 4px 18px; padding: 8px 15px; display: inline-block; max-width: 80%; }
  .chat-bot { background: #e9ecef; color: #333; border-radius: 18px 18px 18px 4px; padding: 8px 15px; display: inline-block; max-width: 80%; }
  .conversation-item { cursor: pointer; transition: all 0.2s ease; }
  .conversation-item:hover { background-color: #e7f3ff; transform: translateX(3px); }
  .conversation-active { background-color: #e7f3ff; border-left: 3px solid #007bff; }
  .unread-badge { background: #dc3545; color: white; border-radius: 10px; padding: 2px 8px; font-size: 0.7rem; margin-left: 5px; }
  .doctor-select-card { cursor: pointer; transition: all 0.2s ease; }
  .doctor-select-card:hover { background-color: #e7f3ff; transform: translateX(5px); }
  .btn-pdf { background: #dc3545; color: white; }
  .btn-pdf:hover { background: #bb2d3b; color: white; }
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="container-fluid mt-3">
  <div class="dash-header text-center mb-4">
    <h2>ExMed</h2>
    <p><span id="userName"><?php echo htmlspecialchars($currentUser['name'] ?? $currentUser['email']); ?></span> | <a href="logout.php" class="text-white text-decoration-underline">Logout</a></p>
  </div>

  <!-- PATIENT NAVIGATION -->
  <ul class="nav nav-pills justify-content-center flex-wrap mb-4 gap-2" id="patientNav">
    <li class="nav-item"><a class="nav-link active" href="#" onclick="event.preventDefault(); showTab('home')"><i class="fas fa-home"></i> Home</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('appointments')"><i class="fas fa-calendar-check"></i> Appointments</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('prescriptions')"><i class="fas fa-pills"></i> Prescriptions</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('messages')"><i class="fas fa-comments"></i> Messages <span id="unreadMsgBadge" class="ms-1"></span></a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('subscription')"><i class="fas fa-credit-card"></i> Subscription<br><small class="text-muted">Basic: 10K | Premium: 17K<br>7-Day Free Trial</small></a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('records')"><i class="fas fa-file-medical"></i> Records</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('insurance')"><i class="fas fa-shield-alt"></i> Insurance</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('profile')"><i class="fas fa-user"></i> Profile</a></li>
  </ul>

  <!-- Home Tab -->
  <div id="tab-home" class="row g-4">
    <div id="patientHome" class="col-12 row g-4">
      <div class="col-md-3"><div class="card h-100 text-center p-4 home-dashboard-card" style="cursor:pointer;" onclick="showTab('appointments')"><i class="fas fa-calendar-check fa-3x text-primary mb-3"></i><h5>Book Appointment</h5><button class="btn btn-primary" onclick="event.stopPropagation(); showTab('appointments')">Book Now</button></div></div>
      <div class="col-md-3"><div class="card h-100 text-center p-4 home-dashboard-card" style="cursor:pointer;" onclick="showTab('prescriptions')"><i class="fas fa-pills fa-3x text-success mb-3"></i><h5>My Prescriptions</h5><button class="btn btn-success" onclick="event.stopPropagation(); showTab('prescriptions')">View</button></div></div>
      <div class="col-md-3"><div class="card h-100 text-center p-4 home-dashboard-card" style="cursor:pointer;" onclick="showTab('records')"><i class="fas fa-file-medical fa-3x text-warning mb-3"></i><h5>My Records</h5><button class="btn btn-warning" onclick="event.stopPropagation(); showTab('records')">View</button></div></div>
      <div class="col-md-3"><div class="card h-100 text-center p-4 home-dashboard-card" style="cursor:pointer;" onclick="showTab('messages')"><i class="fas fa-comments fa-3x text-info mb-3"></i><h5>Messages</h5><button class="btn btn-info" onclick="event.stopPropagation(); showTab('messages')">Check Messages</button></div></div>
    </div>
    <div class="col-12 mt-4">
      <div class="card p-4">
        <h4 class="mb-4"><i class="fas fa-credit-card text-primary me-2"></i> Subscription Plans</h4>
        <p class="text-muted small mb-4">Choose a plan to access premium healthcare features. All prices in Ugandan Shillings (UGX). Start with a 7-day free trial!</p>
        <div class="row g-4" id="homeSubscriptionPlans"></div>
      </div>
    </div>
  </div>

  <!-- Appointments Tab -->
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
    
    <h6 class="mt-4">📋 Your Appointments</h6>
    <div id="appointmentsList" class="row g-3"></div>
    <div class="card p-3 mb-4">
      <h6><i class="fas fa-building"></i> Hospital Department Directory</h6>
      <input type="text" id="deptSearch" class="form-control mb-3" placeholder="Search department..." oninput="filterDepartments()">
      <div id="departmentsList"></div>
    </div>
  </div>

  <!-- Prescriptions Tab with PDF Export -->
  <div id="tab-prescriptions" class="d-none card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4><i class="fas fa-pills"></i> My Prescriptions</h4>
      <button class="btn btn-pdf" onclick="exportAllPrescriptionsPDF()">
        <i class="fas fa-file-pdf"></i> Export All Prescriptions PDF
      </button>
    </div>
    <div class="mb-4">
      <p class="text-muted">View and download your current and past prescriptions. All medications prescribed by your doctors.</p>
      <div class="alert alert-info" id="prescsEmptyMsg" style="display:none;">
        <i class="fas fa-info-circle"></i> No prescriptions available yet. Prescriptions from your doctors will appear here.
      </div>
    </div>
    <div id="prescriptionsList" class="row g-3"></div>
  </div>

  <!-- Messages Tab -->
  <div id="tab-messages" class="d-none">
    <div class="card p-4">
      <h4 class="mb-3"><i class="fas fa-comments text-info me-2"></i>Messages with Doctors</h4>
      
      <!-- Doctor Selection Dropdown -->
      <div class="mb-3">
        <label class="form-label fw-bold"><i class="fas fa-user-md me-1"></i>Select Doctor to Chat With:</label>
        <div class="input-group">
          <select id="doctorChatSelect" class="form-select" onchange="selectDoctorToChat()">
            <option value="">-- Select a doctor --</option>
          </select>
          <button class="btn btn-primary" onclick="startNewPatientChat()" id="startChatBtn" disabled>
            <i class="fas fa-comment-dots"></i> Start Chat
          </button>
        </div>
        <small class="text-muted">Select a doctor from the list to start or continue a conversation</small>
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
          <div id="currentChatDoctor" class="alert alert-info mb-2 d-none">
            <i class="fas fa-user-md me-2"></i>Chatting with: <strong id="currentChatDoctorName"></strong> (<span id="currentChatDoctorEmail"></span>)
          </div>
          <div id="messageThread" class="border rounded p-3" style="height: 400px; overflow-y: auto; background-color: #f8f9fa;">
            <div class="text-center text-muted p-5">Select a doctor from the dropdown or click on a conversation to start messaging</div>
          </div>
          <div class="input-group mt-3">
            <input type="text" id="messageInput" class="form-control" placeholder="Type your message..." disabled onkeypress="if(event.key==='Enter') sendPatientMessage()">
            <button class="btn btn-info" onclick="sendPatientMessage()" disabled id="sendMsgBtn">
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

  <!-- Profile Tab -->
  <div id="tab-profile" class="d-none card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4>My Profile</h4>
      <button class="btn btn-pdf" onclick="exportProfilePDF()">
        <i class="fas fa-file-pdf"></i> Export Profile PDF
      </button>
    </div>
    <div id="profileContent"></div>
  </div>

  <!-- Subscription Tab -->
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

  <!-- Records Tab with PDF Export -->
  <div id="tab-records" class="d-none card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4><i class="fas fa-file-medical"></i> My Medical Records</h4>
      <button class="btn btn-pdf" onclick="exportAllRecordsPDF()">
        <i class="fas fa-file-pdf"></i> Export All Records PDF
      </button>
    </div>
    <p class="text-muted">Download and access your medical records.</p>
    <div class="row g-3 mb-4" id="medicalRecordsList"></div>
  </div>

  <!-- Insurance Tab -->
  <div id="tab-insurance" class="d-none card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4><i class="fas fa-shield-alt"></i> Insurance & Documents</h4>
      <button class="btn btn-pdf" onclick="exportInsurancePDF()">
        <i class="fas fa-file-pdf"></i> Export Insurance PDF
      </button>
    </div>
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
  <div class="modal fade" id="subscriptionConfirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-gift"></i> Confirm 7-Day Free Trial</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-success mb-3"><h6><i class="fas fa-check-circle"></i> You're getting 7 days free!</h6><p class="mb-0 small">No credit card charge during the trial. After 7 days, we'll start charging UGX <span id="confirmPlanPrice"></span>/month</p></div>
          <div class="card p-3 mb-3" style="background-color: #f8f9fa;"><h6 class="mb-2">Subscription Summary</h6><p class="mb-2"><strong>Plan:</strong> <span id="confirmPlanName"></span></p><p class="mb-2"><strong>Monthly Price:</strong> <span id="confirmPlanPriceSub"></span></p><p class="mb-2"><strong>Payment Method:</strong> <span id="confirmPaymentMethod"></span></p><p class="mb-0"><strong>Trial Duration:</strong> 7 days - Free!</p></div>
          <div class="alert alert-info"><small><i class="fas fa-info-circle"></i> You can upgrade, downgrade, or cancel anytime. No hidden fees.</small></div>
          <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="agreeTerms" required><label class="form-check-label" for="agreeTerms">I agree to the terms and conditions. I understand that I will be charged after the 7-day trial ends.</label></div>
          <input type="hidden" id="selectedPlanId"><input type="hidden" id="selectedPaymentMethod">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-success" onclick="confirmSubscription()"><i class="fas fa-check"></i> Start Free Trial</button>
        </div>
      </div>
    </div>
  </div>
</div>


</body>
</html>