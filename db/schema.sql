-- SQL Schema for Pharmacy Management System (SQLite)

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    full_name TEXT NOT NULL,
    role TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME
);

-- 2. Patients Table
CREATE TABLE IF NOT EXISTS patients (
    id TEXT PRIMARY KEY,
    card_number TEXT UNIQUE NOT NULL,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    date_of_birth DATE NOT NULL,
    gender TEXT,
    phone_number TEXT,
    email TEXT,
    address TEXT,
    emergency_contact TEXT,
    emergency_phone TEXT,
    blood_type TEXT,
    allergies TEXT,
    status TEXT DEFAULT 'admitted',
    admission_date DATETIME,
    discharge_date DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Medications Table
CREATE TABLE IF NOT EXISTS medications (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    generic_name TEXT NOT NULL,
    dosage_form TEXT NOT NULL,
    strength TEXT NOT NULL,
    manufacturer TEXT,
    barcode TEXT UNIQUE,
    category TEXT,
    requires_prescription INTEGER DEFAULT 0,
    controlled_substance INTEGER DEFAULT 0,
    storage_conditions TEXT,
    unit_price REAL NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 4. Inventory Table
CREATE TABLE IF NOT EXISTS inventory (
    id TEXT PRIMARY KEY,
    medication_id TEXT NOT NULL,
    batch_number TEXT NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 0,
    expiry_date DATE NOT NULL,
    reorder_level INTEGER NOT NULL DEFAULT 10,
    location TEXT NOT NULL,
    last_restocked DATETIME,
    last_checked DATETIME,
    checked_by TEXT,
    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE CASCADE,
    FOREIGN KEY (checked_by) REFERENCES users(id)
);

-- 5. Prescriptions Table
CREATE TABLE IF NOT EXISTS prescriptions (
    id TEXT PRIMARY KEY,
    patient_id TEXT NOT NULL,
    doctor_id TEXT NOT NULL,
    diagnosis TEXT,
    notes TEXT,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id)
);

-- 6. Prescription Items Table
CREATE TABLE IF NOT EXISTS prescription_items (
    id TEXT PRIMARY KEY,
    prescription_id TEXT NOT NULL,
    medication_id TEXT NOT NULL,
    medication_name TEXT NOT NULL,
    dosage TEXT,
    frequency TEXT,
    duration TEXT,
    quantity INTEGER NOT NULL,
    dispensed_quantity INTEGER DEFAULT 0,
    pharmacist_id TEXT,
    dispensed_at DATETIME,
    instructions TEXT,
    status TEXT DEFAULT 'pending',
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (medication_id) REFERENCES medications(id),
    FOREIGN KEY (pharmacist_id) REFERENCES users(id)
);

-- 8. Enquiries Table
CREATE TABLE IF NOT EXISTS enquiries (
    id TEXT PRIMARY KEY,
    prescription_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    doctor_id TEXT NOT NULL,
    message TEXT NOT NULL,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (doctor_id) REFERENCES users(id)
);

-- 7. Audit Logs Table
CREATE TABLE IF NOT EXISTS audit_logs (
    id TEXT PRIMARY KEY,
    user_id TEXT NOT NULL,
    action TEXT NOT NULL,
    entity_type TEXT,
    entity_id TEXT,
    details TEXT,
    ip_address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert Demo Data
INSERT INTO users (id, username, password, full_name, role, email) VALUES
('u1', 'admin', 'admin123', 'System Administrator', 'admin', 'admin@pharmacy.com'),
('u2', 'dr.smith', 'doctor123', 'Dr. John Smith', 'doctor', 'smith@hospital.com'),
('u3', 'pharmacist1', 'pharma123', 'Sarah Johnson', 'pharmacist', 'sarah@pharmacy.com');

INSERT INTO patients (id, card_number, first_name, last_name, date_of_birth, gender, phone_number, status, admission_date) VALUES
('p1', 'PAT-001', 'John', 'Doe', '1985-05-15', 'male', '555-0101', 'admitted', '2026-04-20'),
('p2', 'PAT-002', 'Jane', 'Smith', '1992-08-22', 'female', '555-0102', 'admitted', '2026-04-25');

INSERT INTO medications (id, name, generic_name, dosage_form, strength, manufacturer, barcode, category, unit_price) VALUES
('m1', 'Amoxicillin', 'Amoxicillin', 'Capsule', '500mg', 'PharmaCorp', '123456789', 'Antibiotic', 15.50),
('m2', 'Paracetamol', 'Acetaminophen', 'Tablet', '500mg', 'HealthPlus', '987654321', 'Analgesic', 5.00);

INSERT INTO inventory (id, medication_id, batch_number, quantity, expiry_date, reorder_level, location) VALUES
('i1', 'm1', 'BATCH-001', 100, '2027-12-31', 20, 'Shelf A1'),
('i2', 'm2', 'BATCH-002', 5, '2026-06-30', 10, 'Shelf B2');

INSERT INTO prescriptions (id, patient_id, doctor_id, diagnosis, status, created_at) VALUES
('rx1', 'p1', 'u2', 'Bacterial Infection', 'pending', '2026-04-26 10:00:00');

INSERT INTO prescription_items (id, prescription_id, medication_id, medication_name, dosage, frequency, duration, quantity) VALUES
('rxi1', 'rx1', 'm1', 'Amoxicillin', '500mg', '3 times daily', '7 days', 21);

INSERT INTO audit_logs (id, user_id, action, entity_type, entity_id, details) VALUES
('log1', 'u1', 'LOGIN', 'USER', 'u1', 'Admin logged in');

-- Performance Indexes
CREATE INDEX IF NOT EXISTS idx_prescriptions_status ON prescriptions(status);
CREATE INDEX IF NOT EXISTS idx_prescriptions_patient_id ON prescriptions(patient_id);
CREATE INDEX IF NOT EXISTS idx_prescription_items_rx_id ON prescription_items(prescription_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_created_at ON audit_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_inventory_medication_id ON inventory(medication_id);
