<?php

$config = require __DIR__ . '/config.php';

$siteName       = $config['site_name'];
$siteURL        = 'http://' . rtrim($config['site_url'], '/');
$usersTable     = $config['users_table'];
$settingsTable  = $config['settings_table'];
$testMode       = $config['test_mode'] ?? false;
$testEmail      = $config['test_email'] ?? null;

// Protect with the log_token
if (!isset($_GET['key']) || $_GET['key'] !== $config['log_token']) {
    http_response_code(403);
    exit('Forbidden');
}

mb_internal_encoding("UTF-8");

// Fetch ATOM feeds
function fetchFeed($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'TreyBNewsletterBot/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

$topXml = simplexml_load_string(fetchFeed("$siteURL/atom/discussions?sort=top"));
$newXml = simplexml_load_string(fetchFeed("$siteURL/atom/discussions?sort=newest"));

if (!$topXml || !$newXml) {
    die('<h2>Error: Could not load one or both feeds.</h2>');
}

// Connect to DB and fetch logo path
try {
    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'],
        $config['db_pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT value FROM {$settingsTable} WHERE `key` = 'logo_path'");
    $logo = $stmt->fetchColumn();

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Build newsletter HTML
ob_start();
?>
<!-- <?= $siteName ?> Weekly Email Digest -->
<table cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: auto; font-family: Arial, sans-serif; color: #333;">
    <tr>
        <td align="center" style="padding: 20px;">
            <img src="<?= $siteURL ?>/assets/<?= htmlspecialchars($logo) ?>" alt="<?= $siteName ?> Logo" width="200" style="margin-bottom: 20px;">
            <h2 style="color: #007bff; margin: 0;">📬 <?= $siteName ?> Weekly Digest</h2>
            <p style="font-size: 16px;">Here’s a roundup of the hottest and newest discussions from the community this week.</p>
        </td>
    </tr>

    <tr><td><h3 style="padding: 20px 20px 0; color: #e67e22;">🔥 Top Discussions</h3></td></tr>
    <?php
    $count = 0;
    foreach ($topXml->entry as $entry):
        if (++$count > 5) break;
        $title = htmlspecialchars((string)$entry->title);
        $link = (string)$entry->link['href'];
        $link .= (strpos($link, '?') !== false ? '&' : '?') . 'utm_source=newsletter&utm_medium=email&utm_campaign=' . $siteName . '_digest&utm_content={{username}}';
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
<br><br>
    <tr><td><h3 style="padding: 20px 20px 0; color: #27ae60;">🆕 Newest Posts</h3></td></tr>
    <?php
    $count = 0;
    foreach ($newXml->entry as $entry):
        if (++$count > 5) break;
        $title = htmlspecialchars((string)$entry->title);
        $link = (string)$entry->link['href'];
        $link .= (strpos($link, '?') !== false ? '&' : '?') . 'utm_source=newsletter&utm_medium=email&utm_campaign=' . $siteName . '_digest&utm_content={{username}}';
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
            <p>Want more? Visit <a href="<?= $siteURL ?>" style="color: #007bff;"><?= $siteURL ?></a> to join the conversation.</p>
            <p style="font-size:12px; color:#aaa;">No longer want to receive this? <a href="<?= $siteURL ?>/newsletter/unsubscribe.php?email={{email}}&token={{token}}">Unsubscribe here</a>.</p>
            <img src="<?= $siteURL ?>/newsletter/open.php?token={{token}}" width="1" height="1" alt="." />
        </td>
    </tr>
</table>
<?php
$htmlOutput = ob_get_clean();

// Save snapshot
$folder = __DIR__ . '/newsletters';
if (!is_dir($folder)) mkdir($folder, 0777, true);
file_put_contents($folder . '/newsletter_' . date('Y-m-d') . '.html', mb_convert_encoding($htmlOutput, 'UTF-8', 'UTF-8'));

// Fetch unsubscribed
$unsubscribed = $pdo->query("SELECT email FROM unsubscribed_emails")->fetchAll(PDO::FETCH_COLUMN, 0);

// Get users with email
$users = $pdo->query("SELECT username, email FROM {$usersTable} WHERE email IS NOT NULL AND email != ''")->fetchAll(PDO::FETCH_ASSOC);

$subject = "Your Weekly {$siteName} Digest";
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: {$siteName} <no-reply@{$siteURL}>\r\n";

$sentCount = 0;
foreach ($users as $user) {
    $to = $user['email'];
    $username = htmlspecialchars($user['username']);

    if ($testMode && $to !== $testEmail) continue;
    if (in_array($to, $unsubscribed)) continue;

    $token = bin2hex(random_bytes(16));
    file_put_contents(__DIR__ . "/tokens/" . md5($to) . ".txt", $token);

    // 🔁 Replace placeholders
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

echo "Newsletter saved and sent to {$sentCount} user(s). TEST_MODE=" . ($testMode ? "ON" : "OFF");
