<?php

require_once "../includes/auth.php";
require_once "../config/database.php";


/* Fetch payments */

$stmt = $pdo->query("
    SELECT
        payments.id,
        members.full_name,
        membership_plans.plan_name,
        payments.amount,
        payments.payment_date,
        payments.payment_method,
        payments.transaction_reference,
        payments.status
    FROM payments
    JOIN memberships
        ON payments.membership_id = memberships.id
    JOIN members
        ON memberships.member_id = members.id
    JOIN membership_plans
        ON memberships.plan_id = membership_plans.id
    ORDER BY payments.id DESC
");

$allPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Search and status filter */
$search = trim($_GET["search"] ?? "");
$statusFilter = strtolower(trim($_GET["status"] ?? "all"));
$allowedStatuses = ["all", "paid", "pending", "refunded"];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = "all";
}

$filteredPayments = array_values(array_filter($allPayments, function ($payment) use ($search, $statusFilter) {
    $status = strtolower(trim($payment["status"] ?? ""));
    if ($statusFilter !== "all" && $status !== $statusFilter) {
        return false;
    }
    if ($search === "") {
        return true;
    }
    $searchable = implode(" ", [
        $payment["id"] ?? "",
        $payment["full_name"] ?? "",
        $payment["plan_name"] ?? "",
        $payment["amount"] ?? "",
        $payment["payment_date"] ?? "",
        $payment["payment_method"] ?? "",
        $payment["transaction_reference"] ?? "",
        $payment["status"] ?? ""
    ]);
    return stripos($searchable, $search) !== false;
}));

/* Pagination */
$perPage = 10;
$totalFilteredPayments = count($filteredPayments);
$totalPages = max(1, (int) ceil($totalFilteredPayments / $perPage));
$currentPage = filter_input(INPUT_GET, "page", FILTER_VALIDATE_INT);
$currentPage = max(1, min($totalPages, $currentPage ?: 1));
$offset = ($currentPage - 1) * $perPage;
$payments = array_slice($filteredPayments, $offset, $perPage);
$showingFrom = $totalFilteredPayments > 0 ? $offset + 1 : 0;
$showingTo = min($offset + $perPage, $totalFilteredPayments);


/* Payment summary */

$totalPayments = count($allPayments);

$paidAmount = 0;
$pendingAmount = 0;

foreach ($allPayments as $payment) {

    $status = strtolower(trim($payment["status"]));
    $amount = (float) $payment["amount"];

    if ($status === "paid") {
        $paidAmount += $amount;
    }

    if ($status === "pending") {
        $pendingAmount += $amount;
    }
}


/* Load header */

require_once "../includes/header.php";

?>

<link rel="stylesheet" href="/gym-management/css/payments.css?v=3">


