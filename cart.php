<?php
session_start();
require_once __DIR__ . '/../E-Commerce-Backend/config/mongodb.php';
require_once __DIR__ . '/../E-Commerce-Backend/lib/functions.php';

redirectIfNotLoggedIn();

$user_id  = $_SESSION['user_id'];
$message  = "";
$error    = "";

// Validate the user still exists
$userOid = toObjectId($user_id);
if (!$userOid || !$db->users->findOne(['_id' => $userOid])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// ------------------------------------------------------------------
// UPDATE CART QUANTITIES
// ------------------------------------------------------------------
if (isset($_POST['update_cart']) && isset($_POST['quantities'])) {
    foreach ($_POST['quantities'] as $cart_id_str => $quantity) {
        $quantity   = max(1, (int)$quantity);
        $cartOid    = toObjectId($cart_id_str);
        if (!$cartOid) continue;

        $cartItem = $db->cart->findOne(['_id' => $cartOid, 'user_id' => $user_id]);
        if (!$cartItem) continue;

        $cartArr    = docToArray($cartItem);
        $productOid = toObjectId($cartArr['product_id']);
        $product    = $productOid ? $db->products->findOne(['_id' => $productOid]) : null;

        if ($product) {
            $productArr = docToArray($product);
            $maxStock   = (int)$productArr['stock'];
            if ($quantity > $maxStock) $quantity = $maxStock;
        }

        $db->cart->updateOne(
            ['_id' => $cartOid, 'user_id' => $user_id],
            ['$set' => ['quantity' => $quantity]]
        );
    }
    $message = "Cart updated successfully.";
}

// ------------------------------------------------------------------
// REMOVE FROM CART
// ------------------------------------------------------------------
if (isset($_GET['remove'])) {
    $cartOid = toObjectId($_GET['remove']);
    if ($cartOid) {
        $res = $db->cart->deleteOne(['_id' => $cartOid, 'user_id' => $user_id]);
        $message = $res->getDeletedCount() > 0 ? "Item removed from cart." : "Failed to remove item.";
    }
}

// ------------------------------------------------------------------
// FETCH CART ITEMS
// ------------------------------------------------------------------
$cartCursor = $db->cart->find(['user_id' => $user_id], ['sort' => ['_id' => -1]]);
$cartItems  = [];
$grand_total = 0;
$total_original = 0;

foreach ($cartCursor as $cartDoc) {
    $item       = docToArray($cartDoc);
    $productOid = toObjectId($item['product_id']);
    $product    = $productOid ? $db->products->findOne(['_id' => $productOid]) : null;

    if (!$product) continue;
    $productArr = docToArray($product);

    $price = (float)$productArr['price'];
    $discount = getDiscountPercent($price, $productArr['discount'] ?? null);
    $origPrice = getOriginalPrice($price, $discount);
    $qty = (int)$item['quantity'];

    $mergedItem = array_merge($item, [
        'cart_id'        => $item['id'],
        'product_id'     => $productArr['id'],
        'name'           => $productArr['name'],
        'price'          => $price,
        'original_price' => $origPrice,
        'discount'       => $discount,
        'stock'          => (int)$productArr['stock'],
        'image'          => $productArr['image'] ?? ''
    ]);

    $grand_total += ($price * $qty);
    $total_original += ($origPrice * $qty);
    $cartItems[] = $mergedItem;
}

$total_savings = max(0, $total_original - $grand_total);

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/navbar.php');
?>

<section class="page-banner">
    <div class="container">
        <h1>Shopping Cart</h1>
        <p>Review items, adjust quantities, and proceed to campus delivery checkout.</p>
    </div>
</section>

<section class="page-content py-4">
    <div class="container">
        <?php if ($message !== "") { ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php } ?>

        <?php if ($error !== "") { ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php } ?>

        <?php if (!empty($cartItems)) { ?>
            <form method="POST" id="cartForm">
                <div class="row g-4">
                    <!-- LEFT COLUMN: CART ITEMS (FLIPKART STYLE) -->
                    <div class="col-lg-8">
                        <div class="fk-cart-items-wrapper bg-white rounded-4 shadow-sm border p-4 mb-3">
                            <div class="d-flex justify-content-between align-items-center pb-3 mb-3 border-bottom">
                                <h4 class="mb-0 fw-bold">Cart Items (<?php echo count($cartItems); ?>)</h4>
                                <span class="text-success small fw-semibold"><i class="bi bi-geo-alt-fill"></i> Free delivery to GBU campus</span>
                            </div>

                            <?php foreach ($cartItems as $item) {
                                $price = (float)$item['price'];
                                $qty   = (int)$item['quantity'];
                                $subtotal = $price * $qty;
                            ?>
                                <div class="fk-cart-item-card p-3 mb-3 border rounded-3 position-relative cart-row">
                                    <div class="row g-3 align-items-center">
                                        <!-- Image -->
                                        <div class="col-3 col-md-2 text-center">
                                            <img
                                                src="<?php echo htmlspecialchars($item['image']); ?>"
                                                alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                class="img-fluid rounded-3 fk-cart-thumb"
                                                onerror="this.onerror=null;this.src='https://via.placeholder.com/200x200?text=Product';"
                                            >
                                        </div>

                                        <!-- Details -->
                                        <div class="col-9 col-md-6">
                                            <h6 class="cart-product-title mb-1 fw-bold">
                                                <a href="product.php?id=<?php echo urlencode($item['product_id']); ?>" class="text-dark text-decoration-none">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </a>
                                            </h6>
                                            <div class="d-flex flex-wrap align-items-baseline gap-2 mb-2">
                                                <span class="fs-5 fw-bold text-dark">₹<span class="item-price"><?php echo number_format($price, 2, '.', ''); ?></span></span>
                                                <span class="text-muted text-decoration-line-through small">₹<?php echo number_format($item['original_price'], 2); ?></span>
                                                <span class="text-success small fw-bold"><?php echo $item['discount']; ?>% off</span>
                                            </div>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle small">
                                                In Stock (<?php echo (int)$item['stock']; ?> available)
                                            </span>
                                        </div>

                                        <!-- Quantity and Subtotal -->
                                        <div class="col-12 col-md-4 text-md-end mt-2 mt-md-0 d-flex flex-md-column justify-content-between align-items-end">
                                            <div class="cart-qty-control mb-md-2 d-flex align-items-center gap-2">
                                                <label class="small text-muted d-none d-md-inline">Qty:</label>
                                                <input
                                                    type="number"
                                                    name="quantities[<?php echo htmlspecialchars($item['cart_id']); ?>]"
                                                    class="form-control form-control-sm cart-qty-input text-center fw-bold"
                                                    style="width: 70px;"
                                                    min="1"
                                                    max="<?php echo (int)$item['stock']; ?>"
                                                    value="<?php echo $qty; ?>"
                                                >
                                            </div>

                                            <div class="mb-2">
                                                <small class="text-muted">Subtotal: </small>
                                                <strong class="fs-6 text-primary-custom">₹<span class="item-subtotal"><?php echo number_format($subtotal, 2, '.', ''); ?></span></strong>
                                            </div>

                                            <a
                                                href="cart.php?remove=<?php echo htmlspecialchars($item['cart_id']); ?>"
                                                class="btn btn-outline-danger btn-sm px-3"
                                                onclick="return confirm('Remove this item from your cart?');"
                                            >
                                                <i class="bi bi-trash3"></i> Remove
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>

                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                <a href="shop.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Continue Shopping
                                </a>
                                <button type="submit" name="update_cart" class="btn btn-outline-primary fw-semibold">
                                    <i class="bi bi-arrow-clockwise"></i> Update Quantities
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: FLIPKART PRICE DETAILS SUMMARY -->
                    <div class="col-lg-4">
                        <div class="fk-price-details-card bg-white rounded-4 shadow-sm border p-4 sticky-top" style="top: 100px;">
                            <h5 class="fw-bold text-uppercase text-muted border-bottom pb-3 mb-3 small letter-spacing-1">
                                Price Details
                            </h5>

                            <div class="d-flex justify-content-between py-2">
                                <span class="text-dark">Price (<?php echo count($cartItems); ?> items)</span>
                                <span class="fw-semibold">₹<?php echo number_format($total_original, 2); ?></span>
                            </div>

                            <div class="d-flex justify-content-between py-2 text-success">
                                <span>Discount</span>
                                <span class="fw-semibold">- ₹<?php echo number_format($total_savings, 2); ?></span>
                            </div>

                            <div class="d-flex justify-content-between py-2 text-success">
                                <span>Delivery Charges</span>
                                <span class="fw-semibold"><del class="text-muted">₹40</del> FREE</span>
                            </div>

                            <hr class="my-3">

                            <div class="d-flex justify-content-between py-2 fs-5 fw-bold">
                                <span>Total Amount</span>
                                <span class="text-primary-custom">₹<span id="grandTotal"><?php echo number_format($grand_total, 2, '.', ''); ?></span></span>
                            </div>

                            <div class="savings-alert p-2 my-3 rounded-2 bg-success-subtle text-success text-center small fw-bold">
                                🎉 You will save ₹<?php echo number_format($total_savings, 2); ?> on this order!
                            </div>

                            <a href="checkout.php" class="btn btn-warning-custom w-100 py-3 fw-bold fs-6 shadow-sm">
                                Proceed to Checkout <i class="bi bi-arrow-right ms-1"></i>
                            </a>

                            <div class="trust-shield mt-3 text-center text-muted small d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-shield-lock-fill text-success fs-5"></i>
                                <span>Safe and Secure Payments. 100% Authentic products.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php } else { ?>
            <div class="empty-cart-card bg-white rounded-4 p-5 text-center shadow-sm border my-4">
                <div class="empty-cart-icon mb-3 text-muted display-3">🛒</div>
                <h3 class="fw-bold mb-2">Your Cart is Currently Empty!</h3>
                <p class="text-muted mb-4">Explore our wide selection of academic books, hostel gear, electronics, and stationery.</p>
                <a href="shop.php" class="btn btn-primary px-4 py-3 fw-bold">
                    Start Shopping Now <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        <?php } ?>
    </div>
</section>

<script>
function updateCartTotals() {
    let grandTotal = 0;

    document.querySelectorAll('.cart-row').forEach(function(row) {
        const priceElement    = row.querySelector('.item-price');
        const qtyInput        = row.querySelector('.cart-qty-input');
        const subtotalElement = row.querySelector('.item-subtotal');

        if (!priceElement || !qtyInput || !subtotalElement) return;

        let price = parseFloat(priceElement.textContent) || 0;
        let qty   = parseInt(qtyInput.value) || 1;
        let max   = parseInt(qtyInput.getAttribute('max')) || qty;

        if (qty < 1) qty = 1;
        if (qty > max) qty = max;

        qtyInput.value = qty;

        let subtotal = price * qty;
        subtotalElement.textContent = subtotal.toFixed(2);

        grandTotal += subtotal;
    });

    const grandTotalEl = document.getElementById('grandTotal');
    if (grandTotalEl) {
        grandTotalEl.textContent = grandTotal.toFixed(2);
    }
}

document.querySelectorAll('.cart-qty-input').forEach(function(input) {
    input.addEventListener('input', updateCartTotals);
    input.addEventListener('change', updateCartTotals);
});
</script>

<?php include(__DIR__ . '/includes/footer.php'); ?>