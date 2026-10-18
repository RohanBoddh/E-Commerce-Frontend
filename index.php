<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../E-Commerce-Backend/config/mongodb.php';
require_once __DIR__ . '/../E-Commerce-Backend/lib/functions.php';

// Fetch categories
$homeCategories = [];
try {
    $catCursor = $db->categories->find([], ['sort' => ['category_name' => 1]]);
    $homeCategories = cursorToArray($catCursor);
} catch (Throwable $e) {}

// Fetch trending / featured products
$trendingProducts = [];
try {
    $productCursor = $db->products->find([], ['sort' => ['_id' => -1], 'limit' => 16]);
    foreach ($productCursor as $doc) {
        $p = docToArray($doc);
        // Category Name
        $catName = 'Essentials';
        if (!empty($p['category_id'])) {
            $catOid = toObjectId($p['category_id']);
            $cat = $catOid ? $db->categories->findOne(['_id' => $catOid]) : null;
            if ($cat) {
                $catName = docToArray($cat)['category_name'];
            }
        }
        $p['category_name'] = $catName;
        $trendingProducts[] = $p;
    }
} catch (Throwable $e) {}

if (empty($trendingProducts)) {
    $fallback = getCatalogFallback();
    $trendingProducts = array_slice($fallback['products'] ?? [], 0, 16);
    if (empty($homeCategories)) {
        $homeCategories = $fallback['categories'] ?? [];
    }
}

// Wishlist product IDs for logged-in user
$userWishlistIds = [];
if (isset($_SESSION['user_id'])) {
    try {
        $wCursor = $db->wishlist->find(['user_id' => $_SESSION['user_id']]);
        foreach ($wCursor as $w) {
            $userWishlistIds[] = docToArray($w)['product_id'];
        }
    } catch (Throwable $e) {}
}

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/navbar.php');
?>

