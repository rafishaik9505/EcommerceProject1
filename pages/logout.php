<?php
session_start();

// If a session exists, destroy it
if (isset($_SESSION['user_id']) || !empty($_SESSION)) {
    session_unset();
    session_destroy();
}

// Always redirect (no blank page)
header("Location: index.php"); // you can change to login.php if you prefer
exit;
?>
