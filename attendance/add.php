<?php

require_once "../includes/auth.php";
require_once "../config/database.php";


/* =========================================================
   FETCH ACTIVE MEMBERS
========================================================= */

$stmt = $pdo->query(
    "SELECT id, full_name
     FROM members
     WHERE status = 'active'
     ORDER BY full_name"
);

$members = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   CHECK IN
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $member_id = $_POST["member_id"];


        $stmt = $pdo->prepare(
            "INSERT INTO attendance (member_id)
             VALUES (?)"
        );

        $stmt->execute([$member_id]);


        header("Location: index.php?added=1");
        exit;


    } catch (PDOException $e) {

        $error_message =
            "Unable to check in the member. Please try again.";
    }
}


/* =========================================================
   LOAD HEADER
========================================================= */

require_once "../includes/header.php";

?>

<link
    rel="stylesheet"
    href="/gym-management/css/attendance-form.css?v=1"
>


<div class="attendance-form-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="attendance-form-header">

        <div>

            <span class="attendance-form-label">
                ATTENDANCE MANAGEMENT
            </span>

            <h1>
                Check In
            </h1>

            <p>
                Record today's gym attendance for an active member.
            </p>

        </div>


        <a
            href="/gym-management/attendance/index.php"
            class="attendance-back-button"
        >
            ← Back to Attendance
        </a>

    </div>



    <!-- =====================================================
         ERROR MESSAGE
    ====================================================== -->

    <?php if (!empty($error_message)): ?>

        <div class="attendance-form-error">

            <?= htmlspecialchars($error_message) ?>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         FORM CARD
    ====================================================== -->

    <div class="attendance-form-card">


        <!-- Card Header -->

        <div class="attendance-form-card-header">

            <div class="attendance-form-icon">
                ✓
            </div>

            <div>

                <h2>
                    Check-In Details
                </h2>

                <p>
                    Select the member who is entering the gym.
                </p>

            </div>

        </div>



        <!-- Form -->

        <form
            method="POST"
            class="attendance-form"
        >


            <!-- Member -->

            <div class="attendance-form-group">

                <label for="member_id">
                    Select Member
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


                <small class="attendance-help-text">
                    Only active members are available for check-in.
                </small>

            </div>



            <!-- Actions -->

            <div class="attendance-form-actions">

                <a
                    href="/gym-management/attendance/index.php"
                    class="attendance-cancel-button"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="attendance-submit-button"
                >
                    ✓ Check In
                </button>

            </div>


        </form>

    </div>

</div>