<!-- FLIPKART / AMAZON HERO BANNER SLIDER -->
<section class="fk-hero-carousel-section">
    <div class="container py-3">
        <div id="heroBannerCarousel" class="carousel slide fk-hero-carousel shadow-sm rounded-4 overflow-hidden" data-bs-ride="carousel" data-bs-interval="4500">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#heroBannerCarousel" data-bs-slide-to="0" class="active"></button>
                <button type="button" data-bs-target="#heroBannerCarousel" data-bs-slide-to="1"></button>
                <button type="button" data-bs-target="#heroBannerCarousel" data-bs-slide-to="2"></button>
            </div>
            
            <div class="carousel-inner">
                <!-- Slide 1: Big Campus Sale -->
                <div class="carousel-item active">
                    <div class="fk-banner-slide slide-blue">
                        <div class="row align-items-center h-100 g-4">
                            <div class="col-lg-7 text-white">
                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3 fs-6">
                                    ⚡ BIG CAMPUS SALE DAYS
                                </span>
                                <h1 class="display-5 fw-extrabold mb-3">Up to 60% Off on Student Tech & Gadgets</h1>
                                <p class="lead mb-4 text-light-opacity">
                                    Headphones, USB drives, laptop stands, calculators, and daily electronics with zero delivery fees inside campus.
                                </p>
                                <div class="d-flex flex-wrap gap-3">
                                    <a href="shop.php" class="btn btn-warning-custom px-4 py-3 fw-bold">Shop Tech Deals</a>
                                    <a href="shop.php" class="btn btn-outline-light px-4 py-3 fw-semibold">View All Offers</a>
                                </div>
                            </div>
                            <div class="col-lg-5 text-center d-none d-lg-block">
                                <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=700&q=80" alt="Tech Deals" class="img-fluid rounded-4 shadow-lg banner-img">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slide 2: Academic Books & Stationery -->
                <div class="carousel-item">
                    <div class="fk-banner-slide slide-navy">
                        <div class="row align-items-center h-100 g-4">
                            <div class="col-lg-7 text-white">
                                <span class="badge bg-success px-3 py-2 rounded-pill fw-bold mb-3 fs-6">
                                    📚 SEMESTER ESSENTIALS
                                </span>
                                <h1 class="display-5 fw-extrabold mb-3">Academic Books, Registers & Stationery</h1>
                                <p class="lead mb-4 text-light-opacity">
                                    Engineering, Computer Science, Management, and Biotechnology reference books at student-exclusive discounted prices.
                                </p>
                                <div class="d-flex flex-wrap gap-3">
                                    <a href="shop.php" class="btn btn-warning-custom px-4 py-3 fw-bold">Explore Books</a>
                                    <a href="shop.php" class="btn btn-outline-light px-4 py-3 fw-semibold">Stationery Bundles</a>
                                </div>
                            </div>
                            <div class="col-lg-5 text-center d-none d-lg-block">
                                <img src="https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?auto=format&fit=crop&w=700&q=80" alt="Books" class="img-fluid rounded-4 shadow-lg banner-img">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slide 3: Hostel Essentials -->
                <div class="carousel-item">
                    <div class="fk-banner-slide slide-teal">
                        <div class="row align-items-center h-100 g-4">
                            <div class="col-lg-7 text-white">
                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3 fs-6">
                                    🏠 HOSTEL LIFE EASY
                                </span>
                                <h1 class="display-5 fw-extrabold mb-3">Bed Study Tables, Flasks & Room Gear</h1>
                                <p class="lead mb-4 text-light-opacity">
                                    Get everything you need for your hostel room delivered straight to your block within minutes.
                                </p>
                                <div class="d-flex flex-wrap gap-3">
                                    <a href="shop.php" class="btn btn-warning-custom px-4 py-3 fw-bold">Shop Hostel Items</a>
                                    <a href="about.php" class="btn btn-outline-light px-4 py-3 fw-semibold">Learn More</a>
                                </div>
                            </div>
                            <div class="col-lg-5 text-center d-none d-lg-block">
                                <img src="https://images.unsplash.com/photo-1602143407151-7111542de6e8?auto=format&fit=crop&w=700&q=80" alt="Hostel Gear" class="img-fluid rounded-4 shadow-lg banner-img">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#heroBannerCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroBannerCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>
    </div>
</section>

<!-- AMAZON / FLIPKART VALUE PROPOSITION STRIP -->
<section class="fk-features-strip my-3">
    <div class="container">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="feature-box d-flex align-items-center gap-3 p-3 bg-white rounded-3 shadow-sm">
                    <div class="feature-icon text-primary fs-2"><i class="bi bi-truck"></i></div>
                    <div>
                        <h6 class="mb-0 fw-bold">Free Campus Delivery</h6>
                        <small class="text-muted">On all student orders</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="feature-box d-flex align-items-center gap-3 p-3 bg-white rounded-3 shadow-sm">
                    <div class="feature-icon text-success fs-2"><i class="bi bi-cash-coin"></i></div>
                    <div>
                        <h6 class="mb-0 fw-bold">Cash on Delivery</h6>
                        <small class="text-muted">Pay on room delivery</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="feature-box d-flex align-items-center gap-3 p-3 bg-white rounded-3 shadow-sm">
                    <div class="feature-icon text-warning fs-2"><i class="bi bi-patch-check-fill"></i></div>
                    <div>
                        <h6 class="mb-0 fw-bold">100% Genuine</h6>
                        <small class="text-muted">Verified products</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="feature-box d-flex align-items-center gap-3 p-3 bg-white rounded-3 shadow-sm">
                    <div class="feature-icon text-danger fs-2"><i class="bi bi-arrow-repeat"></i></div>
                    <div>
                        <h6 class="mb-0 fw-bold">Instant Exchange</h6>
                        <small class="text-muted">Hassle-free replacement</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SHOP BY POPULAR CATEGORIES -->
