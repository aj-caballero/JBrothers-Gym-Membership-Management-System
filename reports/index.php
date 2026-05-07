<?php
// C:/Users/Kyle/GYM MEMBERSHIP/reports/index.php

// Handle CSV export for members BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_members'])) {
    require_once '../config/config.php';
    require_once '../config/database.php';
    
    $status_filter = $_POST['member_status'] ?? '';
    
    $whereSql = '';
    $params = [];
    
    if (!empty($status_filter) && $status_filter !== 'all') {
        $whereSql = "WHERE status = ?";
        $params[] = $status_filter;
    }
    
    $sql = "SELECT id, full_name, email, phone, address, date_of_birth, gender, join_date, status, membership_id FROM members $whereSql ORDER BY full_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $members = $stmt->fetchAll();
    
    // Generate CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="members_export_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, ['ID', 'Full Name', 'Email', 'Phone', 'Address', 'Date of Birth', 'Gender', 'Join Date', 'Status', 'Membership ID']);
    
    // CSV Data
    foreach ($members as $member) {
        fputcsv($output, [
            $member->id,
            $member->full_name,
            $member->email,
            $member->phone,
            $member->address,
            $member->date_of_birth,
            $member->gender,
            $member->join_date,
            $member->status,
            $member->membership_id
        ]);
    }
    
    fclose($output);
    exit;
}

// Handle CSV export for payments BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_payments'])) {
    require_once '../config/config.php';
    require_once '../config/database.php';
    
    $date_from = $_POST['payment_date_from'] ?? '';
    $date_to = $_POST['payment_date_to'] ?? '';
    
    $whereSql = '';
    $params = [];
    
    if (!empty($date_from) && !empty($date_to)) {
        $whereSql = "WHERE DATE(payment_date) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
    } elseif (!empty($date_from)) {
        $whereSql = "WHERE DATE(payment_date) = ?";
        $params[] = $date_from;
    }
    
    $sql = "SELECT p.id, p.amount, p.payment_method, p.payment_date, p.status, m.full_name as member_name, m.email as member_email, mp.plan_name FROM payments p JOIN members m ON p.member_id = m.id LEFT JOIN membership_plans mp ON p.requested_plan_id = mp.id $whereSql ORDER BY p.payment_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();
    
    // Generate CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="payments_export_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, ['Payment ID', 'Member Name', 'Member Email', 'Amount', 'Plan', 'Payment Method', 'Payment Date', 'Status']);
    
    // CSV Data
    foreach ($payments as $payment) {
        fputcsv($output, [
            $payment->id,
            $payment->member_name,
            $payment->member_email,
            number_format($payment->amount, 2),
            $payment->plan_name ?? 'N/A',
            $payment->payment_method,
            $payment->payment_date,
            $payment->status
        ]);
    }
    
    fclose($output);
    exit;
}

// Handle CSV export for attendance logs BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_attendance'])) {
    require_once '../config/config.php';
    require_once '../config/database.php';

    $date_from = $_POST['attendance_date_from'] ?? '';
    $date_to = $_POST['attendance_date_to'] ?? '';

    $whereSql = '';
    $params = [];

    if (!empty($date_from) && !empty($date_to)) {
        $whereSql = "WHERE DATE(a.time_in) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
    } elseif (!empty($date_from)) {
        $whereSql = "WHERE DATE(a.time_in) = ?";
        $params[] = $date_from;
    }

    $sql = "SELECT a.id, a.time_in, a.time_out, m.full_name as member_name, m.email as member_email
            FROM attendance_logs a
            JOIN members m ON a.member_id = m.id
            $whereSql
            ORDER BY a.time_in DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    // Generate CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="attendance_export_' . date('Y-m-d_H-i-s') . '.csv"');

    $output = fopen('php://output', 'w');

    // CSV Headers
    fputcsv($output, ['Log ID', 'Member Name', 'Member Email', 'Time In', 'Time Out', 'Duration (Minutes)']);

    // CSV Data
    foreach ($logs as $log) {
        $duration = '-';
        if (!empty($log->time_out)) {
            $in = strtotime($log->time_in);
            $out = strtotime($log->time_out);
            $duration = round(abs($out - $in) / 60, 2);
        }

        fputcsv($output, [
            $log->id,
            $log->member_name,
            $log->member_email,
            $log->time_in,
            $log->time_out ?? '-',
            $duration
        ]);
    }

    fclose($output);
    exit;
}

