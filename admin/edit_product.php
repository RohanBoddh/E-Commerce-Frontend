<?php
session_start();
require_once __DIR__ . '/../../E-Commerce-Backend/config/mongodb.php';
require_once __DIR__ . '/../../E-Commerce-Backend/lib/functions.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_products.php");
    exit();
}

$product_id = trim($_GET['id']);
$productObjectId = toObjectId($product_id);
$message = "";
$error = "";

if (!$productObjectId) {
    header("Location: manage_products.php");
    exit();
}

$productDoc = $db->products->findOne(['_id' => $productObjectId]);
if (!$productDoc) {
    header("Location: manage_products.php");
    exit();
}
$product = docToArray($productDoc);
$categories = cursorToArray($db->categories->find([], ['sort' => ['category_name' => 1]]));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
    $category_id = trim($_POST['category_id'] ?? '');
    $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);
    $image = trim($_POST['image'] ?? '');
    $categoryObjectId = toObjectId($category_id);

    if ($name === '' || $description === '' || $price === false || $price <= 0 || !$categoryObjectId || $stock === false || $stock < 0) {
        $error = "Please fill all required fields correctly.";
    } elseif (!$db->categories->findOne(['_id' => $categoryObjectId])) {
        $error = "Please select a valid category.";
    } else {
        $updateResult = $db->products->updateOne(
            ['_id' => $productObjectId],
            ['$set' => [
                'name' => $name,
                'description' => $description,
                'price' => (float) $price,
                'image' => $image,
                'category_id' => $category_id,
                'stock' => (int) $stock
            ]]
        );

        if ($updateResult->getMatchedCount() === 1) {
            $message = "Product updated successfully.";
            $product = docToArray($db->products->findOne(['_id' => $productObjectId]));
        } else {
            $error = "Failed to update product.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - E-Commerce</title>
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
            <h1>Edit Product</h1>
        </div>

        <div class="admin-card">
            <?php if (!empty($message)) { ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php } ?>

            <?php if (!empty($error)) { ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php } ?>

            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Product Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Price</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $product['price']; ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat) { ?>
                                <option value="<?php echo htmlspecialchars($cat['id']); ?>" <?php if ($product['category_id'] === $cat['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Stock</label>
                        <input type="number" name="stock" class="form-control" min="0" value="<?php echo $product['stock']; ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Image URL</label>
                        <input type="text" name="image" class="form-control" value="<?php echo htmlspecialchars($product['image']); ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" rows="5" class="form-control" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>

                    <div class="col-12 d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn-main">Update Product</button>
                        <a href="manage_products.php" class="btn btn-outline-secondary">Back</a>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>