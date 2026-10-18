<?php
session_start();
require_once __DIR__ . '/../E-Commerce-Backend/config/mongodb.php';
require_once __DIR__ . '/../E-Commerce-Backend/lib/functions.php';

$search   = isset($_GET['search'])   ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$page     = isset($_GET['page'])     ? (int)$_GET['page'] : 1;

$sortKey  = isset($_GET['sort'])     ? trim($_GET['sort']) : 'newest';

if ($page < 1) $page = 1;

$limit  = 12;
$offset = ($page - 1) * $limit;

$allCategories = [];
$products = [];
$totalProducts = 0;
$totalPages = 1;

try {
    // Fetch categories for the filter
    $categoryCursor = $db->categories->find([], ['sort' => ['category_name' => 1]]);
    $allCategories  = cursorToArray($categoryCursor);

    // Build product filter
    $filter = [];
    if ($search !== '') {
        $filter['name'] = ['$regex' => $search, '$options' => 'i'];
    }
    if ($category !== '') {
        $filter['category_id'] = $category;
    }

    $sortMap = [
        'newest'     => ['_id' => -1],
        'price_asc'  => ['price' => 1],
        'price_desc' => ['price' => -1],
        'name_asc'   => ['name' => 1]
    ];
    $sortCriteria = $sortMap[$sortKey] ?? ['_id' => -1];

    $totalProducts = (int)$db->products->countDocuments($filter);
    $totalPages    = max(1, (int)ceil($totalProducts / $limit));

    $productCursor = $db->products->find($filter, [
        'sort'  => $sortCriteria,
        'skip'  => $offset,
        'limit' => $limit
    ]);

    foreach ($productCursor as $doc) {
        $productArr = docToArray($doc);
        $categoryName = 'Uncategorized';
        if (!empty($productArr['category_id'])) {
            $catOid = toObjectId($productArr['category_id']);
            $cat    = $catOid ? $db->categories->findOne(['_id' => $catOid]) : null;
            if ($cat) {
                $catArr       = docToArray($cat);
                $categoryName = $catArr['category_name'];
            }
        }
        $productArr['category_name'] = $categoryName;
        $products[] = $productArr;
    }
} catch (Throwable $e) {}

if (empty($products)) {
    $fallback = getCatalogFallback();
    if (empty($allCategories)) {
        $allCategories = $fallback['categories'] ?? [];
    }
    $allFallbackProducts = $fallback['products'] ?? [];
    if ($category !== '') {
        $allFallbackProducts = array_filter($allFallbackProducts, function($p) use ($category) {
            return ($p['category_id'] ?? '') === $category;
        });
    }
    if ($search !== '') {
        $allFallbackProducts = array_filter($allFallbackProducts, function($p) use ($search) {
            return stripos($p['name'] ?? '', $search) !== false;
        });
    }
    $totalProducts = count($allFallbackProducts);
    $totalPages = max(1, (int)ceil($totalProducts / $limit));
    $products = array_slice(array_values($allFallbackProducts), $offset, $limit);
}

// Wishlist product IDs for current user
$wishlistProductIds = [];
if (isset($_SESSION['user_id'])) {
    $wCursor = $db->wishlist->find(['user_id' => $_SESSION['user_id']]);
    foreach ($wCursor as $wDoc) {
        $wArr = docToArray($wDoc);
        $wishlistProductIds[] = $wArr['product_id'];
    }
}

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/navbar.php');
?>

<section class="page-banner">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h1>Explore Store</h1>
                <p class="mb-0">Browse student essentials, tech items, textbooks, and campus gear.</p>
            </div>
            <div class="text-white small">
                Showing <strong><?php echo count($products); ?></strong> of <strong><?php echo $totalProducts; ?></strong> items
            </div>
        </div>
    </div>
</section>

