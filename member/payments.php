<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

/* =========================================================
   ALLOW MEMBERS ONLY
========================================================= */

if (($_SESSION["role"] ?? "") !== "member") {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];


/* =========================================================
   GET MEMBER
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        id,
        full_name
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
   GET LATEST MEMBERSHIP
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        m.id,
        m.start_date,
        m.end_date,
        m.status,
        mp.plan_name,
        mp.price
    FROM memberships m
    INNER JOIN membership_plans mp
        ON m.plan_id = mp.id
    WHERE m.member_id = ?
    ORDER BY m.start_date DESC
    LIMIT 1
");

$stmt->execute([$member_id]);

$membership = $stmt->fetch(PDO::FETCH_ASSOC);


/* =========================================================
   PAYMENT SUMMARY
========================================================= */

$totalPaid = 0;
$pendingAmount = 0;
$latestPayment = null;

if ($membership) {

    /* =====================================================
       TOTAL PAID
    ===================================================== */

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0)
        FROM payments
        WHERE membership_id = ?
        AND status = 'paid'
    ");

    $stmt->execute([
        $membership["id"]
    ]);

    $totalPaid = (float) $stmt->fetchColumn();


    /* =====================================================
       PENDING AMOUNT
    ===================================================== */

    $planPrice = (float) $membership["price"];

    $pendingAmount = max(
        0,
        $planPrice - $totalPaid
    );


    /* =====================================================
       LATEST PAYMENT
    ===================================================== */

    $stmt = $pdo->prepare("
        SELECT
            id,
            amount,
            payment_date,
            payment_method,
            transaction_reference,
            status
        FROM payments
        WHERE membership_id = ?
        ORDER BY
            payment_date DESC,
            id DESC
        LIMIT 1
    ");

    $stmt->execute([
        $membership["id"]
    ]);

    $latestPayment = $stmt->fetch(PDO::FETCH_ASSOC);
}


/* =========================================================
   PAYMENT FILTERS
========================================================= */

$statusFilter = trim($_GET["status"] ?? "");

$methodFilter = trim($_GET["method"] ?? "");

$fromDate = trim($_GET["from_date"] ?? "");

$toDate = trim($_GET["to_date"] ?? "");


/* =========================================================
   PAGINATION
========================================================= */

$recordsPerPage = 10;

$currentPage = max(
    1,
    (int) ($_GET["page"] ?? 1)
);

$offset = (
    $currentPage - 1
) * $recordsPerPage;


/* =========================================================
   TOTAL FILTERED PAYMENT RECORDS
========================================================= */

$countSql = "
    SELECT COUNT(*)
    FROM payments p
    INNER JOIN memberships m
        ON p.membership_id = m.id
    WHERE m.member_id = ?
";

$countParams = [
    $member_id
];


/* =========================================================
   STATUS FILTER
========================================================= */

if (
    $statusFilter !== "" &&
    in_array(
        $statusFilter,
        [
            "paid",
            "pending",
            "refunded"
        ],
        true
    )
) {

    $countSql .= "
        AND p.status = ?
    ";

    $countParams[] = $statusFilter;
}


/* =========================================================
   PAYMENT METHOD FILTER
========================================================= */

if (
    $methodFilter !== "" &&
    in_array(
        $methodFilter,
        [
            "cash",
            "upi",
            "card",
            "bank_transfer"
        ],
        true
    )
) {

    $countSql .= "
        AND p.payment_method = ?
    ";

    $countParams[] = $methodFilter;
}


/* =========================================================
   FROM DATE FILTER
========================================================= */

if ($fromDate !== "") {

    $countSql .= "
        AND DATE(p.payment_date) >= ?
    ";

    $countParams[] = $fromDate;
}


/* =========================================================
   TO DATE FILTER
========================================================= */

if ($toDate !== "") {

    $countSql .= "
        AND DATE(p.payment_date) <= ?
    ";

    $countParams[] = $toDate;
}


/* =========================================================
   EXECUTE COUNT
========================================================= */

$countStmt = $pdo->prepare($countSql);

$countStmt->execute($countParams);

$totalPayments = (int) $countStmt->fetchColumn();


/* =========================================================
   TOTAL PAGES
========================================================= */

$totalPages = max(
    1,
    (int) ceil(
        $totalPayments / $recordsPerPage
    )
);


/* =========================================================
   VALIDATE CURRENT PAGE
========================================================= */

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}


/* =========================================================
   RECALCULATE OFFSET
========================================================= */

