<?php

require_once "../includes/auth.php";
require_once "../config/database.php";


/* Get Membership ID */

$id = $_GET["id"] ?? null;


if (!$id) {
    die("Invalid membership ID.");
}


/* Fetch Existing Membership */

$stmt = $pdo->prepare("
    SELECT *
    FROM memberships
    WHERE id = ?
");

$stmt->execute([$id]);

$membership = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$membership) {
    die("Membership not found.");
}


/* Fetch Active Members */

$stmt = $pdo->query("
    SELECT id, full_name
    FROM members
    WHERE status = 'active'
    ORDER BY full_name
");

$members = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* Fetch Active Membership Plans */

$stmt = $pdo->query("
    SELECT
        id,
        plan_name,
        duration_days,
        price
    FROM membership_plans
    WHERE status = 'active'
    ORDER BY plan_name
");

$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* Update Membership */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $member_id = $_POST["member_id"] ?? "";
        $plan_id = $_POST["plan_id"] ?? "";
        $start_date = $_POST["start_date"] ?? "";


        /* Validate Required Fields */

        if (
            empty($member_id) ||
            empty($plan_id) ||
            empty($start_date)
        ) {

            throw new Exception(
                "Please fill in all required fields."
            );
        }


        /* Get Selected Plan */

        $stmt = $pdo->prepare("
            SELECT
                duration_days,
                price
            FROM membership_plans
            WHERE id = ?
            AND status = 'active'
        ");

        $stmt->execute([$plan_id]);

        $plan = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$plan) {

            throw new Exception(
                "Invalid membership plan."
            );
        }


        /* Calculate End Date */

        $start = new DateTime($start_date);

        $start->modify(
            "+" . (int)$plan["duration_days"] . " days"
        );

        $end_date = $start->format("Y-m-d");


        /* Determine Membership Status */

        if ($membership["status"] === "cancelled") {

            $status = "cancelled";

        } elseif ($end_date < date("Y-m-d")) {

            $status = "expired";

        } else {

            $status = "active";
        }


        /* Update Membership */

        $stmt = $pdo->prepare("
            UPDATE memberships
            SET
                member_id = ?,
                plan_id = ?,
                start_date = ?,
                end_date = ?,
                status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $member_id,
            $plan_id,
            $start_date,
            $end_date,
            $status,
            $id
        ]);


        /* Redirect */

        header(
            "Location: index.php?updated=1"
        );

        exit;

    } catch (Exception $e) {

        $error_message =
            $e->getMessage();

    } catch (PDOException $e) {

        $error_message =
            "Unable to update membership. Please try again.";
    }
}


/*
 * Load Header
 *
 * Important:
 * Header is loaded AFTER the POST logic
 * so the redirect can work correctly.
 */

require_once "../includes/header.php";

?>


<!-- Membership Form CSS -->

<link
    rel="stylesheet"
    href="/gym-management/css/membership-form.css?v=2"
>


