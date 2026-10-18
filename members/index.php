<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

/*
 * Members listing with server-side search and pagination.
 * Ten members are fetched per page.
 */

$perPage = 10;
$page = filter_input(INPUT_GET, "page", FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;

$searchName = trim((string)($_GET["name"] ?? ""));
$searchEmail = trim((string)($_GET["email"] ?? ""));
$searchPhone = trim((string)($_GET["phone"] ?? ""));
$statusFilter = trim((string)($_GET["status"] ?? ""));

$allowedStatuses = ["active", "inactive"];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = "";
}

/* Summary totals reflect all members, not just the filtered page. */
$summaryStmt = $pdo->query(
    "SELECT
        COUNT(*) AS total_members,
        COALESCE(SUM(CASE WHEN LOWER(TRIM(status)) = 'active' THEN 1 ELSE 0 END), 0) AS active_members
     FROM members"
);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

$totalMembers = (int)($summary["total_members"] ?? 0);
$activeMembers = (int)($summary["active_members"] ?? 0);
$inactiveMembers = $totalMembers - $activeMembers;

/* Build filters safely. */
$where = [];
$params = [];

if ($searchName !== "") {
    $where[] = "full_name LIKE :name";
    $params[":name"] = "%" . $searchName . "%";
}

if ($searchEmail !== "") {
    $where[] = "email LIKE :email";
    $params[":email"] = "%" . $searchEmail . "%";
}

if ($searchPhone !== "") {
    $where[] = "phone LIKE :phone";
    $params[":phone"] = "%" . $searchPhone . "%";
}