$offset = (
    $currentPage - 1
) * $recordsPerPage;


/* =========================================================
   PAYMENT HISTORY QUERY
========================================================= */

$sql = "
    SELECT
        p.id,
        p.amount,
        p.payment_date,
        p.payment_method,
        p.transaction_reference,
        p.status
    FROM payments p
    INNER JOIN memberships m
        ON p.membership_id = m.id
    WHERE m.member_id = ?
";

$params = [
    $member_id
];


/* =========================================================
   STATUS FILTER
========================================================= */

if (
    $statusFilter !== "" &&
    in_array(
        $statusFilter,
        [
            "paid",
            "pending",
            "refunded"
        ],
        true
    )
) {

    $sql .= "
        AND p.status = ?
    ";

    $params[] = $statusFilter;
}


/* =========================================================
   PAYMENT METHOD FILTER
========================================================= */

if (
    $methodFilter !== "" &&
    in_array(
        $methodFilter,
        [
            "cash",
            "upi",
            "card",
            "bank_transfer"
        ],
        true
    )
) {

    $sql .= "
        AND p.payment_method = ?
    ";

    $params[] = $methodFilter;
}


/* =========================================================
   FROM DATE FILTER
========================================================= */

if ($fromDate !== "") {

    $sql .= "
        AND DATE(p.payment_date) >= ?
    ";

    $params[] = $fromDate;
}


/* =========================================================
   TO DATE FILTER
========================================================= */

if ($toDate !== "") {

    $sql .= "
        AND DATE(p.payment_date) <= ?
    ";

    $params[] = $toDate;
}


/* =========================================================
   ORDER + PAGINATION
========================================================= */

$sql .= "
    ORDER BY
        p.payment_date DESC,
        p.id DESC
    LIMIT $recordsPerPage
    OFFSET $offset
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   HEADER
========================================================= */

require_once "../includes/header.php";

?>

<link
    rel="stylesheet"
    href="/gym-management/css/member-payments.css?v=4"
>


