<?php
require_once __DIR__ . '/../E-Commerce-Backend/config/mongodb.php';
include(__DIR__ . '/includes/header.php');
require_once __DIR__ . '/../E-Commerce-Backend/lib/functions.php';

$name = "";
$email = "";
$subject = "";
$messageText = "";
$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject']);
    $messageText = sanitize($_POST['message']);

    if (empty($name) || empty($email) || empty($messageText)) {
        $error = "Name, email, and message are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $result = $db->contact_messages->insertOne([
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $messageText,
            'created_at' => nowUTC()
        ]);
        if ($result->getInsertedCount() === 1) {
            $success = "Your message has been sent successfully.";
            $name = "";
            $email = "";
            $subject = "";
            $messageText = "";
        } else {
            $error = "Failed to send message. Please try again.";
        }
    }
}
?>

<?php include(__DIR__ . '/includes/navbar.php'); ?>

<section class="page-banner">
    <div class="container">
        <h1>Contact Us</h1>
        <p>Get in touch with the E-Commerce team.</p>
    </div>
</section>

<section class="page-content">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="contact-info-box">
                    <h3 class="mb-3">Contact Information</h3>
                    <p><strong>Email:</strong> support@gbushop.com</p>
                    <p><strong>Phone:</strong> +91 98765 43210</p>
                    <p><strong>Address:</strong> E-Commerce, Greater Noida</p>
                    <p class="mb-0">We are here to help students with campus shopping needs.</p>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="contact-form-box">
                    <h3 class="mb-4">Send a Message</h3>

                    <?php if (!empty($success)) { ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php } ?>

                    <?php if (!empty($error)) { ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php } ?>

                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email Address</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Subject</label>
                                <input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($subject); ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Message</label>
                                <textarea name="message" rows="6" class="form-control" required><?php echo htmlspecialchars($messageText); ?></textarea>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn-main">Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include(__DIR__ . '/includes/footer.php'); ?>