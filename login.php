<?php
session_start();
require_once __DIR__ . '/../E-Commerce-Backend/config/mongodb.php';

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = "Please enter email and password.";
    } else {
        $user = $db->users->findOne(['email' => $email]);

        if ($user) {
            $userArr = docToArray($user);

            if (password_verify($password, $userArr['password'])) {
                $_SESSION['user_id']    = $userArr['id'];
                $_SESSION['user_name']  = $userArr['name'];
                $_SESSION['user_email'] = $userArr['email'];
                $_SESSION['role']       = $userArr['role'] ?? 'user';

                if (($userArr['role'] ?? 'user') === 'admin') {
                    $_SESSION['admin_id']    = $userArr['id'];
                    $_SESSION['admin_name']  = $userArr['name'];
                    $_SESSION['admin_email'] = $userArr['email'];
                    $_SESSION['is_admin']    = true;
                    header("Location: admin/dashboard.php");
                    exit();
                } else {
                    unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email'], $_SESSION['is_admin']);
                    header("Location: index.php");
                    exit();
                }
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "Account not found with this email.";
        }
    }
}

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/navbar.php');
?>

<section class="page-content py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="fk-auth-card shadow-lg border-0 rounded-4 overflow-hidden bg-white">
                    <div class="row g-0">
                        <!-- FLIPKART STYLE LEFT BRANDED COLUMN -->
                        <div class="col-md-5 fk-auth-sidebar p-4 p-lg-5 text-white d-flex flex-column justify-content-between">
                            <div>
                                <h2 class="fw-bold mb-3">Login</h2>
                                <p class="text-light-opacity">
                                    Get access to your Campus Orders, Wishlist, Recommendations & Exclusive Deals.
                                </p>
                            </div>
                            <div class="mt-4 pt-4 border-top border-white-50">
                                <small class="text-light-opacity">Student Campus Store • Gautam Buddha University</small>
                            </div>
                        </div>

                        <!-- RIGHT FORM COLUMN -->
                        <div class="col-md-7 p-4 p-lg-5">
                            <h4 class="fw-bold mb-4">Sign in to your account</h4>

                            <?php if (!empty($error)) { ?>
                                <div class="alert alert-danger py-2 small mb-4">
                                    <i class="bi bi-exclamation-circle me-1"></i> <?php echo $error; ?>
                                </div>
                            <?php } ?>

                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-muted">EMAIL ADDRESS</label>
                                    <input type="email" name="email" class="form-control fk-input-styled" placeholder="your.name@college.edu" required>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold small text-muted">PASSWORD</label>
                                    <input type="password" name="password" class="form-control fk-input-styled" placeholder="Enter your password" required>
                                </div>

                                <button type="submit" class="btn btn-warning-custom w-100 py-3 fw-bold mb-3 shadow-sm fs-6">
                                    Login to Account
                                </button>
                            </form>

                            <div class="text-center mt-4">
                                <p class="text-muted small mb-0">
                                    New to E-Commerce?
                                    <a href="register.php" class="fw-bold text-primary text-decoration-none">Create an account</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include(__DIR__ . '/includes/footer.php'); ?>