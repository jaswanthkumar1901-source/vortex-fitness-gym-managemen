<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$id = $_GET["id"] ?? null;


/* =========================================================
   CHECK TRAINER ID
========================================================= */

if (!$id || !is_numeric($id)) {
    die("Trainer ID is missing.");
}


/* =========================================================
   FETCH TRAINER
========================================================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM trainers
    WHERE id = ?
");

$stmt->execute([$id]);

$trainer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trainer) {
    die("Trainer not found.");
}


/* =========================================================
   DELETE TRAINER
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $stmt = $pdo->prepare("
            DELETE FROM trainers
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        header("Location: index.php?deleted=1");
        exit;

    } catch (PDOException $e) {

        $error_message =
            "Unable to delete this trainer. Please try again.";
    }
}


/* =========================================================
   LOAD HEADER
========================================================= */

require_once "../includes/header.php";

?>


<link
    rel="stylesheet"
    href="/gym-management/css/trainer-form.css?v=3"
>


<div class="trainer-form-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="trainer-form-header">

        <div>

            <span class="trainer-form-label">
                TRAINER MANAGEMENT
            </span>

            <h1>
                Delete Trainer
            </h1>

            <p>
                Review the trainer information before deleting.
            </p>

        </div>


        <a
            href="/gym-management/trainer/index.php"
            class="trainer-back-button"
        >
            ← Back to Trainers
        </a>

    </div>



    <!-- =====================================================
         ERROR MESSAGE
    ====================================================== -->

    <?php if (!empty($error_message)): ?>

        <div class="trainer-form-error">

            <?= htmlspecialchars($error_message) ?>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         DELETE CARD
    ====================================================== -->

    <div class="trainer-delete-card">


        <!-- Delete Icon -->

        <div class="trainer-delete-icon">
            !
        </div>


        <h2>
            Delete Trainer?
        </h2>


        <p class="trainer-delete-message">

            Review the trainer details below before continuing.

            <br>

            This action will permanently remove the trainer.

        </p>


        <!-- Warning -->

        <div class="trainer-delete-warning">

            <strong>Warning:</strong>

            Deleting this trainer cannot be undone.

        </div>


        <!-- Trainer Information -->

        <div class="trainer-delete-info">


            <div class="trainer-delete-detail">

                <span>
                    Name
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $trainer["full_name"]
                    ) ?>
                </strong>

            </div>


            <div class="trainer-delete-detail">

                <span>
                    Status
                </span>

                <strong>
                    <?= htmlspecialchars(
                        ucfirst($trainer["status"])
                    ) ?>
                </strong>

            </div>


            <div class="trainer-delete-detail">

                <span>
                    Email
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $trainer["email"] ?? "Not Provided"
                    ) ?>
                </strong>

            </div>


            <div class="trainer-delete-detail">

                <span>
                    Phone
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $trainer["phone"] ?? "Not Provided"
                    ) ?>
                </strong>

            </div>


            <div class="trainer-delete-detail">

                <span>
                    Specialization
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $trainer["specialization"] ?? "Not Provided"
                    ) ?>
                </strong>

            </div>


            <div class="trainer-delete-detail">

                <span>
                    Trainer ID
                </span>

                <strong>
                    #<?= htmlspecialchars($trainer["id"]) ?>
                </strong>

            </div>

        </div>


        <!-- Main Actions -->

        <div class="trainer-delete-actions">


            <a
                href="/gym-management/trainer/index.php"
                class="trainer-cancel-button"
            >
                Cancel
            </a>


            <!-- Opens custom popup -->

            <button
                type="button"
                class="trainer-delete-button"
                onclick="openTrainerDeletePopup()"
            >
                Delete Trainer
            </button>

        </div>

    </div>

</div>



<!-- =========================================================
     DELETE CONFIRMATION POPUP
========================================================= -->

<div
    class="trainer-popup-overlay"
    id="trainerDeletePopup"
>


    <div class="trainer-popup">


        <!-- Close -->

        <button
            type="button"
            class="trainer-popup-close"
            onclick="closeTrainerDeletePopup()"
        >
            &times;
        </button>


        <!-- Icon -->

        <div class="trainer-popup-icon">
            !
        </div>


        <h2>
            Delete Trainer?
        </h2>


        <p>

            Are you sure you want to permanently delete

            <strong>
                <?= htmlspecialchars(
                    $trainer["full_name"]
                ) ?>
            </strong>

            ?

        </p>


        <!-- Warning -->

        <div class="trainer-popup-warning">

            This action cannot be undone.

        </div>


        <!-- Popup Actions -->

        <div class="trainer-popup-actions">


            <button
                type="button"
                class="trainer-popup-cancel"
                onclick="closeTrainerDeletePopup()"
            >
                Cancel
            </button>


            <!-- Actual Delete -->

            <form method="POST">

                <button
                    type="submit"
                    class="trainer-popup-delete"
                >
                    Delete Trainer
                </button>

            </form>


        </div>

    </div>

</div>



<script>

/* =========================================================
   OPEN POPUP
========================================================= */

function openTrainerDeletePopup() {

    document.getElementById(
        "trainerDeletePopup"
    ).style.display = "flex";

}


/* =========================================================
   CLOSE POPUP
========================================================= */

function closeTrainerDeletePopup() {

    document.getElementById(
        "trainerDeletePopup"
    ).style.display = "none";

}


/* =========================================================
   CLOSE WHEN CLICKING OUTSIDE
========================================================= */

document.getElementById(
    "trainerDeletePopup"
).addEventListener("click", function(event) {

    if (event.target === this) {

        closeTrainerDeletePopup();

    }

});

</script>   