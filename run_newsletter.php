<?php

$config = require __DIR__ . '/config.php';

// Protect with the log_token
if (!isset($_GET['key']) || $_GET['key'] !== $config['log_token']) {
    http_response_code(403);
    exit('Forbidden');
}

define('TEST_MODE', false);
define('TEST_EMAIL', 'support@YourSite.com');

// Fetch ATOM feeds
function fetchFeed($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'YourSiteBot/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

$topXml = simplexml_load_string(fetchFeed('https://YourSite.com/atom/discussions?sort=top'));
$newXml = simplexml_load_string(fetchFeed('https://YourSite.com/atom/discussions?sort=newest'));

if (!$topXml || !$newXml) {
    die('<h2>Error: Could not load one or both feeds.</h2>');
}

// Connect to DB and fetch logo
try {
    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'],
        $config['db_pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT value FROM ct_settings WHERE `key` = 'logo_path'");
    $logo = $stmt->fetchColumn();

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Build email HTML
mb_internal_encoding("UTF-8");
ob_start();
?>
<!-- YourSite Weekly Email Digest -->
<table cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: auto; font-family: Arial, sans-serif; color: #333;">
    <tr>
        <td align="center" style="padding: 20px;">
            <img src="https://YourSite.com/assets/<?= htmlspecialchars($logo) ?>" alt="YourSite Logo" width="200" style="margin-bottom: 20px;">
            <h2 style="color: #007bff; margin: 0;">📬 YourSite Weekly Digest</h2>
            <p style="font-size: 16px;">Here’s a roundup of the hottest and newest discussions from Cobb County this week.</p>
        </td>
    </tr>

    <tr><td><h3 style="padding: 20px 20px 0; color: #e67e22;">🔥 Top Discussions</h3></td></tr>
    <?php
    $count = 0;
    foreach ($topXml->entry as $entry):
        if (++$count > 5) break;
        $title = htmlspecialchars((string)$entry->title);
        $link = (string)$entry->link['href'];
        $link .= (strpos($link, '?') !== false ? '&' : '?') . 'utm_source=newsletter&utm_medium=email&utm_campaign=YourSite_digest&utm_content={{username}}';
        $summary = strip_tags((string)$entry->summary);
        $summaryShort = mb_substr($summary, 0, 200) . (mb_strlen($summary) > 200 ? '…' : '');
    ?>
        <tr>
            <td style="padding: 10px 20px;">
                <h4 style="margin-bottom: 5px;"><a href="<?= $link ?>" style="color: #007bff; text-decoration: none;"><?= $title ?></a></h4>
                <p style="margin-top: 0; font-size: 14px;"><?= $summaryShort ?></p>
            </td>
        </tr>
    <?php endforeach; ?>

    <tr><td><h3 style="padding: 20px 20px 0; color: #27ae60;">🆕 Newest Posts</h3></td></tr>
    <?php
    $count = 0;
    foreach ($newXml->entry as $entry):
        if (++$count > 5) break;
        $title = htmlspecialchars((string)$entry->title);
        $link = (string)$entry->link['href'];
        $link .= (strpos($link, '?') !== false ? '&' : '?') . 'utm_source=newsletter&utm_medium=email&utm_campaign=YourSite_digest&utm_content={{username}}';
        $summary = strip_tags((string)$entry->summary);
        $summaryShort = mb_substr($summary, 0, 200) . (mb_strlen($summary) > 200 ? '…' : '');
    ?>
        <tr>
            <td style="padding: 10px 20px;">
                <h4 style="margin-bottom: 5px;"><a href="<?= $link ?>" style="color: #007bff; text-decoration: none;"><?= $title ?></a></h4>
                <p style="margin-top: 0; font-size: 14px;"><?= $summaryShort ?></p>
            </td>
        </tr>
    <?php endforeach; ?>

    <tr>
        <td align="center" style="padding: 20px; font-size: 14px; color: #777;">
            <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
            <p>Want more? Visit <a href="https://YourSite.com" style="color: #007bff;">YourSite.com</a> to join the conversation.</p>
            <p style="font-size:12px; color:#aaa;">No longer want to receive this? <a href="https://YourSite.com/newsletter/unsubscribe.php?email={{email}}&token={{token}}">Unsubscribe here</a>.</p>
            <img src="https://YourSite.com/newsletter/open.php?token={{token}}" width="1" height="1" alt="." />
        </td>
    </tr>
</table>
<?php
$htmlOutput = ob_get_clean();

// Save snapshot of newsletter
$folder = __DIR__ . '/newsletters';
if (!is_dir($folder)) mkdir($folder, 0777, true);
file_put_contents($folder . '/newsletter_' . date('Y-m-d') . '.html', mb_convert_encoding($htmlOutput, 'UTF-8', 'UTF-8'));

// Get unsubscribed list
$unsubscribed = $pdo->query("SELECT email FROM unsubscribed_emails")->fetchAll(PDO::FETCH_COLUMN, 0);

// Get all users with emails
$users = $pdo->query("SELECT username, email FROM ct_users WHERE email IS NOT NULL AND email != ''")->fetchAll(PDO::FETCH_ASSOC);

$subject = "Your Weekly YourSite Digest";
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: YourSite <no-reply@YourSite.com>\r\n";

$sentCount = 0;
foreach ($users as $user) {
    $to = $user['email'];
    $username = htmlspecialchars($user['username']);

    if (TEST_MODE && $to !== TEST_EMAIL) continue;
    if (in_array($to, $unsubscribed)) continue;

    $token = bin2hex(random_bytes(16));
    file_put_contents(__DIR__ . "/tokens/" . md5($to) . ".txt", $token);

    $personalizedHtml = str_replace(
        ['{{username}}', '{{email}}', '{{token}}'],
        [$username, $to, $token],
        $htmlOutput
    );

    mail($to, $subject, $personalizedHtml, $headers);

    $logStmt = $pdo->prepare("INSERT INTO newsletter_logs (email, open_token) VALUES (:email, :token)");
    $logStmt->execute(['email' => $to, 'token' => $token]);

    $sentCount++;
}

echo "Newsletter saved and sent to {$sentCount} user(s). TEST_MODE=" . (TEST_MODE ? "ON" : "OFF");
