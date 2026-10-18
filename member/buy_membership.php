
<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: /gym-management/index.php");
    exit;
}

/* =========================================================
   CHECK MEMBER ROLE
========================================================= */

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "member"
) {
    header("Location: /gym-management/index.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

/* =========================================================
   INITIALIZE CSRF TOKEN
========================================================= */

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["csrf_token"];

/* =========================================================
   FETCH MEMBER PROFILE
========================================================= */

$stmt = $pdo->prepare("
    SELECT id, full_name, email, status
    FROM members
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    http_response_code(403);
    exit("Member profile not found.");
}

if (strtolower(trim($member["status"])) !== "active") {
    http_response_code(403);
    exit("Your member account is not active. Please contact the gym.");
}

$member_id = (int) $member["id"];

/* =========================================================
   MARK EXPIRED MEMBERSHIPS
========================================================= */

$expireStmt = $pdo->prepare("
    UPDATE memberships
    SET status = 'expired'
    WHERE member_id = ?
      AND status = 'active'
      AND end_date < CURDATE()
");

$expireStmt->execute([$member_id]);

/* =========================================================
   FETCH CURRENT MEMBERSHIP
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        memberships.id,
        memberships.start_date,
        memberships.end_date,
        memberships.status,
        membership_plans.plan_name
    FROM memberships
    INNER JOIN membership_plans
        ON memberships.plan_id = membership_plans.id
    WHERE memberships.member_id = ?
      AND memberships.status = 'active'
      AND memberships.end_date >= CURDATE()
    ORDER BY memberships.end_date DESC
    LIMIT 1
");

$stmt->execute([$member_id]);

$currentMembership = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================================================
   FETCH AVAILABLE PLANS
========================================================= */

$stmt = $pdo->query("
    SELECT
        id,
        plan_name,
        description,
        price,
        duration_days
    FROM membership_plans
    WHERE status = 'active'
    ORDER BY price ASC
");

$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   LOAD SHARED HEADER
========================================================= */

require_once "../includes/header.php";

?>

<link
    rel="stylesheet"
    href="/gym-management/css/buy_membership.css?v=5"
>

<main class="buy-membership-page">

    <!-- PAGE HEADER -->

    <header class="buy-membership-heading">

        <span class="page-label">
            MEMBER PORTAL
        </span>

        <h1>Choose Your Membership</h1>

        <p class="page-description">
            Choose a membership plan that suits your fitness goals.
            Review the details and continue to payment.
        </p>

        <a
            href="/gym-management/member/dashboard.php"
            class="back-link"
        >
            &larr; Back to Dashboard
        </a>

    </header>


    <!-- MEMBER INFORMATION -->

    <div class="notice">

        Purchasing for
        <strong>
            <?= htmlspecialchars(
                $member["full_name"],
                ENT_QUOTES,
                "UTF-8"
            ) ?>
        </strong>

    </div>


    <!-- CURRENT MEMBERSHIP -->

    <?php if ($currentMembership): ?>

        <section class="current-membership">

            <h2>Current Membership</h2>

            <p>
                <strong>Plan:</strong>

                <?= htmlspecialchars(
                    $currentMembership["plan_name"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </p>

            <p>
                <strong>Start date:</strong>

                <?= date(
                    "d M Y",
                    strtotime($currentMembership["start_date"])
                ) ?>
            </p>

            <p>
                <strong>Expires on:</strong>

                <?= date(
                    "d M Y",
                    strtotime($currentMembership["end_date"])
                ) ?>
            </p>

            <span class="membership-status">
                Active
            </span>

            <p>
                You can select another plan below to purchase or renew
                your membership.
            </p>

        </section>

    <?php else: ?>

        <section class="current-membership">

            <h2>No Active Membership</h2>

            <p>
                Choose one of the available plans below to get started
                with GYMFIT.
            </p>

        </section>

    <?php endif; ?>


    <!-- AVAILABLE MEMBERSHIP PLANS -->

    <section class="plans-section">

        <h2>Available Plans</h2>

        <p>
            Compare the available plans and select the one you prefer.
        </p>


        <?php if (!empty($plans)): ?>

            <div class="plans-grid">

                <?php foreach ($plans as $plan): ?>

                    <article class="plan-card">

                        <!-- PLAN ICON -->

                        <div class="plan-icon" aria-hidden="true">
                            ★
                        </div>


                        <!-- PLAN NAME -->

                        <h3>
                            <?= htmlspecialchars(
                                $plan["plan_name"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </h3>


                        <!-- PLAN DESCRIPTION -->

                        <div class="plan-description">

                            <?php if (!empty($plan["description"])): ?>

                                <?= nl2br(
                                    htmlspecialchars(
                                        $plan["description"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    )
                                ) ?>

                            <?php else: ?>

                                Gym access for the selected membership
                                duration.

                            <?php endif; ?>

                        </div>


                        <!-- PLAN PRICE -->

                        <div class="plan-price">
                            ₹<?= number_format(
                                (float) $plan["price"],
                                2
                            ) ?>
                        </div>


                        <!-- PLAN DURATION -->

                        <p class="plan-duration">
                            <?= (int) $plan["duration_days"] ?> days
                        </p>


                        <!-- PLAN FEATURES -->

                        <ul class="plan-features">

                            <li>
                                ✓ Membership duration:
                                <?= (int) $plan["duration_days"] ?> days
                            </li>

                            <li>
                                ✓ Secure online payment
                            </li>

                            <li>
                                ✓ Payment confirmation
                            </li>

                        </ul>


                        <!-- SELECT / RENEW PLAN -->

                        <form
                            action="/gym-management/payments/create_order.php"
                            method="POST"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars(
                                    $csrf_token,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="plan_id"
                                value="<?= (int) $plan["id"] ?>"
                            >

                            <button
                                type="submit"
                                class="select-plan-btn"
                            >
                                <?= $currentMembership
                                    ? "Buy / Renew This Plan"
                                    : "Choose This Plan" ?>

                                &rarr;
                            </button>

                        </form>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-state">

                <h3>No Membership Plans Available</h3>

                <p>
                    There are currently no active membership plans.
                    Please contact the gym administrator.
                </p>

            </div>

        <?php endif; ?>

    </section>


    <!-- PAYMENT INFORMATION -->

    <div class="payment-notice">

        <strong>Secure Payment</strong>

        <p>
            Your payment will be based on the price of the selected
            plan. Membership activation should happen only after the
            payment has been verified successfully.
        </p>

    </div>

</main>

</body>
</html>
