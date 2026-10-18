<?php
require_once __DIR__ . '/../E-Commerce-Backend/config/mongodb.php';
require_once __DIR__ . '/../E-Commerce-Backend/lib/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$products = [];

foreach ($db->wishlist->find(['user_id' => $user_id], ['sort' => ['_id' => -1]]) as $wishlistDoc) {
    $wishlist = docToArray($wishlistDoc);
    $productId = toObjectId($wishlist['product_id'] ?? '');
    $productDoc = $productId ? $db->products->findOne(['_id' => $productId]) : null;

    if (!$productDoc) {
        continue;
    }

    $product = docToArray($productDoc);
    $categoryId = toObjectId($product['category_id'] ?? '');
    $categoryDoc = $categoryId ? $db->categories->findOne(['_id' => $categoryId]) : null;
    $category = $categoryDoc ? docToArray($categoryDoc) : [];
    $product['category_name'] = $category['category_name'] ?? 'Essentials';
    $products[] = $product;
}

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/navbar.php');
?>

<section class="page-banner">
    <div class="container">
        <h1>My Wishlist</h1>
        <p>Products you have saved to purchase later.</p>
    </div>
</section>

<section class="page-content py-4">
    <div class="container">
        <?php if (!empty($products)) { ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fw-bold">Saved Items (<?php echo count($products); ?>)</h4>
                <a href="shop.php" class="btn btn-sm btn-outline-primary fw-semibold">Continue Shopping</a>
            </div>

            <div class="row g-4">
                <?php foreach ($products as $product) { ?>
                    <?php
                    $productId     = $product['id'];
                    $rating        = getProductRating(crc32($productId) % 10 + 1);
                    $discount      = getDiscountPercent((float)$product['price'], $product['discount'] ?? null);
                    $originalPrice = getOriginalPrice((float)$product['price'], $discount);
                    $price         = (float)$product['price'];
                    ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="fk-product-card h-100 d-flex flex-column bg-white rounded-3 border">
                            <div class="fk-card-image-box position-relative">
                                <span class="fk-discount-badge"><?php echo $discount; ?>% OFF</span>

                                <a
                                    href="toggle_wishlist.php?product_id=<?php echo urlencode($productId); ?>&redirect=wishlist.php"
                                    class="fk-wishlist-btn active"
                                    title="Remove from Wishlist"
                                >
                                    <i class="bi bi-heart-fill text-danger"></i>
                                </a>

                                <a href="product.php?id=<?php echo urlencode($productId); ?>" class="d-block text-center p-3">
                                    <img
                                        src="<?php echo htmlspecialchars($product['image']); ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                                        class="fk-product-img img-fluid"
                                        onerror="this.onerror=null;this.src='https://via.placeholder.com/400x300?text=No+Image';"
                                    >
                                </a>
                            </div>

                            <div class="fk-card-body p-3 d-flex flex-column flex-grow-1">
                                <div class="fk-product-category text-muted small text-uppercase mb-1">
                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                </div>

                                <h6 class="fk-product-title mb-2">
                                    <a href="product.php?id=<?php echo urlencode($productId); ?>" class="text-dark text-decoration-none">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h6>

                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="fk-rating-badge">
                                        <?php echo number_format($rating, 1); ?> <i class="bi bi-star-fill text-white small"></i>
                                    </span>
                                    <span class="fk-assured-tag">
                                        <i class="bi bi-patch-check-fill text-primary"></i> <span class="assured-text">Plus</span>
                                    </span>
                                </div>

                                <div class="fk-price-block mt-auto mb-3">
                                    <div class="d-flex flex-wrap align-items-baseline gap-2">
                                        <span class="fk-current-price">₹<?php echo number_format($price, 2); ?></span>
                                        <span class="fk-old-price">₹<?php echo number_format($originalPrice, 2); ?></span>
                                        <span class="fk-discount-text"><?php echo $discount; ?>% off</span>
                                    </div>
                                    <small class="text-muted d-block mt-1">Stock: <?php echo (int)$product['stock']; ?> available</small>
                                </div>

                                <div class="fk-card-actions d-flex gap-2">
                                    <form action="add_to_cart.php" method="POST" class="flex-grow-1">
                                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($productId); ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <input type="hidden" name="redirect" value="wishlist.php">
                                        <button type="submit" class="btn btn-sm btn-warning-custom w-100" <?php echo ((int)$product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                            <i class="bi bi-cart-plus"></i> Move to Cart
                                        </button>
                                    </form>

                                    <a href="toggle_wishlist.php?product_id=<?php echo urlencode($productId); ?>&redirect=wishlist.php" class="btn btn-sm btn-outline-danger" title="Remove">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="empty-state-card bg-white rounded-4 p-5 text-center shadow-sm border my-4">
                <div class="display-3 text-muted mb-3">🤍</div>
                <h3 class="fw-bold mb-2">Your Wishlist is Empty</h3>
                <p class="text-muted mb-4">You have not saved any items yet. Browse products and tap the heart icon to save items.</p>
                <a href="shop.php" class="btn btn-primary px-4 py-3 fw-bold">
                    Explore Store <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        <?php } ?>
    </div>
</section>

<?php include(__DIR__ . '/includes/footer.php'); ?>