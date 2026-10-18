<?php

require_once "../includes/auth.php";
require_once "../config/database.php";



$stmt = $pdo->query("
    SELECT
        id,
        full_name
    FROM members
    WHERE status = 'active'
    ORDER BY full_name
");

$members = $stmt->fetchAll(PDO::FETCH_ASSOC);


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



if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $member_id = $_POST["member_id"] ?? "";
        $plan_id = $_POST["plan_id"] ?? "";
        $start_date = $_POST["start_date"] ?? "";


        /* Validate */

        if (
            empty($member_id) ||
            empty($plan_id) ||
            empty($start_date)
        ) {

            throw new Exception(
                "Please fill in all required fields."
            );
        }

        $stmt = $pdo->prepare("
            SELECT
                duration_days
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



        $start = new DateTime($start_date);

        $start->modify(
            "+" . (int)$plan["duration_days"] . " days"
        );

        $end_date = $start->format("Y-m-d");


        if ($end_date < date("Y-m-d")) {

            $status = "expired";

        } else {

            $status = "active";
        }


        $stmt = $pdo->prepare("
            INSERT INTO memberships
            (
                member_id,
                plan_id,
                start_date,
                end_date,
                status
            )
            VALUES
            (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $member_id,
            $plan_id,
            $start_date,
            $end_date,
            $status
        ]);


        header(
            "Location: index.php?added=1"
        );

        exit;


    } catch (Exception $e) {

        $error_message =
            $e->getMessage();

    } catch (PDOException $e) {

        $error_message =
            "Unable to add membership. Please try again.";
    }
}


require_once "../includes/header.php";

?>


<link
    rel="stylesheet"
    href="/gym-management/css/membership-form.css?v=5"
>


<div class="membership-form-page">



    <div class="membership-form-header">

        <div>

            <span class="membership-form-label">
                MEMBERSHIP MANAGEMENT
            </span>

            <h1>
                Add Membership
            </h1>

            <p>
                Create a new membership for a gym member.
            </p>

        </div>


        <a
            href="/gym-management/membership/index.php"
            class="membership-back-button"
        >
            ← Back to Memberships
        </a>

    </div>


    <?php if (!empty($error_message)): ?>

        <div class="membership-error">

            <?= htmlspecialchars(
                $error_message
            ) ?>

        </div>

    <?php endif; ?>


    <div class="membership-form-card">


        <div class="membership-form-card-header">


            <div class="membership-form-icon">
                +
            </div>


            <div>

                <h2>
                    Membership Information
                </h2>

                <p>
                    Select a member, plan and start date.
                </p>

            </div>


        </div>



        <form
            method="POST"
            class="membership-form"
        >



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
                            value="<?= $member["id"] ?>"
                        >

                            <?= htmlspecialchars(
                                $member["full_name"]
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>




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
                            value="<?= $plan["id"] ?>"
                            data-duration="<?= $plan["duration_days"] ?>"
                            data-price="<?= $plan["price"] ?>"
                        >

                            <?= htmlspecialchars(
                                $plan["plan_name"]
                            ) ?>

                            -
                            ₹<?= number_format(
                                $plan["price"],
                                2
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="membership-plan-info">

                <div class="membership-plan-info-item">

                    <span>
                        Plan Duration
                    </span>

                    <strong id="planDuration">
                        -
                    </strong>

                </div>


                <div class="membership-plan-info-item">

                    <span>
                        Plan Price
                    </span>

                    <strong id="planPrice">
                        ₹0.00
                    </strong>

                </div>


            </div>



            <div class="membership-form-group">

                <label for="start_date">
                    Start Date
                </label>


                <input
                    type="date"
                    name="start_date"
                    id="start_date"
                    value="<?= date("Y-m-d") ?>"
                    required
                >

            </div>



            <div class="membership-form-group">

                <label for="end_date">
                    End Date
                </label>


                <input
                    type="date"
                    id="end_date"
                    readonly
                >


                <small>
                    End date is calculated automatically
                    based on the selected plan.
                </small>

            </div>


            <div class="membership-form-actions">


                <a
                    href="/gym-management/membership/index.php"
                    class="membership-cancel-button"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="membership-submit-button"
                >
                    ✓ Add Membership
                </button>


            </div>


        </form>

    </div>

</div>


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


function calculateEndDate() {

    const selectedPlan =
        planSelect.options[
            planSelect.selectedIndex
        ];


    if (!selectedPlan) {

        planDuration.textContent = "-";

        planPrice.textContent = "₹0.00";

        endDate.value = "";

        return;
    }


    const duration =
        selectedPlan.dataset.duration;

    const price =
        selectedPlan.dataset.price;


    /* Plan Duration */

    if (duration) {

        planDuration.textContent =
            duration + " Days";

    } else {

        planDuration.textContent =
            "-";
    }


    /* Plan Price */

    if (price) {

        planPrice.textContent =
            "₹" +
            Number(price).toLocaleString(
                "en-IN",
                {
                    minimumFractionDigits: 2
                }
            );

    } else {

        planPrice.textContent =
            "₹0.00";
    }


    /* End Date */

    if (
        !duration ||
        !startDate.value
    ) {

        endDate.value = "";

        return;
    }


    const date =
        new Date(
            startDate.value + "T00:00:00"
        );


    date.setDate(
        date.getDate() +
        parseInt(duration)
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


planSelect.addEventListener(
    "change",
    calculateEndDate
);




startDate.addEventListener(
    "change",
    calculateEndDate
);


calculateEndDate();

</script>