<div class="membership-form-page">


    <!-- Page Header -->

    <div class="membership-form-header">

        <div>

            <span class="membership-form-label">
                MEMBERSHIP MANAGEMENT
            </span>

            <h1>
                Edit Membership
            </h1>

            <p>
                Update the member, plan, and membership dates.
            </p>

        </div>


        <a
            href="index.php"
            class="membership-back-button"
        >
            ← Back to Memberships
        </a>

    </div>


    <!-- Error Message -->

    <?php if (!empty($error_message)): ?>

        <div class="membership-error">

            <?= htmlspecialchars($error_message) ?>

        </div>

    <?php endif; ?>


    <!-- Main Form Card -->

    <div class="membership-form-card">


        <!-- Card Header -->

        <div class="membership-form-card-header">

            <div class="membership-form-icon">
                ✎
            </div>

            <div>

                <h2>
                    Membership Details
                </h2>

                <p>
                    Modify the membership information below.
                </p>

            </div>

        </div>


        <!-- Form -->

        <form
            method="POST"
            class="membership-form"
        >


            <!-- Member -->

            <div class="membership-form-group">

                <label for="member_id">
                    Member
                </label>

                <select
                    name="member_id"
                    id="member_id"
                    required
                >

                    <option value="">
                        Select Member
                    </option>


                    <?php foreach ($members as $member): ?>

                        <option
                            value="<?= $member['id'] ?>"
                            <?= (
                                $member['id']
                                == $membership['member_id']
                            )
                            ? 'selected'
                            : ''
                            ?>
                        >

                            <?= htmlspecialchars(
                                $member['full_name']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Membership Plan -->

            <div class="membership-form-group">

                <label for="plan_id">
                    Membership Plan
                </label>

                <select
                    name="plan_id"
                    id="plan_id"
                    required
                >

                    <option value="">
                        Select Membership Plan
                    </option>


                    <?php foreach ($plans as $plan): ?>

                        <option
                            value="<?= $plan['id'] ?>"
                            data-duration="<?= $plan['duration_days'] ?>"
                            data-price="<?= $plan['price'] ?>"
                            <?= (
                                $plan['id']
                                == $membership['plan_id']
                            )
                            ? 'selected'
                            : ''
                            ?>
                        >

                            <?= htmlspecialchars(
                                $plan['plan_name']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Plan Information -->

            <div class="membership-plan-info">


                <!-- Duration -->

                <div class="membership-plan-info-item">

                    <span>
                        Plan Duration
                    </span>

                    <strong id="planDuration">
                        -
                    </strong>

                </div>


                <!-- Price -->

                <div class="membership-plan-info-item">

                    <span>
                        Plan Price
                    </span>

                    <strong id="planPrice">
                        ₹0.00
                    </strong>

                </div>

            </div>


            <!-- Start Date -->

            <div class="membership-form-group">

                <label for="start_date">
                    Start Date
                </label>

                <input
                    type="date"
                    name="start_date"
                    id="start_date"
                    value="<?= htmlspecialchars(
                        $membership['start_date']
                    ) ?>"
                    required
                >

            </div>


            <!-- End Date -->

            <div class="membership-form-group">

                <label for="end_date">
                    End Date
                </label>

                <input
                    type="date"
                    name="end_date"
                    id="end_date"
                    value="<?= htmlspecialchars(
                        $membership['end_date']
                    ) ?>"
                    readonly
                >

                <small>
                    End date is automatically calculated
                    from the selected plan duration.
                </small>

            </div>


            <!-- Current Status -->

            <div class="membership-form-group">

                <label>
                    Current Status
                </label>

                <input
                    type="text"
                    value="<?= htmlspecialchars(
                        ucfirst($membership['status'])
                    ) ?>"
                    readonly
                    class="membership-status-input"
                >

            </div>


            <!-- Form Actions -->

            <div class="membership-form-actions">


                <a
                    href="index.php"
                    class="membership-cancel-button"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="membership-submit-button"
                >
                    Update Membership
                </button>

            </div>


        </form>

    </div>

</div>


<!-- JavaScript -->

<script>

const planSelect =
    document.getElementById("plan_id");

const startDate =
    document.getElementById("start_date");

const endDate =
    document.getElementById("end_date");

const planDuration =
    document.getElementById("planDuration");

const planPrice =
    document.getElementById("planPrice");


/*
 * Update Plan Information
 */

function updatePlanInformation() {

    const selectedPlan =
        planSelect.options[
            planSelect.selectedIndex
        ];


    if (!selectedPlan) {

        planDuration.textContent = "-";

        planPrice.textContent = "₹0.00";

        return;
    }


    const duration =
        selectedPlan.dataset.duration;

    const price =
        selectedPlan.dataset.price;


    /* Duration */

    if (duration) {

        planDuration.textContent =
            duration + " Days";

    } else {

        planDuration.textContent =
            "-";
    }


    /* Price */

    if (price) {

        planPrice.textContent =
            "₹" +
            parseFloat(price).toFixed(2);

    } else {

        planPrice.textContent =
            "₹0.00";
    }

}


/*
 * Calculate End Date
 */

function calculateEndDate() {

    const selectedPlan =
        planSelect.options[
            planSelect.selectedIndex
        ];


    if (
        !selectedPlan ||
        !selectedPlan.dataset.duration ||
        !startDate.value
    ) {

        endDate.value = "";

        return;
    }


    const duration =
        parseInt(
            selectedPlan.dataset.duration
        );


    const date =
        new Date(
            startDate.value + "T00:00:00"
        );


    date.setDate(
        date.getDate() + duration
    );


    const year =
        date.getFullYear();


    const month =
        String(
            date.getMonth() + 1
        ).padStart(2, "0");


    const day =
        String(
            date.getDate()
        ).padStart(2, "0");


    endDate.value =
        `${year}-${month}-${day}`;
}


/*
 * Plan Changed
 */

planSelect.addEventListener(
    "change",
    function () {

        updatePlanInformation();

        calculateEndDate();

    }
);


/*
 * Start Date Changed
 */

startDate.addEventListener(
    "change",
    function () {

        calculateEndDate();

    }
);


/*
 * Load Existing Plan Information
 */

updatePlanInformation();

calculateEndDate();

</script>