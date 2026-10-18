
<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/database.php";

/* ========================================
   HANDLE FORM SUBMISSION
======================================== */

$error_message = "";

$full_name = "";
$dob = "";
$email = "";
$phone = "";
$address = "";
$join_date = date("Y-m-d");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $dob = trim($_POST["dob"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $join_date = trim($_POST["join_date"] ?? "");

    // Validate required fields.
    if (
        $full_name === "" ||
        $email === "" ||
        $phone === "" ||
        $join_date === ""
    ) {
        $error_message = "Please fill in all required fields.";

    } elseif (mb_strlen($full_name) > 150) {
        $error_message = "Full name must not exceed 150 characters.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";

    } elseif (strlen($email) > 254) {
        $error_message = "Email address is too long.";

    } elseif (strlen($phone) > 20) {
        $error_message = "Phone number must not exceed 20 characters.";

    } else {

        // Validate dates strictly in YYYY-MM-DD format.
        $validDate = static function ($date) {
            if ($date === "") {
                return false;
            }

            $parsed = DateTime::createFromFormat("!Y-m-d", $date);
            $errors = DateTime::getLastErrors();

            return $parsed !== false
                && ($errors === false ||
                    ($errors["warning_count"] === 0 &&
                     $errors["error_count"] === 0))
                && $parsed->format("Y-m-d") === $date;
        };

        if (
            !$validDate($join_date) ||
            ($dob !== "" && !$validDate($dob))
        ) {
            $error_message = "Please enter valid dates.";

        } elseif (
            $dob !== "" &&
            $dob > date("Y-m-d")
        ) {
            $error_message = "Date of birth cannot be in the future.";

        } else {

            try {
                $sql = "INSERT INTO members
                        (
                            full_name,
                            dob,
                            email,
                            phone,
                            address,
                            join_date
                        )
                        VALUES
                        (
                            :full_name,
                            :dob,
                            :email,
                            :phone,
                            :address,
                            :join_date
                        )";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":full_name" => $full_name,
                    ":dob" => $dob !== "" ? $dob : null,
                    ":email" => $email,
                    ":phone" => $phone,
                    ":address" => $address !== "" ? $address : null,
                    ":join_date" => $join_date
                ]);

                // Redirect after a successful insert.
                header("Location: index.php?added=1");
                exit;

            } catch (PDOException $e) {
                // Keep technical database details out of the browser.
                error_log("Add member error: " . $e->getMessage());

                $error_message =
                    "Unable to add the member. Please check the details and try again.";
            }
        }
    }
}

/* ========================================
   LOAD HEADER
======================================== */

require_once __DIR__ . "/../includes/header.php";

?>

<link
    rel="stylesheet"
    href="/gym-management/css/member-form.css?v=2"
>

<main class="member-add-page">

    <!-- PAGE HEADER -->

    <div class="member-add-header">

        <div>
            <p class="member-page-kicker">VORTEX FITNESS / MEMBERS</p>

            <h1>Add Member</h1>

            <p>Add a new member to your gym.</p>
        </div>

        <a
            href="/gym-management/members/index.php"
            class="member-back-button"
        >
            &larr; Back to Members
        </a>

    </div>

    <!-- FORM CARD -->

    <section class="member-add-card">

        <div class="member-add-card-header">

            <div class="member-add-icon" aria-hidden="true">
                +
            </div>

            <div>
                <h2>Member Information</h2>

                <p>
                    Enter the member's personal and contact details.
                </p>
            </div>

        </div>

        <!-- FORM -->

        <form
            method="POST"
            action=""
            class="member-add-form"
        >

            <?php if ($error_message !== ""): ?>
                <div class="form-error" role="alert">
                    <?= htmlspecialchars(
                        $error_message,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </div>
            <?php endif; ?>

            <!-- FULL NAME -->

            <div class="member-add-group">

                <label for="full_name">
                    Full Name <span>*</span>
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    placeholder="Enter member full name"
                    value="<?= htmlspecialchars(
                        $full_name,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    maxlength="150"
                    autocomplete="name"
                    required
                >

            </div>

            <!-- DATE OF BIRTH -->

            <div class="member-add-group">

                <label for="dob">
                    Date of Birth
                </label>

                <input
                    type="date"
                    id="dob"
                    name="dob"
                    value="<?= htmlspecialchars(
                        $dob,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    max="<?= date("Y-m-d") ?>"
                    autocomplete="bday"
                >

                <small class="member-field-help">
                    Optional — enter the member's date of birth.
                </small>

            </div>

            <!-- EMAIL -->

            <div class="member-add-group">

                <label for="email">
                    Email <span>*</span>
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="example@gmail.com"
                    value="<?= htmlspecialchars(
                        $email,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    maxlength="254"
                    autocomplete="email"
                    required
                >

            </div>

            <!-- PHONE -->

            <div class="member-add-group">

                <label for="phone">
                    Phone <span>*</span>
                </label>

                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    placeholder="Enter phone number"
                    value="<?= htmlspecialchars(
                        $phone,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    maxlength="20"
                    autocomplete="tel"
                    required
                >

            </div>

            <!-- ADDRESS -->

            <div class="member-add-group">

                <label for="address">
                    Address
                </label>

                <textarea
                    id="address"
                    name="address"
                    rows="4"
                    placeholder="Enter member address"
                    autocomplete="street-address"
                ><?= htmlspecialchars(
                    $address,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?></textarea>

            </div>

            <!-- JOIN DATE -->

            <div class="member-add-group">

                <label for="join_date">
                    Join Date <span>*</span>
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
                    required
                >

            </div>

            <!-- FORM ACTIONS -->

            <div class="member-add-actions">

                <a
                    href="/gym-management/members/index.php"
                    class="member-cancel-button"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="member-submit-button"
                >
                    <span aria-hidden="true">+</span>
                    Add Member
                </button>

            </div>

        </form>

    </section>

</main>

<?php

/*
 * Include the shared footer once.
 * If your header or another layout file already includes it,
 * remove this block to avoid a duplicate footer.
 */

$footer_file = __DIR__ . "/../includes/footer.php";

if (is_file($footer_file)) {
    require_once $footer_file;
}

?>
