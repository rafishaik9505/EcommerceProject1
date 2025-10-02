<?php
session_start();
include 'includes/db.php'; // Database connection

// Fetch products
$stmt = $pdo->query("SELECT * FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gather images from images/ folder
$imagesGlob = glob(__DIR__ . '/images/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
$allImages = array_map(function($p){ return basename($p); }, $imagesGlob);

/**
 * Find an appropriate image path for a product:
 * 1. If product image exists on disk, use it.
 * 2. Try to match tokens from product name to filenames.
 * 3. Fallback to first usable image in the folder.
 * 4. If nothing, return empty string (handled in HTML).
 */
function find_image_for_product($productName, $productImage, $allImages) {
    $baseDir = 'images/';
    // 1) explicit image
    if (!empty($productImage) && file_exists(__DIR__ . '/' . $baseDir . $productImage)) {
        return $baseDir . $productImage;
    }

    // 2) match tokens from product name
    $lowerName = strtolower($productName);
    $tokens = preg_split('/\s+|[,\-_\.]/', $lowerName, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($allImages as $img) {
        $lowImg = strtolower($img);
        foreach ($tokens as $t) {
            if (strlen($t) < 3) continue; // ignore very short tokens
            if (strpos($lowImg, $t) !== false) {
                return $baseDir . $img;
            }
        }
    }

    // 3) fallback to first usable image (ignore icons/placeholders)
    foreach ($allImages as $img) {
        if (preg_match('/cart|icon|logo|placeholder/i', $img)) continue;
        return $baseDir . $img;
    }

    // 4) no image found
    return '';
}

// Hero images (first up to 4 usable images)
$heroImages = [];
foreach ($allImages as $img) {
    if (preg_match('/cart|icon|logo|placeholder/i', $img)) continue;
    $heroImages[] = 'images/' . $img;
    if (count($heroImages) >= 4) break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Online Store | 24 Hours Delivery</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <header class="site-header">
    <div class="brand">
      <h1>LOCAL STORE</h1>
      <div class="tagline">Fast & Reliable — 24 Hours Delivery</div>
    </div>

    <nav class="main-nav">
      <a href="pages/login.php">Login</a>
      <a href="pages/register.php">Register</a>
      <a href="pages/cart.php" class="cart-link">
        <img src="images/cart-icon (1).png" alt="Cart icon" class="cart-icon"> Cart
      </a>
      <form method="POST" class="logout-form">
        <button type="submit" name="logout" class="btn-logout">Logout</button>
      </form>
    </nav>
  </header>

  <!-- Delivery bar -->
  <div class="delivery-tag">
    🚚 <strong>24 Hours Delivery</strong> — Order now and get it within the day!
  </div>

  <!-- Hero slider (uses images found in /images) -->
  <section class="hero-slider" id="heroSlider">
    <?php if (!empty($heroImages)): ?>
      <?php foreach ($heroImages as $idx => $img): ?>
        <div class="slide" style="background-image: url('<?= htmlspecialchars($img); ?>')" aria-hidden="<?= $idx===0 ? 'false' : 'true'; ?>">
          <div class="slide-overlay">
            <h2>24 HOUR DELIVERY</h2>
            <p>Quick delivery across the city — Add to cart and choose same-day shipping.</p>
            <a href="#products" class="cta">Shop Now</a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="slide slide-default">
        <div class="slide-overlay">
          <h2>24 HOUR DELIVERY</h2>
          <p>Fast delivery — images missing in your images/ folder.</p>
        </div>
      </div>
    <?php endif; ?>
  </section>

  <main class="main-container" id="products">
    <h2>Products</h2>
    <div class="product-list">
      <?php if (empty($products)) : ?>
        <p class="no-products">No products available.</p>
      <?php else : ?>
        <?php foreach ($products as $product) : ?>
          <?php $imgSrc = find_image_for_product($product['name'] ?? '', $product['image'] ?? '', $allImages); ?>
          <article class="product-card">
            <?php if (!empty($imgSrc)) : ?>
              <img src="<?= htmlspecialchars($imgSrc); ?>" alt="<?= htmlspecialchars($product['name']); ?>" loading="lazy" class="product-image">
            <?php else : ?>
              <div class="product-image product-image--empty">No image</div>
            <?php endif; ?>

            <div class="product-body">
              <h3 class="product-title"><?= htmlspecialchars($product['name']); ?></h3>
              <div class="product-price"><?= isset($product['price']) ? '₹' . number_format($product['price'], 2) : 'Price N/A'; ?></div>
              <p class="product-desc"><?= nl2br(htmlspecialchars($product['description'] ?? '')); ?></p>

              <form method="POST" action="pages/cart.php" class="addcart-form">
                <input type="hidden" name="product_id" value="<?= (int)$product['id']; ?>">
                <button type="submit" name="add_to_cart" class="btn-add">Add to Cart</button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>

  <footer class="site-footer">
    <p>&copy; <?= date('Y'); ?> Online Store | Fast 24 Hours Delivery. All rights reserved.</p>
  </footer>

  <!-- small script to auto-advance the slider (progressive enhancement) -->
  <script>
    (function(){
      const slides = document.querySelectorAll('.hero-slider .slide');
      if (!slides.length) return;
      let idx = 0;
      function show(i){
        slides.forEach((s, k) => s.style.opacity = (k===i ? '1' : '0'));
      }
      show(0);
      setInterval(()=> {
        idx = (idx + 1) % slides.length;
        show(idx);
      }, 4500);
    })();
  </script>
</body>
</html>






