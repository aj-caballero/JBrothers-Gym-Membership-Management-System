<?php
// C:/Users/Kyle/GYM MEMBERSHIP/payments/add.php
$pageTitle = 'Record Payment & Membership';
require_once '../includes/header.php';
require_once '../config/paymongo.php';
require_once '../includes/paymongo.php';

// Pre-fill member if passed
$pre_member_id = $_GET['member_id'] ?? 0;

$membersStmt = $pdo->query("SELECT id, full_name, email FROM members WHERE status != 'Suspended' AND deleted_at IS NULL ORDER BY full_name ASC");
$members = $membersStmt->fetchAll();

$plansStmt = $pdo->query("SELECT * FROM membership_plans WHERE deleted_at IS NULL ORDER BY price ASC");
$plans = $plansStmt->fetchAll();

$supportsPayMongoMethod = paymentsSupportsPayMongo($pdo);
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
    $payMongoOutcome = trim((string) ($_POST['paymongo_outcome'] ?? 'paid'));

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

            if ($method === 'PayMongo') {
                if (!$supportsPayMongoMethod) {
                    throw new Exception('PayMongo method is not yet available in this database. Run paymongo_migration.sql first.');
                }

                if (PAYMONGO_CONFIGURED) {
                    // ── Real PayMongo API flow ──
                    // 1. Create a PENDING payment record (no membership yet)
                    $pendingCols   = ['member_id', 'amount', 'payment_method', 'payment_date', 'status'];
                    $pendingParams = [$member_id, $amount, 'PayMongo', 'Paid'];

                    if ($hasRequestedPlanColumn) {
                        array_splice($pendingCols, 1, 0, ['requested_plan_id']);
                        array_splice($pendingParams, 1, 0, [$plan_id]);
                    }
                    if ($hasGatewayColumns) {
                        $pendingCols[]   = 'gateway';
                        $pendingParams[] = 'PayMongo';
                        $pendingCols[]   = 'gateway_status';
                        $pendingParams[] = 'awaiting_payment_method';
                    }

                    // Build INSERT with NOW() for payment_date
                    $valParts = [];
                    $insertParams = [];
                    foreach ($pendingCols as $col) {
                        if ($col === 'payment_date') {
                            $valParts[] = 'NOW()';
                        } else {
                            $valParts[] = '?';
                            $insertParams[] = array_shift($pendingParams);
                        }
                    }
                    $insertSql = 'INSERT INTO payments (' . implode(', ', $pendingCols) . ') VALUES (' . implode(', ', $valParts) . ')';
                    $pdo->prepare($insertSql)->execute($insertParams);
                    $pendingPaymentId = $pdo->lastInsertId();

                    $pdo->commit();

                    // 2. Redirect to the PayMongo checkout initiator
                    redirect('/payments/paymongo_checkout.php?payment_id=' . $pendingPaymentId);

                } else {
                    // ── Simulation fallback (no real keys configured) ──
                    $sim = simulatePayMongoPayment($amount, $memberInfo->full_name ?? '', $memberInfo->email ?? '', $payMongoOutcome);
                    $gateway = $sim['gateway'];
                    $gatewayTxnId = $sim['transaction_id'];
                    $gatewayStatus = $sim['gateway_status'];
                    $gatewayPayload = json_encode($sim);

                    if ($gatewayStatus === 'failed') {
                        throw new Exception($sim['message'] . ' No membership was activated.');
                    }

                    if ($gatewayStatus === 'awaiting_payment_method') {
                        $paymentStatus = 'Pending';
                    }
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
                <select name="payment_method" id="payment_method" class="form-control" onchange="togglePayMongoOptions()">
                    <option value="Cash">Cash</option>
                    <option value="GCash">GCash</option>
                    <option value="Card">Card</option>
                    <?php if ($supportsPayMongoMethod): ?>
                        <option value="PayMongo">
                            PayMongo <?= PAYMONGO_CONFIGURED ? '(Live Checkout)' : '(Simulated)' ?>
                        </option>
                    <?php endif; ?>
                </select>
                <?php if (!$supportsPayMongoMethod): ?>
                    <small style="color:var(--text-muted);display:block;margin-top:6px;">
                        PayMongo option is hidden until paymongo_migration.sql is applied.
                    </small>
                <?php elseif (PAYMONGO_CONFIGURED): ?>
                    <small style="color:#22c55e;display:block;margin-top:6px;">
                        &#10003; Live PayMongo keys loaded (<?= PAYMONGO_MODE ?> mode). Member will be redirected to PayMongo checkout.
                    </small>
                <?php else: ?>
                    <small style="color:var(--text-muted);display:block;margin-top:6px;">
                        Simulation mode — add real keys in config/paymongo.php to go live.
                    </small>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!PAYMONGO_CONFIGURED): ?>
        <div id="paymongo-sim-group" class="form-group" style="display:none;">
            <label>PayMongo Simulation Outcome</label>
            <select name="paymongo_outcome" class="form-control">
                <option value="paid">Paid (membership activates)</option>
                <option value="pending">Pending (payment recorded, no activation)</option>
                <option value="failed">Failed (no record committed)</option>
            </select>
            <small style="color:var(--text-muted);display:block;margin-top:6px;">
                Simulation mode &mdash; add real keys in <code>config/paymongo.php</code> to use real checkout.
            </small>
        </div>
        <?php endif; ?>

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

    function togglePayMongoOptions() {
        var method  = document.getElementById('payment_method');
        var simGroup = document.getElementById('paymongo-sim-group');
        if (!method) return;
        if (simGroup) simGroup.style.display = method.value === 'PayMongo' ? 'block' : 'none';
    }

    togglePayMongoOptions();
</script>

<?php require_once '../includes/footer.php'; ?>