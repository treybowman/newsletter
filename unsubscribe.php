<?php

$config = require __DIR__ . '/config.php';

$siteName = htmlspecialchars($config['site_name']);
$siteURL  = rtrim($config['site_url'], '/');

// Validate query params
if (!isset($_GET['email']) || !isset($_GET['token'])) {
    echo "Missing email or token.";
    exit;
}

$email = filter_var($_GET['email'], FILTER_VALIDATE_EMAIL);
$token = preg_replace('/[^a-f0-9]/i', '', $_GET['token']); // simple sanitization

if (!$email || !$token) {
    echo "Invalid email or token.";
    exit;
}

// Validate token file
$tokenFile = __DIR__ . "/tokens/" . md5($email) . ".txt";
if (!file_exists($tokenFile) || trim(file_get_contents($tokenFile)) !== $token) {
    echo "Invalid or expired unsubscribe token.";
    exit;
}

// Proceed with unsubscribe
try {
    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'],
        $config['db_pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("INSERT IGNORE INTO unsubscribed_emails (email) VALUES (:email)");
    $stmt->execute(['email' => $email]);

    // Clean up token file
    unlink($tokenFile);

    // Confirmation email
    $subject = "You've Unsubscribed from {$siteName} Weekly Digest";
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html; charset=UTF-8\r\n";
    $headers .= "From: {$siteName} <no-reply@{$siteURL}>\r\n";

    $message = "<p>You have been successfully unsubscribed from {$siteName} newsletters.</p>
                <p>If this was a mistake, please contact an admin to opt back in.</p>";

    mail($email, $subject, $message, $headers);

    echo "<h2>You have been unsubscribed. A confirmation email has been sent.</h2>";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
