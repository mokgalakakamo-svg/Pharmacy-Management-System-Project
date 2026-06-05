<?php
function renderHeader($title, $user) {
    $initials = strtoupper(substr($user['full_name'], 0, 1) . (strpos($user['full_name'], ' ') !== false ? substr(explode(' ', $user['full_name'])[1], 0, 1) : ''));
    $roleDisplayNames = [
        'admin' => 'Administrator',
        'doctor' => 'Doctor',
        'pharmacist' => 'Pharmacist',
        'assistant' => 'Pharmacy Assistant',
        'stock_taker' => 'Stock Taker',
        'patient' => 'Patient',
    ];
    $roleName = $roleDisplayNames[$user['role']] ?? $user['role'];
    $current_page = basename($_SERVER['PHP_SELF']);
    
    $menu = [
        ['title' => 'Overview', 'items' => [
            ['title' => 'Dashboard', 'url' => 'dashboard.php', 'icon' => 'layout-dashboard', 'perm' => 'dashboard'],
        ]],
        ['title' => 'Patient Care', 'items' => [
            ['title' => 'Patients', 'url' => 'patients.php', 'icon' => 'users', 'perm' => 'patients'],
            ['title' => 'Prescriptions', 'url' => 'prescriptions.php', 'icon' => 'clipboard-list', 'perm' => 'prescriptions'],
            ['title' => 'Dispensing', 'url' => 'dispensing.php', 'icon' => 'syringe', 'perm' => 'dispensing'],
        ]],
        ['title' => 'Pharmacy Operations', 'items' => [
            ['title' => 'Cupboard Access', 'url' => 'cupboard.php', 'icon' => 'lock', 'perm' => 'cupboard'],
            ['title' => 'Inventory', 'url' => 'inventory.php', 'icon' => 'package', 'perm' => 'inventory'],
        ]],
        ['title' => 'Administration', 'items' => [
            ['title' => 'Admin Panel', 'url' => 'admin.php', 'icon' => 'user-cog', 'perm' => 'admin'],
            ['title' => 'Patient History', 'url' => 'patient_history.php', 'icon' => 'history', 'perm' => 'admin'],
            ['title' => 'Audit Log', 'url' => 'audit.php', 'icon' => 'file-text', 'perm' => 'audit'],
            ['title' => 'Settings', 'url' => 'settings.php', 'icon' => 'settings', 'perm' => 'settings'],
        ]],
    ];

    function hasPerm($perm, $userRole) {
        $perms = [
            'admin' => ['dashboard', 'admin', 'patients', 'inventory', 'audit', 'settings'],
            'doctor' => ['dashboard', 'prescriptions', 'settings'],
            'pharmacist' => ['dashboard', 'dispensing', 'inventory', 'cupboard', 'settings'],
            'assistant' => ['dashboard', 'dispensing', 'inventory', 'cupboard', 'settings'],
            'stock_taker' => ['dashboard', 'inventory', 'settings'],
            'patient' => ['dashboard', 'settings'],
        ];
        return in_array($perm, $perms[$userRole] ?? []);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - PharmaCare</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="assets/js/modal.js"></script>
</head>
<body class="bg-background">
    <div class="flex h-screen w-full overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 shrink-0 bg-sidebar flex flex-col border-r border-sidebar-border">
            <div class="p-4 border-b border-sidebar-border">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary">
                        <i data-lucide="pill" class="w-6 h-6 text-primary-foreground"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-semibold text-sidebar-foreground">PharmaCare</span>
                        <span class="text-xs text-sidebar-foreground">Hospital Pharmacy</span>
                    </div>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto p-3 space-y-6">
                <?php foreach ($menu as $group): ?>
                    <?php 
                        $visibleItems = array_filter($group['items'], function($item) use ($user) {
                            return hasPerm($item['perm'], $user['role']);
                        });
                        if (empty($visibleItems)) continue;
                    ?>
                    <div>
                        <div class="px-3 mb-2 text-xs font-semibold text-sidebar-foreground uppercase tracking-wider">
                            <?php echo $group['title']; ?>
                        </div>
                        <div class="space-y-1">
                            <?php foreach ($visibleItems as $item): ?>
                                <a href="<?php echo $item['url']; ?>" class="sidebar-nav-item <?php echo ($current_page == $item['url']) ? 'active' : ''; ?>">
                                    <i data-lucide="<?php echo $item['icon']; ?>" class="w-4 h-4"></i>
                                    <span><?php echo $item['title']; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="p-4 border-t border-sidebar-border space-y-4">
                <a href="api/auth.php?action=logout" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-destructive hover:bg-destructive/10 transition-colors w-full">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    <span>Sign Out</span>
                </a>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-xs font-bold text-primary-foreground">
                        <?php echo $initials; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-sidebar-foreground truncate"><?php echo htmlspecialchars($user['full_name']); ?></p>
                        <p class="text-xs text-sidebar-foreground truncate"><?php echo $roleName; ?></p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <header class="h-16 shrink-0 flex items-center justify-between px-6 border-b bg-white">
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <span>Dashboard</span>
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    <span class="text-foreground font-medium"><?php echo $title; ?></span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-xs text-muted-foreground hidden md:inline">Logged in as: <strong><?php echo htmlspecialchars($user['full_name']); ?></strong></span>
                    <a href="api/auth.php?action=logout" class="btn btn-outline text-destructive hover:bg-destructive/10 gap-2 px-3 py-1.5 h-auto">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span class="text-xs font-semibold">Logout</span>
                    </a>
                </div>
            </header>
            <main class="flex-1 overflow-y-auto p-6 bg-muted/30">
<?php
}

function renderFooter() {
?>
            </main>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
<?php
}
?>
