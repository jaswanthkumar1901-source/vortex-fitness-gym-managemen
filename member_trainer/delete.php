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
   FETCH ASSIGNMENT DETAILS
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        member_trainer.id,
        members.full_name AS member_name,
        trainers.full_name AS trainer_name,
        member_trainer.assigned_date
    FROM member_trainer
    JOIN members
        ON member_trainer.member_id = members.id
    JOIN trainers
        ON member_trainer.trainer_id = trainers.id
    WHERE member_trainer.id = ?
");

$stmt->execute([$id]);

$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    die("Assignment not found.");
}


/* =========================================================
   DELETE ASSIGNMENT
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $stmt = $pdo->prepare("
            DELETE FROM member_trainer
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        header("Location: index.php?deleted=1");
        exit;

    } catch (PDOException $e) {

        $error_message =
            "Unable to delete this trainer assignment. Please try again.";
    }
}


/* =========================================================
   LOAD HEADER
========================================================= */

require_once "../includes/header.php";

?>


<!-- =========================================================
     CSS
========================================================= -->

<link
    rel="stylesheet"
    href="/gym-management/css/member-trainer-form.css?v=4"
>


<!-- =========================================================
     PAGE
========================================================= -->

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
                Delete Assignment
            </h1>

            <p>
                Review the assignment information before deleting.
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
         ERROR MESSAGE
    ====================================================== -->

    <?php if (!empty($error_message)): ?>

        <div class="member-trainer-form-error">

            <?= htmlspecialchars($error_message) ?>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         DELETE CARD
    ====================================================== -->

    <div class="member-trainer-delete-card">


        <!-- Delete Icon -->

        <div class="member-trainer-delete-icon">
            !
        </div>


        <h2>
            Delete Trainer Assignment?
        </h2>


        <p class="member-trainer-delete-message">

            Review the assignment details below.

            <br>

            This action will permanently remove the assignment.

        </p>


        <!-- Warning -->

        <div class="member-trainer-delete-warning">

            <strong>Warning:</strong>

            Deleting this assignment cannot be undone.

        </div>


        <!-- =================================================
             ASSIGNMENT DETAILS
        ================================================== -->

        <div class="member-trainer-delete-info">


            <div class="member-trainer-delete-detail">

                <span>
                    Member
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $assignment["member_name"]
                    ) ?>
                </strong>

            </div>


            <div class="member-trainer-delete-detail">

                <span>
                    Trainer
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $assignment["trainer_name"]
                    ) ?>
                </strong>

            </div>


            <div class="member-trainer-delete-detail">

                <span>
                    Assigned Date
                </span>

                <strong>
                    <?= date(
                        "d M Y",
                        strtotime($assignment["assigned_date"])
                    ) ?>
                </strong>

            </div>


            <div class="member-trainer-delete-detail">

                <span>
                    Assignment ID
                </span>

                <strong>
                    #<?= htmlspecialchars(
                        $assignment["id"]
                    ) ?>
                </strong>

            </div>

        </div>



        <!-- =================================================
             MAIN ACTIONS
        ================================================== -->

        <div class="member-trainer-delete-actions">


            <!-- Cancel -->

            <a
                href="/gym-management/member_trainer/index.php"
                class="member-trainer-cancel-button"
            >
                Cancel
            </a>


            <!-- IMPORTANT:
                 This button ONLY opens the popup.
                 It does NOT submit the delete form.
            -->

            <button
                type="button"
                class="member-trainer-delete-button"
                id="openAssignmentDeleteButton"
            >
                Delete Assignment
            </button>

        </div>

    </div>

</div>



<!-- =========================================================
     DELETE CONFIRMATION POPUP
     
     IMPORTANT:
     Popup is hidden by default.
========================================================= -->

<div
    class="member-trainer-popup-overlay"
    id="assignmentDeletePopup"
    style="display: none;"
>


    <div class="member-trainer-delete-popup">


        <!-- Close Button -->

        <button
            type="button"
            class="member-trainer-popup-close"
            id="closeAssignmentDeleteButton"
        >
            &times;
        </button>


        <!-- Popup Icon -->

        <div class="member-trainer-popup-icon">
            !
        </div>


        <h2>
            Delete Assignment?
        </h2>


        <p>

            Are you sure you want to permanently delete the
            trainer assignment for

            <strong>
                <?= htmlspecialchars(
                    $assignment["member_name"]
                ) ?>
            </strong>

            ?

        </p>


        <!-- Warning -->

        <div class="member-trainer-popup-warning">

            This action cannot be undone.

        </div>


        <!-- =================================================
             POPUP ACTIONS
        ================================================== -->

        <div class="member-trainer-popup-actions">


            <!-- Cancel -->

            <button
                type="button"
                class="member-trainer-popup-cancel"
                id="cancelAssignmentDeleteButton"
            >
                Cancel
            </button>


            <!-- Actual Delete -->

            <form method="POST">

                <button
                    type="submit"
                    class="member-trainer-popup-delete"
                >
                    Delete Assignment
                </button>

            </form>

        </div>

    </div>

</div>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

document.addEventListener("DOMContentLoaded", function () {

    const popup =
        document.getElementById("assignmentDeletePopup");

    const openButton =
        document.getElementById("openAssignmentDeleteButton");

    const closeButton =
        document.getElementById("closeAssignmentDeleteButton");

    const cancelButton =
        document.getElementById("cancelAssignmentDeleteButton");


    /* =====================================================
       MAKE SURE POPUP IS HIDDEN WHEN PAGE LOADS
    ====================================================== */

    popup.style.display = "none";


    /* =====================================================
       OPEN POPUP ONLY AFTER CLICKING DELETE ASSIGNMENT
    ====================================================== */

    openButton.addEventListener("click", function () {

        popup.style.display = "flex";

    });


    /* =====================================================
       CLOSE POPUP
    ====================================================== */

    closeButton.addEventListener("click", function () {

        popup.style.display = "none";

    });


    /* =====================================================
       CANCEL
    ====================================================== */

    cancelButton.addEventListener("click", function () {

        popup.style.display = "none";

    });


    /* =====================================================
       CLOSE WHEN CLICKING OUTSIDE POPUP
    ====================================================== */

    popup.addEventListener("click", function (event) {

        if (event.target === popup) {

            popup.style.display = "none";

        }

    });

});

</script>