<div class="payments-page">

    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>
            <h1>Payments</h1>

            <p>
                Track and manage gym membership payments.
            </p>
        </div>

        <a
            href="/gym-management/payments/add.php"
            class="add-payment-btn"
        >
            + Add Payment
        </a>

    </div>


    <!-- SUCCESS MESSAGES -->

    <?php if (isset($_GET["added"]) && $_GET["added"] == "1"): ?>

        <div class="success-message">
            ✓ Payment added successfully!
        </div>

    <?php endif; ?>


    <?php if (isset($_GET["updated"]) && $_GET["updated"] == "1"): ?>

        <div class="success-message">
            ✓ Payment updated successfully!
        </div>

    <?php endif; ?>


    <?php if (isset($_GET["deleted"]) && $_GET["deleted"] == "1"): ?>

        <div class="success-message">
            ✓ Payment deleted successfully!
        </div>

    <?php endif; ?>


    <!-- SUMMARY CARDS -->

    <div class="payment-summary">

        <!-- Total Payments -->

        <div class="summary-card">

            <div class="summary-icon">
                ₹
            </div>

            <div>
                <span>Total Payments</span>
                <strong><?= $totalPayments ?></strong>
            </div>

        </div>


        <!-- Paid Amount -->

        <div class="summary-card">

            <div class="summary-icon paid-icon">
                ✓
            </div>

            <div>
                <span>Paid Amount</span>

                <strong>
                    ₹<?= number_format($paidAmount, 2) ?>
                </strong>
            </div>

        </div>


        <!-- Pending Amount -->

        <div class="summary-card">

            <div class="summary-icon pending-icon">
                !
            </div>

            <div>
                <span>Pending Amount</span>

                <strong>
                    ₹<?= number_format($pendingAmount, 2) ?>
                </strong>
            </div>

        </div>

    </div>


    <!-- PAYMENT LIST -->

    <div class="payments-card">

        <div class="card-header">
            <div class="card-heading">
                <h2>Payment List</h2>
                <p>View and manage recorded membership payments.</p>
            </div>

            <div class="payment-tools">
                <form method="GET" class="payment-search">
                    <label class="visually-hidden" for="payment-search">Search payments</label>
                    <input
                        type="search"
                        id="payment-search"
                        name="search"
                        value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
                        placeholder="Search member, plan, ID, reference..."
                    >
                    <label class="visually-hidden" for="payment-status">Payment status</label>
                    <select id="payment-status" name="status">
                        <option value="all" <?= $statusFilter === "all" ? "selected" : "" ?>>All statuses</option>
                        <option value="paid" <?= $statusFilter === "paid" ? "selected" : "" ?>>Paid</option>
                        <option value="pending" <?= $statusFilter === "pending" ? "selected" : "" ?>>Pending</option>
                        <option value="refunded" <?= $statusFilter === "refunded" ? "selected" : "" ?>>Refunded</option>
                    </select>
                    <button type="submit" class="search-btn">Search</button>
                    <?php if ($search !== "" || $statusFilter !== "all"): ?>
                        <a href="index.php" class="clear-search">Clear</a>
                    <?php endif; ?>
                </form>
                <span class="payment-count"><?= $totalFilteredPayments ?> Payments</span>
            </div>
        </div>


        <div class="table-wrapper">

            <?php if (empty($payments)): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        ₹
                    </div>

                    <h3>No Payments Found</h3>

                    <p>
                        Try changing your search or status filter, or record a new payment.
                    </p>

                    <a
                        href="/gym-management/payments/add.php"
                        class="add-payment-btn"
                    >
                        + Add Payment
                    </a>

                </div>

            <?php else: ?>

                <table class="payments-table">

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Member</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Payment Date</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($payments as $payment): ?>

                            <?php
                            $status = strtolower(
                                trim($payment["status"])
                            );

                            $method = strtolower(
                                trim($payment["payment_method"])
                            );
                            ?>

                            <tr>

                                <!-- ID -->

                                <td>
                                    <span class="payment-id">
                                        #<?= htmlspecialchars(
                                            $payment["id"]
                                        ) ?>
                                    </span>
                                </td>


                                <!-- MEMBER -->

                                <td>

                                    <div class="member-info">

                                        <div class="member-avatar">
                                            <?= strtoupper(
                                                substr(
                                                    $payment["full_name"],
                                                    0,
                                                    1
                                                )
                                            ) ?>
                                        </div>

                                        <div>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $payment["full_name"]
                                                ) ?>
                                            </strong>

                                            <small>
                                                Gym Member
                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <!-- PLAN -->

                                <td>

                                    <div class="plan-info">

                                        <div class="plan-icon">
                                            ▤
                                        </div>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $payment["plan_name"]
                                            ) ?>
                                        </strong>

                                    </div>

                                </td>


                                <!-- AMOUNT -->

                                <td>

                                    <strong class="payment-amount">
                                        ₹<?= number_format(
                                            (float)$payment["amount"],
                                            2
                                        ) ?>
                                    </strong>

                                </td>


                                <!-- PAYMENT DATE -->

                                <td>
                                    <?= htmlspecialchars(
                                        $payment["payment_date"]
                                    ) ?>
                                </td>


                                <!-- PAYMENT METHOD -->

                                <td>

                                    <span class="payment-method">
                                        <?= htmlspecialchars(
                                            strtoupper($method)
                                        ) ?>
                                    </span>

                                </td>


                                <!-- TRANSACTION REFERENCE -->

                                <td>

                                    <?php if (
                                        !empty(
                                            $payment["transaction_reference"]
                                        )
                                    ): ?>

                                        <span class="transaction-reference">
                                            <?= htmlspecialchars(
                                                $payment[
                                                    "transaction_reference"
                                                ]
                                            ) ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="no-reference">
                                            —
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php if ($status === "paid"): ?>

                                        <span class="status-badge status-paid">
                                            Paid
                                        </span>

                                    <?php elseif ($status === "pending"): ?>

                                        <span class="status-badge status-pending">
                                            Pending
                                        </span>

                                    <?php elseif ($status === "refunded"): ?>

                                        <span class="status-badge status-refunded">
                                            Refunded
                                        </span>

                                    <?php else: ?>

                                        <span class="status-badge status-other">
                                            <?= htmlspecialchars(
                                                $payment["status"]
                                            ) ?>
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="action-buttons">

                                        <a
                                            href="/gym-management/payments/edit.php?id=<?= $payment['id'] ?>"
                                            class="action-edit"
                                        >
                                            Edit
                                        </a>

                                        <a
                                            href="/gym-management/payments/delete.php?id=<?= $payment['id'] ?>"
                                            class="action-delete"
                                        >
                                            Delete
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

                <div class="payment-table-footer">
                    <p class="payment-range">
                        Showing <strong><?= $showingFrom ?>–<?= $showingTo ?></strong> of
                        <strong><?= $totalFilteredPayments ?></strong> payments
                    </p>

                    <?php if ($totalPages > 1): ?>
                        <nav class="pagination" aria-label="Payment pages">
                            <?php
                            $queryParams = ["search" => $search, "status" => $statusFilter];
                            ?>
                            <?php if ($currentPage > 1): ?>
                                <a class="page-link page-prev" href="?<?= htmlspecialchars(http_build_query($queryParams + ["page" => $currentPage - 1]), ENT_QUOTES, "UTF-8") ?>">Previous</a>
                            <?php else: ?>
                                <span class="page-link disabled">Previous</span>
                            <?php endif; ?>

                            <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                                <?php if ($pageNumber === $currentPage): ?>
                                    <span class="page-link active" aria-current="page"><?= $pageNumber ?></span>
                                <?php else: ?>
                                    <a class="page-link" href="?<?= htmlspecialchars(http_build_query($queryParams + ["page" => $pageNumber]), ENT_QUOTES, "UTF-8") ?>"><?= $pageNumber ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <a class="page-link page-next" href="?<?= htmlspecialchars(http_build_query($queryParams + ["page" => $currentPage + 1]), ENT_QUOTES, "UTF-8") ?>">Next</a>
                            <?php else: ?>
                                <span class="page-link disabled">Next</span>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

        </div>

    </div>

</div>