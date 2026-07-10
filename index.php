<?php
session_start();
header('Content-Type: text/html; charset=utf-8');


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
// ======================= MULTI-LANGUAGE SYSTEM =======================
const translations = {
    en: {
        // Landing page
        title: "ExMed - Hospital Management, Simplified & Secure",
        tagline: "Hospital Management,<br><span class='text-primary'>Simplified & Secure</span>",
        description: "A secure, offline-first clinical portal where healthcare professionals manage patients, appointments, and records with encrypted data and role-based access control.",
        alerts: "Real-Time Alerts",
        alerts_desc: "Instant notifications for appointments and lab results",
        security: "Military-Grade Security",
        security_desc: "AES-256 encryption keeps patient data safe and compliant",
        offline: "Offline First",
        offline_desc: "Full functionality without internet, syncs automatically",
        get_started: "Get Started",
        sign_in: "Sign In",
        // Auth page
        create_account: "Create Account",
        email_address: "Email Address",
        password: "Password",
        remember_me: "Remember me for 30 days",
        continue_btn: "Continue",
        no_account: "No account? Create one",
        already_account: "Already have an account? Sign in",
        forgot_password: "Forgot Password?",
        full_name: "Full Name",
        phone: "Phone (e.g. +256...)",
        national_id: "National ID Number (optional)",
        user_role: "User Role",
        patient: "Patient",
        nurse: "Nurse",
        doctor: "Doctor",
        admin: "Administrator",
        // OTP
        otp_sent: "We've sent an OTP to your email. Enter it here to complete verification.",
        enter_otp: "Enter OTP",
        generate_otp_test: "Generate OTP (for testing)",
        resend_otp: "Resend OTP",
        verify: "Verify",
        your_otp: "Your OTP:",
        // Forgot password
        reset_password: "Reset Password",
        enter_email_reset: "Enter your email",
        reset_password_btn: "Reset Password",
        enter_otp_reset: "Enter OTP",
        new_password: "New Password",
        confirm_password: "Confirm Password",
        set_new_password: "Set New Password",
        back_to_login: "Back to Login",
        // Lock modal
        session_locked: "Session Locked",
        enter_password_resume: "Enter your password to resume your previous session.",
        unlock: "Unlock",
        cancel: "Cancel",
        // OTP modal
        your_otp_code: "Your OTP Code",
        valid_minutes: "Valid for 5 minutes. This code will expire soon.",
        got_it: "Got It",
        // Alerts
        login_success: "Login successful! Redirecting...",
        invalid_credentials: "Invalid email or password",
        account_created: "Account created! Please login.",
        otp_expired: "OTP expired.",
        incorrect_otp: "Incorrect OTP.",
        password_mismatch: "Passwords do not match",
        password_short: "Password too short (min 3 characters)",
        password_updated: "Password updated successfully! Please login.",
        enter_valid_email: "Please enter a valid email",
        enter_otp_first: "Enter the OTP",
        enter_email_first: "Enter email first",
        network_error: "Network error",
        // ===== DEMO LOCK - ADDED =====
        demo_lock_login: "⚠️ ACCESS RESTRICTED\n\nThis system is currently in demo mode.\nPlease contact the system developer:\nMR. YAHAYA\n\nFull access will be granted upon payment completion.",
        demo_lock_signup: "⚠️ REGISTRATION LOCKED\n\nNew account creation is currently disabled.\nPlease contact:\nMR. YAHAYA\nfor system access and activation."
    },
    lug: { // Luganda
        title: "ExMed - Enkola Eyedwaliro Enyanggu Era Eye Sigika",
        tagline: "Okudukanya eddwaliro,<br><span class='text-primary'>Enyangu Era Eyesigika</span>",
        description: "Empereza Enyangu Okuva eri abasawo baffe ku by'Obulamu ne okudukanya Eddwaliro layffe.",
        alerts: "Obubaka Mubudde",
        alerts_desc: "okufuna Obubaka mubwangu N'ebiva Ewa omusawo",
        security: "Obukuumi GuluGulu",
        security_desc: "Okukuumibwa kwa AES-256 kukuuma ebiwandiiko by'abalwadde",
        offline: "Okozesa Nga Tewali Yintaneeti",
        offline_desc: "Omulimu gwonna nga tewali yintaneeti, gyeziza yekka",
        get_started: "Okutandika",
        sign_in: "Yingiza mu Ebikukwaata Ko",
        create_account: "Tandika Akaunti",
        email_address: "Teekamu Email Address Yo",
        password: "Ekigambo Ky'okukungukiriza",
        remember_me: "Okujukira okumala ennaku 30",
        continue_btn: "Weyongereyo",
        no_account: "Tolina kaunti? Tandika Buto",
        already_account: "Olina akaunti? Yingizamu Ebikwogerako",
        forgot_password: "Nga werabidde Password Yo?",
        full_name: "Erinnya Erijjuvu",
        phone: "Essimu (Ng. +256...)",
        national_id: "Namba ya National ID (obusobola)",
        user_role: "Ekifo Ky'Ku Dwaliro Lyaffe",
        patient: "Omulwadde",
        nurse: "Musawo Omuyambi",
        doctor: "Musawo",
        admin: "Omudukanya",
        otp_sent: "Tukuwereza OTP. Gyingize wano okukakasa.",
        enter_otp: "Yingiza OTP",
        generate_otp_test: "Kola OTP (okugezesa)",
        resend_otp: "Damu OTP Nate",
        verify: "Kakasa",
        your_otp: "OTP Yo:",
        reset_password: "Ddamu Ekigambo Ky'okukungukiriza",
        enter_email_reset: "Gyingiza eriyansi yo",
        reset_password_btn: "Ddamu Ekigambo",
        enter_otp_reset: "Gyingiza OTP",
        new_password: "Ekigambo Ekipya",
        confirm_password: "Kakasa Ekigambo",
        set_new_password: "Teeka Ekigambo Ekipya",
        back_to_login: "Ddayo Ku Kuyingira",
        session_locked: "Omulimu Guggaddwa",
        enter_password_resume: "Gyingiza ekigambo kyo okuddamu okutandika.",
        unlock: "Ggulawo",
        cancel: "Lekayo",
        your_otp_code: "Koodi Ya OTP Yo",
        valid_minutes: "Kola okumala dakika 5. Koodi eno eriggwawo.",
        got_it: "Ntegeera",
        login_success: "Oyingingidde bulungi! Tukutwala...",
        invalid_credentials: "Eriyansi oba ekigambo si kituufu",
        account_created: "Akaunti yatondebwa! Nsaba yingira.",
        otp_expired: "OTP yaggwaako ekiseera.",
        incorrect_otp: "OTP si tuufu.",
        password_mismatch: "Ebigambo tebikwatagana",
        password_short: "Ekigambo kifu (bwetaaga bujjuvu 3)",
        password_updated: "Ekigambo kikyusiddwa! Nsaba yingira.",
        enter_valid_email: "Nsaba gyingiza eriyansi entuufu",
        enter_otp_first: "Gyingiza OTP",
        enter_email_first: "Gyingiza eriyansi okusooka",
        network_error: "Obuzibu bw'omutimbagano",
        // ===== DEMO LOCK - ADDED =====
        demo_lock_login: "⚠️ OKWENGERA KUZIBIKO\n\nOmulimu guno guli mu ngeri y'okulaga.\nNsaba okwogera n'omukola wa sisitemu:\nMR. YAHAYA\n\nOkufuna obuyinza bwonna, malamu okusasula.",
        demo_lock_signup: "⚠️ OKUTANDIKA AKKAUNTI KUZIBIKO\n\nOkutandika akaunti kuzibikodwa kati.\nNsaba okwogera n'a:\nMR. YAHAYA\nokufuna obuyinza."
    },
    sw: { // Swahili
        title: "ExMed - Usimamizi wa Hospitali, Rahisi na Salama",
        tagline: "Usimamizi wa Hospitali,<br><span class='text-primary'>Rahisi na Salama</span>",
        description: "Jukwaa salama la kliniki ambalo wataalamu wa afya wanasimamia wagonjwa, miadi na rekodi kwa data iliyosimbwa na udhibiti wa ufikiaji kulingana na majukumu.",
        alerts: "Arifa za Wakati Halisi",
        alerts_desc: "Arifa za papo kwa hapo kwa miadi na matokeo ya maabara",
        security: "Usalama wa Kijeshi",
        security_desc: "Usimbaji fiche wa AES-256 huweka data ya mgonjwa salama",
        offline: "Bila Mtandao Kwanza",
        offline_desc: "Utendaji kamili bila mtandao, husawazisha kiotomatiki",
        get_started: "Anza",
        sign_in: "Ingia",
        create_account: "Fungua Akaunti",
        email_address: "Barua Pepe",
        password: "Nenosiri",
        remember_me: "Nikumbuke kwa siku 30",
        continue_btn: "Endelea",
        no_account: "Huna akaunti? Fungua moja",
        already_account: "Una akaunti? Ingia",
        forgot_password: "Umesahau Nenosiri?",
        full_name: "Jina Kamili",
        phone: "Simu (mfano +256...)",
        national_id: "Namba ya Kitambulisho cha Taifa (si lazima)",
        user_role: "Nafasi ya Mtumiaji",
        patient: "Mgonjwa",
        nurse: "Muuguzi",
        doctor: "Daktari",
        admin: "Msimamizi",
        otp_sent: "Tumetuma OTP kwa barua pepe yako. Ingiza hapa kukamilisha uthibitishaji.",
        enter_otp: "Ingiza OTP",
        generate_otp_test: "Tengeneza OTP (kwa majaribio)",
        resend_otp: "Tuma OTP Tena",
        verify: "Thibitisha",
        your_otp: "OTP Yako:",
        reset_password: "Weka Upya Nenosiri",
        enter_email_reset: "Ingiza barua pepe yako",
        reset_password_btn: "Weka Upya Nenosiri",
        enter_otp_reset: "Ingiza OTP",
        new_password: "Nenosiri Jipya",
        confirm_password: "Thibitisha Nenosiri",
        set_new_password: "Weka Nenosiri Jipya",
        back_to_login: "Rudi Kwenye Kuingia",
        session_locked: "Kipindi Kimefungwa",
        enter_password_resume: "Ingiza nenosiri lako kuendelea na kipindi chako.",
        unlock: "Fungua",
        cancel: "Ghairi",
        your_otp_code: "Msimbo Wako wa OTP",
        valid_minutes: "Inatumika kwa dakika 5. Msimbo huu utaisha hivi karibuni.",
        got_it: "Nimeelewa",
        login_success: "Umeingia kikamilifu! Inakuelekeza...",
        invalid_credentials: "Barua pepe au nenosiri si sahihi",
        account_created: "Akaunti imeundwa! Tafadhali ingia.",
        otp_expired: "OTP imeisha muda wake.",
        incorrect_otp: "OTP si sahihi.",
        password_mismatch: "Manenosiri hayalingani",
        password_short: "Nenosiri fupi sana (angalau herufi 3)",
        password_updated: "Nenosiri limebadilishwa kikamilifu! Tafadhali ingia.",
        enter_valid_email: "Tafadhali ingiza barua pepe sahihi",
        enter_otp_first: "Ingiza OTP",
        enter_email_first: "Ingiza barua pepe kwanza",
        network_error: "Hitilafu ya mtandao",
        // ===== DEMO LOCK - ADDED =====
        demo_lock_login: "⚠️ UPATIKANAJI UMEZUILIWA\n\nMfumo huu uko katika hali ya onyesho.\nTafadhali wasiliana na msanidi wa mfumo:\nMR. YAHAYA\n\nUpatikanaji kamili utatolewa baada ya malipo kukamilika.",
        demo_lock_signup: "⚠️ USAJILI UMEZUILIWA\n\nUundaji wa akaunti mpya umezuiwa kwa sasa.\nTafadhali wasiliana na:\nMR. YAHAYA\nkwa upatikanaji wa mfumo."
    },
    ate: { // Ateso
        title: "ExMed - Aujakait Idwe, Akwapuc Eong Aine Akine",
        tagline: "Aujakait Idwe,<br><span class='text-primary'>Akwapuc Eong Aine Akine</span>",
        description: "Ekibuga ekipuc ekiyalama aber ai ikoku noi akipuc akwapuc akimak inyeit, ekica n'ebaru ekiyalama ne data ekiyalama ne role-based access control.",
        alerts: "Akinyamutia Akitoreun",
        alerts_desc: "Akinyamutia akitoreun ikoku noi ekica n'ekicokiny",
        security: "Akwapuc Eong Aine Akine",
        security_desc: "AES-256 encryption ekwapuc data ya ikoku noi",
        offline: "Tia Tere Intanet",
        offline_desc: "Akinyamutia akwapuc tia tere intanet, akinyamutia akitoreun",
        get_started: "Ituron",
        sign_in: "Ituron Idwe",
        create_account: "Akwap Akaunti",
        email_address: "E-mail",
        password: "Ekigambo Ekiturwa",
        remember_me: "Akaram akwapuc 30 apol",
        continue_btn: "Akwany",
        no_account: "Tia kaunti? Akwap eka",
        already_account: "Ara kaunti? Ituron idwe",
        forgot_password: "Eriwar Ekigambo Ekiturwa?",
        full_name: "Ekaram Elicok",
        phone: "Esimu (akwana +256...)",
        national_id: "National ID Number (ikakwan)",
        user_role: "Akiteun Akimak",
        patient: "Ikoku Noi",
        nurse: "Akitun Akimak",
        doctor: "Akitun",
        admin: "Aujakait",
        otp_sent: "Akitun OTP e-mail yo. Akitun ka akinyamutia.",
        enter_otp: "Akitun OTP",
        generate_otp_test: "Akwap OTP (akinyamutia)",
        resend_otp: "Akwap OTP Akile",
        verify: "Akaram",
        your_otp: "OTP yo:",
        reset_password: "Akwap Ekigambo Ekiturwa Akile",
        enter_email_reset: "Akitun e-mail yo",
        reset_password_btn: "Akwap Ekigambo Ekiturwa",
        enter_otp_reset: "Akitun OTP",
        new_password: "Ekigambo Ekiturwa Ekile",
        confirm_password: "Akaram Ekigambo Ekiturwa",
        set_new_password: "Akwap Ekigambo Ekiturwa Ekile",
        back_to_login: "Akwany Ituron Idwe",
        session_locked: "Akiteun Akilim",
        enter_password_resume: "Akitun ekigambo ekiturwa yo akinyamutia.",
        unlock: "Akwap",
        cancel: "Akwany",
        your_otp_code: "OTP Koodi Yo",
        valid_minutes: "Akinyamutia dakika 5. Koodi yi akile.",
        got_it: "Akaram",
        login_success: "Ituron idwe! Akinyamutia...",
        invalid_credentials: "E-mail obu ekigambo ekiturwa si atorun",
        account_created: "Akaunti akwap! Ituron idwe.",
        otp_expired: "OTP akile.",
        incorrect_otp: "OTP si atorun.",
        password_mismatch: "Ekigambo ekiturwa si akiteun",
        password_short: "Ekigambo ekiturwa kifu (bwetaaga 3)",
        password_updated: "Ekigambo ekiturwa akwap! Ituron idwe.",
        enter_valid_email: "Akitun e-mail atorun",
        enter_otp_first: "Akitun OTP",
        enter_email_first: "Akitun e-mail akwap",
        network_error: "Akinyamutia network",
        // ===== DEMO LOCK - ADDED =====
        demo_lock_login: "⚠️ AYAPUC AKILIM\n\nAkiteun kai akilim.\nAkwany olo amu akwap:\nMR. YAHAYA\n\nAkiteun akwapuc akan apol.",
        demo_lock_signup: "⚠️ AKAUNTI AKILIM\n\nAkwap akaunti akilim kai.\nAkwany olo amu:\nYAHAYA\nakiteun."
    }
};

