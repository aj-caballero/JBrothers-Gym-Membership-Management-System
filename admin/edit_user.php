<?php
// C:/Users/Kyle/GYM MEMBERSHIP/admin/edit_user.php
$pageTitle = 'Edit User Options';
require_once '../includes/header.php';
require_admin();

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$userEdit = $stmt->fetch();

if (!$userEdit) {
    redirect('/admin/users.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'];
    $permissions_array = $_POST['permissions'] ?? [];
    $permissions_string = ($role === 'admin') ? null : implode(',', $permissions_array);
    $password_reset = $_POST['password'] ?? '';
    
    if (!empty($password_reset)) {
        $hashed = password_hash($password_reset, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET role=?, permissions=?, password=? WHERE id=?");
        $stmt->execute([$role, $permissions_string, $hashed, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET role=?, permissions=? WHERE id=?");
        $stmt->execute([$role, $permissions_string, $id]);
    }
    
    redirect('/admin/users.php?success=edited');
}

$uPerms = $userEdit->permissions ? explode(',', $userEdit->permissions) : [];
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Edit User Access: <?= htmlspecialchars($userEdit->full_name) ?></h3>
        <a href="users.php" class="btn btn-sm" style="background:var(--bg-surface-hover); color:#fff;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    
    <form method="POST">
        <div class="form-group">
            <label>Role</label>
            <select name="role" id="editRoleSelect" class="form-control">
                <option value="staff" <?= $userEdit->role === 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="admin" <?= $userEdit->role === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>
        
        <div class="form-group" id="editPermissionsGroup" style="<?= $userEdit->role === 'admin' ? 'display:none;' : '' ?>">
            <label>Module Access (Staff Only)</label>
            <div style="display:flex; flex-wrap:wrap; gap:15px; margin-top:5px; margin-bottom:10px;">
                <label style="display:inline-flex; align-items:center; gap:5px;"><input type="checkbox" name="permissions[]" value="members" <?= in_array('members', $uPerms) ? 'checked' : '' ?>> Members</label>
                <label style="display:inline-flex; align-items:center; gap:5px;"><input type="checkbox" name="permissions[]" value="plans" <?= in_array('plans', $uPerms) ? 'checked' : '' ?>> Plans</label>
                <label style="display:inline-flex; align-items:center; gap:5px;"><input type="checkbox" name="permissions[]" value="payments" <?= in_array('payments', $uPerms) ? 'checked' : '' ?>> Payments</label>
                <label style="display:inline-flex; align-items:center; gap:5px;"><input type="checkbox" name="permissions[]" value="attendance" <?= in_array('attendance', $uPerms) ? 'checked' : '' ?>> Attendance</label>
                <label style="display:inline-flex; align-items:center; gap:5px;"><input type="checkbox" name="permissions[]" value="reports" <?= in_array('reports', $uPerms) ? 'checked' : '' ?>> Reports</label>
            </div>
        </div>
        
        <div class="form-group">
            <label>Reset Password <small class="text-muted">(Leave blank to keep unchanged)</small></label>
            <input type="password" name="password" class="form-control">
        </div>
        
        <button type="submit" class="btn btn-primary" style="width:100%;">Save Changes</button>
    </form>
</div>

<script>
document.getElementById('editRoleSelect').addEventListener('change', function() {
    document.getElementById('editPermissionsGroup').style.display = (this.value === 'admin') ? 'none' : 'block';
});
</script>

<?php require_once '../includes/footer.php'; ?>
