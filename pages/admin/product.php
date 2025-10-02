<?php
session_start();
require_once "../includes/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Handle delete action
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: products.php?msg=deleted");
    exit;
}

// Fetch all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f4f4f4; }
        img { max-width: 60px; }
        .actions a {
            padding: 6px 10px;
            text-decoration: none;
            margin-right: 5px;
            border-radius: 4px;
        }
        .edit-btn { background: #007bff; color: white; }
        .delete-btn { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <h2>Manage Products</h2>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <p style="color: red;">Product deleted successfully.</p>
    <?php endif; ?>
    <a href="add_product.php">+ Add New Product</a>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Price (₹)</th>
            <th>Image</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($products as $product): ?>
        <tr>
            <td><?= $product['id']; ?></td>
            <td><?= htmlspecialchars($product['name']); ?></td>
            <td><?= number_format($product['price'], 2); ?></td>
            <td>
                <?php if (!empty($product['image'])): ?>
                    <img src="../images/<?= htmlspecialchars($product['image']); ?>" alt="">
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($product['description']); ?></td>
            <td class="actions">
                <a href="edit_product.php?id=<?= $product['id']; ?>" class="edit-btn">Edit</a>
                <a href="products.php?delete=<?= $product['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
