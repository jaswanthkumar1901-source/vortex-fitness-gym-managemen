<?php

require_once "../includes/auth.php";
require_once "../config/database.php";


/* ========================================
   GET MEMBERSHIP ID
======================================== */

$id = $_GET["id"] ?? null;


if (!$id) {
    die("Invalid membership ID.");
}


/* ========================================
   FETCH MEMBERSHIP DETAILS
======================================== */

$stmt = $pdo->prepare("
    SELECT
        memberships.id,
        memberships.member_id,
        memberships.plan_id,
        memberships.start_date,
        memberships.end_date,
        memberships.status,
        members.full_name,
        membership_plans.plan_name
    FROM memberships
    INNER JOIN members
        ON memberships.member_id = members.id
    INNER JOIN membership_plans
        ON memberships.plan_id = membership_plans.id
    WHERE memberships.id = ?
");

$stmt->execute([$id]);

$membership = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$membership) {
    die("Membership not found.");
}


/* ========================================
   HANDLE DELETE
======================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $stmt = $pdo->prepare("
            DELETE FROM memberships
            WHERE id = ?
        ");

        $stmt->execute([$id]);


        /* Redirect after successful delete */

        header(
            "Location: index.php?deleted=1"
        );

        exit;

    } catch (PDOException $e) {

        $error_message =
            "Unable to delete this membership. "
            . "Please try again.";
    }
}


/* ========================================
   LOAD HEADER
======================================== */

require_once "../includes/header.php";

?>


<!-- Membership Form CSS -->

<link
    rel="stylesheet"
    href="/gym-management/css/membership-form.css?v=5"
>


<div class="membership-form-page">


    <!-- ====================================
         PAGE HEADER
    ===================================== -->

    <div class="membership-form-header">

        <div>

            <span class="membership-form-label">
                MEMBERSHIP MANAGEMENT
            </span>

            <h1>
                Delete Membership
            </h1>

            <p>
                Review the membership information before deleting.
            </p>

        </div>


        <a
            href="/gym-management/membership/index.php"
            class="membership-back-button"
        >
            ← Back to Memberships
        </a>

    </div>



    <!-- ====================================
         ERROR MESSAGE
    ===================================== -->

    <?php if (!empty($error_message)): ?>

        <div class="membership-delete-error">

            <?= htmlspecialchars($error_message) ?>

        </div>

    <?php endif; ?>



    <!-- ====================================
         DELETE CARD
    ===================================== -->

    <div class="membership-delete-card">


        <!-- DELETE ICON -->

        <div class="membership-delete-icon">
            !
        </div>


        <!-- TITLE -->

        <h2>
            Delete Membership?
        </h2>


        <!-- MESSAGE -->

        <p class="membership-delete-message">

            Are you sure you want to delete this membership?

        </p>



        <!-- ====================================
             WARNING
        ===================================== -->

        <div class="membership-delete-warning">

            <strong>Warning:</strong>

            This action cannot be undone.

        </div>



        <!-- ====================================
             MEMBERSHIP DETAILS
        ===================================== -->

        <div class="membership-delete-info">


            <!-- MEMBER -->

            <div class="membership-delete-detail">

                <span>
                    Member
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $membership["full_name"]
                    ) ?>
                </strong>

            </div>


            <!-- PLAN -->

            <div class="membership-delete-detail">

                <span>
                    Membership Plan
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $membership["plan_name"]
                    ) ?>
                </strong>

            </div>


            <!-- START DATE -->

            <div class="membership-delete-detail">

                <span>
                    Start Date
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $membership["start_date"]
                    ) ?>
                </strong>

            </div>


            <!-- END DATE -->

            <div class="membership-delete-detail">

                <span>
                    End Date
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $membership["end_date"]
                    ) ?>
                </strong>

            </div>


            <!-- STATUS -->

            <div class="membership-delete-detail">

                <span>
                    Status
                </span>

                <strong>
                    <?= htmlspecialchars(
                        ucfirst(
                            $membership["status"]
                        )
                    ) ?>
                </strong>

            </div>


        </div>



        <!-- ====================================
             ACTION BUTTONS
        ===================================== -->

        <form
            method="POST"
            class="membership-delete-actions"
        >


            <a
                href="/gym-management/membership/index.php"
                class="membership-cancel-button"
            >
                Cancel
            </a>


            <button
                type="submit"
                class="membership-delete-button"
                onclick="
                    return confirm(
                        'Are you sure you want to permanently delete this membership?'
                    );
                "
            >
                Delete Membership
            </button>


        </form>


    </div>

</div>