<?php
require_once 'api/db.php';
require_once 'layout.php';
session_start();
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }
renderHeader('Audit Log', $_SESSION['user']);
?>
<div class="mb-8">
    <h1 class="text-2xl font-bold">System Audit Logs</h1>
    <p class="text-muted-foreground">Track all actions and changes within the system<?php echo ($_SESSION['user']['role'] !== 'admin') ? ' (showing your activity only)' : ' (showing all user activity)'; ?></p>
</div>

<div class="card">
    <div class="card-header border-b flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <h2 class="card-title">Activity Logs</h2>
        <div class="flex gap-2">
            <input type="text" placeholder="Filter logs..." id="logSearch" class="w-full md:w-64">
            <button onclick="loadLogs()" class="btn btn-outline p-2"><i data-lucide="refresh-cw" class="w-4 h-4"></i></button>
        </div>
    </div>
    <div class="card-content p-0 overflow-x-auto">
        <table>
            <thead>
                <tr><th>Timestamp</th><th>User</th><th>Action</th><th>Details</th></tr>
            </thead>
            <tbody id="auditTableBody">
                <tr><td colspan="4" class="text-center py-12 text-muted-foreground">Loading logs...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
async function loadLogs() {
    const tbody = document.getElementById('auditTableBody');
    try {
        const res = await fetch('api/api.php?module=audit');
        const result = await res.json();
        if (result.success) {
            if (result.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-12 text-muted-foreground">No logs found</td></tr>';
                return;
            }
            tbody.innerHTML = result.data.map(log => `
                <tr>
                    <td class="text-xs whitespace-nowrap">${new Date(log.created_at).toLocaleString()}</td>
                    <td><div class="font-bold text-sm">${log.user_name}</div></td>
                    <td><span class="badge badge-outline text-[10px] uppercase">${log.action}</span></td>
                    <td class="text-sm">${log.details}</td>
                </tr>
            `).join('');
            lucide.createIcons();
        }
    } catch (err) { tbody.innerHTML = '<tr><td colspan="4" class="text-center py-12 text-destructive">Error loading logs</td></tr>'; }
}

document.getElementById('logSearch').addEventListener('input', (e) => {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('#auditTableBody tr').forEach(row => row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none');
});

loadLogs();
</script>
<?php renderFooter(); ?>
