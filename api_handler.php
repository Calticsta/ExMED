<?php
session_start();
header('Content-Type: application/json');
require_once 'config/database.php';
require_once 'config/init_db.php';

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
?>