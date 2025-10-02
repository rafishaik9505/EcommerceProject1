<?php
session_start();
include('../includes/db.php'); // include db connection

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header('Location: ../index.php'); // go back to homepage if no order
    exit();
}

$order_id = intval($_GET['order_id']);

// fetch order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

// fetch order items
$stmt2 = $pdo->prepare("
    SELECT oi.quantity, oi.price, p.name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt2->bind_param("i", $order_id);
$stmt2->execute();
$items = $stmt2->get_result();
$stmt2->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Success</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.card { border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
.btn-primary { background-color: #0066ff; border: none; }
.btn-primary:hover { background-color: #0052cc; }
</style>
</head>
<body>
<div class="container mt-5">
  <div class="card p-4">
    <h2 class="text-success">✅ Payment Successful!</h2>
    <p>Thank you <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Customer'); ?></strong>, your order has been placed.</p>
    <p><strong>Order ID:</strong> <?= $order['id']; ?></p>
    <p><strong>Total Paid:</strong> ₹<?= number_format($order['total_price'], 2); ?></p>
    
    <h4>Order Summary</h4>
    <ul class="list-group mb-3">
      <?php while ($item = $items->fetch_assoc()) { ?>
      <li class="list-group-item d-flex justify-content-between">
        <?= htmlspecialchars($item['name']); ?> x <?= $item['quantity']; ?>
        <span>₹<?= number_format($item['price'] * $item['quantity'], 2); ?></span>
      </li>
      <?php } ?>
      <li class="list-group-item d-flex justify-content-between fw-bold">
        Total
        <span>₹<?= number_format($order['total_price'], 2); ?></span>
      </li>
    </ul>

    <!-- ✅ Fixed "Continue Shopping" button -->
    <a href="index.php" class="btn btn-primary btn-lg w-100">Continue Shopping</a>

      Continue Shopping
    </a>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>






