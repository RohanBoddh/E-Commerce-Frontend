<?php
session_start();
require_once __DIR__ . '/../../E-Commerce-Backend/config/mongodb.php';
require_once __DIR__ . '/../../E-Commerce-Backend/lib/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = trim($_POST['category_name']);

    if (!empty($category_name)) {
        if ($db->categories->findOne(['category_name' => $category_name])) {
            $message = "That category already exists.";
        } else {
            $db->categories->insertOne([
                'category_name' => $category_name,
                'created_at' => nowUTC()
            ]);
            $message = "Category added successfully.";
        }
    }
}

if (isset($_GET['delete'])) {
    $categoryId = trim($_GET['delete']);
    $categoryObjectId = toObjectId($categoryId);

    if ($categoryObjectId) {
        $db->categories->deleteOne(['_id' => $categoryObjectId]);
        $db->products->updateMany(
            ['category_id' => $categoryId],
            ['$unset' => ['category_id' => '']]
        );
    }

    header("Location: manage_categories.php");
    exit();
}

$categories = cursorToArray($db->categories->find([], ['sort' => ['_id' => -1]]));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - E-Commerce</title>
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
            <li><a href="manage_categories.php" class="active"><i class="bi bi-tags me-2"></i> Manage Categories</a></li>
            <li><a href="manage_orders.php"><i class="bi bi-cart-check me-2"></i> Manage Orders</a></li>
            <li><a href="manage_users.php"><i class="bi bi-people me-2"></i> Manage Users</a></li>
            <li><hr class="dropdown-divider my-2 border-secondary"></li>
            <li><a href="../index.php" target="_blank"><i class="bi bi-shop me-2 text-warning"></i> View Store</a></li>
            <li><a href="logout.php"><i class="bi bi-box-arrow-right me-2 text-danger"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="admin-content">
        <div class="admin-topbar">
            <h1>Manage Categories</h1>
        </div>

        <div class="admin-card mb-4">
            <?php if (!empty($message)) { ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php } ?>

            <form method="POST" class="row g-3">
                <div class="col-md-9">
                    <input type="text" name="category_name" class="form-control" placeholder="Enter category name" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn-main w-100">Add Category</button>
                </div>
            </form>
        </div>

        <div class="admin-card">
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $row) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td>
                                    <a href="manage_categories.php?delete=<?php echo urlencode($row['id']); ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Delete this category?');">
                                        Delete
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