// Handle CSV export for revenue BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_revenue'])) {
    require_once '../config/config.php';
    require_once '../config/database.php';

    $date_from = $_POST['revenue_date_from'] ?? '';
    $date_to = $_POST['revenue_date_to'] ?? '';

    $whereSql = '';
    $params = [];

    if (!empty($date_from) && !empty($date_to)) {
        $whereSql = "WHERE DATE(p.payment_date) BETWEEN ? AND ? AND p.status = 'Paid'";
        $params[] = $date_from;
        $params[] = $date_to;
    } elseif (!empty($date_from)) {
        $whereSql = "WHERE DATE(p.payment_date) = ? AND p.status = 'Paid'";
        $params[] = $date_from;
    } else {
        $whereSql = "WHERE p.status = 'Paid'";
    }

    $sql = "SELECT p.id, p.amount, p.payment_method, p.payment_date, p.status,
                   m.full_name as member_name, m.email as member_email,
                   mp.plan_name
            FROM payments p
            JOIN members m ON p.member_id = m.id
            LEFT JOIN membership_plans mp ON p.requested_plan_id = mp.id
            $whereSql
            ORDER BY p.payment_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $revenues = $stmt->fetchAll();

    // Generate CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="revenue_export_' . date('Y-m-d_H-i-s') . '.csv"');

    $output = fopen('php://output', 'w');

    // CSV Headers
    fputcsv($output, ['Payment ID', 'Member Name', 'Member Email', 'Plan', 'Payment Method', 'Payment Date', 'Status', 'Amount']);

    $totalRevenue = 0;

    // CSV Data
    foreach ($revenues as $revenue) {
        $totalRevenue += (float) $revenue->amount;

        fputcsv($output, [
            $revenue->id,
            $revenue->member_name,
            $revenue->member_email,
            $revenue->plan_name ?? 'N/A',
            $revenue->payment_method,
            $revenue->payment_date,
            $revenue->status,
            number_format($revenue->amount, 2)
        ]);
    }

    fputcsv($output, []);
    fputcsv($output, ['Total Revenue', '', '', '', '', '', '', number_format($totalRevenue, 2)]);

    fclose($output);
    exit;
}

// Handle database backup download BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_backup'])) {
    require_once '../config/config.php';
    require_once '../config/database.php';

    $dumpValue = function ($value) use ($pdo) {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $pdo->quote((string) $value);
    };

    $backup = [];
    $backup[] = '-- Gym Membership database backup';
    $backup[] = '-- Generated: ' . date('Y-m-d H:i:s');
    $backup[] = '';

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $backup[] = 'DROP TABLE IF EXISTS `' . $table . '`;';

        $createStmt = $pdo->query('SHOW CREATE TABLE `' . $table . '`');
        $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
        $createSql = $createRow['Create Table'] ?? array_values($createRow)[1] ?? '';

        $backup[] = $createSql . ';';
        $backup[] = '';

        $rows = $pdo->query('SELECT * FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $columnList = implode('`, `', $columns);

            foreach ($rows as $row) {
                $values = [];
                foreach ($columns as $column) {
                    $values[] = $dumpValue($row[$column]);
                }

                $backup[] = 'INSERT INTO `' . $table . '` (`' . $columnList . '`) VALUES (' . implode(', ', $values) . ');';
            }
            $backup[] = '';
        }
    }

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="gym_db_backup_' . date('Y-m-d_H-i-s') . '.sql"');
    echo implode("\n", $backup);
    exit;
}

$pageTitle = 'Reports & Analytics';
require_once '../includes/header.php';

// Base monthly revenue chart data
$months = [];
$revenues = [];

// Last 6 months 
for ($i = 5; $i >= 0; $i--) {
    $monthDate = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M Y', strtotime("-$i months"));
    
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE DATE_FORMAT(payment_date, '%Y-%m') = ? AND status = 'Paid'");
    $stmt->execute([$monthDate]);
    $result = $stmt->fetch();
    
    $months[] = $monthLabel;
    $revenues[] = $result->total ?? 0;
}

$monthsJson = json_encode($months);
$revenuesJson = json_encode($revenues);

// Quick Stats
$totalMembers = $pdo->query("SELECT COUNT(*) as total FROM members")->fetch()->total;
$activeMembers = $pdo->query("SELECT COUNT(*) as total FROM members WHERE status='Active'")->fetch()->total;
$expiredMembers = $pdo->query("SELECT COUNT(*) as total FROM members WHERE status='Expired'")->fetch()->total;

?>

