<?php
// Comprehensive Database connection for Pharmacy Management System
$db_path = __DIR__ . '/../db/pharmacy.db';

// Ensure the db directory exists
if (!file_exists(dirname($db_path))) {
    mkdir(dirname($db_path), 0777, true);
}

try {
    $pdo = new PDO("sqlite:" . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Check if tables exist, if not, create them
    $tableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetch();
    if (!$tableExists) {
        $schema = file_get_contents(__DIR__ . '/../db/schema.sql');
        $pdo->exec($schema);
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function sendResponse($success, $data = null, $error = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error
    ]);
    exit;
}

function generateId($prefix = '') {
    return $prefix . bin2hex(random_bytes(8));
}

function addAuditLog($pdo, $userId, $action, $entityType, $entityId, $details) {
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (id, user_id, action, entity_type, entity_id, details) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([generateId('log_'), $userId, $action, $entityType, $entityId, $details]);
    } catch (Exception $e) {
        // Silently fail or log to file
    }
}
?>
