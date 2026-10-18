<?php
session_start();
require_once __DIR__ . '/../../E-Commerce-Backend/config/mongodb.php';
require_once __DIR__ . '/../../E-Commerce-Backend/lib/functions.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['update_status'])) {
    $orderId = trim($_POST['order_id'] ?? '');
    $orderObjectId = toObjectId($orderId);
    $status = trim($_POST['status'] ?? '');
    $paymentStatus = trim($_POST['payment_status'] ?? '');
    $allowedStatuses = ['Pending', 'Processing', 'Packed', 'Shipped', 'Delivered', 'Cancelled'];
    $allowedPaymentStatuses = ['Pending', 'Paid', 'Failed'];

    if ($orderObjectId && in_array($status, $allowedStatuses, true) && in_array($paymentStatus, $allowedPaymentStatuses, true)) {
        $db->orders->updateOne(
            ['_id' => $orderObjectId],
            ['$set' => ['status' => $status, 'payment_status' => $paymentStatus]]
        );
    }
}

$orders = [];
foreach ($db->orders->find([], ['sort' => ['created_at' => -1]]) as $orderDoc) {
    $order = docToArray($orderDoc);
    $userId = toObjectId($order['user_id'] ?? '');
    $user = $userId ? $db->users->findOne(['_id' => $userId]) : null;
    $user = $user ? docToArray($user) : [];
    $order['user_name'] = $user['name'] ?? 'Unknown';
    $order['user_email'] = $user['email'] ?? '';
    $orders[] = $order;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - E-Commerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=2.0">
</head>
<body>

<div class="admin-panel">
    <aside class="admin-sidebar">
        <h2><i class="bi bi-shield-lock-fill text-warning me-2"></i> Admin</h2>
        <ul>
            <li><a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
            <li><a href="add_product.php"><i class="bi bi-plus-circle me-2"></i> Add Product</a></li>
            <li><a href="manage_products.php"><i class="bi bi-box-seam me-2"></i> Manage Products</a></li>
            <li><a href="manage_categories.php"><i class="bi bi-tags me-2"></i> Manage Categories</a></li>
            <li><a href="manage_orders.php" class="active"><i class="bi bi-cart-check me-2"></i> Manage Orders</a></li>
            <li><a href="manage_users.php"><i class="bi bi-people me-2"></i> Manage Users</a></li>
            <li><hr class="dropdown-divider my-2 border-secondary"></li>
            <li><a href="../index.php" target="_blank"><i class="bi bi-shop me-2 text-warning"></i> View Store</a></li>
            <li><a href="logout.php"><i class="bi bi-box-arrow-right me-2 text-danger"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="admin-content">
        <div class="admin-topbar">
            <h1>Manage Orders</h1>
        </div>

        <div class="admin-card">
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User</th>
                            <th>Total</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Invoice</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $row) { ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($row['id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['user_name'] ?? 'Unknown'); ?><br>
                                    <small><?php echo htmlspecialchars($row['user_email'] ?? ''); ?></small>
                                </td>
                                <td>₹<?php echo number_format((float)$row['total_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo htmlspecialchars($row['address']); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                <td><?php echo htmlspecialchars($row['payment_status']); ?></td>
                                <td>
                                    <a href="invoice.php?order_id=<?php echo urlencode($row['id']); ?>" class="btn btn-sm btn-outline-primary">
                                        Invoice
                                    </a>
                                </td>
                                <td>
                                    <form method="POST" class="d-flex flex-column gap-2">
                                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($row['id']); ?>">

                                        <select name="status" class="form-select form-select-sm">
                                            <option value="Pending" <?php if($row['status']=='Pending') echo 'selected'; ?>>Pending</option>
                                            <option value="Processing" <?php if($row['status']=='Processing') echo 'selected'; ?>>Processing</option>
                                            <option value="Packed" <?php if($row['status']=='Packed') echo 'selected'; ?>>Packed</option>
                                            <option value="Shipped" <?php if($row['status']=='Shipped') echo 'selected'; ?>>Shipped</option>
                                            <option value="Delivered" <?php if($row['status']=='Delivered') echo 'selected'; ?>>Delivered</option>
                                            <option value="Cancelled" <?php if($row['status']=='Cancelled') echo 'selected'; ?>>Cancelled</option>
                                        </select>

                                        <select name="payment_status" class="form-select form-select-sm">
                                            <option value="Pending" <?php if($row['payment_status']=='Pending') echo 'selected'; ?>>Pending</option>
                                            <option value="Paid" <?php if($row['payment_status']=='Paid') echo 'selected'; ?>>Paid</option>
                                            <option value="Failed" <?php if($row['payment_status']=='Failed') echo 'selected'; ?>>Failed</option>
                                        </select>

                                        <button type="submit" name="update_status" class="btn btn-primary btn-sm">Save</button>
                                    </form>
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