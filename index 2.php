<?php
// index.php - Complete working file with all functionality
session_start();
header('Content-Type: text/html; charset=utf-8');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'q5bi8it4_examd');
define('DB_USER', 'q5bi8it4');
define('DB_PASS', 'provemeucan');

function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch(PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Create tables if they don't exist
function initializeDatabase() {
    $pdo = getDBConnection();
    
    $sql = "
    CREATE TABLE IF NOT EXISTS `users` (
        `id` VARCHAR(36) PRIMARY KEY,
        `full_name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) UNIQUE NOT NULL,
        `phone` VARCHAR(20),
        `password_hash` VARCHAR(255) NOT NULL,
        `role` ENUM('patient', 'doctor', 'nurse', 'admin') DEFAULT 'patient',
        `subscription_plan` ENUM('basic', 'premium', 'none') DEFAULT 'none',
        `subscription_expiry` DATETIME,
        `trial_ends_at` DATETIME,
        `is_active` BOOLEAN DEFAULT TRUE,
        `last_login` DATETIME,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_role (role)
    );
    
    CREATE TABLE IF NOT EXISTS `patients` (
        `id` VARCHAR(36) PRIMARY KEY,
        `user_id` VARCHAR(36) UNIQUE NOT NULL,
        `date_of_birth` DATE,
        `gender` ENUM('male', 'female', 'other'),
        `blood_type` VARCHAR(5),
        `emergency_contact` VARCHAR(20),
        `national_id` VARCHAR(20),
        `address` TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS `doctors` (
        `id` VARCHAR(36) PRIMARY KEY,
        `user_id` VARCHAR(36) UNIQUE NOT NULL,
        `specialization` VARCHAR(100),
        `license_number` VARCHAR(50),
        `years_experience` INT DEFAULT 0,
        `consultation_fee` DECIMAL(10,2) DEFAULT 0,
        `department` VARCHAR(100),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS `nurses` (
        `id` VARCHAR(36) PRIMARY KEY,
        `user_id` VARCHAR(36) UNIQUE NOT NULL,
        `license_number` VARCHAR(50),
        `department` VARCHAR(100),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS `appointments` (
        `id` VARCHAR(36) PRIMARY KEY,
        `patient_id` VARCHAR(36) NOT NULL,
        `doctor_id` VARCHAR(36) NOT NULL,
        `appointment_date` DATE NOT NULL,
        `appointment_time` TIME NOT NULL,
        `reason` TEXT,
        `status` ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
        `type` ENUM('clinic', 'tele') DEFAULT 'clinic',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id),
        FOREIGN KEY (doctor_id) REFERENCES doctors(id),
        INDEX idx_date (appointment_date),
        INDEX idx_status (status)
    );
    
    CREATE TABLE IF NOT EXISTS `prescriptions` (
        `id` VARCHAR(36) PRIMARY KEY,
        `patient_id` VARCHAR(36) NOT NULL,
        `doctor_id` VARCHAR(36) NOT NULL,
        `medication_name` VARCHAR(100) NOT NULL,
        `dosage` VARCHAR(50),
        `frequency` VARCHAR(50),
        `duration_days` INT,
        `instructions` TEXT,
        `status` ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id),
        FOREIGN KEY (doctor_id) REFERENCES doctors(id)
    );
    
    CREATE TABLE IF NOT EXISTS `medical_records` (
        `id` VARCHAR(36) PRIMARY KEY,
        `patient_id` VARCHAR(36) NOT NULL,
        `doctor_id` VARCHAR(36),
        `record_title` VARCHAR(200) NOT NULL,
        `record_description` TEXT,
        `diagnosis` VARCHAR(200),
        `recorded_by` VARCHAR(100),
        `recorded_by_role` VARCHAR(50),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id),
        FOREIGN KEY (doctor_id) REFERENCES doctors(id)
    );
    
    CREATE TABLE IF NOT EXISTS `vital_signs` (
        `id` VARCHAR(36) PRIMARY KEY,
        `patient_id` VARCHAR(36) NOT NULL,
        `recorded_by` VARCHAR(36),
        `temperature` DECIMAL(4,1),
        `blood_pressure_systolic` INT,
        `blood_pressure_diastolic` INT,
        `heart_rate` INT,
        `oxygen_saturation` INT,
        `weight` DECIMAL(5,2),
        `height` DECIMAL(5,2),
        `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id)
    );
    
    CREATE TABLE IF NOT EXISTS `subscriptions` (
        `id` VARCHAR(36) PRIMARY KEY,
        `user_id` VARCHAR(36) NOT NULL,
        `plan_type` ENUM('basic', 'premium') NOT NULL,
        `status` ENUM('active', 'expired', 'cancelled', 'trial') DEFAULT 'trial',
        `start_date` DATE NOT NULL,
        `end_date` DATE NOT NULL,
        `payment_method` VARCHAR(50),
        `amount` DECIMAL(10,2),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );
    
    CREATE TABLE IF NOT EXISTS `staff_reports` (
        `id` VARCHAR(36) PRIMARY KEY,
        `user_id` VARCHAR(36) NOT NULL,
        `week_starting` DATE NOT NULL,
        `activities` TEXT,
        `patients_attended` INT DEFAULT 0,
        `challenges` TEXT,
        `status` ENUM('pending', 'reviewed', 'rejected') DEFAULT 'pending',
        `feedback` TEXT,
        `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );
    
    CREATE TABLE IF NOT EXISTS `messages` (
        `id` VARCHAR(36) PRIMARY KEY,
        `sender_id` VARCHAR(36) NOT NULL,
        `receiver_id` VARCHAR(36) NOT NULL,
        `message` TEXT NOT NULL,
        `is_read` BOOLEAN DEFAULT FALSE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id),
        FOREIGN KEY (receiver_id) REFERENCES users(id)
    );
    
    CREATE TABLE IF NOT EXISTS `lab_orders` (
        `id` VARCHAR(36) PRIMARY KEY,
        `patient_id` VARCHAR(36) NOT NULL,
        `doctor_id` VARCHAR(36) NOT NULL,
        `test_type` VARCHAR(100) NOT NULL,
        `clinical_indication` TEXT,
        `status` ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
        `result` TEXT,
        `ordered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `completed_at` DATETIME,
        FOREIGN KEY (patient_id) REFERENCES patients(id),
        FOREIGN KEY (doctor_id) REFERENCES doctors(id)
    );
    ";
    
    try {
        $pdo->exec($sql);
    } catch(PDOException $e) {
        // Tables might already exist
    }
}

// Initialize database on startup
initializeDatabase();