<div class="stats-grid">
    <div class="stat-card" style="background: linear-gradient(145deg, #1e1e2e, #1a2a44);">
        <div class="stat-icon" style="color: #007bff; background: rgba(0, 123, 255, 0.1);"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3>Total Members</h3>
            <p class="stat-value"><?= number_format($totalMembers) ?></p>
        </div>
    </div>
    <div class="stat-card" style="background: linear-gradient(145deg, #1e1e2e, #143324);">
        <div class="stat-icon" style="color: #28a745; background: rgba(40, 167, 69, 0.1);"><i class="fas fa-user-check"></i></div>
        <div class="stat-info">
            <h3>Active Members</h3>
            <p class="stat-value"><?= number_format($activeMembers) ?></p>
        </div>
    </div>
    <div class="stat-card" style="background: linear-gradient(145deg, #1e1e2e, #441a1a);">
        <div class="stat-icon" style="color: #dc3545; background: rgba(220, 53, 69, 0.1);"><i class="fas fa-user-times"></i></div>
        <div class="stat-info">
            <h3>Expired Members</h3>
            <p class="stat-value"><?= number_format($expiredMembers) ?></p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Revenue Overview (Last 6 Months)</h3>
    </div>
    <div style="height: 350px;">
        <canvas id="revenueChart"></canvas>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3 class="card-title">Export Data</h3>
    </div>
    
    <div style="padding: 22px;">
        <h4 style="margin-bottom: 16px; font-size: 14px; font-weight: 600;">Members Report</h4>
        <form method="POST" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Filter by Status</label>
                <select name="member_status" class="form-control">
                    <option value="all">All Members</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Suspended">Suspended</option>
                </select>
            </div>
            <button type="submit" name="export_members" value="1" class="btn btn-primary">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </form>
        <p style="color: var(--text-muted); margin-top: 12px; font-size: 13px;">Download a CSV file containing member information for backup or analysis.</p>
    </div>

    <hr style="margin: 0; border-color: var(--border-strong);">

    <div style="padding: 22px;">
        <h4 style="margin-bottom: 16px; font-size: 14px; font-weight: 600;">Payments Report</h4>
        <form method="POST" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Date From</label>
                <input type="date" name="payment_date_from" class="form-control">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>To</label>
                <input type="date" name="payment_date_to" class="form-control">
            </div>
            <button type="submit" name="export_payments" value="1" class="btn btn-primary">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </form>
        <p style="color: var(--text-muted); margin-top: 12px; font-size: 13px;">Download a CSV file containing payment transactions. Use date range to filter specific periods.</p>
    </div>

    <hr style="margin: 0; border-color: var(--border-strong);">

    <div style="padding: 22px;">
        <h4 style="margin-bottom: 16px; font-size: 14px; font-weight: 600;">Attendance Logs Report</h4>
        <form method="POST" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Date From</label>
                <input type="date" name="attendance_date_from" class="form-control">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>To</label>
                <input type="date" name="attendance_date_to" class="form-control">
            </div>
            <button type="submit" name="export_attendance" value="1" class="btn btn-primary">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </form>
        <p style="color: var(--text-muted); margin-top: 12px; font-size: 13px;">Download a CSV file containing attendance logs filtered by the selected date range.</p>
    </div>

    <hr style="margin: 0; border-color: var(--border-strong);">

    <div style="padding: 22px;">
        <h4 style="margin-bottom: 16px; font-size: 14px; font-weight: 600;">Revenue Report</h4>
        <form method="POST" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Date From</label>
                <input type="date" name="revenue_date_from" class="form-control">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>To</label>
                <input type="date" name="revenue_date_to" class="form-control">
            </div>
            <button type="submit" name="export_revenue" value="1" class="btn btn-primary">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </form>
        <p style="color: var(--text-muted); margin-top: 12px; font-size: 13px;">Download a revenue CSV based on payment records, with a total revenue summary at the end.</p>
    </div>

    <hr style="margin: 0; border-color: var(--border-strong);">

    <div style="padding: 22px;">
        <h4 style="margin-bottom: 16px; font-size: 14px; font-weight: 600;">Database Backup</h4>
        <form method="POST" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
            <button type="submit" name="export_backup" value="1" class="btn btn-primary">
                <i class="fas fa-database"></i> Download SQL Backup
            </button>
        </form>
        <p style="color: var(--text-muted); margin-top: 12px; font-size: 13px;">Download a full SQL backup of the current database structure and data.</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    // Create gradient
    let gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(0, 255, 204, 0.5)');
    gradient.addColorStop(1, 'rgba(0, 255, 204, 0.05)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= $monthsJson ?>,
            datasets: [{
                label: 'Revenue (PHP)',
                data: <?= $revenuesJson ?>,
                borderColor: '#00ffcc',
                backgroundColor: gradient,
                borderWidth: 2,
                pointBackgroundColor: '#00ffcc',
                pointBorderColor: '#fff',
                pointRadius: 4,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#a0a0b0' }
                },
                x: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#a0a0b0' }
                }
            }
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
