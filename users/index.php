<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

if (($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

$search = trim($_GET["search"] ?? "");
$page = max(1, (int)($_GET["page"] ?? 1));
$perPage = 10;

$where = "";
$params = [];
if ($search !== "") {
    $where = " WHERE CAST(id AS CHAR) LIKE :search_id OR username LIKE :search_username OR role LIKE :search_role OR DATE_FORMAT(created_at, '%d %b %Y') LIKE :search_date OR CAST(created_at AS CHAR) LIKE :search_created ";
    $term = "%" . $search . "%";
    $params = [
        ":search_id" => $term,
        ":search_username" => $term,
        ":search_role" => $term,
        ":search_date" => $term,
        ":search_created" => $term
    ];
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users" . $where);
$countStmt->execute($params);
$filteredTotal = (int)$countStmt->fetchColumn();

$totalStmt = $pdo->query("SELECT COUNT(*) FROM users");
$totalUsers = (int)$totalStmt->fetchColumn();

$totalPages = max(1, (int)ceil($filteredTotal / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT id, username, role, created_at FROM users" . $where . " ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(":limit", $perPage, PDO::PARAM_INT);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$startRecord = $filteredTotal > 0 ? $offset + 1 : 0;
$endRecord = min($offset + $perPage, $filteredTotal);

require_once "../includes/header.php";
?>
<link rel="stylesheet" href="/gym-management/css/users.css?v=5">

<div class="users-page">
    <div class="users-page-header">
        <div>
            <span class="users-page-label">ACCOUNT MANAGEMENT</span>
            <h1>User Management</h1>
            <p>Manage administrator, trainer and member login accounts.</p>
        </div>
        <a href="add.php" class="users-add-button">+ Create User</a>
    </div>

    <?php if (isset($_GET["updated"])): ?>
        <div class="users-success-message">User account updated successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET["deleted"])): ?>
        <div class="users-success-message">User account deleted successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET["error"])): ?>
        <div class="users-error-message">
            <?php
            $error = $_GET["error"];
            if ($error === "self_delete") {
                echo "You cannot delete your own account.";
            } elseif ($error === "user_not_found") {
                echo "User account was not found.";
            } elseif ($error === "invalid_user") {
                echo "Invalid user ID.";
            } elseif ($error === "delete_failed") {
                echo "Unable to delete this user account.";
            } elseif ($error === "has_records") {
                echo "This user cannot be deleted because related records exist.";
            } else {
                echo "Something went wrong.";
            }
            ?>
        </div>
    <?php endif; ?>

    <div class="users-summary">
        <div class="users-summary-card">
            <span>Total Users</span>
            <strong><?= $totalUsers ?></strong>
        </div>
    </div>

    <div class="users-card">
        <div class="users-card-header">
            <div class="users-card-heading">
                <h2>Login Accounts</h2>
                <p>All registered system users.</p>
            </div>
            <div class="users-list-tools">
                <form method="GET" action="index.php" class="users-search-form">
                    <label class="visually-hidden" for="users-search">Search users</label>
                    <input
                        type="search"
                        id="users-search"
                        name="search"
                        value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
                        placeholder="Search username, role, ID, date..."
                    >
                    <button type="submit" class="users-search-button">Search</button>
                    <?php if ($search !== ""): ?>
                        <a href="index.php" class="users-clear-button">Clear</a>
                    <?php endif; ?>
                </form>
                <span class="users-record-count">
                    <?= $filteredTotal === 1 ? "1 user" : $filteredTotal . " users" ?>
                </span>
            </div>
        </div>

        <?php if (!empty($users)): ?>
            <div class="users-table-wrapper">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php
                            $role = strtolower(trim($user["role"] ?? ""));
                            if ($role === "admin") {
                                $roleClass = "role-admin";
                                $roleName = "Admin";
                            } elseif ($role === "trainer") {
                                $roleClass = "role-trainer";
                                $roleName = "Trainer";
                            } else {
                                $roleClass = "role-member";
                                $roleName = "Member";
                            }
                            ?>
                            <tr>
                                <td class="user-id">#<?= (int)$user["id"] ?></td>
                                <td><strong><?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?></strong></td>
                                <td>
                                    <span class="user-role <?= htmlspecialchars($roleClass, ENT_QUOTES, "UTF-8") ?>">
                                        <?= htmlspecialchars($roleName, ENT_QUOTES, "UTF-8") ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($user["created_at"])): ?>
                                        <?= htmlspecialchars(date("d M Y", strtotime($user["created_at"])), ENT_QUOTES, "UTF-8") ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="user-actions">
                                        <a href="edit.php?id=<?= (int)$user["id"] ?>" class="user-edit-button">Edit</a>
                                        <?php if ((int)$user["id"] !== (int)($_SESSION["user_id"] ?? 0)): ?>
                                            <a href="delete.php?id=<?= (int)$user["id"] ?>" class="user-delete-button">Delete</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="users-pagination-footer">
                <p class="users-showing">
                    Showing <strong><?= $startRecord ?>–<?= $endRecord ?></strong> of <strong><?= $filteredTotal ?></strong> users
                    <?php if ($search !== ""): ?> for <strong><?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?></strong><?php endif; ?>
                </p>
                <?php if ($totalPages > 1): ?>
                    <nav class="users-pagination" aria-label="User list pages">
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(["search" => $search, "page" => $page - 1]) ?>" class="users-page-link">Previous</a>
                        <?php else: ?>
                            <span class="users-page-link disabled">Previous</span>
                        <?php endif; ?>

                        <?php
                        $firstPage = max(1, $page - 2);
                        $lastPage = min($totalPages, $page + 2);
                        if ($firstPage > 1):
                        ?>
                            <a href="?<?= http_build_query(["search" => $search, "page" => 1]) ?>" class="users-page-link">1</a>
                            <?php if ($firstPage > 2): ?><span class="users-page-ellipsis">…</span><?php endif; ?>
                        <?php endif; ?>

                        <?php for ($pageNumber = $firstPage; $pageNumber <= $lastPage; $pageNumber++): ?>
                            <?php if ($pageNumber === $page): ?>
                                <span class="users-page-link active" aria-current="page"><?= $pageNumber ?></span>
                            <?php else: ?>
                                <a href="?<?= http_build_query(["search" => $search, "page" => $pageNumber]) ?>" class="users-page-link"><?= $pageNumber ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($lastPage < $totalPages): ?>
                            <?php if ($lastPage < $totalPages - 1): ?><span class="users-page-ellipsis">…</span><?php endif; ?>
                            <a href="?<?= http_build_query(["search" => $search, "page" => $totalPages]) ?>" class="users-page-link"><?= $totalPages ?></a>
                        <?php endif; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= http_build_query(["search" => $search, "page" => $page + 1]) ?>" class="users-page-link">Next</a>
                        <?php else: ?>
                            <span class="users-page-link disabled">Next</span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="users-empty-state">
                <div class="users-empty-icon">👤</div>
                <h3><?= $search !== "" ? "No Matching Users" : "No Users Found" ?></h3>
                <p><?= $search !== "" ? "Try another username, role, ID or date." : "No login accounts have been created yet." ?></p>
                <?php if ($search !== ""): ?>
                    <a href="index.php" class="users-add-button">Clear Search</a>
                <?php else: ?>
                    <a href="add.php" class="users-add-button">+ Create First User</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