<section class="page-content py-4">
    <div class="container">

        <!-- FLIPKART STYLE FILTER BAR -->
        <div class="shop-filter-box mb-4 p-4 bg-white rounded-4 shadow-sm border">
            <form method="GET" action="shop.php" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold text-dark"><i class="bi bi-search me-1"></i> Search Products</label>
                    <input
                        type="text"
                        name="search"
                        class="form-control fk-input-styled"
                        placeholder="e.g. Mathematics, Notebook, Earphones..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold text-dark"><i class="bi bi-tags me-1"></i> Category</label>
                    <select name="category" class="form-select fk-input-styled">
                        <option value="">All Categories</option>
                        <?php foreach ($allCategories as $cat) { ?>
                            <option value="<?php echo htmlspecialchars($cat['id']); ?>"
                                <?php echo ($category === $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold text-dark"><i class="bi bi-sort-down me-1"></i> Sort By</label>
                    <select name="sort" class="form-select fk-input-styled">
                        <option value="newest" <?php echo ($sortKey === 'newest') ? 'selected' : ''; ?>>Newest First</option>
                        <option value="price_asc" <?php echo ($sortKey === 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo ($sortKey === 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name_asc" <?php echo ($sortKey === 'name_asc') ? 'selected' : ''; ?>>Name: A to Z</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
                        Filter
                    </button>
                    <?php if ($search !== '' || $category !== '' || $sortKey !== 'newest') { ?>
                        <a href="shop.php" class="btn btn-outline-secondary py-2" title="Reset Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    <?php } ?>
                </div>
            </form>
        </div>

        <!-- PRODUCTS GRID -->
        <div class="row g-4">
            <?php if (!empty($products)) { ?>
                <?php foreach ($products as $product) { ?>
                    <?php
                    $productId     = $product['id'];
                    $rating        = getProductRating(crc32($productId) % 10 + 1);
                    $discount      = getDiscountPercent((float)$product['price'], $product['discount'] ?? null);
                    $originalPrice = getOriginalPrice((float)$product['price'], $discount);
                    $isWishlisted  = in_array($productId, $wishlistProductIds);
                    $price         = (float)$product['price'];
                    ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="fk-product-card h-100 d-flex flex-column bg-white rounded-3 border">
                            <div class="fk-card-image-box position-relative">
                                <span class="fk-discount-badge"><?php echo $discount; ?>% OFF</span>

                                <?php if (isset($_SESSION['user_id'])) { ?>
                                    <a
                                        href="toggle_wishlist.php?product_id=<?php echo urlencode($productId); ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>"
                                        class="fk-wishlist-btn <?php echo $isWishlisted ? 'active' : ''; ?>"
                                        title="<?php echo $isWishlisted ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>"
                                    >
                                        <i class="bi <?php echo $isWishlisted ? 'bi-heart-fill text-danger' : 'bi-heart'; ?>"></i>
                                    </a>
                                <?php } ?>

                                <a href="product.php?id=<?php echo urlencode($productId); ?>" class="d-block text-center p-3">
                                    <img
                                        src="<?php echo htmlspecialchars($product['image'] ?? ''); ?>"
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

                                <p class="fk-product-desc text-muted small mb-2">
                                    <?php echo htmlspecialchars(truncateText($product['description'] ?? '', 55)); ?>
                                </p>

                                <div class="fk-price-block mt-auto mb-3">
                                    <div class="d-flex flex-wrap align-items-baseline gap-2">
                                        <span class="fk-current-price">₹<?php echo number_format($price, 2); ?></span>
                                        <span class="fk-old-price">₹<?php echo number_format($originalPrice, 2); ?></span>
                                        <span class="fk-discount-text"><?php echo $discount; ?>% off</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span class="text-success small fw-semibold">
                                            <i class="bi bi-truck me-1"></i> Free delivery
                                        </span>
                                        <small class="text-muted">Stock: <?php echo (int)$product['stock']; ?></small>
                                    </div>
                                </div>

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
                <div class="col-12">
                    <div class="empty-state-box text-center p-5 bg-white rounded-4 shadow-sm">
                        <i class="bi bi-search fs-1 text-muted mb-3 d-block"></i>
                        <h4>No matching products found</h4>
                        <p class="text-muted">Try clearing filters or search with another keyword.</p>
                        <a href="shop.php" class="btn btn-primary px-4 py-2 mt-2">View All Products</a>
                    </div>
                </div>
            <?php } ?>
        </div>

        <!-- PAGINATION -->
        <?php if ($totalPages > 1) { ?>
            <nav class="mt-5">
                <ul class="pagination custom-pagination justify-content-center">
                    <?php if ($page > 1) { ?>
                        <li class="page-item">
                            <a class="page-link" href="?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sortKey); ?>&page=<?php echo $page - 1; ?>">
                                <i class="bi bi-chevron-left"></i> Previous
                            </a>
                        </li>
                    <?php } ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++) { ?>
                        <li class="page-item <?php echo ($page === $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sortKey); ?>&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if ($page < $totalPages) { ?>
                        <li class="page-item">
                            <a class="page-link" href="?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sortKey); ?>&page=<?php echo $page + 1; ?>">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </nav>
        <?php } ?>
    </div>
</section>

<?php include(__DIR__ . '/includes/footer.php'); ?>