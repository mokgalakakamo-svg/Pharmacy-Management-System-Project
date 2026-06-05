<?php
require_once 'api/db.php';
require_once 'layout.php';
session_start();
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }

// Only patients can access this page
if ($_SESSION['user']['role'] !== 'patient') {
    header('Location: dashboard.php');
    exit;
}

renderHeader('My Health Records', $_SESSION['user']);
?>
<div class="mb-8">
    <h1 class="text-2xl font-bold">My Health Records</h1>
    <p class="text-muted-foreground">View your personal information and prescriptions</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Patient Info Card -->
    <div class="card md:col-span-1">
        <div class="card-header border-b">
            <h2 class="card-title">Personal Information</h2>
        </div>
        <div class="card-content space-y-4">
            <div id="patientInfo">
                <div class="text-center py-8 text-muted-foreground">Loading...</div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:col-span-2">
        <div class="card">
            <div class="card-content p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">Pending Prescriptions</p>
                        <p class="text-3xl font-bold mt-2" id="pendingCount">0</p>
                    </div>
                    <i data-lucide="clipboard-list" class="w-12 h-12 text-primary/20"></i>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-content p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">Dispensed Prescriptions</p>
                        <p class="text-3xl font-bold mt-2" id="dispensedCount">0</p>
                    </div>
                    <i data-lucide="check-circle" class="w-12 h-12 text-green-500/20"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Prescriptions Table -->
<div class="card">
    <div class="card-header border-b">
        <h2 class="card-title">My Prescriptions</h2>
    </div>
    <div class="card-content p-0 overflow-x-auto">
        <table>
            <thead>
                <tr>
                    <th>Prescription ID</th>
                    <th>Diagnosis</th>
                    <th>Status</th>
                    <th>Issued Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="prescriptionTableBody">
                <tr><td colspan="5" class="text-center py-12 text-muted-foreground">Loading prescriptions...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Prescription Detail Modal -->
<div id="prescriptionModal" class="hidden modal-overlay">
    <div class="modal-content max-w-2xl">
        <div class="modal-header">
            <h3 class="text-xl font-bold">Prescription Details</h3>
            <button onclick="closeModal('prescriptionModal')" class="text-muted-foreground hover:text-foreground p-1 rounded-md hover:bg-muted transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="space-y-4" id="prescriptionContent">
                <!-- Content injected by JS -->
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('prescriptionModal')" class="btn btn-primary w-full">Close</button>
        </div>
    </div>
</div>

