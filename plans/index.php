<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

/*
 * Membership plans list with server-side search and pagination.
 * Ten plans are fetched per page.
 */

$perPage = 10;
$page = filter_input(INPUT_GET, "page", FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;

$searchName = trim((string)($_GET["name"] ?? ""));
$searchDescription = trim((string)($_GET["description"] ?? ""));
$statusFilter = trim((string)($_GET["status"] ?? ""));

$allowedStatuses = ["active", "inactive"];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = "";
}

/* Summary counts are for all plans, independent of search filters. */
$summaryStmt = $pdo->query(
    "SELECT
        COUNT(*) AS total_plans,
        COALESCE(SUM(CASE WHEN LOWER(TRIM(status)) = 'active' THEN 1 ELSE 0 END), 0) AS active_plans
     FROM membership_plans"
);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

$totalPlans = (int)($summary["total_plans"] ?? 0);
$activePlans = (int)($summary["active_plans"] ?? 0);
$inactivePlans = $totalPlans - $activePlans;

/* Build search conditions safely. */
$where = [];
$params = [];

if ($searchName !== "") {
    $where[] = "plan_name LIKE :plan_name";
    $params[":plan_name"] = "%" . $searchName . "%";
}

if ($searchDescription !== "") {
    $where[] = "description LIKE :description";
    $params[":description"] = "%" . $searchDescription . "%";
}

if ($statusFilter !== "") {
    $where[] = "LOWER(TRIM(status)) = :status";
    $params[":status"] = $statusFilter;
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

/* Count filtered plans for pagination. */
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM membership_plans $whereSql");
$countStmt->execute($params);
$filteredCount = (int)$countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($filteredCount / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

/* Fetch only this page of plans. */
$listSql = "SELECT id, plan_name, description, price, duration_days, status
            FROM membership_plans
            $whereSql
            ORDER BY id DESC
            LIMIT :limit OFFSET :offset";
$listStmt = $pdo->prepare($listSql);

foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value, PDO::PARAM_STR);
}
$listStmt->bindValue(":limit", $perPage, PDO::PARAM_INT);
$listStmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$listStmt->execute();

$plans = $listStmt->fetchAll(PDO::FETCH_ASSOC);

/* Preserve active filters when changing pages. */
$queryBase = [];
if ($searchName !== "") {
    $queryBase["name"] = $searchName;
}
if ($searchDescription !== "") {
    $queryBase["description"] = $searchDescription;
}
if ($statusFilter !== "") {
    $queryBase["status"] = $statusFilter;
}

$buildPageUrl = static function (int $targetPage) use ($queryBase): string {
    $query = $queryBase;
    $query["page"] = $targetPage;
    return "?" . http_build_query($query);
};

require_once "../includes/header.php";
?>

<link rel="stylesheet" href="/gym-management/css/plans.css?v=2">

