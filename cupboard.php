<?php
require_once 'api/db.php';
require_once 'layout.php';
session_start();
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }
if (!in_array($_SESSION['user']['role'], ['pharmacist', 'assistant'])) {
    header('Location: dashboard.php');
    exit;
}
renderHeader('Cupboard Access', $_SESSION['user']);
?>
<div class="mb-8">
    <?php if (isset($_SESSION['cupboard_warning'])): ?>
        <div class="mb-6 p-4 bg-destructive/10 border-l-4 border-destructive text-destructive rounded-r-lg flex items-center gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            <p class="text-sm font-medium"><?php echo $_SESSION['cupboard_warning']; unset($_SESSION['cupboard_warning']); ?></p>
        </div>
    <?php endif; ?>
    <h1 class="text-2xl font-bold">Medication Cupboard</h1>
    <p class="text-muted-foreground">Secure access to controlled substances and high-value medications</p>
</div>

<div class="grid lg:grid-cols-7 gap-6">
    <div class="lg:col-span-3">
        <div class="card h-full">
            <div class="card-header border-b">
                <h2 class="card-title">Access Control</h2>
                <p class="card-description">Verify identity to unlock cupboard</p>
            </div>
            <div class="card-content p-6 flex flex-col items-center justify-center text-center space-y-6 min-h-[400px]" id="lockState">
                <div class="w-24 h-24 rounded-full bg-muted flex items-center justify-center animate-pulse">
                    <i data-lucide="lock" class="w-12 h-12 text-muted-foreground"></i>
                </div>
                <div class="space-y-2">
                    <h3 class="font-bold text-xl">Cupboard is Locked</h3>
                    <p class="text-sm text-muted-foreground">Please authenticate to gain access</p>
                </div>
                <div class="w-full space-y-3">
                    <button onclick="simulateAccess('Biometric')" class="btn btn-primary w-full py-6 flex flex-col gap-1">
                        <span class="flex items-center gap-2 font-bold"><i data-lucide="scan-face" class="w-5 h-5"></i> Authenticate with Face ID</span>
                        <span class="text-xs font-normal opacity-80">Biometric verification</span>
                    </button>
                    <button onclick="openPasswordModal()" class="btn btn-outline w-full py-3">
                        <i data-lucide="key" class="w-4 h-4 mr-2"></i> Use Security Password
                    </button>
                </div>
            </div>
            
            <!-- Password Verification Modal -->
            <div id="passwordModal" class="hidden modal-overlay">
                <div class="modal-content max-w-sm">
                    <div class="modal-header">
                        <h3 class="text-xl font-bold">Security Verification</h3>
                        <button onclick="closeModal('passwordModal')" class="text-muted-foreground hover:text-foreground">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <div class="modal-body py-4">
                        <p class="text-sm text-muted-foreground mb-4">Please enter your account password to unlock the medication cupboard.</p>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium">Password</label>
                            <input type="password" id="cupboardPassword" class="w-full" placeholder="••••••••">
                        </div>
                        <div id="passwordError" class="text-xs text-destructive mt-2 hidden">Invalid password. Please try again.</div>
                    </div>
                    <div class="modal-footer">
                        <button onclick="closeModal('passwordModal')" class="btn btn-outline">Cancel</button>
                        <button onclick="verifyPassword()" class="btn btn-primary" id="verifyBtn">Verify & Unlock</button>
                    </div>
                </div>
            </div>

            <div class="card-content p-6 flex flex-col items-center justify-center text-center space-y-6 min-h-[400px] hidden" id="unlockState">
                <div class="w-24 h-24 rounded-full bg-primary/10 flex items-center justify-center">
                    <i data-lucide="lock-open" class="w-12 h-12 text-primary"></i>
                </div>
                <div class="space-y-2">
                    <h3 class="font-bold text-xl text-primary">Cupboard Unlocked</h3>
                    <p class="text-sm text-muted-foreground">Access granted for the next 5 minutes</p>
                </div>
                <div class="w-full p-4 bg-muted/30 rounded-lg text-left">
                    <p class="text-xs font-bold uppercase text-muted-foreground mb-2">Controlled Medications</p>
                    <ul class="text-sm space-y-1">
                        <li>• Morphine Sulfate (10mg/ml)</li>
                        <li>• Fentanyl Patches (50mcg/hr)</li>
                        <li>• Oxycodone HCl (20mg)</li>
                    </ul>
                </div>
                <button onclick="relock()" class="btn btn-outline w-full py-3">Relock Cupboard</button>
            </div>
        </div>
    </div>
    
    <div class="lg:col-span-4">
        <div class="card h-full">
            <div class="card-header border-b flex justify-between items-center">
                <h2 class="card-title">Recent Access Logs</h2>
                <button onclick="loadCupboardLogs()" class="btn btn-outline p-2"><i data-lucide="refresh-cw" class="w-4 h-4"></i></button>
            </div>
            <div class="card-content p-0 overflow-x-auto">
                <table>
                    <thead>
                        <tr><th>User</th><th>Time</th><th>Method</th><th>Status</th></tr>
                    </thead>
                    <tbody id="cupboardLogsBody">
                        <tr><td colspan="4" class="text-center py-12 text-muted-foreground">Loading access logs...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function openPasswordModal() {
    document.getElementById('cupboardPassword').value = '';
    document.getElementById('passwordError').classList.add('hidden');
    openModal('passwordModal');
    setTimeout(() => document.getElementById('cupboardPassword').focus(), 100);
}

