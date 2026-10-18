
<?php

session_start();

require_once "../config/database.php";

/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

$error = "";
$success = "";

/* =========================================================
   VERIFY PASSWORD CHANGE IS REQUIRED
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        id,
        username,
        role,
        must_change_password
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}

/* =========================================================
   HANDLE FORM SUBMISSION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $current_password = $_POST["current_password"] ?? "";
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        $current_password === "" ||
        $new_password === "" ||
        $confirm_password === ""
    ) {
        $error = "Please fill in all password fields.";

    } elseif (strlen($new_password) < 8) {
        $error = "Your new password must contain at least 8 characters.";

    } elseif ($new_password !== $confirm_password) {
        $error = "The new passwords do not match.";

    } else {

        /* Verify current password */

        $stmt = $pdo->prepare("
            SELECT password
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$user_id]);

        $password_hash = $stmt->fetchColumn();

        if (
            !$password_hash ||
            !password_verify($current_password, $password_hash)
        ) {
            $error = "Your current password is incorrect.";

        } else {

            try {

                $new_hash = password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET
                        password = ?,
                        must_change_password = 0
                    WHERE id = ?
                ");

                $stmt->execute([
                    $new_hash,
                    $user_id
                ]);

                unset($_SESSION["must_change_password"]);

                session_regenerate_id(true);

                /* Redirect according to role */

                if ($user["role"] === "admin") {
                    header("Location: ../admin/dashboard.php");
                    exit;
                }

                if ($user["role"] === "trainer") {
                    header("Location: ../trainer_portal/dashboard.php");
                    exit;
                }

                if ($user["role"] === "member") {
                    header("Location: ../member/dashboard.php");
                    exit;
                }

                $error = "Invalid user role.";

            } catch (PDOException $e) {
                $error = "Unable to update your password. Please try again.";
            }
        }
    }
}

/* =========================================================
   LOAD HEADER HTML
========================================================= */

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Change Password | GYMFIT</title>

    <link
        rel="stylesheet"
        href="/gym-management/css/login.css"
    >

    <style>
        .change-password-wrapper {
            width: 100%;
            max-width: 460px;
            margin: 0 auto;
            padding: 30px 24px;
            box-sizing: border-box;
        }

        .change-password-card {
            padding: 32px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 10px 35px rgba(15, 23, 42, .08);
        }

        .change-password-card h1 {
            margin: 0 0 10px;
            color: #0f172a;
            font-size: 25px;
        }

        .change-password-card p {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }

        .change-password-card .input-group {
            margin-top: 20px;
        }

        .change-password-card input {
            width: 100%;
            box-sizing: border-box;
        }

        .change-password-submit {
            width: 100%;
            margin-top: 24px;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        .change-password-submit:hover {
            background: #1d4ed8;
        }

        .change-password-error {
            margin-top: 18px;
            padding: 12px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            color: #b91c1c;
            font-size: 13px;
        }

        .change-password-user {
            margin-bottom: 20px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
            color: #334155;
            font-size: 13px;
        }

        @media (max-width: 480px) {
            .change-password-card {
                padding: 24px 18px;
            }
        }
    </style>
</head>

<body>

<div class="change-password-wrapper">

    <div class="change-password-card">

        <h1>Change Your Password</h1>

        <p>
            For account security, please set a new password
            before continuing to GYMFIT.
        </p>

        <div class="change-password-user">
            Signed in as:
            <strong>
                <?= htmlspecialchars(
                    $user["username"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </strong>
        </div>

        <?php if ($error !== ""): ?>

            <div class="change-password-error">
                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="input-group">
                <label for="current_password">
                    Current Password
                </label>

                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <div class="input-group">
                <label for="new_password">
                    New Password
                </label>

                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <div class="input-group">
                <label for="confirm_password">
                    Confirm New Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button
                type="submit"
                class="change-password-submit"
            >
                Update Password
            </button>

        </form>

    </div>

</div>

</body>
</html>
