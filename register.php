<?php
session_start();
require_once __DIR__ . '/../E-Commerce-Backend/config/mongodb.php';
require_once __DIR__ . '/../E-Commerce-Backend/lib/functions.php';

$name = $email = $phone = "";
$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name             = sanitize($_POST['name'] ?? '');
    $email            = sanitize($_POST['email'] ?? '');
    $phone            = sanitize($_POST['phone'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $existing = $db->users->findOne(['email' => $email]);

        if ($existing) {
            $error = "Email is already registered. Please login instead.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $result = $db->users->insertOne([
                'name'       => $name,
                'email'      => $email,
                'phone'      => $phone,
                'password'   => $hashedPassword,
                'role'       => 'user',
                'created_at' => nowUTC()
            ]);

            if ($result->getInsertedCount() > 0) {
                $success = "Registration successful! You can now login.";
                $name = $email = $phone = "";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/navbar.php');
?>

<section class="page-content py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-md-11">
                <div class="fk-auth-card shadow-lg border-0 rounded-4 overflow-hidden bg-white">
                    <div class="row g-0">
                        <!-- FLIPKART STYLE LEFT BRANDED COLUMN -->
                        <div class="col-md-5 fk-auth-sidebar p-4 p-lg-5 text-white d-flex flex-column justify-content-between">
                            <div>
                                <h2 class="fw-bold mb-3">Looks like you're new here!</h2>
                                <p class="text-light-opacity">
                                    Sign up with your mobile and email to get started with fast campus deliveries.
                                </p>
                            </div>
                            <div class="mt-4 pt-4 border-top border-white-50">
                                <small class="text-light-opacity">Free Delivery • Direct to Hostel Room</small>
                            </div>
                        </div>

                        <!-- RIGHT FORM COLUMN -->
                        <div class="col-md-7 p-4 p-lg-5">
                            <h4 class="fw-bold mb-4">Create your Student Account</h4>

                            <?php if (!empty($success)) { ?>
                                <div class="alert alert-success py-2 small mb-4">
                                    <i class="bi bi-check-circle me-1"></i> <?php echo $success; ?>
                                    <a href="login.php" class="alert-link ms-2">Click to Login</a>
                                </div>
                            <?php } ?>

                            <?php if (!empty($error)) { ?>
                                <div class="alert alert-danger py-2 small mb-4">
                                    <i class="bi bi-exclamation-circle me-1"></i> <?php echo $error; ?>
                                </div>
                            <?php } ?>

                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-muted">FULL NAME</label>
                                    <input type="text" name="name" class="form-control fk-input-styled" placeholder="Enter your full name" value="<?php echo htmlspecialchars($name); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-muted">EMAIL ADDRESS</label>
                                    <input type="email" name="email" class="form-control fk-input-styled" placeholder="your.name@college.edu" value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-muted">PHONE NUMBER</label>
                                    <input type="tel" name="phone" class="form-control fk-input-styled" placeholder="10-digit mobile number" value="<?php echo htmlspecialchars($phone); ?>" required>
                                </div>

                                <div class="row g-2 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small text-muted">PASSWORD</label>
                                        <input type="password" name="password" class="form-control fk-input-styled" placeholder="At least 6 characters" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small text-muted">CONFIRM PASSWORD</label>
                                        <input type="password" name="confirm_password" class="form-control fk-input-styled" placeholder="Repeat password" required>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-warning-custom w-100 py-3 fw-bold mb-3 shadow-sm fs-6">
                                    Continue & Register
                                </button>
                            </form>

                            <div class="text-center mt-3">
                                <p class="text-muted small mb-0">
                                    Existing User?
                                    <a href="login.php" class="fw-bold text-primary text-decoration-none">Log in here</a>
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