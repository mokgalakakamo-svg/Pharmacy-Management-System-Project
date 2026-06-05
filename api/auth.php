<?php
require_once 'db.php';
session_start();

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'login') {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            sendResponse(false, null, 'Username and password are required');
        }

        try {
            $stmt = $pdo->prepare("SELECT id, username, full_name, role, email, is_active FROM users WHERE username = ? AND password = ?");
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch();

            if ($user) {
                if (!$user['is_active']) {
                    sendResponse(false, null, 'Account is deactivated. Contact administrator.');
                }

                $_SESSION['user'] = $user;
                $_SESSION['expires_at'] = time() + (8 * 3600); // 8 hours session

                // Update last login
                $upd = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $upd->execute([$user['id']]);

                // Audit log
                addAuditLog($pdo, $user['id'], 'LOGIN', 'user', $user['id'], "User {$user['username']} logged in successfully");

                sendResponse(true, ['user' => $user]);
            } else {
                sendResponse(false, null, 'Invalid username or password');
            }
        } catch (Exception $e) {
            sendResponse(false, null, 'Server error: ' . $e->getMessage());
        }
    }
}

if ($action === 'logout') {
    if (isset($_SESSION['user'])) {
        addAuditLog($pdo, $_SESSION['user']['id'], 'LOGOUT', 'user', $_SESSION['user']['id'], "User logged out");
    }
    session_destroy();
    header('Location: ../index.php');
    exit;
}

if ($action === 'check') {
    if (isset($_SESSION['user']) && time() < $_SESSION['expires_at']) {
        sendResponse(true, ['user' => $_SESSION['user']]);
    } else {
        sendResponse(false, null, 'Not authenticated');
    }
}
?>
