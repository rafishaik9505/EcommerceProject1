<?php
session_start();
include('../includes/db.php'); 

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$order   = $_SESSION['order'] ?? null;

if (!$order || empty($order['items'])) {
    echo "<h2 style='color:red;'>Error: Your order session expired or cart is empty.</h2>";
    echo "<a href='payment.php'>Go back to checkout</a>";
    exit();
}

$payment_method = $_POST['payment_method'] ?? '';
$address        = $_POST['address'] ?? '';
$name           = $_POST['name'] ?? '';
$email          = $_POST['email'] ?? '';
$total_price    = $order['total'] ?? 0.0;

// ✅ check if `total` column exists in `orders` table
function hasColumn($pdoOrConn, $table, $column) {
    try {
        if ($pdoOrConn instanceof PDO) {
            $q = $pdoOrConn->prepare("SHOW COLUMNS FROM `$table` LIKE :col");
            $q->execute([':col' => $column]);
            return $q->fetch() ? true : false;
        } elseif ($pdoOrConn instanceof mysqli) {
            $q = $pdoOrConn->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
            $q->bind_param("s", $column);
            $q->execute();
            $res = $q->get_result();
            return ($res && $res->num_rows > 0);
        }
    } catch (Exception $e) {
        return false;
    }
    return false;
}

try {
    if (isset($pdo) && ($pdo instanceof PDO)) {
        $pdo->beginTransaction();

        $hasTotal = hasColumn($pdo, "orders", "total");

        if ($hasTotal) {
            $stmt = $pdo->prepare("INSERT INTO orders 
                (user_id, name, email, address, payment_method, total, created_at) 
                VALUES (:uid, :name, :email, :addr, :pm, :total, NOW())");
            $stmt->execute([
                ':uid'   => $user_id,
                ':name'  => $name,
                ':email' => $email,
                ':addr'  => $address,
                ':pm'    => $payment_method,
                ':total' => $total_price
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO orders 
                (user_id, name, email, address, payment_method, created_at) 
                VALUES (:uid, :name, :email, :addr, :pm, NOW())");
            $stmt->execute([
                ':uid'   => $user_id,
                ':name'  => $name,
                ':email' => $email,
                ':addr'  => $address,
                ':pm'    => $payment_method
            ]);
        }

        $order_id = $pdo->lastInsertId();

        // insert order items
        $stmtItem = $pdo->prepare("INSERT INTO order_items 
            (order_id, product_name, quantity, price) VALUES (:oid, :pname, :qty, :price)");
        foreach ($order['items'] as $item) {
            $stmtItem->execute([
                ':oid'   => $order_id,
                ':pname' => $item['name'],
                ':qty'   => $item['quantity'],
                ':price' => $item['price']
            ]);
        }

        $pdo->prepare("DELETE FROM cart WHERE user_id = :uid")->execute([':uid' => $user_id]);

        $pdo->commit();

    } elseif (isset($pdo) && ($pdo instanceof mysqli)) {
        $pdo->begin_transaction();

        $hasTotal = hasColumn($pdo, "orders", "total");

        if ($hasTotal) {
            $stmt = $pdo->prepare("INSERT INTO orders 
                (user_id, name, email, address, payment_method, total, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("issssd", $user_id, $name, $email, $address, $payment_method, $total_price);
        } else {
            $stmt = $pdo->prepare("INSERT INTO orders 
                (user_id, name, email, address, payment_method, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("issss", $user_id, $name, $email, $address, $payment_method);
        }
        $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();

        // insert order items
        $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_name, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($order['items'] as $item) {
            $stmtItem->bind_param("isid", $order_id, $item['name'], $item['quantity'], $item['price']);
            $stmtItem->execute();
        }
        $stmtItem->close();

        $stmtDel = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmtDel->bind_param("i", $user_id);
        $stmtDel->execute();
        $stmtDel->close();

        $pdo->commit();
    }

    unset($_SESSION['order']); // clear after success

} catch (Exception $e) {
    if (isset($pdo)) $pdo->rollBack();
    if (isset($pdo)) $pdo->rollback();
    echo "<h2>Payment failed</h2><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Success</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <div class="card p-5 shadow-lg">
    <h2 class="text-success">✅ Payment Successful!</h2>
    <p>Thank you <strong><?= htmlspecialchars($name); ?></strong>, your order has been placed.</p>
    <h5 class="mt-3">Order Summary</h5>
    <ul class="list-group mb-3">
      <?php foreach ($order['items'] as $item) { ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <?= htmlspecialchars($item['name']); ?> x <?= (int)$item['quantity']; ?>
          <span>₹<?= number_format($item['price'] * $item['quantity'], 2); ?></span>
        </li>
      <?php } ?>
      <li class="list-group-item d-flex justify-content-between align-items-center fw-bold">
        Total <span>₹<?= number_format($total_price, 2); ?></span>
      </li>
    </ul>
    <a href="/ecommerce1/index.php" class="btn btn-primary btn-lg w-100">Continue Shopping</a>

  </div>
</div>
</body>
</html>


