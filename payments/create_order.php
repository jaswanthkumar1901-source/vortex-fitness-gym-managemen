
<?php


require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/razorpay.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "member"
) {
    header("Location: /gym-management/index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /gym-management/member/buy_membership.php");
    exit;
}

/* =========================================================
   VALIDATE CSRF TOKEN
========================================================= */

$sessionToken = $_SESSION["csrf_token"] ?? "";
$postToken = $_POST["csrf_token"] ?? "";

if (
    !is_string($sessionToken) ||
    !is_string($postToken) ||
    $sessionToken === "" ||
    !hash_equals($sessionToken, $postToken)
) {
    http_response_code(403);
    exit("Invalid request token. Refresh the page and try again.");
}

$userId = (int) $_SESSION["user_id"];
$planId = filter_input(INPUT_POST, "plan_id", FILTER_VALIDATE_INT);

if (!$planId || $planId < 1) {
    exit("Invalid membership plan selected.");
}

/* =========================================================
   CREATE RAZORPAY ORDER
========================================================= */

try {

    // Get the logged-in member.

    $stmt = $pdo->prepare(
        "SELECT id, full_name, email, status
         FROM members
         WHERE user_id = ?
         LIMIT 1"
    );

    $stmt->execute([$userId]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        exit("Member profile not found.");
    }

    if (
        isset($member["status"]) &&
        $member["status"] !== "active"
    ) {
        exit("Your member account is not active.");
    }

    // Fetch the selected plan and its price from the database.

    $stmt = $pdo->prepare(
        "SELECT id, plan_name, price, duration_days
         FROM membership_plans
         WHERE id = ?
           AND status = 'active'
         LIMIT 1"
    );

    $stmt->execute([$planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        exit("The selected plan is unavailable.");
    }

    $amountInPaise = (int) round(
        (float) $plan["price"] * 100
    );

    if ($amountInPaise < 100) {
        exit("The plan price must be at least ₹1.");
    }

    // Create the Razorpay order.

    $order = $razorpay->order->create([
        "receipt" => "gym_" . bin2hex(random_bytes(10)),
        "amount" => $amountInPaise,
        "currency" => "INR",
        "notes" => [
            "user_id" => (string) $userId,
            "member_id" => (string) $member["id"],
            "plan_id" => (string) $plan["id"]
        ]
    ]);

    $orderId = (string) $order["id"];

    // Save the order before opening checkout.

    $stmt = $pdo->prepare(
        "INSERT INTO membership_payment_orders
            (
                user_id,
                member_id,
                plan_id,
                razorpay_order_id,
                amount,
                status
            )
         VALUES (?, ?, ?, ?, ?, 'created')"
    );

    $stmt->execute([
        $userId,
        (int) $member["id"],
        (int) $plan["id"],
        $orderId,
        number_format(
            (float) $plan["price"],
            2,
            ".",
            ""
        )
    ]);

    // Prepare values for the checkout page.

    $keyId = RAZORPAY_KEY_ID;

    $memberName = (string) $member["full_name"];
    $memberEmail = (string) ($member["email"] ?? "");
    $planName = (string) $plan["plan_name"];

    $displayAmount = number_format(
        (float) $plan["price"],
        2
    );

} catch (Throwable $e) {

    error_log(
        "Razorpay order creation error: " .
        $e->getMessage()
    );

    http_response_code(500);

    exit(
        "Unable to create the payment order. Please try again."
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Complete Membership Payment | GYMFIT</title>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <style>
        /* =====================================================
           GYMFIT PAYMENT PAGE
        ===================================================== */

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #172b4d;
            background: #f3f6fc;
        }

        .payment-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 18px;
        }

        .payment-container {
            width: 100%;
            max-width: 540px;
        }

        /* BRAND */

        .payment-brand {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
            color: #173b70;
            font-size: 27px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .payment-brand-icon {
            font-size: 30px;
        }

        /* PAYMENT CARD */

        .payment-card {
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #e0e8f5;
            border-radius: 20px;
            box-shadow: 0 16px 45px rgba(30, 64, 120, 0.09);
        }

        .payment-card-top {
            height: 6px;
            background: linear-gradient(
                90deg,
                #2563eb,
                #60a5fa
            );
        }

        .payment-card-content {
            padding: 34px;
        }

        .payment-eyebrow {
            display: inline-block;
            margin-bottom: 12px;
            color: #2563eb;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.3px;
            text-transform: uppercase;
        }

        .payment-card h1 {
            margin: 0 0 10px;
            color: #142746;
            font-size: 29px;
            line-height: 1.3;
        }

        .payment-subtitle {
            margin: 0 0 28px;
            color: #718096;
            font-size: 14px;
            line-height: 1.7;
        }

        /* PLAN DETAILS */

        .payment-plan-box {
            padding: 22px;
            margin-bottom: 22px;
            background: #f7f9fe;
            border: 1px solid #e4ebf6;
            border-radius: 14px;
        }

        .payment-plan-label {
            display: block;
            margin-bottom: 8px;
            color: #7a8ba5;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .payment-plan-name {
            margin: 0;
            color: #1d3557;
            font-size: 23px;
            font-weight: 750;
        }

        .payment-divider {
            height: 1px;
            margin: 20px 0;
            background: #e1e8f2;
        }

        .payment-amount-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .payment-amount-label {
            color: #687b96;
            font-size: 14px;
        }

        .payment-amount {
            color: #1d4ed8;
            font-size: 27px;
            font-weight: 800;
        }

        /* PAY BUTTON */

        .payment-button {
            display: flex;
            width: 100%;
            min-height: 52px;
            align-items: center;
            justify-content: center;
            padding: 14px 20px;
            border: none;
            border-radius: 10px;
            background: #2563eb;
            color: #ffffff;
            font-family: inherit;
            font-size: 15px;
            font-weight: 750;
            cursor: pointer;
            transition:
                background 0.2s ease,
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .payment-button:hover {
            background: #1d4ed8;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.22);
            transform: translateY(-1px);
        }

        .payment-button:focus-visible {
            outline: 3px solid #93c5fd;
            outline-offset: 3px;
        }

        .payment-button:disabled {
            cursor: not-allowed;
            opacity: 0.65;
            transform: none;
        }

        /* SECURITY MESSAGE */

        .payment-security {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            color: #728198;
            font-size: 12px;
            line-height: 1.6;
            text-align: center;
        }

        .payment-security-icon {
            color: #16a34a;
            font-size: 16px;
        }

        /* BACK LINK */

        .payment-back {
            display: block;
            width: fit-content;
            margin: 22px auto 0;
            color: #41638f;
            font-size: 13px;
            font-weight: 650;
            text-decoration: none;
        }

        .payment-back:hover {
            color: #2563eb;
        }

        /* RESPONSIVE */

        @media (max-width: 480px) {
            .payment-page {
                padding: 20px 14px;
            }

            .payment-card-content {
                padding: 24px 20px;
            }

            .payment-card h1 {
                font-size: 25px;
            }

            .payment-plan-box {
                padding: 18px;
            }

            .payment-amount {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>

<div class="payment-page">

    <div class="payment-container">

        <!-- BRAND -->

        <div class="payment-brand">
            <span class="payment-brand-icon">💪</span>
            <span>GYMFIT</span>
        </div>

        <!-- PAYMENT CARD -->

        <div class="payment-card">

            <div class="payment-card-top"></div>

            <div class="payment-card-content">

                <span class="payment-eyebrow">
                    Secure Checkout
                </span>

                <h1>Complete Your Payment</h1>

                <p class="payment-subtitle">
                    You're one step closer to your fitness goals.
                    Review your membership details before proceeding.
                </p>

                <!-- PLAN DETAILS -->

                <div class="payment-plan-box">

                    <span class="payment-plan-label">
                        Selected Membership Plan
                    </span>

                    <h2 class="payment-plan-name">
                        <?= htmlspecialchars(
                            $planName,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </h2>

                    <div class="payment-divider"></div>

                    <div class="payment-amount-row">

                        <span class="payment-amount-label">
                            Total Amount
                        </span>

                        <span class="payment-amount">
                            ₹<?= htmlspecialchars(
                                $displayAmount,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </span>

                    </div>

                </div>

                <!-- RAZORPAY BUTTON -->

                <button
                    type="button"
                    id="payButton"
                    class="payment-button"
                >
                    Pay ₹<?= htmlspecialchars(
                        $displayAmount,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </button>

                <!-- SECURITY MESSAGE -->

                <div class="payment-security">
                    <span class="payment-security-icon">🔒</span>

                    <span>
                        Secure payment powered by Razorpay.
                        Your payment is verified before your
                        membership is activated.
                    </span>
                </div>

            </div>

        </div>

        <!-- BACK LINK -->

        <a
            href="/gym-management/member/membership.php"
            class="payment-back"
        >
            &larr; Back to My Membership
        </a>

    </div>

</div>

<script>
"use strict";

const options = {
    key: <?= json_encode($keyId) ?>,

    amount: <?= json_encode($amountInPaise) ?>,

    currency: "INR",

    name: "GYMFIT",

    description: <?= json_encode($planName) ?>,

    order_id: <?= json_encode($orderId) ?>,

    prefill: {
        name: <?= json_encode($memberName) ?>,
        email: <?= json_encode($memberEmail) ?>
    },

    theme: {
        color: "#2563eb"
    },

    handler: function (response) {

        const form = document.createElement("form");

        form.method = "POST";

        form.action =
            "/gym-management/payments/verify_payment.php";

        const values = {
            csrf_token: <?= json_encode($sessionToken) ?>,
            razorpay_order_id: response.razorpay_order_id,
            razorpay_payment_id: response.razorpay_payment_id,
            razorpay_signature: response.razorpay_signature
        };

        for (const [name, value] of Object.entries(values)) {

            const input = document.createElement("input");

            input.type = "hidden";
            input.name = name;
            input.value = value;

            form.appendChild(input);
        }

        document.body.appendChild(form);

        form.submit();
    },

    modal: {
        ondismiss: function () {
            alert(
                "Checkout closed. Your membership has not been activated."
            );
        }
    }
};

const payButton = document.getElementById("payButton");

payButton.addEventListener("click", function () {

    if (typeof Razorpay === "undefined") {
        alert(
            "Unable to load Razorpay Checkout. " +
            "Please check your internet connection and try again."
        );
        return;
    }

    payButton.disabled = true;
    payButton.textContent = "Opening secure checkout...";

    try {

        const checkout = new Razorpay(options);

        checkout.on("payment.failed", function () {

            alert(
                "Payment failed. Please try again."
            );

            payButton.disabled = false;
            payButton.textContent =
                "Pay ₹<?= htmlspecialchars(
                    $displayAmount,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>";
        });

        checkout.open();

        // Allow the user to reopen checkout if it is dismissed.
        payButton.disabled = false;
        payButton.textContent =
            "Pay ₹<?= htmlspecialchars(
                $displayAmount,
                ENT_QUOTES,
                "UTF-8"
            ) ?>";

    } catch (error) {

        console.error("Razorpay Checkout error:", error);

        alert(
            "Unable to open payment checkout. Please try again."
        );

        payButton.disabled = false;
        payButton.textContent =
            "Pay ₹<?= htmlspecialchars(
                $displayAmount,
                ENT_QUOTES,
                "UTF-8"
            ) ?>";
    }
});
</script>

</body>
</html>
