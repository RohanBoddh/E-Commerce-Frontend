<?php
$razorpayKeyId = trim(($_ENV['RAZORPAY_KEY_ID'] ?? getenv('RAZORPAY_KEY_ID')) ?: '');
$razorpayKeySecret = trim(($_ENV['RAZORPAY_KEY_SECRET'] ?? getenv('RAZORPAY_KEY_SECRET')) ?: '');

$isConfigured = !empty($razorpayKeyId) && !empty($razorpayKeySecret);

define('RAZORPAY_CONFIGURED', $isConfigured);
define('RAZORPAY_KEY_ID', $isConfigured ? $razorpayKeyId : 'rzp_test_placeholder');
define('RAZORPAY_KEY_SECRET', $isConfigured ? $razorpayKeySecret : 'placeholder_secret');