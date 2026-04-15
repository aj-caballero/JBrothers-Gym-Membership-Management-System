<?php
// qrcode.php - authenticated QR image endpoint powered by Endroid
require_once __DIR__ . '/config/config.php';
require_login();

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'QR generator dependency is missing. Run composer install.';
    exit;
}

require_once $autoloadPath;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

$data = trim((string) ($_GET['data'] ?? ''));
if ($data === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Missing QR payload.';
    exit;
}

// Keep QR payload compact to avoid oversized matrix output.
if (strlen($data) > 256) {
    $data = substr($data, 0, 256);
}

$qrCode = QrCode::create($data)
    ->setEncoding(new Encoding('UTF-8'))
    ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
    ->setSize(320)
    ->setMargin(12)
    ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin)
    ->setForegroundColor(new Color(0, 0, 0))
    ->setBackgroundColor(new Color(255, 255, 255));

$writer = new PngWriter();
$result = $writer->write($qrCode);

header('Content-Type: ' . $result->getMimeType());
header('Cache-Control: private, max-age=3600');
echo $result->getString();
