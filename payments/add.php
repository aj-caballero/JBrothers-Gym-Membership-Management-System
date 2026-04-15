<?php
// C:/Users/Kyle/GYM MEMBERSHIP/payments/add.php
$pageTitle = 'Record Payment & Membership';
require_once '../includes/header.php';

// Pre-fill member if passed
$pre_member_id = $_GET['member_id'] ?? 0;

$membersStmt = $pdo->query("SELECT id, full_name, email FROM members WHERE status != 'Suspended' AND deleted_at IS NULL ORDER BY full_name ASC");
$members = $membersStmt->fetchAll();

$plansStmt = $pdo->query("SELECT * FROM membership_plans WHERE deleted_at IS NULL ORDER BY price ASC");
$plans = $plansStmt->fetchAll();

$supportsMangoMethod = paymentsSupportsMangoPay($pdo);
$hasRequestedPlanColumn = dbHasColumn($pdo, 'payments', 'requested_plan_id');
$hasGatewayColumns = dbHasColumn($pdo, 'payments', 'gateway')
    && dbHasColumn($pdo, 'payments', 'gateway_transaction_id')
    && dbHasColumn($pdo, 'payments', 'gateway_status')
    && dbHasColumn($pdo, 'payments', 'gateway_payload');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = (int) $_POST['member_id'];
    $plan_id = (int) $_POST['plan_id'];
    $amount = (float) $_POST['amount'];
    $method = trim((string) ($_POST['payment_method'] ?? 'Cash'));
    $mangoOutcome = trim((string) ($_POST['mangopay_outcome'] ?? 'succeeded'));

    if ($member_id > 0 && $plan_id > 0 && $amount >= 0) {
        try {
            $pdo->beginTransaction();

            $memberStmt = $pdo->prepare("SELECT full_name, email FROM members WHERE id = ? LIMIT 1");
            $memberStmt->execute([$member_id]);
            $memberInfo = $memberStmt->fetch();
            if (!$memberInfo) {
                throw new Exception('Selected member was not found.');
            }

            // 1. Get plan details for duration
            $planStmt = $pdo->prepare("SELECT duration_days FROM membership_plans WHERE id = ?");
            $planStmt->execute([$plan_id]);
            $plan = $planStmt->fetch();
            if (!$plan) {
                throw new Exception('Selected plan was not found.');
            }

            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime("+$plan->duration_days days"));

            $gateway = null;
            $gatewayTxnId = null;
            $gatewayStatus = null;
            $gatewayPayload = null;
            $paymentStatus = 'Paid';
            $membership_id = null;

            if ($method === 'MangoPay') {
                if (!$supportsMangoMethod) {
                    throw new Exception('MangoPay method is not yet available in this database. Run mangopay_simulation_migration.sql first.');
                }
                $sim = simulateMangoPayPayment($amount, $memberInfo->full_name ?? '', $memberInfo->email ?? '', $mangoOutcome);
                $gateway = $sim['gateway'];
                $gatewayTxnId = $sim['transaction_id'];
                $gatewayStatus = $sim['gateway_status'];
                $gatewayPayload = json_encode($sim);

                if ($gatewayStatus === 'FAILED') {
                    throw new Exception($sim['message'] . ' No membership was activated.');
                }

                if ($gatewayStatus === 'PENDING') {
                    $paymentStatus = 'Pending';
                }
            }

            if ($paymentStatus === 'Paid') {
                // 2. Create Membership Record only for paid transactions
                $msStmt = $pdo->prepare("INSERT INTO memberships (member_id, plan_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'Active')");
                $msStmt->execute([$member_id, $plan_id, $start_date, $end_date]);
                $membership_id = $pdo->lastInsertId();
            }

            // 3. Create Payment Record (schema-aware)
            $columns = ['member_id'];
            $params = [$member_id];

            if ($hasRequestedPlanColumn) {
                $columns[] = 'requested_plan_id';
                $params[] = $plan_id;
            }

            $columns[] = 'membership_id';
            $params[] = $membership_id;

            $columns[] = 'amount';
            $params[] = $amount;

            $columns[] = 'payment_method';
            $params[] = $method;

            $columns[] = 'payment_date';
            $columns[] = 'status';
            $params[] = $paymentStatus;

            if ($hasGatewayColumns) {
                $columns[] = 'gateway';
                $columns[] = 'gateway_transaction_id';
                $columns[] = 'gateway_status';
                $columns[] = 'gateway_payload';
                $params[] = $gateway;
                $params[] = $gatewayTxnId;
                $params[] = $gatewayStatus;
                $params[] = $gatewayPayload;
            }

            $placeholders = array_fill(0, count($params), '?');
            $insertSql = "INSERT INTO payments (" . implode(', ', $columns) . ") VALUES (";

            // payment_date uses NOW() while all others use parameter placeholders
            $valueParts = [];
            $paramIndex = 0;
            foreach ($columns as $col) {
                if ($col === 'payment_date') {
                    $valueParts[] = 'NOW()';
                } else {
                    $valueParts[] = $placeholders[$paramIndex++];
                }
            }
            $insertSql .= implode(', ', $valueParts) . ')';

            $payStmt = $pdo->prepare($insertSql);
            $payStmt->execute($params);
            $payment_id = $pdo->lastInsertId();

            // 4. Update member status only on successful payment
            if ($paymentStatus === 'Paid') {
                $pdo->prepare("UPDATE members SET status = 'Active' WHERE id = ?")->execute([$member_id]);
            }

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
                <select name="payment_method" id="payment_method" class="form-control" onchange="toggleMangoOptions()">
                    <option value="Cash">Cash</option>
                    <option value="GCash">GCash</option>
                    <option value="Card">Card</option>
                    <?php if ($supportsMangoMethod): ?>
                        <option value="MangoPay">MangoPay (Simulated)</option>
                    <?php endif; ?>
                </select>
                <?php if (!$supportsMangoMethod): ?>
                    <small style="color:var(--text-muted);display:block;margin-top:6px;">
                        MangoPay simulation is hidden until migration is applied.
                    </small>
                <?php endif; ?>
            </div>
        </div>

        <div id="mangopay-sim-group" class="form-group" style="display:none;">
            <label>MangoPay Simulation Outcome</label>
            <select name="mangopay_outcome" class="form-control">
                <option value="succeeded">Succeeded (membership activates)</option>
                <option value="pending">Pending (payment recorded, no activation)</option>
                <option value="failed">Failed (no record committed)</option>
            </select>
            <small style="color:var(--text-muted);display:block;margin-top:6px;">
                Simulation mode lets staff test payment flows without real gateway calls.
            </small>
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

    function toggleMangoOptions() {
        var method = document.getElementById('payment_method');
        var simGroup = document.getElementById('mangopay-sim-group');
        if (!method || !simGroup) return;
        simGroup.style.display = method.value === 'MangoPay' ? 'block' : 'none';
    }

    toggleMangoOptions();
</script>

<?php require_once '../includes/footer.php'; ?>