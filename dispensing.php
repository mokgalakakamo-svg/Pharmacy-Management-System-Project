<?php
require_once 'api/db.php';
require_once 'layout.php';
session_start();
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }

// Force Cupboard authorization check
$isAuthorized = isset($_SESSION['cupboard_authorized']) && $_SESSION['cupboard_authorized'] === true;
$isTimedOut = isset($_SESSION['cupboard_auth_time']) && (time() - $_SESSION['cupboard_auth_time'] > 300);

if (!$isAuthorized || $isTimedOut) {
    if ($isTimedOut) {
        unset($_SESSION['cupboard_authorized']);
        unset($_SESSION['cupboard_auth_time']);
    }
    // Redirect to cupboard with a warning
    $_SESSION['cupboard_warning'] = "Access Denied: You must unlock the Medication Cupboard before you can access the Dispensing module.";
    header('Location: cupboard.php');
    exit;
}

renderHeader('Dispensing', $_SESSION['user']);
?>
<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-2xl font-bold">Medication Dispensing</h1>
        <p class="text-muted-foreground">Process prescriptions and dispense medications to patients</p>
    </div>
</div>

<div class="card">
    <div class="card-header border-b flex justify-between items-center">
        <h2 class="card-title">Pending Prescriptions</h2>
        <button onclick="loadPendingRx()" class="btn btn-outline p-2"><i data-lucide="refresh-cw" class="w-4 h-4"></i></button>
    </div>
    <div class="card-content p-0 overflow-x-auto">
        <table id="pendingRxTable">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Diagnosis</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="5" class="text-center py-12 text-muted-foreground">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Dispensing Modal -->
<div id="dispensingModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-background rounded-xl shadow-lg w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-xl font-bold" id="dispensingModalTitle">Dispensing Panel</h3>
            <button onclick="closeModal('dispensingModal')" class="text-muted-foreground hover:text-foreground"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div id="dispensingContent" class="p-6 space-y-6">
            <div class="flex flex-col items-center justify-center text-center py-12 space-y-4">
                <div class="w-16 h-16 rounded-full bg-muted flex items-center justify-center">
                    <i data-lucide="syringe" class="w-8 h-8 text-muted-foreground"></i>
                </div>
                <div>
                    <h3 class="font-semibold">No Prescription Selected</h3>
                    <p class="text-sm text-muted-foreground max-w-[200px] mx-auto mt-1">Select a prescription from the list to start dispensing</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enquiry Modal -->
<div id="enquiryModal" class="hidden modal-overlay">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <h3 class="text-xl font-bold">Send Enquiry to Doctor</h3>
            <button onclick="closeModal('enquiryModal')" class="text-muted-foreground hover:text-foreground p-1 rounded-md hover:bg-muted transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="enquiryForm" class="space-y-4">
                <input type="hidden" id="enquiryRxId">
                <div class="space-y-1.5">
                    <label>Message</label>
                    <textarea id="enquiryMessage" required placeholder="Ask the doctor about this prescription..." rows="4" class="w-full"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('enquiryModal')" class="btn btn-outline">Cancel</button>
            <button type="submit" form="enquiryForm" class="btn btn-primary">Send Enquiry</button>
        </div>
    </div>
</div>

<script>
let currentRxId = null;

