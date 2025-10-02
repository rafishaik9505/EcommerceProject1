<?php
// pages/continue.php
// Server-side helper: locate index.php and redirect to it.
// Saves us from client-side path issues.

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];              // e.g. localhost
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'); // filesystem root for webserver
$scriptFile = $_SERVER['SCRIPT_FILENAME'];  // full path to this script
$scriptDir = dirname($scriptFile);

// Candidate filesystem paths to check (order: same dir, parent, docroot)
$candidates = [
    $scriptDir . DIRECTORY_SEPARATOR . 'index.php',          // /.../pages/index.php
    dirname($scriptDir) . DIRECTORY_SEPARATOR . 'index.php', // /.../index.php
    $docRoot . DIRECTORY_SEPARATOR . 'index.php'             // DOCUMENT_ROOT/index.php
];

$found = false;
$foundFs = '';
foreach ($candidates as $fsPath) {
    if (file_exists($fsPath)) {
        $found = true;
        $foundFs = $fsPath;
        break;
    }
}

if ($found) {
    // convert filesystem path to web path
    // if found path starts with docRoot, use the remainder as web path
    $fsNormalized = str_replace('\\', '/', $foundFs);
    $docNormalized = str_replace('\\', '/', $docRoot);

    if (strpos($fsNormalized, $docNormalized) === 0) {
        $webPath = substr($fsNormalized, strlen($docNormalized));
        if ($webPath === '' ) $webPath = '/';
        // ensure leading slash
        if ($webPath[0] !== '/') $webPath = '/' . $webPath;
    } else {
        // fallback: try to compute relative from script name
        $scriptNameDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $basename = basename($foundFs);
        $webPath = $scriptNameDir . '/' . $basename;
    }

    $url = $scheme . '://' . $host . $webPath;
    header('Location: ' . $url);
    exit;
}

// Nothing found — show helpful debug for you
http_response_code(404);
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Continue Debug</title></head>
<body style="font-family:Arial,helvetica,sans-serif">
  <h2 style="color:#b00">Continue helper: index.php not found in expected places</h2>
  <p><strong>SERVER INFO</strong></p>
  <ul>
    <li>DOCUMENT_ROOT: <?= htmlspecialchars($docRoot) ?></li>
    <li>SCRIPT_FILENAME: <?= htmlspecialchars($scriptFile) ?></li>
    <li>SCRIPT_NAME: <?= htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? '') ?></li>
    <li>HOST: <?= htmlspecialchars($host) ?></li>
  </ul>

  <p><strong>Checked candidates:</strong></p>
  <ul>
    <?php foreach ($candidates as $c) {
      echo '<li>' . htmlspecialchars($c) . ' — ' . (file_exists($c) ? '<strong>FOUND</strong>' : 'not found') . '</li>';
    } ?>
  </ul>

  <p>If one of the above says <strong>FOUND</strong> but clicking still 404s, copy the link below and open in new tab to see exact URL used:</p>
  <p><em>Try manual links that are most likely correct:</em></p>
  <ul>
    <li><a href="http://<?= htmlspecialchars($host) ?>/<?= htmlspecialchars(basename(dirname($docRoot)) ) ?>/index.php">http://<?= htmlspecialchars($host) ?>/.../index.php</a></li>
    <li><a href="/">http://<?= htmlspecialchars($host) ?>/</a></li>
  </ul>

  <p>When you paste the output of this debug (the "Checked candidates" results) back here I will tell you exactly what to change next.</p>
</body>
</html>
