<?php
require_once __DIR__ . '/../E-Commerce-Backend/config/mongodb.php';
require_once __DIR__ . '/../E-Commerce-Backend/lib/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$orders = cursorToArray($db->orders->find(
    ['user_id' => $user_id],
    ['sort' => ['created_at' => -1]]
));

// Prefetch items for all orders to display product list
$ordersWithItems = [];
foreach ($orders as $order) {
    $orderId = (string)$order['id'];
    $itemsCursor = $db->order_items->find(['order_id' => $orderId]);
    $items = cursorToArray($itemsCursor);
    $order['items'] = $items;
    $ordersWithItems[] = $order;
}

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/navbar.php');
?>

<section class="page-banner">
    <div class="container">
        <h1>My Orders</h1>
        <p>Track your campus deliveries, view payment receipts, and manage orders.</p>
    </div>
</section>

<section class="page-content py-4">
    <div class="container">
        <?php if (isset($_GET['order_placed']) && $_GET['order_placed'] === 'success') { ?>
            <div class="alert alert-success alert-dismissible fade show p-4 rounded-4 shadow-sm mb-4 border-success" role="alert">
                <div class="d-flex align-items-center gap-3">
                    <div class="display-6 text-success"><i class="bi bi-patch-check-fill"></i></div>
                    <div>
                        <h4 class="alert-heading fw-bold mb-1">Order Placed & Confirmed!</h4>
                        <p class="mb-0">Your order has been received and sent to our campus delivery team. Your items will arrive at your hostel gate shortly.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php } ?>

        <?php if (!empty($ordersWithItems)) { ?>
            <div class="orders-list-wrapper">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0 fw-bold">Past Orders (<?php echo count($ordersWithItems); ?>)</h4>
                    <span class="text-muted small">All active & completed campus deliveries</span>
                </div>

                <div class="row g-4">
                    <?php foreach ($ordersWithItems as $order) {
                        $status = $order['status'] ?? 'Pending';
                        $paymentStatus = $order['payment_status'] ?? 'Pending';

                        $statusClass = 'bg-primary-subtle text-primary border-primary-subtle';
                        $statusIcon = 'bi-clock-history';
                        if ($status === 'Delivered') {
                            $statusClass = 'bg-success-subtle text-success border-success-subtle';
                            $statusIcon = 'bi-check2-all';
                        } elseif ($status === 'Cancelled') {
                            $statusClass = 'bg-danger-subtle text-danger border-danger-subtle';
                            $statusIcon = 'bi-x-circle';
                        } elseif ($status === 'Shipped') {
                            $statusClass = 'bg-info-subtle text-info border-info-subtle';
                            $statusIcon = 'bi-truck';
                        }

                        $payClass = ($paymentStatus === 'Paid') 
                            ? 'bg-success-subtle text-success border border-success-subtle' 
                            : 'bg-warning-subtle text-dark border border-warning-subtle';
                    ?>
                        <div class="col-12">
                            <div class="order-history-card bg-white rounded-4 shadow-sm border overflow-hidden">
                                <!-- ORDER HEADER -->
                                <div class="p-3 bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <div>
                                            <span class="text-muted small d-block">ORDER PLACED</span>
                                            <strong class="text-dark small"><?php echo date("d M Y, h:i A", strtotime($order['created_at'])); ?></strong>
                                        </div>
                                        <div class="vr d-none d-sm-block"></div>
                                        <div>
                                            <span class="text-muted small d-block">TOTAL AMOUNT</span>
                                            <strong class="text-primary-custom small">₹<?php echo number_format((float)$order['total_amount'], 2); ?></strong>
                                        </div>
                                        <div class="vr d-none d-sm-block"></div>
                                        <div>
                                            <span class="text-muted small d-block">SHIP TO</span>
                                            <span class="text-dark small fw-medium text-truncate d-inline-block" style="max-width:200px;" title="<?php echo htmlspecialchars($order['address']); ?>">
                                                <?php echo htmlspecialchars($order['address']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge <?php echo $payClass; ?> px-2 py-1 rounded small">
                                            <?php echo ($paymentStatus === 'Paid') ? '✓ Paid' : '⏳ Pay on Delivery'; ?>
                                        </span>
                                        <span class="text-muted font-monospace small">#<?php echo htmlspecialchars($order['id']); ?></span>
                                    </div>
                                </div>

                                <!-- ORDER CONTENT / ITEMS -->
                                <div class="p-4">
                                    <div class="row g-4 align-items-center">
                                        <div class="col-lg-7">
                                            <div class="order-items-list">
                                                <?php if (!empty($order['items'])) { ?>
                                                    <?php foreach ($order['items'] as $item) { ?>
                                                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                                            <div>
                                                                <h6 class="mb-0 fw-bold text-dark">
                                                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                                                </h6>
                                                                <small class="text-muted">
                                                                    Quantity: <strong><?php echo (int)$item['quantity']; ?></strong> × ₹<?php echo number_format((float)$item['price'], 2); ?>
                                                                </small>
                                                            </div>
                                                            <div class="fw-bold text-dark">
                                                                ₹<?php echo number_format((float)$item['price'] * (int)$item['quantity'], 2); ?>
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <p class="text-muted mb-0 small">Items details recorded in order record.</p>
                                                <?php } ?>
                                            </div>

                                            <div class="mt-3 text-muted small">
                                                <i class="bi bi-wallet2 me-1"></i> Payment Method: <strong><?php echo htmlspecialchars($order['payment_method'] ?? 'COD'); ?></strong>
                                                <?php if (!empty($order['razorpay_payment_id'])) { ?>
                                                    &bull; Ref: <code class="text-dark"><?php echo htmlspecialchars($order['razorpay_payment_id']); ?></code>
                                                <?php } ?>
                                            </div>
                                        </div>

                                        <div class="col-lg-5 text-lg-end border-start-lg ps-lg-4">
                                            <div class="d-flex flex-column align-items-lg-end gap-2">
                                                <div class="mb-2">
                                                    <span class="badge <?php echo $statusClass; ?> border px-3 py-2 rounded-pill fw-semibold fs-6">
                                                        <i class="bi <?php echo $statusIcon; ?> me-1"></i> <?php echo htmlspecialchars($status); ?>
                                                    </span>
                                                </div>

                                                <small class="text-muted d-block">
                                                    <i class="bi bi-truck me-1"></i> Estimated: Same-day Campus Delivery
                                                </small>

                                                <div class="d-flex gap-2 mt-2 w-100 justify-content-lg-end">
                                                    <a href="shop.php" class="btn btn-outline-primary btn-sm px-3 py-2 fw-semibold">
                                                        Buy More Items
                                                    </a>
                                                    <button class="btn btn-light btn-sm px-3 py-2 border fw-semibold" onclick="window.print()">
                                                        <i class="bi bi-printer me-1"></i> Print Invoice
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } else { ?>
            <div class="empty-orders-card bg-white rounded-4 p-5 text-center shadow-sm border my-4">
                <div class="display-3 text-muted mb-3">📦</div>
                <h3 class="fw-bold mb-2">No Orders Placed Yet</h3>
                <p class="text-muted mb-4">You have not ordered anything yet. Browse our store to place your first campus delivery order.</p>
                <a href="shop.php" class="btn btn-primary px-4 py-3 fw-bold">
                    Start Shopping <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        <?php } ?>
    </div>
</section>

<?php include(__DIR__ . '/includes/footer.php'); ?>