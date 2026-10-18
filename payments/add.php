<?php

require_once "../includes/auth.php";
require_once "../config/database.php";


/* Fetch currently active memberships */

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


/* Add payment */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $membership_id = $_POST["membership_id"];
        $payment_method = $_POST["payment_method"];

        $transaction_reference =
            trim($_POST["transaction_reference"] ?? "");


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


        /* Fetch membership and plan price */

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

        $membership = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$membership) {
            throw new Exception("Invalid membership selected.");
        }


        /* Check membership status */

        if (
            $membership["status"] !== "active" ||
            $membership["end_date"] < date("Y-m-d")
        ) {
            throw new Exception(
                "Payment cannot be added for an expired membership."
            );
        }


        /* Get amount from membership plan */

        $amount = $membership["price"];


        /* Insert payment */

        $stmt = $pdo->prepare("
            INSERT INTO payments
            (
                membership_id,
                amount,
                payment_method,
                transaction_reference,
                status
            )
            VALUES (?, ?, ?, ?, 'paid')
        ");

        $stmt->execute([
            $membership_id,
            $amount,
            $payment_method,
            $transaction_reference
        ]);


        header("Location: index.php?added=1");
        exit;

    } catch (Exception $e) {

        $error_message = $e->getMessage();

    } catch (PDOException $e) {

        $error_message =
            "Unable to add payment. Please try again.";
    }
}


/* Load header */

require_once "../includes/header.php";

?>

<link rel="stylesheet" href="/gym-management/css/payment-form.css?v=1">


<div class="payment-form-page">

    <!-- Page Header -->

    <div class="payment-form-header">

        <div>

            <span class="payment-form-label">
                PAYMENT MANAGEMENT
            </span>

            <h1>Add Payment</h1>

            <p>
                Record a payment for an active membership.
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
                ₹
            </div>

            <div>

                <h2>Payment Details</h2>

                <p>
                    Enter the payment information below.
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

                    <option value="">
                        Select Payment Method
                    </option>

                    <option value="cash">
                        Cash
                    </option>

                    <option value="upi">
                        UPI
                    </option>

                    <option value="card">
                        Card
                    </option>

                    <option value="bank_transfer">
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
                    placeholder="Enter transaction / UTR reference (optional)"
                >

                <small class="payment-help-text">
                    Required only when you need to record a reference number.
                </small>

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
                    Add Payment
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


membershipSelect.addEventListener(
    "change",
    function () {

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
);

</script>