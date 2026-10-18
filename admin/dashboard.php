<?php
session_start();
require_once __DIR__ . '/../../E-Commerce-Backend/config/mongodb.php';
require_once __DIR__ . '/../../E-Commerce-Backend/lib/functions.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.php");
    exit();
}

$totalProducts = $db->products->countDocuments();
$totalOrders = $db->orders->countDocuments();
$totalUsers = $db->users->countDocuments(['role' => 'user']);
$totalAdmins = $db->users->countDocuments(['role' => 'admin']);

$salesResult = $db->orders->aggregate([
    ['$match' => ['payment_status' => 'Paid']],
    ['$group' => ['_id' => null, 'total_sales' => ['$sum' => '$total_amount']]]
])->toArray();
$totalSales = $salesResult ? $salesResult[0]['total_sales'] : 0;
$pendingOrders = $db->orders->countDocuments(['status' => 'Pending']);
$deliveredOrders = $db->orders->countDocuments(['status' => 'Delivered']);

$recentOrders = [];
foreach ($db->orders->find([], ['sort' => ['created_at' => -1], 'limit' => 5]) as $orderDoc) {
    $order = docToArray($orderDoc);
    $userId = toObjectId($order['user_id'] ?? '');
    $user = $userId ? $db->users->findOne(['_id' => $userId]) : null;
    $order['user_name'] = $user ? docToArray($user)['name'] : 'Unknown';
    $recentOrders[] = $order;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Commerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=2.1">
    <script>
        window.USER_LOGGED_IN = <?php echo isset($_SESSION['admin_id']) ? 'true' : 'false'; ?>;
        window.USER_ID = <?php echo json_encode((string)($_SESSION['admin_id'] ?? '')); ?>;
        window.JUST_LOGGED_IN = <?php echo !empty($_SESSION['just_logged_in']) ? 'true' : 'false'; ?>;
    </script>
    <script src="../assets/js/script.js?v=2.1"></script>
</head>
<body>

<div class="admin-panel">
    <aside class="admin-sidebar">
        <h2><i class="bi bi-shield-lock-fill text-warning me-2"></i> Admin</h2>
        <ul>
            <li><a href="dashboard.php" class="active"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
            <li><a href="add_product.php"><i class="bi bi-plus-circle me-2"></i> Add Product</a></li>
            <li><a href="manage_products.php"><i class="bi bi-box-seam me-2"></i> Manage Products</a></li>
            <li><a href="manage_categories.php"><i class="bi bi-tags me-2"></i> Manage Categories</a></li>
            <li><a href="manage_orders.php"><i class="bi bi-cart-check me-2"></i> Manage Orders</a></li>
            <li><a href="manage_users.php"><i class="bi bi-people me-2"></i> Manage Users</a></li>
            <li><hr class="dropdown-divider my-2 border-secondary"></li>
            <li><a href="../index.php" target="_blank"><i class="bi bi-shop me-2 text-warning"></i> View Store</a></li>
            <li><a href="logout.php"><i class="bi bi-box-arrow-right me-2 text-danger"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="admin-content">
        <div class="admin-topbar">
            <h1>Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-xl-3">
                <div class="admin-stat-card">
                    <h3><?php echo $totalProducts; ?></h3>
                    <p>Total Products</p>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="admin-stat-card">
                    <h3><?php echo $totalOrders; ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="admin-stat-card">
                    <h3><?php echo $totalUsers; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="admin-stat-card">
                    <h3>₹<?php echo number_format((float)$totalSales, 2); ?></h3>
                    <p>Total Sales</p>
                </div>
            </div>
        </div>

        <div class="admin-card mt-4">
            <h4 class="mb-3">Quick Overview</h4>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="mini-admin-box">
                        <h5><?php echo $totalAdmins; ?></h5>
                        <p>Admin Accounts</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mini-admin-box">
                        <h5><?php echo $pendingOrders; ?></h5>
                        <p>Pending Orders</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mini-admin-box">
                        <h5><?php echo $deliveredOrders; ?></h5>
                        <p>Delivered Orders</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-card mt-4">
            <h4 class="mb-3">Recent Orders</h4>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Invoice</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order) { ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo htmlspecialchars($order['user_name'] ?? 'Unknown'); ?></td>
                                <td>₹<?php echo number_format((float)$order['total_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($order['status']); ?></td>
                                <td><?php echo htmlspecialchars($order['payment_status']); ?></td>
                                <td>
                                    <a href="invoice.php?order_id=<?php echo urlencode($order['id']); ?>" class="btn btn-sm btn-outline-primary">
                                        View Invoice
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

</body>
</html>