<?php

session_start();

require_once "../config/database.php";



/* =========================================================

   CHECK LOGIN

========================================================= */

if (!isset($_SESSION["user_id"])) {

    header("Location: ../auth/login.php");

    exit;

}



/* =========================================================

   CHECK MEMBER ROLE

========================================================= */

if (

    !isset($_SESSION["role"]) ||

    $_SESSION["role"] !== "member"

) {

    header("Location: ../auth/login.php");

    exit;

}



$user_id = $_SESSION["user_id"];

$error_message = "";



/* =========================================================

   FETCH MEMBER

========================================================= */

$stmt = $pdo->prepare("

    SELECT

        id,

        full_name,

        email,

        phone,

        address,

        date_of_birth,

        join_date,

        status

    FROM members

    WHERE user_id = ?

    LIMIT 1

");



$stmt->execute([$user_id]);

$member = $stmt->fetch(PDO::FETCH_ASSOC);



if (!$member) {

    die("Member profile not found.");

}



$member_id = $member["id"];



/* =========================================================

   UPDATE EXPIRED MEMBERSHIP

========================================================= */

$stmt = $pdo->prepare("

    UPDATE memberships

    SET status = 'expired'

    WHERE member_id = ?

      AND status = 'active'

      AND end_date < CURDATE()

");



$stmt->execute([$member_id]);



/* =========================================================

   FETCH ACTIVE MEMBERSHIP

========================================================= */

$stmt = $pdo->prepare("

    SELECT

        memberships.id,

        membership_plans.plan_name,

        membership_plans.price,

        membership_plans.duration_days,

        memberships.start_date,

        memberships.end_date,

        memberships.status

    FROM memberships

    JOIN membership_plans

        ON memberships.plan_id = membership_plans.id

    WHERE memberships.member_id = ?

      AND memberships.status = 'active'

      AND memberships.end_date >= CURDATE()

    ORDER BY memberships.end_date DESC

    LIMIT 1

");
/* =========================================================
   FETCH ACTIVE MEMBERSHIP
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        memberships.id,
        membership_plans.plan_name,
        membership_plans.price,
        membership_plans.duration_days,
        memberships.start_date,
        memberships.end_date,
        memberships.status
    FROM memberships
    JOIN membership_plans
        ON memberships.plan_id = membership_plans.id
    WHERE memberships.member_id = ?
      AND memberships.status = 'active'
      AND memberships.start_date <= CURDATE()
      AND memberships.end_date >= CURDATE()
    ORDER BY memberships.end_date DESC
    LIMIT 1
");

$stmt->execute([$member_id]);
$membership = $stmt->fetch(PDO::FETCH_ASSOC);


/* =========================================================

   FETCH TRAINER

========================================================= */

$stmt = $pdo->prepare("

    SELECT

        trainers.full_name,

        trainers.email,

        trainers.phone,

        trainers.specialization

    FROM member_trainer

    JOIN trainers

        ON member_trainer.trainer_id = trainers.id

    WHERE member_trainer.member_id = ?

      AND trainers.status = 'active'

    ORDER BY member_trainer.assigned_date DESC

    LIMIT 1

");



$stmt->execute([$member_id]);

$trainer = $stmt->fetch(PDO::FETCH_ASSOC);



/* =========================================================

   TODAY'S ATTENDANCE

========================================================= */

$stmt = $pdo->prepare("

    SELECT

        check_in,

        check_out

    FROM attendance

    WHERE member_id = ?

      AND DATE(check_in) = CURDATE()

    ORDER BY check_in DESC

    LIMIT 1

");



$stmt->execute([$member_id]);

$todayAttendance = $stmt->fetch(PDO::FETCH_ASSOC);



/* =========================================================

   RECENT ATTENDANCE

========================================================= */

$stmt = $pdo->prepare("

    SELECT

        check_in,

        check_out

    FROM attendance

    WHERE member_id = ?

    ORDER BY check_in DESC

    LIMIT 5

");



$stmt->execute([$member_id]);



$recentAttendance =

    $stmt->fetchAll(PDO::FETCH_ASSOC);



/* =========================================================

   RECENT PAYMENTS

========================================================= */

$stmt = $pdo->prepare("

    SELECT

        payments.amount,

        payments.payment_date,

        payments.payment_method,

        payments.transaction_reference,

        payments.status,

        membership_plans.plan_name

    FROM payments

    JOIN memberships

        ON payments.membership_id = memberships.id

    JOIN membership_plans

        ON memberships.plan_id = membership_plans.id

    WHERE memberships.member_id = ?

    ORDER BY payments.payment_date DESC

    LIMIT 5

");



$stmt->execute([$member_id]);



$recentPayments =

    $stmt->fetchAll(PDO::FETCH_ASSOC);



/* =========================================================

   LOAD MEMBER HEADER

========================================================= */

require_once "../includes/header.php";

?>



<link

    rel="stylesheet"

    href="/gym-management/css/member-dashboard.css?v=2"

>



<div class="member-dashboard">



    <!-- =====================================================

         PAGE HEADER

    ====================================================== -->



    <div class="member-dashboard-header">



        <div>



            <span class="member-dashboard-label">

                MEMBER PORTAL

            </span>



            <h1>

                Welcome,

                <?= htmlspecialchars($member["full_name"]) ?>

            </h1>



            <p>

                Manage your membership, attendance,

                trainer and payment information.

            </p>



        </div>



        



    </div>



    <!-- =====================================================

         SUMMARY CARDS

    ====================================================== -->



    <div class="member-summary-grid">



        <!-- MEMBERSHIP SUMMARY -->



        <div class="member-summary-card">



            <div class="member-summary-icon membership-summary-icon">

                ✓

            </div>



            <div>



                <span>

                    Membership

                </span>



                <strong>



                    <?php if ($membership): ?>



                        Active



                    <?php else: ?>



                        No Active Plan



                    <?php endif; ?>



                </strong>



                <small>



                    <?php if ($membership): ?>



                        <?= htmlspecialchars(

                            $membership["plan_name"]

                        ) ?>



                    <?php else: ?>



                        Please contact the gym.



                    <?php endif; ?>



                </small>



            </div>



        </div>



        <!-- TRAINER SUMMARY -->



        <div class="member-summary-card">



            <div class="member-summary-icon trainer-summary-icon">

                ★

            </div>



            <div>



                <span>

                    Trainer

                </span>



                <strong>



                    <?php if ($trainer): ?>



                        <?= htmlspecialchars(

                            $trainer["full_name"]

                        ) ?>



                    <?php else: ?>



                        Not Assigned



                    <?php endif; ?>



                </strong>



                <small>



                    <?php if ($trainer): ?>



                        <?= htmlspecialchars(

                            $trainer["specialization"]

                            ?: "Personal Trainer"

                        ) ?>



                    <?php else: ?>



                        No trainer assigned



                    <?php endif; ?>



                </small>



            </div>



        </div>



        <!-- MEMBERSHIP EXPIRY SUMMARY -->



        <div class="member-summary-card">



            <div class="member-summary-icon expiry-summary-icon">

                ◷

            </div>



            <div>



                <span>

                    Membership Expiry

                </span>



                <strong>



                    <?php if ($membership): ?>



                        <?= date(

                            "d M Y",

                            strtotime($membership["end_date"])

                        ) ?>



                    <?php else: ?>



                        —



                    <?php endif; ?>



                </strong>



                <small>

                    Current membership

                </small>



            </div>



        </div>



        <!-- TODAY'S ATTENDANCE SUMMARY -->



        <div class="member-summary-card">



            <div class="member-summary-icon attendance-summary-icon">

                ✓

            </div>



            <div>



                <span>

                    Today's Attendance

                </span>



                <strong>



                    <?php if ($todayAttendance): ?>



                        Checked In



                    <?php else: ?>



                        Not Checked In



                    <?php endif; ?>



                </strong>



                <small>



                    <?php if (

                        $todayAttendance &&

                        $todayAttendance["check_out"]

                    ): ?>



                        Checked out



                    <?php elseif ($todayAttendance): ?>



                        Currently inside gym



                    <?php else: ?>



                        No check-in today



                    <?php endif; ?>



                </small>



            </div>



        </div>



    </div>

    <!-- END SUMMARY CARDS -->





    <!-- =====================================================

         MAIN CONTENT

    ====================================================== -->



    <div class="member-dashboard-grid">



        <!-- =================================================

             MEMBERSHIP DETAILS

        ================================================== -->



        <div class="member-dashboard-card">



            <div class="member-card-header">



                <div>



                    <h2>

                        My Membership

                    </h2>



                    <p>

                        Your current membership details.

                    </p>



                </div>



            </div>



            <?php if ($membership): ?>



                <div class="member-detail-list">



                    <div>



                        <span>

                            Plan

                        </span>



                        <strong>

                            <?= htmlspecialchars(

                                $membership["plan_name"]

                            ) ?>

                        </strong>



                    </div>



                    <div>



                        <span>

                            Start Date

                        </span>



                        <strong>

                            <?= date(

                                "d M Y",

                                strtotime($membership["start_date"])

                            ) ?>

                        </strong>



                    </div>



                    <div>



                        <span>

                            End Date

                        </span>



                        <strong>

                            <?= date(

                                "d M Y",

                                strtotime($membership["end_date"])

                            ) ?>

                        </strong>



                    </div>



                    <div>



                        <span>

                            Status

                        </span>



                        <strong class="member-status-active">

                            Active

                        </strong>



                    </div>



                </div>



            <?php else: ?>



                <div class="member-empty-state">



                    <span>

                        No active membership found.

                    </span>



                </div>



            <?php endif; ?>



            <!-- BUY / RENEW MEMBERSHIP BUTTON -->



                        



        </div>





        <!-- =================================================

             TRAINER DETAILS

        ================================================== -->



        <div class="member-dashboard-card">



            <div class="member-card-header">



                <div>



                    <h2>

                        My Trainer

                    </h2>



                    <p>

                        Your assigned trainer.

                    </p>



                </div>



            </div>



            <?php if ($trainer): ?>



                <div class="member-trainer-profile">



                    <div class="member-trainer-avatar">

                        <?= strtoupper(

                            substr(

                                $trainer["full_name"],

                                0,

                                1

                            )

                        ) ?>

                    </div>



                    <div>



                        <h3>

                            <?= htmlspecialchars(

                                $trainer["full_name"]

                            ) ?>

                        </h3>



                        <p>

                            <?= htmlspecialchars(

                                $trainer["specialization"]

                                ?: "Personal Trainer"

                            ) ?>

                        </p>



                        <?php if (!empty($trainer["email"])): ?>



                            <small>

                                <?= htmlspecialchars(

                                    $trainer["email"]

                                ) ?>

                            </small>



                        <?php endif; ?>



                    </div>



                </div>



            <?php else: ?>



                <div class="member-empty-state">



                    <span>

                        No trainer has been assigned yet.

                    </span>



                </div>



            <?php endif; ?>



        </div>



    </div>

    <!-- END MAIN CONTENT GRID -->





    <!-- =====================================================

         RECENT ATTENDANCE

    ====================================================== -->



    <div class="member-dashboard-card member-wide-card">



        <div class="member-card-header">



            <div>



                <h2>

                    Recent Attendance

                </h2>



                <p>

                    Your latest gym attendance records.

                </p>



            </div>



        </div>



        <?php if ($recentAttendance): ?>



            <div class="member-table-wrapper">



                <table class="member-dashboard-table">



                    <thead>



                        <tr>



                            <th>

                                Date

                            </th>



                            <th>

                                Check In

                            </th>



                            <th>

                                Check Out

                            </th>



                            <th>

                                Status

                            </th>



                        </tr>



                    </thead>



                    <tbody>



                        <?php foreach (

                            $recentAttendance as $attendance

                        ): ?>



                            <tr>



                                <td>

                                    <?= date(

                                        "d M Y",

                                        strtotime($attendance["check_in"])

                                    ) ?>

                                </td>



                                <td>

                                    <?= date(

                                        "h:i A",

                                        strtotime($attendance["check_in"])

                                    ) ?>

                                </td>



                                <td>



                                    <?php if ($attendance["check_out"]): ?>



                                        <?= date(

                                            "h:i A",

                                            strtotime($attendance["check_out"])

                                        ) ?>



                                    <?php else: ?>



                                        —



                                    <?php endif; ?>



                                </td>



                                <td>



                                    <?php if ($attendance["check_out"]): ?>



                                        <span class="member-status-completed">

                                            Completed

                                        </span>



                                    <?php else: ?>



                                        <span class="member-status-present">

                                            Present

                                        </span>



                                    <?php endif; ?>



                                </td>



                            </tr>



                        <?php endforeach; ?>



                    </tbody>



                </table>



            </div>



        <?php else: ?>



            <div class="member-empty-state">



                <span>

                    No attendance records found.

                </span>



            </div>



        <?php endif; ?>



    </div>





    <!-- =====================================================

         RECENT PAYMENTS

    ====================================================== -->



    <div class="member-dashboard-card member-wide-card">



        <div class="member-card-header">



            <div>



                <h2>

                    Recent Payments

                </h2>



                <p>

                    Your latest payment records.

                </p>



            </div>



        </div>



        <?php if ($recentPayments): ?>



            <div class="member-table-wrapper">



                <table class="member-dashboard-table">



                    <thead>



                        <tr>



                            <th>

                                Plan

                            </th>



                            <th>

                                Amount

                            </th>



                            <th>

                                Payment Date

                            </th>



                            <th>

                                Method

                            </th>



                            <th>

                                Status

                            </th>



                        </tr>



                    </thead>



                    <tbody>



                        <?php foreach (

                            $recentPayments as $payment

                        ): ?>



                            <tr>



                                <td>

                                    <?= htmlspecialchars(

                                        $payment["plan_name"]

                                    ) ?>

                                </td>



                                <td>

                                    ₹<?= number_format(

                                        $payment["amount"],

                                        2

                                    ) ?>

                                </td>



                                <td>

                                    <?= date(

                                        "d M Y",

                                        strtotime($payment["payment_date"])

                                    ) ?>

                                </td>



                                <td>

                                    <?= htmlspecialchars(

                                        ucwords(

                                            str_replace(

                                                "_",

                                                " ",

                                                $payment["payment_method"]

                                            )

                                        )

                                    ) ?>

                                </td>



                                <td>



                                    <?php

                                    $paymentStatus = strtolower(

                                        trim($payment["status"])

                                    );

                                    ?>



                                    <?php if (

                                        $paymentStatus === "paid"

                                    ): ?>



                                        <span class="member-payment-paid">

                                            Paid

                                        </span>



                                    <?php elseif (

                                        $paymentStatus === "pending"

                                    ): ?>



                                        <span class="member-payment-pending">

                                            Pending

                                        </span>



                                    <?php else: ?>



                                        <span class="member-payment-refunded">

                                            <?= htmlspecialchars(

                                                ucfirst($paymentStatus)

                                            ) ?>

                                        </span>



                                    <?php endif; ?>



                                </td>



                            </tr>



                        <?php endforeach; ?>



                    </tbody>



                </table>



            </div>



        <?php else: ?>



            <div class="member-empty-state">



                <span>

                    No payment records found.

                </span>



            </div>



        <?php endif; ?>



    </div>



</div>

<!-- END MEMBER DASHBOARD -->