let currentLang = localStorage.getItem('exmed_lang') || 'en';

function setLang(lang) {
    if (!translations[lang]) lang = 'en';
    currentLang = lang;
    localStorage.setItem('exmed_lang', lang);
    
    // Update UI text
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (translations[lang][key]) {
            if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                el.placeholder = translations[lang][key];
            } else if (el.tagName === 'SELECT') {
                // For selects, we update options later
            } else {
                el.innerHTML = translations[lang][key];
            }
        }
    });
    
    // Update placeholder for inputs with i18n-placeholder
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        if (translations[lang][key]) el.placeholder = translations[lang][key];
    });
    
    // Update select options
    document.querySelectorAll('select[data-i18n-options]').forEach(select => {
        const optKey = select.getAttribute('data-i18n-options');
        if (optKey && translations[lang][optKey]) {
            // handled separately
        }
    });
    
    // Update role select options
    const roleSelect = document.getElementById('role');
    if (roleSelect) {
        const options = roleSelect.options;
        if (options[0]) options[0].text = translations[lang].patient;
        if (options[1]) options[1].text = translations[lang].nurse;
        if (options[2]) options[2].text = translations[lang].doctor;
        if (options[3]) options[3].text = translations[lang].admin;
    }
    
    // Update language buttons active state
    document.querySelectorAll('.lang-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(`lang-${lang}`)?.classList.add('active');
    
    // Update auth language select
    const authLangSelect = document.getElementById('authLangSelect');
    if (authLangSelect) authLangSelect.value = lang;
}

