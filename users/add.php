<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$error_message = "";


/* =========================================================
   CREATE USER
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $role = $_POST["role"] ?? "";

    $username = trim($_POST["username"] ?? "");
    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $status = $_POST["status"] ?? "active";
    $date_of_birth = $_POST["date_of_birth"] ?? "";


    try {

        /* =================================================
           BASIC VALIDATION
        ================================================= */

        if (!in_array($role, ["trainer", "member"], true)) {
            throw new Exception(
                "Please select a valid account type."
            );
        }


        if ($username === "") {
            throw new Exception(
                "Username is required."
            );
        }


        if ($full_name === "") {
            throw new Exception(
                "Full name is required."
            );
        }


        if ($date_of_birth === "") {
            throw new Exception(
                "Date of birth is required."
            );
        }


        /* =================================================
           VALIDATE DATE OF BIRTH
        ================================================= */

        $dob_object = DateTime::createFromFormat(
            "Y-m-d",
            $date_of_birth
        );


        if (
            !$dob_object ||
            $dob_object->format("Y-m-d") !== $date_of_birth
        ) {

            throw new Exception(
                "Please enter a valid date of birth."
            );
        }


        /* =================================================
           DOB CANNOT BE FUTURE
        ================================================= */

        $today = new DateTime();

        if ($dob_object > $today) {

            throw new Exception(
                "Date of birth cannot be a future date."
            );
        }


        /* =================================================
           TRAINER-SPECIFIC DATA
        ================================================= */

        $specialization = null;

        if ($role === "trainer") {

            $specialization =
                trim($_POST["specialization"] ?? "");
        }


        /* =================================================
           MEMBER-SPECIFIC DATA
        ================================================= */

        $address = null;
        $join_date = null;

        if ($role === "member") {

            $address = trim(
                $_POST["address"] ?? ""
            );

            $join_date =
                $_POST["join_date"] ?? "";

            if ($join_date === "") {

                throw new Exception(
                    "Join date is required for a member."
                );
            }
        }


        /* =================================================
           CHECK USERNAME
        ================================================= */

        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->execute([
            $username
        ]);


        if ($stmt->fetch()) {

            throw new Exception(
                "This username already exists. Please choose another username."
            );
        }


        /* =================================================
           CREATE TEMPORARY PASSWORD FROM DOB
           
           Example:
           DOB = 15-08-2005
           Password = 2005-08-15
        ================================================= */

        $temporary_password =
            $dob_object->format("Y-m-d");


        /* =================================================
           HASH PASSWORD
        ================================================= */

        $hashed_password = password_hash(
            $temporary_password,
            PASSWORD_DEFAULT
        );


        /* =================================================
           START TRANSACTION
        ================================================= */

        $pdo->beginTransaction();


        /* =================================================
           CREATE USER ACCOUNT
        ================================================= */

        $stmt = $pdo->prepare("
            INSERT INTO users
            (
                username,
                password,
                role,
                must_change_password
            )
            VALUES
            (
                ?,
                ?,
                ?,
                1
            )
        ");


        $stmt->execute([
            $username,
            $hashed_password,
            $role
        ]);


        /* =================================================
           GET USER ID
        ================================================= */

        $user_id = $pdo->lastInsertId();


        /* =================================================
           CREATE TRAINER
        ================================================= */

        if ($role === "trainer") {

            $stmt = $pdo->prepare("
                INSERT INTO trainers
                (
                    user_id,
                    full_name,
                    date_of_birth,
                    email,
                    phone,
                    specialization,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");


            $stmt->execute([
                $user_id,
                $full_name,
                $date_of_birth,
                $email !== ""
                    ? $email
                    : null,
                $phone !== ""
                    ? $phone
                    : null,
                $specialization !== ""
                    ? $specialization
                    : null,
                $status
            ]);
        }


        /* =================================================
           CREATE MEMBER
        ================================================= */

        if ($role === "member") {

            $stmt = $pdo->prepare("
                INSERT INTO members
                (
                    user_id,
                    full_name,
                    date_of_birth,
                    email,
                    phone,
                    address,
                    join_date,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");


            $stmt->execute([
                $user_id,
                $full_name,
                $date_of_birth,
                $email !== ""
                    ? $email
                    : null,
                $phone !== ""
                    ? $phone
                    : null,
                $address !== ""
                    ? $address
                    : null,
                $join_date,
                $status
            ]);
        }


        /* =================================================
           COMPLETE TRANSACTION
        ================================================= */

        $pdo->commit();


        /* =================================================
           REDIRECT
        ================================================= */

        header(
            "Location: index.php?added=1"
        );

        exit;


    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error_message =
            $e->getMessage();
    }
}


/* =========================================================
   LOAD HEADER
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
                Create User
            </h1>

            <p>
                Create a login account for a trainer or member.
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

    <?php if (!empty($error_message)): ?>

        <div class="user-form-error">

            <?= htmlspecialchars(
                $error_message
            ) ?>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         FORM CARD
    ====================================================== -->

    <div class="user-form-card">


        <div class="user-form-card-header">

            <div class="user-form-icon">
                +
            </div>

            <div>

                <h2>
                    Account Information
                </h2>

                <p>
                    Enter the login and profile details.
                </p>

            </div>

        </div>



        <!-- =================================================
             FORM
        ================================================== -->

        <form
            method="POST"
            class="user-form"
        >


            <!-- ACCOUNT TYPE -->

            <div class="user-form-group">

                <label for="role">
                    Account Type
                </label>

                <select
                    name="role"
                    id="role"
                    required
                >

                    <option value="">
                        Select Account Type
                    </option>

                    <option
                        value="trainer"
                        <?= (
                            ($_POST["role"] ?? "")
                            === "trainer"
                        )
                            ? "selected"
                            : "" ?>
                    >
                        Trainer
                    </option>

                    <option
                        value="member"
                        <?= (
                            ($_POST["role"] ?? "")
                            === "member"
                        )
                            ? "selected"
                            : "" ?>
                    >
                        Member
                    </option>

                </select>

            </div>



            <!-- USERNAME -->

            <div class="user-form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    name="username"
                    id="username"
                    maxlength="100"
                    value="<?= htmlspecialchars(
                        $_POST["username"] ?? ""
                    ) ?>"
                    required
                >

                <small>
                    This username will be used for login.
                </small>

            </div>



            <!-- DATE OF BIRTH -->

            <div class="user-form-group">

                <label for="date_of_birth">
                    Date of Birth
                </label>

                <input
                    type="date"
                    name="date_of_birth"
                    id="date_of_birth"
                    value="<?= htmlspecialchars(
                        $_POST["date_of_birth"] ?? ""
                    ) ?>"
                    required
                >

                <small>
                    This date will be used to create the initial password.
                </small>

            </div>



            <!-- INITIAL PASSWORD INFORMATION -->

            <div class="user-password-info">

                <strong>
                    Initial Password
                </strong>

                <span>
                    The Date of Birth will automatically be used
                    in YYYY-MM-DD format.
                </span>

                <code>
                    Example: 2005-08-15
                </code>

            </div>



            <!-- FULL NAME -->

            <div class="user-form-group">

                <label for="full_name">
                    Full Name
                </label>

                <input
                    type="text"
                    name="full_name"
                    id="full_name"
                    maxlength="150"
                    value="<?= htmlspecialchars(
                        $_POST["full_name"] ?? ""
                    ) ?>"
                    required
                >

            </div>



            <!-- EMAIL -->

            <div class="user-form-group">

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    id="email"
                    maxlength="150"
                    value="<?= htmlspecialchars(
                        $_POST["email"] ?? ""
                    ) ?>"
                >

            </div>



            <!-- PHONE -->

            <div class="user-form-group">

                <label for="phone">
                    Phone
                </label>

                <input
                    type="text"
                    name="phone"
                    id="phone"
                    maxlength="20"
                    value="<?= htmlspecialchars(
                        $_POST["phone"] ?? ""
                    ) ?>"
                >

            </div>



            <!-- TRAINER FIELDS -->

            <div
                class="user-form-group"
                id="trainerFields"
            >

                <label for="specialization">
                    Specialization
                </label>

                <input
                    type="text"
                    name="specialization"
                    id="specialization"
                    maxlength="150"
                    value="<?= htmlspecialchars(
                        $_POST["specialization"] ?? ""
                    ) ?>"
                    placeholder="e.g. Strength Training"
                >

            </div>



            <!-- MEMBER ADDRESS -->

            <div
                class="user-form-group"
                id="memberFields"
            >

                <label for="address">
                    Address
                </label>

                <textarea
                    name="address"
                    id="address"
                    rows="4"
                ><?= htmlspecialchars(
                    $_POST["address"] ?? ""
                ) ?></textarea>

            </div>



            <!-- MEMBER JOIN DATE -->

            <div
                class="user-form-group"
                id="memberJoinDate"
            >

                <label for="join_date">
                    Join Date
                </label>

                <input
                    type="date"
                    name="join_date"
                    id="join_date"
                    value="<?= htmlspecialchars(
                        $_POST["join_date"]
                        ?? date("Y-m-d")
                    ) ?>"
                >

            </div>



            <!-- STATUS -->

            <div class="user-form-group">

                <label for="status">
                    Status
                </label>

                <select
                    name="status"
                    id="status"
                >

                    <option
                        value="active"
                        <?= (
                            ($_POST["status"]
                            ?? "active")
                            === "active"
                        )
                            ? "selected"
                            : "" ?>
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        <?= (
                            ($_POST["status"] ?? "")
                            === "inactive"
                        )
                            ? "selected"
                            : "" ?>
                    >
                        Inactive
                    </option>

                </select>

            </div>



            <!-- ACTIONS -->

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
                    Create User
                </button>

            </div>

        </form>

    </div>

</div>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const roleSelect =
            document.getElementById("role");

        const trainerFields =
            document.getElementById(
                "trainerFields"
            );

        const memberFields =
            document.getElementById(
                "memberFields"
            );

        const memberJoinDate =
            document.getElementById(
                "memberJoinDate"
            );


        function updateFields() {

            const role =
                roleSelect.value;


            /* ============================================
               TRAINER
            ============================================= */

            if (role === "trainer") {

                trainerFields.style.display =
                    "block";

                memberFields.style.display =
                    "none";

                memberJoinDate.style.display =
                    "none";

            }


            /* ============================================
               MEMBER
            ============================================= */

            else if (role === "member") {

                trainerFields.style.display =
                    "none";

                memberFields.style.display =
                    "block";

                memberJoinDate.style.display =
                    "block";

            }


            /* ============================================
               NO ROLE
            ============================================= */

            else {

                trainerFields.style.display =
                    "none";

                memberFields.style.display =
                    "none";

                memberJoinDate.style.display =
                    "none";

            }

        }


        roleSelect.addEventListener(
            "change",
            updateFields
        );


        updateFields();

    }
);

</script>