async function verifyPassword() {
    const password = document.getElementById('cupboardPassword').value;
    const btn = document.getElementById('verifyBtn');
    
    if (!password) return;
    
    btn.disabled = true;
    btn.textContent = 'Verifying...';
    
    const success = await simulateAccess('Security Password', password);
    
    btn.disabled = false;
    btn.textContent = 'Verify & Unlock';
    
    if (success) {
        closeModal('passwordModal');
    } else {
        document.getElementById('passwordError').classList.remove('hidden');
        document.getElementById('cupboardPassword').value = '';
        document.getElementById('cupboardPassword').focus();
    }
}

// Add enter key support for password field
document.getElementById('cupboardPassword')?.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') verifyPassword();
});

async function simulateAccess(method, password = null) {
    const payload = { method };
    if (password) payload.password = password;
    
    const res = await fetch('api/api.php?module=cupboard&action=access', {
        method: 'POST', 
        headers: { 'Content-Type': 'application/json' }, 
        body: JSON.stringify(payload)
    });
    
    const result = await res.json();
    if (result.success) {
        // Set session authorization via a small API call
        await fetch('api/api.php?module=cupboard&action=authorize', { method: 'POST' });
        
        document.getElementById('lockState').classList.add('hidden');
        document.getElementById('unlockState').classList.remove('hidden');
        loadCupboardLogs();
        setTimeout(relock, 300000); // Auto-relock after 5 mins
        return true;
    }
    return false;
}

async function relock() {
    await fetch('api/api.php?module=cupboard&action=deauthorize', { method: 'POST' });
    document.getElementById('unlockState').classList.add('hidden');
    document.getElementById('lockState').classList.remove('hidden');
}

async function loadCupboardLogs() {
    const res = await fetch('api/api.php?module=audit');
    const result = await res.json();
    if (result.success) {
        const logs = result.data.filter(l => l.action === 'CUPBOARD_ACCESS');
        const tbody = document.getElementById('cupboardLogsBody');
        if (logs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-12 text-muted-foreground">No recent access logs</td></tr>';
            return;
        }
        tbody.innerHTML = logs.map(l => `
            <tr>
                <td><div class="font-bold text-sm">${l.user_name}</div></td>
                <td class="text-xs">${new Date(l.created_at).toLocaleString()}</td>
                        <td><span class="badge badge-secondary">${l.details.split('via ')[1] || 'Unknown'}</span></td>
                <td><span class="badge bg-primary/10 text-primary border-primary/20">Granted</span></td>
            </tr>
        `).join('');
        lucide.createIcons();
    }
}
loadCupboardLogs();
</script>
<?php renderFooter(); ?>