function getTranslation(key) {
    return translations[currentLang][key] || translations['en'][key] || key;
}

function alertTranslated(key) {
    alert(getTranslation(key));
}
</script>

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
  <h1 class="display-5 fw-bold text-dark" data-i18n="title">ExMed</h1>
  <h2 class="display-6 text-dark mb-4" data-i18n="tagline">Hospital Management,<br><span class="text-primary">Simplified & Secure</span></h2>
  <p class="lead text-muted col-md-8 mx-auto mb-5" data-i18n="description">
    A secure, offline-first clinical portal where healthcare professionals manage patients, appointments, and records
    with encrypted data and role-based access control.
  </p>

  <div class="row justify-content-center g-5 mb-5">
    <div class="col-md-4">
      <div class="feature-card">
        <i class="fas fa-bell"></i>
        <h5 data-i18n="alerts">Real-Time Alerts</h5>
        <p data-i18n="alerts_desc">Instant notifications for appointments and lab results</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="feature-card">
        <i class="fas fa-shield-alt"></i>
        <h5 data-i18n="security">Military-Grade Security</h5>
        <p data-i18n="security_desc">AES-256 encryption keeps patient data safe and compliant</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="feature-card">
        <i class="fas fa-wifi-slash"></i>
        <h5 data-i18n="offline">Offline First</h5>
        <p data-i18n="offline_desc">Full functionality without internet, syncs automatically</p>
      </div>
    </div>
    
  </div>

  <div class="mb-4">
    <button id="btnGetStarted" class="btn btn-primary btn-lg px-5 me-3" onclick="goToAuth('register')" data-i18n="get_started">Get Started</button>
    <button id="btnSignIn" class="btn btn-outline-primary btn-lg px-5" onclick="goToAuth('login')" data-i18n="sign_in">Sign In</button>
  </div>

  <div class="mt-5">
    <button class="btn lang-btn active" onclick="setLang('en')" id="lang-en">English</button>
    <button class="btn lang-btn" onclick="setLang('lug')" id="lang-lug">Luganda</button>
    <button class="btn lang-btn" onclick="setLang('sw')" id="lang-sw">Swahili</button>
    <button class="btn lang-btn" onclick="setLang('ate')" id="lang-ate">Ateso</button>
  </div>
