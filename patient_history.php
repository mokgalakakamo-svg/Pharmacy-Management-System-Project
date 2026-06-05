<?php
require_once 'api/db.php';
require_once 'layout.php';
session_start();
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }

// Only admins can access patient history
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

renderHeader('Patient History', $_SESSION['user']);
?>
<div class="mb-8">
    <h1 class="text-2xl font-bold">Patient History Search</h1>
    <p class="text-muted-foreground">Search and view complete patient medical records</p>
</div>

<!-- Search Section -->
<div class="card mb-8">
    <div class="card-header border-b">
        <h2 class="card-title">Search Patient</h2>
    </div>
    <div class="card-content p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="space-y-1.5">
                <label>Card Number</label>
                <input type="text" id="searchCardNumber" placeholder="e.g., PAT-001" class="w-full">
            </div>
            <div class="space-y-1.5">
                <label>Patient Name</label>
                <input type="text" id="searchName" placeholder="First or Last Name" class="w-full">
            </div>
            <div class="space-y-1.5">
                <label>Email</label>
                <input type="email" id="searchEmail" placeholder="Email address" class="w-full">
            </div>
        </div>
        <div class="mt-4 flex gap-2">
            <button onclick="searchPatients()" class="btn btn-primary gap-2">
                <i data-lucide="search" class="w-4 h-4"></i>
                Search
            </button>
            <button onclick="clearSearch()" class="btn btn-outline">Clear</button>
        </div>
    </div>
</div>

<!-- Search Results -->
<div class="card mb-8">
    <div class="card-header border-b">
        <h2 class="card-title">Search Results</h2>
    </div>
    <div class="card-content p-0 overflow-x-auto">
        <table id="searchResultsTable">
            <thead>
                <tr>
                    <th>Card Number</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Admission Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="6" class="text-center py-8 text-muted-foreground">Enter search criteria and click Search</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Patient Detail Modal -->
<div id="patientDetailModal" class="hidden modal-overlay">
    <div class="modal-content max-w-4xl">
        <div class="modal-header">
            <h3 class="text-xl font-bold">Patient Medical History</h3>
            <button onclick="closeModal('patientDetailModal')" class="text-muted-foreground hover:text-foreground p-1 rounded-md hover:bg-muted transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="space-y-6" id="patientDetailContent">
                <!-- Content injected by JS -->
            </div>
        </div>
        <div class="modal-footer flex gap-2">
            <button type="button" onclick="downloadCSV()" class="btn btn-outline gap-2">
                <i data-lucide="download" class="w-4 h-4"></i>
                Download CSV
            </button>
            <button type="button" onclick="printHistory()" class="btn btn-outline gap-2">
                <i data-lucide="printer" class="w-4 h-4"></i>
                Print
            </button>
            <button type="button" onclick="closeModal('patientDetailModal')" class="btn btn-primary px-8">Close</button>
        </div>
    </div>
</div>

