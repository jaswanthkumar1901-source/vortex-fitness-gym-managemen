<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

/*
 * Attendance history
 * - Server-side search by member name
 * - Optional exact check-in date filter
 * - Server-side pagination (10 records per page)
 */

$perPage = 10;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;

$searchName = trim((string)($_GET['name'] ?? ''));
$searchDate = trim((string)($_GET['date'] ?? ''));

// Validate date as YYYY-MM-DD. Ignore invalid date input safely.
if ($searchDate !== '') {
    $dateObject = DateTime::createFromFormat('!Y-m-d', $searchDate);
    $dateErrors = DateTime::getLastErrors();
    $validDate = $dateObject &&
        $dateObject->format('Y-m-d') === $searchDate &&
        ($dateErrors === false ||
            ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0));

    if (!$validDate) {
        $searchDate = '';
    }
}

/* Summary cards use all attendance records, not just the current search. */
$summaryStmt = $pdo->query(
    "SELECT
        COUNT(*) AS total_attendance,
        COALESCE(SUM(CASE WHEN DATE(check_in) = CURDATE() THEN 1 ELSE 0 END), 0) AS today_attendance,
        COALESCE(SUM(CASE WHEN check_out IS NULL THEN 1 ELSE 0 END), 0) AS current_check_ins
     FROM attendance"
);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

$totalAttendance = (int)($summary['total_attendance'] ?? 0);
$todayAttendance = (int)($summary['today_attendance'] ?? 0);
$currentCheckIns = (int)($summary['current_check_ins'] ?? 0);

/* Build safe search conditions. */
$where = [];
$params = [];

if ($searchName !== '') {
    $where[] = "members.full_name LIKE :member_name";
    $params[':member_name'] = '%' . $searchName . '%';
}

