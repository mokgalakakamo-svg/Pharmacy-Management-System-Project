<?php
require_once 'api/db.php';
require_once 'layout.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

// Redirect patients to their own dashboard
if ($_SESSION['user']['role'] === 'patient') {
    header('Location: patient_dashboard.php');
    exit;
}

renderHeader('Dashboard', $_SESSION['user']);
?>
<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-2xl font-bold">Welcome back, <?php echo htmlspecialchars(explode(' ', $_SESSION['user']['full_name'])[0]); ?></h1>
        <p class="text-muted-foreground">Here's an overview of your pharmacy operations</p>
    </div>
    <div class="flex gap-2">
        <span class="badge badge-secondary"><?php echo date('l, M j'); ?></span>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="card p-6 flex flex-col gap-2">
        <div class="flex justify-between items-start">
            <span class="text-sm font-medium text-muted-foreground">Admitted Patients</span>
            <i data-lucide="users" class="w-4 h-4 text-primary"></i>
        </div>
        <div class="text-2xl font-bold" id="admittedPatients">-</div>
        <p class="text-xs text-muted-foreground">Currently in hospital</p>
    </div>

    <div class="card p-6 flex flex-col gap-2">
        <div class="flex justify-between items-start">
            <span class="text-sm font-medium text-muted-foreground">Total Medications</span>
            <i data-lucide="pill" class="w-4 h-4 text-primary"></i>
        </div>
        <div class="text-2xl font-bold" id="totalMedications">-</div>
        <p class="text-xs text-muted-foreground">In medication catalog</p>
    </div>
    <div class="card p-6 flex flex-col gap-2">
        <div class="flex justify-between items-start">
            <span class="text-sm font-medium text-muted-foreground">Low Stock Alerts</span>
            <i data-lucide="alert-triangle" class="w-4 h-4 text-destructive"></i>
        </div>
        <div class="text-2xl font-bold" id="lowStockCount">-</div>
        <p class="text-xs text-muted-foreground">Items need restocking</p>
    </div>
</div>

<div class="grid lg:grid-cols-1 gap-6">
    <div class="lg:col-span-1">
        <div class="card">
            <div class="card-header border-b">
                <h2 class="card-title flex items-center gap-2">
                    <i data-lucide="activity" class="w-5 h-5 text-primary"></i>
                    Recent Activity
                </h2>
                <p class="card-description">Latest actions in the pharmacy system</p>
            </div>
            <div class="card-content p-6">
                <div id="recentActivities" class="flex flex-col gap-4">
                    <p class="text-center py-8 text-muted-foreground">Loading activity...</p>
                </div>
            </div>
        </div>
    </div>
    

</div>

<script>
async function loadDashboard() {
    try {
        const response = await fetch('api/dashboard.php?action=stats');
        const result = await response.json();
        if (result.success) {
            const stats = result.data.stats;
            document.getElementById('admittedPatients').textContent = stats.admittedPatients;
            // document.getElementById('pendingPrescriptions').textContent = stats.pendingPrescriptions;
            document.getElementById('totalMedications').textContent = stats.totalMedications;
            document.getElementById('lowStockCount').textContent = stats.lowStockCount;
            
            const activityList = document.getElementById('recentActivities');
            if (result.data.recentActivities.length === 0) {
                activityList.innerHTML = '<p class="text-center py-8 text-muted-foreground">No recent activity</p>';
            } else {
                activityList.innerHTML = result.data.recentActivities.map(activity => `
                    <div class="flex gap-4 p-3 rounded-lg bg-muted/30 border">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary/10 text-primary shrink-0">
                            <i data-lucide="file-text" class="w-5 h-5"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-sm">${activity.action.replace(/_/g, ' ')}</p>
                            <p class="text-sm text-muted-foreground truncate">${activity.details}</p>
                            <p class="text-xs text-muted-foreground mt-1">By ${activity.user_name}</p>
                        </div>
                        <span class="text-xs text-muted-foreground whitespace-nowrap">${new Date(activity.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                    </div>
                `).join('');
                lucide.createIcons();
            }
        }
    } catch (err) { console.error(err); }
}
loadDashboard();
</script>
<?php renderFooter(); ?>
