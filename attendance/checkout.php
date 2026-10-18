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
        attendance.check_in,
        attendance.check_out,
        members.full_name
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
   CHECK OUT
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $stmt = $pdo->prepare("
            UPDATE attendance
            SET check_out = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $stmt->execute([$id]);


        header("Location: index.php?checkout=1");
        exit;


    } catch (PDOException $e) {

        $error_message =
            "Unable to check out this attendance record. Please try again.";
    }
}


/* =========================================================
   LOAD HEADER
========================================================= */

require_once "../includes/header.php";

?>

<link
    rel="stylesheet"
    href="/gym-management/css/attendance-form.css?v=3"
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
                Check Out
            </h1>

            <p>
                Review the attendance information before checking out.
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
         CHECKOUT CARD
    ====================================================== -->

    <div class="attendance-checkout-card">


        <!-- Icon -->

        <div class="attendance-checkout-icon">
            ✓
        </div>


        <!-- Heading -->

        <h2>
            Check Out Member?
        </h2>


        <p class="attendance-checkout-message">

            Are you sure you want to check out this attendance record?

        </p>


        <!-- Warning -->

        <div class="attendance-checkout-info">

            <div class="attendance-checkout-detail">

                <span>
                    Member
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $attendance["full_name"]
                    ) ?>
                </strong>

            </div>


            <div class="attendance-checkout-detail">

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


            <div class="attendance-checkout-detail">

                <span>
                    Check Out
                </span>

                <strong>
                    Current Time
                </strong>

            </div>

        </div>


        <!-- Actions -->

        <div class="attendance-checkout-actions">


            <a
                href="/gym-management/attendance/index.php"
                class="attendance-cancel-button"
            >
                Cancel
            </a>


            <form method="POST">

                <button
                    type="submit"
                    class="attendance-checkout-button"
                >
                    ✓ Confirm Check Out
                </button>

            </form>


        </div>

    </div>

</div>