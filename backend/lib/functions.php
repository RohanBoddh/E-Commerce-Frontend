<?php
function sanitize($data) {
    return htmlspecialchars(trim($data));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectIfNotLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function getCartCount($db, $user_id): int {
    $result = $db->cart->aggregate([
        ['$match' => ['user_id' => $user_id]],
        ['$group' => ['_id' => null, 'total' => ['$sum' => '$quantity']]]
    ])->toArray();

    return $result ? (int) $result[0]['total'] : 0;
}

function getWishlistCount($db, $user_id): int {
    return (int)$db->wishlist->countDocuments(['user_id' => $user_id]);
}

function getProductRating($product_id) {
    $ratingMap = [
        1 => 4.5,
        2 => 4.2,
        3 => 4.7,
        4 => 4.3,
        5 => 4.6,
        6 => 4.1,
        7 => 4.4,
        8 => 4.8,
        9 => 4.0,
        10 => 4.9
    ];
    return $ratingMap[$product_id] ?? 4.3;
}

function getDiscountPercent($price) {
    $price = (float)$price;
    if ($price >= 500) return 20;
    if ($price >= 300) return 15;
    if ($price >= 150) return 10;
    return 5;
}

function getOriginalPrice($price, $discountPercent) {
    $price = (float)$price;
    $discountPercent = (int)$discountPercent;
    if ($discountPercent <= 0) return $price;
    return $price / (1 - ($discountPercent / 100));
}

function truncateText($text, $length = 60) {
    $text = trim((string)$text);
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

if (!function_exists('mb_strimwidth')) {
    function mb_strimwidth($str, $start, $width, $trimmarker = '', $encoding = null) {
        $str = (string)$str;
        $sub = substr($str, $start, $width);
        if (strlen($str) > $width) {
            $markerLen = strlen($trimmarker);
            $sub = substr($sub, 0, max(0, $width - $markerLen)) . $trimmarker;
        }
        return $sub;
    }
}
?>