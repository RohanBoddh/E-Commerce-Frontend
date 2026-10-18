/**
 * E-Commerce - Frontend Interactive Scripts
 * Flipkart & Amazon Style Enhancements
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Back to Top Smooth Scroll
    const backToTopBtn = document.getElementById('backToTopBar');
    if (backToTopBtn) {
        backToTopBtn.addEventListener('click', function (e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // 2. Auto-fade alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            try {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            } catch (e) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
    });

    // 3. Search Bar focus animation
    const searchInput = document.querySelector('.fk-search-input');
    const searchForm = document.querySelector('.fk-search-container form');
    if (searchInput && searchForm) {
        searchInput.addEventListener('focus', function () {
            searchForm.style.boxShadow = '0 0 0 3px rgba(255, 229, 0, 0.4), 0 2px 8px rgba(0,0,0,0.2)';
        });
        searchInput.addEventListener('blur', function () {
            searchForm.style.boxShadow = '0 2px 6px rgba(0, 0, 0, 0.12)';
        });
    }

    // 4. Quantity Stepper Controls (for product detail page and cart)
    const qtyInput = document.getElementById('qty');
    if (qtyInput) {
        qtyInput.addEventListener('keydown', function (e) {
            // Prevent entering negative values
            if (e.key === '-' || e.key === 'e' || e.key === '+') {
                e.preventDefault();
            }
        });
    }
});
