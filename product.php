<?php
session_start();
require_once __DIR__ . '/../E-Commerce-Backend/config/mongodb.php';
require_once __DIR__ . '/../E-Commerce-Backend/lib/functions.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: shop.php");
    exit();
}

$product_id_str = trim($_GET['id']);
$product = null;
try {
    $productOid = toObjectId($product_id_str);
    if ($productOid) {
        $productDoc = $db->products->findOne(['_id' => $productOid]);
        if ($productDoc) {
            $product = docToArray($productDoc);
        }
    }
} catch (Throwable $e) {}

if (!$product) {
    $fallback = getCatalogFallback();
    foreach ($fallback['products'] ?? [] as $p) {
        if (($p['id'] ?? '') === $product_id_str || ($p['product_id'] ?? '') == $product_id_str) {
            $product = $p;
            break;
        }
    }
}

if (!$product) {
    header("Location: shop.php");
    exit();
}

// Attach category name
$categoryName = 'Essentials';
if (!empty($product['category_id'])) {
    $catOid = toObjectId($product['category_id']);
    $cat    = $catOid ? $db->categories->findOne(['_id' => $catOid]) : null;
    if ($cat) {
        $catArr       = docToArray($cat);
        $categoryName = $catArr['category_name'];
    }
}
$product['category_name'] = $categoryName;

$message = "";
$error   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_to_cart']) || isset($_POST['buy_now']))) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $user_id  = $_SESSION['user_id'];
    $userOid  = toObjectId($user_id);

    if (!$userOid || !$db->users->findOne(['_id' => $userOid])) {
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit();
    }

    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if ($quantity < 1) $quantity = 1;

    if ((int)$product['stock'] <= 0) {
        $error = "This product is out of stock.";
    } elseif ($quantity > (int)$product['stock']) {
        $error = "Requested quantity exceeds available stock.";
    } else {
        $existingCart = $db->cart->findOne(['user_id' => $user_id, 'product_id' => $product['id']]);

        if ($existingCart) {
            $existingArr = docToArray($existingCart);
            $newQuantity = (int)$existingArr['quantity'] + $quantity;

            if ($newQuantity > (int)$product['stock']) {
                $newQuantity = (int)$product['stock'];
            }
            $db->cart->updateOne(
                ['_id' => toObjectId($existingArr['id'])],
                ['$set' => ['quantity' => $newQuantity]]
            );
        } else {
            $db->cart->insertOne([
                'user_id'    => $user_id,
                'product_id' => $product['id'],
                'quantity'   => $quantity,
                'created_at' => nowUTC()
            ]);
        }

        if (isset($_POST['buy_now'])) {
            header("Location: checkout.php");
            exit();
        }

        $message = "Product added to your cart successfully!";
    }
}

$price = (float)$product['price'];
$discount = getDiscountPercent($price, $product['discount'] ?? null);
$originalPrice = getOriginalPrice($price, $discount);
$stock = (int)$product['stock'];
$rating = getProductRating(crc32($product['id']) % 10 + 1);

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/navbar.php');
?>

<section class="page-banner">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="shop.php" class="text-white opacity-75 text-decoration-none">Shop</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page"><?php echo htmlspecialchars($product['category_name']); ?></li>
            </ol>
        </nav>
        <h1 class="fs-2 mb-0"><?php echo htmlspecialchars($product['name']); ?></h1>
    </div>
</section>

