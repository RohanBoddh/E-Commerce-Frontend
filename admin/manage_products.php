<?php
session_start();
require_once __DIR__ . '/../../E-Commerce-Backend/config/mongodb.php';
require_once __DIR__ . '/../../E-Commerce-Backend/lib/functions.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['delete'])) {
    $productId = trim($_GET['delete']);
    $productObjectId = toObjectId($productId);

    if ($productObjectId) {
        $db->products->deleteOne(['_id' => $productObjectId]);
        $db->cart->deleteMany(['product_id' => $productId]);
        $db->wishlist->deleteMany(['product_id' => $productId]);
        $db->order_items->deleteMany(['product_id' => $productId]);
    }

    header("Location: manage_products.php");
    exit();
}

$products = [];
foreach ($db->products->find([], ['sort' => ['_id' => -1]]) as $productDoc) {
    $product = docToArray($productDoc);
    $categoryId = toObjectId($product['category_id'] ?? '');
    $category = $categoryId ? $db->categories->findOne(['_id' => $categoryId]) : null;
    $category = $category ? docToArray($category) : [];
    $product['category_name'] = $category['category_name'] ?? 'Uncategorized';
    $products[] = $product;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - E-Commerce</title>
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
            <li><a href="manage_products.php" class="active"><i class="bi bi-box-seam me-2"></i> Manage Products</a></li>
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
            <h1>Manage Products</h1>
        </div>

        <div class="admin-card">
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th style="min-width: 150px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $row) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="" style="width:60px;height:60px;object-fit:cover;border-radius:8px;" onerror="this.style.display='none';">
                                </td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td>₹<?php echo number_format((float)$row['price'], 2); ?></td>
                                <td><?php echo $row['stock']; ?></td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="edit_product.php?id=<?php echo urlencode($row['id']); ?>" class="btn btn-primary btn-sm">
                                            Edit
                                        </a>
                                        <a href="manage_products.php?delete=<?php echo urlencode($row['id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?');">
                                            Delete
                                        </a>
                                    </div>
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