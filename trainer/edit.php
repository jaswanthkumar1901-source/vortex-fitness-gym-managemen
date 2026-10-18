<?php

require_once "../includes/auth.php";
require_once "../config/database.php";


$id = $_GET["id"] ?? null;


/* =========================================================
   CHECK TRAINER ID
========================================================= */

if (!$id || !is_numeric($id)) {
    die("Trainer ID is missing.");
}


/* =========================================================
   FETCH EXISTING TRAINER
========================================================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM trainers
    WHERE id = ?
");

$stmt->execute([$id]);

$trainer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trainer) {
    die("Trainer not found.");
}


/* =========================================================
   UPDATE TRAINER
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $full_name = trim($_POST["full_name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $specialization = trim($_POST["specialization"] ?? "");
        $status = $_POST["status"] ?? "active";


        $stmt = $pdo->prepare("
            UPDATE trainers
            SET
                full_name = ?,
                email = ?,
                phone = ?,
                specialization = ?,
                status = ?
            WHERE id = ?
        ");


        $stmt->execute([
            $full_name,
            $email,
            $phone,
            $specialization,
            $status,
            $id
        ]);


        header("Location: index.php?updated=1");
        exit;


    } catch (PDOException $e) {

        $error_message =
            "Unable to update trainer. Please try again.";
    }
}


/* =========================================================
   LOAD HEADER
========================================================= */

require_once "../includes/header.php";

?>


<link
    rel="stylesheet"
    href="/gym-management/css/trainer-form.css?v=2"
>


<div class="trainer-form-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="trainer-form-header">

        <div>

            <span class="trainer-form-label">
                TRAINER MANAGEMENT
            </span>

            <h1>
                Edit Trainer
            </h1>

            <p>
                Update the trainer's information and status.
            </p>

        </div>


        <a
            href="/gym-management/trainer/index.php"
            class="trainer-back-button"
        >
            ← Back to Trainers
        </a>

    </div>



    <!-- =====================================================
         ERROR MESSAGE
    ====================================================== -->

    <?php if (!empty($error_message)): ?>

        <div class="trainer-form-error">

            <?= htmlspecialchars($error_message) ?>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         FORM CARD
    ====================================================== -->

    <div class="trainer-form-card">


        <!-- Card Header -->

        <div class="trainer-form-card-header">

            <div class="trainer-form-icon">
                ✎
            </div>

            <div>

                <h2>
                    Edit Trainer Information
                </h2>

                <p>
                    Update the trainer details below.
                </p>

            </div>

        </div>



        <!-- Form -->

        <form
            method="POST"
            class="trainer-form"
        >


            <!-- Full Name -->

            <div class="trainer-form-group">

                <label for="full_name">
                    Full Name
                    <span>*</span>
                </label>

                <input
                    type="text"
                    name="full_name"
                    id="full_name"
                    value="<?= htmlspecialchars(
                        $trainer["full_name"]
                    ) ?>"
                    placeholder="Enter trainer full name"
                    required
                >

            </div>



            <!-- Email -->

            <div class="trainer-form-group">

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    id="email"
                    value="<?= htmlspecialchars(
                        $trainer["email"] ?? ""
                    ) ?>"
                    placeholder="Enter email address"
                >

            </div>



            <!-- Phone -->

            <div class="trainer-form-group">

                <label for="phone">
                    Phone
                </label>

                <input
                    type="text"
                    name="phone"
                    id="phone"
                    value="<?= htmlspecialchars(
                        $trainer["phone"] ?? ""
                    ) ?>"
                    placeholder="Enter phone number"
                >

            </div>



            <!-- Specialization -->

            <div class="trainer-form-group">

                <label for="specialization">
                    Specialization
                </label>

                <input
                    type="text"
                    name="specialization"
                    id="specialization"
                    value="<?= htmlspecialchars(
                        $trainer["specialization"] ?? ""
                    ) ?>"
                    placeholder="e.g. Strength Training, Yoga, Cardio"
                >

                <small class="trainer-help-text">
                    Enter the trainer's primary area of expertise.
                </small>

            </div>



            <!-- Status -->

            <div class="trainer-form-group">

                <label for="status">
                    Status
                    <span>*</span>
                </label>

                <select
                    name="status"
                    id="status"
                    required
                    class="trainer-form-select"
                >

                    <option
                        value="active"
                        <?= ($trainer["status"] === "active")
                            ? "selected"
                            : "" ?>
                    >
                        Active
                    </option>


                    <option
                        value="inactive"
                        <?= ($trainer["status"] === "inactive")
                            ? "selected"
                            : "" ?>
                    >
                        Inactive
                    </option>

                </select>

                <small class="trainer-help-text">
                    Inactive trainers will not be available for new assignments.
                </small>

            </div>



            <!-- Actions -->

            <div class="trainer-form-actions">

                <a
                    href="/gym-management/trainer/index.php"
                    class="trainer-cancel-button"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="trainer-submit-button"
                >
                    ✓ Update Trainer
                </button>

            </div>


        </form>

    </div>

</div>