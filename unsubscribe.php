<?php
if (!isset($_GET['email'])) {
    echo "Missing email.";
    exit;
}

$email = filter_var($_GET['email'], FILTER_VALIDATE_EMAIL);
if (!$email) {
    echo "Invalid email address.";
    exit;
}

$dbHost = 'localhost';
$dbName = 'treyb_cobbtalk';
$dbUser = 'treyb_cobb';
$dbPass = 'MaxwellB0wman';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("INSERT IGNORE INTO unsubscribed_emails (email) VALUES (:email)");
    $stmt->execute(['email' => $email]);

    // Send confirmation
    $subject = "You've Unsubscribed from CobbTalk Weekly Digest";
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: CobbTalk <no-reply@cobbtalk.com>\r\n";
    $message = "<p>You have been successfully unsubscribed from CobbTalk newsletters.</p>
                <p>If this was a mistake, please reach out to an admin to opt back in.</p>";
    mail($email, $subject, $message, $headers);

    echo "<h2>You have been unsubscribed and a confirmation email has been sent.</h2>";
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
