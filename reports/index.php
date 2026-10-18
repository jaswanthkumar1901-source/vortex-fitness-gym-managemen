<?php

require_once "../includes/auth.php";
require_once "../config/database.php";


/* Total Members */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM members
");

$totalMembers = $stmt->fetchColumn();


/* Active Memberships */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM memberships
    WHERE status = 'active'
    AND end_date >= CURDATE()
");

$activeMemberships = $stmt->fetchColumn();


/* Today's Attendance */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM attendance
    WHERE DATE(check_in) = CURRENT_DATE
");

$todayAttendance = $stmt->fetchColumn();


/* Total Paid Payments */

$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM payments
    WHERE status = 'paid'
");

$totalPayments = $stmt->fetchColumn();

/* Expired Memberships */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM memberships
    WHERE end_date < CURDATE()
");

$expiredMemberships = $stmt->fetchColumn();


/* Active Trainers */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM trainers
    WHERE status = 'active'
");

$activeTrainers = $stmt->fetchColumn();


/* Pending Payments */

$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM payments
    WHERE status = 'pending'
");

$pendingPayments = $stmt->fetchColumn();

/* Header */

require_once "../includes/header.php";

?>

<link rel="stylesheet" href="/gym-management/css/reports.css?v=2">


<div class="reports-page">

    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <h1>Reports</h1>

            <p>
                View important gym statistics and detailed reports.
            </p>

        </div>

    </div>


    <!-- SUMMARY CARDS -->

    <div class="reports-summary">


        <!-- TOTAL MEMBERS -->

        <div class="report-card">

            <div class="report-icon members-icon">
                👥
            </div>

            <div>

                <span>Total Members</span>

                <strong>
                    <?= $totalMembers ?>
                </strong>

                <small>
                    Registered members
                </small>

            </div>

        </div>


        <!-- ACTIVE MEMBERSHIPS -->

        <div class="report-card">

            <div class="report-icon active-icon">
                ✓
            </div>

            <div>

                <span>Active Memberships</span>

                <strong>
                    <?= $activeMemberships ?>
                </strong>

                <small>
                    Currently active
                </small>

            </div>

        </div>


        <!-- TODAY ATTENDANCE -->

        <div class="report-card">

            <div class="report-icon attendance-icon">
                ◷
            </div>

            <div>

                <span>Today's Attendance</span>

                <strong>
                    <?= $todayAttendance ?>
                </strong>

                <small>
                    Check-ins today
                </small>

            </div>

        </div>


        <!-- TOTAL PAYMENTS -->

        <div class="report-card">

            <div class="report-icon payment-icon">
                ₹
            </div>

            <div>

                <span>Total Paid Payments</span>

                <strong>
                    ₹<?= number_format($totalPayments, 2) ?>
                </strong>

                <small>
                    Successfully collected
                </small>

            </div>

        </div>
        <!-- EXPIRED MEMBERSHIPS -->

        <div class="report-card">

            <div class="report-icon expired-icon">
                !
            </div>

            <div>

                <span>Expired Memberships</span>

                <strong>
                    <?= $expiredMemberships ?>
                </strong>

                <small>
                    Memberships expired
                </small>

            </div>

        </div>


        <!-- ACTIVE TRAINERS -->

        <div class="report-card">

            <div class="report-icon trainer-icon">
                ★
            </div>

            <div>

                <span>Active Trainers</span>

                <strong>
                    <?= $activeTrainers ?>
                </strong>

                <small>
                    Currently active
                </small>

            </div>

        </div>


        <!-- PENDING PAYMENTS -->

        <div class="report-card">

            <div class="report-icon pending-icon">
                ₹
            </div>

            <div>

                <span>Pending Payments</span>

                <strong>
                    ₹<?= number_format($pendingPayments, 2) ?>
                </strong>

                <small>
                    Awaiting confirmation
                </small>

            </div>

        </div>

    </div>


    <!-- DETAILED REPORTS -->

    <div class="detailed-reports">

        <div class="section-header">

            <div>

                <h2>Detailed Reports</h2>

                <p>
                    View detailed information about your gym operations.
                </p>

            </div>

        </div>


        <div class="report-list">


            <!-- MEMBERSHIP REPORT -->

            <div class="detail-report-card">

                <div class="detail-icon membership-icon">
                    ▤
                </div>

                <div class="detail-content">

                    <h3>Membership Report</h3>

                    <p>
                        View member subscriptions, plans,
                        start dates, end dates and membership status.
                    </p>

                </div>

                <a
                    href="../membership/index.php"
                    class="view-report-btn"
                >
                    View Report →
                </a>

            </div>


            <!-- PAYMENT REPORT -->

            <div class="detail-report-card">

                <div class="detail-icon payment-detail-icon">
                    ₹
                </div>

                <div class="detail-content">

                    <h3>Payment Report</h3>

                    <p>
                        View payment records, amounts,
                        payment methods and transaction details.
                    </p>

                </div>

                <a
                    href="../payments/index.php"
                    class="view-report-btn"
                >
                    View Report →
                </a>

            </div>


            <!-- ATTENDANCE REPORT -->

            <div class="detail-report-card">

                <div class="detail-icon attendance-detail-icon">
                    ✓
                </div>

                <div class="detail-content">

                    <h3>Attendance Report</h3>

                    <p>
                        View member check-ins, check-outs
                        and attendance records.
                    </p>

                </div>

                <a
                    href="../attendance/index.php"
                    class="view-report-btn"
                >
                    View Report →
                </a>

            </div>

            <!-- TRAINER REPORT -->

            <div class="detail-report-card">

                <div class="detail-icon trainer-detail-icon">
                    ★
                </div>

                <div class="detail-content">

                    <h3>Trainer Report</h3>

                    <p>
                        View trainers, their status and
                        assigned member information.
                    </p>

                </div>

                <a
                    href="../trainer/index.php"
                    class="view-report-btn"
                >
                    View Report →
                </a>

            </div>


        </div>

    </div>

</div>