if ($searchDate !== '') {
    $where[] = "DATE(attendance.check_in) = :check_in_date";
    $params[':check_in_date'] = $searchDate;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* Count filtered results for pagination. */
$countSql = "SELECT COUNT(*)
             FROM attendance
             JOIN members ON attendance.member_id = members.id
             $whereSql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$filteredCount = (int)$countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($filteredCount / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

/* Fetch only the records needed for this page. */
$listSql = "SELECT
                attendance.id,
                members.full_name,
                attendance.check_in,
                attendance.check_out
            FROM attendance
            JOIN members ON attendance.member_id = members.id
            $whereSql
            ORDER BY attendance.id DESC
            LIMIT :limit OFFSET :offset";

$listStmt = $pdo->prepare($listSql);

foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value, PDO::PARAM_STR);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();

$attendance = $listStmt->fetchAll(PDO::FETCH_ASSOC);

/* Build pagination URLs while preserving active filters. */
$queryBase = [];
if ($searchName !== '') {
    $queryBase['name'] = $searchName;
}
if ($searchDate !== '') {
    $queryBase['date'] = $searchDate;
}

$buildPageUrl = static function (int $targetPage) use ($queryBase): string {
    $query = $queryBase;
    $query['page'] = $targetPage;
    return '?' . http_build_query($query);
};

/* Header */
require_once "../includes/header.php";
?>

<link rel="stylesheet" href="/gym-management/css/attendance.css?v=3">

<div class="attendance-page">

    <div class="page-header">
        <div>
            <h1>Attendance</h1>
            <p>Track member check-ins and check-outs.</p>
        </div>

        <a href="/gym-management/attendance/add.php" class="add-attendance-btn">
            + Check In
        </a>
    </div>

    <?php if (isset($_GET["added"]) && $_GET["added"] == "1"): ?>
        <div class="success-message">✓ Attendance added successfully!</div>
    <?php endif; ?>

    <?php if (isset($_GET["updated"]) && $_GET["updated"] == "1"): ?>
        <div class="success-message">✓ Attendance updated successfully!</div>
    <?php endif; ?>

    <?php if (isset($_GET["deleted"]) && $_GET["deleted"] == "1"): ?>
        <div class="success-message">✓ Attendance deleted successfully!</div>
    <?php endif; ?>

    <?php if (isset($_GET["checkout"]) && $_GET["checkout"] == "1"): ?>
        <div class="success-message">✓ Member checked out successfully!</div>
    <?php endif; ?>

    <div class="attendance-summary">
        <div class="summary-card">
            <div class="summary-icon">✓</div>
            <div>
                <span>Total Attendance</span>
                <strong><?= $totalAttendance ?></strong>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon today-icon">◷</div>
            <div>
                <span>Today's Check-ins</span>
                <strong><?= $todayAttendance ?></strong>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon current-icon">●</div>
            <div>
                <span>Currently Checked In</span>
                <strong><?= $currentCheckIns ?></strong>
            </div>
        </div>
    </div>

    <div class="attendance-card">

        <!-- Header and search controls in the circled area -->
        <div class="card-header attendance-list-header">
            <div class="attendance-list-title">
                <h2>Attendance List</h2>
                <p>View and manage member attendance records.</p>
            </div>

            <div class="attendance-list-tools">
                <span class="attendance-count">
                    <?= $filteredCount ?> <?= $filteredCount === 1 ? 'Record' : 'Records' ?>
                </span>

                <form class="attendance-search-form" method="GET"
                      action="/gym-management/attendance/index.php">
                    <label class="attendance-search-field">
                        <span class="sr-only">Search member name</span>
                        <input
                            type="search"
                            name="name"
                            placeholder="Search member name..."
                            value="<?= htmlspecialchars($searchName, ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </label>

                    <label class="attendance-search-field attendance-date-field">
                        <span class="sr-only">Filter by check-in date</span>
                        <input
                            type="date"
                            name="date"
                            aria-label="Filter by check-in date"
                            value="<?= htmlspecialchars($searchDate, ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </label>

                    <button type="submit" class="attendance-search-button">Search</button>

                    <?php if ($searchName !== '' || $searchDate !== ''): ?>
                        <a class="attendance-clear-button"
                           href="/gym-management/attendance/index.php">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="table-wrapper">
            <?php if (empty($attendance)): ?>
                <div class="empty-state">
                    <div class="empty-icon">✓</div>
                    <h3><?= ($searchName !== '' || $searchDate !== '') ? 'No Matching Records' : 'No Attendance Records' ?></h3>
                    <p>
                        <?= ($searchName !== '' || $searchDate !== '')
                            ? 'Try another member name or date, or clear your filters.'
                            : 'Start by checking in a gym member.' ?>
                    </p>

                    <?php if ($searchName !== '' || $searchDate !== ''): ?>
                        <a href="/gym-management/attendance/index.php" class="add-attendance-btn">
                            Clear Search
                        </a>
                    <?php else: ?>
                        <a href="/gym-management/attendance/add.php" class="add-attendance-btn">
                            + Check In
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Member</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($attendance as $record): ?>
                            <tr>
                                <td>
                                    <span class="attendance-id">
                                        #<?= htmlspecialchars((string)$record['id'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="member-info">
                                        <div class="member-avatar">
                                            <?= htmlspecialchars(
                                                strtoupper(mb_substr((string)$record['full_name'], 0, 1)),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars((string)$record['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <small>Gym Member</small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="time-value">
                                        <?= htmlspecialchars((string)$record['check_in'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if (!empty($record['check_out'])): ?>
                                        <span class="time-value">
                                            <?= htmlspecialchars((string)$record['check_out'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="not-checked-out">—</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($record['check_out'] === null): ?>
                                        <span class="status-badge status-present">Checked In</span>
                                    <?php else: ?>
                                        <span class="status-badge status-completed">Completed</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="action-buttons">
                                        <?php if ($record['check_out'] === null): ?>
                                            <a
                                                href="/gym-management/attendance/checkout.php?id=<?= (int)$record['id'] ?>"
                                                class="action-checkout"
                                            >Check Out</a>
                                        <?php endif; ?>

                                        <a
                                            href="/gym-management/attendance/edit.php?id=<?= (int)$record['id'] ?>"
                                            class="action-edit"
                                        >Edit</a>

                                        <a
                                            href="/gym-management/attendance/delete.php?id=<?= (int)$record['id'] ?>"
                                            class="action-delete"
                                        >Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php if ($filteredCount > 0): ?>
            <div class="attendance-pagination">
                <div class="pagination-info">
                    Showing
                    <strong><?= $offset + 1 ?></strong>–<strong><?= min($offset + $perPage, $filteredCount) ?></strong>
                    of <strong><?= $filteredCount ?></strong> records
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="pagination-controls" aria-label="Attendance pages">
                        <?php if ($page > 1): ?>
                            <a class="pagination-link pagination-prev"
                               href="<?= htmlspecialchars($buildPageUrl($page - 1), ENT_QUOTES, 'UTF-8') ?>"
                               aria-label="Previous page">‹ Previous</a>
                        <?php else: ?>
                            <span class="pagination-link pagination-disabled" aria-disabled="true">‹ Previous</span>
                        <?php endif; ?>

                        <?php
                        // Show a compact page range when there are many pages.
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);

                        if ($startPage > 1):
                        ?>
                            <a class="pagination-link" href="<?= htmlspecialchars($buildPageUrl(1), ENT_QUOTES, 'UTF-8') ?>">1</a>
                            <?php if ($startPage > 2): ?><span class="pagination-ellipsis">…</span><?php endif; ?>
                        <?php endif; ?>

                        <?php for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++): ?>
                            <?php if ($pageNumber === $page): ?>
                                <span class="pagination-link pagination-active" aria-current="page"><?= $pageNumber ?></span>
                            <?php else: ?>
                                <a class="pagination-link"
                                   href="<?= htmlspecialchars($buildPageUrl($pageNumber), ENT_QUOTES, 'UTF-8') ?>"><?= $pageNumber ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?><span class="pagination-ellipsis">…</span><?php endif; ?>
                            <a class="pagination-link"
                               href="<?= htmlspecialchars($buildPageUrl($totalPages), ENT_QUOTES, 'UTF-8') ?>"><?= $totalPages ?></a>
                        <?php endif; ?>

                        <?php if ($page < $totalPages): ?>
                            <a class="pagination-link pagination-next"
                               href="<?= htmlspecialchars($buildPageUrl($page + 1), ENT_QUOTES, 'UTF-8') ?>"
                               aria-label="Next page">Next ›</a>
                        <?php else: ?>
                            <span class="pagination-link pagination-disabled" aria-disabled="true">Next ›</span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div>
