<?php
session_start();
include(__DIR__ . '/includes/db.php');

// If admin already logged in → redirect
if (isset($_SESSION['admin_id'])) {
    header("Location: pages/admin/dashboard.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm']);

    if ($password !== $confirm) {
        $message = "❌ Passwords do not match!";
    } else {
        try {
            // Check if email/username already exists
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = :username");
            $stmt->execute([':username' => $username]);
            if ($stmt->fetch()) {
                $message = "❌ Username already registered!";
            } else {
                // Hash password
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                // Insert new shopkeeper
                $stmt = $pdo->prepare("INSERT INTO admins (username, password, created_at) 
                                       VALUES (:username, :password, NOW())");
                $stmt->execute([
                    ':username' => $username,
                    ':password' => $hashed
                ]);

                $message = "✅ Registration successful! You can now login.";
            }
        } catch (PDOException $e) {
            $message = "❌ Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopkeeper Registration</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f4f4; }
        .container { width: 400px; margin: 60px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { margin-bottom: 20px; }
        input { width: 100%; padding: 10px; margin: 10px 0; }
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #218838; }
        p { text-align:center; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Shopkeeper Registration</h2>
        <?php if ($message): ?>
            <p style="color:red;"><?php echo $message; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="email" name="username" placeholder="Email (will be your login)" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm" placeholder="Confirm Password" required>
            <button type="submit">Register</button>
        </form>
        <p>Already registered? <a href="admin_login.php">Login here</a></p>
    </div>
</body>
</html>
