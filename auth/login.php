
<?php
session_start();

require_once __DIR__ . "/../config/database.php";

$error = "";

/* =========================================================
   LOGIN PROCESS
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Please enter your username and password.";
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT
                    id,
                    username,
                    password,
                    role,
                    must_change_password
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user["password"])) {
                $allowed_roles = ["admin", "trainer", "member"];

                if (!in_array($user["role"], $allowed_roles, true)) {
                    $error = "Invalid user role.";
                } else {
                    session_regenerate_id(true);

                    $_SESSION["user_id"] = (int) $user["id"];
                    $_SESSION["username"] = $user["username"];
                    $_SESSION["role"] = $user["role"];

                    // Preserve the existing optional password-change behavior.
                    unset($_SESSION["must_change_password"]);

                    // Redirect according to the user's role.
                    switch ($user["role"]) {
                        case "admin":
                            header("Location: ../admin/dashboard.php");
                            exit;

                        case "trainer":
                            header("Location: ../trainer_portal/dashboard.php");
                            exit;

                        case "member":
                            header("Location: ../member/dashboard.php");
                            exit;
                    }
                }
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            error_log("Login database error: " . $e->getMessage());
            $error = "Unable to sign in right now. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0B1113">

    <title>Sign In | VORTEX FITNESS</title>

    <link
        rel="stylesheet"
        href="/gym-management/css/login.css?v=11"
    >
</head>

<body>

    <!-- =====================================================
         BACKGROUND DECORATIONS
         ===================================================== -->

    <div class="gym-background" aria-hidden="true">

        <div class="background-glow glow-left"></div>
        <div class="background-glow glow-right"></div>
        <div class="background-glow glow-bottom"></div>

        <div class="background-grid"></div>

        <!-- First dumbbell -->
        <div class="gym-object dumbbell dumbbell-one">
            <div class="dumbbell-bar"></div>

            <div class="dumbbell-weight weight-left">
                <span></span>
            </div>

            <div class="dumbbell-weight weight-right">
                <span></span>
            </div>
        </div>

        <!-- Second dumbbell -->
        <div class="gym-object dumbbell dumbbell-two">
            <div class="dumbbell-bar"></div>

            <div class="dumbbell-weight weight-left">
                <span></span>
            </div>

            <div class="dumbbell-weight weight-right">
                <span></span>
            </div>
        </div>

        <!-- First weight plate -->
        <div class="weight-plate plate-one">
            <div class="plate-rim">
                <div class="plate-inner">
                    <div class="plate-hole"></div>
                </div>
            </div>
        </div>

        <!-- Second weight plate -->
        <div class="weight-plate plate-two">
            <div class="plate-rim">
                <div class="plate-inner">
                    <div class="plate-hole"></div>
                </div>
            </div>
        </div>

        <!-- Floating spheres -->
        <div class="metal-sphere sphere-one"></div>
        <div class="metal-sphere sphere-two"></div>
        <div class="metal-sphere sphere-three"></div>

        <!-- Decorative rings -->
        <div class="energy-ring ring-one">
            <span></span>
        </div>

        <div class="energy-ring ring-two">
            <span></span>
        </div>

        <!-- Ambient particles -->
        <div class="ambient-particle particle-one"></div>
        <div class="ambient-particle particle-two"></div>
        <div class="ambient-particle particle-three"></div>
        <div class="ambient-particle particle-four"></div>
        <div class="ambient-particle particle-five"></div>
        <div class="ambient-particle particle-six"></div>
        <div class="ambient-particle particle-seven"></div>
        <div class="ambient-particle particle-eight"></div>

    </div>

    <!-- =====================================================
         LOGIN PAGE
         ===================================================== -->

    <main class="login-page">

        <!-- VORTEX FITNESS BRAND -->
        <a
            href="/gym-management/index.php"
            class="brand-logo"
            aria-label="VORTEX FITNESS home"
        >
            <span class="brand-symbol" aria-hidden="true">
                <svg
                    viewBox="0 0 48 48"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M8 18V30M14 12V36M20 19V29M28 19V29M34 12V36M40 18V30M8 24H40"
                        stroke="currentColor"
                        stroke-width="4"
                        stroke-linecap="round"
                    />
                </svg>
            </span>

            <span class="brand-name">
                VORTEX<span> FITNESS</span>
            </span>
        </a>

        <!-- Tagline on the image -->
        <p class="brand-tagline">
            STRENGTH · DISCIPLINE · PROGRESS
        </p>

        <!-- LOGIN FORM PANEL -->
        <section class="login-card">

            <!-- Heading -->
            <header class="login-heading">

                <p class="heading-eyebrow">
                    YOUR NEXT REP STARTS HERE
                </p>

                <h1>Welcome Back</h1>

                <p class="heading-description">
                    Sign in to continue your fitness journey.
                </p>

            </header>

            <!-- Password changed message -->
            <?php if (isset($_GET["password_changed"])): ?>
                <div
                    class="form-message success-message"
                    role="status"
                >
                    <span class="message-symbol" aria-hidden="true">✓</span>

                    <span>
                        Password changed successfully.
                        Please sign in with your new password.
                    </span>
                </div>
            <?php endif; ?>

            <!-- Login error -->
            <?php if ($error !== ""): ?>
                <div
                    class="form-message error-message"
                    role="alert"
                >
                    <span class="message-symbol" aria-hidden="true">!</span>

                    <span>
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </span>
                </div>
            <?php endif; ?>

            <!-- Login form -->
            <form
                method="POST"
                action="/gym-management/auth/login.php"
                class="login-form"
                autocomplete="on"
            >

                <!-- Username -->
                <div class="input-group">

                    <label for="username">Username</label>

                    <div class="input-control">

                        <span class="input-icon" aria-hidden="true">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Enter your username"
                            autocomplete="username"
                            value="<?= htmlspecialchars(
                                $_POST["username"] ?? "",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            required
                        >

                    </div>
                </div>

                <!-- Password -->
                <div class="input-group">

                    <label for="password">Password</label>

                    <div class="input-control">

                        <span class="input-icon" aria-hidden="true">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <rect
                                    x="3"
                                    y="11"
                                    width="18"
                                    height="11"
                                    rx="2"
                                    ry="2"
                                ></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <!-- Password visibility toggle -->
                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword()"
                            aria-label="Show password"
                            aria-controls="password"
                        >
                            <svg
                                id="password-eye"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>

                            <span class="toggle-label">Show</span>
                        </button>

                    </div>
                </div>

                <!-- Submit button -->
                <button type="submit" class="login-submit">

                    <span>Sign In</span>

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M5 12h14"></path>
                        <path d="m12 5 7 7-7 7"></path>
                    </svg>

                </button>

                <!-- Back to home -->
                <footer class="login-footer">
                    <a
                        href="/gym-management/index.php"
                        class="back-link"
                    >
                        <span aria-hidden="true">←</span>
                        Back to Home
                    </a>
                </footer>

            </form>

        </section>

        <!-- Bottom-right caption -->
        <p class="page-caption">
            BUILT FOR YOUR FITNESS JOURNEY
        </p>

    </main>

    <!-- =====================================================
         PASSWORD VISIBILITY
         ===================================================== -->

    <script>
        function togglePassword() {
            const password = document.getElementById("password");
            const button = document.querySelector(".password-toggle");
            const label = button.querySelector(".toggle-label");
            const eye = document.getElementById("password-eye");

            if (password.type === "password") {
                password.type = "text";
                label.textContent = "Hide";
                button.setAttribute("aria-label", "Hide password");

                eye.innerHTML = `
                    <path d="M3 3l18 18"></path>
                    <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                    <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a16 16 0 0 1-3 3.9"></path>
                    <path d="M6.6 6.6C3.6 8.5 2 12 2 12s3.5 7 10 7a10 10 0 0 0 4-.8"></path>
                `;
            } else {
                password.type = "password";
                label.textContent = "Show";
                button.setAttribute("aria-label", "Show password");

                eye.innerHTML = `
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                `;
            }
        }
    </script>

</body>
</html>
