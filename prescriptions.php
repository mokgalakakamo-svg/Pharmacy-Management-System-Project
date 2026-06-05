<?php
require_once 'api/db.php';
require_once 'layout.php';
session_start();
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }

// Check if user has permission to access prescriptions
if (!in_array($_SESSION['user']['role'], ['doctor'])) {
    header('Location: dashboard.php');
    exit;
}

renderHeader('Prescriptions', $_SESSION['user']);
?>
<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-2xl font-bold">Prescriptions</h1>
        <p class="text-muted-foreground">Manage medication orders and dispensing status</p>
    </div>
    <button class="btn btn-primary gap-2" onclick="openRxModal()">
        <i data-lucide="plus" class="w-4 h-4"></i>
        New Prescription
    </button>
</div>

<div class="card">
    <div class="card-content p-0 overflow-x-auto">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Diagnosis</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="rxTableBody">
                <tr><td colspan="6" class="text-center py-12 text-muted-foreground">Loading prescriptions...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- View Prescription Modal -->
<div id="viewRxModal" class="hidden modal-overlay">
    <div class="modal-content max-w-2xl">
        <div class="modal-header">
            <h3 class="text-xl font-bold">Prescription Details</h3>
            <button onclick="closeModal('viewRxModal')" class="text-muted-foreground hover:text-foreground p-1 rounded-md hover:bg-muted transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="modal-body" id="viewRxContent">
            <!-- Content injected by JS -->
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('viewRxModal')" class="btn btn-primary w-full">Close</button>
        </div>
    </div>
</div>

<!-- New Prescription Modal -->
<div id="rxModal" class="hidden modal-overlay">
    <div class="modal-content max-w-3xl">
        <div class="modal-header">
            <div>
                <h3 class="text-xl font-bold">Create New Prescription</h3>
                <p class="text-sm text-muted-foreground mt-1">Enter diagnosis and medication details for the patient</p>
            </div>
            <button onclick="closeModal('rxModal')" class="text-muted-foreground hover:text-foreground p-1 rounded-md hover:bg-muted transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="rxForm" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5">
                        <label>Patient <span class="text-destructive">*</span></label>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="patientSearchInput" 
                                placeholder="Search by patient number, name, or surname..." 
                                class="w-full px-3 py-2 border border-border rounded-md"
                                autocomplete="off"
                            >
                            <div id="patientDropdown" class="hidden absolute top-full left-0 right-0 bg-white border border-border rounded-md shadow-lg z-50 max-h-64 overflow-y-auto mt-1">
                                <!-- Dropdown options will be populated here -->
                            </div>
                        </div>
                        <input type="hidden" id="patientId" name="patientId" required>
                        <div id="selectedPatientDisplay" class="text-sm text-muted-foreground mt-2"></div>
                    </div>
                    <div class="space-y-1.5">
                        <label>Diagnosis <span class="text-destructive">*</span></label>
                        <input type="text" name="diagnosis" required placeholder="e.g. Bacterial Infection">
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h4 class="font-semibold text-sm uppercase tracking-wider text-muted-foreground">Medications</h4>
                        <button type="button" onclick="addRxItem()" class="btn btn-outline text-xs py-1 px-3 gap-1">
                            <i data-lucide="plus" class="w-3 h-3"></i> Add Item
                        </button>
                    </div>
                    <div id="rxItemsList" class="space-y-3"></div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('rxModal')" class="btn btn-outline">Cancel</button>
            <button type="submit" form="rxForm" class="btn btn-primary px-8">Create Prescription</button>
        </div>
    </div>
</div>

<style>
.patient-option {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
}

.patient-option:hover {
    background-color: #f5f5f5;
}

.patient-option.selected {
    background-color: #e8f5e9;
    border-left: 3px solid #66bb6a;
}

.patient-option-name {
    font-weight: 600;
    color: #222;
}

.patient-option-info {
    font-size: 0.875rem;
    color: #666;
    margin-top: 0.25rem;
}
</style>

<script>
let medications = [];
let allPatients = [];
let selectedPatient = null;