if ($statusFilter !== "") {
    $where[] = "LOWER(TRIM(status)) = :status";
    $params[":status"] = $statusFilter;
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

/* Count filtered members for pagination. */
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM members $whereSql");
$countStmt->execute($params);
$filteredCount = (int)$countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($filteredCount / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

/* Fetch only the records needed for the current page. */
$listSql = "SELECT id, full_name, email, phone, join_date, status
            FROM members
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

$members = $listStmt->fetchAll(PDO::FETCH_ASSOC);

/* Preserve active filters when changing pages. */
$queryBase = [];
if ($searchName !== "") {
    $queryBase["name"] = $searchName;
}
if ($searchEmail !== "") {
    $queryBase["email"] = $searchEmail;
}
if ($searchPhone !== "") {
    $queryBase["phone"] = $searchPhone;
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

<link rel="stylesheet" href="/gym-management/css/members.css?v=2">

<div class="members-page">

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1>Members</h1>
            <p>Manage your gym members and their information.</p>
        </div>

        <a href="/gym-management/members/add.php" class="add-member-btn">
            + Add Member
        </a>
    </div>

    <!-- Success Messages -->
    <?php if (isset($_GET["added"]) && $_GET["added"] == "1"): ?>
        <div class="success-message">✓ Member added successfully!</div>
    <?php endif; ?>

    <?php if (isset($_GET["updated"]) && $_GET["updated"] == "1"): ?>
        <div class="success-message">✓ Member updated successfully!</div>
    <?php endif; ?>

    <?php if (isset($_GET["deleted"]) && $_GET["deleted"] == "1"): ?>
        <div class="success-message">✓ Member deleted successfully!</div>
    <?php endif; ?>

    <!-- Summary Cards: all members -->
    <div class="member-summary">

        <div class="summary-card">
            <div class="summary-icon">👥</div>
            <div>
                <span>Total Members</span>
                <strong><?= $totalMembers ?></strong>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon active-icon">✓</div>
            <div>
                <span>Active Members</span>
                <strong><?= $activeMembers ?></strong>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon inactive-icon">●</div>
            <div>
                <span>Inactive Members</span>
                <strong><?= $inactiveMembers ?></strong>
            </div>
        </div>

    </div>

    <!-- Members Table Card -->
    <div class="members-card">

        <div class="card-header members-list-header">
            <div class="members-list-title">
                <h2>Member List</h2>
                <p>View and manage registered gym members.</p>
            </div>

            <div class="members-list-tools">
                <span class="member-count">
                    <?= $filteredCount ?> <?= $filteredCount === 1 ? "Member" : "Members" ?>
                </span>

                <form class="members-search-form" method="GET"
                      action="/gym-management/members/index.php">

                    <label class="members-search-field">
                        <span class="members-sr-only">Search member name</span>
                        <input type="search" name="name"
                               placeholder="Search name..."
                               value="<?= htmlspecialchars($searchName, ENT_QUOTES, "UTF-8") ?>">
                    </label>

                    <label class="members-search-field">
                        <span class="members-sr-only">Search email</span>
                        <input type="search" name="email"
                               placeholder="Search email..."
                               value="<?= htmlspecialchars($searchEmail, ENT_QUOTES, "UTF-8") ?>">
                    </label>

                    <label class="members-search-field">
                        <span class="members-sr-only">Search phone</span>
                        <input type="search" name="phone"
                               placeholder="Search phone..."
                               value="<?= htmlspecialchars($searchPhone, ENT_QUOTES, "UTF-8") ?>">
                    </label>

                    <label class="members-search-field">
                        <span class="members-sr-only">Filter by status</span>
                        <select name="status" aria-label="Filter by member status">
                            <option value="">All statuses</option>
                            <option value="active" <?= $statusFilter === "active" ? "selected" : "" ?>>Active</option>
                            <option value="inactive" <?= $statusFilter === "inactive" ? "selected" : "" ?>>Inactive</option>
                        </select>
                    </label>

                    <button type="submit" class="members-search-button">Search</button>

                    <?php if ($searchName !== "" || $searchEmail !== "" || $searchPhone !== "" || $statusFilter !== ""): ?>
                        <a class="members-clear-button" href="/gym-management/members/index.php">Clear</a>
                    <?php endif; ?>

                </form>
            </div>
        </div>

        <div class="table-wrapper">

            <?php if (empty($members)): ?>

                <div class="empty-state">
                    <div class="empty-icon">👥</div>
                    <h3><?= ($searchName !== "" || $searchEmail !== "" || $searchPhone !== "" || $statusFilter !== "") ? "No Matching Members" : "No Members Found" ?></h3>
                    <p>
                        <?= ($searchName !== "" || $searchEmail !== "" || $searchPhone !== "" || $statusFilter !== "")
                            ? "Try changing your search filters."
                            : "Start by adding your first gym member." ?>
                    </p>

                    <?php if ($searchName !== "" || $searchEmail !== "" || $searchPhone !== "" || $statusFilter !== ""): ?>
                        <a href="/gym-management/members/index.php" class="add-member-btn">Clear Search</a>
                    <?php else: ?>
                        <a href="/gym-management/members/add.php" class="add-member-btn">+ Add Member</a>
                    <?php endif; ?>
                </div>

            <?php else: ?>

                <table class="members-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Member</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Join Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <?php $status = strtolower(trim((string)$member["status"])); ?>

                            <tr>
                                <td>
                                    <span class="member-id">
                                        #<?= htmlspecialchars((string)$member["id"], ENT_QUOTES, "UTF-8") ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="member-info">
                                        <div class="member-avatar">
                                            <?= htmlspecialchars(strtoupper(substr((string)$member["full_name"], 0, 1)), ENT_QUOTES, "UTF-8") ?>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars((string)$member["full_name"], ENT_QUOTES, "UTF-8") ?></strong>
                                            <small>Gym Member</small>
                                        </div>
                                    </div>
                                </td>

                                <td><?= htmlspecialchars((string)($member["email"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                <td><?= htmlspecialchars((string)($member["phone"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                <td><?= htmlspecialchars((string)($member["join_date"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>

                                <td>
                                    <?php if ($status === "active"): ?>
                                        <span class="status-badge status-active">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="action-buttons">
                                        <a href="/gym-management/members/edit.php?id=<?= (int)$member["id"] ?>"
                                           class="action-edit">Edit</a>
                                        <a href="/gym-management/members/delete.php?id=<?= (int)$member["id"] ?>"
                                           class="action-delete">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>

        </div>

        <!-- Pagination -->
        <?php if ($filteredCount > 0): ?>
            <div class="members-pagination">
                <div class="members-pagination-info">
                    Showing <strong><?= $offset + 1 ?></strong>–<strong><?= min($offset + $perPage, $filteredCount) ?></strong>
                    of <strong><?= $filteredCount ?></strong> members
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="members-pagination-controls" aria-label="Member pages">

                        <?php if ($page > 1): ?>
                            <a class="members-page-link"
                               href="<?= htmlspecialchars($buildPageUrl($page - 1), ENT_QUOTES, "UTF-8") ?>"
                               aria-label="Previous page">‹ Previous</a>
                        <?php else: ?>
                            <span class="members-page-link members-page-disabled" aria-disabled="true">‹ Previous</span>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        ?>

                        <?php if ($startPage > 1): ?>
                            <a class="members-page-link" href="<?= htmlspecialchars($buildPageUrl(1), ENT_QUOTES, "UTF-8") ?>">1</a>
                            <?php if ($startPage > 2): ?><span class="members-page-ellipsis">…</span><?php endif; ?>
                        <?php endif; ?>

                        <?php for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++): ?>
                            <?php if ($pageNumber === $page): ?>
                                <span class="members-page-link members-page-active" aria-current="page"><?= $pageNumber ?></span>
                            <?php else: ?>
                                <a class="members-page-link"
                                   href="<?= htmlspecialchars($buildPageUrl($pageNumber), ENT_QUOTES, "UTF-8") ?>"><?= $pageNumber ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?><span class="members-page-ellipsis">…</span><?php endif; ?>
                            <a class="members-page-link"
                               href="<?= htmlspecialchars($buildPageUrl($totalPages), ENT_QUOTES, "UTF-8") ?>"><?= $totalPages ?></a>
                        <?php endif; ?>

                        <?php if ($page < $totalPages): ?>
                            <a class="members-page-link"
                               href="<?= htmlspecialchars($buildPageUrl($page + 1), ENT_QUOTES, "UTF-8") ?>"
                               aria-label="Next page">Next ›</a>
                        <?php else: ?>
                            <span class="members-page-link members-page-disabled" aria-disabled="true">Next ›</span>
                        <?php endif; ?>

                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div>
