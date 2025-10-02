<?php
session_start();
require_once("../../includes/db.php");

// ✅ Only allow logged in admins
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// ✅ Delete product
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_product.php");
    exit();
}

// ✅ Fetch products
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .container { margin-top: 40px; }
        .card { border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn-sm { padding: 4px 10px; }
    </style>
</head>
<body>
<div class="container">
    <div class="card p-4">
        <h3 class="mb-4">Manage Products</h3>

        <a href="add_product.php" class="btn btn-success mb-3">+ Add New Product</a>
        <a href="dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

        <?php if ($products): ?>
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['id']) ?></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td>₹<?= number_format($p['price'], 2) ?></td>
                        <td>
                            <?php if (!empty($p['image'])): ?>
                                <img src="../../images/<?= htmlspecialchars($p['image']) ?>" width="60">
                            <?php else: ?>
                                <span class="text-muted">No Image</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="manage_product.php?delete=<?= $p['id'] ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Are you sure you want to delete this product?');">
                               Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">No products found.</div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
