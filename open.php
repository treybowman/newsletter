<?php
$config = require __DIR__ . '/config.php';

// Validate token
$token = $_GET['token'] ?? '';
if (!$token) {
    header('Content-Type: image/gif');
    echo base64_decode("R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==");
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'],
        $config['db_pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Mark newsletter as opened
    $stmt = $pdo->prepare("UPDATE newsletter_logs SET opened = 1 WHERE open_token = :token");
    $stmt->execute(['token' => $token]);

} catch (PDOException $e) {
    // Silent fail — do not expose errors in a tracking pixel
}

// Return transparent 1x1 GIF
header('Content-Type: image/gif');
echo base64_decode("R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==");
exit;
