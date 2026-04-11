<?php
// C:/Users/Kyle/GYM MEMBERSHIP/plans/add.php
$pageTitle = 'Create Plan';
require_once '../includes/header.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_name = trim($_POST['plan_name']);
    $duration_days = (int) $_POST['duration_days'];
    $price = (float) $_POST['price'];
    $description = trim($_POST['description']);

    if (empty($plan_name) || $duration_days <= 0 || $price < 0) {
        $error = "Please fill in all required fields correctly.";
    } else {
        $sql = "INSERT INTO membership_plans (plan_name, duration_days, price, description) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$plan_name, $duration_days, $price, $description])) {
            redirect('/plans/index.php');
        } else {
            $error = "Failed to create plan.";
        }
    }
}
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">New Membership Plan</h3>
        <a href="index.php" class="btn btn-sm" style="background:var(--bg-surface-hover); color:#fff;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Plan Name *</label>
            <input type="text" name="plan_name" class="form-control" required>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Duration (in days) *</label>
                <input type="number" name="duration_days" class="form-control" min="1" required>
                <small style="color:var(--text-muted); display:block; margin-top:5px;">e.g., 30 for 1 month</small>
            </div>
            <div class="form-group">
                <label>Price *</label>
                <input type="number" step="0.01" name="price" class="form-control" min="0" required>
            </div>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="4"></textarea>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Plan</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
