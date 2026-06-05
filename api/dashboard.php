<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user'])) {
    sendResponse(false, null, 'Unauthorized');
}

$action = $_GET['action'] ?? 'stats';

if ($action === 'stats') {
    try {
        // Admitted patients
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM patients WHERE status = 'admitted'");
        $admitted = $stmt->fetch()['count'];

        // Pending prescriptions
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM prescriptions WHERE status IN ('pending', 'partially_dispensed')");
        $pending = $stmt->fetch()['count'];

        // Total medications
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM medications");
        $totalMedications = $stmt->fetch()['count'];

        // Low stock alerts
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory WHERE quantity <= reorder_level");
        $lowStock = $stmt->fetch()['count'];

        // Expiring soon (next 3 months)
        $threeMonths = date('Y-m-d', strtotime('+3 months'));
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM inventory WHERE expiry_date <= ?");
        $stmt->execute([$threeMonths]);
        $expiring = $stmt->fetch()['count'];

        // Recent activity - filter by user role
        // Admins see all activities, regular users see only their own
        $userRole = $_SESSION['user']['role'];
        $userId = $_SESSION['user']['id'];
        
        if ($userRole === 'admin') {
            // Admins see all recent activities
            $stmt = $pdo->query("
                SELECT l.*, u.full_name as user_name 
                FROM audit_logs l 
                JOIN users u ON l.user_id = u.id 
                ORDER BY l.created_at DESC LIMIT 5
            ");
        } else {
            // Regular users see only their own activities
            $stmt = $pdo->prepare("
                SELECT l.*, u.full_name as user_name 
                FROM audit_logs l 
                JOIN users u ON l.user_id = u.id 
                WHERE l.user_id = ? 
                ORDER BY l.created_at DESC LIMIT 5
            ");
            $stmt->execute([$userId]);
        }
        $activities = $stmt->fetchAll();

        sendResponse(true, [
            'stats' => [
                'admittedPatients' => (int)$admitted,
                'pendingPrescriptions' => (int)$pending,
                'totalMedications' => (int)$totalMedications,
                'lowStockCount' => (int)$lowStock,
                'expiringSoonCount' => (int)$expiring
            ],
            'recentActivities' => $activities
        ]);
    } catch (Exception $e) {
        sendResponse(false, null, $e->getMessage());
    }
}
?>