<script>
async function loadPatientInfo() {
    try {
        const res = await fetch('api/api.php?module=patients&action=list');
        const result = await res.json();
        if (result.success && result.data && result.data.length > 0) {
            const patient = result.data[0];
            const info = document.getElementById('patientInfo');
            info.innerHTML = `
                <div class="flex flex-col items-center mb-6">
                    <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mb-3">
                        <i data-lucide="user" class="w-8 h-8 text-primary"></i>
                    </div>
                    <h4 class="text-lg font-bold">${patient.first_name} ${patient.last_name}</h4>
                </div>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Card Number:</span>
                        <span class="font-semibold">${patient.card_number}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Date of Birth:</span>
                        <span class="font-semibold">${patient.date_of_birth}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Gender:</span>
                        <span class="font-semibold capitalize">${patient.gender}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Blood Type:</span>
                        <span class="font-semibold">${patient.blood_type || '-'}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Status:</span>
                        <span class="badge ${patient.status === 'admitted' ? 'bg-primary/10 text-primary' : 'badge-secondary'}">${patient.status}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Phone:</span>
                        <span class="font-semibold">${patient.phone_number || '-'}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Email:</span>
                        <span class="font-semibold text-xs">${patient.email || '-'}</span>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }
    } catch (error) {
        console.error('Error loading patient info:', error);
    }
}

async function loadPrescriptions() {
    try {
        const res = await fetch('api/api.php?module=prescriptions&action=list');
        const result = await res.json();
        const tbody = document.getElementById('prescriptionTableBody');
        
        if (result.success && result.data && result.data.length > 0) {
            let pendingCount = 0;
            let dispensedCount = 0;
            
            tbody.innerHTML = result.data.map(p => {
                if (p.status === 'pending') pendingCount++;
                if (p.status === 'dispensed') dispensedCount++;
                
                return `
                    <tr>
                        <td class="font-bold text-primary">${p.id}</td>
                        <td>${p.diagnosis || 'N/A'}</td>
                        <td><span class="badge ${p.status === 'pending' ? 'bg-yellow-500/10 text-yellow-700' : 'bg-green-500/10 text-green-700'}">${p.status}</span></td>
                        <td class="text-xs">${new Date(p.created_at).toLocaleDateString()}</td>
                        <td>
                            <button onclick='viewPrescription(${JSON.stringify(p)})' class="btn btn-outline p-1.5"><i data-lucide="eye" class="w-4 h-4"></i></button>
                        </td>
                    </tr>
                `;
            }).join('');
            
            document.getElementById('pendingCount').textContent = pendingCount;
            document.getElementById('dispensedCount').textContent = dispensedCount;
            lucide.createIcons();
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-12 text-muted-foreground">No prescriptions found</td></tr>';
        }
    } catch (error) {
        console.error('Error loading prescriptions:', error);
    }
}

function viewPrescription(rx) {
    const content = document.getElementById('prescriptionContent');
    const itemsHtml = rx.items ? rx.items.map(item => `
        <div class="p-3 bg-muted/30 rounded-lg">
            <p class="font-semibold">${item.medication_name}</p>
            <div class="grid grid-cols-2 gap-2 text-sm mt-2">
                <div><span class="text-muted-foreground">Dosage:</span> <span class="font-semibold">${item.dosage}</span></div>
                <div><span class="text-muted-foreground">Frequency:</span> <span class="font-semibold">${item.frequency}</span></div>
                <div><span class="text-muted-foreground">Quantity:</span> <span class="font-semibold">${item.quantity}</span></div>
                <div><span class="text-muted-foreground">Status:</span> <span class="badge ${item.status === 'pending' ? 'bg-yellow-500/10 text-yellow-700' : 'bg-green-500/10 text-green-700'}">${item.status}</span></div>
            </div>
        </div>
    `).join('') : '';
    
    content.innerHTML = `
        <div class="space-y-4">
            <div class="p-4 bg-primary/10 rounded-lg">
                <p class="text-sm text-muted-foreground">Prescription ID</p>
                <p class="font-bold text-lg">${rx.id}</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-3 bg-muted/30 rounded-lg">
                    <p class="text-muted-foreground text-xs uppercase font-semibold">Diagnosis</p>
                    <p class="font-semibold mt-1">${rx.diagnosis || 'N/A'}</p>
                </div>
                <div class="p-3 bg-muted/30 rounded-lg">
                    <p class="text-muted-foreground text-xs uppercase font-semibold">Status</p>
                    <p class="font-semibold mt-1"><span class="badge ${rx.status === 'pending' ? 'bg-yellow-500/10 text-yellow-700' : 'bg-green-500/10 text-green-700'}">${rx.status}</span></p>
                </div>
            </div>
            <div class="p-3 bg-muted/30 rounded-lg">
                <p class="text-muted-foreground text-xs uppercase font-semibold">Issued Date</p>
                <p class="font-semibold mt-1">${new Date(rx.created_at).toLocaleDateString()}</p>
            </div>
            <div>
                <p class="text-muted-foreground text-xs uppercase font-semibold mb-3">Medications</p>
                <div class="space-y-2">${itemsHtml}</div>
            </div>
        </div>
    `;
    openModal('prescriptionModal');
    lucide.createIcons();
}

// Load data on page load
loadPatientInfo();
loadPrescriptions();
</script>
<?php renderFooter(); ?>
