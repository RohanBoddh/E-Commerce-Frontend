<?php
session_start();
require_once __DIR__ . '/../E-Commerce-Backend/config/mongodb.php';
require_once __DIR__ . '/../E-Commerce-Backend/lib/functions.php';
require_once __DIR__ . '/../E-Commerce-Backend/config/payment.php';

redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$error   = "";
$message = "";

// Fetch cart items with product details
$cartCursor = $db->cart->find(['user_id' => $user_id], ['sort' => ['_id' => -1]]);
$cartItems  = [];
$grand_total = 0;
$total_original = 0;

foreach ($cartCursor as $cartDoc) {
    $cartArr    = docToArray($cartDoc);
    $productOid = toObjectId($cartArr['product_id']);
    $product    = $productOid ? $db->products->findOne(['_id' => $productOid]) : null;

    if (!$product) continue;
    $productArr = docToArray($product);

    if ((int)$cartArr['quantity'] > (int)$productArr['stock']) {
        $error = "One or more products exceed available stock.";
        break;
    }

    $price = (float)$productArr['price'];
    $discount = getDiscountPercent($price);
    $origPrice = getOriginalPrice($price, $discount);
    $qty = (int)$cartArr['quantity'];

    $item             = $cartArr;
    $item['name']     = $productArr['name'];
    $item['price']    = $price;
    $item['original_price'] = $origPrice;
    $item['stock']    = $productArr['stock'];
    $item['image']    = $productArr['image'] ?? '';
    $item['subtotal'] = $price * $qty;

    $grand_total += $item['subtotal'];
    $total_original += ($origPrice * $qty);
    $cartItems[] = $item;
}

if (empty($cartItems)) {
    header("Location: cart.php");
    exit();
}

$total_savings = max(0, $total_original - $grand_total);

// -------------------------------------------------------------------------
// Handle Order Placement (COD, UPI, Card, Net Banking)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $address = sanitize($_POST['address'] ?? '');
    $phone   = sanitize($_POST['phone'] ?? '');
    $paymentMethodChoice = sanitize($_POST['payment_method_choice'] ?? 'cod');

    if (empty($address) || empty($phone)) {
        $error = "Please provide delivery address and phone number.";
    } elseif ($error === "") {
        // Determine method details & transaction reference
        $status = 'Pending';
        $paymentStatus = 'Pending';
        $paymentMethodTitle = 'Cash on Delivery';
        $transactionId = 'COD_' . time() . '_' . rand(1000, 9999);
        $orderId = 'ORD_' . time();

        if ($paymentMethodChoice === 'upi') {
            $upiId = sanitize($_POST['upi_id'] ?? 'student@upi');
            if (empty($upiId)) $upiId = 'campus.pay@upi';
            $paymentMethodTitle = 'UPI (' . $upiId . ')';
            $paymentStatus = 'Paid';
            $transactionId = 'UPI_' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
        } elseif ($paymentMethodChoice === 'card') {
            $cardNumber = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
            $last4 = substr($cardNumber, -4) ?: '4242';
            $cardName = sanitize($_POST['card_name'] ?? 'Cardholder');
            $paymentMethodTitle = 'Credit/Debit Card (Ending in ' . $last4 . ')';
            $paymentStatus = 'Paid';
            $transactionId = 'CARD_' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
        } elseif ($paymentMethodChoice === 'netbanking') {
            $bank = sanitize($_POST['bank_choice'] ?? 'State Bank of India');
            $paymentMethodTitle = 'Net Banking (' . $bank . ')';
            $paymentStatus = 'Paid';
            $transactionId = 'NB_' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
        }

        $session = $mongoClient->startSession();
        $session->startTransaction();

        try {
            $orderDoc = [
                'user_id'             => $user_id,
                'total_amount'        => $grand_total,
                'status'              => $status,
                'payment_status'      => $paymentStatus,
                'payment_method'      => $paymentMethodTitle,
                'razorpay_payment_id' => $transactionId,
                'razorpay_order_id'   => $orderId,
                'address'             => $address,
                'phone'               => $phone,
                'created_at'          => nowUTC()
            ];

            $orderResult  = $db->orders->insertOne($orderDoc, ['session' => $session]);
            $new_order_id = (string)$orderResult->getInsertedId();

            foreach ($cartItems as $item) {
                $db->order_items->insertOne([
                    'order_id'     => $new_order_id,
                    'product_id'   => $item['product_id'],
                    'product_name' => $item['name'],
                    'quantity'     => (int)$item['quantity'],
                    'price'        => (float)$item['price'],
                    'created_at'   => nowUTC()
                ], ['session' => $session]);

                $productOid   = toObjectId($item['product_id']);
                $updateResult = $db->products->updateOne(
                    ['_id' => $productOid, 'stock' => ['$gte' => (int)$item['quantity']]],
                    ['$inc' => ['stock' => -(int)$item['quantity']]],
                    ['session' => $session]
                );

                if ($updateResult->getModifiedCount() !== 1) {
                    throw new RuntimeException('Product stock changed during order placement.');
                }
            }

            $db->cart->deleteMany(['user_id' => $user_id], ['session' => $session]);
            $session->commitTransaction();

            header("Location: my_orders.php?order_placed=success&order_id=" . urlencode($new_order_id));
            exit();
        } catch (Throwable $e) {
            try {
                $session->abortTransaction();
            } catch (Throwable $ex) {}
            $error = "Order placement failed: " . $e->getMessage();
        }
    }
}