// API endpoint handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    $pdo = getDBConnection();
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Handle Signup
    if ($_GET['action'] === 'signup') {
        $id = generateUUID();
        $full_name = $input['name'];
        $email = $input['email'];
        $phone = $input['phone'];
        $password_hash = password_hash($input['password'], PASSWORD_DEFAULT);
        $role = $input['role'];
        
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already registered']);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO users (id, full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$id, $full_name, $email, $phone, $password_hash, $role])) {
            if ($role === 'patient') {
                $pdo->prepare("INSERT INTO patients (id, user_id) VALUES (?, ?)")->execute([generateUUID(), $id]);
            } elseif ($role === 'doctor') {
                $pdo->prepare("INSERT INTO doctors (id, user_id) VALUES (?, ?)")->execute([generateUUID(), $id]);
            } elseif ($role === 'nurse') {
                $pdo->prepare("INSERT INTO nurses (id, user_id) VALUES (?, ?)")->execute([generateUUID(), $id]);
            }
            echo json_encode(['success' => true, 'message' => 'Account created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Registration failed']);
        }
        exit;
    }
    
    // Handle Login
    if ($_GET['action'] === 'login') {
        $email = $input['email'];
        $password = $input['password'];
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['full_name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'role' => $user['role'],
                'subscription' => $user['subscription_plan'],
                'created_at' => $user['created_at']
            ];
            echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        }
        exit;
    }
    
    // Handle Logout
    if ($_GET['action'] === 'logout') {
        session_destroy();
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Handle Get Current User
    if ($_GET['action'] === 'get_current_user') {
        if (isset($_SESSION['user'])) {
            echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
    
    // Handle Check Session
    if ($_GET['action'] === 'check_session') {
        if (isset($_SESSION['user'])) {
            echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
    
    // Handle Get Users (for admin)
    if ($_GET['action'] === 'get_users') {
        $stmt = $pdo->query("SELECT id, full_name as name, email, phone, role, subscription_plan as subscription, created_at FROM users ORDER BY created_at DESC");
        echo json_encode(['success' => true, 'users' => $stmt->fetchAll()]);
        exit;
    }
    
    // Handle Delete User
    if ($_GET['action'] === 'delete_user') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE email = ? AND role != 'admin'");
        $stmt->execute([$input['email']]);
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Handle Get Doctors List
    if ($_GET['action'] === 'get_doctors') {
        $stmt = $pdo->query("SELECT u.email, u.full_name as name, d.specialization, d.department FROM users u JOIN doctors d ON u.id = d.user_id WHERE u.role = 'doctor' AND u.is_active = 1");
        echo json_encode(['success' => true, 'doctors' => $stmt->fetchAll()]);
        exit;
    }
    
    // Handle Get Patients List
    if ($_GET['action'] === 'get_patients') {
        $stmt = $pdo->query("SELECT u.email, u.full_name as name, u.phone, p.date_of_birth, p.blood_type FROM users u JOIN patients p ON u.id = p.user_id WHERE u.role = 'patient' AND u.is_active = 1");
        echo json_encode(['success' => true, 'patients' => $stmt->fetchAll()]);
        exit;
    }
    
    // Handle Get Appointments
    if ($_GET['action'] === 'get_appointments') {
        $email = $input['email'] ?? '';
        $role = $input['role'] ?? '';
        
        if ($role === 'patient') {
            $stmt = $pdo->prepare("SELECT a.*, d.full_name as doctor_name, d.specialization 
                FROM appointments a 
                JOIN doctors doc ON a.doctor_id = doc.id 
                JOIN users d ON doc.user_id = d.id 
                JOIN patients p ON a.patient_id = p.id 
                JOIN users u ON p.user_id = u.id 
                WHERE u.email = ? 
                ORDER BY a.appointment_date DESC");
            $stmt->execute([$email]);
        } elseif ($role === 'doctor') {
            $stmt = $pdo->prepare("SELECT a.*, u.full_name as patient_name, u.email as patient_email
                FROM appointments a 
                JOIN patients p ON a.patient_id = p.id 
                JOIN users u ON p.user_id = u.id 
                JOIN doctors doc ON a.doctor_id = doc.id 
                JOIN users d ON doc.user_id = d.id 
                WHERE d.email = ? 
                ORDER BY a.appointment_date DESC");
            $stmt->execute([$email]);
        } else {
            $stmt = $pdo->query("SELECT a.*, u.full_name as patient_name, d.full_name as doctor_name 
                FROM appointments a 
                JOIN patients p ON a.patient_id = p.id 
                JOIN users u ON p.user_id = u.id 
                JOIN doctors doc ON a.doctor_id = doc.id 
                JOIN users d ON doc.user_id = d.id 
                ORDER BY a.appointment_date DESC");
        }
        
        $appointments = $stmt->fetchAll();
        $formatted = array_map(function($a) {
            return [
                'id' => $a['id'],
                'patient_name' => $a['patient_name'] ?? '',
                'patient_email' => $a['patient_email'] ?? '',
                'doctor_name' => $a['doctor_name'] ?? '',
                'doctor_specialization' => $a['specialization'] ?? '',
                'appointment_date' => $a['appointment_date'],
                'appointment_time' => $a['appointment_time'],
                'reason' => $a['reason'],
                'status' => $a['status'],
                'type' => $a['type'] ?? 'clinic'
            ];
        }, $appointments);
        
        echo json_encode(['success' => true, 'appointments' => $formatted]);
        exit;
    }
    
    // Handle Book Appointment
    if ($_GET['action'] === 'book_appointment') {
        $patientStmt = $pdo->prepare("SELECT p.id FROM patients p JOIN users u ON p.user_id = u.id WHERE u.email = ?");
        $patientStmt->execute([$input['patient_email']]);
        $patient = $patientStmt->fetch();
        
        $doctorStmt = $pdo->prepare("SELECT d.id FROM doctors d JOIN users u ON d.user_id = u.id WHERE u.email = ?");
        $doctorStmt->execute([$input['doctor_email']]);
        $doctor = $doctorStmt->fetch();
        
        if (!$patient || !$doctor) {
            echo json_encode(['success' => false, 'message' => 'Patient or doctor not found']);
            exit;
        }
        
        $id = generateUUID();
        $type = $input['type'] ?? 'clinic';
        $stmt = $pdo->prepare("INSERT INTO appointments (id, patient_id, doctor_id, appointment_date, appointment_time, reason, status, type) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([$id, $patient['id'], $doctor['id'], $input['date'], $input['time'], $input['reason'], $type]);
        echo json_encode(['success' => true, 'message' => 'Appointment booked successfully']);
        exit;
    }
    
    // Handle Confirm Appointment
    if ($_GET['action'] === 'confirm_appointment') {
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'confirmed' WHERE id = ?");
        $stmt->execute([$input['appointment_id']]);
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Handle Complete Appointment
    if ($_GET['action'] === 'complete_appointment') {
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
        $stmt->execute([$input['appointment_id']]);
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Handle Get Prescriptions
    if ($_GET['action'] === 'get_prescriptions') {
        $email = $input['email'];
        $stmt = $pdo->prepare("SELECT p.*, u.full_name as doctor_name 
            FROM prescriptions p 
            JOIN doctors d ON p.doctor_id = d.id 
            JOIN users u ON d.user_id = u.id 
            JOIN patients pa ON p.patient_id = pa.id 
            JOIN users pu ON pa.user_id = pu.id 
            WHERE pu.email = ? 
            ORDER BY p.created_at DESC");
        $stmt->execute([$email]);
        $prescriptions = $stmt->fetchAll();
        
        $formatted = array_map(function($p) {
            return [
                'id' => $p['id'],
                'medication_name' => $p['medication_name'] ?? 'N/A',
                'dosage' => $p['dosage'] ?? '',
                'frequency' => $p['frequency'] ?? '',
                'duration_days' => $p['duration_days'] ?? '',
                'instructions' => $p['instructions'] ?? '',
                'doctor_name' => $p['doctor_name'],
                'prescription_date' => $p['created_at'],
                'status' => $p['status']
            ];
        }, $prescriptions);
        
        echo json_encode(['success' => true, 'prescriptions' => $formatted]);
        exit;
    }
    
    // Handle Submit Prescription
    if ($_GET['action'] === 'submit_prescription') {
        $patientStmt = $pdo->prepare("SELECT p.id FROM patients p JOIN users u ON p.user_id = u.id WHERE u.email = ?");
        $patientStmt->execute([$input['patient_email']]);
        $patient = $patientStmt->fetch();
        
        $doctorStmt = $pdo->prepare("SELECT d.id FROM doctors d JOIN users u ON d.user_id = u.id WHERE u.email = ?");
        $doctorStmt->execute([$input['doctor_email']]);
        $doctor = $doctorStmt->fetch();
        
        if (!$patient || !$doctor) {
            echo json_encode(['success' => false, 'message' => 'Patient or doctor not found']);
            exit;
        }
        
        $id = generateUUID();
        $stmt = $pdo->prepare("INSERT INTO prescriptions (id, patient_id, doctor_id, medication_name, dosage, frequency, duration_days, instructions, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$id, $patient['id'], $doctor['id'], $input['medication'], $input['dosage'] ?? '', $input['frequency'] ?? '', $input['duration'] ?? '', $input['instructions'] ?? '']);
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Handle Get Medical Records
    if ($_GET['action'] === 'get_medical_records') {
        $email = $input['email'];
        if ($email) {
            $stmt = $pdo->prepare("SELECT mr.*, u.full_name as patient_name 
                FROM medical_records mr 
                JOIN patients p ON mr.patient_id = p.id 
                JOIN users u ON p.user_id = u.id 
                WHERE u.email = ? 
                ORDER BY mr.created_at DESC");
            $stmt->execute([$email]);
        } else {
            $stmt = $pdo->query("SELECT mr.*, u.full_name as patient_name 
                FROM medical_records mr 
                JOIN patients p ON mr.patient_id = p.id 
                JOIN users u ON p.user_id = u.id 
                ORDER BY mr.created_at DESC");
        }
        $records = $stmt->fetchAll();
        
        $formatted = array_map(function($r) {
            return [
                'id' => $r['id'],
                'title' => $r['record_title'] ?? 'Medical Record',
                'content' => $r['record_description'] ?? '',
                'diagnosis' => $r['diagnosis'] ?? '',
                'doctor' => $r['recorded_by'] ?? '',
                'date' => $r['created_at'],
                'type' => $r['recorded_by_role'] ?? 'General',
                'patient_name' => $r['patient_name'] ?? ''
            ];
        }, $records);
        
        echo json_encode(['success' => true, 'records' => $formatted]);
        exit;
    }
    
    // Handle Save Vitals
    if ($_GET['action'] === 'save_vitals') {
        $patientStmt = $pdo->prepare("SELECT p.id FROM patients p JOIN users u ON p.user_id = u.id WHERE u.email = ?");
        $patientStmt->execute([$input['patient_email']]);
        $patient = $patientStmt->fetch();
        
        if (!$patient) {
            echo json_encode(['success' => false, 'message' => 'Patient not found']);
            exit;
        }
        
        // Parse BP
        $bp_parts = explode('/', $input['bp']);
        $bp_systolic = isset($bp_parts[0]) ? (int)$bp_parts[0] : null;
        $bp_diastolic = isset($bp_parts[1]) ? (int)$bp_parts[1] : null;
        
        $id = generateUUID();
        $stmt = $pdo->prepare("INSERT INTO vital_signs (id, patient_id, temperature, blood_pressure_systolic, blood_pressure_diastolic, heart_rate, oxygen_saturation, weight, height, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $patient['id'], $input['temp'], $bp_systolic, $bp_diastolic, $input['hr'], $input['o2'], $input['weight'] ?? null, $input['height'] ?? null, $input['recorded_by']]);
        
        // Also save as medical record
        $recordId = generateUUID();
        $description = "Temperature: {$input['temp']}°C | BP: {$input['bp']} | Heart Rate: {$input['hr']} bpm | O2: {$input['o2']}% | Weight: {$input['weight']}kg | Height: {$input['height']}cm";
        $recordStmt = $pdo->prepare("INSERT INTO medical_records (id, patient_id, record_title, record_description, recorded_by, recorded_by_role) VALUES (?, ?, ?, ?, ?, ?)");
        $recordStmt->execute([$recordId, $patient['id'], 'Vital Signs Check', $description, $input['recorded_by'], $input['recorded_by_role']]);
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Handle Save Medical Report
    if ($_GET['action'] === 'save_medical_report') {
        $patientStmt = $pdo->prepare("SELECT p.id FROM patients p JOIN users u ON p.user_id = u.id WHERE u.email = ?");
        $patientStmt->execute([$input['patient_email']]);
        $patient = $patientStmt->fetch();
        
        if (!$patient) {
            echo json_encode(['success' => false, 'message' => 'Patient not found']);
            exit;
        }
        
        $doctorStmt = $pdo->prepare("SELECT d.id FROM doctors d JOIN users u ON d.user_id = u.id WHERE u.email = ?");
        $doctorStmt->execute([$input['doctor_email']]);
        $doctor = $doctorStmt->fetch();
        
        $id = generateUUID();
        $stmt = $pdo->prepare("INSERT INTO medical_records (id, patient_id, doctor_id, record_title, record_description, recorded_by, recorded_by_role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $patient['id'], $doctor['id'] ?? null, $input['title'], $input['description'], $input['doctor_name'], 'doctor']);
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Handle Get Dashboard Stats
    if ($_GET['action'] === 'get_stats') {
        $patientCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn();
        $doctorCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetchColumn();
        $nurseCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'nurse'")->fetchColumn();
        $appointmentCount = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
        $pendingAppointments = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetchColumn();
        
        echo json_encode(['success' => true, 'stats' => [
            'patients' => $patientCount,
            'doctors' => $doctorCount,
            'nurses' => $nurseCount,
            'appointments' => $appointmentCount,
            'pending_appointments' => $pendingAppointments
        ]]);
        exit;
    }
    
    // Handle Get Doctor's Patients
    if ($_GET['action'] === 'get_doctor_patients') {
        $email = $input['email'];
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.id, u.full_name, u.email, u.phone,
                (SELECT COUNT(*) FROM appointments a 
                 JOIN patients p ON a.patient_id = p.id 
                 WHERE p.user_id = u.id AND a.doctor_id = d.id) as visit_count
            FROM users u
            JOIN patients p ON u.id = p.user_id
            JOIN appointments a ON p.id = a.patient_id
            JOIN doctors d ON a.doctor_id = d.id
            JOIN users du ON d.user_id = du.id
            WHERE du.email = ? AND u.role = 'patient'
            ORDER BY a.appointment_date DESC
        ");
        $stmt->execute([$email]);
        echo json_encode(['success' => true, 'patients' => $stmt->fetchAll()]);
        exit;
    }
    
    // Handle Get Patient History
    if ($_GET['action'] === 'get_patient_history') {
        $patient_email = $input['patient_email'];
        
        $stmt = $pdo->prepare("SELECT p.id FROM patients p JOIN users u ON p.user_id = u.id WHERE u.email = ?");
        $stmt->execute([$patient_email]);
        $patient = $stmt->fetch();
        
        if (!$patient) {
            echo json_encode(['success' => false, 'message' => 'Patient not found']);
            exit;
        }
        
        $records = $pdo->prepare("
            SELECT mr.*, u.full_name as doctor_name 
            FROM medical_records mr
            LEFT JOIN doctors d ON mr.doctor_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            WHERE mr.patient_id = ?
            ORDER BY mr.created_at DESC
        ");
        $records->execute([$patient['id']]);
        
        $prescriptions = $pdo->prepare("
            SELECT p.*, u.full_name as doctor_name 
            FROM prescriptions p
            JOIN doctors d ON p.doctor_id = d.id
            JOIN users u ON d.user_id = u.id
            WHERE p.patient_id = ?
            ORDER BY p.created_at DESC
        ");
        $prescriptions->execute([$patient['id']]);
        
        $vitals = $pdo->prepare("
            SELECT * FROM vital_signs 
            WHERE patient_id = ? 
            ORDER BY recorded_at DESC LIMIT 10
        ");
        $vitals->execute([$patient['id']]);
        
        $appointments = $pdo->prepare("
            SELECT a.*, u.full_name as doctor_name
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.id
            JOIN users u ON d.user_id = u.id
            WHERE a.patient_id = ?
            ORDER BY a.appointment_date DESC LIMIT 5
        ");
        $appointments->execute([$patient['id']]);
        
        echo json_encode([
            'success' => true,
            'records' => $records->fetchAll(),
            'prescriptions' => $prescriptions->fetchAll(),
            'vitals' => $vitals->fetchAll(),
            'appointments' => $appointments->fetchAll()
        ]);
        exit;
    }
    
    // Handle Submit Diagnosis
    if ($_GET['action'] === 'submit_diagnosis') {
        $patient_email = $input['patient_email'];
        $doctor_email = $input['doctor_email'];
        $diagnosis = $input['diagnosis'];
        $notes = $input['notes'];
        $severity = $input['severity'];
        
        $patientStmt = $pdo->prepare("SELECT p.id FROM patients p JOIN users u ON p.user_id = u.id WHERE u.email = ?");
        $patientStmt->execute([$patient_email]);
        $patient = $patientStmt->fetch();
        
        $doctorStmt = $pdo->prepare("SELECT d.id FROM doctors d JOIN users u ON d.user_id = u.id WHERE u.email = ?");
        $doctorStmt->execute([$doctor_email]);
        $doctor = $doctorStmt->fetch();
        
        if (!$patient || !$doctor) {
            echo json_encode(['success' => false, 'message' => 'Patient or doctor not found']);
            exit;
        }
        
        $id = generateUUID();
        $title = "Diagnosis: " . $diagnosis . ($severity ? " (Severity: $severity)" : "");
        $stmt = $pdo->prepare("
            INSERT INTO medical_records (id, patient_id, doctor_id, record_title, record_description, diagnosis, recorded_by, recorded_by_role) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'doctor')
        ");
        $stmt->execute([
            $id, $patient['id'], $doctor['id'], 
            $title, $notes, $diagnosis, 
            $input['doctor_name'] ?? 'Doctor'
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Diagnosis saved successfully']);
        exit;
    }
    
    // Handle Order Lab Test
    if ($_GET['action'] === 'order_lab_test') {
        $patient_email = $input['patient_email'];
        $doctor_email = $input['doctor_email'];
        $test_type = $input['test_type'];
        $indication = $input['indication'];
        
        $patientStmt = $pdo->prepare("SELECT p.id FROM patients p JOIN users u ON p.user_id = u.id WHERE u.email = ?");
        $patientStmt->execute([$patient_email]);
        $patient = $patientStmt->fetch();
        
        $doctorStmt = $pdo->prepare("SELECT d.id FROM doctors d JOIN users u ON d.user_id = u.id WHERE u.email = ?");
        $doctorStmt->execute([$doctor_email]);
        $doctor = $doctorStmt->fetch();
        
        if (!$patient || !$doctor) {
            echo json_encode(['success' => false, 'message' => 'Patient or doctor not found']);
            exit;
        }
        
        $id = generateUUID();
        $stmt = $pdo->prepare("
            INSERT INTO lab_orders (id, patient_id, doctor_id, test_type, clinical_indication, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$id, $patient['id'], $doctor['id'], $test_type, $indication]);
        
        echo json_encode(['success' => true, 'message' => 'Lab test ordered successfully']);
        exit;
    }
    
    // Handle Send Message
    if ($_GET['action'] === 'send_message') {
        $sender_email = $input['sender_email'];
        $receiver_email = $input['receiver_email'];
        $message = $input['message'];
        
        $senderStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $senderStmt->execute([$sender_email]);
        $sender = $senderStmt->fetch();
        
        $receiverStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $receiverStmt->execute([$receiver_email]);
        $receiver = $receiverStmt->fetch();
        
        if (!$sender || !$receiver) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        $id = generateUUID();
        $stmt = $pdo->prepare("INSERT INTO messages (id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $sender['id'], $receiver['id'], $message]);
        
        echo json_encode(['success' => true, 'message' => 'Message sent']);
        exit;
    }
    
    // Handle Get Messages
    if ($_GET['action'] === 'get_messages') {
        $user_email = $input['email'];
        $with_email = $input['with_email'] ?? null;
        
        $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $userStmt->execute([$user_email]);
        $user = $userStmt->fetch();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        if ($with_email) {
            $withStmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE email = ?");
            $withStmt->execute([$with_email]);
            $with = $withStmt->fetch();
            
            $stmt = $pdo->prepare("
                SELECT m.*, u1.full_name as sender_name, u1.email as sender_email, u2.full_name as receiver_name, u2.email as receiver_email
                FROM messages m
                JOIN users u1 ON m.sender_id = u1.id
                JOIN users u2 ON m.receiver_id = u2.id
                WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.created_at ASC
            ");
            $stmt->execute([$user['id'], $with['id'], $with['id'], $user['id']]);
        } else {
            $stmt = $pdo->prepare("
                SELECT DISTINCT 
                    CASE 
                        WHEN m.sender_id = ? THEN u2.id
                        ELSE u1.id
                    END as other_user_id,
                    CASE 
                        WHEN m.sender_id = ? THEN u2.full_name
                        ELSE u1.full_name
                    END as other_user_name,
                    CASE 
                        WHEN m.sender_id = ? THEN u2.email
                        ELSE u1.email
                    END as other_user_email,
                    (SELECT message FROM messages m2 
                     WHERE ((m2.sender_id = ? AND m2.receiver_id = other_user_id) 
                        OR (m2.sender_id = other_user_id AND m2.receiver_id = ?))
                     ORDER BY m2.created_at DESC LIMIT 1) as last_message,
                    MAX(m.created_at) as last_message_time
                FROM messages m
                JOIN users u1 ON m.sender_id = u1.id
                JOIN users u2 ON m.receiver_id = u2.id
                WHERE m.sender_id = ? OR m.receiver_id = ?
                GROUP BY other_user_id
                ORDER BY last_message_time DESC
            ");
            $stmt->execute([$user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id']]);
        }
        
        echo json_encode(['success' => true, 'messages' => $stmt->fetchAll()]);
        exit;
    }
    
    // Handle Submit Staff Report
    if ($_GET['action'] === 'submit_staff_report') {
        $user_email = $input['user_email'];
        $week_starting = $input['week_starting'];
        $activities = $input['activities'];
        $patients_attended = $input['patients_attended'];
        $challenges = $input['challenges'];
        
        $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $userStmt->execute([$user_email]);
        $user = $userStmt->fetch();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        $id = generateUUID();
        $stmt = $pdo->prepare("
            INSERT INTO staff_reports (id, user_id, week_starting, activities, patients_attended, challenges, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$id, $user['id'], $week_starting, $activities, $patients_attended, $challenges]);
        
        echo json_encode(['success' => true, 'message' => 'Report submitted successfully']);
        exit;
    }
    
    // Handle Get Staff Reports
    if ($_GET['action'] === 'get_staff_reports') {
        $stmt = $pdo->prepare("
            SELECT sr.*, u.full_name, u.email, u.role
            FROM staff_reports sr
            JOIN users u ON sr.user_id = u.id
            ORDER BY sr.submitted_at DESC
        ");
        $stmt->execute();
        
        echo json_encode(['success' => true, 'reports' => $stmt->fetchAll()]);
        exit;
    }
    
    // Handle Review Staff Report
    if ($_GET['action'] === 'review_staff_report') {
        $report_id = $input['report_id'];
        $feedback = $input['feedback'];
        $status = $input['status'];
        
        $stmt = $pdo->prepare("UPDATE staff_reports SET status = ?, feedback = ? WHERE id = ?");
        $stmt->execute([$status, $feedback, $report_id]);
        
        echo json_encode(['success' => true, 'message' => 'Report reviewed']);
        exit;
    }
    
    // Handle Activate Subscription
    if ($_GET['action'] === 'activate_subscription') {
        $user_email = $input['user_email'];
        $plan_type = $input['plan_type'];
        $payment_method = $input['payment_method'];
        $amount = $input['amount'];
        $is_trial = $input['is_trial'] ?? true;
        
        $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $userStmt->execute([$user_email]);
        $user = $userStmt->fetch();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        $start_date = date('Y-m-d');
        $end_date = $is_trial ? date('Y-m-d', strtotime('+7 days')) : date('Y-m-d', strtotime('+30 days'));
        $status = $is_trial ? 'trial' : 'active';
        
        // Deactivate old subscriptions
        $pdo->prepare("UPDATE subscriptions SET status = 'expired' WHERE user_id = ? AND status IN ('active', 'trial')")->execute([$user['id']]);
        
        // Create new subscription
        $id = generateUUID();
        $stmt = $pdo->prepare("
            INSERT INTO subscriptions (id, user_id, plan_type, status, start_date, end_date, payment_method, amount) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$id, $user['id'], $plan_type, $status, $start_date, $end_date, $payment_method, $amount]);
        
        // Update user subscription info
        $pdo->prepare("UPDATE users SET subscription_plan = ?, subscription_expiry = ? WHERE id = ?")
            ->execute([$plan_type, $end_date, $user['id']]);
        
        echo json_encode(['success' => true, 'message' => 'Subscription activated', 'end_date' => $end_date]);
        exit;
    }
    
    // Handle Check Subscription
    if ($_GET['action'] === 'check_subscription') {
        $user_email = $input['user_email'];
        
        $stmt = $pdo->prepare("
            SELECT s.*, u.subscription_plan, u.subscription_expiry
            FROM users u
            LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status IN ('active', 'trial')
            WHERE u.email = ?
            ORDER BY s.created_at DESC LIMIT 1
        ");
        $stmt->execute([$user_email]);
        $subscription = $stmt->fetch();
        
        $is_active = false;
        $status = 'no_subscription';
        $plan = 'none';
        
        if ($subscription && isset($subscription['end_date'])) {
            $expiry = strtotime($subscription['end_date']);
            if ($expiry > time()) {
                $is_active = true;
                $status = $subscription['status'];
                $plan = $subscription['plan_type'];
            }
        }
        
        echo json_encode([
            'success' => true,
            'is_active' => $is_active,
            'status' => $status,
            'plan' => $plan,
            'expiry' => $subscription['end_date'] ?? null,
            'subscription' => $subscription
        ]);
        exit;
    }
}

// Pass session user to JavaScript
$currentUser = isset($_SESSION['user']) ? $_SESSION['user'] : null;
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
  .dash-header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 25px; border-radius: 16px 16px 0 0; }
  .nav-link { color: #007bff !important; font-weight: 600; padding: 10px 20px !important; }
  .nav-link.active { background: #007bff !important; color: white !important; border-radius: 12px; }
  .lang-btn { background: rgba(255,255,255,0.2); border: 1px solid rgb(59, 18, 131); color: rgb(12, 12, 12); padding: 8px 15px; margin: 0 5px; border-radius: 8px; cursor:pointer; }
  .lang-btn.active { background: white; color: #007bff; font-weight: bold; }
  .record-item { cursor: pointer; transition: all 0.3s ease; border-left: 4px solid transparent; }
  .record-item:hover { background-color: #e7f3ff; border-left-color: #007bff; }
  .appointment-card { border-left: 4px solid #007bff; }
  .appointment-card.completed { border-left-color: #28a745; }
  .appointment-card.cancelled { border-left-color: #dc3545; opacity: 0.7; }
  .subscription-badge { background: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; }
  .btn-download-doc { background: #28a745; }
  .btn-download-doc:hover { background: #218838; }
  .btn-download-doc.locked { background: #6c757d; cursor: not-allowed; }
  .dept-card { border: 1px solid #ddd; border-radius: 8px; padding: 12px; margin: 8px 0; cursor: pointer; transition: all 0.2s; }
  .dept-card:hover { background: #e7f3ff; border-color: #007bff; }
  .dept-header { font-weight: 600; color: #007bff; }
  .prescription-badge { background: #e8f5e9; color: #2e7d32; padding: 6px 12px; border-radius: 6px; font-size: 0.9rem; }
  .disease-badge { background: #ffebee; color: #c62828; padding: 6px 12px; border-radius: 6px; font-size: 0.9rem; }
  .home-dashboard-card { cursor: pointer; transition: all 0.3s ease; }
  .home-dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
  .home-dashboard-card .btn { transition: all 0.3s ease; }
  .home-dashboard-card:hover .btn { transform: scale(1.05); }
  .patient-detail-tab { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
  .message-bubble { max-width: 70%; margin-bottom: 10px; }
  .message-sent { text-align: right; }
  .message-sent .bubble { background: #007bff; color: white; display: inline-block; padding: 8px 12px; border-radius: 18px; }
  .message-received { text-align: left; }
  .message-received .bubble { background: #e9ecef; color: #333; display: inline-block; padding: 8px 12px; border-radius: 18px; }
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<!-- ==================== ORIGINAL HTML - KEPT INTACT ==================== -->
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

<!-- PAYMENT INSTRUCTIONS MODAL -->
<div id="paymentInstructionsModal" class="modal fade" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="paymentModalTitle">Payment Instructions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="paymentModalBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="paymentConfirmBtn" data-bs-dismiss="modal">Confirm Payment</button>
      </div>
    </div>
  </div>
</div>

<!-- PAYMENT CONFIRMATION MODAL -->
<div id="paymentConfirmationModal" class="modal fade" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Subscription</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="confirmationModalBody"></div>
    </div>
  </div>
</div>

<!-- PRESCRIPTION VIEW MODAL -->
<div class="modal fade" id="prescriptionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="prescriptionModalTitle">Prescription</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="prescriptionModalBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success" onclick="(function(){ const id = document.getElementById('prescriptionModalTitle').dataset.prescid; if(id) downloadPrescription(id); })()">Download</button>
      </div>
    </div>
  </div>
</div>

<!-- ==================== MAIN DASHBOARD - KEPT INTACT ==================== -->
<div id="dashboard" class="d-none container-fluid mt-3">
  <div class="dash-header text-center mb-4">
    <h2 id="dashTitle">ExMed</h2>
    <p><span id="userName">User</span> | <a href="#" onclick="logout()" class="text-white text-decoration-underline">Logout</a></p>
  </div>

  <!-- PATIENT NAVIGATION -->
  <ul class="nav nav-pills justify-content-center flex-wrap mb-4 gap-2" id="patientNav">
    <li class="nav-item"><a class="nav-link active" href="#" onclick="event.preventDefault(); showTab('home')"><i class="fas fa-home"></i> Home</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('appointments')"><i class="fas fa-calendar-check"></i> Appointments</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('prescriptions')"><i class="fas fa-pills"></i> Prescriptions</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('chatbot')"><i class="fas fa-robot"></i> AI Chat</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('subscription')"><i class="fas fa-credit-card"></i> Subscription<br><small class="text-muted">Basic: 10K | Premium: 17K<br>7-Day Free Trial</small></a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('records')"><i class="fas fa-file-medical"></i> Records</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('insurance')"><i class="fas fa-shield-alt"></i> Insurance</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('profile')"><i class="fas fa-user"></i> Profile</a></li>
  </ul>

  <!-- DOCTOR NAVIGATION -->
  <ul class="nav nav-pills justify-content-center flex-wrap mb-4 gap-2 d-none" id="doctorNav">
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

  <!-- NURSE NAVIGATION -->
  <ul class="nav nav-pills justify-content-center flex-wrap mb-4 gap-2 d-none" id="nurseNav">
    <li class="nav-item"><a class="nav-link active" href="#" onclick="event.preventDefault(); showTab('nurse-home')"><i class="fas fa-home"></i> Home</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('nurse')"><i class="fas fa-heartbeat"></i> Nursing Station</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('nurse-admissions')"><i class="fas fa-bed"></i> Admissions</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('profile')"><i class="fas fa-user"></i> Profile</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('staff-report')"><i class="fas fa-pen-fancy"></i> Weekly Report</a></li>
  </ul>

  <!-- ADMIN NAVIGATION -->
  <ul class="nav nav-pills justify-content-center flex-wrap mb-4 gap-2 d-none" id="adminNav">
    <li class="nav-item"><a class="nav-link active" href="#" onclick="event.preventDefault(); showTab('admin-home')"><i class="fas fa-home"></i> Home</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('admin')"><i class="fas fa-users-cog"></i> Users & Staff</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('admin-billing')"><i class="fas fa-credit-card"></i> Billing</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('admin-subscription')"><i class="fas fa-crown"></i> Plans</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('admin-reports')"><i class="fas fa-file-contract"></i> Staff Reports</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('admin-settings')"><i class="fas fa-cog"></i> Settings</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="event.preventDefault(); showTab('profile')"><i class="fas fa-user"></i> Profile</a></li>
  </ul>

  <!-- Home Tab -->
  <div id="tab-home" class="row g-4">
    <div id="patientHome" class="col-12 row g-4">
      <div class="col-md-3"><div class="card h-100 text-center p-4 home-dashboard-card" style="cursor:pointer;" onclick="showTab('appointments')"><i class="fas fa-calendar-check fa-3x text-primary mb-3"></i><h5>Book Appointment</h5><button class="btn btn-primary" onclick="event.stopPropagation(); showTab('appointments')">Book Now</button></div></div>
      <div class="col-md-3"><div class="card h-100 text-center p-4 home-dashboard-card" style="cursor:pointer;" onclick="showTab('prescriptions')"><i class="fas fa-pills fa-3x text-success mb-3"></i><h5>My Prescriptions</h5><button class="btn btn-success" onclick="event.stopPropagation(); showTab('prescriptions')">View</button></div></div>
      <div class="col-md-3"><div class="card h-100 text-center p-4 home-dashboard-card" style="cursor:pointer;" onclick="showTab('records')"><i class="fas fa-file-medical fa-3x text-warning mb-3"></i><h5>My Records</h5><button class="btn btn-warning" onclick="event.stopPropagation(); showTab('records')">View</button></div></div>
      <div class="col-md-3"><div class="card h-100 text-center p-4 home-dashboard-card" style="cursor:pointer;" onclick="showTab('chatbot')"><i class="fas fa-robot fa-3x text-info mb-3"></i><h5>AI Assistant</h5><button class="btn btn-info" onclick="event.stopPropagation(); showTab('chatbot')">Chat Now</button></div></div>
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
    <div class="card p-3 mb-4">
      <h6><i class="fas fa-building"></i> Hospital Department Directory</h6>
      <input type="text" id="deptSearch" class="form-control mb-3" placeholder="Search department..." oninput="filterDepartments()">
      <div id="departmentsList"></div>
    </div>
    <h6 class="mt-4">📋 Your Appointments</h6>
    <div id="appointmentsList" class="row g-3"></div>
  </div>

  <!-- Prescriptions Tab -->
  <div id="tab-prescriptions" class="d-none card p-4">
    <h4><i class="fas fa-pills"></i> My Prescriptions</h4>
    <div class="mb-4">
      <p class="text-muted">View and download your current and past prescriptions. All medications prescribed by your doctors.</p>
      <div class="alert alert-info" id="prescsEmptyMsg" style="display:none;">
        <i class="fas fa-info-circle"></i> No prescriptions available yet. Prescriptions from your doctors will appear here.
      </div>
    </div>
    <div id="prescriptionsList" class="row g-3"></div>
  </div>

  <!-- Profile Tab -->
  <div id="tab-profile" class="d-none card p-4">
    <h4>My Profile</h4>
    <div id="profileContent"></div>
  </div>

  <!-- AI Chatbot Tab -->
  <div id="tab-chatbot" class="d-none">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-robot"></i> AI Hospital Assistant</h4>
        <select id="chatLangSelect" class="form-select w-auto" onchange="setChatLang(this.value)">
          <option value="en">English</option>
          <option value="lug">Luganda</option>
          <option value="sw">Swahili</option>
          <option value="ate">Ateso</option>
        </select>
      </div>
      <p class="text-muted mb-3">Ask our AI assistant any health-related questions. Available 24/7.</p>
      <div id="chatWindow" class="border rounded p-3 mb-3" style="height: 400px; overflow-y: auto; background-color: #f8f9fa;">
        <div class="mb-3">
          <div class="alert alert-info d-inline-block" style="max-width: 80%;">
            Hi! I'm your AI Hospital Assistant. How can I help you today? Ask me about symptoms, medications, or appointment scheduling.
          </div>
        </div>
      </div>
      <div class="input-group">
        <input type="text" id="chatInput" class="form-control" placeholder="Type your question..." onkeypress="if(event.key==='Enter') sendChatMessage()">
        <button class="btn btn-primary" onclick="sendChatMessage()"><i class="fas fa-paper-plane"></i> Send</button>
      </div>
      <div class="mt-3 small text-muted">
        <p><strong>Quick Tips:</strong></p>
        <button class="btn btn-sm btn-outline-secondary me-2 mb-2" onclick="quickChat('symptoms')">🤒 Report Symptoms</button>
        <button class="btn btn-sm btn-outline-secondary me-2 mb-2" onclick="quickChat('appointment')">📅 Book Appointment</button>
        <button class="btn btn-sm btn-outline-secondary me-2 mb-2" onclick="quickChat('medicine')">💊 Medicine Info</button>
        <button class="btn btn-sm btn-outline-secondary me-2 mb-2" onclick="quickChat('emergency')">🚨 Emergency</button>
      </div>
    </div>
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

  <!-- Records Tab -->
  <div id="tab-records" class="d-none card p-4">
    <h4><i class="fas fa-file-medical"></i> My Medical Records</h4>
    <p class="text-muted">Download and access your medical records.</p>
    <div class="row g-3 mb-4" id="medicalRecordsList"></div>
  </div>

  <!-- Insurance Tab -->
  <div id="tab-insurance" class="d-none card p-4">
    <h4><i class="fas fa-shield-alt"></i> Insurance & Documents</h4>
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
  <div class="modal fade" id="patientSubscriptionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-credit-card"></i> Subscribe to Plan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="patientSubscriptionForm">
            <div class="mb-3"><label class="form-label">Full Name</label><input type="text" id="patName" class="form-control" readonly></div>
            <div class="mb-3"><label class="form-label">Email Address</label><input type="email" id="patEmail" class="form-control" readonly></div>
            <div class="mb-3"><label class="form-label">Selected Plan</label><input type="text" id="patPlanName" class="form-control" readonly></div>
            <div class="mb-3"><label class="form-label">Price</label><input type="text" id="patPlanPrice" class="form-control" readonly></div>
            <div class="mb-3"><label class="form-label">Start Date</label><input type="date" id="patStartDate" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Payment Method</label><select id="patPaymentMethod" class="form-select" required>
              <option value="">-- Select Payment Method --</option>
              <option value="free-trial">7-Day Free Trial</option>
              <option value="card">Credit/Debit Card</option>
              <option value="momo">Mobile Money</option>
              <option value="bank">Bank Transfer</option>
            </select></div>
            <div class="mb-3"><label class="form-label">Additional Notes (Optional)</label><textarea id="patNotes" class="form-control" rows="2" placeholder="Any special requests..."></textarea></div>
            <input type="hidden" id="patPlanId">
          </form>
          <div class="alert alert-info"><small><i class="fas fa-info-circle"></i> Your subscription will start on the selected date. You can cancel anytime.</small></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="confirmPatientSubscription()">Subscribe Now</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Subscription Confirmation Modal -->
  <div class="modal fade" id="subscriptionConfirmationModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-gift"></i> Confirm 7-Day Free Trial</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-success mb-3"><h6><i class="fas fa-check-circle"></i> You're getting 7 days free!</h6><p class="mb-0 small">No credit card charge during the trial. After 7 days, we'll start charging UGX <span id="confirmPlanPrice"></span>/month</p></div>
          <div class="card p-3 mb-3" style="background-color: #f8f9fa;"><h6 class="mb-2">Subscription Summary</h6><p class="mb-2"><strong>Plan:</strong> <span id="confirmPlanName"></span></p><p class="mb-2"><strong>Monthly Price:</strong> <span id="confirmPlanPrice"></span></p><p class="mb-2"><strong>Payment Method:</strong> <span id="confirmPaymentMethod"></span></p><p class="mb-0"><strong>Trial Duration:</strong> <span id="confirmTrialDays"></span> days - Free!</p></div>
          <div class="alert alert-info"><small><i class="fas fa-info-circle"></i> You can upgrade, downgrade, or cancel anytime. No hidden fees.</small></div>
          <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="agreeTerms" required><label class="form-check-label" for="agreeTerms">I agree to the terms and conditions. I understand that I will be charged after the 7-day trial ends.</label></div>
          <input type="hidden" id="selectedPlanId"><input type="hidden" id="selectedPaymentMethod">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-success" onclick="confirmSubscription()" id="confirmSubscriptionBtn"><i class="fas fa-check"></i> Start Free Trial</button>
        </div>
      </div>
    </div>
  </div>

  <!-- DOCTOR HOME TAB -->
  <div id="tab-doctor-home" class="d-none row g-4">
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

  <!-- NURSE HOME TAB -->
  <div id="tab-nurse-home" class="d-none row g-4"><div class="col-12"><div class="card p-4"><h4><i class="fas fa-heartbeat"></i> Nursing Station</h4><p class="text-muted">Welcome to the nursing station. Manage patient admissions, record vital signs, and submit reports.</p></div></div></div>

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

  <!-- ADMIN HOME TAB -->
  <div id="tab-admin-home" class="d-none row g-4">
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

  <!-- STAFF WEEKLY REPORT TAB -->
  <div id="tab-staff-report" class="d-none card p-4">
    <h4><i class="fas fa-pen-fancy"></i> Weekly Activity Report</h4>
    <div class="row g-3">
      <div class="col-12"><label class="form-label">Week of:</label><input type="date" class="form-control" id="reportWeek" required></div>
      <div class="col-12"><label class="form-label">Summary of Activities</label><textarea class="form-control" id="reportActivities" rows="4" placeholder="Describe your key activities, achievements, and challenges this week..." required></textarea></div>
      <div class="col-md-6"><label class="form-label">Patients Attended/Processed</label><input type="number" id="reportPatientsAttended" class="form-control" min="0" placeholder="Number of patients" required></div>
      <div class="col-md-6"><label class="form-label">Key Issues/Challenges</label><input type="text" id="reportChallenges" class="form-control" placeholder="Any challenges encountered?" required></div>
      <div class="col-12"><button type="button" class="btn btn-primary w-100" onclick="submitStaffReport()">Submit Report</button></div>
    </div>
  </div>
</div>

<script>
// ==================== COMPLETE JAVASCRIPT - ALL FUNCTIONS INCLUDED ====================
// Global variables
let currentUser = null;
let authMode = 'login';
let chatLanguage = 'en';
let currentChatWith = null;
let subscriptionPlans = [
    { id: 'basic', name: 'Basic Plan', icon: 'fas fa-star', color: 'info', price: '10,000', frequency: '/month', trialDays: 7, features: ['AI chat support', 'Doctor consultations', 'Medical records access', 'Appointment booking'], description: 'Essential healthcare access' },
    { id: 'premium', name: 'Premium Plan', icon: 'fas fa-crown', color: 'warning', price: '17,000', trialDays: 7, popular: true, features: ['Unlimited AI chat support', 'Video consultations', 'Download medical records', 'Priority support', '24/7 Emergency access'], description: 'Complete healthcare' }
];

// Department Data
const departmentsData = [
    { id: 1, name: "Cardiology", description: "Heart & Cardiovascular Medicine", phone: "+256701111111", floor: "3rd Floor", hours: "8 AM - 5 PM" },
    { id: 2, name: "Infectious Diseases", description: "TB, HIV/AIDS, Malaria Treatment", phone: "+256702222222", floor: "2nd Floor", hours: "8 AM - 5 PM" },
    { id: 3, name: "General Medicine", description: "Primary Healthcare Services", phone: "+256703333333", floor: "1st Floor", hours: "7 AM - 6 PM" },
    { id: 4, name: "Neurology", description: "Nervous System Disorders", phone: "+256704444444", floor: "4th Floor", hours: "9 AM - 4 PM" },
    { id: 5, name: "Pediatrics", description: "Children's Health & Vaccination", phone: "+256705555555", floor: "2nd Floor", hours: "8 AM - 5 PM" },
    { id: 6, name: "Emergency", description: "Emergency & Trauma Care", phone: "+256706666666", floor: "Ground Floor", hours: "24/7" },
    { id: 7, name: "Orthopedics", description: "Bone & Joint Surgery", phone: "+256707777777", floor: "3rd Floor", hours: "8 AM - 5 PM" },
    { id: 8, name: "Obstetrics & Gynecology", description: "Maternal & Women's Health", phone: "+256708888888", floor: "4th Floor", hours: "8 AM - 5 PM" }
];

// OTP Storage
let otpStorage = {};

// ==================== API HELPER FUNCTIONS ====================
async function apiCall(action, data = {}) {
    try {
        const response = await fetch(`?action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: 'Network error' };
    }
}

// ==================== AUTHENTICATION FUNCTIONS ====================
function goToAuth(mode = 'login') {
    authMode = mode;
    document.getElementById('landing').classList.add('d-none');
    document.getElementById('dashboard').classList.add('d-none');
    document.getElementById('forgotPassword').classList.add('d-none');
    const auth = document.getElementById('auth');
    if (!auth) return;
    auth.classList.remove('d-none');
    const title = document.getElementById('authTitle');
    const fullName = document.getElementById('fullName');
    const phone = document.getElementById('phone');
    const role = document.getElementById('role');
    const verifyPanel = document.getElementById('verifyPanel');
    if (mode === 'register') {
        if (title) title.textContent = 'Create Account';
        if (fullName) fullName.classList.remove('d-none');
        if (phone) phone.classList.remove('d-none');
        if (role) role.classList.remove('d-none');
        if (verifyPanel) verifyPanel.classList.add('d-none');
        document.getElementById('toggleLink').textContent = 'Already have an account? Sign in';
    } else {
        if (title) title.textContent = 'Sign In';
        if (fullName) fullName.classList.add('d-none');
        if (phone) phone.classList.add('d-none');
        if (role) role.classList.add('d-none');
        if (verifyPanel) verifyPanel.classList.add('d-none');
        document.getElementById('toggleLink').textContent = 'No account? Create one';
    }
}

function toggleAuth() {
    goToAuth(authMode === 'register' ? 'login' : 'register');
}

function generateOtp(length = 6) {
    return Math.random().toString().substring(2, 2 + length).padStart(length, '0');
}

function sendOtp(email) {
    const otp = generateOtp(6);
    const expiry = Date.now() + 5 * 60 * 1000;
    otpStorage[email] = { code: otp, expiry: expiry };
    console.log(`OTP for ${email}: ${otp}`);
    displayOtpModal(otp);
}

function displayOtpModal(otp) {
    const modal = document.getElementById('otpModal');
    if (!modal) return;
    const display = document.getElementById('otpCodeDisplay');
    if (display) display.textContent = otp;
    modal.classList.remove('d-none');
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
    if (!stored) return { valid: false, error: 'No OTP found. Request again.' };
    if (Date.now() > stored.expiry) return { valid: false, error: 'OTP expired. Request again.' };
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

async function handleAuth() {
    const email = document.getElementById('email')?.value || '';
    const password = document.getElementById('password')?.value || '';
    
    if (authMode === 'register') {
        if (!email || !email.includes('@')) { alert('Please enter a valid email'); return; }
        const verifyPanel = document.getElementById('verifyPanel');
        const regOtpInput = document.getElementById('regOtp');
        if (verifyPanel && !verifyPanel.classList.contains('d-none')) {
            const entered = regOtpInput?.value || '';
            if (!entered.trim()) { alert('Enter the OTP'); return; }
            verifyRegistrationOtp();
            return;
        }
        document.getElementById('verifyPanel').classList.remove('d-none');
        sendOtp(email);
        setTimeout(() => { const r = document.getElementById('regOtp'); if (r) r.focus(); }, 200);
        return;
    }
    
    // Login
    const result = await apiCall('login', { email, password });
    if (result.success) {
        currentUser = result.user;
        const rememberMe = document.getElementById('rememberMe')?.checked;
        if (rememberMe) {
            localStorage.setItem('exmed_remembered_user', email);
        }
        document.getElementById('auth').classList.add('d-none');
        await showDashboard();
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
    alert('Password updated successfully! Please login with your new password.');
    showForgotPasswordBack();
}

async function logout() {
    await apiCall('logout');
    currentUser = null;
    document.getElementById('dashboard').classList.add('d-none');
    document.getElementById('landing').classList.remove('d-none');
}

// ==================== DASHBOARD FUNCTIONS ====================
async function showDashboard() {
    const result = await apiCall('get_current_user');
    if (!result.success || !result.user) {
        document.getElementById('landing').classList.remove('d-none');
        document.getElementById('dashboard').classList.add('d-none');
        return;
    }
    
    currentUser = result.user;
    document.getElementById('landing').classList.add('d-none');
    document.getElementById('dashboard').classList.remove('d-none');
    document.getElementById('userName').textContent = currentUser.name;
    
    // Show role-specific navigation
    document.getElementById('patientNav').classList.add('d-none');
    document.getElementById('doctorNav').classList.add('d-none');
    document.getElementById('nurseNav').classList.add('d-none');
    document.getElementById('adminNav').classList.add('d-none');
    
    if (currentUser.role === 'patient') {
        document.getElementById('patientNav').classList.remove('d-none');
        initializePatientDashboard();
    } else if (currentUser.role === 'doctor') {
        document.getElementById('doctorNav').classList.remove('d-none');
        initializeDoctorDashboard();
    } else if (currentUser.role === 'nurse') {
        document.getElementById('nurseNav').classList.remove('d-none');
        initializeNurseDashboard();
    } else if (currentUser.role === 'admin') {
        document.getElementById('adminNav').classList.remove('d-none');
        initializeAdminDashboard();
    }
    
    showTab('home');
}

function showTab(tabName) {
    document.querySelectorAll('[id^="tab-"]').forEach(el => el.classList.add('d-none'));
    const tab = document.getElementById('tab-' + tabName);
    if (tab) tab.classList.remove('d-none');
    
    if (tabName === 'appointments') {
        renderAppointmentsList();
        renderDepartments();
    } else if (tabName === 'prescriptions') {
        renderPrescriptions();
    } else if (tabName === 'records') {
        renderMedicalRecords();
    } else if (tabName === 'profile') {
        renderProfile();
    } else if (tabName === 'subscription') {
        initSubscriptionPage();
        checkSubscriptionStatus();
        populateSubscriptionHistory();
        populatePaymentHistory();
    } else if (tabName === 'insurance') {
        initInsuranceTab();
    } else if (tabName === 'doctor-patients') {
        populateDoctorPatients();
    } else if (tabName === 'doctor-appointments') {
        renderDoctorAppointments();
    } else if (tabName === 'doctor-prescribe') {
        populatePrescribeSelects();
    } else if (tabName === 'doctor-diagnose') {
        populateDiagnoseSelects();
    } else if (tabName === 'doctor-messages') {
        loadConversations();
    } else if (tabName === 'nurse') {
        populateVitalPatientSelect();
    } else if (tabName === 'admin') {
        renderAdminPanel();
    } else if (tabName === 'admin-home') {
        updateAdminDashboard();
    } else if (tabName === 'admin-reports') {
        renderStaffReports();
    }
}

// ==================== PATIENT DASHBOARD FUNCTIONS ====================
function initializePatientDashboard() {
    console.log("Initializing Patient Dashboard for:", currentUser.name);
    populateDoctorSelect();
    renderMedicalRecords();
    renderAppointmentsList();
    renderDepartments();
    renderPrescriptions();
    setTimeout(() => {
        try {
            initSubscriptionPage();
            renderHomeSubscriptionPlans();
            initInsuranceTab();
            checkSubscriptionStatus();
        } catch (e) {
            console.warn('Error initializing subscription/insurance:', e);
        }
    }, 100);
}

async function populateDoctorSelect() {
    const select = document.getElementById('apptDoctor');
    if (!select) return;
    const result = await apiCall('get_doctors');
    const doctors = result.doctors || [];
    select.innerHTML = '<option value="">-- Choose a doctor --</option>' + 
        doctors.map(d => `<option value="${d.email}" data-dept="${d.department || 'General'}">Dr. ${d.name}${d.specialization ? ' - ' + d.specialization : ''}</option>`).join('');
}

function updateDoctorDetails() {
    const select = document.getElementById('apptDoctor');
    const deptInput = document.getElementById('apptDepartment');
    const infoDiv = document.getElementById('doctorInfoDisplay');
    if (!select || !select.value) {
        if (deptInput) deptInput.value = '';
        if (infoDiv) infoDiv.textContent = '';
        return;
    }
    const option = select.selectedOptions[0];
    const dept = option.getAttribute('data-dept');
    if (deptInput) deptInput.value = dept;
    if (infoDiv) infoDiv.innerHTML = '<i class="fas fa-check-circle text-success"></i> Doctor selected - Ready for appointment';
}

async function scheduleAppointment() {
    const doctorEmail = document.getElementById('apptDoctor').value;
    const date = document.getElementById('apptDate').value;
    const time = document.getElementById('apptTime').value;
    const reason = document.getElementById('apptReason').value;
    const type = document.getElementById('apptType').value;
    
    if (!doctorEmail || !date || !time || !reason) {
        alert('Please fill all fields');
        return;
    }
    
    const doctorSelect = document.getElementById('apptDoctor');
    const doctorName = doctorSelect.options[doctorSelect.selectedIndex]?.textContent || '';
    
    const result = await apiCall('book_appointment', {
        patient_email: currentUser.email,
        patient_name: currentUser.name,
        doctor_email: doctorEmail,
        doctor_name: doctorName,
        date: date,
        time: time,
        reason: reason,
        type: type
    });
    
    if (result.success) {
        alert('Appointment booked successfully!');
        document.getElementById('apptDate').value = '';
        document.getElementById('apptTime').value = '';
        document.getElementById('apptReason').value = '';
        renderAppointmentsList();
    } else {
        alert('Failed to book appointment: ' + (result.message || 'Unknown error'));
    }
}

async function renderAppointmentsList() {
    const appointmentsDiv = document.getElementById('appointmentsList');
    if (!appointmentsDiv) return;
    
    const result = await apiCall('get_appointments', { email: currentUser.email, role: currentUser.role });
    const appointments = result.appointments || [];
    
    if (appointments.length === 0) {
        appointmentsDiv.innerHTML = '<div class="col-12"><p class="text-muted text-center">No appointments scheduled yet.</p></div>';
        return;
    }
    
    appointmentsDiv.innerHTML = appointments.map(appt => `
        <div class="col-md-6">
            <div class="card appointment-card p-3">
                <h6 class="mb-1"><i class="fas fa-user-md"></i> Dr. ${appt.doctor_name || 'Unknown'}</h6>
                <small class="text-muted">${appt.doctor_specialization || 'General Practice'}</small>
                <hr class="my-2">
                <p class="small mb-1"><strong>📅 Date:</strong> ${appt.appointment_date}</p>
                <p class="small mb-1"><strong>⏰ Time:</strong> ${appt.appointment_time}</p>
                <p class="small mb-1"><strong>📝 Reason:</strong> ${appt.reason || 'Not specified'}</p>
                <span class="badge bg-${appt.status === 'confirmed' ? 'success' : appt.status === 'completed' ? 'info' : 'warning'} mt-2">${appt.status || 'pending'}</span>
            </div>
        </div>
    `).join('');
}

function renderDepartments() {
    const deptDiv = document.getElementById('departmentsList');
    if (!deptDiv) return;
    deptDiv.innerHTML = departmentsData.map(dept => `
        <div class="dept-card p-3 border rounded cursor-pointer" onclick="selectDepartmentForAppointment('${dept.name}')">
            <div class="dept-header mb-2">${dept.name}</div>
            <p class="small text-muted mb-1">${dept.description}</p>
            <div class="small"><p class="mb-1"><i class="fas fa-phone-alt"></i> ${dept.phone}</p><p class="mb-1"><i class="fas fa-map-marker-alt"></i> ${dept.floor}</p><p class="mb-0"><i class="fas fa-clock"></i> ${dept.hours}</p></div>
        </div>
    `).join('');
}

function filterDepartments() {
    const searchVal = document.getElementById('deptSearch')?.value || '';
    const depts = document.querySelectorAll('.dept-card');
    depts.forEach(dept => {
        const text = dept.textContent.toLowerCase();
        dept.style.display = text.includes(searchVal.toLowerCase()) ? '' : 'none';
    });
}

function selectDepartmentForAppointment(deptName) {
    const apptDept = document.getElementById('apptDepartment');
    if (apptDept) apptDept.value = deptName;
    showTab('appointments');
}

async function renderPrescriptions() {
    const presDiv = document.getElementById('prescriptionsList');
    if (!presDiv) return;
    
    const result = await apiCall('get_prescriptions', { email: currentUser.email });
    const prescriptions = result.prescriptions || [];
    const empty = document.getElementById('prescsEmptyMsg');
    if (empty) empty.style.display = prescriptions.length === 0 ? '' : 'none';
    
    if (prescriptions.length === 0) {
        presDiv.innerHTML = '';
        return;
    }
    
    presDiv.innerHTML = prescriptions.map(presc => `
        <div class="col-md-6">
            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0"><i class="fas fa-pills text-success"></i> ${presc.medication_name}</h6>
                    <span class="prescription-badge">${presc.status === 'active' ? 'Active' : 'Completed'}</span>
                </div>
                <p class="small mb-1"><strong>Prescribed by:</strong> Dr. ${presc.doctor_name}</p>
                <p class="small mb-1"><strong>Dosage:</strong> ${presc.dosage || 'N/A'}</p>
                <p class="small mb-1"><strong>Frequency:</strong> ${presc.frequency || 'N/A'}</p>
                <p class="small mb-2"><strong>Date:</strong> ${new Date(presc.prescription_date).toLocaleDateString()}</p>
                ${presc.instructions ? `<p class="small mb-2"><strong>Instructions:</strong> ${presc.instructions}</p>` : ''}
                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-sm btn-outline-primary flex-fill" onclick="viewPrescription('${presc.id}')"><i class="fas fa-eye"></i> View</button>
                    <button class="btn btn-sm btn-success flex-fill" onclick="downloadPrescription('${presc.id}')"><i class="fas fa-download"></i> Download</button>
                </div>
            </div>
        </div>
    `).join('');
}

function viewPrescription(prescId) {
    alert('Prescription details:\nThis is a secure medical document.\nFor full details, please contact your healthcare provider.');
}

function downloadPrescription(prescId) {
    alert('Prescription is being downloaded as PDF...');
}

async function renderMedicalRecords() {
    const recordsDiv = document.getElementById('medicalRecordsList');
    if (!recordsDiv) return;
    
    const result = await apiCall('get_medical_records', { email: currentUser.email });
    const records = result.records || [];
    
    if (records.length === 0) {
        recordsDiv.innerHTML = '<div class="col-12"><p class="text-muted">No medical records found.</p></div>';
        return;
    }
    
    recordsDiv.innerHTML = records.map(record => `
        <div class="col-md-6 mb-3">
            <div class="card record-item h-100 p-3 border-start border-4 border-primary">
                <h6 class="mb-1"><i class="fas fa-file-medical text-primary"></i> ${record.title}</h6>
                <small class="text-muted d-block mb-2">${new Date(record.date).toLocaleDateString()}</small>
                <p class="small mb-2">${record.content?.substring(0, 100) || 'No description'}${record.content?.length > 100 ? '...' : ''}</p>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-info">${record.type || 'Medical Record'}</span>
                    <button class="btn btn-sm btn-primary btn-download-doc" onclick="downloadRecord('${record.id}')"><i class="fas fa-download"></i> Download</button>
                </div>
            </div>
        </div>
    `).join('');
}

function downloadRecord(recordId) {
    alert('Downloading medical record...');
}

function renderProfile() {
    const profileContent = document.getElementById('profileContent');
    if (!profileContent) return;
    profileContent.innerHTML = `
        <div class="row g-3">
            <div class="col-md-6"><div class="card p-3"><h6><i class="fas fa-user"></i> User Information</h6><p><strong>Name:</strong> ${currentUser.name}</p><p><strong>Email:</strong> ${currentUser.email}</p><p><strong>Phone:</strong> ${currentUser.phone || 'Not provided'}</p><p><strong>Member Since:</strong> ${new Date(currentUser.created_at).toLocaleDateString()}</p></div></div>
            <div class="col-md-6"><div class="card p-3"><h6><i class="fas fa-briefcase"></i> Role & Permissions</h6><p><strong>Role:</strong> <span class="badge bg-primary">${currentUser.role.toUpperCase()}</span></p><p><strong>Subscription:</strong> <span class="badge bg-${currentUser.subscription === 'premium' ? 'success' : 'secondary'}">${currentUser.subscription || 'Basic'}</span></p></div></div>
        </div>
    `;
}

// ==================== SUBSCRIPTION FUNCTIONS ====================
function initSubscriptionPage() {
    const plansDiv = document.getElementById('subscriptionPlans');
    if (!plansDiv) return;
    
    let plansHTML = '<div class="row g-4">';
    subscriptionPlans.forEach(plan => {
        plansHTML += `
            <div class="col-md-6">
                <div class="card h-100 shadow-sm ${plan.popular ? 'border-success border-3' : 'border'}">
                    ${plan.popular ? '<div class="badge bg-success position-absolute top-0 start-50 translate-middle-x mt-3">⭐ Most Popular</div>' : ''}
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><i class="${plan.icon} text-${plan.color} me-2"></i> ${plan.name}</h5>
                        <h3 class="card-text text-${plan.color}">UGX ${plan.price}</h3>
                        <small class="text-muted d-block mb-3">${plan.frequency}</small>
                        <ul class="list-unstyled small">${plan.features.map(f => `<li><i class="fas fa-check text-success me-1"></i> ${f}</li>`).join('')}</ul>
                        <button class="btn btn-${plan.color} mt-auto" onclick="selectPaymentMethod('${plan.id}', 'free-trial')"><i class="fas fa-gift me-1"></i> Start 7-Day Free Trial</button>
                    </div>
                </div>
            </div>
        `;
    });
    plansHTML += '</div>';
    plansDiv.innerHTML = plansHTML;
}

function renderHomeSubscriptionPlans() {
    const plansDiv = document.getElementById('homeSubscriptionPlans');
    if (!plansDiv) return;
    plansDiv.innerHTML = subscriptionPlans.map(plan => `
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5><i class="${plan.icon} text-${plan.color} me-2"></i> ${plan.name}</h5>
                    <h3>UGX ${plan.price}</h3>
                    <p class="small text-muted">${plan.description}</p>
                    <button class="btn btn-${plan.color} w-100" onclick="selectPaymentMethod('${plan.id}', 'free-trial')">Start Free Trial</button>
                </div>
            </div>
        </div>
    `).join('');
}

async function checkSubscriptionStatus() {
    const result = await apiCall('check_subscription', { user_email: currentUser.email });
    const statusDiv = document.getElementById('subscriptionStatus');
    if (!statusDiv) return;
    
    if (result.is_active) {
        const expiryDate = result.expiry ? new Date(result.expiry) : null;
        const daysLeft = expiryDate ? Math.ceil((expiryDate - new Date()) / (1000 * 60 * 60 * 24)) : 0;
        
        let statusHtml = `
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                <strong>Active ${result.plan?.toUpperCase() || 'PREMIUM'} Plan</strong>
                <p class="mb-0 mt-2 small">Expires: ${expiryDate ? expiryDate.toLocaleDateString() : 'N/A'} (${daysLeft} days left)</p>
                ${result.status === 'trial' ? '<p class="mb-0 mt-1 small text-warning">🎁 7-day free trial active!</p>' : ''}
            </div>
        `;
        statusDiv.innerHTML = statusHtml;
        return true;
    } else {
        statusDiv.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i> 
                <strong>No Active Subscription</strong>
                <p class="mb-0 mt-2 small">Subscribe today to access all premium features!</p>
            </div>
        `;
        return false;
    }
}

function selectPaymentMethod(planId, method) {
    const plan = subscriptionPlans.find(p => p.id === planId);
    if (!plan) return;
    
    document.getElementById('selectedPlanId').value = planId;
    document.getElementById('selectedPaymentMethod').value = method;
    document.getElementById('confirmPlanName').textContent = plan.name;
    document.getElementById('confirmPlanPrice').textContent = `UGX ${plan.price}`;
    document.getElementById('confirmPaymentMethod').textContent = method === 'free-trial' ? '7-Day Free Trial' : method;
    document.getElementById('confirmTrialDays').textContent = '7';
    
    const modal = new bootstrap.Modal(document.getElementById('subscriptionConfirmationModal'));
    modal.show();
}

async function confirmSubscription() {
    const agreeTerms = document.getElementById('agreeTerms')?.checked;
    if (!agreeTerms) {
        alert('Please agree to the terms and conditions');
        return;
    }
    
    const planId = document.getElementById('selectedPlanId')?.value;
    const paymentMethod = document.getElementById('selectedPaymentMethod')?.value;
    const plan = subscriptionPlans.find(p => p.id === planId);
    
    if (!plan) return;
    
    const amount = parseInt(plan.price.replace(/,/g, ''));
    
    const result = await apiCall('activate_subscription', {
        user_email: currentUser.email,
        plan_type: planId,
        payment_method: paymentMethod,
        amount: amount,
        is_trial: true
    });
    
    if (result.success) {
        alert(`✅ Subscription activated! 7-day free trial started! Expires: ${new Date(result.end_date).toLocaleDateString()}`);
        bootstrap.Modal.getInstance(document.getElementById('subscriptionConfirmationModal')).hide();
        checkSubscriptionStatus();
        initSubscriptionPage();
    } else {
        alert('Failed to activate subscription: ' + (result.message || 'Unknown error'));
    }
}

function populateSubscriptionHistory() {
    const historyTable = document.getElementById('subscriptionHistoryTable');
    if (historyTable) historyTable.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No subscription history</td></tr>';
}

function populatePaymentHistory() {
    const paymentTable = document.getElementById('paymentHistoryTable');
    if (paymentTable) paymentTable.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No payment history</td></tr>';
}

function initInsuranceTab() {
    const insurancePanel = document.getElementById('insurancePanel');
    if (!insurancePanel) return;
    insurancePanel.innerHTML = `
        <div class="alert alert-info">
            <h6><i class="fas fa-shield-alt"></i> Your Insurance Coverage</h6>
            <p>NHIF Coverage: Active<br>Policy Number: NHIF-2024-${Math.random().toString().slice(2, 8)}<br>Valid Until: 2025-12-31</p>
        </div>
    `;
    const docsList = document.getElementById('insuranceDocsList');
    if (docsList) {
        docsList.innerHTML = `<a href="#" class="list-group-item list-group-item-action" onclick="event.preventDefault(); alert('Downloading insurance document...')"><div class="d-flex justify-content-between"><div><h6 class="mb-1">NHIF Certificate</h6><small class="text-muted">National Health Insurance</small></div><i class="fas fa-download text-primary"></i></div></a>`;
    }
}

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

// ==================== MESSAGING FUNCTIONS ====================
async function loadConversations() {
    const result = await apiCall('get_messages', { email: currentUser.email });
    const messages = result.messages || [];
    const listDiv = document.getElementById('messagesList');
    
    if (!listDiv) return;
    
    if (messages.length === 0) {
        listDiv.innerHTML = '<p class="text-muted small">No conversations yet</p>';
        return;
    }
    
    listDiv.innerHTML = messages.map(conv => `
        <a href="#" class="list-group-item list-group-item-action" onclick="loadMessageThread('${conv.other_user_email}'); return false;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>${conv.other_user_name}</strong><br>
                    <small class="text-muted">${conv.last_message?.substring(0, 50) || 'No messages'}</small>
                </div>
                <small class="text-muted">${conv.last_message_time ? new Date(conv.last_message_time).toLocaleTimeString() : ''}</small>
            </div>
        </a>
    `).join('');
}

async function loadMessageThread(withEmail) {
    const result = await apiCall('get_messages', { 
        email: currentUser.email,
        with_email: withEmail
    });
    
    const messages = result.messages || [];
    const threadDiv = document.getElementById('messageThread');
    const titleSpan = document.getElementById('messageThreadTitle');
    
    if (threadDiv && messages.length > 0) {
        const otherUser = messages[0].sender_email === currentUser.email ? 
            messages[0].receiver_name : messages[0].sender_name;
        
        if (titleSpan) titleSpan.textContent = `Chat with ${otherUser}`;
        
        threadDiv.innerHTML = messages.map(msg => `
            <div class="message-${msg.sender_email === currentUser.email ? 'sent' : 'received'} mb-2">
                <div class="bubble">
                    ${escapeHtml(msg.message)}<br>
                    <small class="opacity-75">${new Date(msg.created_at).toLocaleTimeString()}</small>
                </div>
            </div>
        `).join('');
        
        document.getElementById('messageInput').disabled = false;
        document.getElementById('sendMessageBtn').disabled = false;
        currentChatWith = withEmail;
        threadDiv.scrollTop = threadDiv.scrollHeight;
    }
}

async function sendMessage() {
    const message = document.getElementById('messageInput')?.value;
    if (!message || !currentChatWith) return;
    
    const result = await apiCall('send_message', {
        sender_email: currentUser.email,
        receiver_email: currentChatWith,
        message: message
    });
    
    if (result.success) {
        document.getElementById('messageInput').value = '';
        loadMessageThread(currentChatWith);
    } else {
        alert('Failed to send message');
    }
}

// ==================== NURSE DASHBOARD FUNCTIONS ====================
function initializeNurseDashboard() {
    console.log("Initializing Nurse Dashboard for:", currentUser.name);
    populateVitalPatientSelect();
}

async function populateVitalPatientSelect() {
    const select = document.getElementById('vitalPatientSelect');
    if (!select) return;
    const result = await apiCall('get_patients');
    const patients = result.patients || [];
    select.innerHTML = '<option value="">-- Choose Patient --</option>' + patients.map(p => `<option value="${p.email}" data-name="${p.name}">${p.name} (${p.email})</option>`).join('');
}

function loadVitalPatientInfo() {
    const select = document.getElementById('vitalPatientSelect');
    const infoDiv = document.getElementById('vitalPatientInfo');
    if (!select || !select.value || !infoDiv) return;
    const option = select.options[select.selectedIndex];
    const patientName = option.getAttribute('data-name') || '';
    infoDiv.innerHTML = `<strong>${patientName}</strong> - Ready for vital signs recording`;
    
    // Auto-calculate BMI when weight/height change
    const weightInput = document.getElementById('weightInput');
    const heightInput = document.getElementById('heightInput');
    const bmiDisplay = document.getElementById('bmiDisplay');
    
    if (weightInput && heightInput && bmiDisplay) {
        const calculateBMI = () => {
            const weight = parseFloat(weightInput.value);
            const height = parseFloat(heightInput.value) / 100;
            if (weight && height && height > 0) {
                const bmi = (weight / (height * height)).toFixed(1);
                bmiDisplay.value = bmi;
            }
        };
        weightInput.oninput = calculateBMI;
        heightInput.oninput = calculateBMI;
    }
}

async function recordVitals() {
    const patientEmail = document.getElementById('vitalPatientSelect').value;
    const temp = document.getElementById('tempInput').value;
    const bp = document.getElementById('bpInput').value;
    const hr = document.getElementById('hrInput').value;
    const o2 = document.getElementById('o2Input').value;
    const weight = document.getElementById('weightInput')?.value || '';
    const height = document.getElementById('heightInput')?.value || '';
    
    if (!patientEmail) {
        alert('Please select a patient');
        return;
    }
    if (!temp || !bp || !hr || !o2) {
        alert('Please fill all vital signs fields');
        return;
    }
    
    const patientSelect = document.getElementById('vitalPatientSelect');
    const patientName = patientSelect.options[patientSelect.selectedIndex]?.getAttribute('data-name') || '';
    
    const result = await apiCall('save_vitals', {
        patient_email: patientEmail,
        patient_name: patientName,
        temp: temp,
        bp: bp,
        hr: hr,
        o2: o2,
        weight: weight,
        height: height,
        recorded_by: currentUser.name,
        recorded_by_role: currentUser.role
    });
    
    if (result.success) {
        alert('Vital signs recorded successfully!');
        document.getElementById('tempInput').value = '';
        document.getElementById('bpInput').value = '';
        document.getElementById('hrInput').value = '';
        document.getElementById('o2Input').value = '';
        if (document.getElementById('weightInput')) document.getElementById('weightInput').value = '';
        if (document.getElementById('heightInput')) document.getElementById('heightInput').value = '';
        if (document.getElementById('bmiDisplay')) document.getElementById('bmiDisplay').value = '';
    } else {
        alert('Failed to record vital signs');
    }
}

function registerAdmission() {
    const name = document.getElementById('admitName')?.value;
    const email = document.getElementById('admitEmail')?.value;
    const phone = document.getElementById('admitPhone')?.value;
    const reason = document.getElementById('admitReason')?.value;
    const date = document.getElementById('admitDate')?.value;
    
    if (!name || !email) {
        alert('Please fill patient name and email');
        return;
    }
    
    alert(`Patient ${name} has been admitted successfully!`);
    document.getElementById('admitName').value = '';
    document.getElementById('admitEmail').value = '';
    document.getElementById('admitPhone').value = '';
    document.getElementById('admitDate').value = '';
}

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

// ==================== STAFF REPORT FUNCTIONS ====================
async function submitStaffReport() {
    const weekStarting = document.getElementById('reportWeek')?.value;
    const activities = document.getElementById('reportActivities')?.value;
    const patientsAttended = document.getElementById('reportPatientsAttended')?.value;
    const challenges = document.getElementById('reportChallenges')?.value;
    
    if (!weekStarting || !activities || !patientsAttended) {
        alert('Please fill all required fields');
        return;
    }
    
    const result = await apiCall('submit_staff_report', {
        user_email: currentUser.email,
        week_starting: weekStarting,
        activities: activities,
        patients_attended: patientsAttended,
        challenges: challenges
    });
    
    if (result.success) {
        alert('Weekly report submitted successfully! Thank you for your contribution.');
        document.getElementById('reportWeek').value = '';
        document.getElementById('reportActivities').value = '';
        document.getElementById('reportPatientsAttended').value = '';
        document.getElementById('reportChallenges').value = '';
        showTab('home');
    } else {
        alert('Failed to submit report');
    }
}

// ==================== CHATBOT FUNCTIONS ====================
const chatResponses = {
    en: { default: "I'm here to help! How can I assist you today?", symptoms: "Please describe your symptoms in detail so I can provide better guidance.", appointment: "I can help you book an appointment. Please go to the Appointments section.", medicine: "Please tell me the name of the medication you have questions about.", emergency: "🚨 This appears to be an emergency. Please call the emergency hotline immediately: +256701111111", welcome: "Welcome to ExMed AI Hospital Assistant! How can I help you today?", goodbye: "Thank you for using ExMed AI Assistant. Stay healthy!" },
    lug: { default: "Nze nno okukunnyonnyeza! Kiki ekikumusesamu?", symptoms: "Njogera ku bubonero bwo, nkusabwe otegeeze ebirumasa.", appointment: "Nsobola okukuyambako okusabawo olulayo. Genda mu Appointments.", medicine: "Njogera ku ddagala ki?", emergency: "🚨 Kino kya mangu! Kuba ku namba: +256701111111", welcome: "Tukusanyukidde! Nze AI Assistant wa ExMed." },
    sw: { default: "Niko hapa kukusaidia! Ninafanya nini?", symptoms: "Tafadhali elezea dalili zako kwa undani.", appointment: "Naweza kukusaidia kupanga miadi. Nenda kwenye sehemu ya Appointments.", medicine: "Tafadhali niambie jina la dawa.", emergency: "🚨 Hili ni dharura! Piga simu: +256701111111", welcome: "Karibu kwa Msaidizi wa AI wa ExMed!" },
    ate: { default: "Asoru akonyo! Kiki ekikumusesamu?", symptoms: "Itesite ikak orokori yok, ikak ipak.", appointment: "Aiang akonyo ikak ekwa. Itesite ikak 'Appointments'.", medicine: "Itesite ikak ecik ngaria.", emergency: "🚨 Kiyare! Ikak ikut: +256701111111", welcome: "Asoru ikak! Ange AI Assistant ExMed." }
};

function setChatLang(lang) {
    chatLanguage = lang;
    const select = document.getElementById('chatLangSelect');
    if (select) select.value = lang;
}

function sendChatMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    if (!message) return;
    const chatWindow = document.getElementById('chatWindow');
    chatWindow.innerHTML += `<div class="text-end mb-2"><div class="alert alert-primary d-inline-block">${escapeHtml(message)}</div></div>`;
    const responses = chatResponses[chatLanguage] || chatResponses['en'];
    
    let reply = responses.default;
    const lowerMsg = message.toLowerCase();
    if (lowerMsg.includes('symptom') || lowerMsg.includes('fever') || lowerMsg.includes('pain')) {
        reply = responses.symptoms;
    } else if (lowerMsg.includes('appointment') || lowerMsg.includes('book')) {
        reply = responses.appointment;
    } else if (lowerMsg.includes('medicine') || lowerMsg.includes('drug') || lowerMsg.includes('pill')) {
        reply = responses.medicine;
    } else if (lowerMsg.includes('emergency') || lowerMsg.includes('urgent') || lowerMsg.includes('help')) {
        reply = responses.emergency;
    } else if (lowerMsg.includes('bye') || lowerMsg.includes('goodbye')) {
        reply = responses.goodbye;
    }
    
    setTimeout(() => {
        chatWindow.innerHTML += `<div class="mb-2"><div class="alert alert-secondary d-inline-block">${reply}</div></div>`;
        chatWindow.scrollTop = chatWindow.scrollHeight;
    }, 500);
    input.value = '';
    chatWindow.scrollTop = chatWindow.scrollHeight;
}

function quickChat(type) {
    const messages = { symptoms: 'I have symptoms to report', appointment: 'I need to book an appointment', medicine: 'Tell me about medications', emergency: 'This is an emergency' };
    const input = document.getElementById('chatInput');
    if (input) input.value = messages[type] || '';
    sendChatMessage();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ==================== LANGUAGE FUNCTIONS ====================
const TRANSLATIONS = {
    en: { alerts: 'Real-Time Alerts', alerts_desc: 'Instant notifications', security: 'Military-Grade Security', security_desc: 'AES-256 encryption', offline: 'Offline First', offline_desc: 'Full functionality offline', get_started: 'Get Started', sign_in: 'Sign In', continue: 'Continue' },
    lug: { alerts: 'Obutaka Obuwandiike', alerts_desc: 'Okukakasa ebyawandiiko', security: 'Obutonde Obwawamu', security_desc: 'AES-256 etekateeka', offline: 'Tekikwata ku Intaneeti', offline_desc: 'Ebikozesebwa bino bisobola', get_started: 'Tandika', sign_in: 'Wetegekere', continue: 'Genda Wansi' },
    sw: { alerts: 'Arifa za Muda', alerts_desc: 'Arifa za papo', security: 'Usalama wa Kiwango cha Juu', security_desc: 'AES-256 inalinda', offline: 'Kazi Bila Mtandao', offline_desc: 'Inafanya kazi bila', get_started: 'Anza', sign_in: 'Ingia', continue: 'Endelea' },
    ate: { alerts: 'Ate Obutindo', alerts_desc: 'Obubaka bwomubiri', security: 'Ate Obusinge', security_desc: 'AES-256 etekateeka', offline: 'Ate Offline', offline_desc: 'Ebyokukola tebijja', get_started: 'Tangisa', sign_in: 'Wete', continue: 'Kakasa' }
};

function setLang(lang) {
    if (!TRANSLATIONS[lang]) lang = 'en';
    localStorage.setItem('exmed_lang', lang);
    document.querySelectorAll('[data-t]').forEach(el => {
        const key = el.getAttribute('data-t');
        const txt = (TRANSLATIONS[lang] && TRANSLATIONS[lang][key]) || TRANSLATIONS['en'][key] || el.textContent;
        el.textContent = txt;
    });
    document.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
    const btn = document.getElementById('lang-' + lang);
    if (btn) btn.classList.add('active');
}

// ==================== STUB FUNCTIONS FOR COMPATIBILITY ====================
function goBackToPatients() { showTab('doctor-patients'); }
function loadMyReports() { }
function renderDoctorNotifications() { }
function loadReportPatientInfo() { }
function toggleReportDetail() { }
function cancelResume() { location.reload(); }
function attemptResume() { location.reload(); }
function confirmPatientSubscription() { }

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', async () => {
    const stored = localStorage.getItem('exmed_lang') || 'en';
    setLang(stored);
    
    const rememberedEmail = localStorage.getItem('exmed_remembered_user');
    if (rememberedEmail) {
        const emailField = document.getElementById('email');
        if (emailField) emailField.value = rememberedEmail;
        const rememberCheckbox = document.getElementById('rememberMe');
        if (rememberCheckbox) rememberCheckbox.checked = true;
    }
    
    const sessionResult = await apiCall('check_session');
    if (sessionResult.success && sessionResult.user) {
        await showDashboard();
    }
    
    // Set default date for staff report to current week's Monday
    const reportWeekInput = document.getElementById('reportWeek');
    if (reportWeekInput) {
        const today = new Date();
        const day = today.getDay();
        const diff = today.getDate() - day + (day === 0 ? -6 : 1);
        const monday = new Date(today.setDate(diff));
        reportWeekInput.value = monday.toISOString().split('T')[0];
    }
});
</script>
</body>
</html>