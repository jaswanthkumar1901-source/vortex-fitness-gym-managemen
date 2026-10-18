
<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

/* =========================================================
   TRAINER ACCESS ONLY
========================================================= */

if (($_SESSION["role"] ?? "") !== "trainer") {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = (int)($_SESSION["user_id"] ?? 0);

/* =========================================================
   FETCH LOGGED-IN TRAINER
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        id,
        full_name,
        specialization,
        email,
        phone,
        status
    FROM trainers
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$trainer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trainer) {
    http_response_code(403);
    exit(
        "Trainer profile not found. Please contact the administrator."
    );
}

$trainer_id = (int)$trainer["id"];

/* =========================================================
   TOTAL ASSIGNED MEMBERS
========================================================= */

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT m.id)
    FROM member_trainer mt
    INNER JOIN members m
        ON m.id = mt.member_id
    WHERE mt.trainer_id = ?
");

$stmt->execute([$trainer_id]);

$totalMembers = (int)$stmt->fetchColumn();

/* =========================================================
   ACTIVE ASSIGNED MEMBERS
========================================================= */

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT m.id)
    FROM member_trainer mt
    INNER JOIN members m
        ON m.id = mt.member_id
    WHERE mt.trainer_id = ?
      AND m.status = 'active'
");

$stmt->execute([$trainer_id]);

$activeMembers = (int)$stmt->fetchColumn();

/* =========================================================
   RECENTLY ASSIGNED MEMBERS
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        m.id,
        m.full_name,
        m.email,
        m.phone,
        m.status,
        mt.assigned_date
    FROM member_trainer mt
    INNER JOIN members m
        ON m.id = mt.member_id
    WHERE mt.trainer_id = ?
    ORDER BY mt.assigned_date DESC, mt.id DESC
    LIMIT 5
");

$stmt->execute([$trainer_id]);

$recentMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   LOAD HEADER
========================================================= */

require_once "../includes/header.php";

?>

<link
    rel="stylesheet"
    href="/gym-management/css/trainer-portal.css?v=1"
>

<div class="trainer-dashboard">

    <!-- PAGE HEADER -->

    <div class="trainer-dashboard-header">

        <div>
            <span class="trainer-dashboard-label">
                TRAINER PORTAL
            </span>

            <h1>
                Welcome, <?= htmlspecialchars(
                    $trainer["full_name"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>!
            </h1>

            <p>
                Manage your assigned members and monitor their progress.
            </p>
        </div>

        <div class="trainer-profile-badge">
            <div class="trainer-avatar">
                <?= htmlspecialchars(
                    strtoupper(substr($trainer["full_name"], 0, 1)),
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </div>

            <div>
                <strong>
                    <?= htmlspecialchars(
                        $trainer["full_name"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </strong>

                <span>Trainer</span>
            </div>
        </div>

    </div>


    <!-- SUMMARY CARDS -->

    <div class="trainer-stats-grid">

        <div class="trainer-stat-card">
            <div class="trainer-stat-icon blue">M</div>

            <div>
                <span>Total Assigned Members</span>
                <strong><?= $totalMembers ?></strong>
                <small>Members assigned to you</small>
            </div>
        </div>

        <div class="trainer-stat-card">
            <div class="trainer-stat-icon green">A</div>

            <div>
                <span>Active Members</span>
                <strong><?= $activeMembers ?></strong>
                <small>Currently active member profiles</small>
            </div>
        </div>

        <div class="trainer-stat-card">
            <div class="trainer-stat-icon purple">T</div>

            <div>
                <span>Specialization</span>
                <strong class="trainer-specialization">
                    <?= htmlspecialchars(
                        $trainer["specialization"] ?: "Not specified",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </strong>
                <small>Your trainer profile</small>
            </div>
        </div>

    </div>


    <!-- QUICK ACTIONS -->

    <div class="trainer-quick-actions">

        <a
            href="my_members.php"
            class="trainer-action-card"
        >
            <div class="trainer-action-icon">M</div>

            <div>
                <h3>My Members</h3>
                <p>View your assigned members and their details.</p>
            </div>

            <span class="trainer-action-arrow">→</span>
        </a>

        <a
            href="attendance.php"
            class="trainer-action-card"
        >
            <div class="trainer-action-icon attendance-icon">A</div>

            <div>
                <h3>Member Attendance</h3>
                <p>Review attendance for your assigned members.</p>
            </div>

            <span class="trainer-action-arrow">→</span>
        </a>

    </div>


    <!-- RECENT ASSIGNMENTS -->

    <div class="trainer-members-card">

        <div class="trainer-card-header">
            <div>
                <h2>Recently Assigned Members</h2>
                <p>Your five most recently assigned members.</p>
            </div>

            <a href="my_members.php" class="trainer-view-all">
                View All →
            </a>
        </div>

        <?php if (!empty($recentMembers)): ?>

            <div class="trainer-table-wrapper">

                <table class="trainer-members-table">

                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Assigned Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($recentMembers as $member): ?>

                            <tr>
                                <td>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $member["full_name"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $member["email"] ?? "—",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $member["phone"] ?? "—",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <?= !empty($member["assigned_date"])
                                        ? date(
                                            "d M Y",
                                            strtotime($member["assigned_date"])
                                        )
                                        : "—" ?>
                                </td>

                                <td>
                                    <span class="trainer-member-status <?= strtolower(
                                        $member["status"]
                                    ) === "active"
                                        ? "status-active"
                                        : "status-inactive" ?>">
                                        <?= htmlspecialchars(
                                            ucfirst($member["status"]),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </span>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="trainer-empty-state">
                <div class="trainer-empty-icon">M</div>

                <h3>No Members Assigned Yet</h3>

                <p>
                    Ask the administrator to assign members to your trainer account.
                </p>
            </div>

        <?php endif; ?>

    </div>

</div>
