<?php

require_once "../includes/auth.php";
require_once "../config/database.php";


/* ========================================
   GET MEMBER ID
======================================== */

$id = $_GET["id"] ?? null;

if (!$id) {
    die("Invalid member ID");
}


/* ========================================
   FETCH MEMBER
======================================== */

$stmt = $pdo->prepare(
    "SELECT * FROM members WHERE id = ?"
);

$stmt->execute([$id]);

$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    die("Member not found");
}


/* ========================================
   HANDLE UPDATE
======================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $join_date = $_POST["join_date"] ?? "";
    $status = $_POST["status"] ?? "active";


    $stmt = $pdo->prepare(
        "UPDATE members
         SET full_name = ?,
             email = ?,
             phone = ?,
             address = ?,
             join_date = ?,
             status = ?
         WHERE id = ?"
    );


    $stmt->execute([
        $full_name,
        $email,
        $phone,
        $address,
        $join_date,
        $status,
        $id
    ]);


    /* Redirect after successful update */

    header("Location: index.php?updated=1");
    exit;
}


/* ========================================
   HEADER
======================================== */

require_once "../includes/header.php";

?>

<link
    rel="stylesheet"
    href="/gym-management/css/member-form.css?v=3"
>


<div class="member-add-page">


    <!-- ====================================
         PAGE HEADER
    ===================================== -->

    <div class="member-add-header">

        <div>

            <h1>Edit Member</h1>

            <p>
                Update the member's personal and account information.
            </p>

        </div>


        <a
            href="/gym-management/members/index.php"
            class="member-back-button"
        >
            ← Back to Members
        </a>

    </div>



    <!-- ====================================
         FORM CARD
    ===================================== -->

    <div class="member-add-card">


        <!-- CARD HEADER -->

        <div class="member-add-card-header">

            <div class="member-add-icon">
                ✎
            </div>


            <div>

                <h2>
                    Member Information
                </h2>

                <p>
                    Update the member details below.
                </p>

            </div>

        </div>



        <!-- ====================================
             FORM
        ===================================== -->

        <form
            method="POST"
            class="member-add-form"
        >


            <!-- FULL NAME -->

            <div class="member-add-group">

                <label for="full_name">

                    Full Name

                    <span>*</span>

                </label>


                <input
                    type="text"
                    name="full_name"
                    id="full_name"
                    value="<?= htmlspecialchars($member["full_name"]) ?>"
                    placeholder="Enter member full name"
                    required
                >

            </div>



            <!-- EMAIL -->

            <div class="member-add-group">

                <label for="email">

                    Email

                    <span>*</span>

                </label>


                <input
                    type="email"
                    name="email"
                    id="email"
                    value="<?= htmlspecialchars($member["email"] ?? "") ?>"
                    placeholder="example@gmail.com"
                    required
                >

            </div>



            <!-- PHONE -->

            <div class="member-add-group">

                <label for="phone">

                    Phone

                    <span>*</span>

                </label>


                <input
                    type="tel"
                    name="phone"
                    id="phone"
                    value="<?= htmlspecialchars($member["phone"] ?? "") ?>"
                    placeholder="Enter phone number"
                    required
                >

            </div>



            <!-- ADDRESS -->

            <div class="member-add-group">

                <label for="address">
                    Address
                </label>


                <textarea
                    name="address"
                    id="address"
                    rows="4"
                    placeholder="Enter member address"
                ><?= htmlspecialchars($member["address"] ?? "") ?></textarea>

            </div>



            <!-- JOIN DATE -->

            <div class="member-add-group">

                <label for="join_date">

                    Join Date

                    <span>*</span>

                </label>


                <input
                    type="date"
                    name="join_date"
                    id="join_date"
                    value="<?= htmlspecialchars($member["join_date"]) ?>"
                    required
                >

            </div>



            <!-- STATUS -->

            <div class="member-add-group">

                <label for="status">

                    Status

                    <span>*</span>

                </label>


                <select
                    name="status"
                    id="status"
                    required
                >

                    <option
                        value="active"
                        <?= ($member["status"] === "active")
                            ? "selected"
                            : "" ?>
                    >
                        Active
                    </option>


                    <option
                        value="inactive"
                        <?= ($member["status"] === "inactive")
                            ? "selected"
                            : "" ?>
                    >
                        Inactive
                    </option>

                </select>

            </div>



            <!-- ====================================
                 BUTTONS
            ===================================== -->

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
                    ✓ Update Member
                </button>


            </div>


        </form>

    </div>

</div>