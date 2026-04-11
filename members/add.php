<?php
// C:/Users/Kyle/GYM MEMBERSHIP/members/add.php
$pageTitle = 'Add Member';
require_once '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $dob = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $join_date = $_POST['join_date'];
    $status = $_POST['status'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM members WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email already exists!";
        } else {
            $sql = "INSERT INTO members (full_name, email, phone, address, date_of_birth, gender, join_date, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$full_name, $email, $phone, $address, $dob, $gender, $join_date, $status])) {
                redirect('/members/index.php');
            } else {
                $error = "Failed to add member.";
            }
        }
    }
}
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">New Member Registration</h3>
        <a href="index.php" class="btn btn-sm" style="background:var(--bg-surface-hover); color:#fff;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" class="form-control" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" class="form-control">
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Gender</label>
                <select name="gender" class="form-control">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Join Date *</label>
                <input type="date" name="join_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Address</label>
            <textarea name="address" class="form-control" rows="3"></textarea>
        </div>

        <div class="form-group">
            <label>Initial Status</label>
            <select name="status" class="form-control">
                <option value="Active">Active</option>
                <option value="Expired">Expired</option>
                <option value="Suspended">Suspended</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Member</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
