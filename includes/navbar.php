<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($db)) {
    try {
        require_once __DIR__ . '/../../E-Commerce-Backend/config/mongodb.php';
    } catch (Throwable $e) {}
}

$userName = $_SESSION['user_name'] ?? '';
$userInitial = $userName !== '' ? strtoupper(substr($userName, 0, 1)) : 'U';
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

$cartCount = 0;
$wishlistCount = 0;

if (isset($_SESSION['user_id']) && isset($db)) {
    require_once __DIR__ . '/../../E-Commerce-Backend/lib/functions.php';
    $cartCount = getCartCount($db, $_SESSION['user_id']);
    $wishlistCount = getWishlistCount($db, $_SESSION['user_id']);
}

// Fetch categories for the category navigation strip
$navCategories = [];
if (isset($db)) {
    try {
        $navCategories = cursorToArray($db->categories->find([], ['sort' => ['category_name' => 1], 'limit' => 12]));
    } catch (Throwable $e) {}
}

$currentSearch = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'])) : '';
?>

<!-- TOP ANNOUNCEMENT BAR (FLIPKART / AMAZON STYLE) -->
<div class="top-announcement-bar">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="announcement-text">
            <span>🎉 <strong>Big Campus Deals:</strong> Free express delivery inside campus hostels & departments!</span>
        </div>
        <div class="announcement-links d-none d-md-flex align-items-center gap-3">
            <span class="delivery-location"><i class="bi bi-geo-alt-fill text-warning"></i> Deliver to Gautam Buddha University</span>
            <?php if ($isAdmin) { ?>
                <a href="admin/dashboard.php" class="admin-toplink"><i class="bi bi-shield-lock-fill"></i> Admin Panel</a>
            <?php } ?>
        </div>
    </div>
</div>