async function openRxModal() {
    openModal('rxModal');
    
    // Load medications
    const mRes = await fetch('api/api.php?module=medications&action=list');
    const mData = await mRes.json();
    medications = mData.data || [];
    
    // Load patients
    const pRes = await fetch('api/api.php?module=patients&action=list');
    const pData = await pRes.json();
    allPatients = pData.data || [];
    
    // Initialize search input
    const searchInput = document.getElementById('patientSearchInput');
    searchInput.value = '';
    document.getElementById('patientId').value = '';
    document.getElementById('selectedPatientDisplay').innerHTML = '';
    selectedPatient = null;
    
    // Populate dropdown with all patients initially
    updatePatientDropdown('');
    
    // Setup search input listener
    searchInput.addEventListener('input', (e) => {
        updatePatientDropdown(e.target.value);
    });
    
    // Setup dropdown close on outside click
    document.addEventListener('click', (e) => {
        if (e.target !== searchInput && !document.getElementById('patientDropdown').contains(e.target)) {
            document.getElementById('patientDropdown').classList.add('hidden');
        }
    });
    
    // Show dropdown on focus
    searchInput.addEventListener('focus', () => {
        document.getElementById('patientDropdown').classList.remove('hidden');
    });
    
    document.getElementById('rxItemsList').innerHTML = '';
    addRxItem();
}

function updatePatientDropdown(searchTerm) {
    const dropdown = document.getElementById('patientDropdown');
    const searchLower = searchTerm.toLowerCase();
    
    // Filter patients based on search term
    const filtered = allPatients.filter(p => {
        const cardMatch = p.card_number.toLowerCase().includes(searchLower);
        const firstNameMatch = p.first_name.toLowerCase().includes(searchLower);
        const lastNameMatch = p.last_name.toLowerCase().includes(searchLower);
        return cardMatch || firstNameMatch || lastNameMatch;
    });
    
    if (filtered.length === 0) {
        dropdown.innerHTML = '<div class="patient-option text-muted-foreground">No patients found</div>';
        return;
    }
    
    dropdown.innerHTML = filtered.map(p => `
        <div class="patient-option" onclick="selectPatient('${p.id}', '${p.card_number}', '${p.first_name}', '${p.last_name}')">
            <div class="patient-option-name">${p.first_name} ${p.last_name}</div>
            <div class="patient-option-info">Patient #: ${p.card_number}</div>
        </div>
    `).join('');
}

function selectPatient(id, cardNumber, firstName, lastName) {
    selectedPatient = { id, cardNumber, firstName, lastName };
    document.getElementById('patientId').value = id;
    document.getElementById('patientSearchInput').value = `${firstName} ${lastName} (${cardNumber})`;
    document.getElementById('selectedPatientDisplay').innerHTML = `<strong>Selected:</strong> ${firstName} ${lastName} - ${cardNumber}`;
    document.getElementById('patientDropdown').classList.add('hidden');
}

function addRxItem() {
    const div = document.createElement('div');
    div.className = 'grid grid-cols-12 gap-3 items-end p-4 bg-muted/20 rounded-lg border border-dashed';
    div.innerHTML = `
        <div class="col-span-12 md:col-span-4 space-y-1">
            <label class="text-xs font-medium">Medication</label>
            <select class="med-select">
                ${medications.map(m => `<option value="${m.id}">${m.name} (${m.strength})</option>`).join('')}
            </select>
        </div>
        <div class="col-span-12 md:col-span-3 space-y-1">
            <label class="text-xs font-medium">Dosage</label>
            <input type="text" class="dosage-input" placeholder="e.g. 500mg" required>
        </div>
        <div class="col-span-12 md:col-span-3 space-y-1">
            <label class="text-xs font-medium">Frequency</label>
            <input type="text" class="freq-input" placeholder="e.g. 3x daily" required>
        </div>
        <div class="col-span-10 md:col-span-1 space-y-1">
            <label class="text-xs font-medium">Qty</label>
            <input type="number" class="qty-input" value="1" min="1" required>
        </div>
        <div class="col-span-2 md:col-span-1 flex justify-end">
            <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-destructive p-2 hover:bg-destructive/10 rounded-md transition-colors">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
        </div>
    `;
    document.getElementById('rxItemsList').appendChild(div);
    lucide.createIcons();
}

