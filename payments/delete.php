<?php

require_once "../includes/auth.php";
require_once "../config/database.php";


/* =========================================================
   GET PAYMENT ID
========================================================= */

$id = $_GET["id"] ?? null;


/* Validate Payment ID */

if (!$id || !is_numeric($id)) {
    die("Invalid payment ID.");
}


$error_message = "";


/* =========================================================
   FETCH PAYMENT DETAILS
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        payments.id,
        members.full_name,
        membership_plans.plan_name,
        payments.amount,
        payments.payment_method,
        payments.transaction_reference,
        payments.status
    FROM payments
    JOIN memberships
        ON payments.membership_id = memberships.id
    JOIN members
        ON memberships.member_id = members.id
    JOIN membership_plans
        ON memberships.plan_id = membership_plans.id
    WHERE payments.id = ?
");

$stmt->execute([$id]);

$payment = $stmt->fetch(PDO::FETCH_ASSOC);


/* Check Payment Exists */

if (!$payment) {
    die("Payment not found.");
}


/* =========================================================
   DELETE PAYMENT
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $stmt = $pdo->prepare("
            DELETE FROM payments
            WHERE id = ?
        ");

        $stmt->execute([$id]);


        /* Redirect after successful delete */

        header("Location: index.php?deleted=1");
        exit;


    } catch (PDOException $e) {

        $error_message =
            "Unable to delete the payment. Please try again.";
    }
}


/* =========================================================
   LOAD HEADER
========================================================= */

require_once "../includes/header.php";

?>

<link
    rel="stylesheet"
    href="/gym-management/css/payment-form.css?v=4"
>


<div class="payment-form-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="payment-form-header">

        <div>

            <span class="payment-form-label">
                PAYMENT MANAGEMENT
            </span>

            <h1>
                Delete Payment
            </h1>

            <p>
                Review the payment information before deleting.
            </p>

        </div>


        <a
            href="/gym-management/payments/index.php"
            class="payment-back-button"
        >
            ← Back to Payments
        </a>

    </div>



    <!-- =====================================================
         ERROR MESSAGE
    ====================================================== -->

    <?php if (!empty($error_message)): ?>

        <div class="payment-form-error">

            <?= htmlspecialchars($error_message) ?>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         DELETE CARD
    ====================================================== -->

    <div class="payment-delete-card">


        <!-- Delete Icon -->

        <div class="payment-delete-icon">
            !
        </div>


        <!-- Heading -->

        <h2>
            Delete Payment?
        </h2>


        <!-- Message -->

        <p class="payment-delete-message">

            Are you sure you want to delete this payment?

        </p>



        <!-- =================================================
             WARNING
        ================================================== -->

        <div class="payment-delete-warning">

            <strong>Warning:</strong>

            This action cannot be undone.

        </div>



        <!-- =================================================
             PAYMENT INFORMATION
        ================================================== -->

        <div class="payment-delete-info">


            <!-- Member -->

            <div class="payment-delete-detail">

                <span>
                    Member
                </span>

                <strong>

                    <?= htmlspecialchars(
                        $payment["full_name"]
                    ) ?>

                </strong>

            </div>



            <!-- Membership Plan -->

            <div class="payment-delete-detail">

                <span>
                    Membership Plan
                </span>

                <strong>

                    <?= htmlspecialchars(
                        $payment["plan_name"]
                    ) ?>

                </strong>

            </div>



            <!-- Amount -->

            <div class="payment-delete-detail">

                <span>
                    Amount
                </span>

                <strong>

                    ₹<?= number_format(
                        (float)$payment["amount"],
                        2
                    ) ?>

                </strong>

            </div>



            <!-- Payment Method -->

            <div class="payment-delete-detail">

                <span>
                    Payment Method
                </span>

                <strong>

                    <?= htmlspecialchars(
                        ucwords(
                            str_replace(
                                "_",
                                " ",
                                $payment["payment_method"]
                            )
                        )
                    ) ?>

                </strong>

            </div>



            <!-- Transaction Reference -->

            <?php if (!empty($payment["transaction_reference"])): ?>

                <div class="payment-delete-detail">

                    <span>
                        Transaction Reference
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            $payment["transaction_reference"]
                        ) ?>

                    </strong>

                </div>

            <?php endif; ?>



            <!-- Status -->

            <div class="payment-delete-detail">

                <span>
                    Status
                </span>

                <strong>

                    <?= htmlspecialchars(
                        ucfirst($payment["status"])
                    ) ?>

                </strong>

            </div>


        </div>



        <!-- =================================================
             MAIN ACTIONS
        ================================================== -->

        <div class="payment-delete-actions">


            <!-- Cancel -->

            <a
                href="/gym-management/payments/index.php"
                class="payment-cancel-button"
            >
                Cancel
            </a>


            <!-- Open Custom Popup -->

            <button
                type="button"
                class="payment-delete-button"
                onclick="openDeletePopup()"
            >
                Delete Payment
            </button>


        </div>


    </div>



    <!-- =====================================================
         CUSTOM DELETE POPUP
    ====================================================== -->

    <div
        class="payment-popup-overlay"
        id="paymentDeletePopup"
    >


        <div class="payment-popup">


            <!-- Close Button -->

            <button
                type="button"
                class="payment-popup-close"
                onclick="closeDeletePopup()"
            >
                ×
            </button>



            <!-- Popup Icon -->

            <div class="payment-popup-icon">
                !
            </div>



            <!-- Popup Heading -->

            <h2>
                Delete Payment?
            </h2>



            <!-- Popup Message -->

            <p>
                Are you sure you want to permanently
                delete this payment?
            </p>



            <!-- Popup Warning -->

            <div class="payment-popup-warning">

                <strong>Warning:</strong>

                This action cannot be undone.

            </div>



            <!-- Popup Actions -->

            <div class="payment-popup-actions">


                <!-- Cancel -->

                <button
                    type="button"
                    class="payment-popup-cancel"
                    onclick="closeDeletePopup()"
                >
                    Cancel
                </button>



                <!-- Actual Delete Form -->

                <form method="POST">

                    <button
                        type="submit"
                        class="payment-popup-delete"
                    >
                        Delete Payment
                    </button>

                </form>


            </div>


        </div>

    </div>


</div>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>


/* Open Popup */

function openDeletePopup() {

    document.getElementById(
        "paymentDeletePopup"
    ).style.display = "flex";

}



/* Close Popup */

function closeDeletePopup() {

    document.getElementById(
        "paymentDeletePopup"
    ).style.display = "none";

}



/* Close Popup When Clicking Outside */

document.getElementById(
    "paymentDeletePopup"
).addEventListener(
    "click",
    function(event) {

        if (event.target === this) {

            closeDeletePopup();

        }

    }
);


</script>