<?php
require_once 'api/db.php';
require_once 'layout.php';
session_start();
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }
renderHeader('Inventory', $_SESSION['user']);
?>
<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-2xl font-bold flex items-center gap-2">
            <i data-lucide="package" class="w-6 h-6 text-primary"></i>
            Inventory Management
        </h1>
        <p class="text-muted-foreground">Track medication stock levels, expiry dates, and manage restocking</p>
    </div>
    <button class="btn btn-primary gap-2" onclick="openModal('medModal')">
        <i data-lucide="plus" class="w-4 h-4"></i> Quick Restock
    </button>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="card p-6 border-l-4 border-l-primary">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-muted-foreground text-sm">Total Medications</p>
                <p class="text-3xl font-bold mt-2" id="totalMeds">0</p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                <i data-lucide="pill" class="w-6 h-6 text-primary"></i>
            </div>
        </div>
    </div>
    
    <div class="card p-6 border-l-4 border-l-destructive">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-muted-foreground text-sm">Low Stock</p>
                <p class="text-3xl font-bold mt-2 text-destructive" id="lowStock">0</p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-destructive/10 flex items-center justify-center">
                <i data-lucide="alert-circle" class="w-6 h-6 text-destructive"></i>
            </div>
        </div>
    </div>
    
    <div class="card p-6 border-l-4 border-l-orange-500">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-muted-foreground text-sm">Expiring Soon</p>
                <p class="text-3xl font-bold mt-2 text-orange-500" id="expiringSoon">0</p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-orange-500/10 flex items-center justify-center">
                <i data-lucide="clock" class="w-6 h-6 text-orange-500"></i>
            </div>
        </div>
    </div>
    
    <div class="card p-6 border-l-4 border-l-purple-500">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-muted-foreground text-sm">Controlled</p>
                <p class="text-3xl font-bold mt-2 text-purple-500" id="controlled">0</p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-purple-500/10 flex items-center justify-center">
                <i data-lucide="lock" class="w-6 h-6 text-purple-500"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="card mb-8">
    <div class="card-header border-b">
        <h2 class="card-title">Medications</h2>
    </div>
    <div class="card-content p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="relative">
                <i data-lucide="search" class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground"></i>
                <input type="text" placeholder="Search medications..." id="inventorySearch" class="pl-9" onkeyup="filterTable()">
            </div>
            <select id="categoryFilter" onchange="filterTable()">
                <option value="">All Categories</option>
            </select>
            <select id="stockFilter" onchange="filterTable()">
                <option value="">All Stock</option>
                <option value="low">Low Stock</option>
                <option value="good">Good Stock</option>
                <option value="expired">Expired</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table>
                <thead>
                    <tr>
                        <th>Medication</th>
                        <th>Category</th>
                        <th>Stock Level</th>
                        <th>Quantity</th>
                        <th>Expiry Date</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="inventoryTableBody">
                    <tr><td colspan="7" class="text-center py-12 text-muted-foreground">Loading inventory...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Medication Modal -->