</div>

<!-- LOCK / RE-AUTH MODAL (kept original) -->
<div id="lockModal" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-flex align-items-center justify-content-center" style="z-index: 10000;">
  <div class="card shadow" style="width: 360px;">
    <div class="card-body p-4 text-center">
      <h5 class="mb-3" data-i18n="session_locked">Session Locked</h5>
      <p class="small text-muted" data-i18n="enter_password_resume">Enter your password to resume your previous session.</p>
      <input id="resumePassword" type="password" class="form-control mb-3" data-i18n-placeholder="password">
      <div class="d-flex gap-2">
        <button class="btn btn-secondary flex-fill" onclick="cancelResume()" data-i18n="cancel">Cancel</button>
        <button class="btn btn-primary flex-fill" onclick="attemptResume()" data-i18n="unlock">Unlock</button>
      </div>
      <div id="resumeMsg" class="mt-3 small text-danger d-none"></div>
    </div>
  </div>
</div>

<!-- AUTH PAGE (with language select) -->
<div id="auth" class="container d-none" style="max-width: 500px; margin-top: 80px;">
  <div class="card card-custom shadow">
    <div class="card-body p-5 text-center">
      <img src="" class="exmed-logo mb-3" width="70" alt="ExMed">
      <h3 id="authTitle" data-i18n="sign_in">Sign In</h3>
      <form class="mt-4" onsubmit="event.preventDefault();handleAuth();">
        <label for="authLangSelect" class="form-label">Language</label>
        <select id="authLangSelect" class="form-select form-select-sm mb-3" autocomplete="off" onchange="setLang(this.value)">
          <option value="en">English</option>
          <option value="lug">Luganda</option>
          <option value="sw">Swahili</option>
          <option value="ate">Ateso</option>
        </select>
        <input type="text" id="fullName" class="form-control mb-3 d-none" data-i18n-placeholder="full_name" placeholder="Full Name" autocomplete="name">
        <input type="tel" id="phone" class="form-control mb-3 d-none" data-i18n-placeholder="phone" placeholder="Phone (e.g. +256...)" autocomplete="tel">
        <input type="text" id="nationalId" class="form-control mb-3 d-none" data-i18n-placeholder="national_id" placeholder="National ID Number (optional)" autocomplete="off">
        <input type="email" id="email" class="form-control mb-3" data-i18n-placeholder="email_address" placeholder="Email" autocomplete="email" required>
        <input type="password" id="password" class="form-control mb-3" data-i18n-placeholder="password" placeholder="Password" autocomplete="current-password" required>
        <div class="form-check mb-4">
          <input class="form-check-input" type="checkbox" id="rememberMe">
          <label class="form-check-label" for="rememberMe" data-i18n="remember_me">Remember me for 30 days</label>
        </div>
        <select id="role" class="form-select mb-4 d-none" autocomplete="off">
          <option value="patient" data-i18n="patient">Patient</option>
          <option value="nurse" data-i18n="nurse">Nurse</option>
          <option value="doctor" data-i18n="doctor">Doctor</option>
          <option value="admin" data-i18n="admin">Administrator</option>
        </select>
        <div id="verifyPanel" class="d-none text-start mb-3">
          <div class="alert alert-info small" data-i18n="otp_sent">We've sent an OTP to the phone you provided. Enter it here to complete verification.</div>
          <label for="regOtp" class="form-label" data-i18n="enter_otp">Enter OTP</label>
          <input type="text" id="regOtp" class="form-control mb-2" placeholder="Enter OTP" autocomplete="off">
          <div id="generatedOtpDisplay" class="d-none alert alert-warning mb-2">
            <strong data-i18n="your_otp">Your OTP:</strong> <span id="displayedOtp" style="font-size: 18px; font-weight: bold; letter-spacing: 4px;">------</span>
          </div>
          <button type="button" class="btn btn-sm btn-info w-100 mb-2" onclick="generateSimpleOtp()" data-i18n="generate_otp_test">ðŸ”“ Generate OTP (for testing)</button>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary flex-fill" onclick="resendOtp()" data-i18n="resend_otp">Resend OTP</button>
            <button type="button" class="btn btn-success flex-fill" onclick="verifyRegistrationOtp()" data-i18n="verify">Verify</button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-3" data-i18n="continue_btn">Continue</button>
      </form>
      <p class="mt-4"><a href="#" onclick="toggleAuth()" id="toggleLink" data-i18n="no_account">No account? Create one</a></p>
      <p class="mt-4"><a href="#" onclick="showForgotPassword()" id="forgotPasswordLink" data-i18n="forgot_password">Forgot Password?</a></p>
    </div>
  </div>
