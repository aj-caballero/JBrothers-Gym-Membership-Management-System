<?php
// C:/Users/Kyle/GYM MEMBERSHIP/members/edit.php
$pageTitle = 'Edit Member';
require_once '../includes/header.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) {
    redirect('/members/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $dob = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $status = $_POST['status'];
    $reset_password = $_POST['reset_password'] ?? '';

    $stmt = $pdo->prepare("SELECT id FROM members WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        $error = "Email already in use by another member.";
    } else {
        if (!empty($reset_password)) {
            $hashed = password_hash($reset_password, PASSWORD_DEFAULT);
            $sql = "UPDATE members SET full_name=?, email=?, password=?, phone=?, address=?, date_of_birth=?, gender=?, status=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$full_name, $email, $hashed, $phone, $address, $dob, $gender, $status, $id]);
        } else {
            $sql = "UPDATE members SET full_name=?, email=?, phone=?, address=?, date_of_birth=?, gender=?, status=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$full_name, $email, $phone, $address, $dob, $gender, $status, $id]);
        }
        
        if ($success) {
            redirect('/members/index.php');
        } else {
            $error = "Failed to update member.";
        }
    }
}
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Edit Member: <?= htmlspecialchars($member->full_name) ?></h3>
        <a href="index.php" class="btn btn-sm" style="background:var(--bg-surface-hover); color:#fff;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($member->full_name) ?>" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($member->email) ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($member->phone) ?>">
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control" value="<?= $member->date_of_birth ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Reset Password <small style="color:var(--text-muted)">(Leave blank to keep current password)</small></label>
            <input type="password" name="reset_password" class="form-control" placeholder="Enter new password">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Gender</label>
                <select name="gender" class="form-control">
                    <option value="Male" <?= $member->gender === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $member->gender === 'Female' ? 'selected' : '' ?>>Female</option>
                    <option value="Other" <?= $member->gender === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="Active" <?= $member->status === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $member->status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="Expired" <?= $member->status === 'Expired' ? 'selected' : '' ?>>Expired</option>
                    <option value="Suspended" <?= $member->status === 'Suspended' ? 'selected' : '' ?>>Suspended</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Address</label>
            <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($member->address) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Member</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