<div id="medModal" class="hidden modal-overlay">
    <div class="modal-content max-w-2xl">
        <div class="modal-header">
            <div>
                <h3 class="text-xl font-bold">Add New Medication</h3>
                <p class="text-sm text-muted-foreground mt-1">Register a new medication in the system inventory</p>
            </div>
            <button onclick="closeModal('medModal')" class="text-muted-foreground hover:text-foreground p-1 rounded-md hover:bg-muted transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="medForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5"><label>Name</label><input type="text" name="name" required placeholder="e.g. Amoxicillin"></div>
                    <div class="space-y-1.5"><label>Generic Name</label><input type="text" name="genericName" required placeholder="e.g. Amoxicillin Trihydrate"></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5"><label>Category</label><input type="text" name="category" required placeholder="e.g. Antibiotics"></div>
                    <div class="space-y-1.5"><label>Strength</label><input type="text" name="strength" required placeholder="e.g. 500mg"></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5"><label>Dosage Form</label><input type="text" name="dosageForm" required placeholder="e.g. Capsule"></div>
                    <div class="space-y-1.5"><label>Unit Price</label><input type="number" step="0.01" name="unitPrice" required placeholder="0.00"></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5"><label>Initial Quantity</label><input type="number" name="quantity" required placeholder="0"></div>
                    <div class="space-y-1.5"><label>Batch Number</label><input type="text" name="batchNumber" required placeholder="e.g. B12345"></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5"><label>Expiry Date</label><input type="date" name="expiryDate" required></div>
                    <div class="space-y-1.5"><label>Location</label><input type="text" name="location" required placeholder="e.g. Shelf A-1"></div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('medModal')" class="btn btn-outline">Cancel</button>
            <button type="submit" form="medForm" class="btn btn-primary px-8">Add Medication</button>
        </div>
    </div>
</div>

<!-- Quick Restock Modal -->
<div id="restockModal" class="hidden modal-overlay">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <div>
                <h3 class="text-xl font-bold">Quick Restock</h3>
                <p class="text-sm text-muted-foreground mt-1" id="restockMedName">Medication Name</p>
            </div>
            <button onclick="closeModal('restockModal')" class="text-muted-foreground hover:text-foreground p-1 rounded-md hover:bg-muted transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="restockForm" class="space-y-4">
                <input type="hidden" name="id" id="restockId">
                <div class="space-y-1.5">
                    <label>Amount to Add</label>
                    <input type="number" name="amount" id="restockAmount" required min="1" placeholder="Enter quantity">
                </div>
                <div class="p-3 bg-primary/5 rounded-lg border border-primary/10">
                    <p class="text-xs text-muted-foreground">This will increase the current stock level and update the last restocked date.</p>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('restockModal')" class="btn btn-outline">Cancel</button>
            <button type="submit" form="restockForm" class="btn btn-primary px-8">Update Stock</button>
        </div>
    </div>
</div>

<script>
let allInventoryData = [];

function getStockStatus(quantity, reorderLevel, expiryDate) {
    const today = new Date();
    const expiry = new Date(expiryDate);
    const daysUntilExpiry = Math.floor((expiry - today) / (1000 * 60 * 60 * 24));
    
    if (expiry < today) return 'expired';
    if (daysUntilExpiry <= 90) return 'expiring';
    if (quantity <= reorderLevel) return 'low';
    return 'good';
}

function getStockBadge(status) {
    const badges = {
        'good': '<span class="badge" style="background-color: #10b981; color: white;">Good</span>',
        'low': '<span class="badge" style="background-color: #ef4444; color: white;">Low Stock</span>',
        'expiring': '<span class="badge" style="background-color: #f97316; color: white;">Expiring</span>',
        'expired': '<span class="badge" style="background-color: #dc2626; color: white;">Expired</span>'
    };
    return badges[status] || badges['good'];
}

async function loadInventory() {
    const res = await fetch('api/api.php?module=inventory&action=list');
    const result = await res.json();
    const tbody = document.getElementById('inventoryTableBody');
    
    if (result.success) {
        allInventoryData = result.data;
        updateStats();
        populateCategoryFilter();
        renderTable(allInventoryData);
        lucide.createIcons();
    }
}

function updateStats() {
    let totalMeds = 0;
    let lowStock = 0;
    let expiringSoon = 0;
    let controlled = 0;
    
    allInventoryData.forEach(item => {
        totalMeds++;
        const status = getStockStatus(item.quantity, item.reorder_level || 50, item.expiry_date);
        if (status === 'low') lowStock++;
        if (status === 'expiring') expiringSoon++;
        if (item.category && item.category.toLowerCase().includes('controlled')) controlled++;
    });
    
    document.getElementById('totalMeds').textContent = totalMeds;
    document.getElementById('lowStock').textContent = lowStock;
    document.getElementById('expiringSoon').textContent = expiringSoon;
    document.getElementById('controlled').textContent = controlled;
}