<script>
async function searchPatients() {
    const cardNumber = document.getElementById('searchCardNumber').value.trim();
    const name = document.getElementById('searchName').value.trim();
    const email = document.getElementById('searchEmail').value.trim();
    
    if (!cardNumber && !name && !email) {
        alert('Please enter at least one search criterion');
        return;
    }
    
    const tbody = document.querySelector('#searchResultsTable tbody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8"><div class="flex items-center justify-center gap-2"><div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary"></div>Searching...</div></td></tr>';
    
    try {
        const params = new URLSearchParams();
        if (cardNumber) params.append('card_number', cardNumber);
        if (name) params.append('name', name);
        if (email) params.append('email', email);
        
        const res = await fetch(`api/api.php?module=patient_search&${params.toString()}`);
        const result = await res.json();
        
        if (result.success && result.data && result.data.length > 0) {
            tbody.innerHTML = result.data.map(p => `
                <tr>
                    <td class="font-bold text-primary">${p.card_number}</td>
                    <td><div class="font-medium">${p.first_name} ${p.last_name}</div></td>
                    <td class="text-sm">${p.email || '-'}</td>
                    <td><span class="badge ${p.status === 'admitted' ? 'bg-primary/10 text-primary' : 'badge-secondary'}">${p.status}</span></td>
                    <td class="text-xs">${new Date(p.admission_date).toLocaleDateString()}</td>
                    <td>
                        <button onclick="viewPatientHistory('${p.id}')" class="btn btn-primary text-xs py-1 px-3">View History</button>
                    </td>
                </tr>
            `).join('');
            lucide.createIcons();
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-muted-foreground">No patients found</td></tr>';
        }
    } catch (error) {
        console.error(error);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-destructive">Error searching patients</td></tr>';
    }
}

async function viewPatientHistory(patientId) {
    openModal('patientDetailModal');
    const content = document.getElementById('patientDetailContent');
    content.innerHTML = '<div class="flex justify-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div></div>';
    
    try {
        // Fetch patient details
        const patRes = await fetch(`api/api.php?module=patients&action=get&id=${patientId}`);
        const patResult = await patRes.json();
        
        if (!patResult.success) {
            content.innerHTML = '<div class="text-destructive">Error loading patient details</div>';
            return;
        }
        
        const patient = patResult.data;
        currentPatient = patient;
        
        // Fetch prescriptions and history
        const rxRes = await fetch(`api/api.php?module=patient_history&patient_id=${patientId}`);
        const rxResult = await rxRes.json();
        const history = rxResult.success ? rxResult.data : [];
        currentHistory = history;
        
        // Build HTML
        let historyHtml = '';
        if (history.length > 0) {
            historyHtml = history.map(record => `
                <div class="border rounded-lg p-4 space-y-3">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
                        <div>
                            <p class="text-muted-foreground text-xs uppercase font-semibold">Timestamp</p>
                            <p class="font-semibold">${new Date(record.created_at).toLocaleString()}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-xs uppercase font-semibold">Doctor</p>
                            <p class="font-semibold">${record.doctor_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-xs uppercase font-semibold">Diagnosis</p>
                            <p class="font-semibold">${record.diagnosis || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-xs uppercase font-semibold">Status</p>
                            <p class="font-semibold"><span class="badge ${record.rx_status === 'dispensed' ? 'bg-green-500/10 text-green-700' : 'bg-yellow-500/10 text-yellow-700'}">${record.rx_status}</span></p>
                        </div>
                    </div>
                    
                    <div class="border-t pt-3">
                        <p class="text-xs uppercase font-semibold text-muted-foreground mb-2">Medications</p>
                        <div class="space-y-2">
                            ${record.items.map(item => `
                                <div class="flex justify-between items-center text-sm bg-muted/30 p-2 rounded">
                                    <div>
                                        <span class="font-semibold">${item.medication_name}</span>
                                        <span class="text-xs text-muted-foreground ml-2">${item.dosage} - ${item.frequency}</span>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs">Qty: <span class="font-semibold">${item.quantity}</span></div>
                                        ${item.pharmacist_name ? `<div class="text-xs text-muted-foreground">Dispensed by: ${item.pharmacist_name}</div>` : ''}
                                        ${item.dispensed_at ? `<div class="text-xs text-muted-foreground">${new Date(item.dispensed_at).toLocaleString()}</div>` : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            historyHtml = '<div class="text-center py-8 text-muted-foreground">No prescription history found</div>';
        }
        
        content.innerHTML = `
            <div class="space-y-6">
                <!-- Patient Info -->
                <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-muted-foreground uppercase font-semibold">Full Name</p>
                            <p class="font-bold text-lg mt-1">${patient.first_name} ${patient.last_name}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground uppercase font-semibold">Card Number</p>
                            <p class="font-bold text-lg mt-1">${patient.card_number}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground uppercase font-semibold">Status</p>
                            <p class="font-bold text-lg mt-1"><span class="badge ${patient.status === 'admitted' ? 'bg-primary/10 text-primary' : 'badge-secondary'}">${patient.status}</span></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-blue-200">
                        <div>
                            <p class="text-xs text-muted-foreground">DOB</p>
                            <p class="font-semibold">${patient.date_of_birth}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Gender</p>
                            <p class="font-semibold capitalize">${patient.gender}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Blood Type</p>
                            <p class="font-semibold">${patient.blood_type || '-'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Phone</p>
                            <p class="font-semibold">${patient.phone_number || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Medical History -->
                <div>
                    <h4 class="font-bold text-lg mb-4">Medical History</h4>
                    <div class="space-y-4">
                        ${historyHtml}
                    </div>
                </div>
            </div>
        `;
        lucide.createIcons();
    } catch (error) {
        console.error(error);
        content.innerHTML = '<div class="text-destructive">Error loading patient history</div>';
    }
}

let currentPatient = null;
let currentHistory = [];

function printHistory() {
    const content = document.getElementById('patientDetailContent').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Patient History - ${currentPatient.first_name} ${currentPatient.last_name}</title>
                <link rel="stylesheet" href="assets/css/tailwind.css">
                <link rel="stylesheet" href="assets/css/style.css">
                <style>
                    body { padding: 2rem; background: white; }
                    .no-print { display: none; }
                    .badge { border: 1px solid #ccc; padding: 2px 8px; border-radius: 4px; }
                    .bg-blue-50 { background-color: #f0f7ff !important; border: 1px solid #cce3ff !important; }
                    .border { border: 1px solid #e5e7eb !important; }
                    .p-6 { padding: 1.5rem !important; }
                    .p-4 { padding: 1rem !important; }
                    .rounded-lg { border-radius: 0.5rem !important; }
                </style>
            </head>
            <body>
                <div class="mb-8 flex justify-between items-center">
                    <h1 class="text-2xl font-bold">Patient Medical Record</h1>
                    <p class="text-sm text-muted-foreground">Generated on ${new Date().toLocaleString()}</p>
                </div>
                ${content}
                <div class="mt-12 pt-4 border-t text-center text-xs text-muted-foreground">
                    PharmaCare Hospital Pharmacy System - Official Medical Record
                </div>
                <script>
                    setTimeout(() => {
                        window.print();
                        // window.close();
                    }, 500);
                <\/script>
            </body>
        </html>
    `);
    printWindow.document.close();
}

function downloadCSV() {
    if (!currentHistory || currentHistory.length === 0) {
        alert('No history data to download');
        return;
    }

    let csv = 'Date,Doctor,Diagnosis,Status,Medication,Dosage,Frequency,Quantity,Pharmacist,Dispensed At\n';
    
    currentHistory.forEach(record => {
        record.items.forEach(item => {
            const row = [
                new Date(record.created_at).toLocaleString(),
                `"${record.doctor_name || 'N/A'}"`,
                `"${record.diagnosis || 'N/A'}"`,
                record.rx_status,
                `"${item.medication_name}"`,
                `"${item.dosage}"`,
                `"${item.frequency}"`,
                item.quantity,
                `"${item.pharmacist_name || 'N/A'}"`,
                item.dispensed_at ? new Date(item.dispensed_at).toLocaleString() : 'N/A'
            ];
            csv += row.join(',') + '\n';
        });
    });

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `history_${currentPatient.card_number}_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function clearSearch() {
    document.getElementById('searchCardNumber').value = '';
    document.getElementById('searchName').value = '';
    document.getElementById('searchEmail').value = '';
    document.querySelector('#searchResultsTable tbody').innerHTML = '<tr><td colspan="6" class="text-center py-8 text-muted-foreground">Enter search criteria and click Search</td></tr>';
}
</script>
<?php renderFooter(); ?>
