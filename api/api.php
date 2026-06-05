<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user'])) {
    sendResponse(false, null, 'Not authenticated');
}

function checkPermission($permission) {
    $role = $_SESSION['user']['role'];
    $permissions = [
        'admin' => ['dashboard', 'admin', 'patients', 'inventory', 'audit', 'settings'],
        'doctor' => ['dashboard', 'prescriptions', 'settings'],
        'pharmacist' => ['dashboard', 'dispensing', 'inventory', 'cupboard', 'settings'],
        'assistant' => ['dashboard', 'patients', 'prescriptions', 'dispensing', 'inventory', 'cupboard', 'settings'],
        'stock_taker' => ['dashboard', 'inventory', 'settings'],
    ];
    return in_array($permission, $permissions[$role] ?? []);
}

function requirePermission($permission) {
    if (!checkPermission($permission)) {
        sendResponse(false, null, 'Unauthorized');
    }
}

$module = $_GET['module'] ?? '';
$action = $_GET['action'] ?? 'list';

switch ($module) {
    case 'patients':
        // Patients can retrieve their own record
        // Doctors can also access patients for prescriptions
        if ($_SESSION['user']['role'] !== 'patient' && $_SESSION['user']['role'] !== 'doctor') {
            requirePermission('patients');
        }
        if ($action === 'list') {
            // Patients can only see their own record
            if ($_SESSION['user']['role'] === 'patient') {
                $stmt = $pdo->prepare("SELECT * FROM patients WHERE email = ? LIMIT 1");
                $stmt->execute([$_SESSION['user']['email']]);
                $patient = $stmt->fetch();
                sendResponse(true, $patient ? [$patient] : []);
            } else {
                // Doctors, admins, and other staff can see all patients
                $stmt = $pdo->query("SELECT id, card_number, first_name, last_name, date_of_birth, gender, phone_number, email, status FROM patients ORDER BY first_name ASC, last_name ASC");
                sendResponse(true, $stmt->fetchAll());
            }
        } elseif ($action === 'get') {
            $id = $_GET['id'] ?? '';
            $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
            $stmt->execute([$id]);
            $patient = $stmt->fetch();
            // Patients can only view their own record
            if ($_SESSION['user']['role'] === 'patient') {
                if (!$patient || $patient['email'] !== $_SESSION['user']['email']) {
                    sendResponse(false, null, 'Unauthorized');
                }
            }
            sendResponse(true, $patient);
        } elseif ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            // Only admins can create patients
            if ($_SESSION['user']['role'] !== 'admin') {
                sendResponse(false, null, 'Only administrators can add patients');
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $id = generateId('pat_');
            $stmt = $pdo->prepare("INSERT INTO patients (id, card_number, first_name, last_name, date_of_birth, gender, phone_number, email, address, blood_type, status, admission_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([
                $id, $data['cardNumber'], $data['firstName'], $data['lastName'], $data['dateOfBirth'], 
                $data['gender'], $data['phoneNumber'], $data['email'], $data['address'], 
                $data['bloodType'], 'admitted'
            ]);
            addAuditLog($pdo, $_SESSION['user']['id'], 'CREATE_PATIENT', 'patients', $id, "Patient {$data['firstName']} {$data['lastName']} registered");
            sendResponse(true, ['id' => $id]);
        } elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            // Only admins can update patients
            if ($_SESSION['user']['role'] !== 'admin') {
                sendResponse(false, null, 'Only administrators can update patients');
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("UPDATE patients SET first_name=?, last_name=?, date_of_birth=?, gender=?, phone_number=?, email=?, address=?, blood_type=?, status=? WHERE id=?");
            $stmt->execute([
                $data['firstName'], $data['lastName'], $data['dateOfBirth'], $data['gender'], 
                $data['phoneNumber'], $data['email'], $data['address'], $data['bloodType'], 
                $data['status'], $data['id']
            ]);
            addAuditLog($pdo, $_SESSION['user']['id'], 'UPDATE_PATIENT', 'patients', $data['id'], "Updated patient {$data['firstName']} {$data['lastName']}");
            sendResponse(true);
        }
        break;

    case 'inventory':
        requirePermission('inventory');
        if ($action === 'list') {
            $stmt = $pdo->query("SELECT i.*, m.name as medication_name, m.category, m.barcode, m.unit_price FROM inventory i JOIN medications m ON i.medication_id = m.id ORDER BY i.expiry_date ASC");
            sendResponse(true, $stmt->fetchAll());
        } elseif ($action === 'restock' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity + ?, last_restocked = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$data['amount'], $data['id']]);
            addAuditLog($pdo, $_SESSION['user']['id'], 'RESTOCK', 'inventory', $data['id'], "Restocked {$data['amount']} units");
            sendResponse(true);
        } elseif ($action === 'add_medication' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $medId = generateId('med_');
            $stmt = $pdo->prepare("INSERT INTO medications (id, name, generic_name, dosage_form, strength, category, unit_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$medId, $data['name'], $data['genericName'], $data['dosageForm'], $data['strength'], $data['category'], $data['unitPrice']]);
            
            $invId = generateId('inv_');
            $stmtInv = $pdo->prepare("INSERT INTO inventory (id, medication_id, batch_number, quantity, expiry_date, location) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtInv->execute([$invId, $medId, $data['batchNumber'], $data['quantity'], $data['expiryDate'], $data['location']]);
            
            addAuditLog($pdo, $_SESSION['user']['id'], 'ADD_MEDICATION', 'medications', $medId, "Added new medication: {$data['name']}");
            sendResponse(true);
        }
        break;

    case 'users':
        requirePermission('admin');
        if ($action === 'list') {
            $stmt = $pdo->query("SELECT id, username, full_name, role, email, is_active, last_login FROM users");
            sendResponse(true, $stmt->fetchAll());
        } elseif ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = generateId('u_');
            $stmt = $pdo->prepare("INSERT INTO users (id, username, password, full_name, role, email) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $data['username'], $data['password'], $data['fullName'], $data['role'], $data['email']]);
            addAuditLog($pdo, $_SESSION['user']['id'], 'CREATE_USER', 'users', $id, "Created user {$data['username']}");
            sendResponse(true);
        } elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!empty($data['password'])) {
                $stmt = $pdo->prepare("UPDATE users SET username=?, password=?, full_name=?, role=?, email=? WHERE id=?");
                $stmt->execute([$data['username'], $data['password'], $data['fullName'], $data['role'], $data['email'], $data['id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=?, full_name=?, role=?, email=? WHERE id=?");
                $stmt->execute([$data['username'], $data['fullName'], $data['role'], $data['email'], $data['id']]);
            }
            addAuditLog($pdo, $_SESSION['user']['id'], 'UPDATE_USER', 'users', $data['id'], "Updated user {$data['username']}");
            sendResponse(true);
        } elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            if ($data['id'] === $_SESSION['user']['id']) {
                sendResponse(false, null, 'Cannot delete your own account');
            }
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$data['id']]);
            addAuditLog($pdo, $_SESSION['user']['id'], 'DELETE_USER', 'users', $data['id'], "Deleted user ID: {$data['id']}");
            sendResponse(true);
        }
        break;

    case 'cupboard':
        requirePermission('cupboard');
        if ($action === 'access' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $method = $data['method'] ?? '';
            
            if ($method === 'Security Password') {
                $password = $data['password'] ?? '';
                $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND password = ?");
                $stmt->execute([$_SESSION['user']['id'], $password]);
                if (!$stmt->fetch()) {
                    addAuditLog($pdo, $_SESSION['user']['id'], 'CUPBOARD_ACCESS_FAILED', 'cupboard', 'main_cupboard', "Failed access attempt via Password");
                    sendResponse(false, null, 'Invalid security password');
                }
            }
            
            addAuditLog($pdo, $_SESSION['user']['id'], 'CUPBOARD_ACCESS', 'cupboard', 'main_cupboard', "Accessed via {$method}");
            sendResponse(true);
        } elseif ($action === 'authorize' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SESSION['cupboard_authorized'] = true;
            $_SESSION['cupboard_auth_time'] = time();
            sendResponse(true);
        } elseif ($action === 'deauthorize' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            unset($_SESSION['cupboard_authorized']);
            unset($_SESSION['cupboard_auth_time']);
            sendResponse(true);
        }
        break;

    case 'patient_search':
        requirePermission('admin');
        $cardNumber = $_GET['card_number'] ?? '';
        $name = $_GET['name'] ?? '';
        $email = $_GET['email'] ?? '';
        
        $query = "SELECT * FROM patients WHERE 1=1";
        $params = [];
        
        if (!empty($cardNumber)) {
            $query .= " AND card_number LIKE ?";
            $params[] = "%" . $cardNumber . "%";
        }
        if (!empty($name)) {
            $query .= " AND (first_name LIKE ? OR last_name LIKE ?)";
            $params[] = "%" . $name . "%";
            $params[] = "%" . $name . "%";
        }
        if (!empty($email)) {
            $query .= " AND email LIKE ?";
            $params[] = "%" . $email . "%";
        }
        
        $query .= " ORDER BY created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        sendResponse(true, $stmt->fetchAll());
        break;
    
    case 'patient_history':
        requirePermission('admin');
        $patientId = $_GET['patient_id'] ?? '';
        
        // Get all prescriptions for this patient with doctor and medication details
        $stmt = $pdo->prepare("
            SELECT 
                p.id as rx_id,
                p.diagnosis,
                p.status as rx_status,
                p.created_at,
                u.full_name as doctor_name,
                pi.id as item_id,
                pi.medication_name,
                pi.dosage,
                pi.frequency,
                pi.quantity,
                pi.status as item_status,
                pi.dispensed_at,
                pi.pharmacist_id,
                ph.full_name as pharmacist_name
            FROM prescriptions p
            JOIN users u ON p.doctor_id = u.id
            LEFT JOIN prescription_items pi ON p.id = pi.prescription_id
            LEFT JOIN users ph ON pi.pharmacist_id = ph.id
            WHERE p.patient_id = ?
            ORDER BY p.created_at DESC, pi.id
        ");
        $stmt->execute([$patientId]);
        $records = $stmt->fetchAll();
        
        // Group by prescription
        $grouped = [];
        foreach ($records as $record) {
            $rxId = $record['rx_id'];
            if (!isset($grouped[$rxId])) {
                $grouped[$rxId] = [
                    'rx_id' => $rxId,
                    'diagnosis' => $record['diagnosis'],
                    'rx_status' => $record['rx_status'],
                    'created_at' => $record['created_at'],
                    'doctor_name' => $record['doctor_name'],
                    'items' => []
                ];
            }
            if ($record['item_id']) {
                $grouped[$rxId]['items'][] = [
                    'medication_name' => $record['medication_name'],
                    'dosage' => $record['dosage'],
                    'frequency' => $record['frequency'],
                    'quantity' => $record['quantity'],
                    'status' => $record['item_status'],
                    'dispensed_at' => $record['dispensed_at'],
                    'pharmacist_name' => $record['pharmacist_name']
                ];
            }
        }
        
        sendResponse(true, array_values($grouped));
        break;
    
    case 'audit':
        // Allow both admins and users to access audit endpoint
        // But filter results based on role
        $userRole = $_SESSION['user']['role'];
        $userId = $_SESSION['user']['id'];
        
        if ($userRole === 'admin') {
            // Admins see all audit logs
            $stmt = $pdo->query("SELECT a.*, u.full_name as user_name FROM audit_logs a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 100");
        } else {
            // Regular users see only their own audit logs
            $stmt = $pdo->prepare("SELECT a.*, u.full_name as user_name FROM audit_logs a JOIN users u ON a.user_id = u.id WHERE a.user_id = ? ORDER BY a.created_at DESC LIMIT 100");
            $stmt->execute([$userId]);
        }
        sendResponse(true, $stmt->fetchAll());
        break;

    case 'medication_logs':
        requirePermission('admin');
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        // Get medication dispensing logs with date filtering
        $stmt = $pdo->prepare("
            SELECT 
                a.id,
                a.created_at,
                u.full_name as dispensed_by,
                a.details,
                a.action
            FROM audit_logs a
            JOIN users u ON a.user_id = u.id
            WHERE a.action = 'MEDICATION_DISPENSED'
            AND DATE(a.created_at) >= ?
            AND DATE(a.created_at) <= ?
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        sendResponse(true, $stmt->fetchAll());
        break;

    default:
        // Re-use existing cases for prescriptions and dispense from previous version
        require_once 'api_prescriptions.php'; 
        break;
}
?>
