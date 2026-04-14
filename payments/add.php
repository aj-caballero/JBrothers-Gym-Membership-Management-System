<?php
// C:/Users/Kyle/GYM MEMBERSHIP/payments/add.php
$pageTitle = 'Record Payment & Membership';
require_once '../includes/header.php';

// Pre-fill member if passed
$pre_member_id = $_GET['member_id'] ?? 0;

$membersStmt = $pdo->query("SELECT id, full_name, email FROM members WHERE status != 'Suspended' ORDER BY full_name ASC");
$members = $membersStmt->fetchAll();

$plansStmt = $pdo->query("SELECT * FROM membership_plans ORDER BY price ASC");
$plans = $plansStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = (int) $_POST['member_id'];
    $plan_id = (int) $_POST['plan_id'];
    $amount = (float) $_POST['amount'];
    $method = $_POST['payment_method'];

    if ($member_id > 0 && $plan_id > 0 && $amount >= 0) {
        try {
            $pdo->beginTransaction();

            // 1. Get plan details for duration
            $planStmt = $pdo->prepare("SELECT duration_days FROM membership_plans WHERE id = ?");
            $planStmt->execute([$plan_id]);
            $plan = $planStmt->fetch();

            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime("+$plan->duration_days days"));

            // 2. Create Membership Record
            $msStmt = $pdo->prepare("INSERT INTO memberships (member_id, plan_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'Active')");
            $msStmt->execute([$member_id, $plan_id, $start_date, $end_date]);
            $membership_id = $pdo->lastInsertId();

            // 3. Create Payment Record
            $payStmt = $pdo->prepare("INSERT INTO payments (member_id, membership_id, amount, payment_method, payment_date, status) VALUES (?, ?, ?, ?, NOW(), 'Paid')");
            $payStmt->execute([$member_id, $membership_id, $amount, $method]);
            $payment_id = $pdo->lastInsertId();

            // 4. Update member status to active
            $pdo->prepare("UPDATE members SET status = 'Active' WHERE id = ?")->execute([$member_id]);

            $pdo->commit();
            redirect("/payments/receipt.php?id=$payment_id");

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Transaction failed: " . $e->getMessage();
        }
    } else {
        $error = "Valid inputs are required.";
    }
}
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">New Payment & Subscription</h3>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Select Member</label>
            <select name="member_id" class="form-control searchable-select" required>
                <option value="">-- Choose Member --</option>
                <?php foreach ($members as $m): ?>
                    <option value="<?= $m->id ?>" <?= $m->id == $pre_member_id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m->full_name) ?> (<?= htmlspecialchars($m->email) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Select Membership Plan</label>
            <select name="plan_id" id="plan_id" class="form-control" required onchange="updateAmount()">
                <option value="" data-price="0">-- Choose Plan --</option>
                <?php foreach ($plans as $p): ?>
                    <option value="<?= $p->id ?>" data-price="<?= $p->price ?>">
                        <?= htmlspecialchars($p->plan_name) ?> - <?= formatCurrency($p->price) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Amount Paid</label>
                <input type="number" step="0.01" name="amount" id="amount" class="form-control" required readonly>
            </div>
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method" class="form-control">
                    <option value="Cash">Cash</option>
                    <option value="GCash">GCash</option>
                    <option value="Card">Card</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Complete Transaction</button>
    </form>
</div>

<script>
    function updateAmount() {
        var planSelect = document.getElementById('plan_id');
        var amountInput = document.getElementById('amount');
        var selectedOption = planSelect.options[planSelect.selectedIndex];
        var price = selectedOption.getAttribute('data-price');
        amountInput.value = price;
    }
</script>

<?php require_once '../includes/footer.php'; ?>