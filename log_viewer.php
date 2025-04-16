
<?php
$dbHost = 'localhost';
$dbName = 'your_database_name';
$dbUser = 'your_database_user';
$dbPass = 'your_database_password';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch summary stats
    $summaryStmt = $pdo->query("SELECT COUNT(*) AS total_sent, SUM(opened) AS total_opens FROM newsletter_logs");
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);
    $total_sent = (int) $summary['total_sent'];
    $total_opens = (int) $summary['total_opens'];
    $open_rate = $total_sent > 0 ? round($total_opens / $total_sent * 100, 2) : 0;

    // Fetch last 50 logs
    $logsStmt = $pdo->query("SELECT email, sent_at, opened FROM newsletter_logs ORDER BY sent_at DESC LIMIT 50");
    $logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Newsletter Log Viewer</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f9f9f9; color: #333; }
        h1, h2 { color: #007bff; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #f1f1f1; }
        .opened { color: green; }
        .not-opened { color: red; }
    </style>
</head>
<body>
    <h1>📬 Newsletter Logs</h1>
    <h2>Stats Summary</h2>
    <p><strong>Total Sent:</strong> <?= $total_sent ?></p>
    <p><strong>Total Opens:</strong> <?= $total_opens ?></p>
    <p><strong>Open Rate:</strong> <?= $open_rate ?>%</p>

    <h2>📊 Latest 50 Sends</h2>
    <table>
        <thead>
            <tr>
                <th>Email</th>
                <th>Sent At</th>
                <th>Opened?</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['email']) ?></td>
                    <td><?= htmlspecialchars($log['sent_at']) ?></td>
                    <td class="<?= $log['opened'] ? 'opened' : 'not-opened' ?>">
                        <?= $log['opened'] ? '✅ Opened' : '❌ Not Yet' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
