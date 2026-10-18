<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$id = $_GET["id"] ?? null;


/* =========================================================
   FETCH EXISTING ATTENDANCE
========================================================= */

$stmt = $pdo->prepare(
    "SELECT *
     FROM attendance
     WHERE id = ?"
);

$stmt->execute([$id]);

$attendance = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$attendance) {
    die("Attendance record not found.");
}


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
   UPDATE ATTENDANCE
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $member_id = $_POST["member_id"];
        $check_in = $_POST["check_in"];
        $check_out = $_POST["check_out"];


        /* Convert empty checkout to NULL */

        if ($check_out === "") {
            $check_out = null;
        }


        /* Update attendance */

        $stmt = $pdo->prepare(
            "UPDATE attendance
             SET member_id = ?,
                 check_in = ?,
                 check_out = ?
             WHERE id = ?"
        );

        $stmt->execute([
            $member_id,
            $check_in,
            $check_out,
            $id
        ]);


        header("Location: index.php?updated=1");
        exit;


    } catch (PDOException $e) {

        $error_message =
            "Unable to update attendance. Please try again.";
    }
}


/* =========================================================
   LOAD HEADER
========================================================= */

require_once "../includes/header.php";

?>

<link
    rel="stylesheet"
    href="/gym-management/css/attendance-form.css?v=2"
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
                Edit Attendance
            </h1>

            <p>
                Update the member attendance details.
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
                ✎
            </div>

            <div>

                <h2>
                    Attendance Details
                </h2>

                <p>
                    Modify the check-in and check-out information.
                </p>

            </div>

        </div>



        <!-- Form -->

        <form
            method="POST"
            class="attendance-form"
        >


            <!-- =================================================
                 MEMBER
            ================================================== -->

            <div class="attendance-form-group">

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
                            <?= (
                                $member["id"]
                                == $attendance["member_id"]
                            ) ? "selected" : "" ?>
                        >

                            <?= htmlspecialchars(
                                $member["full_name"]
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <small class="attendance-help-text">
                    Select the active member associated with this attendance record.
                </small>

            </div>



            <!-- =================================================
                 CHECK IN
            ================================================== -->

            <div class="attendance-form-group">

                <label for="check_in">
                    Check In
                </label>


                <input
                    type="datetime-local"
                    name="check_in"
                    id="check_in"
                    value="<?= date(
                        'Y-m-d\TH:i',
                        strtotime($attendance['check_in'])
                    ) ?>"
                    required
                >

            </div>



            <!-- =================================================
                 CHECK OUT
            ================================================== -->

            <div class="attendance-form-group">

                <label for="check_out">
                    Check Out
                </label>


                <input
                    type="datetime-local"
                    name="check_out"
                    id="check_out"
                    value="<?= $attendance['check_out']
                        ? date(
                            'Y-m-d\TH:i',
                            strtotime($attendance['check_out'])
                        )
                        : '' ?>"
                >


                <small class="attendance-help-text">
                    Leave this empty if the member has not checked out yet.
                </small>

            </div>



            <!-- =================================================
                 ACTIONS
            ================================================== -->

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
                    Update Attendance
                </button>

            </div>


        </form>

    </div>

</div>