<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Load database connection
include(__DIR__ . '/../../includes/db.php'); // db.php must set up $pdo

$message = "";

// ✅ Handle Registration
if (isset($_POST['action']) && $_POST['action'] === 'register') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm']);

    if ($password !== $confirm) {
        $message = "❌ Passwords do not match!";
    } else {
        try {
            // Check if user already exists
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = :username");
            $stmt->execute([':username' => $email]);
            if ($stmt->fetch()) {
                $message = "❌ Email already registered!";
            } else {
                // ✅ Secure password hashing
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                // Insert into DB
                $stmt = $pdo->prepare("INSERT INTO admins (username, password, created_at) VALUES (:username, :password, NOW())");
                $stmt->execute([
                    ':username' => $email,
                    ':password' => $hashed
                ]);

                $message = "✅ Registration successful! Please login.";
            }
        } catch (PDOException $e) {
            $message = "❌ Error: " . $e->getMessage();
        }
    }
}

// ✅ Handle Login
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $message = "❌ Please enter both email and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // ✅ Verify hashed password
                if (password_verify($password, $row['password'])) {
                    $_SESSION['admin_id'] = $row['id'];
                    $_SESSION['admin_email'] = $row['username'];

                    header("Location: dashboard.php");
                    exit;
                } else {
                    $message = "❌ Invalid password.";
                }
            } else {
                $message = "❌ Admin account not found.";
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login & Registration</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .container { width: 400px; margin: 60px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { margin-bottom: 20px; }
        input { width: 100%; padding: 10px; margin: 10px 0; }
        button { width: 100%; padding: 10px; background: #28a745; border: none; color: white; font-size: 16px; cursor: pointer; }
        button:hover { background: #218838; }
        .toggle { text-align:center; margin-top:15px; }
    </style>
    <script>
        function toggleForm(form) {
            document.getElementById('login-form').style.display = (form === 'login') ? 'block' : 'none';
            document.getElementById('register-form').style.display = (form === 'register') ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <div class="container">
        <?php if ($message): ?>
            <p style="color:red;"><?php echo $message; ?></p>
        <?php endif; ?>

        <!-- ✅ Login Form -->
        <div id="login-form" style="display:block;">
            <h2>Admin Login</h2>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <input type="email" name="email" placeholder="Enter email" required>
                <input type="password" name="password" placeholder="Enter password" required>
                <button type="submit">Login</button>
            </form>
            <div class="toggle">
                <a href="#" onclick="toggleForm('register')">New? Register here</a>
            </div>
        </div>

        <!-- ✅ Registration Form -->
        <div id="register-form" style="display:none;">
            <h2>Register Shopkeeper</h2>
            <form method="POST">
                <input type="hidden" name="action" value="register">
                <input type="email" name="email" placeholder="Enter email" required>
                <input type="password" name="password" placeholder="Enter password" required>
                <input type="password" name="confirm" placeholder="Confirm password" required>
                <button type="submit">Register</button>
            </form>
            <div class="toggle">
                <a href="#" onclick="toggleForm('login')">Already have an account? Login</a>
            </div>
        </div>
    </div>
</body>
</html>










