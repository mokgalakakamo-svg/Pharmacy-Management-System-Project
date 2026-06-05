<?php
require_once 'api/db.php';
require_once 'layout.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

renderHeader('Help & Support', $_SESSION['user']);
?>
<div class="mb-8">
    <h1 class="text-2xl font-bold">Help & Support</h1>
    <p class="text-muted-foreground">Find answers to common questions and learn how to use the system</p>
</div>

<div class="grid md:grid-cols-3 gap-6">
    <div class="card">
        <div class="card-header">
            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center mb-2">
                <i data-lucide="book-open" class="text-primary"></i>
            </div>
            <h2 class="card-title">User Guide</h2>
            <p class="card-description">Comprehensive documentation for all system features</p>
        </div>
        <div class="card-content">
            <button class="btn btn-outline w-full">View Guide</button>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center mb-2">
                <i data-lucide="message-square" class="text-primary"></i>
            </div>
            <h2 class="card-title">Support Desk</h2>
            <p class="card-description">Get in touch with our technical support team</p>
        </div>
        <div class="card-content">
            <button class="btn btn-outline w-full">Contact Support</button>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center mb-2">
                <i data-lucide="video" class="text-primary"></i>
            </div>
            <h2 class="card-title">Video Tutorials</h2>
            <p class="card-description">Watch step-by-step guides on common workflows</p>
        </div>
        <div class="card-content">
            <button class="btn btn-outline w-full">Watch Videos</button>
        </div>
    </div>
</div>
<?php renderFooter(); ?>
