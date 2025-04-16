<?php
if (!isset($_GET['token'])) exit;

$dbHost = 'localhost';
$dbName = 'your_database_name';
$dbUser = 'your_database_user';
$dbPass = 'your_database_password';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("UPDATE newsletter_logs SET opened = 1 WHERE open_token = :token");
    $stmt->execute(['token' => $token]);

} catch (PDOException $e) {
    // Silent fail
}

// Send transparent 1x1 gif
header('Content-Type: image/gif');
echo base64_decode("R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==");
exit;
?>
