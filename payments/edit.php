<?php

require_once "../includes/auth.php";
require_once "../config/database.php";


/* Fetch existing payment */

$id = $_GET["id"] ?? null;

if (!$id) {
    die("Invalid payment ID.");
}


$stmt = $pdo->prepare("
    SELECT *
    FROM payments
    WHERE id = ?
");

$stmt->execute([$id]);

$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    die("Payment not found.");
}


/* Fetch active memberships */

$stmt = $pdo->query("
    SELECT
        memberships.id,
        members.full_name,
        membership_plans.plan_name,
        membership_plans.price
    FROM memberships
    JOIN members
        ON memberships.member_id = members.id
    JOIN membership_plans
        ON memberships.plan_id = membership_plans.id
    WHERE memberships.status = 'active'
    AND memberships.end_date >= CURDATE()
    ORDER BY members.full_name
");

$memberships = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* Update payment */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $membership_id = $_POST["membership_id"];
        $payment_method = $_POST["payment_method"];

        $transaction_reference =
            trim($_POST["transaction_reference"] ?? "");

        $status = $_POST["status"];


        /* Validate payment method */

        $allowed_methods = [
            "cash",
            "upi",
            "card",
            "bank_transfer"
        ];

        if (!in_array($payment_method, $allowed_methods, true)) {
            throw new Exception("Invalid payment method.");
        }


        /* Validate payment status */

        $allowed_statuses = [
            "paid",
            "pending",
            "refunded"
        ];

        if (!in_array($status, $allowed_statuses, true)) {
            throw new Exception("Invalid payment status.");
        }


        /* Fetch selected membership */

        $stmt = $pdo->prepare("
            SELECT
                memberships.id,
                memberships.status,
                memberships.end_date,
                membership_plans.price
            FROM memberships
            JOIN membership_plans
                ON memberships.plan_id = membership_plans.id
            WHERE memberships.id = ?
        ");

        $stmt->execute([$membership_id]);

        $selected_membership =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$selected_membership) {

            throw new Exception(
                "Invalid membership selected."
            );
        }


        /* Check membership */

        if (
            $selected_membership["status"] !== "active" ||
            $selected_membership["end_date"] < date("Y-m-d")
        ) {

            throw new Exception(
                "Payment cannot be assigned to an expired membership."
            );
        }


        /* Get amount from database */

        $amount =
            $selected_membership["price"];


        /* Update payment */

        $stmt = $pdo->prepare("
            UPDATE payments
            SET
                membership_id = ?,
                amount = ?,
                payment_method = ?,
                transaction_reference = ?,
                status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $membership_id,
            $amount,
            $payment_method,
            $transaction_reference,
            $status,
            $id
        ]);


        header("Location: index.php?updated=1");
        exit;


    } catch (Exception $e) {

        $error_message =
            $e->getMessage();

    } catch (PDOException $e) {

        $error_message =
            "Unable to update payment. Please try again.";
    }
}


/* Load header */

require_once "../includes/header.php";

?>

<link rel="stylesheet" href="/gym-management/css/payment-form.css?v=2">