async function loadPendingRx() {
    const tbody = document.querySelector('#pendingRxTable tbody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-12 text-muted-foreground"><div class="flex items-center justify-center gap-2"><div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary"></div>Loading...</div></td></tr>';
    
    try {
        const res = await fetch('api/api.php?module=prescriptions&action=list');
        const result = await res.json();
        if (result.success) {
            const pending = result.data.filter(rx => rx.status === 'pending');
            if (pending.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-12 text-muted-foreground">No pending prescriptions</td></tr>';
                return;
            }
            tbody.innerHTML = pending.map(rx => `
                <tr>
                    <td><div class="font-medium">${rx.patient_name}</div></td>
                    <td><div class="text-sm">${rx.doctor_name || 'N/A'}</div></td>
                    <td><div class="text-xs truncate max-w-[150px]">${rx.diagnosis}</div></td>
                    <td class="text-xs">${new Date(rx.created_at).toLocaleDateString()}</td>
                    <td><button onclick="selectRx('${rx.id}')" class="btn btn-primary text-xs py-1 px-3">Dispense</button></td>
                </tr>
            `).join('');
            lucide.createIcons();
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-12 text-destructive">Error: ${result.error || 'Failed to load prescriptions'}</td></tr>`;
        }
    } catch (err) {
        console.error(err);
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-12 text-destructive">Network error. Please try again.</td></tr>';
    }
}

async function selectRx(id) {
    currentRxId = id;
    openModal('dispensingModal');
    const content = document.getElementById('dispensingContent');
    content.innerHTML = '<div class="flex justify-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div></div>';
    
    try {
        const res = await fetch(`api/api.php?module=prescriptions&action=get&id=${id}`);
        const result = await res.json();
        if (result.success) {
            const rx = result.data;
            document.getElementById('dispensingModalTitle').textContent = `Dispensing - ${rx.patient_name}`;
            
            // Fetch doctor information
            const doctorName = rx.doctor_name || 'Unknown Doctor';
            
            content.innerHTML = `
                <div class="space-y-4">
                    <!-- Patient & Doctor Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pb-4 border-b">
                        <div>
                            <p class="text-xs text-muted-foreground uppercase font-semibold">Patient</p>
                            <h3 class="font-bold text-lg mt-1">${rx.patient_name}</h3>
                            <p class="text-sm text-muted-foreground">Diagnosis: ${rx.diagnosis}</p>
                        </div>
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <p class="text-xs text-muted-foreground uppercase font-semibold">Prescribed By</p>
                            <h3 class="font-bold text-lg mt-1">${doctorName}</h3>
                            <p class="text-xs text-muted-foreground mt-2">Prescription ID: ${rx.id}</p>
                        </div>
                    </div>
                    
                    <!-- Medications to Dispense -->
                    <div class="space-y-3">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Medications to Dispense</p>
                        ${rx.items.map(item => `
                            <div class="p-4 border rounded-lg bg-muted/20 space-y-3">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-bold">${item.medication_name}</div>
                                        <div class="text-xs text-muted-foreground">${item.dosage} - ${item.frequency}</div>
                                    </div>
                                    <span class="badge ${item.status === 'dispensed' ? 'bg-primary/10 text-primary' : 'badge-secondary'}">${item.status}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="text-sm">Qty: <span class="font-bold">${item.quantity}</span></div>
                                    ${item.status === 'pending' ? 
                                        `<button onclick="dispenseItem('${item.id}', '${rx.id}')" class="btn btn-primary text-xs py-1.5 px-4">Dispense</button>` : 
                                        `<i data-lucide="check-circle" class="text-primary w-5 h-5"></i>`}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="pt-4 border-t flex justify-end gap-2">
                        <button onclick="openEnquiryModal('${rx.id}')" class="btn btn-outline gap-2">
                            <i data-lucide="message-circle" class="w-4 h-4"></i>
                            Enquiry
                        </button>
                        <button onclick="closeModal('dispensingModal')" class="btn btn-outline">Close</button>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }
    } catch (err) {
        console.error(err);
        content.innerHTML = '<div class="text-center text-destructive">Error loading prescription details</div>';
    }
}

async function dispenseItem(itemId, rxId) {
    if (!confirm('Are you sure you want to dispense this medication?')) return;
    const res = await fetch('api/api.php?module=dispense', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ itemId })
    });
    const result = await res.json();
    if (result.success) {
        selectRx(rxId);
        loadPendingRx();
    } else {
        if (result.error.includes('Cupboard access required')) {
            if (confirm(result.error + '\n\nWould you like to go to the Cupboard module now?')) {
                window.location.href = 'cupboard.php';
            }
        } else {
            alert(result.error);
        }
    }
}

function openEnquiryModal(rxId) {
    document.getElementById('enquiryRxId').value = rxId;
    document.getElementById('enquiryMessage').value = '';
    openModal('enquiryModal');
}

document.getElementById('enquiryForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const prescriptionId = document.getElementById('enquiryRxId').value;
    const message = document.getElementById('enquiryMessage').value;
    
    const res = await fetch('api/api.php?module=enquiries&action=create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ prescriptionId, message })
    });
    
    const result = await res.json();
    if (result.success) {
        alert('Enquiry sent successfully!');
        closeModal('enquiryModal');
    } else {
        alert('Error sending enquiry: ' + result.error);
    }
});

loadPendingRx();
</script>
<?php renderFooter(); ?>
