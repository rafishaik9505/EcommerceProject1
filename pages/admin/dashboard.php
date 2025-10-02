<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Only admin can access
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// ✅ Include DB connection
include(__DIR__ . '/../../includes/db.php');

// ✅ Handle delete product
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    try {
        // Delete image file also
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = :id AND admin_id = :admin_id");
        $stmt->execute([':id' => $id, ':admin_id' => $_SESSION['admin_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product && $product['image']) {
            $file = __DIR__ . '/../../images/' . $product['image'];
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id AND admin_id = :admin_id");
        $stmt->execute([':id' => $id, ':admin_id' => $_SESSION['admin_id']]);

        header("Location: dashboard.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "❌ Error deleting product: " . $e->getMessage();
    }
}

// ✅ Build query with filters
$where = ["admin_id = :admin_id"];
$params = [':admin_id' => $_SESSION['admin_id']];

// Search by name
if (!empty($_GET['search'])) {
    $where[] = "name LIKE :search";
    $params[':search'] = "%" . $_GET['search'] . "%";
}

// Filter by price range
if (!empty($_GET['min_price'])) {
    $where[] = "price >= :min_price";
    $params[':min_price'] = floatval($_GET['min_price']);
}
if (!empty($_GET['max_price'])) {
    $where[] = "price <= :max_price";
    $params[':max_price'] = floatval($_GET['max_price']);
}

// Filter by stock availability
if (!empty($_GET['stock_status'])) {
    if ($_GET['stock_status'] === "in") {
        $where[] = "stock > 0";
    } elseif ($_GET['stock_status'] === "out") {
        $where[] = "stock = 0";
    }
}

// ✅ Final SQL query
$sql = "SELECT * FROM products WHERE " . implode(" AND ", $where) . " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .container { width: 95%; margin: 30px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { margin-bottom: 20px; }
        a.button { display: inline-block; padding: 10px 15px; margin-bottom: 15px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; }
        a.button:hover { background: #218838; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        img { max-width: 80px; border-radius: 5px; }
        .actions a { margin-right: 10px; text-decoration: none; padding: 6px 10px; border-radius: 5px; }
        .edit { background: #ffc107; color: #000; }
        .edit:hover { background: #e0a800; }
        .delete { background: #dc3545; color: #fff; }
        .delete:hover { background: #c82333; }
        .filter-box { background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .filter-box input, .filter-box select { padding: 8px; margin: 5px; }
        .filter-box button { padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .filter-box button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Admin Dashboard</h2>

        <?php if (!empty($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <p style="color: green;">✅ Product deleted successfully!</p>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>

        <a href="add_product.php" class="button">+ Add New Product</a>
        <a href="admin_logout.php" class="button" style="background:#6c757d;">Logout</a>




        <!-- 🔍 Search & Filter Box -->
        <div class="filter-box">
            <form method="GET" action="dashboard.php">
                <input type="text" name="search" placeholder="Search by name" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                <input type="number" step="0.01" name="min_price" placeholder="Min Price" value="<?php echo htmlspecialchars($_GET['min_price'] ?? ''); ?>">
                <input type="number" step="0.01" name="max_price" placeholder="Max Price" value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>">

                <select name="stock_status">
                    <option value="">-- Stock Status --</option>
                    <option value="in" <?php if(($_GET['stock_status'] ?? '') === 'in') echo 'selected'; ?>>In Stock</option>
                    <option value="out" <?php if(($_GET['stock_status'] ?? '') === 'out') echo 'selected'; ?>>Out of Stock</option>
                </select>

                <button type="submit">Apply Filters</button>
                <a href="dashboard.php" style="margin-left:10px; text-decoration:none; color:#007bff;">Reset</a>
            </form>
        </div>

        <!-- 📦 Product Table -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Description</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?php echo $p['id']; ?></td>
                            <td>
                                <?php if ($p['image']): ?>
                                    <img src="../../images/<?php echo $p['image']; ?>" alt="Product">
                                <?php else: ?>
                                    No Image
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($p['name']); ?></td>
                            <td>₹<?php echo number_format($p['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($p['description']); ?></td>
                            <td><?php echo $p['stock']; ?></td>
                            <td class="actions">
                                <a href="edit_product.php?id=<?php echo $p['id']; ?>" class="edit">✏ Edit</a>
                                <a href="dashboard.php?delete=<?php echo $p['id']; ?>" class="delete" onclick="return confirm('Are you sure?')">🗑 Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;">No products found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>




