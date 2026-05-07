<?php
// includes/mailer.php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!defined('MAIL_HOST')) {
    define('MAIL_HOST', getenv('MAIL_HOST') ?: '');
}
if (!defined('MAIL_PORT')) {
    define('MAIL_PORT', (int) (getenv('MAIL_PORT') ?: 587));
}
if (!defined('MAIL_USERNAME')) {
    define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: '');
}
if (!defined('MAIL_PASSWORD')) {
    define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
}
if (!defined('MAIL_ENCRYPTION')) {
    define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: 'tls');
}
if (!defined('MAIL_FROM_EMAIL')) {
    define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL') ?: 'no-reply@localhost');
}
if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: APP_NAME);
}

/**
 * Compute remaining days before expiry. 0 means expires today.
 */
function membershipDaysRemaining($endDate) {
    $today = new DateTime(date('Y-m-d'));
    $end = new DateTime(date('Y-m-d', strtotime($endDate)));
    $diff = (int) $today->diff($end)->format('%r%a');
    return max($diff, 0);
}

/**
 * Send expiry reminder email to a member.
 */
function sendMembershipExpiryReminderEmail($pdo, $member, $membership) {
    $memberEmail = trim((string)($member->email ?? ''));
    if ($memberEmail === '') {
        return ['ok' => false, 'message' => 'Member has no email address.'];
    }

    $daysLeft = membershipDaysRemaining($membership->end_date);
    $gymSettings = getGymSettings($pdo);
    $gymName = $gymSettings->gym_name ?? APP_NAME;

    $subject = $daysLeft === 0
        ? $gymName . ' Membership Expires Today'
        : $gymName . ' Membership Expiry Reminder - ' . $daysLeft . ' Day(s) Left';

    $safeName = htmlspecialchars((string)($member->full_name ?? 'Member'));
    $safePlan = htmlspecialchars((string)($membership->plan_name ?? 'Current Plan'));
    $safeEnd = htmlspecialchars(date('F d, Y', strtotime($membership->end_date)));
    $safeGym = htmlspecialchars((string)$gymName);

    $htmlBody = '
        <div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;color:#1f2937;line-height:1.6;">
            <h2 style="margin:0 0 12px;">Membership Expiry Reminder</h2>
            <p>Hello ' . $safeName . ',</p>
            <p>This is a reminder from <strong>' . $safeGym . '</strong> that your <strong>' . $safePlan . '</strong> membership will expire on <strong>' . $safeEnd . '</strong>.</p>
            <p><strong>Remaining days:</strong> ' . $daysLeft . ' day(s)</p>
            <p>Please renew before expiry to continue uninterrupted access.</p>
            <p style="margin-top:22px;">Thank you,<br>' . $safeGym . '</p>
        </div>
    ';

    $textBody = "Membership Expiry Reminder\n"
        . "Hello " . ($member->full_name ?? 'Member') . ",\n\n"
        . "Your " . ($membership->plan_name ?? 'Current Plan') . " membership will expire on "
        . date('F d, Y', strtotime($membership->end_date)) . ".\n"
        . "Remaining days: " . $daysLeft . " day(s).\n\n"
        . "Please renew before expiry to continue uninterrupted access.\n\n"
        . "Thank you,\n" . $gymName;

    try {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';

        // Use SMTP only when credentials are provided; fallback to PHP mail() otherwise.
        if (MAIL_HOST !== '' && MAIL_USERNAME !== '') {
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            $mail->Port = MAIL_PORT;

            if (MAIL_ENCRYPTION === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif (MAIL_ENCRYPTION === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
        }

        $fromEmail = MAIL_FROM_EMAIL;
        $fromName = MAIL_FROM_NAME;
        if (!empty($gymSettings->contact_email) && filter_var($gymSettings->contact_email, FILTER_VALIDATE_EMAIL)) {
            $fromEmail = $gymSettings->contact_email;
            $fromName = $gymName;
        }

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($memberEmail, (string)($member->full_name ?? 'Member'));
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody;

        $mail->send();
        return ['ok' => true, 'message' => 'Reminder email sent.', 'days_left' => $daysLeft];
    } catch (Exception $e) {
        return ['ok' => false, 'message' => 'Mailer error: ' . $mail->ErrorInfo];
    }
}
