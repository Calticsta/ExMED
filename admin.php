<?php
// admin.php - Admin dashboard content
?>
<script>
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
    const apptCount = document.getElementById('adminApptCount');
    
    if (pendingCount) pendingCount.textContent = stats.pending_appointments || '0';
    if (reportCount) reportCount.textContent = '0';
    if (staffCount) staffCount.textContent = doctors + nurses;
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
</script>

<!-- ADMIN NAVIGATION -->
<ul class="nav nav-pills justify-content-center flex-wrap mb-4 gap-2" id="adminNav">
    <li class="nav-item"><a class="nav-link active" href="#" onclick="event.preventDefault(); showTab('admin-home')"><i class="fas fa-home"></i> Home</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('admin')"><i class="fas fa-users-cog"></i> Users & Staff</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('admin-billing')"><i class="fas fa-credit-card"></i> Billing</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('admin-subscription')"><i class="fas fa-crown"></i> Plans</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('admin-reports')"><i class="fas fa-file-contract"></i> Staff Reports</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('admin-settings')"><i class="fas fa-cog"></i> Settings</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('profile')"><i class="fas fa-user"></i> Profile</a></li>
</ul>

<!-- ADMIN HOME TAB -->
<div id="tab-admin-home" class="row g-4">
    <div class="col-12"><div class="card p-4"><h4><i class="fas fa-users-cog"></i> Administration Dashboard</h4><p class="text-muted">Full system administration and management portal for hospital operations and staff management.</p></div></div>
    <div class="col-md-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="d-flex justify-content-between align-items-start"><div><p class="text-muted small mb-1"><i class="fas fa-file-alt"></i> Pending Reports</p><h3 class="mb-0" id="adminPendingCount">0</h3></div><span class="badge bg-warning">Reports</span></div></div></div></div>
    <div class="col-md-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="d-flex justify-content-between align-items-start"><div><p class="text-muted small mb-1"><i class="fas fa-check-circle"></i> Total Reports</p><h3 class="mb-0" id="adminReportCount">0</h3></div><span class="badge bg-info">All</span></div></div></div></div>
    <div class="col-md-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="d-flex justify-content-between align-items-start"><div><p class="text-muted small mb-1"><i class="fas fa-users"></i> Active Staff</p><h3 class="mb-0" id="adminStaffCount">0</h3></div><span class="badge bg-success">Staff</span></div></div></div></div>
    <div class="col-md-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="d-flex justify-content-between align-items-start"><div><p class="text-muted small mb-1"><i class="fas fa-calendar-check"></i> Appointments</p><h3 class="mb-0" id="adminApptCount">0</h3></div><span class="badge bg-secondary">Today</span></div></div></div></div>
</div>

<!-- ADMIN USERS & STAFF TAB -->
<div id="tab-admin" class="d-none card p-4">
    <h4><i class="fas fa-users"></i> Users & Staff Management</h4>
    <div class="nav nav-tabs mb-3"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#allUsers">All Users</button><button class="nav-link" data-bs-toggle="tab" data-bs-target="#addStaff">Add Staff</button></div>
    <div class="tab-content">
        <div id="allUsers" class="tab-pane fade show active"><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Name</th><th>Role</th><th>Email</th><th>Actions</th></tr></thead><tbody id="usersTableBody"></tbody></table></div></div>
        <div id="addStaff" class="tab-pane fade"><div class="row g-2"><div class="col-md-6"><input type="text" id="staffName" class="form-control" placeholder="Full Name" required></div><div class="col-md-6"><input type="email" id="staffEmail" class="form-control" placeholder="Email" required></div><div class="col-md-6"><select id="staffRole" class="form-select" required><option>-- Select Role --</option><option value="doctor">Doctor</option><option value="nurse">Nurse</option><option value="admin">Admin</option></select></div><div class="col-md-6"><input type="tel" id="staffPhone" class="form-control" placeholder="Phone"></div><div class="col-12"><button class="btn btn-primary w-100" onclick="addStaffMember()">Add Staff Member</button></div></div></div>
    </div>
</div>

<!-- ADMIN BILLING TAB -->
<div id="tab-admin-billing" class="d-none card p-4">
    <h4><i class="fas fa-credit-card"></i> Billing & Payments</h4>
    <div class="alert alert-info mb-3" id="revenueDisplay">Total Revenue: <strong>UGX 0</strong> | Active Subscriptions: <strong>0</strong></div>
    <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Patient</th><th>Plan</th><th>Amount</th><th>Status</th></tr></thead><tbody id="billingTableBody"></tbody></table></div>
</div>

<!-- ADMIN REPORTS TAB -->
<div id="tab-admin-reports" class="d-none card p-4">
    <h4><i class="fas fa-file-contract"></i> Staff Weekly Reports & Feedback</h4>
    <p class="text-muted">Review reports submitted by doctors and nurses. Provide feedback to support your team.</p>
    <div id="adminReportsList" class="mt-4"></div>
</div>

<!-- ADMIN SUBSCRIPTION TAB -->
<div id="tab-admin-subscription" class="d-none card p-4">
    <h4><i class="fas fa-credit-card"></i> Subscription Management</h4>
    <p class="text-muted mb-4">Manage system subscription plans and view payment analytics.</p>
    <ul class="nav nav-tabs mb-3"><li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#adminPlansTab">Manage Plans</a></li><li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#subscriptionAnalytics">Analytics</a></li></ul>
    <div class="tab-content">
        <div id="adminPlansTab" class="tab-pane fade show active"><div class="card p-3 mb-4"><h6>Add New Plan</h6><div class="row g-3"><div class="col-md-4"><input type="text" id="planNameInput" class="form-control" placeholder="Plan Name"></div><div class="col-md-4"><input type="number" id="planPriceInput" class="form-control" placeholder="Price (UGX)"></div><div class="col-md-4"><input type="text" id="planFeaturesInput" class="form-control" placeholder="Features (comma separated)"></div></div><button class="btn btn-primary mt-3 w-100" onclick="addNewPlan()">Create Plan</button></div><div id="adminPlansList" class="row g-3"></div></div>
        <div id="subscriptionAnalytics" class="tab-pane fade"><div class="row"><div class="col-md-4"><div class="card p-3 text-center"><h6>Total Subscribers</h6><h3 id="totalSubscribers">0</h3></div></div><div class="col-md-4"><div class="card p-3 text-center"><h6>Monthly Revenue</h6><h3 id="monthlyRevenue">UGX 0</h3></div></div><div class="col-md-4"><div class="card p-3 text-center"><h6>Active Trials</h6><h3 id="activeTrials">0</h3></div></div></div></div>
    </div>
</div>

<!-- ADMIN SETTINGS TAB -->
<div id="tab-admin-settings" class="d-none card p-4">
    <h4><i class="fas fa-cog"></i> System Settings</h4>
    <div class="card p-3"><h6>Hospital Configuration</h6><div class="row g-3"><div class="col-md-6"><label class="form-label">Hospital Name</label><input type="text" id="hospitalName" class="form-control" value="ExMed Hospital"></div><div class="col-md-6"><label class="form-label">Emergency Hotline</label><input type="tel" id="emergencyHotline" class="form-control" value="+256701111111"></div><div class="col-md-6"><label class="form-label">Support Email</label><input type="email" id="supportEmail" class="form-control" value="support@exmed.ug"></div><div class="col-md-6"><label class="form-label">Working Hours</label><input type="text" id="workingHours" class="form-control" value="8 AM - 6 PM"></div></div><button class="btn btn-primary mt-3 w-100" onclick="saveSystemSettings()">Save Settings</button></div>
</div>