</div>

<!-- FORGOT PASSWORD PAGE (with translations) -->
<div id="forgotPassword" class="container d-none" style="max-width: 500px; margin-top: 80px;">
  <div class="card card-custom shadow">
    <div class="card-body p-5 text-center">
      <h3 data-i18n="reset_password">Forgot Password</h3>
      <form class="mt-4" onsubmit="event.preventDefault(); handleForgotPassword();">
        <label for="forgotEmail" class="form-label" data-i18n="email_address">Email Address</label>
        <input type="email" id="forgotEmail" class="form-control mb-4" data-i18n-placeholder="enter_email_reset" placeholder="Enter your email" autocomplete="email" required>
        <button type="submit" id="forgotSendBtn" class="btn btn-primary w-100 py-3" data-i18n="reset_password_btn">Reset Password</button>
      </form>
      <div id="resetPanel" class="d-none mt-3 text-start">
        <label for="resetOtpInput" class="form-label" data-i18n="enter_otp">Enter OTP</label>
        <input id="resetOtpInput" class="form-control mb-2" placeholder="Enter OTP" autocomplete="off">
        <div class="d-flex gap-2 mb-2">
          <button class="btn btn-outline-primary flex-fill" onclick="resendResetOtp()" data-i18n="resend_otp">Resend OTP</button>
          <button class="btn btn-success flex-fill" onclick="verifyResetOtp()" data-i18n="verify">Verify OTP</button>
        </div>
      </div>
      <div id="setNewPasswordPanel" class="d-none mt-3 text-start">
        <label for="newPassword" class="form-label" data-i18n="new_password">New Password</label>
        <input id="newPassword" type="password" class="form-control mb-2" data-i18n-placeholder="new_password" placeholder="New password" autocomplete="new-password">
        <label for="confirmNewPassword" class="form-label" data-i18n="confirm_password">Confirm Password</label>
        <input id="confirmNewPassword" type="password" class="form-control mb-2" data-i18n-placeholder="confirm_password" placeholder="Confirm new password" autocomplete="new-password">
        <button class="btn btn-primary w-100" onclick="setNewPassword()" data-i18n="set_new_password">Set New Password</button>
      </div>
      <p class="mt-4"><a href="#" onclick="showForgotPasswordBack()" id="forgotBackLink" data-i18n="back_to_login">Back to Login</a></p>
    </div>
  </div>
