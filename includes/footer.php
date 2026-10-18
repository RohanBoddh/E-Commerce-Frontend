<?php
$isAdminPage = (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin') !== false);
$assetPrefix = $isAdminPage ? '../assets/' : 'assets/';
?>
<!-- BACK TO TOP BUTTON -->
<div class="back-to-top-bar text-center py-2" id="backToTopBar">
    <a href="#" class="text-white text-decoration-none fw-semibold small">Back to top <i class="bi bi-chevron-up"></i></a>
</div>

<!-- FLIPKART / AMAZON STYLE FOOTER -->
<footer class="fk-footer">
    <div class="container py-5">
        <div class="row gy-4">
            <div class="col-lg-3 col-md-6">
                <h6 class="footer-heading">ABOUT E-COMMERCE</h6>
                <p class="footer-desc">
                    Your premier campus shopping destination at Gautam Buddha University. Genuine academic books, hostel essentials, electronics, and daily student supplies delivered straight to your campus room.
                </p>
                <div class="campus-trust-pill d-inline-flex align-items-center gap-2 mt-2">
                    <i class="bi bi-patch-check-fill text-warning"></i>
                    <span>100% Genuine & Verified Store</span>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="footer-heading">STUDENT HELP & SUPPORT</h6>
                <ul class="footer-links list-unstyled mb-0">
                    <li><a href="shop.php"><i class="bi bi-chevron-right me-1"></i> Browse All Products</a></li>
                    <li><a href="my_orders.php"><i class="bi bi-chevron-right me-1"></i> Track Your Orders</a></li>
                    <li><a href="wishlist.php"><i class="bi bi-chevron-right me-1"></i> Saved Wishlist</a></li>
                    <li><a href="cart.php"><i class="bi bi-chevron-right me-1"></i> Shopping Cart</a></li>
                    <li><a href="contact.php"><i class="bi bi-chevron-right me-1"></i> Help & Contact</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="footer-heading">SECURITY & POLICY</h6>
                <ul class="footer-links list-unstyled mb-0">
                    <li><a href="about.php"><i class="bi bi-shield-check me-1"></i> Safe & Secure Payments</a></li>
                    <li><a href="about.php"><i class="bi bi-truck me-1"></i> Free Campus Delivery</a></li>
                    <li><a href="about.php"><i class="bi bi-arrow-repeat me-1"></i> Easy Replacements</a></li>
                    <li><a href="about.php"><i class="bi bi-file-earmark-text me-1"></i> Terms of Usage</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="footer-heading">CAMPUS HUB</h6>
                <p class="footer-contact mb-2"><i class="bi bi-geo-alt-fill text-danger me-2"></i> Gautam Buddha University, Greater Noida, UP - 201312</p>
                <p class="footer-contact mb-2"><i class="bi bi-envelope-fill text-primary me-2"></i> support@gbushop.com</p>
                <p class="footer-contact mb-3"><i class="bi bi-telephone-fill text-success me-2"></i> +91 98765 43210</p>
                
                <div class="payment-partners d-flex flex-wrap gap-2 align-items-center">
                    <span class="badge bg-light text-dark border">UPI</span>
                    <span class="badge bg-light text-dark border">RuPay</span>
                    <span class="badge bg-light text-dark border">Visa / Master</span>
                    <span class="badge bg-light text-dark border">Cash on Delivery</span>
                </div>
            </div>
        </div>

        <hr class="footer-divider my-4">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="copyright text-muted small">
                © <?php echo date('Y'); ?> E-Commerce Campus Shopping. Inspired by Flipkart & Amazon e-commerce experiences.
            </div>
            <div class="footer-bottom-links small">
                <a href="index.php" class="text-muted text-decoration-none me-3">Home</a>
                <a href="shop.php" class="text-muted text-decoration-none me-3">Shop</a>
                <a href="about.php" class="text-muted text-decoration-none me-3">About</a>
                <a href="contact.php" class="text-muted text-decoration-none">Contact</a>
            </div>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $assetPrefix; ?>js/script.js?v=2.0"></script>
</body>
</html>