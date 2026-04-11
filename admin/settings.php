<?php
// C:/Users/Kyle/GYM MEMBERSHIP/admin/settings.php
$pageTitle = 'Gym Settings';
require_once '../includes/header.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gym_name = trim($_POST['gym_name']);
    $contact_email = trim($_POST['contact_email']);
    $contact_phone = trim($_POST['contact_phone']);
    $address = trim($_POST['address']);
    $currency = trim($_POST['currency'] ?? 'PHP');

    $stmtCheck = $pdo->query("SELECT id FROM gym_settings LIMIT 1");
    if ($stmtCheck->fetch()) {
        $sql = "UPDATE gym_settings SET gym_name=?, contact_email=?, contact_phone=?, address=?, currency=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$gym_name, $contact_email, $contact_phone, $address, $currency]);
    } else {
        $sql = "INSERT INTO gym_settings (gym_name, contact_email, contact_phone, address, currency) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$gym_name, $contact_email, $contact_phone, $address, $currency]);
    }

    $success = "Settings updated successfully.";
    $settings = getGymSettings($pdo); // Refresh obj
}
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">System Settings</h3>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert" style="background: rgba(40, 167, 69, 0.2); color: #28a745; border: 1px solid #28a745;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Gym Name</label>
            <input type="text" name="gym_name" class="form-control" value="<?= htmlspecialchars($settings->gym_name ?? '') ?>" required>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Contact Email</label>
                <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($settings->contact_email ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Contact Phone</label>
                <input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($settings->contact_phone ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Physical Address</label>
            <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($settings->address ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Currency Override (Default PHP)</label>
            <input type="text" name="currency" class="form-control" value="<?= htmlspecialchars($settings->currency ?? 'PHP') ?>">
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
