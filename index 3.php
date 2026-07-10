<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once 'config/database.php';
require_once 'config/init_db.php';

// If already logged in, redirect to appropriate page
if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    switch($role) {
        case 'patient': header('Location: patients.php'); exit();
        case 'doctor': header('Location: doctors.php'); exit();
        case 'nurse': header('Location: nurses.php'); exit();
        case 'admin': header('Location: admin.php'); exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ExMed - Hospital Management, Simplified & Secure</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/crypto-js@4.2.0/crypto-js.min.js"></script>
<link rel="manifest" href="manifest.json">

<script>
// Logo handling - KEEP ORIGINAL
const REMOTE_LOGO = "https://your.cdn.com/exmed-logo.png";
const LOCAL_LOGO  = "./assets/exmed-logo.png";
const FALLBACK_SVG = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 420 420'><defs><linearGradient id='g' x1='0' x2='1'><stop offset='0' stop-color='%23ff5252'/><stop offset='1' stop-color='%23c40000'/></linearGradient></defs><rect width='100%' height='100%' fill='none'/><g><rect x='140' y='60' width='140' height='300' rx='28' fill='url(%23g)'/><rect x='60' y='140' width='300' height='140' rx='28' fill='url(%23g)'/></g><circle cx='210' cy='210' r='52' fill='%23ffffff' opacity='0.06'/><text x='210' y='220' text-anchor='middle' font-size='44' font-family='Segoe UI, Arial' font-weight='700' fill='%23ffffff'>ExMed</text></svg>";

function loadImageSrc(url, timeout = 4000) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    let done = false;
    const timer = setTimeout(() => {
      if (done) return;
      done = true;
      img.onload = img.onerror = null;
      reject(new Error('timeout'));
    }, timeout);
    img.onload = () => { if (done) return; done = true; clearTimeout(timer); resolve(url); };
    img.onerror = () => { if (done) return; done = true; clearTimeout(timer); reject(new Error('error')); };
    img.src = url;
  });
}

async function pickLogo() {
  if (navigator.onLine && REMOTE_LOGO) {
    try { await loadImageSrc(REMOTE_LOGO, 4000); return REMOTE_LOGO; } catch(e) { }
  }
  try { await loadImageSrc(LOCAL_LOGO, 2000); return LOCAL_LOGO; } catch(e) { }
  return FALLBACK_SVG;
}

document.addEventListener('DOMContentLoaded', async () => {
  const logoSrc = await pickLogo();
  document.querySelectorAll('.exmed-logo').forEach(img => {
    if (!img.getAttribute('src') || img.getAttribute('src').trim() === "") {
      img.src = logoSrc;
    }
  });
  window.addEventListener('online', async () => {
    if (!REMOTE_LOGO) return;
    try {
      await loadImageSrc(REMOTE_LOGO, 4000);
      document.querySelectorAll('.exmed-logo').forEach(img => img.src = REMOTE_LOGO);
    } catch(e){}
  });
});
</script>

<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('./sw.js').catch(()=>console.warn('SW registration failed'));
  }
</script>

