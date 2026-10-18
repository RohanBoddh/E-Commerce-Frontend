<?php
session_start();
require_once __DIR__ . '/../../E-Commerce-Backend/config/mongodb.php';
require_once __DIR__ . '/../../E-Commerce-Backend/lib/functions.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    header("Location: manage_orders.php");
    exit();
}

$orderId = trim($_GET['order_id']);
$orderObjectId = toObjectId($orderId);
$orderDoc = $orderObjectId ? $db->orders->findOne(['_id' => $orderObjectId]) : null;

if (!$orderDoc) {
    header("Location: manage_orders.php");
    exit();
}

$order = docToArray($orderDoc);
$userId = toObjectId($order['user_id'] ?? '');
$userDoc = $userId ? $db->users->findOne(['_id' => $userId]) : null;
$user = $userDoc ? docToArray($userDoc) : [];
$order['user_name'] = $user['name'] ?? 'Unknown';
$order['user_email'] = $user['email'] ?? '';

$items = [];
foreach ($db->order_items->find(['order_id' => $orderId]) as $itemDoc) {
    $item = docToArray($itemDoc);
    $productId = toObjectId($item['product_id'] ?? '');
    $product = $productId ? $db->products->findOne(['_id' => $productId]) : null;
    $item['name'] = $item['product_name'] ?? ($product ? docToArray($product)['name'] : 'Product unavailable');
    $items[] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo htmlspecialchars($order['id']); ?> - E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: Arial, sans-serif; }
        .invoice-box {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 32px;
            border-radius: 18px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }
        .invoice-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .invoice-top {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        .invoice-table th {
            background: #f8fafc;
        }
        @media print {
            .no-print { display: none; }
            body { background: #fff; }
            .invoice-box {
                box-shadow: none;
                margin: 0;
                max-width: 100%;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>

<div class="invoice-box">
    <div class="invoice-top">
        <div class="invoice-header">
            <h1>E-Commerce Invoice</h1>
            <p class="mb-0">Order ID: #<?php echo htmlspecialchars($order['id']); ?></p>
            <p class="mb-0">Date: <?php echo date("d M Y, h:i A", strtotime($order['created_at'])); ?></p>
        </div>

        <div>
            <h5>Customer Details</h5>
            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['user_name']); ?></p>
            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['user_email']); ?></p>
            <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
            <p class="mb-1"><strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
        </div>
    </div>

    <div class="mb-4">
        <h5>Order Status</h5>
        <p class="mb-1"><strong>Delivery Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
        <p class="mb-1"><strong>Payment Status:</strong> <?php echo htmlspecialchars($order['payment_status']); ?></p>
        <p class="mb-1"><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered invoice-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th width="120">Qty</th>
                    <th width="150">Price</th>
                    <th width="160">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo (int)$item['quantity']; ?></td>
                        <td>₹<?php echo number_format((float)$item['price'], 2); ?></td>
                        <td>₹<?php echo number_format((float)$item['price'] * (int)$item['quantity'], 2); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="text-end mt-4">
        <h4>Total: ₹<?php echo number_format((float)$order['total_amount'], 2); ?></h4>
    </div>

    <div class="mt-4 no-print d-flex gap-2">
        <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
        <a href="manage_orders.php" class="btn btn-secondary">Back</a>
    </div>
</div>

</body>
</html>