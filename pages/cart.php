<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

$user_id = $_SESSION['user_id'];  

// Handle Add to Cart with Quantity
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart_item) {
        $new_quantity = $cart_item['quantity'] + $quantity;
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$new_quantity, $user_id, $product_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $quantity]);
    }
}

// Handle Remove
if (isset($_POST['remove_from_cart'])) {
    $product_id = $_POST['product_id'];
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
}

// Handle Quantity Update
if (isset($_POST['update_quantity'])) {
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$quantity, $user_id, $product_id]);
}

// Fetch cart items
$stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_cost = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your Cart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body { background: #f1f3f6; }
    .cart-container { margin-top: 30px; }
    .cart-card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .cart-item img { max-width: 100px; border-radius: 8px; }
    .price-details { position: sticky; top: 20px; }
    .btn-remove { background: #ff4d4d; color: #fff; border:none; }
    .btn-remove:hover { background: #e60000; }
  </style>
</head>
<body>
<div class="container cart-container">
  <div class="row">
    <!-- Cart Items -->
    <div class="col-lg-8">
      <div class="card cart-card p-3">
        <h4 class="mb-3"><i class="fa fa-shopping-cart"></i> Your Shopping Cart</h4>
        <?php
        if (empty($cart_items)) {
            echo "<p class='text-center text-muted'>Your cart is empty 😢 <a href='../index.php' class='btn btn-primary btn-sm ms-2'>Shop Now</a></p>";
        } else {
            $product_ids = array_column($cart_items, 'product_id');
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
            $stmt->execute($product_ids);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($products as $product) {
                $quantity = 0;
                foreach ($cart_items as $cart_item) {
                    if ($cart_item['product_id'] == $product['id']) {
                        $quantity = $cart_item['quantity'];
                        break;
                    }
                }
                $subtotal = $product['price'] * $quantity;
                $total_cost += $subtotal;

                echo "
                <div class='row align-items-center mb-4 border-bottom pb-3'>
                    <div class='col-md-2 text-center'>
                        <img src='../images/{$product['image']}' alt='{$product['name']}' class='img-fluid'>
                    </div>
                    <div class='col-md-4'>
                        <h6>{$product['name']}</h6>
                        <p class='mb-1 text-muted'>Price: ₹{$product['price']}</p>
                        <p class='fw-bold text-success'>Subtotal: ₹".number_format($subtotal,2)."</p>
                    </div>
                    <div class='col-md-6 text-end'>
                        <form method='POST' class='d-inline'>
                            <input type='hidden' name='product_id' value='{$product['id']}'>
                            <input type='number' name='quantity' value='{$quantity}' class='form-control d-inline w-25' min='1' required>
                            <button type='submit' name='update_quantity' class='btn btn-sm btn-outline-primary ms-2'><i class='fa fa-sync'></i></button>
                        </form>
                        <form method='POST' class='d-inline'>
                            <input type='hidden' name='product_id' value='{$product['id']}'>
                            <button type='submit' name='remove_from_cart' class='btn btn-sm btn-remove ms-2'><i class='fa fa-trash'></i></button>
                        </form>
                    </div>
                </div>
                ";
            }
        }
        ?>
      </div>
    </div>

    <!-- Price Details -->
    <?php if (!empty($cart_items)) : ?>
    <div class="col-lg-4">
      <div class="card cart-card p-3 price-details">
        <h5 class="mb-3">Price Details</h5>
        <div class="d-flex justify-content-between mb-2">
          <span>Total Items</span>
          <span><?= count($cart_items) ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span>Total Price</span>
          <span>₹<?= number_format($total_cost, 2) ?></span>
        </div>
        <hr>
        <div class="d-flex justify-content-between fw-bold mb-3">
          <span>Amount Payable</span>
          <span>₹<?= number_format($total_cost, 2) ?></span>
        </div>
        <a href="payment.php" class="btn btn-success w-100"><i class="fa fa-credit-card"></i> Proceed to Checkout</a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


