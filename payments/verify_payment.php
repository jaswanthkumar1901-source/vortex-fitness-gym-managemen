
<?php


require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/razorpay.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// --------------------------------------------------
// 1. Validate logged-in member
// --------------------------------------------------

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "member"
) {
    header("Location: /gym-management/index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /gym-management/member/buy_membership.php");
    exit;
}

// --------------------------------------------------
// 2. Validate CSRF token
// --------------------------------------------------

$sessionToken = $_SESSION["csrf_token"] ?? "";
$postToken = $_POST["csrf_token"] ?? "";

if (
    !is_string($sessionToken) ||
    !is_string($postToken) ||
    $sessionToken === "" ||
    !hash_equals($sessionToken, $postToken)
) {
    http_response_code(403);
    exit("Invalid request token. Please return to the membership page and try again.");
}

// --------------------------------------------------
// 3. Get Razorpay response
// --------------------------------------------------

$orderId = trim((string) ($_POST["razorpay_order_id"] ?? ""));
$paymentId = trim((string) ($_POST["razorpay_payment_id"] ?? ""));
$signature = trim((string) ($_POST["razorpay_signature"] ?? ""));

if ($orderId === "" || $paymentId === "" || $signature === "") {
    http_response_code(400);
    exit("Payment verification details are missing.");
}

$userId = (int) $_SESSION["user_id"];

