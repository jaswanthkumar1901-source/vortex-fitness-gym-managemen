<?php

require_once "../includes/auth.php";
require_once "../config/database.php";


/* =========================================================
   FETCH ACTIVE MEMBERS
========================================================= */

$stmt = $pdo->query("
    SELECT id, full_name
    FROM members
    WHERE status = 'active'
    ORDER BY full_name
");

$members = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   FETCH ACTIVE TRAINERS
========================================================= */

$stmt = $pdo->query("
    SELECT id, full_name
    FROM trainers
    WHERE status = 'active'
    ORDER BY full_name
");

$trainers = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   ASSIGN TRAINER
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $member_id = $_POST["member_id"] ?? "";
        $trainer_id = $_POST["trainer_id"] ?? "";
        $assigned_date = $_POST["assigned_date"] ?? "";


        $stmt = $pdo->prepare("
            INSERT INTO member_trainer
            (member_id, trainer_id, assigned_date)
            VALUES (?, ?, ?)
        ");


        $stmt->execute([
            $member_id,
            $trainer_id,
            $assigned_date
        ]);


        header("Location: index.php?added=1");
        exit;


    } catch (PDOException $e) {

        if (
            isset($e->errorInfo[0]) &&
            $e->errorInfo[0] === "23000"
        ) {

            $error_message =
                "This member is already assigned to this trainer.";

        } else {

            $error_message =
                "Unable to assign trainer. Please try again.";
        }
    }
}


/* =========================================================
   LOAD HEADER
========================================================= */

require_once "../includes/header.php";

?>


<link
    rel="stylesheet"
    href="/gym-management/css/member-trainer-form.css?v=1"
>


<!-- =========================================================
     ERROR POPUP
========================================================= -->

<?php if (!empty($error_message)): ?>

<div
    class="member-trainer-popup-overlay"
    id="assignmentErrorPopup"
>

    <div class="member-trainer-popup">


        <button
            type="button"
            class="member-trainer-popup-close"
            onclick="closeAssignmentError()"
        >
            &times;
        </button>


        <div class="member-trainer-popup-icon">
            !
        </div>


        <h2>
            Assignment Error
        </h2>


        <p>
            <?= htmlspecialchars($error_message) ?>
        </p>


        <button
            type="button"
            class="member-trainer-popup-button"
            onclick="closeAssignmentError()"
        >
            OK
        </button>

    </div>

</div>


<script>

function closeAssignmentError() {

    document.getElementById(
        "assignmentErrorPopup"
    ).style.display = "none";

}

</script>

<?php endif; ?>



<div class="member-trainer-form-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="member-trainer-form-header">

        <div>

            <span class="member-trainer-form-label">
                TRAINER ASSIGNMENT
            </span>

            <h1>
                Assign Trainer
            </h1>

            <p>
                Assign an active trainer to a gym member.
            </p>

        </div>


        <a
            href="/gym-management/member_trainer/index.php"
            class="member-trainer-back-button"
        >
            ← Back to Assignments
        </a>

    </div>



    <!-- =====================================================
         FORM CARD
    ====================================================== -->

    <div class="member-trainer-form-card">


        <!-- Card Header -->

        <div class="member-trainer-form-card-header">

            <div class="member-trainer-form-icon">
                ↔
            </div>

            <div>

                <h2>
                    Assignment Information
                </h2>

                <p>
                    Select a member, trainer and assignment date.
                </p>

            </div>

        </div>



        <!-- Form -->

        <form
            method="POST"
            class="member-trainer-form"
        >


            <!-- Member -->

            <div class="member-trainer-form-group">

                <label for="member_id">
                    Member
                    <span>*</span>
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
                            value="<?= htmlspecialchars(
                                $member["id"]
                            ) ?>"
                        >
                            <?= htmlspecialchars(
                                $member["full_name"]
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>


                <small class="member-trainer-help-text">
                    Only active members are shown.
                </small>

            </div>



            <!-- Trainer -->

            <div class="member-trainer-form-group">

                <label for="trainer_id">
                    Trainer
                    <span>*</span>
                </label>


                <select
                    name="trainer_id"
                    id="trainer_id"
                    required
                >

                    <option value="">
                        Select Trainer
                    </option>


                    <?php foreach ($trainers as $trainer): ?>

                        <option
                            value="<?= htmlspecialchars(
                                $trainer["id"]
                            ) ?>"
                        >
                            <?= htmlspecialchars(
                                $trainer["full_name"]
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>


                <small class="member-trainer-help-text">
                    Only active trainers are shown.
                </small>

            </div>



            <!-- Assigned Date -->

            <div class="member-trainer-form-group">

                <label for="assigned_date">
                    Assigned Date
                    <span>*</span>
                </label>


                <input
                    type="date"
                    name="assigned_date"
                    id="assigned_date"
                    required
                >


                <small class="member-trainer-help-text">
                    Select the date from which the trainer assignment begins.
                </small>

            </div>



            <!-- Actions -->

            <div class="member-trainer-form-actions">

                <a
                    href="/gym-management/member_trainer/index.php"
                    class="member-trainer-cancel-button"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="member-trainer-submit-button"
                >
                    ↔ Assign Trainer
                </button>

            </div>

        </form>

    </div>

</div>