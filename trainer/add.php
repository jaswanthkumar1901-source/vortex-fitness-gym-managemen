<?php

require_once "../includes/auth.php";
require_once "../config/database.php";


/* =========================================================
   ADD TRAINER
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $full_name = trim($_POST["full_name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $specialization = trim($_POST["specialization"] ?? "");


        $stmt = $pdo->prepare("
            INSERT INTO trainers
            (full_name, email, phone, specialization)
            VALUES (?, ?, ?, ?)
        ");


        $stmt->execute([
            $full_name,
            $email,
            $phone,
            $specialization
        ]);


        header("Location: index.php?added=1");
        exit;


    } catch (PDOException $e) {

        $error_message =
            "Unable to add trainer. Please try again.";
    }
}


/* =========================================================
   LOAD HEADER
========================================================= */

require_once "../includes/header.php";

?>


<link
    rel="stylesheet"
    href="/gym-management/css/trainer-form.css?v=1"
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
                Add Trainer
            </h1>

            <p>
                Add a new trainer to your gym management system.
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
                +
            </div>

            <div>

                <h2>
                    Trainer Information
                </h2>

                <p>
                    Enter the trainer's basic details below.
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
                    placeholder="e.g. Strength Training, Yoga, Cardio"
                >

                <small class="trainer-help-text">
                    Enter the trainer's primary area of expertise.
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
                    + Add Trainer
                </button>

            </div>


        </form>

    </div>

</div>