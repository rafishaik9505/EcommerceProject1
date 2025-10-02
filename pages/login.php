<?php
// DEBUG MODE - set to false on production
ini_set('display_errors', 1);
error_reporting(E_ALL);
define('DEBUG', true);

session_start();
include('../includes/db.php'); // make sure path is correct

// Quick check: do we have $pdo?
if (!isset($pdo)) {
    die("DB connection variable \$pdo is not defined. Check includes/db.php");
}
if (DEBUG && !($pdo instanceof PDO)) {
    die("DB connection is not PDO. Found: " . gettype($pdo));
}

$error_message = '';
$debug_info = '';

if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error_message = "Please enter email and password.";
    } else {
        try {
            // Use a prepared statement with named parameter
            $stmt = $pdo->prepare("SELECT id, email, password FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error_message = "Invalid email or password.";
                if (DEBUG) $debug_info = "No user row returned for email: {$email}";
            } else {
                $stored = $user['password'];

                // Case A: password is hashed (recommended)
                if (password_verify($password, $stored)) {
                    // Optional: rehash if algorithm parameters changed
                    if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $up = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :id");
                        $up->execute([':pass' => $newHash, ':id' => $user['id']]);
                    }
                    $_SESSION['user_id'] = $user['id'];
                    header("Location: ../index.php");
                    exit();
                }

                // Case B: legacy plain-text password in DB -> migrate to hash
                if ($password === $stored) {
                    // Migrate: hash and update DB
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $up = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :id");
                    $up->execute([':pass' => $newHash, ':id' => $user['id']]);

                    $_SESSION['user_id'] = $user['id'];
                    header("Location: ../index.php");
                    exit();
                }

                // Neither matched
                $error_message = "Invalid email or password.";
                if (DEBUG) {
                    $debug_info = "Found user id={$user['id']}. password_verify returned false. Stored password length: " . strlen($stored);
                }
            }
        } catch (PDOException $e) {
            // Fatal DB error
            if (DEBUG) {
                die("PDO error: " . $e->getMessage());
            } else {
                $error_message = "Server error. Try again later.";
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Login</title>
<style>
/* keep your existing styling here or paste your styles */
body{font-family:Arial,Helvetica,sans-serif;background:#f4f4f9;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.login-container{background:#fff;padding:30px;border-radius:8px;box-shadow:0 4px 10px rgba(0,0,0,0.08);width:100%;max-width:420px}
.error{color:#e74c3c;margin-top:12px;text-align:center}
.debug{color:#444;background:#f0f0f0;padding:10px;border-radius:6px;margin-top:12px;font-size:13px}
</style>
</head>
<body>
  <div class="login-container">
    <h2 style="text-align:center">Login</h2>
    <form method="post" novalidate>
      <label>Email</label>
      <input type="email" name="email" required style="width:100%;padding:10px;margin:8px 0;border:1px solid #ccc;border-radius:4px">
      <label>Password</label>
      <input type="password" name="password" required style="width:100%;padding:10px;margin:8px 0;border:1px solid #ccc;border-radius:4px">
      <button type="submit" name="login" style="width:100%;padding:12px;background:#28a745;color:#fff;border:none;border-radius:4px;cursor:pointer">Login</button>
    </form>

    <?php if ($error_message): ?>
      <div class="error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if (DEBUG && $debug_info): ?>
      <pre class="debug"><?= htmlspecialchars($debug_info) ?></pre>
    <?php endif; ?>
  </div>
</body>
</html>




