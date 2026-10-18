
<?php

// Razorpay Test Mode Configuration
// Keep this file on the server. Never expose the Key Secret in HTML or JavaScript.

define('RAZORPAY_KEY_ID', 'rzp_test_Tlrl1INFpMHuv9');
define('RAZORPAY_KEY_SECRET', '6QuDo3jFEc6I48OxOXr28QIM');

require_once __DIR__ . '/../vendor/autoload.php';

use Razorpay\Api\Api;

$razorpay = new Api(
    RAZORPAY_KEY_ID,
    RAZORPAY_KEY_SECRET
);
