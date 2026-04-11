<?php
// C:/Users/Kyle/GYM MEMBERSHIP/plans/edit.php
$pageTitle = 'Edit Plan';
require_once '../includes/header.php';

require_admin();

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM membership_plans WHERE id = ?");
$stmt->execute([$id]);
$plan = $stmt->fetch();

if (!$plan) {
    redirect('/plans/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_name = trim($_POST['plan_name']);
    $duration_days = (int) $_POST['duration_days'];
    $price = (float) $_POST['price'];
    $description = trim($_POST['description']);

    if (empty($plan_name) || $duration_days <= 0 || $price < 0) {
        $error = "Please fill in all required fields correctly.";
    } else {
        $sql = "UPDATE membership_plans SET plan_name=?, duration_days=?, price=?, description=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$plan_name, $duration_days, $price, $description, $id])) {
            redirect('/plans/index.php');
        } else {
            $error = "Failed to update plan.";
        }
    }
}
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Edit Membership Plan</h3>
        <a href="index.php" class="btn btn-sm" style="background:var(--bg-surface-hover); color:#fff;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Plan Name *</label>
            <input type="text" name="plan_name" class="form-control" value="<?= htmlspecialchars($plan->plan_name) ?>" required>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Duration (in days) *</label>
                <input type="number" name="duration_days" class="form-control" min="1" value="<?= $plan->duration_days ?>" required>
            </div>
            <div class="form-group">
                <label>Price *</label>
                <input type="number" step="0.01" name="price" class="form-control" min="0" value="<?= $plan->price ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($plan->description) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
