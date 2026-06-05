<?php
require_once 'api/db.php';
require_once 'layout.php';
session_start();
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }

// Check if user has permission to access patients - only admins can add patients
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

renderHeader('Patients', $_SESSION['user']);
?>
<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-2xl font-bold">Patient Management</h1>
        <p class="text-muted-foreground">View and manage hospital patient records</p>
    </div>
    <button class="btn btn-primary gap-2" onclick="openPatientModal()">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Add Patient
    </button>
</div>

<div class="card">
    <div class="card-header border-b flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <h2 class="card-title">Patient Records</h2>
        <div class="relative w-full md:w-64">
            <i data-lucide="search" class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground"></i>
            <input type="text" placeholder="Search patients..." class="pl-9" id="patientSearch">
        </div>
    </div>
    <div class="card-content p-0 overflow-x-auto">
        <table>
            <thead>
                <tr>
                    <th>Card Number</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Gender</th>
                    <th>Blood Type</th>
                    <th>Admission Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="patientTableBody">
                <tr><td colspan="7" class="text-center py-12 text-muted-foreground">Loading patients...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- View Patient Modal -->
<div id="viewPatientModal" class="hidden modal-overlay">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <h3 class="text-xl font-bold">Patient Details</h3>
            <button onclick="closeModal('viewPatientModal')" class="text-muted-foreground hover:text-foreground p-1 rounded-md hover:bg-muted transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="space-y-4" id="viewPatientContent">
                <!-- Content injected by JS -->
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('viewPatientModal')" class="btn btn-primary w-full">Close</button>
        </div>
    </div>
</div>

<!-- Patient Modal (Add/Edit) -->
<div id="patientModal" class="hidden modal-overlay">
    <div class="modal-content max-w-2xl">
        <div class="modal-header">
            <div>
                <h3 class="text-xl font-bold" id="modalTitle">Admit New Patient</h3>
                <p class="text-sm text-muted-foreground mt-1">Enter patient information to create a new admission record</p>
            </div>
            <button onclick="closeModal('patientModal')" class="text-muted-foreground hover:text-foreground p-1 rounded-md hover:bg-muted transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="patientForm" class="space-y-6">
                <input type="hidden" name="id" id="patientId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5">
                        <label>Card Number</label>
                        <input type="text" name="cardNumber" id="cNum" required placeholder="P-2026-000">
                    </div>
                    <div class="space-y-1.5">
                        <label>Status</label>
                        <select name="status" id="status">
                            <option value="admitted">Admitted</option>
                            <option value="discharged">Discharged</option>
                            <option value="outpatient">Outpatient</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5">
                        <label>First Name</label>
                        <input type="text" name="firstName" id="fName" required placeholder="Enter first name">
                    </div>
                    <div class="space-y-1.5">
                        <label>Last Name</label>
                        <input type="text" name="lastName" id="lName" required placeholder="Enter last name">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5">
                        <label>Date of Birth</label>
                        <input type="date" name="dateOfBirth" id="dob" required>
                    </div>
                    <div class="space-y-1.5">
                        <label>Gender</label>
                        <select name="gender" id="gender">
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5">
                        <label>Phone Number</label>
                        <input type="text" name="phoneNumber" id="phone" placeholder="Enter phone number">
                    </div>
                    <div class="space-y-1.5">
                        <label>Email (Optional)</label>
                        <input type="email" name="email" id="email" placeholder="Enter email address">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label>Address</label>
                    <textarea name="address" id="addr" rows="3" placeholder="Enter residential address"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5">
                        <label>Blood Type</label>
                        <select name="bloodType" id="bType">
                            <option value="A+">A+</option><option value="A-">A-</option>
                            <option value="B+">B+</option><option value="B-">B-</option>
                            <option value="AB+">AB+</option><option value="AB-">AB-</option>
                            <option value="O+">O+</option><option value="O-">O-</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('patientModal')" class="btn btn-outline">Cancel</button>
            <button type="submit" form="patientForm" class="btn btn-primary px-8" id="saveBtn">Save Patient</button>
        </div>
    </div>
</div>

