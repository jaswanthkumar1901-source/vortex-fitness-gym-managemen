<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

/* Automatically mark expired memberships */

$stmt = $pdo->query("
    UPDATE memberships
    SET status = 'expired'
    WHERE status = 'active'
    AND end_date < CURDATE()
");

/* Fetch membership history */
$stmt = $pdo->query("
    SELECT memberships.id, members.full_name, membership_plans.plan_name, memberships.start_date, memberships.end_date, memberships.status
    FROM memberships
    JOIN members ON memberships.member_id = members.id
    JOIN membership_plans ON memberships.plan_id = membership_plans.id
    ORDER BY memberships.id DESC
");
$allMemberships = $stmt->fetchAll(PDO::FETCH_ASSOC);
$search = trim($_GET["q"] ?? "");
$statusFilter = strtolower(trim($_GET["status"] ?? ""));
$memberships = array_values(array_filter($allMemberships, function ($membership) use ($search, $statusFilter) {
    $matchesSearch = $search === "" || stripos((string)$membership["id"], $search) !== false || stripos($membership["full_name"], $search) !== false || stripos($membership["plan_name"], $search) !== false || stripos($membership["start_date"], $search) !== false || stripos($membership["end_date"], $search) !== false || stripos($membership["status"], $search) !== false;
    $matchesStatus = $statusFilter === "" || strtolower(trim($membership["status"])) === $statusFilter;
    return $matchesSearch && $matchesStatus;
}));
$totalMemberships = count($allMemberships);
$activeMemberships = 0;
$expiredMemberships = 0;
$cancelledMemberships = 0;
foreach ($allMemberships as $membership) {
    $status = strtolower(trim($membership["status"]));
    if ($status === "active") {
        $activeMemberships++;
    } elseif ($status === "expired") {
        $expiredMemberships++;
    } elseif ($status === "cancelled") {
        $cancelledMemberships++;
    }
}
$filteredCount = count($memberships);

/* Pagination */
$perPage = 10;
$totalPages = max(1, (int)ceil($filteredCount / $perPage));
$currentPage = max(1, (int)($_GET["page"] ?? 1));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;
$paginatedMemberships = array_slice($memberships, $offset, $perPage);
$startRecord = $filteredCount > 0 ? $offset + 1 : 0;
$endRecord = min($offset + $perPage, $filteredCount);

/* Load header */

require_once "../includes/header.php";

?>

<link rel="stylesheet" href="/gym-management/css/membership.css?v=6">

<div class="membership-page">

    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>
            <h1>Memberships</h1>

            <p>
                Manage member subscriptions and membership validity.
            </p>
        </div>

        <a
            href="/gym-management/membership/add.php"
            class="add-membership-btn"
        >
            + Add Membership
        </a>

    </div>

    <!-- SUCCESS MESSAGES -->

    <?php if (isset($_GET["added"]) && $_GET["added"] == "1"): ?>

        <div class="success-message">
            ✓ Membership added successfully!
        </div>

    <?php endif; ?>

    <?php if (isset($_GET["updated"]) && $_GET["updated"] == "1"): ?>

        <div class="success-message">
            ✓ Membership updated successfully!
        </div>

    <?php endif; ?>

    <?php if (isset($_GET["deleted"]) && $_GET["deleted"] == "1"): ?>

        <div class="success-message">
            ✓ Membership deleted successfully!
        </div>

    <?php endif; ?>

    <!-- SUMMARY CARDS -->

    <div class="membership-summary">

        <div class="summary-card">

            <div class="summary-icon">
                ▤
            </div>

            <div>
                <span>Total Memberships</span>
                <strong><?= $totalMemberships ?></strong>
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-icon active-icon">
                ✓
            </div>

            <div>
                <span>Active Memberships</span>
                <strong><?= $activeMemberships ?></strong>
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-icon expired-icon">
                !
            </div>

            <div>
                <span>Expired Memberships</span>
                <strong><?= $expiredMemberships ?></strong>
            </div>

        </div>

    </div>

    <!-- MEMBERSHIP LIST -->

    <div class="membership-card">

        <div class="card-header">

            <div>
                <h2>Membership List</h2>

                <p>
                    View and manage member subscriptions.
                </p>
            </div>

            <div class="membership-tools">
                <form class="membership-search" method="get" action="">
                    <label class="visually-hidden" for="membership-search">Search membership history</label>
                    <span class="search-symbol" aria-hidden="true">⌕</span>
                    <input id="membership-search" type="search" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>" placeholder="Search member, plan, ID, date..." autocomplete="off">
                    <select name="status" aria-label="Filter by membership status">
                        <option value="">All statuses</option>
                        <option value="active" <?= $statusFilter === "active" ? "selected" : "" ?>>Active</option>
                        <option value="expired" <?= $statusFilter === "expired" ? "selected" : "" ?>>Expired</option>
                        <option value="cancelled" <?= $statusFilter === "cancelled" ? "selected" : "" ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="search-btn">Search</button>
                    <?php if ($search !== "" || $statusFilter !== ""): ?>
                        <a class="clear-search" href="/gym-management/membership/index.php">Clear</a>
                    <?php endif; ?>
                </form>
                <span class="membership-count"><?= $filteredCount ?> Memberships</span>
            </div>

        </div>

        <div class="table-wrapper">

            <?php if (empty($memberships)): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        ▤
                    </div>

                    <h3><?= $search !== "" || $statusFilter !== "" ? "No Matching Memberships" : "No Memberships Found" ?></h3>
                    <p><?= $search !== "" || $statusFilter !== "" ? "Try another search term or clear the filters." : "Start by assigning a membership plan to a member." ?></p>
                    <?php if ($search !== "" || $statusFilter !== ""): ?>
                        <a href="/gym-management/membership/index.php" class="add-membership-btn">Clear Search</a>
                    <?php else: ?>
                        <a href="/gym-management/membership/add.php" class="add-membership-btn">+ Add Membership</a>
                    <?php endif; ?>

                </div>

            <?php else: ?>

                <table class="membership-table">

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Member</th>
                            <th>Plan</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($paginatedMemberships as $membership): ?>

                            <?php
                            $status = strtolower(
                                trim($membership["status"])
                            );
                            ?>

                            <tr>

                                <!-- ID -->

                                <td>
                                    <span class="membership-id">
                                        #<?= htmlspecialchars(
                                            $membership["id"]
                                        ) ?>
                                    </span>
                                </td>

                                <!-- MEMBER -->

                                <td>

                                    <div class="member-info">

                                        <div class="member-avatar">
                                            <?= strtoupper(
                                                substr(
                                                    $membership["full_name"],
                                                    0,
                                                    1
                                                )
                                            ) ?>
                                        </div>

                                        <div>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $membership["full_name"]
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
                                                $membership["plan_name"]
                                            ) ?>
                                        </strong>

                                    </div>

                                </td>

                                <!-- START DATE -->

                                <td>
                                    <?= htmlspecialchars(
                                        $membership["start_date"]
                                    ) ?>
                                </td>

                                <!-- END DATE -->

                                <td>
                                    <?= htmlspecialchars(
                                        $membership["end_date"]
                                    ) ?>
                                </td>

                                <!-- STATUS -->

                                <td>

                                    <?php if ($status === "active"): ?>

                                        <span class="status-badge status-active">
                                            Active
                                        </span>

                                    <?php elseif ($status === "expired"): ?>

                                        <span class="status-badge status-expired">
                                            Expired
                                        </span>

                                    <?php elseif ($status === "cancelled"): ?>

                                        <span class="status-badge status-cancelled">
                                            Cancelled
                                        </span>

                                    <?php else: ?>

                                        <span class="status-badge status-other">
                                            <?= htmlspecialchars(
                                                $membership["status"]
                                            ) ?>
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <!-- ACTIONS -->

                                <td>

                                    <div class="action-buttons">

                                        <a
                                            href="/gym-management/membership/edit.php?id=<?= $membership['id'] ?>"
                                            class="action-edit"
                                        >
                                            Edit
                                        </a>

                                        <a
                                            href="/gym-management/membership/delete.php?id=<?= $membership['id'] ?>"
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

            <?php endif; ?>

        </div>

        <?php if ($filteredCount > 0): ?>
            <div class="membership-pagination">
                <div class="pagination-info">
                    Showing <strong><?= $startRecord ?>–<?= $endRecord ?></strong> of <strong><?= $filteredCount ?></strong> memberships
                </div>
                <?php if ($totalPages > 1): ?>
                    <nav class="pagination-controls" aria-label="Membership pages">
                        <?php
                        $queryParams = $_GET;
                        unset($queryParams["page"]);
                        $queryBase = http_build_query($queryParams);
                        $pageUrl = "/gym-management/membership/index.php" . ($queryBase !== "" ? "?" . $queryBase . "&" : "?") . "page=";
                        ?>
                        <?php if ($currentPage > 1): ?>
                            <a class="pagination-link pagination-prev" href="<?= htmlspecialchars($pageUrl . ($currentPage - 1), ENT_QUOTES, "UTF-8") ?>" aria-label="Previous page">‹ Previous</a>
                        <?php else: ?>
                            <span class="pagination-link pagination-disabled" aria-disabled="true">‹ Previous</span>
                        <?php endif; ?>

                        <?php
                        $firstPage = max(1, $currentPage - 2);
                        $lastPage = min($totalPages, $currentPage + 2);
                        if ($firstPage > 1):
                        ?>
                            <a class="pagination-link" href="<?= htmlspecialchars($pageUrl . "1", ENT_QUOTES, "UTF-8") ?>">1</a>
                            <?php if ($firstPage > 2): ?><span class="pagination-ellipsis">…</span><?php endif; ?>
                        <?php endif; ?>

                        <?php for ($pageNumber = $firstPage; $pageNumber <= $lastPage; $pageNumber++): ?>
                            <?php if ($pageNumber === $currentPage): ?>
                                <span class="pagination-link pagination-current" aria-current="page"><?= $pageNumber ?></span>
                            <?php else: ?>
                                <a class="pagination-link" href="<?= htmlspecialchars($pageUrl . $pageNumber, ENT_QUOTES, "UTF-8") ?>"><?= $pageNumber ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($lastPage < $totalPages): ?>
                            <?php if ($lastPage < $totalPages - 1): ?><span class="pagination-ellipsis">…</span><?php endif; ?>
                            <a class="pagination-link" href="<?= htmlspecialchars($pageUrl . $totalPages, ENT_QUOTES, "UTF-8") ?>"><?= $totalPages ?></a>
                        <?php endif; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <a class="pagination-link pagination-next" href="<?= htmlspecialchars($pageUrl . ($currentPage + 1), ENT_QUOTES, "UTF-8") ?>" aria-label="Next page">Next ›</a>
                        <?php else: ?>
                            <span class="pagination-link pagination-disabled" aria-disabled="true">Next ›</span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

</div>