// Generate UPI QR text string
$upiQrData = "upi://pay?pa=campusstore@upi&pn=CampusStore&am=" . number_format($grand_total, 2, '.', '') . "&cu=INR&tn=CampusOrder";
$upiQrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . urlencode($upiQrData);

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/navbar.php');
?>

<section class="page-banner">
    <div class="container">
        <h1>Secure Checkout</h1>
        <p>Complete your delivery address and choose your payment method.</p>
    </div>
</section>

<section class="page-content py-4">
    <div class="container">
        <?php if ($error !== "") { ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php } ?>

        <div class="row g-4">
            <!-- LEFT COLUMN: CHECKOUT STEPS -->
            <div class="col-lg-8">
                <form method="POST" id="checkoutForm" autocomplete="off">
                    <input type="hidden" name="action" value="place_order">

                    <!-- STEP 1: USER ACCOUNT -->
                    <div class="checkout-stepper-box mb-3">
                        <div class="checkout-step-header active d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="step-num"><i class="bi bi-check-lg"></i></span>
                                <h4>1. Login Information</h4>
                            </div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill small">
                                Verified
                            </span>
                        </div>
                        <div class="checkout-step-body py-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Student'); ?></strong>
                                    <span class="text-muted ms-2">(<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>)</span>
                                </div>
                                <span class="text-success small fw-semibold"><i class="bi bi-shield-check"></i> Connected</span>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: DELIVERY ADDRESS -->
                    <div class="checkout-stepper-box mb-3">
                        <div class="checkout-step-header active">
                            <span class="step-num">2</span>
                            <h4>2. Campus Delivery Address & Contact</h4>
                        </div>
                        <div class="checkout-step-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Hostel / Campus Room Address <span class="text-danger">*</span></label>
                                <textarea id="address" name="address" class="form-control" rows="3" placeholder="Room Number, Hostel / Block Name (e.g. Aryabhatta Hostel, Room 314), Gautam Buddha University Campus, Greater Noida" required></textarea>
                                <div class="form-text text-muted small">
                                    <i class="bi bi-info-circle me-1"></i> Our delivery partner delivers directly to your hostel gate / department.
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Contact Phone Number <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light fw-bold">+91</span>
                                        <input id="phone" name="phone" type="tel" class="form-control" placeholder="10-digit mobile number" pattern="[0-9]{10}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Campus PIN Code</label>
                                    <input type="text" class="form-control" value="201312 (GBU Campus)" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: PAYMENT METHOD OPTIONS -->
                    <div class="checkout-stepper-box mb-4">
                        <div class="checkout-step-header active">
                            <span class="step-num">3</span>
                            <h4>3. Select Payment Option</h4>
                        </div>
                        <div class="checkout-step-body">

                            <!-- OPTION 1: UPI / QR CODE -->
                            <div class="payment-method-card selected p-3 mb-3" onclick="selectPaymentMethod('upi')">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" name="payment_method_choice" id="payUPI" value="upi" checked>
                                        <label class="form-check-label fw-bold text-dark fs-6" for="payUPI">
                                            📱 UPI (Google Pay, PhonePe, Paytm, BHIM)
                                        </label>
                                        <small class="d-block text-muted">Pay instantly via QR scan or your UPI ID.</small>
                                    </div>
                                    <div class="d-none d-sm-flex gap-1">
                                        <span class="badge bg-light text-dark border">GPay</span>
                                        <span class="badge bg-light text-dark border">PhonePe</span>
                                        <span class="badge bg-light text-dark border">Paytm</span>
                                    </div>
                                </div>

                                <div class="payment-details-drawer" id="upiDrawer">
                                    <div class="upi-qr-card">
                                        <h6 class="fw-bold mb-2">Scan & Pay ₹<?php echo number_format($grand_total, 2); ?></h6>
                                        <div class="upi-qr-wrapper mb-2">
                                            <img src="<?php echo $upiQrUrl; ?>" alt="UPI QR Code" style="width:160px;height:160px;" class="img-fluid" onerror="this.onerror=null;this.src='https://via.placeholder.com/160x160?text=Scan+UPI+QR';">
                                        </div>
                                        <p class="text-muted small mb-2">Scan this QR code using any UPI app on your phone</p>
                                        
                                        <div class="upi-badge-strip">
                                            <span class="upi-pill"><i class="bi bi-shield-check text-success"></i> 100% Instant Approval</span>
                                            <span class="upi-pill"><i class="bi bi-lightning-charge-fill text-warning"></i> Zero Convenience Fee</span>
                                        </div>

                                        <div class="mt-3 text-start">
                                            <label class="form-label small fw-bold text-muted">Or enter your UPI ID:</label>
                                            <div class="input-group">
                                                <input type="text" name="upi_id" class="form-control" placeholder="yourname@okhdfcbank" value="student@okhdfcbank">
                                                <button class="btn btn-outline-primary fw-bold" type="button" onclick="alert('UPI ID verified!')">Verify</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- OPTION 2: CREDIT / DEBIT / ATM CARD -->
                            <div class="payment-method-card p-3 mb-3" onclick="selectPaymentMethod('card')">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" name="payment_method_choice" id="payCard" value="card">
                                        <label class="form-check-label fw-bold text-dark fs-6" for="payCard">
                                            💳 Credit / Debit / ATM Card
                                        </label>
                                        <small class="d-block text-muted">Visa, MasterCard, RuPay, Maestro accepted.</small>
                                    </div>
                                    <div class="d-none d-sm-flex gap-1">
                                        <span class="badge bg-light text-dark border">Visa</span>
                                        <span class="badge bg-light text-dark border">Mastercard</span>
                                        <span class="badge bg-light text-dark border">RuPay</span>
                                    </div>
                                </div>

                                <div class="payment-details-drawer" id="cardDrawer">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Card Number</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
                                                <input type="text" name="card_number" id="cardNumberInput" class="form-control font-monospace" placeholder="4532 •••• •••• 8892" maxlength="19" value="4532 8901 2345 8892">
                                            </div>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-bold">Expiry (MM/YY)</label>
                                                <input type="text" name="card_expiry" class="form-control font-monospace text-center" placeholder="12/28" maxlength="5" value="10/28">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-bold">CVV</label>
                                                <input type="password" name="card_cvv" class="form-control font-monospace text-center" placeholder="•••" maxlength="4" value="842">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label small fw-bold">Cardholder Name</label>
                                            <input type="text" name="card_name" class="form-control" placeholder="Name as printed on card" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'College Student'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- OPTION 3: NET BANKING -->
                            <div class="payment-method-card p-3 mb-3" onclick="selectPaymentMethod('netbanking')">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" name="payment_method_choice" id="payNetBanking" value="netbanking">
                                        <label class="form-check-label fw-bold text-dark fs-6" for="payNetBanking">
                                            🏦 Net Banking
                                        </label>
                                        <small class="d-block text-muted">All Indian major banks supported.</small>
                                    </div>
                                    <span class="badge bg-light text-dark border">All Banks</span>
                                </div>

                                <div class="payment-details-drawer" id="netbankingDrawer">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <label class="form-label small fw-bold mb-2">Select Popular Bank:</label>
                                        <div class="bank-grid mb-3">
                                            <label class="bank-option-btn active">
                                                <input type="radio" name="bank_choice" value="State Bank of India" checked class="d-none">
                                                <span>SBI</span>
                                            </label>
                                            <label class="bank-option-btn">
                                                <input type="radio" name="bank_choice" value="HDFC Bank" class="d-none">
                                                <span>HDFC</span>
                                            </label>
                                            <label class="bank-option-btn">
                                                <input type="radio" name="bank_choice" value="ICICI Bank" class="d-none">
                                                <span>ICICI</span>
                                            </label>
                                            <label class="bank-option-btn">
                                                <input type="radio" name="bank_choice" value="Axis Bank" class="d-none">
                                                <span>Axis</span>
                                            </label>
                                            <label class="bank-option-btn">
                                                <input type="radio" name="bank_choice" value="Punjab National Bank" class="d-none">
                                                <span>PNB</span>
                                            </label>
                                            <label class="bank-option-btn">
                                                <input type="radio" name="bank_choice" value="Kotak Mahindra Bank" class="d-none">
                                                <span>Kotak</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- OPTION 4: CASH ON DELIVERY -->
                            <div class="payment-method-card p-3 mb-3" onclick="selectPaymentMethod('cod')">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" name="payment_method_choice" id="payCOD" value="cod">
                                        <label class="form-check-label fw-bold text-dark fs-6" for="payCOD">
                                            💵 Cash on Delivery / Pay on Delivery
                                        </label>
                                        <small class="d-block text-muted">Pay cash or scan QR when item reaches your room/hostel gate.</small>
                                    </div>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded small">
                                        Safe Option
                                    </span>
                                </div>

                                <div class="payment-details-drawer" id="codDrawer">
                                    <div class="alert alert-info py-2 px-3 small mb-0">
                                        <i class="bi bi-shield-check me-1"></i> Exact change or UPI scan is accepted at the time of delivery by our campus delivery team.
                                    </div>
                                </div>
                            </div>

                            <!-- OPTION 5: RAZORPAY GATEWAY (IF CONFIGURED) -->
                            <?php if (defined('RAZORPAY_CONFIGURED') && RAZORPAY_CONFIGURED) { ?>
                                <div class="payment-method-card p-3 mb-3" onclick="selectPaymentMethod('razorpay')">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="radio" name="payment_method_choice" id="payRazorpay" value="razorpay">
                                            <label class="form-check-label fw-bold text-dark fs-6" for="payRazorpay">
                                                ⚡ Razorpay Live Gateway Modal
                                            </label>
                                            <small class="d-block text-muted">Direct gateway popup window.</small>
                                        </div>
                                        <span class="badge bg-primary-subtle text-primary border small">Live Gateway</span>
                                    </div>
                                </div>
                            <?php } ?>

                        </div>
                    </div>

                    <!-- SUBMIT BUTTON -->
                    <div class="d-flex flex-wrap gap-3">
                        <button type="submit" id="mainSubmitBtn" class="btn btn-warning-custom flex-grow-1 py-3 fw-bold fs-5 shadow-sm" <?php echo ($error !== "") ? 'disabled' : ''; ?>>
                            📱 Pay ₹<?php echo number_format($grand_total, 2); ?> via UPI
                        </button>
                    </div>
                </form>
            </div>

            <!-- RIGHT COLUMN: FLIPKART STYLE PRICE DETAILS & ORDER SUMMARY -->
            <div class="col-lg-4">
                <div class="order-summary-box position-sticky" style="top: 100px;">
                    <div class="price-details-card bg-white rounded-3 border p-4 shadow-sm">
                        <h5 class="fw-bold text-muted border-bottom pb-3 mb-3 text-uppercase fs-6">
                            Price Details (<?php echo count($cartItems); ?> Items)
                        </h5>

                        <div class="checkout-items-preview mb-3 overflow-y-auto" style="max-height: 220px;">
                            <?php foreach ($cartItems as $item) { ?>
                                <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if (!empty($item['image'])) { ?>
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:6px;" onerror="this.style.display='none';">
                                        <?php } ?>
                                        <div>
                                            <div class="small fw-bold text-truncate" style="max-width: 170px;"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <small class="text-muted">Qty: <?php echo (int)$item['quantity']; ?></small>
                                        </div>
                                    </div>
                                    <div class="small fw-bold">
                                        ₹<?php echo number_format((float)$item['subtotal'], 2); ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="price-row d-flex justify-content-between mb-2">
                            <span class="text-muted">Total MRP</span>
                            <span class="text-decoration-line-through text-muted">₹<?php echo number_format($total_original, 2); ?></span>
                        </div>

                        <div class="price-row d-flex justify-content-between mb-2">
                            <span class="text-muted">Discount on MRP</span>
                            <span class="text-success fw-semibold">- ₹<?php echo number_format($total_savings, 2); ?></span>
                        </div>

                        <div class="price-row d-flex justify-content-between mb-2">
                            <span class="text-muted">Delivery Charges</span>
                            <span class="text-success fw-bold"><del class="text-muted">₹40</del> FREE</span>
                        </div>

                        <div class="price-row d-flex justify-content-between mb-2">
                            <span class="text-muted">Campus Packaging</span>
                            <span class="text-success fw-semibold">FREE</span>
                        </div>

                        <hr class="my-3">

                        <div class="total-row d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold fs-5">Total Payable</span>
                            <strong class="fs-4 text-primary-custom">₹<?php echo number_format($grand_total, 2); ?></strong>
                        </div>

                        <?php if ($total_savings > 0) { ?>
                            <div class="savings-alert p-2 text-success fw-bold rounded-2 bg-success-subtle text-center small mb-3">
                                🎉 You will save ₹<?php echo number_format($total_savings, 2); ?> on this order!
                            </div>
                        <?php } ?>

                        <div class="safe-delivery-strip text-center text-muted small pt-2 border-top">
                            <i class="bi bi-shield-check text-success fs-5 d-block mb-1"></i>
                            Safe and Secure Payments. 100% Authentic College Essentials.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function selectPaymentMethod(method) {
    const cards = document.querySelectorAll('.payment-method-card');
    cards.forEach(c => c.classList.remove('selected'));

    const radio = document.getElementById(
        method === 'upi' ? 'payUPI' :
        method === 'card' ? 'payCard' :
        method === 'netbanking' ? 'payNetBanking' :
        method === 'cod' ? 'payCOD' : 'payRazorpay'
    );
    if (radio) {
        radio.checked = true;
        const parentCard = radio.closest('.payment-method-card');
        if (parentCard) parentCard.classList.add('selected');
    }

    const btn = document.getElementById('mainSubmitBtn');
    const totalStr = "₹<?php echo number_format($grand_total, 2); ?>";

    if (method === 'upi') {
        btn.innerHTML = '📱 Pay ' + totalStr + ' via UPI';
        btn.className = 'btn btn-warning-custom flex-grow-1 py-3 fw-bold fs-5 shadow-sm';
    } else if (method === 'card') {
        btn.innerHTML = '💳 Pay ' + totalStr + ' with Card';
        btn.className = 'btn btn-warning-custom flex-grow-1 py-3 fw-bold fs-5 shadow-sm';
    } else if (method === 'netbanking') {
        btn.innerHTML = '🏦 Pay ' + totalStr + ' via Net Banking';
        btn.className = 'btn btn-warning-custom flex-grow-1 py-3 fw-bold fs-5 shadow-sm';
    } else if (method === 'cod') {
        btn.innerHTML = '🛍️ Place Order (Cash on Delivery)';
        btn.className = 'btn btn-orange-custom flex-grow-1 py-3 fw-bold fs-5 shadow-sm';
    } else if (method === 'razorpay') {
        btn.innerHTML = '⚡ Launch Razorpay (' + totalStr + ')';
        btn.className = 'btn btn-primary-custom flex-grow-1 py-3 fw-bold fs-5 shadow-sm';
    }
}

// Bank selection button highlights
document.querySelectorAll('.bank-option-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        document.querySelectorAll('.bank-option-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const radio = this.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
    });
});

// Card number auto-spacing
const cardInput = document.getElementById('cardNumberInput');
if (cardInput) {
    cardInput.addEventListener('input', function (e) {
        let val = e.target.value.replace(/\D/g, '').substring(0, 16);
        let formatted = val.match(/.{1,4}/g)?.join(' ') || val;
        e.target.value = formatted;
    });
}
</script>

<?php include(__DIR__ . '/includes/footer.php'); ?>