<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$id = $_GET["id"] ?? null;


/* =========================================================
   CHECK ATTENDANCE ID
========================================================= */

if (!$id || !is_numeric($id)) {
    die("Attendance ID is missing.");
}


/* =========================================================
   FETCH ATTENDANCE RECORD
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        attendance.id,
        members.full_name,
        attendance.check_in,
        attendance.check_out
    FROM attendance
    JOIN members
        ON attendance.member_id = members.id
    WHERE attendance.id = ?
");

$stmt->execute([$id]);

$attendance = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$attendance) {
    die("Attendance record not found.");
}


/* =========================================================
   DELETE ATTENDANCE
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $stmt = $pdo->prepare("
            DELETE FROM attendance
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        header("Location: index.php?deleted=1");
        exit;

    } catch (PDOException $e) {

        $error_message =
            "Unable to delete this attendance record. Please try again.";
    }
}


/* =========================================================
   LOAD HEADER
========================================================= */

require_once "../includes/header.php";

?>

<link
    rel="stylesheet"
    href="/gym-management/css/attendance-form.css?v=5"
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
                Delete Attendance
            </h1>

            <p>
                Review the attendance information before deleting it.
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
         DELETE CARD
    ====================================================== -->

    <div class="attendance-delete-card">


        <!-- Delete Icon -->

        <div class="attendance-delete-icon">
            !
        </div>


        <h2>
            Delete Attendance Record?
        </h2>


        <p class="attendance-delete-message">

            Review the attendance details below.

            <br>

            The record will be permanently removed after confirmation.

        </p>


        <!-- Warning -->

        <div class="attendance-delete-warning">

            <strong>Warning:</strong>

            Deleting this attendance record cannot be undone.

        </div>


        <!-- Attendance Details -->

        <div class="attendance-delete-info">


            <div class="attendance-delete-detail">

                <span>
                    Member
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $attendance["full_name"]
                    ) ?>
                </strong>

            </div>


            <div class="attendance-delete-detail">

                <span>
                    Attendance ID
                </span>

                <strong>
                    #<?= htmlspecialchars($attendance["id"]) ?>
                </strong>

            </div>


            <div class="attendance-delete-detail">

                <span>
                    Check In
                </span>

                <strong>
                    <?= date(
                        "d M Y, h:i A",
                        strtotime($attendance["check_in"])
                    ) ?>
                </strong>

            </div>


            <div class="attendance-delete-detail">

                <span>
                    Check Out
                </span>

                <strong>

                    <?php if ($attendance["check_out"]): ?>

                        <?= date(
                            "d M Y, h:i A",
                            strtotime($attendance["check_out"])
                        ) ?>

                    <?php else: ?>

                        Not Checked Out

                    <?php endif; ?>

                </strong>

            </div>

        </div>


        <!-- Main Actions -->

        <div class="attendance-delete-actions">


            <a
                href="/gym-management/attendance/index.php"
                class="attendance-cancel-button"
            >
                Cancel
            </a>


            <!-- IMPORTANT:
                 This is NOT a submit button.
                 It only opens the popup.
            -->

            <button
                type="button"
                class="attendance-delete-button"
                onclick="openAttendanceDeletePopup()"
            >
                Delete Attendance
            </button>

        </div>

    </div>

</div>



<!-- =========================================================
     DELETE CONFIRMATION POPUP
========================================================= -->

<div
    class="attendance-popup-overlay"
    id="attendanceDeletePopup"
>


    <div class="attendance-popup">


        <!-- Close -->

        <button
            type="button"
            class="attendance-popup-close"
            onclick="closeAttendanceDeletePopup()"
        >
            &times;
        </button>


        <!-- Icon -->

        <div class="attendance-popup-icon">
            !
        </div>


        <h2>
            Delete Attendance?
        </h2>


        <p>

            Are you sure you want to permanently delete the
            attendance record for

            <strong>
                <?= htmlspecialchars(
                    $attendance["full_name"]
                ) ?>
            </strong>

            ?

        </p>


        <!-- Warning -->

        <div class="attendance-popup-warning">

            This action cannot be undone.

        </div>


        <!-- Popup Actions -->

        <div class="attendance-popup-actions">


            <!-- Cancel -->

            <button
                type="button"
                class="attendance-popup-cancel"
                onclick="closeAttendanceDeletePopup()"
            >
                Cancel
            </button>


            <!-- Actual Delete Form -->

            <form method="POST">

                <button
                    type="submit"
                    class="attendance-popup-delete"
                >
                    Delete Attendance
                </button>

            </form>


        </div>

    </div>

</div>



<script>

/* =========================================================
   OPEN POPUP
========================================================= */

function openAttendanceDeletePopup() {

    document.getElementById(
        "attendanceDeletePopup"
    ).style.display = "flex";

}


/* =========================================================
   CLOSE POPUP
========================================================= */

function closeAttendanceDeletePopup() {

    document.getElementById(
        "attendanceDeletePopup"
    ).style.display = "none";

}


/* =========================================================
   CLOSE WHEN CLICKING OUTSIDE
========================================================= */

document.getElementById(
    "attendanceDeletePopup"
).addEventListener("click", function(event) {

    if (event.target === this) {

        closeAttendanceDeletePopup();

    }

});

</script>