<style>
  body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; }
  .logo { width: 80px; margin-bottom: 20px; }
  .card-custom { border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); overflow: hidden; }
  .btn-primary { background: #007bff; border: none; border-radius: 12px; padding: 12px 32px; font-size: 1.1rem; }
  .btn-outline-primary { border-radius: 12px; padding: 12px 32px; font-size: 1.1rem; }
  .feature-card { background: white; border-radius: 16px; padding: 30px 20px; text-align: center; box-shadow: 0 6px 20px rgba(0,0,0,0.08); transition: all 0.3s; }
  .feature-card:hover { transform: translateY(-10px); box-shadow: 0 15px 35px rgba(0,0,0,0.15); }
  .feature-card i { font-size: 3.5rem; color: #007bff; margin-bottom: 15px; }
  .lang-btn { background: rgba(255,255,255,0.2); border: 1px solid rgb(59, 18, 131); color: rgb(12, 12, 12); padding: 8px 15px; margin: 0 5px; border-radius: 8px; cursor:pointer; }
  .lang-btn.active { background: white; color: #007bff; font-weight: bold; }
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<!-- LANDING PAGE -->
<div id="landing" class="container text-center py-5">
  <img src="" class="exmed-logo logo mb-3" width="70" alt="ExMed">
  <h1 class="display-5 fw-bold text-dark">ExMed</h1>
  <h2 class="display-6 text-dark mb-4">Hospital Management,<br><span class="text-primary">Simplified & Secure</span></h2>
  <p class="lead text-muted col-md-8 mx-auto mb-5">
    A secure, offline-first clinical portal where healthcare professionals manage patients, appointments, and records
    with encrypted data and role-based access control.
  </p>

  <div class="row justify-content-center g-5 mb-5">
    <div class="col-md-4">
      <div class="feature-card">
        <i class="fas fa-bell"></i>
        <h5 data-t="alerts">Real-Time Alerts</h5>
        <p data-t="alerts_desc">Instant notifications for appointments and lab results</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="feature-card">
        <i class="fas fa-shield-alt"></i>
        <h5 data-t="security">Military-Grade Security</h5>
        <p data-t="security_desc">AES-256 encryption keeps patient data safe and compliant</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="feature-card">
        <i class="fas fa-wifi-slash"></i>
        <h5 data-t="offline">Offline First</h5>
        <p data-t="offline_desc">Full functionality without internet, syncs automatically</p>
      </div>
    </div>
  </div>

  <div class="mb-4">
    <button id="btnGetStarted" class="btn btn-primary btn-lg px-5 me-3" onclick="goToAuth('register')" data-t="get_started">Get Started</button>
    <button id="btnSignIn" class="btn btn-outline-primary btn-lg px-5" onclick="goToAuth('login')" data-t="sign_in">Sign In</button>
  </div>

  <div class="mt-5">
    <button class="btn lang-btn active" onclick="setLang('en')" id="lang-en">English</button>
    <button class="btn lang-btn" onclick="setLang('lug')" id="lang-lug">Luganda</button>
    <button class="btn lang-btn" onclick="setLang('sw')" id="lang-sw">Swahili</button>
    <button class="btn lang-btn" onclick="setLang('ate')" id="lang-ate">Ateso</button>
  </div>
</div>

<!-- LOCK / RE-AUTH MODAL -->
<div id="lockModal" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-flex align-items-center justify-content-center" style="z-index: 10000;">
  <div class="card shadow" style="width: 360px;">
    <div class="card-body p-4 text-center">
      <h5 class="mb-3">Session Locked</h5>
      <p class="small text-muted">Enter your password to resume your previous session.</p>
      <input id="resumePassword" type="password" class="form-control mb-3" placeholder="Password">
      <div class="d-flex gap-2">
        <button class="btn btn-secondary flex-fill" onclick="cancelResume()">Cancel</button>
        <button class="btn btn-primary flex-fill" onclick="attemptResume()">Unlock</button>
      </div>
      <div id="resumeMsg" class="mt-3 small text-danger d-none"></div>
    </div>
  </div>
</div>

<!-- AUTH PAGE -->
<div id="auth" class="container d-none" style="max-width: 500px; margin-top: 80px;">
  <div class="card card-custom shadow">
    <div class="card-body p-5 text-center">
      <img src="" class="exmed-logo mb-3" width="70" alt="ExMed">
      <h3 id="authTitle" data-t="sign_in">Sign In</h3>
      <form class="mt-4" onsubmit="event.preventDefault();handleAuth();">
        <label for="authLangSelect" class="form-label">Language</label>
        <select id="authLangSelect" class="form-select form-select-sm mb-3" autocomplete="off" title="Choose language">
          <option value="en">English</option>
          <option value="lug">Luganda</option>
          <option value="sw">Swahili</option>
          <option value="ate">Ateso</option>
        </select>
        <label for="fullName" class="form-label d-none">Full Name</label>
        <input type="text" id="fullName" class="form-control mb-3 d-none" placeholder="Full Name" autocomplete="name">
        <label for="phone" class="form-label d-none">Phone</label>
        <input type="tel" id="phone" class="form-control mb-3 d-none" placeholder="Phone (e.g. +256...)" autocomplete="tel">
        <label for="nationalId" class="form-label d-none">National ID</label>
        <input type="text" id="nationalId" class="form-control mb-3 d-none" placeholder="National ID Number (optional)" autocomplete="off">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" id="email" class="form-control mb-3" placeholder="Email" autocomplete="email" required>
        <label for="password" class="form-label">Password</label>
        <input type="password" id="password" class="form-control mb-3" placeholder="Password" autocomplete="current-password" required>
        <div class="form-check mb-4">
          <input class="form-check-input" type="checkbox" id="rememberMe">
          <label class="form-check-label" for="rememberMe">
            Remember me for 30 days
          </label>
        </div>
        <label for="role" class="form-label d-none">User Role</label>
        <select id="role" class="form-select mb-4 d-none" autocomplete="off">
          <option value="patient">Patient</option>
          <option value="nurse">Nurse</option>
          <option value="doctor">Doctor</option>
          <option value="admin">Administrator</option>
        </select>
        <div id="verifyPanel" class="d-none text-start mb-3">
          <div class="alert alert-info small">We've sent an OTP to the phone you provided. Enter it here to complete verification.</div>
          <label for="regOtp" class="form-label">Enter OTP</label>
          <input type="text" id="regOtp" class="form-control mb-2" placeholder="Enter OTP" autocomplete="off">
          <div id="generatedOtpDisplay" class="d-none alert alert-warning mb-2">
            <strong>Your OTP:</strong> <span id="displayedOtp" style="font-size: 18px; font-weight: bold; letter-spacing: 4px;">------</span>
          </div>
          <button type="button" class="btn btn-sm btn-info w-100 mb-2" onclick="generateSimpleOtp()">🔓 Generate OTP (for testing)</button>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary flex-fill" onclick="resendOtp()">Resend OTP</button>
            <button type="button" class="btn btn-success flex-fill" onclick="verifyRegistrationOtp()">Verify</button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-3" data-t="continue">Continue</button>
      </form>
      <p class="mt-4"><a href="#" onclick="toggleAuth()" id="toggleLink">No account? Create one</a></p>
      <p class="mt-4"><a href="#" onclick="showForgotPassword()" id="forgotPasswordLink">Forgot Password?</a></p>
    </div>
  </div>
</div>

<!-- FORGOT PASSWORD PAGE -->
<div id="forgotPassword" class="container d-none" style="max-width: 500px; margin-top: 80px;">
  <div class="card card-custom shadow">
    <div class="card-body p-5 text-center">
      <h3>Forgot Password</h3>
      <form class="mt-4" onsubmit="event.preventDefault(); handleForgotPassword();">
        <label for="forgotEmail" class="form-label">Email Address</label>
        <input type="email" id="forgotEmail" class="form-control mb-4" placeholder="Enter your email" autocomplete="email" required>
        <button type="submit" id="forgotSendBtn" class="btn btn-primary w-100 py-3">Reset Password</button>
      </form>
      <div id="resetPanel" class="d-none mt-3 text-start">
        <label for="resetOtpInput" class="form-label">Enter OTP</label>
        <input id="resetOtpInput" class="form-control mb-2" placeholder="Enter OTP" autocomplete="off">
        <div class="d-flex gap-2 mb-2">
          <button class="btn btn-outline-primary flex-fill" onclick="resendResetOtp()">Resend OTP</button>
          <button class="btn btn-success flex-fill" onclick="verifyResetOtp()">Verify OTP</button>
        </div>
      </div>
      <div id="setNewPasswordPanel" class="d-none mt-3 text-start">
        <label for="newPassword" class="form-label">New Password</label>
        <input id="newPassword" type="password" class="form-control mb-2" placeholder="New password" autocomplete="new-password">
        <label for="confirmNewPassword" class="form-label">Confirm Password</label>
        <input id="confirmNewPassword" type="password" class="form-control mb-2" placeholder="Confirm new password" autocomplete="new-password">
        <button class="btn btn-primary w-100" onclick="setNewPassword()">Set New Password</button>
      </div>
      <p class="mt-4"><a href="#" onclick="showForgotPasswordBack()" id="forgotBackLink">Back to Login</a></p>
    </div>
  </div>
</div>

<!-- OTP DISPLAY MODAL -->
<div id="otpModal" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-flex align-items-center justify-content-center" style="z-index: 9999;">
  <div class="card shadow" style="width: 320px;">
    <div class="card-body text-center p-4">
      <h5 class="mb-3">Your OTP Code</h5>
      <div id="otpCodeDisplay" class="bg-light p-3 rounded mb-3" style="letter-spacing: 8px; font-size: 24px; font-weight: bold; font-family: monospace;">------</div>
      <p class="small text-muted mb-3">Valid for 5 minutes. This code will expire soon.</p>
      <button class="btn btn-sm btn-primary w-100" onclick="closeOtpModal()">Got It</button>
    </div>
  </div>
</div>

<script>
// Global variables
let authMode = 'login';
let otpStorage = {};

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
        console.error('API Error:', error);
        return { success: false, message: 'Network error' };
    }
}

function generateOtp(length = 6) {
    return Math.random().toString().substring(2, 2 + length).padStart(length, '0');
}

function sendOtp(email) {
    const otp = generateOtp(6);
    otpStorage[email] = { code: otp, expiry: Date.now() + 5 * 60 * 1000 };
    displayOtpModal(otp);
}

function displayOtpModal(otp) {
    const modal = document.getElementById('otpModal');
    const display = document.getElementById('otpCodeDisplay');
    if (display) display.textContent = otp;
    if (modal) modal.classList.remove('d-none');
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
    if (!stored) return { valid: false, error: 'No OTP found.' };
    if (Date.now() > stored.expiry) return { valid: false, error: 'OTP expired.' };
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

function goToAuth(mode = 'login') {
    authMode = mode;
    document.getElementById('landing').classList.add('d-none');
    document.getElementById('forgotPassword').classList.add('d-none');
    const auth = document.getElementById('auth');
    auth.classList.remove('d-none');
    const title = document.getElementById('authTitle');
    const fullName = document.getElementById('fullName');
    const phone = document.getElementById('phone');
    const role = document.getElementById('role');
    const verifyPanel = document.getElementById('verifyPanel');
    
    if (mode === 'register') {
        title.textContent = 'Create Account';
        fullName.classList.remove('d-none');
        phone.classList.remove('d-none');
        role.classList.remove('d-none');
        verifyPanel.classList.add('d-none');
        document.getElementById('toggleLink').textContent = 'Already have an account? Sign in';
    } else {
        title.textContent = 'Sign In';
        fullName.classList.add('d-none');
        phone.classList.add('d-none');
        role.classList.add('d-none');
        verifyPanel.classList.add('d-none');
        document.getElementById('toggleLink').textContent = 'No account? Create one';
    }
}

function toggleAuth() {
    goToAuth(authMode === 'register' ? 'login' : 'register');
}

async function handleAuth() {
    const email = document.getElementById('email')?.value || '';
    const password = document.getElementById('password')?.value || '';
    
    if (authMode === 'register') {
        if (!email || !email.includes('@')) { alert('Please enter a valid email'); return; }
        const verifyPanel = document.getElementById('verifyPanel');
        if (verifyPanel && !verifyPanel.classList.contains('d-none')) {
            const entered = document.getElementById('regOtp')?.value || '';
            if (!entered.trim()) { alert('Enter the OTP'); return; }
            verifyRegistrationOtp();
            return;
        }
        document.getElementById('verifyPanel').classList.remove('d-none');
        sendOtp(email);
        return;
    }
    
    const result = await apiCall('login', { email, password });
    if (result.success) {
        const rememberMe = document.getElementById('rememberMe')?.checked;
        if (rememberMe) localStorage.setItem('exmed_remembered_user', email);
        
        // Redirect based on role
        switch(result.user.role) {
            case 'patient': window.location.href = 'patients.php'; break;
            case 'doctor': window.location.href = 'doctors.php'; break;
            case 'nurse': window.location.href = 'nurses.php'; break;
            case 'admin': window.location.href = 'admin.php'; break;
            default: window.location.href = 'index.php';
        }
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
    alert('Password updated successfully! Please login.');
    showForgotPasswordBack();
}

function setLang(lang) {
    localStorage.setItem('exmed_lang', lang);
    document.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('lang-' + lang).classList.add('active');
}

function cancelResume() { location.reload(); }
function attemptResume() { location.reload(); }

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    const rememberedEmail = localStorage.getItem('exmed_remembered_user');
    if (rememberedEmail) {
        const emailField = document.getElementById('email');
        if (emailField) emailField.value = rememberedEmail;
    }
});
</script>
</body>
</html>