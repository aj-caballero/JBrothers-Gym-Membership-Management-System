<?php
// C:/Users/Kyle/GYM MEMBERSHIP/member_panel/profile.php
$pageTitle = 'My Profile';
require_once 'includes/header.php';

$member_id = $_SESSION['user_id'];

// Get current member info
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $dob = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    $update_pw = false;
    if (!empty($new_password)) {
        if ($member->password && password_verify($current_password, $member->password)) {
            $update_pw = true;
        } else {
            $error = "Incorrect current password.";
        }
    }
    
    if (!isset($error)) {
        if ($update_pw) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE members SET phone=?, address=?, date_of_birth=?, gender=?, password=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$phone, $address, $dob, $gender, $hashed, $member_id])) {
                $success = "Profile and password updated successfully.";
                $member->password = $hashed;
                $member->phone = $phone;
                $member->address = $address;
                $member->date_of_birth = $dob;
                $member->gender = $gender;
            } else {
                $error = "Failed to update profile.";
            }
        } else {
            $sql = "UPDATE members SET phone=?, address=?, date_of_birth=?, gender=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$phone, $address, $dob, $gender, $member_id])) {
                $success = "Profile updated successfully.";
                $member->phone = $phone;
                $member->address = $address;
                $member->date_of_birth = $dob;
                $member->gender = $gender;
            } else {
                $error = "Failed to update profile.";
            }
        }
    }
}
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Manage Your Profile</h3>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert" style="background-color:rgba(220,53,69,0.2); color:var(--danger); padding:10px; margin-bottom:15px; border-radius:4px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <div class="alert" style="background-color:rgba(40,167,69,0.2); color:var(--success); padding:10px; margin-bottom:15px; border-radius:4px;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>Full Name <small style="color:var(--text-muted);">(Contact admin to change)</small></label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($member->full_name) ?>" readonly>
            </div>
            <div class="form-group">
                <label>Email <small style="color:var(--text-muted);">(Contact admin to change)</small></label>
                <input type="email" class="form-control" value="<?= htmlspecialchars($member->email) ?>" readonly>
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
            <label>Gender</label>
            <select name="gender" class="form-control">
                <option value="Male" <?= $member->gender === 'Male' ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= $member->gender === 'Female' ? 'selected' : '' ?>>Female</option>
                <option value="Other" <?= $member->gender === 'Other' ? 'selected' : '' ?>>Other</option>
            </select>
        </div>

        <div class="form-group">
            <label>Address</label>
            <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($member->address) ?></textarea>
        </div>
        
        <hr style="border-top:1px solid var(--border); margin: 30px 0;">
        <h4 style="margin-top:0;">Change Password</h4>
        <p style="color:var(--text-muted); font-size:14px; margin-bottom:20px;">Leave blank if you do not wish to change your password.</p>
        
        <div class="form-row">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" class="form-control" placeholder="Required if setting new password">
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="Enter new password">
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
