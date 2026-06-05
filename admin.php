<?php
require_once 'api/db.php';
require_once 'layout.php';
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') { header('Location: dashboard.php'); exit; }
renderHeader('Admin Panel', $_SESSION['user']);
?>
<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-2xl font-bold">Administration</h1>
        <p class="text-muted-foreground">Manage users, roles, and system configuration</p>
    </div>
    <button class="btn btn-primary gap-2" onclick="openAddUserModal()">
        <i data-lucide="user-plus" class="w-4 h-4"></i> Add User
    </button>
</div>

<!-- Medication Logs Section -->
<div class="card mb-8">
    <div class="card-header border-b flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <h2 class="card-title">Medication Logs</h2>
        <div class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
            <div class="flex gap-2">
                <input type="date" id="startDate" class="flex-1 md:flex-none" />
                <span class="flex items-center text-muted-foreground">to</span>
                <input type="date" id="endDate" class="flex-1 md:flex-none" />
            </div>
            <button onclick="loadMedicationLogs()" class="btn btn-primary gap-2">
                <i data-lucide="filter" class="w-4 h-4"></i> Filter
            </button>
        </div>
    </div>
    <div class="card-content p-0 overflow-x-auto">
        <table>
            <thead>
                <tr><th>Date & Time</th><th>Medication</th><th>Dosage</th><th>Quantity</th><th>Patient</th><th>Dispensed By</th></tr>
            </thead>
            <tbody id="medicationLogsBody">
                <tr><td colspan="6" class="text-center py-12 text-muted-foreground">Loading medication logs...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- User Management Section -->
<div class="card">
    <div class="card-header border-b">
        <h2 class="card-title">User Management</h2>
    </div>
    <div class="card-content p-0 overflow-x-auto">
        <table>
            <thead>
                <tr><th>User</th><th>Role</th><th>Email</th><th>Status</th><th>Last Login</th><th>Actions</th></tr>
            </thead>
            <tbody id="userTableBody">
                <tr><td colspan="6" class="text-center py-12 text-muted-foreground">Loading users...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div id="userModal" class="hidden modal-overlay">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <div>
                <h3 class="text-xl font-bold" id="userModalTitle">Add New User</h3>
                <p class="text-sm text-muted-foreground mt-1">Create a new system user with specific permissions</p>
            </div>
            <button onclick="closeModal('userModal')" class="text-muted-foreground hover:text-foreground p-1 rounded-md hover:bg-muted transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="userForm" class="space-y-6">
                <input type="hidden" name="id" id="userId">
                <div class="space-y-1.5">
                    <label>Full Name</label>
                    <input type="text" name="fullName" id="userFullName" required placeholder="Enter full name">
                </div>
                <div class="space-y-1.5">
                    <label>Username</label>
                    <input type="text" name="username" id="userUsername" required placeholder="Enter username">
                </div>
                <div class="space-y-1.5">
                    <label id="passwordLabel">Password</label>
                    <input type="password" name="password" id="userPassword" placeholder="Enter password">
                </div>
                <div class="space-y-1.5">
                    <label>Email</label>
                    <input type="email" name="email" id="userEmail" required placeholder="Enter email address">
                </div>
                <div class="space-y-1.5">
                    <label>Role</label>
                    <select name="role" id="userRole">
                        <option value="admin">Administrator</option>
                        <option value="doctor">Doctor</option>
                        <option value="pharmacist">Pharmacist</option>
                        <option value="assistant">Assistant</option>
                        <option value="patient">Patient</option>
                    </select>
                </div>
                <div id="userFormError" class="hidden p-3 rounded-lg bg-destructive/10 text-destructive text-sm"></div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('userModal')" class="btn btn-outline">Cancel</button>
            <button type="submit" form="userForm" class="btn btn-primary px-8" id="userSubmitBtn">Create User</button>
        </div>
    </div>
</div>

<script>

// Initialize date inputs with default range (last 30 days)
function initializeDateInputs() {
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
    
    document.getElementById('startDate').valueAsDate = thirtyDaysAgo;
    document.getElementById('endDate').valueAsDate = today;
}

