<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $plan_name = trim($_POST["plan_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = $_POST["price"] ?? "";
    $duration_days = $_POST["duration_days"] ?? "";
    $status = $_POST["status"] ?? "active";

    $sql = "
        INSERT INTO membership_plans
        (plan_name, description, price, duration_days, status)
        VALUES
        (:plan_name, :description, :price, :duration_days, :status)
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":plan_name" => $plan_name,
        ":description" => $description,
        ":price" => $price,
        ":duration_days" => $duration_days,
        ":status" => $status
    ]);

    header("Location: index.php?added=1");
    exit;
}

require_once "../includes/header.php";

?>

<link rel="stylesheet" href="/gym-management/css/plan-form.css?v=1">

<div class="plan-form-page">

    <!-- Page Header -->

    <div class="plan-form-header">

        <div>

            <span class="plan-form-label">
                MEMBERSHIP PLAN MANAGEMENT
            </span>

            <h1>Add Membership Plan</h1>

            <p>
                Create a new membership plan for your gym.
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
                +
            </div>

            <div>

                <h2>Plan Details</h2>

                <p>
                    Enter the membership plan information below.
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
                    placeholder="Example: Premium Monthly"
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
                ></textarea>

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
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                            required
                        >

                    </div>

                </div>


                <div class="plan-form-group">

                    <label for="duration_days">
                        Duration
                    </label>

                    <div class="plan-input-suffix">

                        <input
                            type="number"
                            name="duration_days"
                            id="duration_days"
                            min="1"
                            placeholder="30"
                            required
                        >

                        <span>Days</span>

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

                    <option value="active">
                        Active
                    </option>

                    <option value="inactive">
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
                    Add Membership Plan
                </button>

            </div>

        </form>

    </div>

</div>