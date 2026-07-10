<?php
require_once 'database.php';

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
?>