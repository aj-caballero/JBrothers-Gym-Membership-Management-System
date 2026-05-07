<?php
// C:/Users/Kyle/GYM MEMBERSHIP/attendance/history.php
$pageTitle = 'Attendance History';
require_once '../includes/header.php';

// Pagination and Filters
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$searchParams = [];
$whereClauses = [];

if (!empty($date_from) || !empty($date_to)) {
    if (!empty($date_from) && !empty($date_to)) {
        $whereClauses[] = "DATE(a.time_in) BETWEEN ? AND ?";
        $searchParams[] = $date_from;
        $searchParams[] = $date_to;
    } elseif (!empty($date_from)) {
        $whereClauses[] = "DATE(a.time_in) = ?";
        $searchParams[] = $date_from;
    }
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = "WHERE " . implode(' AND ', $whereClauses);
}

// Get total for pagination
$stmtTotal = $pdo->prepare("SELECT COUNT(*) as total FROM attendance_logs a $whereSql");
$stmtTotal->execute($searchParams);
$total = $stmtTotal->fetch()->total;
$totalPages = ceil($total / $limit);

// Get records
$sql = "SELECT a.*, m.full_name 
        FROM attendance_logs a 
        JOIN members m ON a.member_id = m.id 
        $whereSql 
        ORDER BY a.time_in DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($searchParams);
$logs = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Detailed Logs</h3>
    </div>

    <form method="GET" class="form-row" style="margin-bottom: 20px; align-items:flex-end; gap: 10px; flex-wrap: wrap;">
        <div class="form-group" style="margin-bottom:0;">
            <label>Date From</label>
            <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label>To</label>
            <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <button type="submit" class="btn btn-primary" style="margin-bottom: 0;">Filter</button>
            <a href="history.php" class="btn" style="background:var(--bg-surface-hover); color:#fff;">Clear</a>
        </div>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Member Name</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Duration</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= formatDate($log->time_in) ?></td>
                        <td><?= htmlspecialchars($log->full_name) ?></td>
                        <td><?= date('h:i A', strtotime($log->time_in)) ?></td>
                        <td><?= $log->time_out ? date('h:i A', strtotime($log->time_out)) : '-' ?></td>
                        <td>
                            <?php 
                                if ($log->time_out) {
                                    $in = strtotime($log->time_in);
                                    $out = strtotime($log->time_out);
                                    $diff = round(abs($out - $in) / 60,2);
                                    if ($diff > 60) {
                                        echo floor($diff / 60) . ' hr ' . ($diff % 60) . ' min';
                                    } else {
                                        echo $diff . ' mins';
                                    }
                                } else {
                                    echo '-';
                                }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="5">No attendance logs found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Basic Pagination -->
    <?php if ($totalPages > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>" class="btn btn-sm <?= ($page == $i) ? 'btn-primary' : '' ?>" style="border: 1px solid var(--border);"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
