<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

$id = $_GET["id"] ?? null;

if (!$id) {
    die("Invalid membership plan ID.");
}


/* Fetch plan */

$stmt = $pdo->prepare(
    "SELECT * FROM membership_plans WHERE id = ?"
);

$stmt->execute([$id]);

$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    die("Membership plan not found.");
}


/* Delete plan */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $stmt = $pdo->prepare(
            "DELETE FROM membership_plans WHERE id = ?"
        );

        $stmt->execute([$id]);

        header("Location: index.php?deleted=1");
        exit;

    } catch (PDOException $e) {

        $error_message =
            "Unable to delete this membership plan. Please try again.";
    }
}


/* Load header */

require_once "../includes/header.php";

?>

<link rel="stylesheet" href="/gym-management/css/plan-form.css?v=3">


<div class="plan-form-page">

    <!-- Page Header -->

    <div class="plan-form-header">

        <div>

            <span class="plan-form-label">
                MEMBERSHIP PLAN MANAGEMENT
            </span>

            <h1>Delete Membership Plan</h1>

            <p>
                Review the plan information before deleting.
            </p>

        </div>


        <a
            href="/gym-management/plans/index.php"
            class="plan-back-button"
        >
            ← Back to Plans
        </a>

    </div>


    <!-- Error Message -->

    <?php if (!empty($error_message)): ?>

        <div class="plan-delete-error">

            <?= htmlspecialchars($error_message) ?>

        </div>

    <?php endif; ?>


    <!-- Delete Card -->

    <div class="plan-delete-card">

        <div class="plan-delete-icon">
            !
        </div>


        <h2>
            Delete Membership Plan?
        </h2>


        <p class="plan-delete-message">

            Are you sure you want to delete this membership plan?

        </p>


        <!-- Warning -->

        <div class="plan-delete-warning">

            <strong>Warning:</strong>
            This action cannot be undone.

        </div>


        <!-- Plan Information -->

        <div class="plan-delete-info">

            <div class="plan-delete-detail">

                <span>Plan Name</span>

                <strong>
                    <?= htmlspecialchars(
                        $plan["plan_name"]
                    ) ?>
                </strong>

            </div>


            <div class="plan-delete-detail">

                <span>Price</span>

                <strong>
                    ₹<?= htmlspecialchars(
                        $plan["price"]
                    ) ?>
                </strong>

            </div>


            <div class="plan-delete-detail">

                <span>Duration</span>

                <strong>
                    <?= htmlspecialchars(
                        $plan["duration_days"]
                    ) ?>
                    Days
                </strong>

            </div>


            <div class="plan-delete-detail">

                <span>Status</span>

                <strong>
                    <?= htmlspecialchars(
                        ucfirst($plan["status"])
                    ) ?>
                </strong>

            </div>

        </div>


        <!-- Actions -->

        <form
            method="POST"
            class="plan-delete-actions"
        >

            <a
                href="/gym-management/plans/index.php"
                class="plan-cancel-button"
            >
                Cancel
            </a>


            <button
                type="submit"
                class="plan-delete-button"
                onclick="return confirm(
                    'Are you sure you want to permanently delete this membership plan?'
                );"
            >
                Delete Membership Plan
            </button>

        </form>

    </div>

</div>