<!-- MAIN NAVBAR -->
<nav class="navbar navbar-expand-lg fk-main-navbar sticky-top">
    <div class="container">
        <!-- BRAND LOGO -->
        <a class="navbar-brand fk-brand" href="index.php">
            <span class="brand-title">E-Commerce</span>
            <span class="brand-sub">
                Explore <span class="brand-plus">Plus <i class="bi bi-stars text-warning"></i></span>
            </span>
        </a>

        <!-- SEARCH BAR (ICONIC FLIPKART / AMAZON BAR) -->
        <div class="fk-search-container flex-grow-1 mx-lg-4">
            <form action="shop.php" method="GET" class="d-flex align-items-center">
                <input
                    type="text"
                    name="search"
                    class="form-control fk-search-input"
                    placeholder="Search for books, stationery, electronics, hostel items..."
                    value="<?php echo $currentSearch; ?>"
                    aria-label="Search"
                >
                <button class="btn fk-search-btn" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>

        <button class="navbar-toggler fk-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list fs-2 text-white"></i>
        </button>

        <div class="collapse navbar-collapse flex-grow-0" id="mainNavbar">
            <ul class="navbar-nav align-items-lg-center fk-nav-list gap-1 gap-lg-3">
                <li class="nav-item">
                    <a class="nav-link fk-nav-link" href="index.php">
                        <i class="bi bi-house-door me-1"></i> Home
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link fk-nav-link" href="shop.php">
                        <i class="bi bi-grid me-1"></i> All Products
                    </a>
                </li>

                <?php if (isset($_SESSION['user_id'])) { ?>
                    <!-- WISHLIST -->
                    <li class="nav-item">
                        <a class="nav-link fk-nav-link position-relative" href="wishlist.php" title="My Wishlist">
                            <i class="bi bi-heart me-1"></i> Wishlist
                            <?php if ($wishlistCount > 0) { ?>
                                <span class="badge fk-nav-badge"><?php echo $wishlistCount; ?></span>
                            <?php } ?>
                        </a>
                    </li>

                    <!-- MY ORDERS -->
                    <li class="nav-item">
                        <a class="nav-link fk-nav-link" href="my_orders.php">
                            <i class="bi bi-box-seam me-1"></i> Orders
                        </a>
                    </li>

                    <!-- CART BUTTON -->
                    <li class="nav-item">
                        <a class="btn fk-cart-btn position-relative" href="cart.php">
                            <i class="bi bi-cart3 fs-5"></i>
                            <span class="d-none d-lg-inline">Cart</span>
                            <?php if ($cartCount > 0) { ?>
                                <span class="badge fk-cart-badge"><?php echo $cartCount; ?></span>
                            <?php } ?>
                        </a>
                    </li>

                    <!-- ACCOUNT DROPDOWN -->
                    <li class="nav-item dropdown fk-profile-dropdown">
                        <a class="nav-link dropdown-toggle fk-profile-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="fk-avatar"><?php echo htmlspecialchars($userInitial); ?></span>
                            <span class="fk-username"><?php echo htmlspecialchars($userName); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end fk-dropdown-menu shadow-lg border-0" aria-labelledby="userDropdown">
                            <li class="dropdown-header">
                                <div class="fw-bold"><?php echo htmlspecialchars($userName); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></small>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="my_orders.php"><i class="bi bi-box me-2 text-primary"></i> My Orders</a></li>
                            <li><a class="dropdown-item" href="wishlist.php"><i class="bi bi-heart me-2 text-danger"></i> My Wishlist</a></li>
                            <li><a class="dropdown-item" href="cart.php"><i class="bi bi-cart me-2 text-warning"></i> My Cart (<?php echo $cartCount; ?>)</a></li>
                            <?php if ($isAdmin) { ?>
                                <li><a class="dropdown-item text-primary fw-semibold" href="admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Admin Panel</a></li>
                            <?php } ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php } else { ?>
                    <!-- LOGIN / REGISTER -->
                    <li class="nav-item">
                        <a class="btn fk-login-btn" href="login.php">
                            Login
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="btn fk-cart-btn position-relative" href="cart.php">
                            <i class="bi bi-cart3 fs-5"></i>
                            <span>Cart</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link fk-nav-link" href="register.php">
                            Register
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
</nav>

<!-- SUB-HEADER CATEGORY STRIP (ICONIC FLIPKART STYLE) -->
<div class="fk-category-strip">
    <div class="container">
        <ul class="category-strip-list d-flex align-items-center mb-0 list-unstyled overflow-x-auto py-2">
            <li class="category-strip-item">
                <a href="shop.php" class="category-strip-link <?php echo (!isset($_GET['category']) || $_GET['category'] === '') ? 'active' : ''; ?>">
                    <span class="cat-icon">⚡</span>
                    <span class="cat-label">All Offers</span>
                </a>
            </li>
            <?php
            $stripIcons = [
                'Books & Academics' => '📚',
                'Electronics & Gadgets' => '💻',
                'Stationery & Study' => '✏️',
                'Hostel Essentials' => '🏠',
                'Fashion & Campus Wear' => '👕',
                'Sports & Fitness' => '⚽',
                'Snacks & Campus Beverages' => '☕'
            ];
            foreach ($navCategories as $navCat) {
                $cName = $navCat['category_name'];
                $cIcon = $stripIcons[$cName] ?? '🏷️';
                $isActive = (isset($_GET['category']) && $_GET['category'] === $navCat['id']);
            ?>
                <li class="category-strip-item">
                    <a href="shop.php?category=<?php echo urlencode($navCat['id']); ?>" class="category-strip-link <?php echo $isActive ? 'active' : ''; ?>">
                        <span class="cat-icon"><?php echo $cIcon; ?></span>
                        <span class="cat-label"><?php echo htmlspecialchars($cName); ?></span>
                    </a>
                </li>
            <?php } ?>
            <li class="category-strip-item ms-auto d-none d-lg-block">
                <a href="about.php" class="category-strip-link text-muted">
                    <span class="cat-icon"><i class="bi bi-info-circle"></i></span>
                    <span class="cat-label">Help & About</span>
                </a>
            </li>
        </ul>
    </div>
</div>