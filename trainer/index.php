<?php

require_once "../includes/auth.php";
require_once "../config/database.php";


/* Fetch trainers */

$stmt = $pdo->query("
    SELECT
        id,
        full_name,
        email,
        phone,
        specialization,
        status
    FROM trainers
    ORDER BY id DESC
");

$allTrainers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$search = trim($_GET["search"] ?? "");
$statusFilter = strtolower(trim($_GET["status"] ?? "all"));
if (!in_array($statusFilter, ["all", "active", "inactive"], true)) {
    $statusFilter = "all";
}

$filteredTrainers = array_values(array_filter($allTrainers, function ($trainer) use ($search, $statusFilter) {
    $status = strtolower(trim($trainer["status"] ?? ""));
    if ($statusFilter !== "all" && $status !== $statusFilter) {
        return false;
    }
    if ($search === "") {
        return true;
    }
    $searchable = implode(" ", [
        $trainer["id"] ?? "",
        $trainer["full_name"] ?? "",
        $trainer["email"] ?? "",
        $trainer["phone"] ?? "",
        $trainer["specialization"] ?? "",
        $trainer["status"] ?? ""
    ]);
    return stripos($searchable, $search) !== false;
}));

$perPage = 10;
$totalFilteredTrainers = count($filteredTrainers);
$totalPages = max(1, (int) ceil($totalFilteredTrainers / $perPage));
$currentPage = filter_input(INPUT_GET, "page", FILTER_VALIDATE_INT);
$currentPage = max(1, min($totalPages, $currentPage ?: 1));
$offset = ($currentPage - 1) * $perPage;
$trainers = array_slice($filteredTrainers, $offset, $perPage);
$showingFrom = $totalFilteredTrainers > 0 ? $offset + 1 : 0;
$showingTo = min($offset + $perPage, $totalFilteredTrainers);


/* Trainer summary */

$totalTrainers = count($allTrainers);
$activeTrainers = 0;
$inactiveTrainers = 0;

foreach ($allTrainers as $trainer) {

    $status = strtolower(trim($trainer["status"]));

    if ($status === "active") {
        $activeTrainers++;
    } else {
        $inactiveTrainers++;
    }
}


/* Header */

require_once "../includes/header.php";

?>

<link rel="stylesheet" href="/gym-management/css/trainer.css?v=3">


<div class="trainers-page">

    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <h1>Trainers</h1>

            <p>
                Manage gym trainers and their specializations.
            </p>

        </div>


        <a
            href="/gym-management/trainer/add.php"
            class="add-trainer-btn"
        >
            + Add Trainer
        </a>

    </div>


    <!-- SUCCESS MESSAGES -->

    <?php if (isset($_GET["added"]) && $_GET["added"] == "1"): ?>

        <div class="success-message">
            ✓ Trainer added successfully!
        </div>

    <?php endif; ?>


    <?php if (isset($_GET["updated"]) && $_GET["updated"] == "1"): ?>

        <div class="success-message">
            ✓ Trainer updated successfully!
        </div>

    <?php endif; ?>


    <?php if (isset($_GET["deleted"]) && $_GET["deleted"] == "1"): ?>

        <div class="success-message">
            ✓ Trainer deleted successfully!
        </div>

    <?php endif; ?>


    <!-- SUMMARY CARDS -->

    <div class="trainer-summary">

        <!-- TOTAL -->

        <div class="summary-card">

            <div class="summary-icon">
                ★
            </div>

            <div>

                <span>Total Trainers</span>

                <strong>
                    <?= $totalTrainers ?>
                </strong>

            </div>

        </div>


        <!-- ACTIVE -->

        <div class="summary-card">

            <div class="summary-icon active-icon">
                ✓
            </div>

            <div>

                <span>Active Trainers</span>

                <strong>
                    <?= $activeTrainers ?>
                </strong>

            </div>

        </div>


        <!-- INACTIVE -->

        <div class="summary-card">

            <div class="summary-icon inactive-icon">
                ●
            </div>

            <div>

                <span>Inactive Trainers</span>

                <strong>
                    <?= $inactiveTrainers ?>
                </strong>

            </div>

        </div>

    </div>


    <!-- TRAINER LIST -->

    <div class="trainers-card">

        <div class="card-header">
            <div class="trainer-heading">
                <h2>Trainer List</h2>
                <p>View and manage registered gym trainers.</p>
            </div>

            <div class="trainer-tools">
                <form method="GET" class="trainer-search-form">
                    <label class="visually-hidden" for="trainer-search">Search trainers</label>
                    <input
                        type="search"
                        id="trainer-search"
                        name="search"
                        value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
                        placeholder="Search name, email, phone, specialization..."
                    >
                    <label class="visually-hidden" for="trainer-status">Trainer status</label>
                    <select id="trainer-status" name="status">
                        <option value="all" <?= $statusFilter === "all" ? "selected" : "" ?>>All statuses</option>
                        <option value="active" <?= $statusFilter === "active" ? "selected" : "" ?>>Active</option>
                        <option value="inactive" <?= $statusFilter === "inactive" ? "selected" : "" ?>>Inactive</option>
                    </select>
                    <button type="submit" class="trainer-search-btn">Search</button>
                    <?php if ($search !== "" || $statusFilter !== "all"): ?>
                        <a href="index.php" class="trainer-clear-btn">Clear</a>
                    <?php endif; ?>
                </form>
                <span class="trainer-count"><?= $totalFilteredTrainers ?> Trainers</span>
            </div>
        </div>


        <div class="table-wrapper">

            <?php if (empty($trainers)): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        ★
                    </div>

                    <h3>No Trainers Found</h3>

                    <p>
                        No trainers match your search. Try changing the search or status filter.
                    </p>

                    <a
                        href="/gym-management/trainer/add.php"
                        class="add-trainer-btn"
                    >
                        + Add Trainer
                    </a>

                </div>

            <?php else: ?>

                <table class="trainers-table">

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Trainer</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Specialization</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($trainers as $trainer): ?>

                            <?php
                            $status = strtolower(
                                trim($trainer["status"])
                            );
                            ?>

                            <tr>

                                <!-- ID -->

                                <td>

                                    <span class="trainer-id">
                                        #<?= htmlspecialchars(
                                            $trainer["id"]
                                        ) ?>
                                    </span>

                                </td>


                                <!-- TRAINER -->

                                <td>

                                    <div class="trainer-info">

                                        <div class="trainer-avatar">

                                            <?= strtoupper(
                                                substr(
                                                    $trainer["full_name"],
                                                    0,
                                                    1
                                                )
                                            ) ?>

                                        </div>

                                        <div>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $trainer["full_name"]
                                                ) ?>
                                            </strong>

                                            <small>
                                                Gym Trainer
                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <!-- EMAIL -->

                                <td>
                                    <?= htmlspecialchars(
                                        $trainer["email"] ?? ""
                                    ) ?>
                                </td>


                                <!-- PHONE -->

                                <td>
                                    <?= htmlspecialchars(
                                        $trainer["phone"] ?? ""
                                    ) ?>
                                </td>


                                <!-- SPECIALIZATION -->

                                <td>

                                    <?php if (
                                        !empty(
                                            $trainer["specialization"]
                                        )
                                    ): ?>

                                        <span class="specialization-badge">
                                            <?= htmlspecialchars(
                                                $trainer["specialization"]
                                            ) ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="no-specialization">
                                            —
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php if ($status === "active"): ?>

                                        <span class="status-badge status-active">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="status-badge status-inactive">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="action-buttons">

                                        <a
                                            href="/gym-management/trainer/edit.php?id=<?= $trainer['id'] ?>"
                                            class="action-edit"
                                        >
                                            Edit
                                        </a>

                                        <a
                                            href="/gym-management/trainer/delete.php?id=<?= $trainer['id'] ?>"
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

                <div class="trainer-table-footer">
                    <p class="trainer-range">
                        Showing <strong><?= $showingFrom ?>–<?= $showingTo ?></strong> of
                        <strong><?= $totalFilteredTrainers ?></strong> trainers
                    </p>
                    <?php if ($totalPages > 1): ?>
                        <nav class="trainer-pagination" aria-label="Trainer pages">
                            <?php $queryParams = ["search" => $search, "status" => $statusFilter]; ?>
                            <?php if ($currentPage > 1): ?>
                                <a class="trainer-page-link" href="?<?= htmlspecialchars(http_build_query($queryParams + ["page" => $currentPage - 1]), ENT_QUOTES, "UTF-8") ?>">Previous</a>
                            <?php else: ?>
                                <span class="trainer-page-link disabled">Previous</span>
                            <?php endif; ?>

                            <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                                <?php if ($pageNumber === $currentPage): ?>
                                    <span class="trainer-page-link active" aria-current="page"><?= $pageNumber ?></span>
                                <?php else: ?>
                                    <a class="trainer-page-link" href="?<?= htmlspecialchars(http_build_query($queryParams + ["page" => $pageNumber]), ENT_QUOTES, "UTF-8") ?>"><?= $pageNumber ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <a class="trainer-page-link" href="?<?= htmlspecialchars(http_build_query($queryParams + ["page" => $currentPage + 1]), ENT_QUOTES, "UTF-8") ?>">Next</a>
                            <?php else: ?>
                                <span class="trainer-page-link disabled">Next</span>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

        </div>

    </div>

</div>