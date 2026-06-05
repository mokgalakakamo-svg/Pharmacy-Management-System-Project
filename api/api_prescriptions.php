<?php
// Handle prescriptions and dispensing logic
if ($module === 'prescriptions') {
    if ($action === 'list') {
        // Patients can only see their own prescriptions
        if ($_SESSION['user']['role'] === 'patient') {
            // Find patient record for this user
            $patStmt = $pdo->prepare("SELECT id FROM patients WHERE email = ? LIMIT 1");
            $patStmt->execute([$_SESSION['user']['email']]);
            $patient = $patStmt->fetch();
            if (!$patient) {
                sendResponse(true, []);
            }
            $stmt = $pdo->prepare("SELECT p.*, pt.first_name || ' ' || pt.last_name as patient_name, u.full_name as doctor_name FROM prescriptions p JOIN patients pt ON p.patient_id = pt.id JOIN users u ON p.doctor_id = u.id WHERE p.patient_id = ? ORDER BY p.created_at DESC");
            $stmt->execute([$patient['id']]);
            sendResponse(true, $stmt->fetchAll());
        } else {
            // Both doctors and pharmacists/assistants need to see prescriptions
            if (!checkPermission('prescriptions') && !checkPermission('dispensing')) {
                sendResponse(false, null, 'Unauthorized');
            }
            $stmt = $pdo->query("SELECT p.*, pt.first_name || ' ' || pt.last_name as patient_name, u.full_name as doctor_name FROM prescriptions p JOIN patients pt ON p.patient_id = pt.id JOIN users u ON p.doctor_id = u.id ORDER BY p.created_at DESC");
            sendResponse(true, $stmt->fetchAll());
        }
    } elseif ($action === 'get') {
        $id = $_GET['id'] ?? '';
        $stmt = $pdo->prepare("SELECT p.*, pt.first_name || ' ' || pt.last_name as patient_name, u.full_name as doctor_name FROM prescriptions p JOIN patients pt ON p.patient_id = pt.id JOIN users u ON p.doctor_id = u.id WHERE p.id = ?");
        $stmt->execute([$id]);
        $rx = $stmt->fetch();
        if ($rx) {
            // Patients can only view their own prescriptions
            if ($_SESSION['user']['role'] === 'patient') {
                $patStmt = $pdo->prepare("SELECT id FROM patients WHERE email = ? LIMIT 1");
                $patStmt->execute([$_SESSION['user']['email']]);
                $patient = $patStmt->fetch();
                if (!$patient || $rx['patient_id'] !== $patient['id']) {
                    sendResponse(false, null, 'Unauthorized');
                }
            }
            $iStmt = $pdo->prepare("SELECT * FROM prescription_items WHERE prescription_id = ?");
            $iStmt->execute([$id]);
            $rx['items'] = $iStmt->fetchAll();
            sendResponse(true, $rx);
        } else {
            sendResponse(false, null, 'Not found');
        }
    } elseif ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $rxId = generateId('rx_');
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO prescriptions (id, patient_id, doctor_id, diagnosis, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$rxId, $data['patientId'], $_SESSION['user']['id'], $data['diagnosis']]);
            $itemStmt = $pdo->prepare("INSERT INTO prescription_items (id, prescription_id, medication_id, medication_name, dosage, frequency, quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($data['items'] as $item) {
                $itemStmt->execute([generateId('rxi_'), $rxId, $item['medicationId'], $item['name'], $item['dosage'], $item['frequency'], $item['quantity']]);
            }
            $pdo->commit();
            addAuditLog($pdo, $_SESSION['user']['id'], 'CREATE_RX', 'prescriptions', $rxId, "New prescription for patient {$data['patientId']}");
            sendResponse(true, ['id' => $rxId]);
        } catch (Exception $e) {
            $pdo->rollBack();
            sendResponse(false, null, $e->getMessage());
        }
    }
} elseif ($module === 'dispense') {
    requirePermission('dispensing');
    
    // Check for cupboard authorization
    $isAuthorized = isset($_SESSION['cupboard_authorized']) && $_SESSION['cupboard_authorized'] === true;
    $isTimedOut = isset($_SESSION['cupboard_auth_time']) && (time() - $_SESSION['cupboard_auth_time'] > 300); // 5 mins timeout
    
    if (!$isAuthorized || $isTimedOut) {
        if ($isTimedOut) {
            unset($_SESSION['cupboard_authorized']);
            unset($_SESSION['cupboard_auth_time']);
        }
        sendResponse(false, null, 'Cupboard access required. Please authorize in the Cupboard module first.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $pdo->beginTransaction();
        try {
            // Get medication and patient details before updating
            $detailsStmt = $pdo->prepare("SELECT pi.medication_name, pi.dosage, pi.quantity, p.first_name, p.last_name, rx.id as rx_id FROM prescription_items pi JOIN prescriptions rx ON pi.prescription_id = rx.id JOIN patients p ON rx.patient_id = p.id WHERE pi.id = ?");
            $detailsStmt->execute([$data['itemId']]);
            $details = $detailsStmt->fetch();
            
            $stmt = $pdo->prepare("UPDATE prescription_items SET status = 'dispensed', dispensed_quantity = quantity, pharmacist_id = ?, dispensed_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$_SESSION['user']['id'], $data['itemId']]);
            $invStmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - (SELECT quantity FROM prescription_items WHERE id = ?) WHERE medication_id = (SELECT medication_id FROM prescription_items WHERE id = ?)");
            $invStmt->execute([$data['itemId'], $data['itemId']]);
            $rxIdStmt = $pdo->prepare("SELECT prescription_id FROM prescription_items WHERE id = ?");
            $rxIdStmt->execute([$data['itemId']]);
            $rxId = $rxIdStmt->fetchColumn();
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM prescription_items WHERE prescription_id = ? AND status = 'pending'");
            $checkStmt->execute([$rxId]);
            if ($checkStmt->fetchColumn() == 0) {
                $updRx = $pdo->prepare("UPDATE prescriptions SET status = 'dispensed' WHERE id = ?");
                $updRx->execute([$rxId]);
            }
            $pdo->commit();
            
            // Create detailed audit log with medication and patient information
            $auditDetails = sprintf(
                "Medication: %s | Dosage: %s | Qty: %d | Patient: %s %s",
                $details['medication_name'],
                $details['dosage'],
                $details['quantity'],
                $details['first_name'],
                $details['last_name']
            );
            addAuditLog($pdo, $_SESSION['user']['id'], 'MEDICATION_DISPENSED', 'prescription_items', $data['itemId'], $auditDetails);
            sendResponse(true);
        } catch (Exception $e) {
            $pdo->rollBack();
            sendResponse(false, null, $e->getMessage());
        }
    }
} elseif ($module === 'medications' && $action === 'list') {
    $stmt = $pdo->query("SELECT * FROM medications ORDER BY name ASC");
    sendResponse(true, $stmt->fetchAll());
} elseif ($module === 'enquiries') {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = generateId('enq_');
        
        // Get doctor_id from prescription
        $rxStmt = $pdo->prepare("SELECT doctor_id FROM prescriptions WHERE id = ?");
        $rxStmt->execute([$data['prescriptionId']]);
        $doctor_id = $rxStmt->fetchColumn();
        
        $stmt = $pdo->prepare("INSERT INTO enquiries (id, prescription_id, user_id, doctor_id, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id, $data['prescriptionId'], $_SESSION['user']['id'], $doctor_id, $data['message']]);
        
        addAuditLog($pdo, $_SESSION['user']['id'], 'CREATE_ENQUIRY', 'enquiries', $id, "New enquiry for prescription {$data['prescriptionId']}");
        sendResponse(true, ['id' => $id]);
    } elseif ($action === 'list') {
        $userRole = $_SESSION['user']['role'];
        if ($userRole === 'doctor') {
            $stmt = $pdo->prepare("SELECT e.*, u.full_name as from_user, p.id as rx_id FROM enquiries e JOIN users u ON e.user_id = u.id JOIN prescriptions p ON e.prescription_id = p.id WHERE e.doctor_id = ? ORDER BY e.created_at DESC");
            $stmt->execute([$_SESSION['user']['id']]);
        } else {
            $stmt = $pdo->prepare("SELECT e.*, u.full_name as to_doctor, p.id as rx_id FROM enquiries e JOIN users u ON e.doctor_id = u.id JOIN prescriptions p ON e.prescription_id = p.id WHERE e.user_id = ? ORDER BY e.created_at DESC");
            $stmt->execute([$_SESSION['user']['id']]);
        }
        sendResponse(true, $stmt->fetchAll());
    }
}
?>
