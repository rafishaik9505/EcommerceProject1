<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Only admin can access this page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// ✅ Include DB connection
include(__DIR__ . '/../../includes/db.php');

// ✅ Handle form submission
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $stock = intval($_POST['stock']);
    $image = null;

    // ✅ Image upload handling
    if (!empty($_FILES['image']['name'])) {
        $targetDir = __DIR__ . '/../../images/';
        $fileName = basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;

        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image = $fileName;
            } else {
                $message = "❌ Failed to upload image.";
            }
        } else {
            $message = "❌ Only JPG, JPEG, PNG, GIF, WEBP allowed.";
        }
    }

    if (empty($name) || empty($price) || empty($description) || empty($stock)) {
        $message = "⚠️ Please fill all fields.";
    } else {
        try {
            // ✅ Insert product with admin_id
            $stmt = $pdo->prepare("INSERT INTO products (name, price, description, stock, image, admin_id) 
                                   VALUES (:name, :price, :description, :stock, :image, :admin_id)");
            $stmt->execute([
                ':name' => $name,
                ':price' => $price,
                ':description' => $description,
                ':stock' => $stock,
                ':image' => $image,
                ':admin_id' => $_SESSION['admin_id']
            ]);

            $message = "✅ Product added successfully!";
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
    <title>Add Product</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .form-container { width: 500px; margin: 50px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input, textarea { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; }
        button { width: 100%; padding: 10px; background: #007bff; border: none; color: white; font-size: 16px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .message { padding: 10px; margin-bottom: 10px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Add New Product</h2>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo (strpos($message, '✅') !== false) ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="name" placeholder="Product Name" required>
            <input type="number" step="0.01" name="price" placeholder="Product Price" required>
            <textarea name="description" placeholder="Product Description" required></textarea>
            <input type="number" name="stock" placeholder="Stock Limit" required>
            <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif,.webp">
            <button type="submit">Add Product</button>
        </form>
    </div>
</body>
</html>