</div>

<!-- OTP DISPLAY MODAL -->
<div id="otpModal" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-flex align-items-center justify-content-center" style="z-index: 9999;">
  <div class="card shadow" style="width: 320px;">
    <div class="card-body text-center p-4">
      <h5 class="mb-3" data-i18n="your_otp_code">Your OTP Code</h5>
      <div id="otpCodeDisplay" class="bg-light p-3 rounded mb-3" style="letter-spacing: 8px; font-size: 24px; font-weight: bold; font-family: monospace;">------</div>
      <p class="small text-muted mb-3" data-i18n="valid_minutes">Valid for 5 minutes. This code will expire soon.</p>
      <button class="btn btn-sm btn-primary w-100" onclick="closeOtpModal()" data-i18n="got_it">Got It</button>
    </div>
  </div>
</div>

<script>
// ==================== ORIGINAL JAVASCRIPT (PRESERVED) ====================
let authMode = 'login';
let otpStorage = {};

// ===== DEMO LOCK FUNCTION - ADDED =====
function checkDemoLock(actionType) {
    var message = "";
    if (actionType === 'login') {
        message = getTranslation('demo_lock_login');
    } else if (actionType === 'signup') {
        message = getTranslation('demo_lock_signup');
    } else {
        message = "⚠️ ACCESS RESTRICTED\n\nThis system is currently in demo mode.\nPlease contact the system developer:\nMR. YAHAYA\n\nFull access will be granted upon payment completion.";
    }
    alert(message);
    return false;
}

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
        return { success: false, message: getTranslation('network_error') };
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
    if (!stored) return { valid: false, error: getTranslation('otp_expired') };
    if (Date.now() > stored.expiry) return { valid: false, error: getTranslation('otp_expired') };
    if (stored.code !== enteredOtp.trim()) return { valid: false, error: getTranslation('incorrect_otp') };
    delete otpStorage[email];
    return { valid: true };
}