<div class="payment-form-page">

    <!-- Page Header -->

    <div class="payment-form-header">

        <div>

            <span class="payment-form-label">
                PAYMENT MANAGEMENT
            </span>

            <h1>Edit Payment</h1>

            <p>
                Update the payment information below.
            </p>

        </div>


        <a
            href="/gym-management/payments/index.php"
            class="payment-back-button"
        >
            ← Back to Payments
        </a>

    </div>


    <!-- Error Message -->

    <?php if (!empty($error_message)): ?>

        <div class="payment-form-error">

            <?= htmlspecialchars($error_message) ?>

        </div>

    <?php endif; ?>


    <!-- Form Card -->

    <div class="payment-form-card">

        <div class="payment-form-card-header">

            <div class="payment-form-icon">
                ✎
            </div>

            <div>

                <h2>Payment Details</h2>

                <p>
                    Modify the payment information as required.
                </p>

            </div>

        </div>


        <form method="POST" class="payment-form">


            <!-- Membership -->

            <div class="payment-form-group">

                <label for="membership_id">
                    Membership
                </label>

                <select
                    name="membership_id"
                    id="membership_id"
                    required
                >

                    <option value="">
                        Select Membership
                    </option>


                    <?php foreach ($memberships as $membership): ?>

                        <option
                            value="<?= $membership["id"] ?>"
                            data-price="<?= $membership["price"] ?>"
                            <?= (
                                $membership["id"]
                                == $payment["membership_id"]
                            ) ? "selected" : "" ?>
                        >

                            <?= htmlspecialchars(
                                $membership["full_name"]
                            ) ?>

                            -

                            <?= htmlspecialchars(
                                $membership["plan_name"]
                            ) ?>

                            -
                            ₹<?= number_format(
                                (float)$membership["price"],
                                2
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Amount -->

            <div class="payment-form-group">

                <label for="amount">
                    Amount
                </label>

                <div class="payment-amount-input">

                    <span>₹</span>

                    <input
                        type="number"
                        name="amount"
                        id="amount"
                        min="0"
                        step="0.01"
                        value="<?= htmlspecialchars(
                            $payment["amount"]
                        ) ?>"
                        readonly
                        required
                    >

                </div>

                <small class="payment-help-text">
                    Amount is automatically taken from the selected membership plan.
                </small>

            </div>


            <!-- Payment Method -->

            <div class="payment-form-group">

                <label for="payment_method">
                    Payment Method
                </label>

                <select
                    name="payment_method"
                    id="payment_method"
                    required
                >

                    <option
                        value="cash"
                        <?= (
                            $payment["payment_method"]
                            === "cash"
                        ) ? "selected" : "" ?>
                    >
                        Cash
                    </option>

                    <option
                        value="upi"
                        <?= (
                            $payment["payment_method"]
                            === "upi"
                        ) ? "selected" : "" ?>
                    >
                        UPI
                    </option>

                    <option
                        value="card"
                        <?= (
                            $payment["payment_method"]
                            === "card"
                        ) ? "selected" : "" ?>
                    >
                        Card
                    </option>

                    <option
                        value="bank_transfer"
                        <?= (
                            $payment["payment_method"]
                            === "bank_transfer"
                        ) ? "selected" : "" ?>
                    >
                        Bank Transfer
                    </option>

                </select>

            </div>


            <!-- Transaction Reference -->

            <div class="payment-form-group">

                <label for="transaction_ref">
                    Transaction Reference
                </label>

                <input
                    type="text"
                    name="transaction_reference"
                    id="transaction_ref"
                    value="<?= htmlspecialchars(
                        $payment["transaction_reference"] ?? ""
                    ) ?>"
                    placeholder="Enter transaction / UTR reference"
                >

            </div>


            <!-- Status -->

            <div class="payment-form-group">

                <label for="status">
                    Payment Status
                </label>

                <select
                    name="status"
                    id="status"
                    required
                >

                    <option
                        value="paid"
                        <?= (
                            $payment["status"]
                            === "paid"
                        ) ? "selected" : "" ?>
                    >
                        Paid
                    </option>

                    <option
                        value="pending"
                        <?= (
                            $payment["status"]
                            === "pending"
                        ) ? "selected" : "" ?>
                    >
                        Pending
                    </option>

                    <option
                        value="refunded"
                        <?= (
                            $payment["status"]
                            === "refunded"
                        ) ? "selected" : "" ?>
                    >
                        Refunded
                    </option>

                </select>

            </div>


            <!-- Actions -->

            <div class="payment-form-actions">

                <a
                    href="/gym-management/payments/index.php"
                    class="payment-cancel-button"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="payment-submit-button"
                >
                    Update Payment
                </button>

            </div>

        </form>

    </div>

</div>


<script>

const membershipSelect =
    document.getElementById("membership_id");

const amountInput =
    document.getElementById("amount");


function updateAmount() {

    const selectedOption =
        membershipSelect.options[
            membershipSelect.selectedIndex
        ];

    const price =
        selectedOption.dataset.price;


    if (price) {

        amountInput.value = price;

    } else {

        amountInput.value = "";

    }
}


membershipSelect.addEventListener(
    "change",
    updateAmount
);

</script>