<div class="member-payments-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="page-header">

        <div>

            <span class="page-label">
                MEMBER PORTAL
            </span>

            <h1>
                My Payments
            </h1>

            <p>
                View your membership payments and payment history.
            </p>

        </div>

    </div>


    <!-- =====================================================
         PAYMENT OVERVIEW
    ====================================================== -->

    <div class="payment-overview">


        <!-- TOTAL PAID -->

        <div class="payment-card">

            <div class="payment-card-icon">
                ₹
            </div>

            <div>

                <span>
                    Total Paid
                </span>

                <strong>
                    ₹<?= number_format(
                        $totalPaid,
                        2
                    ) ?>
                </strong>

            </div>

        </div>


        <!-- PENDING -->

        <div class="payment-card">

            <div class="payment-card-icon pending">
                ₹
            </div>

            <div>

                <span>
                    Pending Amount
                </span>

                <strong>
                    ₹<?= number_format(
                        $pendingAmount,
                        2
                    ) ?>
                </strong>

            </div>

        </div>


        <!-- LATEST PAYMENT -->

        <div class="payment-card">

            <div class="payment-card-icon latest">
                ✓
            </div>

            <div>

                <span>
                    Latest Payment
                </span>

                <strong>

                    <?php if ($latestPayment): ?>

                        ₹<?= number_format(
                            (float) $latestPayment["amount"],
                            2
                        ) ?>

                    <?php else: ?>

                        No Payment

                    <?php endif; ?>

                </strong>

            </div>

        </div>


    </div>


    <!-- =====================================================
         CURRENT MEMBERSHIP
    ====================================================== -->

    <?php if ($membership): ?>

        <div class="membership-payment-card">

            <div class="section-heading">

                <div>

                    <span class="card-label">
                        CURRENT MEMBERSHIP
                    </span>

                    <h2>
                        Payment Summary
                    </h2>

                </div>

                <span class="membership-status">

                    <?= htmlspecialchars(
                        ucfirst(
                            $membership["status"]
                        )
                    ) ?>

                </span>

            </div>


            <div class="membership-payment-grid">


                <!-- PLAN -->

                <div class="membership-info">

                    <span>
                        Plan
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            (string) $membership["plan_name"]
                        ) ?>
                    </strong>

                </div>


                <!-- PLAN PRICE -->

                <div class="membership-info">

                    <span>
                        Plan Price
                    </span>

                    <strong>
                        ₹<?= number_format(
                            (float) $membership["price"],
                            2
                        ) ?>
                    </strong>

                </div>


                <!-- START DATE -->

                <div class="membership-info">

                    <span>
                        Start Date
                    </span>

                    <strong>

                        <?= date(
                            "d M Y",
                            strtotime(
                                $membership["start_date"]
                            )
                        ) ?>

                    </strong>

                </div>


                <!-- END DATE -->

                <div class="membership-info">

                    <span>
                        End Date
                    </span>

                    <strong>

                        <?= date(
                            "d M Y",
                            strtotime(
                                $membership["end_date"]
                            )
                        ) ?>

                    </strong>

                </div>


            </div>

        </div>

    <?php else: ?>

        <div class="empty-membership">

            <strong>
                No Membership Found
            </strong>

            <p>
                You currently don't have a membership.
            </p>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         PAYMENT HISTORY
    ====================================================== -->

    <div class="payment-history-card">


        <!-- SECTION HEADING -->

        <div class="section-heading">

            <div>

                <span class="card-label">
                    TRANSACTION HISTORY
                </span>

                <h2>
                    Payment History
                </h2>

            </div>

        </div>


        <!-- =================================================
             PAYMENT FILTER
        ================================================== -->

        <form
            method="GET"
            class="payment-filter-form"
        >


            <!-- STATUS -->

            <div class="payment-filter-field">

                <label for="status">
                    Payment Status
                </label>

                <select
                    name="status"
                    id="status"
                >

                    <option value="">
                        All Status
                    </option>

                    <option
                        value="paid"
                        <?= $statusFilter === "paid"
                            ? "selected"
                            : "" ?>
                    >
                        Paid
                    </option>

                    <option
                        value="pending"
                        <?= $statusFilter === "pending"
                            ? "selected"
                            : "" ?>
                    >
                        Pending
                    </option>

                    <option
                        value="refunded"
                        <?= $statusFilter === "refunded"
                            ? "selected"
                            : "" ?>
                    >
                        Refunded
                    </option>

                </select>

            </div>


            <!-- PAYMENT METHOD -->

            <div class="payment-filter-field">

                <label for="method">
                    Payment Method
                </label>

                <select
                    name="method"
                    id="method"
                >

                    <option value="">
                        All Methods
                    </option>

                    <option
                        value="cash"
                        <?= $methodFilter === "cash"
                            ? "selected"
                            : "" ?>
                    >
                        Cash
                    </option>

                    <option
                        value="upi"
                        <?= $methodFilter === "upi"
                            ? "selected"
                            : "" ?>
                    >
                        UPI
                    </option>

                    <option
                        value="card"
                        <?= $methodFilter === "card"
                            ? "selected"
                            : "" ?>
                    >
                        Card
                    </option>

                    <option
                        value="bank_transfer"
                        <?= $methodFilter === "bank_transfer"
                            ? "selected"
                            : "" ?>
                    >
                        Bank Transfer
                    </option>

                </select>

            </div>


            <!-- FROM DATE -->

            <div class="payment-filter-field">

                <label for="from_date">
                    From Date
                </label>

                <input
                    type="date"
                    name="from_date"
                    id="from_date"
                    value="<?= htmlspecialchars(
                        $fromDate
                    ) ?>"
                >

            </div>


            <!-- TO DATE -->

            <div class="payment-filter-field">

                <label for="to_date">
                    To Date
                </label>

                <input
                    type="date"
                    name="to_date"
                    id="to_date"
                    value="<?= htmlspecialchars(
                        $toDate
                    ) ?>"
                >

            </div>


            <!-- ACTIONS -->

            <div class="payment-filter-actions">

                <button
                    type="submit"
                    class="payment-filter-button"
                >
                    Search
                </button>

                <a
                    href="payments.php"
                    class="payment-clear-button"
                >
                    Clear
                </a>

            </div>


        </form>


        <!-- =================================================
             PAYMENT TABLE
        ================================================== -->

        <?php if (!empty($payments)): ?>

            <div class="payment-table-wrapper">

                <table class="payment-table">

                    <thead>

                        <tr>

                            <th>
                                Payment ID
                            </th>

                            <th>
                                Amount
                            </th>

                            <th>
                                Payment Date
                            </th>

                            <th>
                                Payment Method
                            </th>

                            <th>
                                Transaction Reference
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach (
                            $payments
                            as $payment
                        ): ?>

                            <tr>


                                <!-- PAYMENT ID -->

                                <td>

                                    #<?= (int)
                                        $payment["id"] ?>

                                </td>


                                <!-- AMOUNT -->

                                <td class="amount-cell">

                                    ₹<?= number_format(
                                        (float)
                                        $payment["amount"],
                                        2
                                    ) ?>

                                </td>


                                <!-- PAYMENT DATE -->

                                <td>

                                    <?= date(
                                        "d M Y, h:i A",
                                        strtotime(
                                            $payment[
                                                "payment_date"
                                            ]
                                        )
                                    ) ?>

                                </td>


                                <!-- PAYMENT METHOD -->

                                <td>

                                    <?= htmlspecialchars(
                                        ucwords(
                                            str_replace(
                                                "_",
                                                " ",
                                                $payment[
                                                    "payment_method"
                                                ]
                                            )
                                        )
                                    ) ?>

                                </td>


                                <!-- TRANSACTION REFERENCE -->

                                <td>

                                    <?php if (
                                        !empty(
                                            $payment[
                                                "transaction_reference"
                                            ]
                                        )
                                    ): ?>

                                        <?= htmlspecialchars(
                                            $payment[
                                                "transaction_reference"
                                            ]
                                        ) ?>

                                    <?php else: ?>

                                        <span
                                            class="no-reference"
                                        >
                                            —
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <span
                                        class="payment-status status-<?= htmlspecialchars(
                                            $payment["status"]
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            ucfirst(
                                                $payment[
                                                    "status"
                                                ]
                                            )
                                        ) ?>

                                    </span>

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <a
                                        href="payment_details.php?id=<?= (int) $payment["id"] ?>"
                                        class="payment-view-button"
                                    >
                                        View
                                    </a>

                                </td>


                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <!-- =================================================
                 PAGINATION
            ================================================== -->

            <?php if ($totalPages > 1): ?>

                <div class="payment-pagination">


                    <!-- PREVIOUS -->

                    <?php if ($currentPage > 1): ?>

                        <a
                            href="?<?= http_build_query([
                                "status" =>
                                    $statusFilter,

                                "method" =>
                                    $methodFilter,

                                "from_date" =>
                                    $fromDate,

                                "to_date" =>
                                    $toDate,

                                "page" =>
                                    $currentPage - 1
                            ]) ?>"
                            class="payment-page-button"
                        >
                            Previous
                        </a>

                    <?php else: ?>

                        <span
                            class="payment-page-button disabled"
                        >
                            Previous
                        </span>

                    <?php endif; ?>


                    <!-- PAGE NUMBERS -->

                    <?php for (
                        $page = 1;
                        $page <= $totalPages;
                        $page++
                    ): ?>

                        <a
                            href="?<?= http_build_query([
                                "status" =>
                                    $statusFilter,

                                "method" =>
                                    $methodFilter,

                                "from_date" =>
                                    $fromDate,

                                "to_date" =>
                                    $toDate,

                                "page" =>
                                    $page
                            ]) ?>"
                            class="payment-page-number
                            <?= $page === $currentPage
                                ? "active"
                                : "" ?>"
                        >

                            <?= $page ?>

                        </a>

                    <?php endfor; ?>


                    <!-- NEXT -->

                    <?php if (
                        $currentPage < $totalPages
                    ): ?>

                        <a
                            href="?<?= http_build_query([
                                "status" =>
                                    $statusFilter,

                                "method" =>
                                    $methodFilter,

                                "from_date" =>
                                    $fromDate,

                                "to_date" =>
                                    $toDate,

                                "page" =>
                                    $currentPage + 1
                            ]) ?>"
                            class="payment-page-button"
                        >
                            Next
                        </a>

                    <?php else: ?>

                        <span
                            class="payment-page-button disabled"
                        >
                            Next
                        </span>

                    <?php endif; ?>


                </div>


                <!-- PAGINATION INFO -->

                <div class="payment-pagination-info">

                    Showing

                    <strong>

                        <?= $totalPayments > 0
                            ? $offset + 1
                            : 0 ?>

                    </strong>

                    to

                    <strong>

                        <?= min(
                            $offset + $recordsPerPage,
                            $totalPayments
                        ) ?>

                    </strong>

                    of

                    <strong>
                        <?= $totalPayments ?>
                    </strong>

                    payments

                </div>

            <?php endif; ?>


        <?php else: ?>


            <!-- =================================================
                 NO PAYMENTS
            ================================================== -->

            <div class="no-payments">

                <div class="no-payments-icon">
                    ₹
                </div>

                <strong>
                    No Payments Found
                </strong>

                <p>
                    No payment transactions match
                    your selected filters.
                </p>

            </div>


        <?php endif; ?>


    </div>


</div>