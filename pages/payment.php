<?php
session_start();
include('../includes/db.php'); // make sure this file defines either $pdo (PDO) or $conn (mysqli)

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$cart_items = [];
$total_price = 0.0;
$user = [];

try {
    // ---- PDO branch ----
    if (isset($pdo) && ($pdo instanceof PDO)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt2 = $pdo->prepare("
            SELECT c.id, c.quantity, p.name, p.price
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = :id
        ");
        $stmt2->execute([':id' => $user_id]);
        $cart_items = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($cart_items as $row) {
            $total_price += (float)$row['price'] * (int)$row['quantity'];
        }

    // ---- MySQLi branch ----
    } elseif (isset($pdo) && ($pdo instanceof mysqli)) {
        // fetch user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        if (!$stmt) throw new Exception("Prepare failed: " . $pdo->error);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            $user = $res->fetch_assoc() ?: [];
        }
        $stmt->close();

        // fetch cart items
        $stmt2 = $pdo->prepare("
            SELECT c.id, c.quantity, p.name, p.price
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        if (!$stmt2) throw new Exception("Prepare failed: " . $pdo->error);
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        $res3 = $stmt2->get_result();

        if ($res3) {
            while ($row = $res3->fetch_assoc()) {
                $cart_items[] = $row;
                $total_price += (float)$row['price'] * (int)$row['quantity'];
            }
        }
        $stmt2->close();

    } else {
        throw new Exception("No database connection found. Make sure ../includes/db.php defines either \$pdo (PDO) or \$pdo (mysqli).");
    }

    // ✅ Store order in session so it doesn’t disappear after redirect
    $_SESSION['order'] = [
        'items' => $cart_items,
        'total' => $total_price
    ];

} catch (Exception $e) {
    echo "<h2>Database error</h2><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #f8f9fa; }
.card { border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
.btn-primary { background-color: #ff6f00; border: none; }
.btn-primary:hover { background-color: #e65c00; }
</style>
</head>
<body>
<div class="container mt-5">
  <div class="row">
    <div class="col-md-7">
      <div class="card p-4 mb-4">
        <h4>Billing Details</h4>
        <form action="process_payment.php" method="POST">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? ''); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? ''); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" required><?= htmlspecialchars($user['address'] ?? ''); ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-select" required>
              <option value="">Select</option>
              <option value="credit_card">Credit/Debit Card</option>
              <option value="upi">UPI</option>
              <option value="wallet">Wallet</option>
            </select>
          </div>
          <input type="hidden" name="total_price" value="<?= number_format($total_price, 2, '.', ''); ?>">
          <button type="submit" class="btn btn-primary">
            Pay ₹<?= number_format($total_price, 2); ?>
          </button>
        </form>
      </div>
    </div>

    <div class="col-md-5">
      <div class="card p-4 mb-4">
        <h4>Order Summary</h4>
        <ul class="list-group mb-3">
          <?php if (empty($cart_items)) { ?>
            <li class="list-group-item">Your cart is empty.</li>
          <?php } else {
            foreach ($cart_items as $item) { ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <?= htmlspecialchars($item['name']); ?> x <?= (int)$item['quantity']; ?>
              <span>₹<?= number_format($item['price'] * $item['quantity'], 2); ?></span>
            </li>
          <?php } } ?>
          <li class="list-group-item d-flex justify-content-between align-items-center fw-bold">
            Total
            <span>₹<?= number_format($total_price, 2); ?></span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



