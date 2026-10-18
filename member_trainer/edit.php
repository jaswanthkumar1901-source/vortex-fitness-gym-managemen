<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$id = $_GET["id"] ?? null;


/* =========================================================
   CHECK ASSIGNMENT ID
========================================================= */

if (!$id || !is_numeric($id)) {
    die("Invalid assignment ID.");
}


/* =========================================================
   FETCH EXISTING ASSIGNMENT
========================================================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM member_trainer
    WHERE id = ?
");

$stmt->execute([$id]);

$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    die("Assignment not found.");
}


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
   UPDATE ASSIGNMENT
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $member_id = $_POST["member_id"] ?? "";
        $trainer_id = $_POST["trainer_id"] ?? "";
        $assigned_date = $_POST["assigned_date"] ?? "";


        $stmt = $pdo->prepare("
            UPDATE member_trainer
            SET
                member_id = ?,
                trainer_id = ?,
                assigned_date = ?
            WHERE id = ?
        ");


        $stmt->execute([
            $member_id,
            $trainer_id,
            $assigned_date,
            $id
        ]);


        header("Location: index.php?updated=1");
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
                "Unable to update the trainer assignment. Please try again.";
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
    href="/gym-management/css/member-trainer-form.css?v=2"
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
                Edit Assignment
            </h1>

            <p>
                Update the member, trainer or assignment date.
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
                ✎
            </div>

            <div>

                <h2>
                    Edit Assignment Information
                </h2>

                <p>
                    Update the trainer assignment details below.
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
                            <?= (
                                $member["id"] ==
                                $assignment["member_id"]
                            ) ? "selected" : "" ?>
                        >
                            <?= htmlspecialchars(
                                $member["full_name"]
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>


                <small class="member-trainer-help-text">
                    Only active members are available.
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
                            <?= (
                                $trainer["id"] ==
                                $assignment["trainer_id"]
                            ) ? "selected" : "" ?>
                        >
                            <?= htmlspecialchars(
                                $trainer["full_name"]
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>


                <small class="member-trainer-help-text">
                    Only active trainers are available.
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
                    value="<?= htmlspecialchars(
                        $assignment["assigned_date"]
                    ) ?>"
                    required
                >


                <small class="member-trainer-help-text">
                    Select the date from which this assignment is effective.
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
                    ✓ Update Assignment
                </button>

            </div>

        </form>

    </div>

</div>