<section class="page-content py-4">
    <div class="container">

        <?php if ($message !== "") { ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($message); ?>
                <a href="cart.php" class="alert-link ms-2">View Cart</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php } ?>

        <?php if ($error !== "") { ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php } ?>

        <div class="fk-product-detail-card bg-white p-4 p-lg-5 rounded-4 shadow-sm border mb-4">
            <div class="row g-5">
                <!-- PRODUCT IMAGE COLUMN -->
                <div class="col-lg-5">
                    <div class="fk-detail-img-wrapper position-sticky" style="top: 100px;">
                        <div class="main-img-box text-center p-4 border rounded-4 bg-light position-relative">
                            <span class="fk-discount-badge fs-6 px-3 py-2"><?php echo $discount; ?>% OFF</span>
                            
                            <img
                                src="<?php echo htmlspecialchars($product['image'] ?? ''); ?>"
                                alt="<?php echo htmlspecialchars($product['name']); ?>"
                                class="img-fluid rounded-3 detail-main-image"
                                onerror="this.onerror=null;this.src='https://via.placeholder.com/600x500?text=Product+Image';"
                            >
                        </div>

                        <!-- Highlights strip under image -->
                        <div class="row g-2 mt-3 text-center">
                            <div class="col-4">
                                <div class="p-2 border rounded-3 bg-light small">
                                    <i class="bi bi-truck text-primary d-block fs-5"></i>
                                    <span>Fast Campus Delivery</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 border rounded-3 bg-light small">
                                    <i class="bi bi-cash-coin text-success d-block fs-5"></i>
                                    <span>Cash on Delivery</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 border rounded-3 bg-light small">
                                    <i class="bi bi-arrow-repeat text-warning d-block fs-5"></i>
                                    <span>Easy Exchange</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PRODUCT INFO COLUMN -->
                <div class="col-lg-7">
                    <div class="fk-detail-info">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-2 fw-semibold">
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </span>

                        <h2 class="fk-detail-heading fw-bold text-dark mb-3">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h2>

                        <!-- RATING & ASSURED -->
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="fk-rating-badge fs-6 px-3 py-1">
                                <?php echo number_format($rating, 1); ?> <i class="bi bi-star-fill text-white small"></i>
                            </span>
                            <span class="text-muted small">128 Ratings & 45 Verified Student Reviews</span>
                            <span class="fk-assured-tag">
                                <i class="bi bi-patch-check-fill text-primary"></i> <span class="assured-text">Plus Assured</span>
                            </span>
                        </div>

                        <hr class="my-3">

                        <!-- PRICE BLOCK (FLIPKART STYLE) -->
                        <div class="detail-price-box p-3 rounded-3 bg-light mb-4">
                            <div class="special-price-tag text-success fw-bold small mb-1">
                                <i class="bi bi-tag-fill me-1"></i> Special Student Deal
                            </div>
                            <div class="d-flex flex-wrap align-items-baseline gap-2 gap-sm-3">
                                <span class="fs-1 fw-extrabold text-dark">₹<span id="unitPrice"><?php echo number_format($price, 2, '.', ''); ?></span></span>
                                <span class="fs-5 text-muted text-decoration-line-through">₹<?php echo number_format($originalPrice, 2); ?></span>
                                <span class="badge bg-success fs-6 fw-bold px-2 py-1"><?php echo $discount; ?>% OFF</span>
                                <span class="text-success fw-bold small">You Save ₹<?php echo number_format($originalPrice - $price, 2); ?>!</span>
                            </div>
                            <div class="text-success small fw-semibold mt-1">
                                <i class="bi bi-check-circle-fill me-1"></i> Inclusive of all campus taxes & free hostel gate delivery
                            </div>
                        </div>

                        <!-- DESCRIPTION -->
                        <div class="product-description-wrap mb-4">
                            <h5 class="fw-bold mb-2">Product Description</h5>
                            <p class="text-muted lh-lg"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>

                        <!-- STOCK & QUANTITY FORM -->
                        <form method="POST" class="detail-order-form">
                            <div class="d-flex align-items-center gap-4 mb-4">
                                <div>
                                    <label class="form-label fw-bold mb-1">Select Quantity</label>
                                    <div class="qty-stepper d-flex align-items-center border rounded-3 overflow-hidden bg-white">
                                        <button type="button" class="btn btn-light qty-step-btn px-3 py-2 fw-bold" onclick="decreaseQty()">-</button>
                                        <input
                                            type="number"
                                            id="qty"
                                            name="quantity"
                                            class="form-control border-0 text-center fw-bold"
                                            style="width: 60px;"
                                            min="1"
                                            max="<?php echo $stock; ?>"
                                            value="1"
                                            <?php echo ($stock <= 0) ? 'disabled' : ''; ?>
                                        >
                                        <button type="button" class="btn btn-light qty-step-btn px-3 py-2 fw-bold" onclick="increaseQty(<?php echo $stock; ?>)">+</button>
                                    </div>
                                </div>

                                <div>
                                    <label class="form-label fw-bold mb-1">Availability</label>
                                    <div>
                                        <?php if ($stock > 0) { ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-semibold">
                                                <i class="bi bi-check2-circle me-1"></i> In Stock (<?php echo $stock; ?> units available)
                                            </span>
                                        <?php } else { ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill fw-semibold">
                                                Out of Stock
                                            </span>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>

                            <div class="total-preview mb-4">
                                <span class="text-muted">Total Price: </span>
                                <strong class="fs-4 text-primary-custom">₹<span id="totalPrice"><?php echo number_format($price, 2, '.', ''); ?></span></strong>
                            </div>

                            <!-- CTA BUTTONS (FLIPKART STYLE ADD TO CART & BUY NOW) -->
                            <div class="d-flex flex-wrap gap-3">
                                <?php if ($stock > 0) { ?>
                                    <button type="submit" name="add_to_cart" class="btn btn-warning-custom px-4 py-3 fw-bold flex-grow-1 shadow-sm fs-6">
                                        <i class="bi bi-cart3 me-1"></i> ADD TO CART
                                    </button>

                                    <button type="submit" name="buy_now" class="btn btn-orange-custom px-4 py-3 fw-bold flex-grow-1 shadow-sm fs-6">
                                        <i class="bi bi-lightning-charge-fill me-1"></i> BUY NOW
                                    </button>
                                <?php } else { ?>
                                    <button type="button" class="btn btn-secondary px-4 py-3 fw-bold flex-grow-1" disabled>
                                        CURRENTLY OUT OF STOCK
                                    </button>
                                <?php } ?>

                                <a href="shop.php" class="btn btn-outline-secondary px-4 py-3 fw-semibold">
                                    Back to Shop
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
const unitPrice = <?php echo json_encode($price); ?>;
const qtyInput  = document.getElementById('qty');
const totalPrice = document.getElementById('totalPrice');

function updateTotal() {
    if (!qtyInput || !totalPrice) return;
    let qty = parseInt(qtyInput.value || 1, 10);
    if (qty < 1) qty = 1;
    if (qty > <?php echo $stock; ?>) qty = <?php echo $stock; ?>;
    qtyInput.value = qty;
    totalPrice.textContent = (qty * unitPrice).toFixed(2);
}

function increaseQty(maxStock) {
    if (!qtyInput) return;
    let qty = parseInt(qtyInput.value || 1, 10);
    if (qty < maxStock) { qtyInput.value = qty + 1; updateTotal(); }
}

function decreaseQty() {
    if (!qtyInput) return;
    let qty = parseInt(qtyInput.value || 1, 10);
    if (qty > 1) { qtyInput.value = qty - 1; updateTotal(); }
}

if (qtyInput) { qtyInput.addEventListener('input', updateTotal); }
</script>

<?php include(__DIR__ . '/includes/footer.php'); ?>