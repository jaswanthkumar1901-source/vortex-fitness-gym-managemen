<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

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

$user_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($user_id <= 0) {
    header("Location: index.php");
    exit;
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

$stmt->execute([$user_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: index.php");
    exit;
}

/* =========================================================
   VARIABLES
========================================================= */

$error = "";

$username = $user["username"];
$role = strtolower($user["role"]);

/* Member fields */
$member_id = null;
$full_name = "";
$email = "";
$phone = "";
$address = "";
$join_date = "";
$member_status = "active";

/* Trainer fields */
$trainer_id = null;
$trainer_name = "";
$specialization = "";
$trainer_phone = "";
$trainer_email = "";
$trainer_status = "active";

/* =========================================================
   FETCH RELATED PROFILE
========================================================= */

if ($role === "member") {

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

    $stmt->execute([$user_id]);

    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($member) {

        $member_id = $member["id"];
        $full_name = $member["full_name"];
        $email = $member["email"];
        $phone = $member["phone"];
        $address = $member["address"];
        $join_date = $member["join_date"];
        $member_status = $member["status"];
    }

} elseif ($role === "trainer") {

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

    $stmt->execute([$user_id]);

    $trainer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($trainer) {

        $trainer_id = $trainer["id"];
        $trainer_name = $trainer["full_name"];
        $specialization = $trainer["specialization"];
        $trainer_phone = $trainer["phone"];
        $trainer_email = $trainer["email"];
        $trainer_status = $trainer["status"];
    }
}

/* =========================================================
   FORM SUBMISSION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $new_role = strtolower(trim($_POST["role"] ?? ""));

    /* Member fields */
    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $join_date = trim($_POST["join_date"] ?? "");
    $member_status = trim($_POST["member_status"] ?? "active");

    /* Trainer fields */
    $trainer_name = trim($_POST["trainer_name"] ?? "");
    $specialization = trim($_POST["specialization"] ?? "");
    $trainer_phone = trim($_POST["trainer_phone"] ?? "");
    $trainer_email = trim($_POST["trainer_email"] ?? "");
    $trainer_status = trim($_POST["trainer_status"] ?? "active");

    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($username === "") {

        $error = "Username is required.";

    } elseif (!in_array($new_role, ["admin", "trainer", "member"], true)) {

        $error = "Invalid user role.";

    } elseif (
        $new_role === "member"
        && $full_name === ""
    ) {

        $error = "Member full name is required.";

    } elseif (
        $new_role === "trainer"
        && $trainer_name === ""
    ) {

        $error = "Trainer full name is required.";
    }

    /* =====================================================
       CHECK DUPLICATE USERNAME
    ===================================================== */

    if ($error === "") {

        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE username = ?
            AND id != ?
            LIMIT 1
        ");

        $stmt->execute([
            $username,
            $user_id
        ]);

        if ($stmt->fetch()) {
            $error = "Username already exists.";
        }
    }

    /* =====================================================
       UPDATE
    ===================================================== */

    if ($error === "") {

        try {

            $pdo->beginTransaction();

            /* =============================================
               UPDATE USER ACCOUNT
            ============================================= */

            $stmt = $pdo->prepare("
                UPDATE users
                SET
                    username = ?,
                    role = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $username,
                $new_role,
                $user_id
            ]);

            /* =============================================
               ROLE = MEMBER
            ============================================= */

            if ($new_role === "member") {

                /* Check existing member profile */

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM members
                    WHERE user_id = ?
                    LIMIT 1
                ");

                $stmt->execute([$user_id]);

                $existing_member = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing_member) {

                    $stmt = $pdo->prepare("
                        UPDATE members
                        SET
                            full_name = ?,
                            email = ?,
                            phone = ?,
                            address = ?,
                            join_date = ?,
                            status = ?
                        WHERE user_id = ?
                    ");

                    $stmt->execute([
                        $full_name,
                        $email,
                        $phone,
                        $address,
                        $join_date !== "" ? $join_date : null,
                        $member_status,
                        $user_id
                    ]);

                } else {

                    $stmt = $pdo->prepare("
                        INSERT INTO members
                        (
                            user_id,
                            full_name,
                            email,
                            phone,
                            address,
                            join_date,
                            status
                        )
                        VALUES
                        (?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $user_id,
                        $full_name,
                        $email,
                        $phone,
                        $address,
                        $join_date !== "" ? $join_date : null,
                        $member_status
                    ]);
                }

                /*
                 * Remove trainer profile only if this account
                 * was previously a trainer.
                 */
                $stmt = $pdo->prepare("
                    DELETE FROM trainers
                    WHERE user_id = ?
                ");

                $stmt->execute([$user_id]);
            }

            /* =============================================
               ROLE = TRAINER
            ============================================= */

            elseif ($new_role === "trainer") {

                /* Check existing trainer profile */

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM trainers
                    WHERE user_id = ?
                    LIMIT 1
                ");

                $stmt->execute([$user_id]);

                $existing_trainer = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing_trainer) {

                    $stmt = $pdo->prepare("
                        UPDATE trainers
                        SET
                            full_name = ?,
                            specialization = ?,
                            phone = ?,
                            email = ?,
                            status = ?
                        WHERE user_id = ?
                    ");

                    $stmt->execute([
                        $trainer_name,
                        $specialization,
                        $trainer_phone,
                        $trainer_email,
                        $trainer_status,
                        $user_id
                    ]);

                } else {

                    $stmt = $pdo->prepare("
                        INSERT INTO trainers
                        (
                            user_id,
                            full_name,
                            specialization,
                            phone,
                            email,
                            status
                        )
                        VALUES
                        (?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $user_id,
                        $trainer_name,
                        $specialization,
                        $trainer_phone,
                        $trainer_email,
                        $trainer_status
                    ]);
                }

                /*
                 * Remove member profile only if this account
                 * was previously a member.
                 */
                $stmt = $pdo->prepare("
                    DELETE FROM members
                    WHERE user_id = ?
                ");

                $stmt->execute([$user_id]);
            }

            /* =============================================
               ROLE = ADMIN
            ============================================= */

            elseif ($new_role === "admin") {

                /*
                 * Admin accounts do not require a member
                 * or trainer profile.
                 */
                $stmt = $pdo->prepare("
                    DELETE FROM members
                    WHERE user_id = ?
                ");

                $stmt->execute([$user_id]);

                $stmt = $pdo->prepare("
                    DELETE FROM trainers
                    WHERE user_id = ?
                ");

                $stmt->execute([$user_id]);
            }

            $pdo->commit();

            header("Location: index.php?updated=1");
            exit;

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = "Unable to update user. Please try again.";
        }
    }

    /*
     * Update current role variable so the correct
     * conditional fields remain visible after an error.
     */
    $role = $new_role;
}

/* =========================================================
   HEADER
========================================================= */

require_once "../includes/header.php";

?>

<link
    rel="stylesheet"
    href="/gym-management/css/user-form.css?v=2"
>

<div class="user-form-page">

    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="user-form-header">

        <div>

            <span class="user-form-label">
                ACCOUNT MANAGEMENT
            </span>

            <h1>
                Edit User
            </h1>

            <p>
                Update login account and profile information.
            </p>

        </div>

        <a
            href="index.php"
            class="user-back-button"
        >
            ← Back to Users
        </a>

    </div>


    <!-- =====================================================
         ERROR
    ====================================================== -->

    <?php if ($error !== ""): ?>

        <div class="user-form-error">
            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                "UTF-8"
            ) ?>
        </div>

    <?php endif; ?>


    <!-- =====================================================
         FORM CARD
    ====================================================== -->

    <div class="user-form-card">

        <div class="user-form-card-header">

            <div class="user-form-icon">
                ✎
            </div>

            <div>

                <h2>
                    User Information
                </h2>

                <p>
                    Update the selected login account.
                </p>

            </div>

        </div>


        <form
            method="POST"
            class="user-form"
        >

            <!-- =================================================
                 USERNAME
            ================================================== -->

            <div class="user-form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?= htmlspecialchars(
                        $username,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required
                >

                <small>
                    This username is used for login.
                </small>

            </div>


            <!-- =================================================
                 ROLE
            ================================================== -->

            <div class="user-form-group">

                <label for="role">
                    Role
                </label>

                <select
                    id="role"
                    name="role"
                    onchange="toggleUserFields()"
                    required
                >

                    <option
                        value="admin"
                        <?= $role === "admin" ? "selected" : "" ?>
                    >
                        Admin
                    </option>

                    <option
                        value="trainer"
                        <?= $role === "trainer" ? "selected" : "" ?>
                    >
                        Trainer
                    </option>

                    <option
                        value="member"
                        <?= $role === "member" ? "selected" : "" ?>
                    >
                        Member
                    </option>

                </select>

            </div>


            <!-- =================================================
                 MEMBER FIELDS
            ================================================== -->

            <div
                id="memberFields"
                style="<?= $role === "member"
                    ? "display:block;"
                    : "display:none;" ?>"
            >

                <div class="user-form-group">

                    <label for="full_name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?= htmlspecialchars(
                            $full_name,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >

                </div>


                <div class="user-form-group">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars(
                            $email,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >

                </div>


                <div class="user-form-group">

                    <label for="phone">
                        Phone
                    </label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?= htmlspecialchars(
                            $phone,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >

                </div>


                <div class="user-form-group">

                    <label for="address">
                        Address
                    </label>

                    <textarea
                        id="address"
                        name="address"
                    ><?= htmlspecialchars(
                        $address,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?></textarea>

                </div>


                <div class="user-form-group">

                    <label for="join_date">
                        Join Date
                    </label>

                    <input
                        type="date"
                        id="join_date"
                        name="join_date"
                        value="<?= htmlspecialchars(
                            $join_date,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >

                </div>


                <div class="user-form-group">

                    <label for="member_status">
                        Status
                    </label>

                    <select
                        id="member_status"
                        name="member_status"
                    >

                        <option
                            value="active"
                            <?= $member_status === "active"
                                ? "selected"
                                : "" ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?= $member_status === "inactive"
                                ? "selected"
                                : "" ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>

            </div>


            <!-- =================================================
                 TRAINER FIELDS
            ================================================== -->

            <div
                id="trainerFields"
                style="<?= $role === "trainer"
                    ? "display:block;"
                    : "display:none;" ?>"
            >

                <div class="user-form-group">

                    <label for="trainer_name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="trainer_name"
                        name="trainer_name"
                        value="<?= htmlspecialchars(
                            $trainer_name,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >

                </div>


                <div class="user-form-group">

                    <label for="specialization">
                        Specialization
                    </label>

                    <input
                        type="text"
                        id="specialization"
                        name="specialization"
                        value="<?= htmlspecialchars(
                            $specialization,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >

                </div>


                <div class="user-form-group">

                    <label for="trainer_phone">
                        Phone
                    </label>

                    <input
                        type="tel"
                        id="trainer_phone"
                        name="trainer_phone"
                        value="<?= htmlspecialchars(
                            $trainer_phone,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >

                </div>


                <div class="user-form-group">

                    <label for="trainer_email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="trainer_email"
                        name="trainer_email"
                        value="<?= htmlspecialchars(
                            $trainer_email,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >

                </div>


                <div class="user-form-group">

                    <label for="trainer_status">
                        Status
                    </label>

                    <select
                        id="trainer_status"
                        name="trainer_status"
                    >

                        <option
                            value="active"
                            <?= $trainer_status === "active"
                                ? "selected"
                                : "" ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?= $trainer_status === "inactive"
                                ? "selected"
                                : "" ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>

            </div>


            <!-- =================================================
                 PASSWORD INFORMATION
            ================================================== -->

            <div class="user-password-info">

                <strong>
                    Password
                </strong>

                <span>
                    The existing password will not be changed when
                    updating this account.
                </span>

                <code>
                    Password remains unchanged
                </code>

            </div>


            <!-- =================================================
                 ACTIONS
            ================================================== -->

            <div class="user-form-actions">

                <a
                    href="index.php"
                    class="user-cancel-button"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="user-submit-button"
                >
                    Update User
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =========================================================
     ROLE FIELD JAVASCRIPT
========================================================= -->

<script>

function toggleUserFields() {

    const role =
        document.getElementById("role").value;

    const memberFields =
        document.getElementById("memberFields");

    const trainerFields =
        document.getElementById("trainerFields");

    if (role === "member") {

        memberFields.style.display = "block";
        trainerFields.style.display = "none";

    } else if (role === "trainer") {

        memberFields.style.display = "none";
        trainerFields.style.display = "block";

    } else {

        memberFields.style.display = "none";
        trainerFields.style.display = "none";
    }
}

document.addEventListener(
    "DOMContentLoaded",
    toggleUserFields
);

</script>