function resendOtp() {
    const email = document.getElementById('email')?.value || '';
    if (!email) { alert(getTranslation('enter_email_first')); return; }
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
        alert(getTranslation('account_created'));
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
        title.textContent = getTranslation('create_account');
        fullName.classList.remove('d-none');
        phone.classList.remove('d-none');
        role.classList.remove('d-none');
        verifyPanel.classList.add('d-none');
        document.getElementById('toggleLink').innerHTML = getTranslation('already_account');
    } else {
        title.textContent = getTranslation('sign_in');
        fullName.classList.add('d-none');
        phone.classList.add('d-none');
        role.classList.add('d-none');
        verifyPanel.classList.add('d-none');
        document.getElementById('toggleLink').innerHTML = getTranslation('no_account');
    }
}

function toggleAuth() {
    goToAuth(authMode === 'register' ? 'login' : 'register');
}

// ===== UPDATED handleAuth() - DEMO LOCK ADDED =====
async function handleAuth() {
    // ===== DEMO LOCK CHECK - ADDED =====
    if (authMode === 'login') {
        checkDemoLock('login');
        return;
    }
    if (authMode === 'register') {
        checkDemoLock('signup');
        return;
    }
    // ===== END DEMO LOCK =====
    
    const email = document.getElementById('email')?.value || '';
    const password = document.getElementById('password')?.value || '';
    
    if (authMode === 'register') {
        if (!email || !email.includes('@')) { alert(getTranslation('enter_valid_email')); return; }
        const verifyPanel = document.getElementById('verifyPanel');
        if (verifyPanel && !verifyPanel.classList.contains('d-none')) {
            const entered = document.getElementById('regOtp')?.value || '';
            if (!entered.trim()) { alert(getTranslation('enter_otp_first')); return; }
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
        alert(getTranslation('login_success'));
        switch(result.user.role) {
            case 'patient': window.location.href = 'patients.php'; break;
            case 'doctor': window.location.href = 'doctors.php'; break;
            case 'nurse': window.location.href = 'nurses.php'; break;
            case 'admin': window.location.href = 'admin.php'; break;
            default: window.location.href = 'index.php';
        }
    } else {
        alert(getTranslation('invalid_credentials'));
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
    if (!email) { alert(getTranslation('enter_email_first')); return; }
    document.getElementById('resetPanel').classList.remove('d-none');
    sendOtp(email);
}

function resendResetOtp() {
    const email = document.getElementById('forgotEmail')?.value || '';
    if (!email) { alert(getTranslation('enter_email_first')); return; }
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
    if (newPwd !== confirmPwd) { alert(getTranslation('password_mismatch')); return; }
    if (newPwd.length < 3) { alert(getTranslation('password_short')); return; }
    alert(getTranslation('password_updated'));
    showForgotPasswordBack();
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
    setLang(currentLang);
    const authLangSelect = document.getElementById('authLangSelect');
    if (authLangSelect) authLangSelect.value = currentLang;
});
</script>
</body>
</html>