<div class="plans-page">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1>Membership Plans</h1>
            <p>Manage and configure your gym membership plans.</p>
        </div>

        <a href="/gym-management/plans/add.php" class="add-plan-btn">
            + Add Plan
        </a>
    </div>

    <!-- SUCCESS MESSAGES -->
    <?php if (isset($_GET["added"]) && $_GET["added"] == "1"): ?>
        <div class="success-message">✓ Membership plan added successfully!</div>
    <?php endif; ?>

    <?php if (isset($_GET["updated"]) && $_GET["updated"] == "1"): ?>
        <div class="success-message">✓ Membership plan updated successfully!</div>
    <?php endif; ?>

    <?php if (isset($_GET["deleted"]) && $_GET["deleted"] == "1"): ?>
        <div class="success-message">✓ Membership plan deleted successfully!</div>
    <?php endif; ?>

    <!-- SUMMARY CARDS -->
    <div class="plan-summary">
        <div class="summary-card">
            <div class="summary-icon">▣</div>
            <div>
                <span>Total Plans</span>
                <strong><?= $totalPlans ?></strong>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon active-icon">✓</div>
            <div>
                <span>Active Plans</span>
                <strong><?= $activePlans ?></strong>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon inactive-icon">●</div>
            <div>
                <span>Inactive Plans</span>
                <strong><?= $inactivePlans ?></strong>
            </div>
        </div>
    </div>

    <!-- PLAN TABLE -->
    <div class="plans-card">

        <div class="card-header plans-list-header">
            <div class="plans-list-title">
                <h2>Plan List</h2>
                <p>View and manage your gym membership plans.</p>
            </div>

            <div class="plans-list-tools">
                <span class="plan-count">
                    <?= $filteredCount ?> <?= $filteredCount === 1 ? "Plan" : "Plans" ?>
                </span>

                <form class="plans-search-form" method="GET" action="/gym-management/plans/index.php">
                    <label class="plans-search-field">
                        <span class="plans-sr-only">Search plan name</span>
                        <input type="search" name="name" placeholder="Search plan name..."
                               value="<?= htmlspecialchars($searchName, ENT_QUOTES, "UTF-8") ?>">
                    </label>

                    <label class="plans-search-field">
                        <span class="plans-sr-only">Search description</span>
                        <input type="search" name="description" placeholder="Search description..."
                               value="<?= htmlspecialchars($searchDescription, ENT_QUOTES, "UTF-8") ?>">
                    </label>

                    <label class="plans-search-field">
                        <span class="plans-sr-only">Filter by status</span>
                        <select name="status" aria-label="Filter by plan status">
                            <option value="">All statuses</option>
                            <option value="active" <?= $statusFilter === "active" ? "selected" : "" ?>>Active</option>
                            <option value="inactive" <?= $statusFilter === "inactive" ? "selected" : "" ?>>Inactive</option>
                        </select>
                    </label>

                    <button type="submit" class="plans-search-button">Search</button>

                    <?php if ($searchName !== "" || $searchDescription !== "" || $statusFilter !== ""): ?>
                        <a class="plans-clear-button" href="/gym-management/plans/index.php">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="table-wrapper">
            <?php if (empty($plans)): ?>
                <div class="empty-state">
                    <div class="empty-icon">▣</div>
                    <h3><?= ($searchName !== "" || $searchDescription !== "" || $statusFilter !== "") ? "No Matching Plans Found" : "No Membership Plans Found" ?></h3>
                    <p>
                        <?= ($searchName !== "" || $searchDescription !== "" || $statusFilter !== "")
                            ? "Try changing your search filters."
                            : "Start by creating your first membership plan." ?>
                    </p>

                    <?php if ($searchName !== "" || $searchDescription !== "" || $statusFilter !== ""): ?>
                        <a href="/gym-management/plans/index.php" class="add-plan-btn">Clear Search</a>
                    <?php else: ?>
                        <a href="/gym-management/plans/add.php" class="add-plan-btn">+ Add Plan</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <table class="plans-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Plan</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($plans as $plan): ?>
                            <?php $status = strtolower(trim((string)$plan["status"])); ?>
                            <tr>
                                <td>
                                    <span class="plan-id">
                                        #<?= htmlspecialchars((string)$plan["id"], ENT_QUOTES, "UTF-8") ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="plan-info">
                                        <div class="plan-icon">▣</div>
                                        <div>
                                            <strong><?= htmlspecialchars((string)$plan["plan_name"], ENT_QUOTES, "UTF-8") ?></strong>
                                            <small>Membership Plan</small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="plan-description">
                                        <?= htmlspecialchars((string)($plan["description"] ?? ""), ENT_QUOTES, "UTF-8") ?>
                                    </span>
                                </td>

                                <td>
                                    <strong class="plan-price">₹<?= number_format((float)$plan["price"], 2) ?></strong>
                                </td>

                                <td>
                                    <span class="duration-badge">
                                        <?= htmlspecialchars((string)$plan["duration_days"], ENT_QUOTES, "UTF-8") ?> Days
                                    </span>
                                </td>

                                <td>
                                    <?php if ($status === "active"): ?>
                                        <span class="status-badge status-active">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="action-buttons">
                                        <a href="/gym-management/plans/edit.php?id=<?= (int)$plan["id"] ?>" class="action-edit">Edit</a>
                                        <a href="/gym-management/plans/delete.php?id=<?= (int)$plan["id"] ?>" class="action-delete">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- PAGINATION -->
        <?php if ($filteredCount > 0): ?>
            <div class="plans-pagination">
                <div class="plans-pagination-info">
                    Showing <strong><?= $offset + 1 ?></strong>–<strong><?= min($offset + $perPage, $filteredCount) ?></strong>
                    of <strong><?= $filteredCount ?></strong> plans
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="plans-pagination-controls" aria-label="Membership plan pages">
                        <?php if ($page > 1): ?>
                            <a class="plans-page-link" href="<?= htmlspecialchars($buildPageUrl($page - 1), ENT_QUOTES, "UTF-8") ?>">‹ Previous</a>
                        <?php else: ?>
                            <span class="plans-page-link plans-page-disabled" aria-disabled="true">‹ Previous</span>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        ?>

                        <?php if ($startPage > 1): ?>
                            <a class="plans-page-link" href="<?= htmlspecialchars($buildPageUrl(1), ENT_QUOTES, "UTF-8") ?>">1</a>
                            <?php if ($startPage > 2): ?><span class="plans-page-ellipsis">…</span><?php endif; ?>
                        <?php endif; ?>

                        <?php for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++): ?>
                            <?php if ($pageNumber === $page): ?>
                                <span class="plans-page-link plans-page-active" aria-current="page"><?= $pageNumber ?></span>
                            <?php else: ?>
                                <a class="plans-page-link" href="<?= htmlspecialchars($buildPageUrl($pageNumber), ENT_QUOTES, "UTF-8") ?>"><?= $pageNumber ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?><span class="plans-page-ellipsis">…</span><?php endif; ?>
                            <a class="plans-page-link" href="<?= htmlspecialchars($buildPageUrl($totalPages), ENT_QUOTES, "UTF-8") ?>"><?= $totalPages ?></a>
                        <?php endif; ?>

                        <?php if ($page < $totalPages): ?>
                            <a class="plans-page-link" href="<?= htmlspecialchars($buildPageUrl($page + 1), ENT_QUOTES, "UTF-8") ?>">Next ›</a>
                        <?php else: ?>
                            <span class="plans-page-link plans-page-disabled" aria-disabled="true">Next ›</span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div>