try {

    // --------------------------------------------------
    // 4. Find the order belonging to this user
    // --------------------------------------------------

    $stmt = $pdo->prepare(
        "SELECT *
         FROM membership_payment_orders
         WHERE razorpay_order_id = ?
           AND user_id = ?
         LIMIT 1"
    );

    $stmt->execute([$orderId, $userId]);
    $paymentOrder = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$paymentOrder) {
        http_response_code(404);
        exit("Payment order not found for this account.");
    }

    // If this order was already processed, do not create
    // another membership or payment record.
    if ($paymentOrder["status"] === "paid") {
        header(
            "Location: /gym-management/member/membership.php?payment=success"
        );
        exit;
    }

    if ($paymentOrder["status"] !== "created") {
        http_response_code(400);
        exit("This payment order cannot be processed.");
    }

    // --------------------------------------------------
    // 5. Verify Razorpay signature
    // --------------------------------------------------

    $razorpay->utility->verifyPaymentSignature([
        "razorpay_order_id" => $orderId,
        "razorpay_payment_id" => $paymentId,
        "razorpay_signature" => $signature
    ]);

    // --------------------------------------------------
    // 6. Fetch payment directly from Razorpay
    // --------------------------------------------------

    $gatewayPayment = $razorpay->payment->fetch($paymentId);

    if ((string) $gatewayPayment["order_id"] !== $orderId) {
        throw new RuntimeException(
            "Razorpay payment does not belong to this order."
        );
    }

    $expectedAmount = (int) round(
        (float) $paymentOrder["amount"] * 100
    );

    if ((int) $gatewayPayment["amount"] !== $expectedAmount) {
        throw new RuntimeException(
            "Razorpay payment amount does not match the order."
        );
    }

    if (
        strtolower((string) $gatewayPayment["currency"]) !== "inr"
    ) {
        throw new RuntimeException("Unexpected payment currency.");
    }

    // Only captured payments can activate a membership.
    if ((string) $gatewayPayment["status"] !== "captured") {
        http_response_code(400);
        exit(
            "Your payment is not yet captured. "
            . "Please check the payment status before retrying."
        );
    }

    // --------------------------------------------------
    // 7. Map Razorpay method to the existing ENUM
    // --------------------------------------------------

    $gatewayMethod = strtolower(
        (string) $gatewayPayment["method"]
    );

    $methodMap = [
        "upi" => "upi",
        "card" => "card",
        "netbanking" => "bank_transfer",
        "bank_transfer" => "bank_transfer"
    ];

    if (!isset($methodMap[$gatewayMethod])) {
        throw new RuntimeException(
            "Unsupported payment method: " . $gatewayMethod
            . ". Update the payments.payment_method ENUM before processing this method."
        );
    }

    $paymentMethod = $methodMap[$gatewayMethod];

    // --------------------------------------------------
    // 8. Start database transaction
    // --------------------------------------------------

    $pdo->beginTransaction();

    // Lock the order to prevent duplicate processing.
    $stmt = $pdo->prepare(
        "SELECT *
         FROM membership_payment_orders
         WHERE id = ?
         FOR UPDATE"
    );

    $stmt->execute([(int) $paymentOrder["id"]]);
    $lockedOrder = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lockedOrder) {
        throw new RuntimeException("Payment order not found.");
    }

    // A second request may have completed while this request
    // was verifying the payment.
    if ($lockedOrder["status"] === "paid") {
        $pdo->commit();

        header(
            "Location: /gym-management/member/membership.php?payment=success"
        );
        exit;
    }

    if ($lockedOrder["status"] !== "created") {
        throw new RuntimeException(
            "Payment order is no longer available."
        );
    }

    // Check whether the payment ID has already been used.
    $stmt = $pdo->prepare(
        "SELECT id
         FROM membership_payment_orders
         WHERE razorpay_payment_id = ?
         LIMIT 1"
    );

    $stmt->execute([$paymentId]);

    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new RuntimeException(
            "This Razorpay payment ID has already been recorded."
        );
    }

    // --------------------------------------------------
    // 9. Validate the plan and price
    // --------------------------------------------------

    $stmt = $pdo->prepare(
        "SELECT id, price, duration_days
         FROM membership_plans
         WHERE id = ?
           AND status = 'active'
         LIMIT 1"
    );

    $stmt->execute([(int) $lockedOrder["plan_id"]]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        throw new RuntimeException(
            "The selected membership plan is no longer active."
        );
    }

    if (
        (int) round((float) $plan["price"] * 100)
        !== (int) round((float) $lockedOrder["amount"] * 100)
    ) {
        throw new RuntimeException(
            "The membership plan price has changed. Contact the administrator."
        );
    }

    // Confirm that the member belongs to this logged-in user.
    $stmt = $pdo->prepare(
        "SELECT id
         FROM members
         WHERE id = ?
           AND user_id = ?
         LIMIT 1"
    );

    $stmt->execute([
        (int) $lockedOrder["member_id"],
        $userId
    ]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new RuntimeException(
            "The member account could not be verified."
        );
    }

    // --------------------------------------------------
    // 10. Calculate membership dates
    // --------------------------------------------------

    $stmt = $pdo->prepare(
        "SELECT id, end_date
         FROM memberships
         WHERE member_id = ?
           AND status = 'active'
         ORDER BY end_date DESC
         LIMIT 1
         FOR UPDATE"
    );

    $stmt->execute([(int) $lockedOrder["member_id"]]);
    $currentMembership = $stmt->fetch(PDO::FETCH_ASSOC);

    $today = new DateTimeImmutable("today");

    if (
        $currentMembership &&
        $currentMembership["end_date"] >= $today->format("Y-m-d")
    ) {
        // Renewal starts after the current membership expires.
        $startDate = (
            new DateTimeImmutable($currentMembership["end_date"])
        )->modify("+1 day");
    } else {
        $startDate = $today;
    }

    $durationDays = (int) $plan["duration_days"];

    if ($durationDays < 1) {
        throw new RuntimeException(
            "The selected plan has an invalid duration."
        );
    }

    $endDate = $startDate->modify(
        "+" . $durationDays . " days"
    );

    // --------------------------------------------------
    // 11. Create the new membership
    // --------------------------------------------------

    // Expire the previous active membership only if it has
    // already ended. Preserve an unexpired membership until
    // its end date.
    $stmt = $pdo->prepare(
        "UPDATE memberships
         SET status = 'expired'
         WHERE member_id = ?
           AND status = 'active'
           AND end_date < ?"
    );

    $stmt->execute([
        (int) $lockedOrder["member_id"],
        $today->format("Y-m-d")
    ]);

    $stmt = $pdo->prepare(
        "INSERT INTO memberships
            (member_id, plan_id, start_date, end_date, status)
         VALUES (?, ?, ?, ?, 'active')"
    );

    $stmt->execute([
        (int) $lockedOrder["member_id"],
        (int) $lockedOrder["plan_id"],
        $startDate->format("Y-m-d"),
        $endDate->format("Y-m-d")
    ]);

    $membershipId = (int) $pdo->lastInsertId();

    // --------------------------------------------------
    // 12. Save the payment record
    // --------------------------------------------------

    $stmt = $pdo->prepare(
        "INSERT INTO payments
            (membership_id, amount, payment_method,
             transaction_reference, status)
         VALUES (?, ?, ?, ?, 'paid')"
    );

    $stmt->execute([
        $membershipId,
        number_format(
            (float) $lockedOrder["amount"],
            2,
            ".",
            ""
        ),
        $paymentMethod,
        $paymentId
    ]);

    // --------------------------------------------------
    // 13. Mark the Razorpay order as paid
    // --------------------------------------------------

    $stmt = $pdo->prepare(
        "UPDATE membership_payment_orders
         SET razorpay_payment_id = ?,
             status = 'paid',
             membership_id = ?
         WHERE id = ?
           AND status = 'created'"
    );

    $stmt->execute([
        $paymentId,
        $membershipId,
        (int) $lockedOrder["id"]
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException(
            "Unable to update the payment order."
        );
    }

    // Commit all database changes together.
    $pdo->commit();

    // --------------------------------------------------
    // 14. Redirect after successful verification
    // --------------------------------------------------

    header(
        "Location: /gym-management/member/membership.php?payment=success"
    );
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Keep technical details in the PHP error log, not on screen.
    error_log(
        "VORTEX FITNESS payment verification error. "
        . "Order ID: " . $orderId
        . "; Payment ID: " . $paymentId
        . "; Error: " . $e->getMessage()
    );

    http_response_code(500);

    echo "We could not complete the membership update. "
       . "Do not make another payment yet. "
       . "Your payment may already have been received. "
       . "Please check the payment status or contact the gym administrator.";
    exit;
}
