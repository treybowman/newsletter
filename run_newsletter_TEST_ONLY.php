
<?php

$secret = 'runDaily2025!';
if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    http_response_code(403);
    exit('Forbidden');
}
define('TEST_MODE', false); // Set to false when going live
define('TEST_EMAIL', 'support@cobbtalk.com');

function fetchFeed($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'CobbTalkBot/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

$topFeedData = fetchFeed('https://cobbtalk.com/atom/discussions?sort=top');
$newFeedData = fetchFeed('https://cobbtalk.com/atom/discussions?sort=newest');

$topXml = simplexml_load_string($topFeedData);
$newXml = simplexml_load_string($newFeedData);

if (!$topXml || !$newXml) {
    die('<h2>Error: Could not load one or both feeds.</h2>');
}

ob_start();
?>
<!-- CobbTalk Weekly Email Digest -->
<table cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: auto; font-family: Arial, sans-serif; color: #333;">
    <tr>
        <td align="center" style="padding: 20px;">
            <img src="https://cobbtalk.com/assets/logo-6m4unrdr.png" alt="CobbTalk Logo" width="200" style="margin-bottom: 20px;">
            <h2 style="color: #007bff; margin: 0;">📫 CobbTalk Weekly Digest</h2>
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
        $link .= (strpos($link, '?') !== false ? '&' : '?') . 'utm_source=newsletter&utm_medium=email&utm_campaign=cobbtalk_digest&utm_content={{username}}';
        $summary = strip_tags((string)$entry->summary);
        $summaryShort = strlen($summary) > 200 ? substr($summary, 0, 200) . '…' : $summary;
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
        $link .= (strpos($link, '?') !== false ? '&' : '?') . 'utm_source=newsletter&utm_medium=email&utm_campaign=cobbtalk_digest&utm_content={{username}}';
        $summary = strip_tags((string)$entry->summary);
        $summaryShort = strlen($summary) > 200 ? substr($summary, 0, 200) . '…' : $summary;
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
            <p>Want more? Visit <a href="https://cobbtalk.com" style="color: #007bff;">CobbTalk.com</a> to join the conversation.</p>
            <p style="font-size:12px; color:#aaa;">No longer want to receive this? <a href="https://cobbtalk.com/newsletter/unsubscribe.php?email={{email}}">Unsubscribe here</a>.</p>
            <img src="https://cobbtalk.com/newsletter/open.php?token={{token}}" width="1" height="1" alt="." />
        </td>
    </tr>
</table>
<?php
$htmlOutput = ob_get_clean();
$folder = __DIR__ . '/newsletters';
if (!is_dir($folder)) mkdir($folder, 0777, true);
file_put_contents($folder . '/newsletter_' . date('Y-m-d') . '.html', mb_convert_encoding($htmlOutput, 'UTF-8', 'UTF-8'));

$dbHost = 'localhost';
$dbName = 'treyb_cobbtalk';
$dbUser = 'treyb_cobb';
$dbPass = 'MaxwellB0wman';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$unsubscribeQuery = $pdo->query("SELECT email FROM unsubscribed_emails");
$unsubscribed = $unsubscribeQuery->fetchAll(PDO::FETCH_COLUMN, 0);

$query = $pdo->query("SELECT username, email FROM ct_users WHERE email IS NOT NULL AND email != ''");
$users = $query->fetchAll(PDO::FETCH_ASSOC);

$subject = "Your Weekly CobbTalk Digest";
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: CobbTalk <no-reply@cobbtalk.com>\r\n";

$sentCount = 0;
foreach ($users as $user) {
    $to = $user['email'];
    $username = htmlspecialchars($user['username']);

    if (TEST_MODE && $to !== TEST_EMAIL) continue;
    if (in_array($to, $unsubscribed)) continue;

    $token = bin2hex(random_bytes(16));
    $personalizedHtml = str_replace(['{{username}}', '{{email}}', '{{token}}'], [$username, $to, $token], $htmlOutput);
    echo "Would send to: $to<br>";

    // $logStmt = \$pdo->prepare("INSERT INTO newsletter_logs (email, open_token) VALUES (:email, :token)");
    // \$logStmt->execute(['email' => \$to, 'token' => \$token]);

    $sentCount++;
}

echo "Newsletter saved in /newsletters and sent to " . $sentCount . " user(s). TEST_MODE=" . (TEST_MODE ? "ON" : "OFF");
?>
