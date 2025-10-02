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

// ✅ Get product ID
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}
$id = intval($_GET['id']);

// ✅ Fetch product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "❌ Product not found.";
    exit;
}

$message = "";

// ✅ Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $stock = intval($_POST['stock']);
    $image = $product['image']; // keep old image by default

    // ✅ Handle image upload if new image provided
    if (!empty($_FILES['image']['name'])) {
        $targetDir = __DIR__ . '/../../images/';
        $fileName = basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                // delete old image if exists
                if ($product['image'] && file_exists($targetDir . $product['image'])) {
                    unlink($targetDir . $product['image']);
                }
                $image = $fileName;
            } else {
                $message = "❌ Failed to upload new image.";
            }
        } else {
            $message = "❌ Invalid file type. Allowed: JPG, PNG, GIF, WEBP.";
        }
    }

    // ✅ Update product
    if (empty($message)) {
        try {
            $stmt = $pdo->prepare("UPDATE products 
                                   SET name = :name, price = :price, description = :description, stock = :stock, image = :image 
                                   WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':price' => $price,
                ':description' => $description,
                ':stock' => $stock,
                ':image' => $image,
                ':id' => $id
            ]);
            $message = "✅ Product updated successfully!";
            // refresh product data
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $message = "❌ Database Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .form-container { width: 500px; margin: 50px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input, textarea { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; }
        button { width: 100%; padding: 10px; background: #007bff; border: none; color: white; font-size: 16px; cursor: pointer; }
        button:hover { background: #0056b3; }
        img { max-width: 120px; display: block; margin-bottom: 10px; }
        .message { padding: 10px; margin-bottom: 10px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Edit Product</h2>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo (strpos($message, '✅') !== false) ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            <input type="number" step="0.01" name="price" value="<?php echo $product['price']; ?>" required>
            <textarea name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
            <input type="number" name="stock" value="<?php echo $product['stock']; ?>" required>
            
            <?php if ($product['image']): ?>
                <p>Current Image:</p>
                <img src="../../images/<?php echo $product['image']; ?>" alt="Product">
            <?php endif; ?>
            
            <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif,.webp">
            <button type="submit">Update Product</button>
        </form>

        <p><a href="dashboard.php">⬅ Back to Dashboard</a></p>
    </div>
</body>
</html>


