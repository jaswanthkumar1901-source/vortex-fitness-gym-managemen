
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
   FETCH TRAINER PROFILE
========================================================= */

$stmt = $pdo->prepare("
    SELECT id, full_name
    FROM trainers
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);
$trainer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trainer) {
    http_response_code(403);
    exit("Trainer profile not found. Contact the administrator.");
}

$trainer_id = (int)$trainer["id"];

/* =========================================================
   SEARCH
========================================================= */

$search = trim($_GET["search"] ?? "");

/* =========================================================
   FETCH ONLY THIS TRAINER'S ASSIGNED MEMBERS
========================================================= */

$sql = "
    SELECT
        m.id,
        m.full_name,
        m.email,
        m.phone,
        m.join_date,
        m.status,
        mt.assigned_date
    FROM member_trainer mt
    INNER JOIN members m
        ON m.id = mt.member_id
    WHERE mt.trainer_id = ?
";

$params = [$trainer_id];

if ($search !== "") {
    $sql .= "
        AND (
            m.full_name LIKE ?
            OR m.email LIKE ?
            OR m.phone LIKE ?
        )
    ";

    $term = "%" . $search . "%";

    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= "
    ORDER BY mt.assigned_date DESC, mt.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   SUMMARY
========================================================= */

$totalMembers = count($members);

$activeMembers = 0;

foreach ($members as $member) {
    if (strtolower($member["status"]) === "active") {
        $activeMembers++;
    }
}

/* =========================================================
   HEADER
========================================================= */

require_once "../includes/header.php";

?>

<link
    rel="stylesheet"
    href="/gym-management/css/trainer-portal-member.css?v=2"
>

<div class="trainer-dashboard">

    <!-- PAGE HEADER -->

    <div class="trainer-dashboard-header">

        <div>
            <span class="trainer-dashboard-label">
                TRAINER PORTAL
            </span>

            <h1>My Members</h1>

            <p>
                View and manage information about members assigned to you.
            </p>
        </div>

        <a
            href="/gym-management/trainer_portal/dashboard.php"
            class="trainer-back-link"
        >
            ← Back to Dashboard
        </a>

    </div>


    <!-- SUMMARY -->

    <div class="trainer-stats-grid">

        <div class="trainer-stat-card">

            <div class="trainer-stat-icon blue">M</div>

            <div>
                <span>Members Found</span>
                <strong><?= $totalMembers ?></strong>
                <small>Based on the current search</small>
            </div>

        </div>

        <div class="trainer-stat-card">

            <div class="trainer-stat-icon green">A</div>

            <div>
                <span>Active Members</span>
                <strong><?= $activeMembers ?></strong>
                <small>Active profiles in this list</small>
            </div>

        </div>

    </div>


    <!-- MEMBER LIST -->

    <div class="trainer-members-card">

        <div class="trainer-card-header">

            <div>
                <h2>Assigned Members</h2>
                <p>Only members assigned to your trainer account are shown.</p>
            </div>

        </div>


        <!-- SEARCH -->

        <form method="GET" class="trainer-member-search">

            <input
                type="search"
                name="search"
                placeholder="Search by name, email or phone..."
                value="<?= htmlspecialchars(
                    $search,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >

            <button type="submit">
                Search
            </button>

            <?php if ($search !== ""): ?>

                <a href="my_members.php" class="trainer-clear-search">
                    Clear
                </a>

            <?php endif; ?>

        </form>


        <?php if (!empty($members)): ?>

            <div class="trainer-table-wrapper">

                <table class="trainer-members-table">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Member Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Join Date</th>
                            <th>Assigned Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($members as $member): ?>

                            <tr>

                                <td>
                                    #<?= (int)$member["id"] ?>
                                </td>

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
                                    <?= !empty($member["join_date"])
                                        ? date(
                                            "d M Y",
                                            strtotime($member["join_date"])
                                        )
                                        : "—" ?>
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

                <h3>
                    <?= $search !== ""
                        ? "No Matching Members"
                        : "No Members Assigned Yet" ?>
                </h3>

                <p>
                    <?= $search !== ""
                        ? "Try another name, email address or phone number."
                        : "Ask the administrator to assign members to your trainer account." ?>
                </p>

            </div>

        <?php endif; ?>

    </div>

</div>
