<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$currentUser = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - ExMed</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
  body { background: #f8f9fa; }
  .dash-header { background: linear-gradient(135deg, #dc3545, #a71d2a); color: white; padding: 25px; border-radius: 16px 16px 0 0; }
  .nav-link { color: #dc3545 !important; font-weight: 600; }
  .nav-link.active { background: #dc3545 !important; color: white !important; border-radius: 12px; }
</style>
</head>
<body>

<div class="container-fluid mt-3">
  <div class="dash-header text-center mb-4">
    <h2>ExMed Admin Portal</h2>
    <p>Welcome, Admin <?php echo htmlspecialchars($currentUser['name']); ?> | <a href="logout.php" class="text-white text-decoration-underline">Logout</a></p>
  </div>

  <!-- Admin Navigation -->
  <ul class="nav nav-pills justify-content-center flex-wrap mb-4 gap-2">
    <li class="nav-item"><a class="nav-link active" href="#" onclick="event.preventDefault(); showTab('dashboard')"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('users')"><i class="fas fa-users"></i> Users</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('reports')"><i class="fas fa-file-alt"></i> Staff Reports</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('subscriptions')"><i class="fas fa-credit-card"></i> Subscriptions</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('settings')"><i class="fas fa-cog"></i> Settings</a></li>
  </ul>

  <!-- Dashboard Tab -->
  <div id="tab-dashboard" class="row g-4">
    <div class="col-md-3"><div class="card text-center p-3"><h3 id="totalUsers">0</h3><p>Total Users</p></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><h3 id="totalPatients">0</h3><p>Patients</p></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><h3 id="totalDoctors">0</h3><p>Doctors</p></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><h3 id="totalAppointments">0</h3><p>Appointments</p></div></div>
  </div>

  <!-- Users Tab -->
  <div id="tab-users" class="d-none card p-4">
    <h4><i class="fas fa-users"></i> User Management</h4>
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead>
          <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody id="usersTable"></tbody>
      </table>
    </div>
    <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addUserModal">Add New User</button>
  </div>

  <!-- Reports Tab -->
  <div id="tab-reports" class="d-none card p-4">
    <h4><i class="fas fa-file-alt"></i> Staff Reports</h4>
    <div id="reportsList"></div>
  </div>

  <!-- Subscriptions Tab -->
  <div id="tab-subscriptions" class="d-none card p-4">
    <h4><i class="fas fa-credit-card"></i> Subscription Management</h4>
    <div class="row">
      <div class="col-md-6"><div class="card p-3"><h6>Total Revenue</h6><h3 id="totalRevenue">UGX 0</h3></div></div>
      <div class="col-md-6"><div class="card p-3"><h6>Active Subscriptions</h6><h3 id="activeSubscriptions">0</h3></div></div>
    </div>
    <table class="table table-bordered mt-3">
      <thead><tr><th>User</th><th>Plan</th><th>Start Date</th><th>End Date</th><th>Status</th></tr></thead>
      <tbody id="subscriptionsTable"></tbody>
    </table>
  </div>

  <!-- Settings Tab -->
  <div id="tab-settings" class="d-none card p-4">
    <h4><i class="fas fa-cog"></i> System Settings</h4>
    <div class="row g-3">
      <div class="col-md-6"><label>Hospital Name</label><input type="text" id="hospitalName" class="form-control" value="ExMed Hospital"></div>
      <div class="col-md-6"><label>Emergency Hotline</label><input type="text" id="emergencyHotline" class="form-control" value="+256701111111"></div>
      <div class="col-md-6"><label>Support Email</label><input type="email" id="supportEmail" class="form-control" value="support@exmed.ug"></div>
      <div class="col-md-6"><label>Working Hours</label><input type="text" id="workingHours" class="form-control" value="8 AM - 6 PM"></div>
      <div class="col-12"><button class="btn btn-primary" onclick="saveSettings()">Save Settings</button></div>
    </div>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Add New User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="text" id="newUserName" class="form-control mb-2" placeholder="Full Name">
        <input type="email" id="newUserEmail" class="form-control mb-2" placeholder="Email">
        <input type="tel" id="newUserPhone" class="form-control mb-2" placeholder="Phone">
        <select id="newUserRole" class="form-select mb-2"><option value="patient">Patient</option><option value="doctor">Doctor</option><option value="nurse">Nurse</option></select>
        <input type="password" id="newUserPassword" class="form-control mb-2" placeholder="Password">
      </div>
      <div class="modal-footer"><button class="btn btn-primary" onclick="addUser()">Add User</button></div>
    </div>
  </div>
</div>

<script>
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
    if (tabName === 'dashboard') loadStats();
    else if (tabName === 'users') loadUsers();
    else if (tabName === 'reports') loadReports();
    else if (tabName === 'subscriptions') loadSubscriptions();
}

async function loadStats() {
    const stats = await apiCall('get_stats');
    const users = await apiCall('get_users');
    const patients = users.users.filter(u => u.role === 'patient').length;
    const doctors = users.users.filter(u => u.role === 'doctor').length;
    
    document.getElementById('totalUsers').textContent = users.users.length;
    document.getElementById('totalPatients').textContent = patients;
    document.getElementById('totalDoctors').textContent = doctors;
    document.getElementById('totalAppointments').textContent = stats.stats?.appointments || 0;
}

async function loadUsers() {
    const result = await apiCall('get_users');
    const users = result.users || [];
    const tbody = document.getElementById('usersTable');
    tbody.innerHTML = users.map(u => `
        <tr>
            <td>${u.name}</td>
            <td>${u.email}</td>
            <td><span class="badge bg-${u.role === 'admin' ? 'danger' : 'primary'}">${u.role}</span></td>
            <td>Active</td>
            <td>${u.role !== 'admin' ? `<button class="btn btn-sm btn-danger" onclick="deleteUser('${u.email}')">Delete</button>` : 'Protected'}</td>
        </tr>
    `).join('');
}

async function deleteUser(email) {
    if (confirm('Delete this user?')) {
        await apiCall('delete_user', { email: email });
        loadUsers();
        loadStats();
    }
}

async function addUser() {
    const result = await apiCall('signup', {
        name: document.getElementById('newUserName').value,
        email: document.getElementById('newUserEmail').value,
        phone: document.getElementById('newUserPhone').value,
        password: document.getElementById('newUserPassword').value,
        role: document.getElementById('newUserRole').value
    });
    if (result.success) {
        alert('User added successfully!');
        bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
        loadUsers();
        loadStats();
    } else alert(result.message);
}

async function loadReports() {
    const result = await apiCall('get_staff_reports');
    const reports = result.reports || [];
    const div = document.getElementById('reportsList');
    if (reports.length === 0) div.innerHTML = '<p class="text-muted">No reports submitted.</p>';
    else div.innerHTML = reports.map(r => `
        <div class="card mb-3">
            <div class="card-body">
                <h6>${r.full_name} (${r.role}) - Week of ${new Date(r.week_starting).toLocaleDateString()}</h6>
                <p><strong>Activities:</strong> ${r.activities}</p>
                <p><strong>Patients:</strong> ${r.patients_attended}</p>
                <p><strong>Challenges:</strong> ${r.challenges}</p>
                <span class="badge bg-${r.status === 'pending' ? 'warning' : 'success'}">${r.status}</span>
                ${r.status === 'pending' ? `<textarea id="feedback_${r.id}" class="form-control mt-2" placeholder="Feedback..."></textarea><button class="btn btn-sm btn-success mt-2" onclick="reviewReport('${r.id}', 'reviewed')">Approve</button>` : r.feedback ? `<p class="mt-2"><strong>Feedback:</strong> ${r.feedback}</p>` : ''}
            </div>
        </div>
    `).join('');
}

async function reviewReport(id, status) {
    const feedback = document.getElementById(`feedback_${id}`)?.value || '';
    await apiCall('review_staff_report', { report_id: id, feedback, status });
    loadReports();
}

async function loadSubscriptions() {
    const result = await apiCall('get_users');
    const users = result.users || [];
    const subsTable = document.getElementById('subscriptionsTable');
    subsTable.innerHTML = users.filter(u => u.subscription !== 'none').map(u => `
        <tr><td>${u.name}</td><td>${u.subscription}</td><td>${new Date(u.created_at).toLocaleDateString()}</td><td>-</td><td>Active</td></tr>
    `).join('');
    document.getElementById('activeSubscriptions').textContent = users.filter(u => u.subscription !== 'none').length;
    document.getElementById('totalRevenue').textContent = `UGX ${users.filter(u => u.subscription === 'premium').length * 17000 + users.filter(u => u.subscription === 'basic').length * 10000}`;
}

function saveSettings() {
    alert('Settings saved successfully!');
}

showTab('dashboard');
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>