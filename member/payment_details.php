<?php

require_once "../includes/auth.php";
require_once "../config/database.php";


/* =========================================================
   ALLOW MEMBERS ONLY
========================================================= */

if (($_SESSION["role"] ?? "") !== "member") {

    header("Location: ../auth/login.php");
    exit;
}


$user_id = $_SESSION["user_id"];


/* =========================================================
   GET MEMBER
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        id,
        full_name
    FROM members
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([
    $user_id
]);

$member = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$member) {

    die("Member profile not found.");

}


$member_id = $member["id"];


/* =========================================================
   GET PAYMENT ID
========================================================= */

$payment_id = (int) (
    $_GET["id"] ?? 0
);


if ($payment_id <= 0) {

    header("Location: payments.php");
    exit;

}


/* =========================================================
   GET PAYMENT DETAILS
========================================================= */

$stmt = $pdo->prepare("
    SELECT

        p.id AS payment_id,

        p.amount,

        p.payment_date,

        p.payment_method,

        p.transaction_reference,

        p.status,

        m.id AS membership_id,

        m.start_date,

        m.end_date,

        m.status AS membership_status,

        mp.plan_name,

        mp.price AS plan_price

    FROM payments p

    INNER JOIN memberships m
        ON p.membership_id = m.id

    INNER JOIN membership_plans mp
        ON m.plan_id = mp.id

    WHERE p.id = ?

    AND m.member_id = ?

    LIMIT 1
");


$stmt->execute([
    $payment_id,
    $member_id
]);


$payment = $stmt->fetch(
    PDO::FETCH_ASSOC
);


/* =========================================================
   PAYMENT NOT FOUND
========================================================= */

if (!$payment) {

    die("Payment record not found.");

}


/* =========================================================
   HEADER
========================================================= */

require_once "../includes/header.php";

?>


<link
    rel="stylesheet"
    href="/gym-management/css/member-payments.css?v=4"
>


<div class="member-payments-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="page-header">

        <div>

            <span class="page-label">
                MEMBER PORTAL
            </span>

            <h1>
                Payment Details
            </h1>

            <p>
                View complete details of your payment transaction.
            </p>

        </div>

    </div>



    <!-- =====================================================
         PAYMENT RECEIPT
    ====================================================== -->

    <div class="payment-receipt-card">


        <!-- =================================================
             RECEIPT HEADER
        ================================================== -->

        <div class="payment-receipt-header">


            <div>

                <span class="card-label">
                    GYMFIT
                </span>

                <h2>
                    Payment Receipt
                </h2>

            </div>


            <div class="receipt-payment-id">

                <span>
                    PAYMENT ID
                </span>

                <strong>
                    #<?= (int)
                    $payment["payment_id"] ?>
                </strong>

            </div>


        </div>



        <!-- =================================================
             PAYMENT STATUS
        ================================================== -->

        <div class="receipt-status-row">


            <span>
                Payment Status
            </span>


            <span
                class="payment-status status-<?= htmlspecialchars(
                    $payment["status"]
                ) ?>"
            >

                <?= htmlspecialchars(
                    ucfirst(
                        $payment["status"]
                    )
                ) ?>

            </span>


        </div>



        <!-- =================================================
             MEMBER INFORMATION
        ================================================== -->

        <div class="receipt-section">


            <span class="receipt-section-label">
                MEMBER INFORMATION
            </span>


            <div class="receipt-grid">


                <div class="receipt-info">

                    <span>
                        Member Name
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            $member["full_name"]
                        ) ?>
                    </strong>

                </div>


                <div class="receipt-info">

                    <span>
                        Membership ID
                    </span>

                    <strong>
                        #<?= (int)
                        $payment["membership_id"] ?>
                    </strong>

                </div>


            </div>


        </div>



        <!-- =================================================
             PAYMENT INFORMATION
        ================================================== -->

        <div class="receipt-section">


            <span class="receipt-section-label">
                PAYMENT INFORMATION
            </span>


            <div class="receipt-grid">


                <!-- AMOUNT -->

                <div class="receipt-info">

                    <span>
                        Amount Paid
                    </span>

                    <strong class="receipt-amount">

                        ₹<?= number_format(
                            (float)
                            $payment["amount"],
                            2
                        ) ?>

                    </strong>

                </div>



                <!-- PAYMENT DATE -->

                <div class="receipt-info">

                    <span>
                        Payment Date
                    </span>

                    <strong>

                        <?= date(
                            "d M Y, h:i A",
                            strtotime(
                                $payment[
                                    "payment_date"
                                ]
                            )
                        ) ?>

                    </strong>

                </div>



                <!-- PAYMENT METHOD -->

                <div class="receipt-info">

                    <span>
                        Payment Method
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            ucwords(
                                str_replace(
                                    "_",
                                    " ",
                                    $payment[
                                        "payment_method"
                                    ]
                                )
                            )
                        ) ?>

                    </strong>

                </div>



                <!-- TRANSACTION REFERENCE -->

                <div class="receipt-info">

                    <span>
                        Transaction Reference
                    </span>

                    <strong>

                        <?php if (
                            !empty(
                                $payment[
                                    "transaction_reference"
                                ]
                            )
                        ): ?>

                            <?= htmlspecialchars(
                                $payment[
                                    "transaction_reference"
                                ]
                            ) ?>

                        <?php else: ?>

                            —

                        <?php endif; ?>

                    </strong>

                </div>


            </div>


        </div>



        <!-- =================================================
             MEMBERSHIP INFORMATION
        ================================================== -->

        <div class="receipt-section">


            <span class="receipt-section-label">
                MEMBERSHIP INFORMATION
            </span>


            <div class="receipt-grid">


                <!-- PLAN -->

                <div class="receipt-info">

                    <span>
                        Membership Plan
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            (string)
                            $payment["plan_name"]
                        ) ?>

                    </strong>

                </div>



                <!-- PLAN PRICE -->

                <div class="receipt-info">

                    <span>
                        Plan Price
                    </span>

                    <strong>

                        ₹<?= number_format(
                            (float)
                            $payment["plan_price"],
                            2
                        ) ?>

                    </strong>

                </div>



                <!-- START DATE -->

                <div class="receipt-info">

                    <span>
                        Membership Start
                    </span>

                    <strong>

                        <?= date(
                            "d M Y",
                            strtotime(
                                $payment[
                                    "start_date"
                                ]
                            )
                        ) ?>

                    </strong>

                </div>



                <!-- END DATE -->

                <div class="receipt-info">

                    <span>
                        Membership End
                    </span>

                    <strong>

                        <?= date(
                            "d M Y",
                            strtotime(
                                $payment[
                                    "end_date"
                                ]
                            )
                        ) ?>

                    </strong>

                </div>


            </div>


        </div>



        <!-- =================================================
             ACTIONS
        ================================================== -->

        <div class="receipt-actions">


            <a
                href="payments.php"
                class="receipt-back-button"
            >
                ← Back to Payments
            </a>


            <button
                type="button"
                onclick="window.print()"
                class="receipt-print-button"
            >
                Print Receipt
            </button>


        </div>


    </div>


</div>