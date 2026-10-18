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


/* Update plan */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $plan_name = trim($_POST["plan_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = $_POST["price"] ?? "";
    $duration_days = $_POST["duration_days"] ?? "";
    $status = $_POST["status"] ?? "active";

    $stmt = $pdo->prepare(
        "UPDATE membership_plans
         SET plan_name = ?,
             description = ?,
             price = ?,
             duration_days = ?,
             status = ?
         WHERE id = ?"
    );

    $stmt->execute([
        $plan_name,
        $description,
        $price,
        $duration_days,
        $status,
        $id
    ]);

    header("Location: index.php?updated=1");
    exit;
}


require_once "../includes/header.php";

?>

<link rel="stylesheet" href="/gym-management/css/plan-form.css?v=2">


<div class="plan-form-page">

    <!-- Page Header -->

    <div class="plan-form-header">

        <div>

            <span class="plan-form-label">
                MEMBERSHIP PLAN MANAGEMENT
            </span>

            <h1>Edit Membership Plan</h1>

            <p>
                Update the membership plan information.
            </p>

        </div>


        <a
            href="/gym-management/plans/index.php"
            class="plan-back-button"
        >
            ← Back to Plans
        </a>

    </div>


    <!-- Form Card -->

    <div class="plan-form-card">

        <div class="plan-form-card-header">

            <div class="plan-form-icon">
                ✎
            </div>

            <div>

                <h2>Plan Details</h2>

                <p>
                    Update the details of this membership plan.
                </p>

            </div>

        </div>


        <form method="POST" class="plan-form">


            <!-- Plan Name -->

            <div class="plan-form-group">

                <label for="plan_name">
                    Plan Name
                </label>

                <input
                    type="text"
                    name="plan_name"
                    id="plan_name"
                    value="<?= htmlspecialchars($plan["plan_name"]) ?>"
                    required
                >

            </div>


            <!-- Description -->

            <div class="plan-form-group">

                <label for="description">
                    Description
                </label>

                <textarea
                    name="description"
                    id="description"
                    rows="4"
                    placeholder="Enter a short description of this membership plan"
                ><?= htmlspecialchars($plan["description"] ?? "") ?></textarea>

            </div>


            <!-- Price + Duration -->

            <div class="plan-form-row">

                <div class="plan-form-group">

                    <label for="price">
                        Price
                    </label>

                    <div class="plan-input-prefix">

                        <span>₹</span>

                        <input
                            type="number"
                            name="price"
                            id="price"
                            value="<?= htmlspecialchars($plan["price"]) ?>"
                            min="0"
                            step="0.01"
                            required
                        >

                    </div>

                </div>


                <div class="plan-form-group">

                    <label for="duration_days">
                        Duration
                    </label>

                    <div class="plan-input-suffix">

                        <span>Days</span>

                        <input
                            type="number"
                            name="duration_days"
                            id="duration_days"
                            value="<?= htmlspecialchars($plan["duration_days"]) ?>"
                            min="1"
                            required
                        >

                    </div>

                </div>

            </div>


            <!-- Status -->

            <div class="plan-form-group">

                <label for="status">
                    Status
                </label>

                <select
                    name="status"
                    id="status"
                    required
                >

                    <option
                        value="active"
                        <?= ($plan["status"] === "active")
                            ? "selected"
                            : "" ?>
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        <?= ($plan["status"] === "inactive")
                            ? "selected"
                            : "" ?>
                    >
                        Inactive
                    </option>

                </select>

            </div>


            <!-- Actions -->

            <div class="plan-form-actions">

                <a
                    href="/gym-management/plans/index.php"
                    class="plan-cancel-button"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="plan-submit-button"
                >
                    Update Membership Plan
                </button>

            </div>

        </form>

    </div>

</div>  