// Load medication logs with date filtering
async function loadMedicationLogs() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (!startDate || !endDate) {
        alert('Please select both start and end dates');
        return;
    }
    
    const tbody = document.getElementById('medicationLogsBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-muted-foreground">Loading medication logs...</td></tr>';
    
    try {
        const res = await fetch(`api/api.php?module=medication_logs&start_date=${startDate}&end_date=${endDate}`);
        const result = await res.json();
        
        if (result.success && result.data.length > 0) {
            tbody.innerHTML = result.data.map(log => {
                // Parse the details string to extract medication info
                const details = log.details;
                const medMatch = details.match(/Medication: ([^|]+)/);
                const dosageMatch = details.match(/Dosage: ([^|]+)/);
                const qtyMatch = details.match(/Qty: (\d+)/);
                const patientMatch = details.match(/Patient: ([^|]+)$/);
                
                const medication = medMatch ? medMatch[1].trim() : 'N/A';
                const dosage = dosageMatch ? dosageMatch[1].trim() : 'N/A';
                const quantity = qtyMatch ? qtyMatch[1] : 'N/A';
                const patient = patientMatch ? patientMatch[1].trim() : 'N/A';
                const dateTime = new Date(log.created_at).toLocaleString();
                
                return `
                    <tr>
                        <td class="text-xs whitespace-nowrap">${dateTime}</td>
                        <td><strong>${medication}</strong></td>
                        <td>${dosage}</td>
                        <td class="text-center">${quantity}</td>
                        <td>${patient}</td>
                        <td>${log.dispensed_by}</td>
                    </tr>
                `;
            }).join('');
            lucide.createIcons();
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-muted-foreground">No medication logs found for the selected date range</td></tr>';
        }
    } catch (err) {
        console.error('Error loading medication logs:', err);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-destructive">Error loading medication logs</td></tr>';
    }
}

function openAddUserModal() {
    document.getElementById('userModalTitle').textContent = 'Add New User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('userPassword').required = true;
    document.getElementById('userPassword').placeholder = 'Enter password';
    document.getElementById('userSubmitBtn').textContent = 'Create User';
    openModal('userModal');
}

function openEditUserModal(user) {
    document.getElementById('userModalTitle').textContent = 'Edit User';
    document.getElementById('userId').value = user.id;
    document.getElementById('userFullName').value = user.full_name;
    document.getElementById('userUsername').value = user.username;
    document.getElementById('userEmail').value = user.email;
    document.getElementById('userRole').value = user.role;
    document.getElementById('userPassword').value = '';
    document.getElementById('userPassword').required = false;
    document.getElementById('userPassword').placeholder = 'Leave blank to keep current password';
    document.getElementById('userSubmitBtn').textContent = 'Update User';
    openModal('userModal');
}

async function loadUsers() {
    const res = await fetch('api/api.php?module=users&action=list');
    const result = await res.json();
    const tbody = document.getElementById('userTableBody');
    if (result.success) {
        tbody.innerHTML = result.data.map(u => `
            <tr>
                <td><div class="font-bold">${u.full_name}</div><div class="text-xs text-muted-foreground">@${u.username}</div></td>
                <td><span class="badge badge-secondary">${u.role}</span></td>
                <td>${u.email}</td>
                <td><span class="badge ${u.is_active ? 'bg-primary/10 text-primary' : 'badge-secondary'}">${u.is_active ? 'Active' : 'Inactive'}</span></td>
                <td class="text-xs">${u.last_login ? new Date(u.last_login).toLocaleString() : 'Never'}</td>
                <td>
                    <div class="flex gap-1">
                        <button class="btn btn-outline p-1.5" onclick='openEditUserModal(${JSON.stringify(u)})'><i data-lucide="edit" class="w-4 h-4"></i></button>
                        <button class="btn btn-outline p-1.5 text-destructive hover:bg-destructive/10" onclick='deleteUser("${u.id}", "${u.username}")'><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
        lucide.createIcons();
    }
}

document.getElementById('userForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    const userId = data.id;
    const action = userId ? 'update' : 'create';
    
    const errorDiv = document.getElementById('userFormError');
    errorDiv.classList.add('hidden');
    
    const res = await fetch(`api/api.php?module=users&action=${action}`, {
        method: 'POST', 
        headers: { 'Content-Type': 'application/json' }, 
        body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.success) { 
        closeModal('userModal'); 
        loadUsers(); 
    } else {
        errorDiv.textContent = result.error || 'Failed to save user';
        errorDiv.classList.remove('hidden');
    }
});

async function deleteUser(id, username) {
    if (confirm(`Are you sure you want to delete user @${username}? This action cannot be undone.`)) {
        const res = await fetch('api/api.php?module=users&action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const result = await res.json();
        if (result.success) {
            loadUsers();
        } else {
            alert(result.error || 'Failed to delete user');
        }
    }
}

// Initialize the page
initializeDateInputs();
loadMedicationLogs();
loadUsers();
</script>
<?php renderFooter(); ?>
