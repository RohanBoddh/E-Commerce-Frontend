<?php
session_start();
require_once __DIR__ . '/../../E-Commerce-Backend/config/mongodb.php';
require_once __DIR__ . '/../../E-Commerce-Backend/lib/functions.php';

if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter email and password.";
    } else {
        $adminDoc = $db->users->findOne(['email' => $email, 'role' => 'admin']);

        if ($adminDoc) {
            $admin = docToArray($adminDoc);

            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['is_admin'] = true;

                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['user_name'] = $admin['name'];
                $_SESSION['user_email'] = $admin['email'];
                $_SESSION['role'] = $admin['role'];
                $_SESSION['just_logged_in'] = true;

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "Admin account not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - E-Commerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=2.0">
</head>
<body class="admin-login-body">

<section class="admin-login-section">
    <div class="container">
        <div class="col-md-6 col-lg-5 mx-auto">
            <div class="admin-login-card">
                <h2 class="text-center mb-4">Admin Login</h2>

                <?php if (!empty($error)) { ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php } ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Admin Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn-main w-100">Login</button>
                </form>

                <p class="text-center mt-3 mb-0">
                    <a href="../index.php">Back to Website</a>
                </p>
            </div>
        </div>
    </div>
</section>

</body>
</html>