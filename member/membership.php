
<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

/* =========================================================
   ALLOW MEMBERS ONLY
========================================================= */

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "member"
) {
    header("Location: /gym-management/auth/login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

/* =========================================================
   GET MEMBER DETAILS
========================================================= */

$stmt = $pdo->prepare("
    SELECT id, full_name
    FROM members
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    http_response_code(404);
    exit("Member profile not found.");
}

$member_id = (int) $member["id"];

/* =========================================================
   MARK EXPIRED MEMBERSHIPS
========================================================= */

$expireStmt = $pdo->prepare("
    UPDATE memberships
    SET status = 'expired'
    WHERE member_id = ?
      AND status = 'active'
      AND end_date < CURDATE()
");

$expireStmt->execute([$member_id]);

/* =========================================================
   GET MEMBERSHIP HISTORY
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        memberships.id,
        memberships.plan_id,
        membership_plans.plan_name,
        membership_plans.description,
        membership_plans.price,
        membership_plans.duration_days,
        memberships.start_date,
        memberships.end_date,
        memberships.status
    FROM memberships
    INNER JOIN membership_plans
        ON memberships.plan_id = membership_plans.id
    WHERE memberships.member_id = ?
    ORDER BY memberships.start_date DESC, memberships.id DESC
");

$stmt->execute([$member_id]);
$memberships = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   GET CURRENT ACTIVE MEMBERSHIP

   A membership is current only when today's date falls
   between its start date and end date.
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        memberships.id,
        memberships.plan_id,
        membership_plans.plan_name,
        membership_plans.price,
        memberships.start_date,
        memberships.end_date,
        memberships.status
    FROM memberships
    INNER JOIN membership_plans
        ON memberships.plan_id = membership_plans.id
    WHERE memberships.member_id = ?
      AND memberships.status = 'active'
      AND memberships.start_date <= CURDATE()
      AND memberships.end_date >= CURDATE()
    ORDER BY memberships.end_date DESC, memberships.id DESC
    LIMIT 1
");

$stmt->execute([$member_id]);
$currentMembership = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================================================
   LOAD SHARED HEADER
========================================================= */

require_once "../includes/header.php";

?>

<link
    rel="stylesheet"
    href="/gym-management/css/member-membership.css?v=4"
>

<div class="member-membership-page">

    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>
            <span class="page-label">MEMBER PORTAL</span>

            <h1>My Membership</h1>

            <p>
                View your current membership, purchase a new plan,
                or renew your existing membership.
            </p>
        </div>

        <!-- PURCHASE / RENEW ACTION -->

        <a
            href="/gym-management/member/buy_membership.php"
            class="membership-action-btn"
        >
            <?= $currentMembership
                ? "Renew / Buy Membership"
                : "Buy Membership"
            ?>
        </a>

    </div>

    <!-- CURRENT MEMBERSHIP -->

    <?php if ($currentMembership): ?>

        <div class="current-membership-banner">

            <div class="current-membership-info">

                <span class="current-membership-label">
                    YOUR CURRENT MEMBERSHIP
                </span>

                <h2>
                    <?= htmlspecialchars(
                        $currentMembership["plan_name"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </h2>

                <p>
                    Valid from

                    <strong>
                        <?= date(
                            "d M Y",
                            strtotime($currentMembership["start_date"])
                        ) ?>
                    </strong>

                    until

                    <strong>
                        <?= date(
                            "d M Y",
                            strtotime($currentMembership["end_date"])
                        ) ?>
                    </strong>
                </p>

                <p>
                    Plan price:

                    <strong>
                        ₹<?= number_format(
                            (float) $currentMembership["price"],
                            2
                        ) ?>
                    </strong>
                </p>

                <span class="membership-status status-active">
                    Active
                </span>

            </div>

        </div>

    <?php else: ?>

        <div class="current-membership-banner no-current-membership">

            <div class="current-membership-info">

                <span class="current-membership-label">
                    MEMBERSHIP STATUS
                </span>

                <h2>No Current Membership</h2>

                <p>
                    You currently do not have a membership that is
                    valid today. Choose a plan to get started or
                    check your upcoming membership in the history below.
                </p>

            </div>

        </div>

    <?php endif; ?>

    <!-- MEMBERSHIP HISTORY -->

    <section class="membership-history-section">

        <div class="section-heading">

            <div>
                <h2>Membership History</h2>

                <p>
                    View your current and previous gym memberships.
                </p>
            </div>

            <span class="membership-history-count">
                <?= count($memberships) ?>

                <?= count($memberships) === 1
                    ? "Record"
                    : "Records"
                ?>
            </span>

        </div>

        <?php if (!empty($memberships)): ?>

            <div class="membership-list">

                <?php foreach ($memberships as $membership): ?>

                    <?php

                    /* =============================================
                       DETERMINE DISPLAY STATUS
                    ============================================= */

                    $status = strtolower(
                        trim((string) ($membership["status"] ?? ""))
                    );

                    $today = date("Y-m-d");
                    $startDate = $membership["start_date"];
                    $endDate = $membership["end_date"];

                    if ($status === "cancelled") {

                        $displayStatus = "cancelled";

                    } elseif ($status === "pending") {

                        $displayStatus = "pending";

                    } elseif ($endDate < $today) {

                        $displayStatus = "expired";

                    } elseif ($startDate > $today) {

                        $displayStatus = "upcoming";

                    } elseif ($status === "active") {

                        $displayStatus = "active";

                    } else {

                        $displayStatus = $status ?: "inactive";

                    }

                    $allowedStatusClasses = [
                        "active",
                        "expired",
                        "cancelled",
                        "pending",
                        "upcoming",
                        "inactive"
                    ];

                    $statusClass = in_array(
                        $displayStatus,
                        $allowedStatusClasses,
                        true
                    ) ? $displayStatus : "inactive";

                    ?>

                    <div class="membership-card">

                        <!-- MEMBERSHIP CARD HEADER -->

                        <div class="membership-card-header">

                            <div>

                                <span class="membership-label">
                                    MEMBERSHIP PLAN
                                </span>

                                <h3>
                                    <?= htmlspecialchars(
                                        $membership["plan_name"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </h3>

                            </div>

                            <span class="membership-status status-<?= htmlspecialchars(
                                $statusClass,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>">

                                <?= htmlspecialchars(
                                    ucfirst($displayStatus),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </span>

                        </div>

                        <!-- PLAN DESCRIPTION -->

                        <div class="membership-description">

                            <?= !empty($membership["description"])
                                ? htmlspecialchars(
                                    $membership["description"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                )
                                : "No description available."
                            ?>

                        </div>

                        <!-- MEMBERSHIP DETAILS -->

                        <div class="membership-details">

                            <div class="detail-box">

                                <span>Start Date</span>

                                <strong>
                                    <?= date(
                                        "d M Y",
                                        strtotime($membership["start_date"])
                                    ) ?>
                                </strong>

                            </div>

                            <div class="detail-box">

                                <span>End Date</span>

                                <strong>
                                    <?= date(
                                        "d M Y",
                                        strtotime($membership["end_date"])
                                    ) ?>
                                </strong>

                            </div>

                            <div class="detail-box">

                                <span>Plan Price</span>

                                <strong>
                                    ₹<?= number_format(
                                        (float) $membership["price"],
                                        2
                                    ) ?>
                                </strong>

                            </div>

                            <div class="detail-box">

                                <span>Duration</span>

                                <strong>
                                    <?= (int) $membership["duration_days"] ?>
                                    days
                                </strong>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-membership">

                <div class="empty-icon">✓</div>

                <h2>No Membership History</h2>

                <p>
                    You have not purchased a membership yet.
                    Select Buy Membership above to browse the available plans.
                </p>

                <a
                    href="/gym-management/member/buy_membership.php"
                    class="membership-action-btn"
                >
                    Browse Membership Plans
                </a>

            </div>

        <?php endif; ?>

    </section>

</div>
