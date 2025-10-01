<?php
// includes/db.php

$host = "localhost";
$dbname = "ecommerce1";  // <-- change to your database name
$username = "root";      // <-- change if you set a MySQL user
$password = "";          // <-- change if your MySQL has a password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
