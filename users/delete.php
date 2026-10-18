<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

/* =========================================================
   ADMIN ONLY
========================================================= */

if (($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

/* =========================================================
   GET USER ID
========================================================= */

$id = isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;

if ($id <= 0) {
    die("Invalid user ID.");
}

/* =========================================================
   PREVENT SELF DELETE
========================================================= */

$currentUserId = (int)(
    $_SESSION["user_id"] ?? 0
);

if ($id === $currentUserId) {
    die("You cannot delete your own account.");
}

/* =========================================================
   FETCH USER
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        id,
        username,
        role,
        created_at
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}


/* =========================================================
   FETCH PROFILE
========================================================= */

$profile = null;

if ($user["role"] === "member") {

    $stmt = $pdo->prepare("
        SELECT
            id,
            full_name,
            email,
            phone,
            address,
            join_date,
            status
        FROM members
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$id]);

    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

}


elseif ($user["role"] === "trainer") {

    $stmt = $pdo->prepare("
        SELECT
            id,
            full_name,
            specialization,
            phone,
            email,
            status
        FROM trainers
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$id]);

    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

}


/* =========================================================
   DELETE
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $pdo->beginTransaction();


        /* =================================================
           MEMBER
        ================================================== */

        if ($user["role"] === "member" && $profile) {

            /*
             * Check whether this member has memberships.
             * We don't delete historical membership records.
             */

            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM memberships
                WHERE member_id = ?
            ");

            $stmt->execute([
                $profile["id"]
            ]);

            $membershipCount = (int)$stmt->fetchColumn();

            if ($membershipCount > 0) {

                $pdo->rollBack();

                header(
                    "Location: index.php?error=has_records"
                );

                exit;
            }


            /*
             * Remove member-trainer assignments.
             */

            $stmt = $pdo->prepare("
                DELETE FROM member_trainer
                WHERE member_id = ?
            ");

            $stmt->execute([
                $profile["id"]
            ]);


            /*
             * Delete member profile.
             */

            $stmt = $pdo->prepare("
                DELETE FROM members
                WHERE user_id = ?
            ");

            $stmt->execute([$id]);
        }


        /* =================================================
           TRAINER
        ================================================== */

        if ($user["role"] === "trainer" && $profile) {

            /*
             * Remove trainer assignments first.
             */

            $stmt = $pdo->prepare("
                DELETE FROM member_trainer
                WHERE trainer_id = ?
            ");

            $stmt->execute([
                $profile["id"]
            ]);


            /*
             * Delete trainer profile.
             */

            $stmt = $pdo->prepare("
                DELETE FROM trainers
                WHERE user_id = ?
            ");

            $stmt->execute([$id]);
        }


        /* =================================================
           DELETE USER ACCOUNT
        ================================================== */

        $stmt = $pdo->prepare("
            DELETE FROM users
            WHERE id = ?
        ");

        $stmt->execute([$id]);


        if ($stmt->rowCount() !== 1) {

            throw new Exception(
                "User account could not be deleted."
            );
        }


        /* =================================================
           COMMIT
        ================================================== */

        $pdo->commit();


        /* =================================================
           REDIRECT
        ================================================== */

        header(
            "Location: index.php?deleted=1"
        );

        exit;


    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error_message =
            "Unable to delete this user account.";
    }

}


/* =========================================================
   HEADER
========================================================= */

require_once "../includes/header.php";

?>

<link
    rel="stylesheet"
    href="/gym-management/css/user-delete.css?v=1"
>


<div class="user-delete-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="user-delete-header">

        <div>

            <span class="user-delete-label">
                ACCOUNT MANAGEMENT
            </span>

            <h1>
                Delete User
            </h1>

            <p>
                Review the user information before deleting.
            </p>

        </div>


        <a
            href="/gym-management/users/index.php"
            class="user-delete-back-button"
        >
            ← Back to Users
        </a>

    </div>


    <!-- =====================================================
         ERROR
    ====================================================== -->

    <?php if (!empty($error_message)): ?>

        <div class="user-delete-error">

            <?= htmlspecialchars(
                $error_message,
                ENT_QUOTES,
                "UTF-8"
            ) ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         DELETE CARD
    ====================================================== -->

    <div class="user-delete-card">


        <!-- ICON -->

        <div class="user-delete-icon">
            !
        </div>


        <!-- TITLE -->

        <h2>
            Delete User?
        </h2>


        <!-- MESSAGE -->

        <p class="user-delete-message">

            Are you sure you want to delete this
            user account?

        </p>


        <!-- WARNING -->

        <div class="user-delete-warning">

            <strong>
                Warning:
            </strong>

            This action cannot be undone.

        </div>


        <!-- =================================================
             USER INFORMATION
        ================================================== -->

        <div class="user-delete-info">


            <!-- USERNAME -->

            <div class="user-delete-detail">

                <span>
                    Username
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $user["username"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </strong>

            </div>


            <!-- ROLE -->

            <div class="user-delete-detail">

                <span>
                    Role
                </span>

                <strong>
                    <?= htmlspecialchars(
                        ucfirst($user["role"]),
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </strong>

            </div>


            <!-- CREATED DATE -->

            <div class="user-delete-detail">

                <span>
                    Created Date
                </span>

                <strong>

                    <?php

                    if (!empty($user["created_at"])) {

                        echo date(
                            "d M Y",
                            strtotime(
                                $user["created_at"]
                            )
                        );

                    } else {

                        echo "—";
                    }

                    ?>

                </strong>

            </div>


            <?php if ($profile): ?>


                <!-- FULL NAME -->

                <div class="user-delete-detail">

                    <span>
                        Full Name
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            $profile["full_name"] ?? "—",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </strong>

                </div>


                <!-- EMAIL -->

                <div class="user-delete-detail">

                    <span>
                        Email
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            $profile["email"] ?? "—",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </strong>

                </div>


                <!-- PHONE -->

                <div class="user-delete-detail">

                    <span>
                        Phone
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            $profile["phone"] ?? "—",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </strong>

                </div>


                <!-- STATUS -->

                <div class="user-delete-detail">

                    <span>
                        Status
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            ucfirst(
                                $profile["status"] ?? "—"
                            ),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </strong>

                </div>


                <?php if ($user["role"] === "trainer"): ?>


                    <!-- SPECIALIZATION -->

                    <div class="user-delete-detail">

                        <span>
                            Specialization
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $profile["specialization"] ?? "—",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </strong>

                    </div>


                <?php endif; ?>


            <?php endif; ?>


        </div>


        <!-- =================================================
             ACTIONS
        ================================================== -->

        <form
            method="POST"
            class="user-delete-actions"
        >

            <a
                href="/gym-management/users/index.php"
                class="user-cancel-button"
            >
                Cancel
            </a>


            <button
                type="submit"
                class="user-delete-confirm-button"
            >
                Delete User
            </button>

        </form>


    </div>

</div>