<script>
function openPatientModal(data = null) {
    openModal('patientModal');
    document.getElementById('patientForm').reset();
    if (data) {
        document.getElementById('modalTitle').textContent = 'Edit Patient';
        document.getElementById('patientId').value = data.id;
        document.getElementById('fName').value = data.first_name;
        document.getElementById('lName').value = data.last_name;
        document.getElementById('cNum').value = data.card_number;
        document.getElementById('dob').value = data.date_of_birth;
        document.getElementById('gender').value = data.gender;
        document.getElementById('bType').value = data.blood_type;
        document.getElementById('phone').value = data.phone_number;
        document.getElementById('email').value = data.email;
        document.getElementById('status').value = data.status;
        document.getElementById('addr').value = data.address;
        document.getElementById('saveBtn').textContent = 'Update Patient';
    } else {
        document.getElementById('modalTitle').textContent = 'Admit New Patient';
        document.getElementById('patientId').value = '';
        document.getElementById('cNum').value = 'P-2026-' + Math.floor(100 + Math.random() * 900);
        document.getElementById('saveBtn').textContent = 'Save Patient';
    }
}

async function loadPatients() {
    const res = await fetch('api/api.php?module=patients&action=list');
    const result = await res.json();
    const tbody = document.getElementById('patientTableBody');
    if (result.success) {
        tbody.innerHTML = result.data.map(p => `
            <tr>
                <td class="font-bold text-primary">${p.card_number}</td>
                <td><div class="font-medium">${p.first_name} ${p.last_name}</div><div class="text-xs text-muted-foreground">${p.email || 'No email'}</div></td>
                <td><span class="badge ${p.status === 'admitted' ? 'bg-primary/10 text-primary' : 'badge-secondary'}">${p.status}</span></td>
                <td>${p.gender}</td>
                <td><span class="badge badge-outline">${p.blood_type || '-'}</span></td>
                <td class="text-xs">${new Date(p.admission_date).toLocaleDateString()}</td>
                <td>
                    <div class="flex gap-1">
                        <button onclick='viewPatient(${JSON.stringify(p)})' class="btn btn-outline p-1.5"><i data-lucide="eye" class="w-4 h-4"></i></button>
                        <button onclick='openPatientModal(${JSON.stringify(p)})' class="btn btn-outline p-1.5"><i data-lucide="edit" class="w-4 h-4"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
        lucide.createIcons();
    }
}

function viewPatient(p) {
    const content = document.getElementById('viewPatientContent');
    content.innerHTML = `
        <div class="flex flex-col items-center mb-6">
            <div class="w-20 h-20 rounded-full bg-primary/10 flex items-center justify-center mb-3">
                <i data-lucide="user" class="w-10 h-10 text-primary"></i>
            </div>
            <h4 class="text-xl font-bold">${p.first_name} ${p.last_name}</h4>
            <span class="badge ${p.status === 'admitted' ? 'bg-primary/10 text-primary' : 'badge-secondary'} mt-1">${p.status}</span>
        </div>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="p-3 bg-muted/30 rounded-lg">
                <p class="text-muted-foreground text-xs uppercase font-semibold">Card Number</p>
                <p class="font-bold mt-1">${p.card_number}</p>
            </div>
            <div class="p-3 bg-muted/30 rounded-lg">
                <p class="text-muted-foreground text-xs uppercase font-semibold">Blood Type</p>
                <p class="font-bold mt-1">${p.blood_type || '-'}</p>
            </div>
            <div class="p-3 bg-muted/30 rounded-lg">
                <p class="text-muted-foreground text-xs uppercase font-semibold">Gender</p>
                <p class="font-bold mt-1 capitalize">${p.gender}</p>
            </div>
            <div class="p-3 bg-muted/30 rounded-lg">
                <p class="text-muted-foreground text-xs uppercase font-semibold">Date of Birth</p>
                <p class="font-bold mt-1">${p.date_of_birth}</p>
            </div>
            <div class="col-span-2 p-3 bg-muted/30 rounded-lg">
                <p class="text-muted-foreground text-xs uppercase font-semibold">Contact Info</p>
                <p class="font-bold mt-1">${p.phone_number || 'No phone'}</p>
                <p class="text-xs text-muted-foreground mt-0.5">${p.email || 'No email'}</p>
            </div>
            <div class="col-span-2 p-3 bg-muted/30 rounded-lg">
                <p class="text-muted-foreground text-xs uppercase font-semibold">Address</p>
                <p class="mt-1">${p.address || 'No address recorded'}</p>
            </div>
        </div>
    `;
    openModal('viewPatientModal');
    lucide.createIcons();
}

document.getElementById('patientForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    const action = data.id ? 'update' : 'create';
    const res = await fetch(`api/api.php?module=patients&action=${action}`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
    });
    if ((await res.json()).success) { closeModal('patientModal'); loadPatients(); }
});

document.getElementById('patientSearch').addEventListener('input', (e) => {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('#patientTableBody tr').forEach(row => row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none');
});

loadPatients();

// Auto-open modal if action=new is in URL
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('action') === 'new') {
    openPatientModal();
}
</script>
<?php renderFooter(); ?>