function populateCategoryFilter() {
    const categories = [...new Set(allInventoryData.map(i => i.category))];
    const select = document.getElementById('categoryFilter');
    select.innerHTML = '<option value="">All Categories</option>';
    categories.forEach(cat => {
        if (cat) {
            const option = document.createElement('option');
            option.value = cat;
            option.textContent = cat;
            select.appendChild(option);
        }
    });
}

function renderTable(data) {
    const tbody = document.getElementById('inventoryTableBody');
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-12 text-muted-foreground">No medications found</td></tr>';
        return;
    }
    
    tbody.innerHTML = data.map(i => {
        const status = getStockStatus(i.quantity, i.reorder_level || 50, i.expiry_date);
        const expiryDate = new Date(i.expiry_date).toLocaleDateString();
        return `
            <tr>
                <td>
                    <div class="flex items-center gap-2">
                        <i data-lucide="pill" class="w-4 h-4 text-primary"></i>
                        <div>
                            <div class="font-bold">${i.medication_name}</div>
                            <div class="text-xs text-muted-foreground">${i.batch_number}</div>
                        </div>
                    </div>
                </td>
                <td><span class="badge badge-secondary">${i.category}</span></td>
                <td>${getStockBadge(status)}</td>
                <td><div class="font-bold">${i.quantity} units</div><div class="text-xs text-muted-foreground">Reorder at ${i.reorder_level || 50}</div></td>
                <td><span class="text-sm ${status === 'expired' ? 'text-destructive font-bold' : ''}">${expiryDate}</span></td>
                <td><i data-lucide="map-pin" class="w-4 h-4 inline"></i> ${i.location}</td>
                <td><button onclick="restock('${i.id}')" class="btn btn-outline text-xs py-1 px-3">Restock</button></td>
            </tr>
        `;
    }).join('');
    lucide.createIcons();
}

function filterTable() {
    const searchTerm = document.getElementById('inventorySearch').value.toLowerCase();
    const categoryFilter = document.getElementById('categoryFilter').value;
    const stockFilter = document.getElementById('stockFilter').value;
    
    const filtered = allInventoryData.filter(item => {
        const matchesSearch = item.medication_name.toLowerCase().includes(searchTerm) || 
                            item.batch_number.toLowerCase().includes(searchTerm);
        const matchesCategory = !categoryFilter || item.category === categoryFilter;
        const status = getStockStatus(item.quantity, item.reorder_level || 50, item.expiry_date);
        const matchesStock = !stockFilter || status === stockFilter;
        
        return matchesSearch && matchesCategory && matchesStock;
    });
    
    renderTable(filtered);
}

function restock(id) {
    const item = allInventoryData.find(i => i.id === id);
    if (item) {
        document.getElementById('restockId').value = id;
        document.getElementById('restockMedName').textContent = item.medication_name;
        document.getElementById('restockAmount').value = '';
        openModal('restockModal');
    }
}

document.getElementById('restockForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('restockId').value;
    const amount = document.getElementById('restockAmount').value;
    
    const res = await fetch('api/api.php?module=inventory&action=restock', {
        method: 'POST', 
        headers: { 'Content-Type': 'application/json' }, 
        body: JSON.stringify({ id, amount: parseInt(amount) })
    });
    
    if ((await res.json()).success) {
        closeModal('restockModal');
        loadInventory();
    }
});

document.getElementById('medForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    const res = await fetch('api/api.php?module=inventory&action=add_medication', {
        method: 'POST', 
        headers: { 'Content-Type': 'application/json' }, 
        body: JSON.stringify(data)
    });
    if ((await res.json()).success) { 
        closeModal('medModal'); 
        loadInventory(); 
    }
});

loadInventory();
</script>
<?php renderFooter(); ?>
