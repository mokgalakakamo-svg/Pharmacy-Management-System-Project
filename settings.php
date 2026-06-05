<?php
require_once 'api/db.php';
require_once 'layout.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

renderHeader('Settings', $_SESSION['user']);
?>
<div class="mb-8">
    <h1 class="text-2xl font-bold">Settings</h1>
    <p class="text-muted-foreground">Manage your account and system preferences</p>
</div>

<div class="grid lg:grid-cols-7 gap-6">
    <div class="lg:col-span-2">
        <div class="flex flex-col gap-1">
            <button class="btn btn-primary justify-start" style="text-align: left;">Account</button>
            <button class="btn btn-outline justify-start" style="text-align: left; border: none;">Security</button>
            <button class="btn btn-outline justify-start" style="text-align: left; border: none;">Notifications</button>
            <button class="btn btn-outline justify-start" style="text-align: left; border: none;">Display</button>
        </div>
    </div>
    
    <div class="lg:col-span-5">
        <div class="card">
            <div class="card-header border-b">
                <h2 class="card-title">Profile Information</h2>
                <p class="card-description">Update your personal details and contact information</p>
            </div>
            <div class="card-content p-6 space-y-6">
                <div class="flex items-center gap-6">
                    <div class="w-20 h-20 rounded-full bg-primary flex items-center justify-center text-2xl font-bold text-primary-foreground">
                        <?php echo strtoupper(substr($_SESSION['user']['full_name'], 0, 1)); ?>
                    </div>
                    <button class="btn btn-outline">Change Avatar</button>
                </div>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($_SESSION['user']['full_name']); ?>" disabled class="bg-muted">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Email Address</label>
                        <input type="email" value="<?php echo htmlspecialchars($_SESSION['user']['email']); ?>" disabled class="bg-muted">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($_SESSION['user']['username']); ?>" disabled class="bg-muted">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Role</label>
                        <input type="text" value="<?php echo htmlspecialchars($_SESSION['user']['role']); ?>" disabled class="bg-muted">
                    </div>
                </div>
                
                <div class="pt-4 border-t flex justify-end">
                    <button class="btn btn-primary" onclick="openEditProfileModal()">Edit Profile</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="editProfileModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-background rounded-xl shadow-lg w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-xl font-bold">Edit Profile</h3>
            <button onclick="closeModal('editProfileModal')" class="text-muted-foreground hover:text-foreground"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form id="editProfileForm" class="p-6 space-y-4">
            <div class="space-y-1.5">
                <label class="text-sm font-medium">Full Name</label>
                <input type="text" name="fullName" id="editFullName" required>
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-medium">Email Address</label>
                <input type="email" name="email" id="editEmail" required>
            </div>
            <div class="pt-4 flex justify-end gap-2">
                <button type="button" onclick="closeModal('editProfileModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>

function openEditProfileModal() {
    document.getElementById('editFullName').value = '<?php echo htmlspecialchars($_SESSION['user']['full_name']); ?>';
    document.getElementById('editEmail').value = '<?php echo htmlspecialchars($_SESSION['user']['email']); ?>';
    openModal('editProfileModal');
}

document.getElementById('editProfileForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    const res = await fetch('api/api.php?module=users&action=update_profile', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.success) {
        closeModal('editProfileModal');
        alert('Profile updated successfully!');
        location.reload();
    } else {
        alert('Error: ' + (result.error || 'Failed to update profile'));
    }
});
</script>
<?php renderFooter(); ?>