async function loadRx() {
    const tbody = document.getElementById('rxTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-muted-foreground"><div class="flex items-center justify-center gap-2"><div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary"></div>Loading prescriptions...</div></td></tr>';
    
    try {
        const res = await fetch('api/api.php?module=prescriptions&action=list');
        const result = await res.json();
        if (result.success) {
            if (result.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-muted-foreground">No prescriptions found</td></tr>';
                return;
            }
            tbody.innerHTML = result.data.map(rx => `
                <tr>
                    <td class="text-xs font-bold">${rx.id}</td>
                    <td>${rx.patient_name}</td>
                    <td class="text-sm">${rx.doctor_name || 'N/A'}</td>
                    <td>${rx.diagnosis}</td>
                    <td><span class="badge ${rx.status === 'pending' ? 'bg-primary/10 text-primary' : 'bg-secondary text-secondary-foreground'}">${rx.status}</span></td>
                    <td class="text-xs">${new Date(rx.created_at).toLocaleDateString()}</td>
                    <td><button onclick='viewRx(${JSON.stringify(rx)})' class="btn btn-outline p-1.5"><i data-lucide="eye" class="w-4 h-4"></i></button></td>
                </tr>
            `).join('');
            lucide.createIcons();
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-12 text-destructive">Error: ${result.error || 'Failed to load prescriptions'}</td></tr>`;
        }
    } catch (err) {
        console.error(err);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-destructive">Network error. Please try again.</td></tr>';
    }
}

async function viewRx(rx) {
    const content = document.getElementById('viewRxContent');
    content.innerHTML = '<div class="flex justify-center py-8"><i data-lucide="loader-2" class="w-8 h-8 animate-spin text-primary"></i></div>';
    openModal('viewRxModal');
    lucide.createIcons();

    const res = await fetch(`api/api_prescriptions.php?module=prescriptions&action=get&id=${rx.id}`);
    const result = await res.json();
    
    if (result.success) {
        const data = result.data;
        content.innerHTML = `
            <div class="flex justify-between items-start mb-6">
                <div>
                    <p class="text-xs text-muted-foreground uppercase font-bold tracking-wider">Prescription ID</p>
                    <h4 class="text-xl font-bold text-primary">${data.id}</h4>
                </div>
                <div class="text-right">
                    <p class="text-xs text-muted-foreground uppercase font-bold tracking-wider">Status</p>
                    <span class="badge ${data.status === 'pending' ? 'bg-primary/10 text-primary' : 'bg-secondary text-secondary-foreground'}">${data.status}</span>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="p-4 bg-muted/30 rounded-xl">
                    <p class="text-xs text-muted-foreground uppercase font-bold mb-1">Patient</p>
                    <p class="font-bold">${data.patient_name}</p>
                    <p class="text-xs text-muted-foreground">${data.patient_card}</p>
                </div>
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl">
                    <p class="text-xs text-muted-foreground uppercase font-bold mb-1">Prescribed By</p>
                    <p class="font-bold">${data.doctor_name || 'N/A'}</p>
                </div>
                <div class="p-4 bg-muted/30 rounded-xl">
                    <p class="text-xs text-muted-foreground uppercase font-bold mb-1">Diagnosis</p>
                    <p class="font-bold">${data.diagnosis}</p>
                </div>
            </div>
            
            <h5 class="font-bold text-sm uppercase tracking-wider text-muted-foreground mb-3">Medication Items</h5>
            <div class="space-y-3">
                ${data.items.map(item => `
                    <div class="p-4 border rounded-xl flex justify-between items-center">
                        <div>
                            <p class="font-bold">${item.medication_name}</p>
                            <p class="text-sm text-muted-foreground">${item.dosage} • ${item.frequency}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold">${item.quantity} units</p>
                            <span class="text-xs ${item.status === 'dispensed' ? 'text-primary' : 'text-orange-500'} font-medium capitalize">${item.status}</span>
                        </div>
                    </div>
                `).join('')}
            </div>
            
            <div class="mt-8 pt-6 border-t text-xs text-muted-foreground flex justify-between">
                <p>Prescribed by: ${data.doctor_name}</p>
                <p>Date: ${new Date(data.created_at).toLocaleString()}</p>
            </div>
        `;
        lucide.createIcons();
    } else {
        content.innerHTML = `<div class="p-8 text-center text-destructive">Failed to load details: ${result.error}</div>`;
    }
}

document.getElementById('rxForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    if (!selectedPatient) {
        alert('Please select a patient');
        return;
    }
    
    const patientId = document.getElementById('patientId').value;
    const diagnosis = e.target.diagnosis.value;
    const items = Array.from(document.querySelectorAll('#rxItemsList > div')).map(div => ({
        medicationId: div.querySelector('.med-select').value,
        name: div.querySelector('.med-select').selectedOptions[0].text.split(' (')[0],
        dosage: div.querySelector('.dosage-input').value,
        frequency: div.querySelector('.freq-input').value,
        quantity: div.querySelector('.qty-input').value
    }));
    
    const res = await fetch('api/api.php?module=prescriptions&action=create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ patientId, diagnosis, items })
    });
    const result = await res.json();
    if (result.success) { closeModal('rxModal'); loadRx(); } else { alert(result.error); }
});

loadRx();

// Auto-open modal if action=new is in URL
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('action') === 'new') {
    openRxModal();
}
</script>
<?php renderFooter(); ?>
