
<?php
$dir = __DIR__ . '/newsletters';
$files = glob($dir . '/newsletter_*.html');
usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>FORUMNAME Newsletter Archive</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f9f9f9; color: #333; }
        h1 { color: #007bff; }
        ul { list-style: none; padding: 0; }
        li { margin: 10px 0; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>FORUMNAME Newsletter Archive</h1>
    <p>Browse all past issues of our community newsletter.</p>
    <ul>
        <?php foreach ($files as $file): 
            $basename = basename($file);
            $dateStr = str_replace(['newsletter_', '.html'], '', $basename);
            $dateFormatted = date("F j, Y", strtotime($dateStr));
        ?>
            <li><a href="newsletters/<?= $basename ?>"><?= $dateFormatted ?></a></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
