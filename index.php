<?php
session_start();
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaCare | Staff Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #059669;
            --primary-dark: #047857;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #0f172a;
            --text-light: #64748b;
            --border: #e2e8f0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #ffffff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text);
        }

        .welcome-container {
            text-align: center;
            padding: 2rem;
        }

        .logo {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .welcome-card {
            background: var(--card-bg);
            border-radius: 1.5rem;
            padding: 3rem 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: 0 auto;
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #064e3b;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            font-size: 1rem;
            color: var(--text-light);
            margin-bottom: 2rem;
        }

        .nfc-tap-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            padding: 2.5rem;
            background: rgba(5, 150, 105, 0.05);
            border-radius: 1.5rem;
            margin-bottom: 2rem;
            border: 3px dashed var(--primary);
            cursor: pointer;
            transition: all 0.3s;
        }

        .nfc-tap-area:hover {
            background: rgba(5, 150, 105, 0.1);
            transform: scale(1.02);
        }

        .nfc-icon {
            font-size: 4rem;
            color: var(--primary);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .nfc-text {
            text-align: center;
        }

        .nfc-text p {
            font-size: 1.1rem;
            color: var(--text-light);
            margin-bottom: 0.25rem;
        }

        .nfc-text strong {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.3rem;
        }

        .btn-manual {
            width: 100%;
            padding: 1rem;
            background: transparent;
            color: var(--text-light);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .btn-manual:hover {
            background: var(--bg);
            color: var(--text);
            border-color: var(--text-light);
        }

        /* NFC Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-out;
        }

        .modal.active {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: var(--card-bg);
            border-radius: 1.5rem;
            padding: 2.5rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
            animation: slideUp 0.3s ease-out;
            position: relative;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .close-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-light);
            cursor: pointer;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #064e3b;
            margin-bottom: 1.5rem;
        }

        .scanning-indicator {
            display: none;
            padding: 1rem;
            background: rgba(5, 150, 105, 0.1);
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .scanning-indicator.active {
            display: block;
        }

        .scanning-text {
            color: var(--primary);
            font-weight: 600;
        }

        .dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary);
            margin: 0 3px;
            animation: blink 1.4s infinite;
        }

        .dot:nth-child(2) { animation-delay: 0.2s; }
        .dot:nth-child(3) { animation-delay: 0.4s; }

        @keyframes blink {
            0%, 60%, 100% { opacity: 0.3; }
            30% { opacity: 1; }
        }

        .status-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }

        .success-message {
            display: none;
            color: #10b981;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .error-message {
            display: none;
            color: #ef4444;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        /* Login Form Modal */
        .login-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .login-modal.active {
            display: flex;
        }

        .login-modal-content {
            background: var(--card-bg);
            border-radius: 1.5rem;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            animation: slideUp 0.3s ease-out;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 1rem;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
        }

        .login-message {
            margin-bottom: 1rem;
            font-size: 0.9rem;
            text-align: center;
        }

        .login-message.error { color: #ef4444; }
        .login-message.success { color: #10b981; }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div class="welcome-card">
            <div class="logo">
                <i class="fas fa-prescription-bottle-medical"></i>
            </div>
            <h1 class="welcome-title">PharmaCare</h1>
            <p class="welcome-subtitle">Hospital Pharmacy Management System</p>

            <div class="nfc-tap-area" onclick="startNFCTap()">
                <div class="nfc-icon">
                    <i class="fas fa-wifi" style="transform: rotate(90deg);"></i>
                </div>
                <div class="nfc-text">
                    <p><strong>Tap</strong></p>
                    <p>Place your Staff Card here</p>
                </div>
            </div>

            <button class="btn-manual" onclick="openLoginModal()">
                <i class="fas fa-keyboard"></i> Use Login Details
            </button>
        </div>
    </div>

    <!-- NFC Tap Modal -->
    <div id="nfcModal" class="modal">
        <div class="modal-content">
            <button class="close-btn" onclick="closeNFCModal()">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="modal-title">NFC Authentication</h2>

            <div class="scanning-indicator" id="nfcScanning">
                <span class="scanning-text">
                    Reading Card
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </span>
            </div>

            <div class="status-icon" id="nfcStatusIcon">
                <i class="fas fa-wifi" style="transform: rotate(90deg);"></i>
            </div>

            <div class="success-message" id="nfcSuccess">✓ Card verified successfully</div>
            <div class="error-message" id="nfcError">Card read failed. Try again.</div>

            <p id="nfcInstruction" style="color: var(--text-light);">Hold your card near the reader</p>
        </div>
    </div>

    <!-- Login Form Modal -->
    <div id="loginModal" class="login-modal">
        <div class="login-modal-content">
            <button class="close-btn" onclick="closeLoginModal()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; color: var(--text-light); cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="modal-title">Staff Login</h2>

            <form id="loginForm" onsubmit="handleLogin(event)">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required placeholder="Enter your username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>

                <div class="login-message" id="loginMessage"></div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
        </div>
    </div>

    <script>
        function startNFCTap() {
            document.getElementById('nfcModal').classList.add('active');
            document.getElementById('nfcScanning').classList.add('active');
            document.getElementById('nfcSuccess').style.display = 'none';
            document.getElementById('nfcError').style.display = 'none';
            document.getElementById('nfcStatusIcon').innerHTML = '<i class="fas fa-wifi" style="transform: rotate(90deg);"></i>';
            document.getElementById('nfcInstruction').textContent = 'Reading staff card...';

            // Simulate NFC Tap (2 seconds)
            setTimeout(() => {
                const success = Math.random() > 0.1; // 90% success rate

                document.getElementById('nfcScanning').classList.remove('active');
                if (success) {
                    document.getElementById('nfcStatusIcon').innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i>';
                    document.getElementById('nfcSuccess').style.display = 'block';
                    document.getElementById('nfcInstruction').textContent = 'Authenticating...';
                    
                    // Simulate login with admin account
                    setTimeout(() => {
                        loginWithNFC('admin', 'admin123');
                    }, 1000);
                } else {
                    document.getElementById('nfcStatusIcon').innerHTML = '<i class="fas fa-times-circle" style="color: #ef4444;"></i>';
                    document.getElementById('nfcError').style.display = 'block';
                    document.getElementById('nfcInstruction').textContent = 'Please try tapping again.';
                }
            }, 2000);
        }

        function loginWithNFC(username, password) {
            fetch('api/auth.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    closeNFCModal();
                    alert('NFC Authentication failed: ' + data.error);
                }
            });
        }

        function closeNFCModal() {
            document.getElementById('nfcModal').classList.remove('active');
        }

        function openLoginModal() {
            document.getElementById('loginModal').classList.add('active');
            document.getElementById('username').focus();
        }

        function closeLoginModal() {
            document.getElementById('loginModal').classList.remove('active');
        }

        function handleLogin(event) {
            event.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const submitBtn = document.getElementById('submitBtn');
            const loginMessage = document.getElementById('loginMessage');

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';

            fetch('api/auth.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    loginMessage.textContent = '✓ Login successful! Redirecting...';
                    loginMessage.className = 'login-message success';
                    setTimeout(() => { window.location.href = 'dashboard.php'; }, 1000);
                } else {
                    loginMessage.textContent = data.error || 'Invalid credentials';
                    loginMessage.className = 'login-message error';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
                }
            })
            .catch(() => {
                loginMessage.textContent = 'Connection error';
                loginMessage.className = 'login-message error';
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
            });
        }

        // Close modals on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) closeNFCModal();
            if (event.target.classList.contains('login-modal')) closeLoginModal();
        }
    </script>
</body>
</html>