<?php if (!empty($homeCategories)) { ?>
<section class="fk-category-grid-section py-4">
    <div class="container">
        <div class="section-card-wrapper bg-white p-4 rounded-4 shadow-sm mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="section-heading mb-0">Shop by Categories</h3>
                    <small class="text-muted">Find exactly what you need for campus life</small>
                </div>
                <a href="shop.php" class="btn btn-sm btn-outline-primary fw-semibold">View All <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="row g-3">
                <?php
                $catIcons = [
                    'Books & Academics' => '📚',
                    'Electronics & Gadgets' => '💻',
                    'Stationery & Study' => '✏️',
                    'Hostel Essentials' => '🏠',
                    'Fashion & Campus Wear' => '👕',
                    'Sports & Fitness' => '⚽',
                    'Snacks & Campus Beverages' => '☕'
                ];
                $catImages = [
                    'Books & Academics' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=400&q=80',
                    'Electronics & Gadgets' => 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?auto=format&fit=crop&w=400&q=80',
                    'Stationery & Study' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=400&q=80',
                    'Hostel Essentials' => 'https://images.unsplash.com/photo-1602143407151-7111542de6e8?auto=format&fit=crop&w=400&q=80',
                    'Fashion & Campus Wear' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=400&q=80',
                    'Sports & Fitness' => 'https://images.unsplash.com/photo-1517649763962-0c623266ddc0?auto=format&fit=crop&w=400&q=80',
                    'Snacks & Campus Beverages' => 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=400&q=80'
                ];
                foreach ($homeCategories as $cat) {
                    $catTitle = $cat['category_name'];
                    $img = $catImages[$catTitle] ?? 'https://images.unsplash.com/photo-1556740749-887f6717d7e4?auto=format&fit=crop&w=400&q=80';
                ?>
                    <div class="col-6 col-md-4 col-lg-2">
                        <a href="shop.php?category=<?php echo urlencode($cat['id']); ?>" class="fk-category-card text-center text-decoration-none d-block p-3 rounded-3">
                            <div class="cat-img-wrap mb-2">
                                <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($catTitle); ?>" class="img-fluid rounded-circle cat-thumbnail">
                            </div>
                            <h6 class="cat-card-title text-dark mb-1 fw-bold"><?php echo htmlspecialchars($catTitle); ?></h6>
                            <span class="cat-card-sub text-primary small fw-semibold">Explore Deals <i class="bi bi-chevron-right"></i></span>
                        </a>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</section>
<?php } ?>

<!-- TRENDING PRODUCTS / DEALS OF THE DAY -->
<section class="fk-products-showcase py-2">
    <div class="container">
        <div class="section-card-wrapper bg-white p-4 rounded-4 shadow-sm mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <h3 class="section-heading mb-0">Deals of the Day</h3>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill small">
                        <i class="bi bi-alarm-fill me-1"></i> Special Student Pricing
                    </span>
                </div>
                <a href="shop.php" class="btn btn-sm btn-primary px-3 py-2 fw-semibold">
                    View All Products <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="row g-4">
                <?php if (!empty($trendingProducts)) { ?>
                    <?php foreach ($trendingProducts as $product) {
                        $productId = $product['id'];
                        $price = (float)$product['price'];
                        $discount = getDiscountPercent($price);
                        $originalPrice = getOriginalPrice($price, $discount);
                        $rating = getProductRating(crc32($productId) % 10 + 1);
                        $isWishlisted = in_array($productId, $userWishlistIds);
                    ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="fk-product-card h-100 d-flex flex-column bg-white rounded-3 border">
                                <!-- CARD IMAGE & BADGES -->
                                <div class="fk-card-image-box position-relative">
                                    <span class="fk-discount-badge"><?php echo $discount; ?>% OFF</span>
                                    
                                    <?php if (isset($_SESSION['user_id'])) { ?>
                                        <a href="toggle_wishlist.php?product_id=<?php echo urlencode($productId); ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>"
                                           class="fk-wishlist-btn <?php echo $isWishlisted ? 'active' : ''; ?>"
                                           title="<?php echo $isWishlisted ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
                                            <i class="bi <?php echo $isWishlisted ? 'bi-heart-fill text-danger' : 'bi-heart'; ?>"></i>
                                        </a>
                                    <?php } ?>

                                    <a href="product.php?id=<?php echo urlencode($productId); ?>" class="d-block text-center p-3">
                                        <img src="<?php echo htmlspecialchars($product['image'] ?? ''); ?>"
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="fk-product-img img-fluid"
                                             onerror="this.onerror=null;this.src='https://via.placeholder.com/400x300?text=Product+Image';">
                                    </a>
                                </div>

                                <!-- CARD BODY -->
                                <div class="fk-card-body p-3 d-flex flex-column flex-grow-1">
                                    <div class="fk-product-category text-muted small text-uppercase mb-1">
                                        <?php echo htmlspecialchars($product['category_name']); ?>
                                    </div>

                                    <h6 class="fk-product-title mb-2">
                                        <a href="product.php?id=<?php echo urlencode($productId); ?>" class="text-dark text-decoration-none">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h6>

                                    <!-- RATING & ASSURED BADGE -->
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="fk-rating-badge">
                                            <?php echo number_format($rating, 1); ?> <i class="bi bi-star-fill text-white small"></i>
                                        </span>
                                        <span class="fk-assured-tag">
                                            <i class="bi bi-patch-check-fill text-primary"></i> <span class="assured-text">Plus</span>
                                        </span>
                                    </div>

                                    <!-- PRICE BLOCK -->
                                    <div class="fk-price-block mt-auto mb-3">
                                        <div class="d-flex align-items-baseline gap-2">
                                            <span class="fk-current-price">₹<?php echo number_format($price, 2); ?></span>
                                            <span class="fk-old-price">₹<?php echo number_format($originalPrice, 2); ?></span>
                                            <span class="fk-discount-text text-success small fw-bold"><?php echo $discount; ?>% off</span>
                                        </div>
                                        <div class="text-success small fw-semibold">
                                            <i class="bi bi-truck me-1"></i> Free delivery
                                        </div>
                                    </div>

                                    <!-- ACTIONS -->
                                    <div class="fk-card-actions d-flex gap-2">
                                        <a href="product.php?id=<?php echo urlencode($productId); ?>" class="btn btn-sm btn-outline-primary flex-grow-1">
                                            View
                                        </a>

                                        <form action="add_to_cart.php" method="POST" class="flex-grow-1">
                                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($productId); ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                            <button type="submit" class="btn btn-sm btn-warning-custom w-100" <?php echo ((int)$product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                                <?php echo ((int)$product['stock'] > 0) ? '<i class="bi bi-cart-plus"></i> Add' : 'Out'; ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <div class="col-12 text-center py-5">
                        <div class="alert alert-info">Store is preparing new products. Check back shortly!</div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</section>

<!-- EXCLUSIVE STUDENT PROMO BANNER -->
<section class="fk-student-offer-section py-4">
    <div class="container">
        <div class="student-offer-banner p-4 p-md-5 rounded-4 shadow-sm text-white position-relative overflow-hidden">
            <div class="row align-items-center position-relative z-1">
                <div class="col-lg-8">
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3 fs-6">
                        🎓 GAUTAM BUDDHA UNIVERSITY SPECIAL
                    </span>
                    <h2 class="display-6 fw-bold mb-3">Hostel Room Delivery in Under 30 Minutes</h2>
                    <p class="lead mb-4 text-light-opacity">
                        Order stationery before late-night assignments or get snacks and tech accessories delivered directly to your hostel gate with <strong>zero delivery fee</strong>.
                    </p>
                    <a href="shop.php" class="btn btn-warning-custom px-4 py-3 fw-bold">
                        Browse All Campus Essentials <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include(__DIR__ . '/includes/footer.php'); ?>