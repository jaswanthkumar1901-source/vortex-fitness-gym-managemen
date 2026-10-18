
<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "trainer") {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

/* Find the logged-in trainer's profile */
$stmt = $pdo->prepare(
    "SELECT id FROM trainers WHERE user_id = ? LIMIT 1"
);
$stmt->execute([$user_id]);
$trainer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trainer) {
    http_response_code(403);
    exit("Trainer profile not found. Please contact the administrator.");
}

$trainer_id = (int) $trainer["id"];

/* CSRF token */
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$message_type = "success";

/* Handle check-in and check-out */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $message = "Invalid request. Please refresh the page and try again.";
        $message_type = "error";
    } else {
        $member_id = filter_input(
            INPUT_POST,
            "member_id",
            FILTER_VALIDATE_INT
        );

        $action = $_POST["action"] ?? "";

        if (!$member_id || !in_array($action, ["check_in", "check_out"], true)) {
            $message = "Invalid attendance request.";
            $message_type = "error";
        } else {
            /* Verify that this member is assigned to this trainer */
            $stmt = $pdo->prepare(
                "SELECT m.id, m.full_name
                 FROM members m
                 INNER JOIN member_trainer mt
                    ON mt.member_id = m.id
                 WHERE m.id = ?
                   AND mt.trainer_id = ?
                 LIMIT 1"
            );
            $stmt->execute([$member_id, $trainer_id]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$member) {
                $message = "You are not authorized to manage this member.";
                $message_type = "error";
            } else {
                /* Check-in */
                if ($action === "check_in") {
                    $stmt = $pdo->prepare(
                        "SELECT id
                         FROM attendance
                         WHERE member_id = ?
                           AND check_out IS NULL
                         LIMIT 1"
                    );
                    $stmt->execute([$member_id]);

                    if ($stmt->fetch()) {
                        $message = $member["full_name"]
                            . " already has an open check-in.";
                        $message_type = "error";
                    } else {
                        $stmt = $pdo->prepare(
                            "INSERT INTO attendance (member_id)
                             VALUES (?)"
                        );
                        $stmt->execute([$member_id]);

                        $message = $member["full_name"]
                            . " checked in successfully.";
                    }
                }

                /* Check-out */
                if ($action === "check_out") {
                    $stmt = $pdo->prepare(
                        "SELECT id
                         FROM attendance
                         WHERE member_id = ?
                           AND check_out IS NULL
                         ORDER BY check_in DESC, id DESC
                         LIMIT 1"
                    );
                    $stmt->execute([$member_id]);
                    $open_attendance = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$open_attendance) {
                        $message = "No open check-in found for "
                            . $member["full_name"] . ".";
                        $message_type = "error";
                    } else {
                        $stmt = $pdo->prepare(
                            "UPDATE attendance
                             SET check_out = CURRENT_TIMESTAMP
                             WHERE id = ?
                               AND member_id = ?
                               AND check_out IS NULL"
                        );
                        $stmt->execute([
                            $open_attendance["id"],
                            $member_id
                        ]);

                        $message = $member["full_name"]
                            . " checked out successfully.";
                    }
                }
            }
        }
    }
}

/* Selected date for attendance history */
$selected_date = $_GET["date"] ?? date("Y-m-d");

$date_object = DateTime::createFromFormat("!Y-m-d", $selected_date);

if (!$date_object || $date_object->format("Y-m-d") !== $selected_date) {
    $selected_date = date("Y-m-d");
}

/* Fetch members assigned to this trainer */
$stmt = $pdo->prepare(
    "SELECT
        m.id,
        m.full_name,
        m.email,
        m.phone,
        m.status,
        (
            SELECT a.id
            FROM attendance a
            WHERE a.member_id = m.id
              AND a.check_out IS NULL
            ORDER BY a.check_in DESC, a.id DESC
            LIMIT 1
        ) AS open_attendance_id,
        (
            SELECT a.check_in
            FROM attendance a
            WHERE a.member_id = m.id
              AND a.check_out IS NULL
            ORDER BY a.check_in DESC, a.id DESC
            LIMIT 1
        ) AS open_check_in
     FROM members m
     INNER JOIN member_trainer mt
        ON mt.member_id = m.id
     WHERE mt.trainer_id = ?
     ORDER BY m.full_name ASC"
);
$stmt->execute([$trainer_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Fetch attendance records for assigned members on selected date */
$stmt = $pdo->prepare(
    "SELECT
        a.id,
        a.member_id,
        a.check_in,
        a.check_out,
        m.full_name
     FROM attendance a
     INNER JOIN members m
        ON m.id = a.member_id
     WHERE EXISTS (
        SELECT 1
        FROM member_trainer mt
        WHERE mt.member_id = a.member_id
          AND mt.trainer_id = ?
     )
       AND a.check_in >= ?
       AND a.check_in < DATE_ADD(?, INTERVAL 1 DAY)
     ORDER BY a.check_in DESC"
);
$stmt->execute([
    $trainer_id,
    $selected_date . " 00:00:00",
    $selected_date
]);
$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Statistics for the selected date */
$stmt = $pdo->prepare(
    "SELECT
        COUNT(DISTINCT a.member_id) AS checked_in_count,
        SUM(CASE WHEN a.check_out IS NULL THEN 1 ELSE 0 END)
            AS currently_checked_in
     FROM attendance a
     WHERE EXISTS (
        SELECT 1
        FROM member_trainer mt
        WHERE mt.member_id = a.member_id
          AND mt.trainer_id = ?
     )
       AND a.check_in >= ?
       AND a.check_in < DATE_ADD(?, INTERVAL 1 DAY)"
);
$stmt->execute([
    $trainer_id,
    $selected_date . " 00:00:00",
    $selected_date
]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$members_count = count($members);
$checked_in_count = (int) ($stats["checked_in_count"] ?? 0);
$currently_checked_in = (int) ($stats["currently_checked_in"] ?? 0);

require_once "../includes/header.php";
?>

<link rel="stylesheet"
      href="/gym-management/css/trainer-portal-attendance.css?v=1">

<main class="trainer-attendance-page">

    <div class="attendance-page-header">
        <div>
            <span class="attendance-eyebrow">TRAINER PORTAL</span>
            <h1>Member Attendance</h1>
            <p>Record check-ins and check-outs for members assigned to you.</p>
        </div>

        <a href="dashboard.php" class="attendance-back-link">
            &larr; Back to Dashboard
        </a>
    </div>

    <?php if ($message !== ""): ?>
        <div class="attendance-message <?= $message_type === "error" ? "error" : "success" ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <section class="attendance-stats">
        <div class="attendance-stat-card">
            <div class="attendance-stat-icon blue">M</div>
            <div>
                <p>Assigned Members</p>
                <h2><?= $members_count ?></h2>
                <span>Members assigned to you</span>
            </div>
        </div>

        <div class="attendance-stat-card">
            <div class="attendance-stat-icon green">✓</div>
            <div>
                <p>Members Attended</p>
                <h2><?= $checked_in_count ?></h2>
                <span>Distinct members with check-ins on the selected date</span>
            </div>
        </div>

        <div class="attendance-stat-card">
            <div class="attendance-stat-icon orange">↗</div>
            <div>
                <p>Currently Checked In</p>
                <h2><?= $currently_checked_in ?></h2>
                <span>Open attendance records on the selected date</span>
            </div>
        </div>
    </section>

    <section class="attendance-panel">
        <div class="attendance-panel-heading">
            <div>
                <h2>Assigned Members</h2>
                <p>Use the buttons to record a member's attendance.</p>
            </div>
        </div>

        <div class="attendance-search-area">
            <input
                type="search"
                id="memberSearch"
                placeholder="Search by member name, email or phone..."
                aria-label="Search assigned members"
            >
        </div>

        <?php if (empty($members)): ?>
            <div class="attendance-empty-state">
                <div class="attendance-empty-icon">M</div>
                <h3>No Members Assigned Yet</h3>
                <p>Ask the administrator to assign members to your trainer account.</p>
            </div>
        <?php else: ?>
            <div class="attendance-table-wrapper">
                <table class="attendance-table" id="membersTable">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Attendance Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td class="attendance-member-name">
                                <?= htmlspecialchars($member["full_name"] ?? "") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($member["email"] ?? "-") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($member["phone"] ?? "-") ?>
                            </td>

                            <td>
                                <?php if (!empty($member["open_attendance_id"])): ?>
                                    <span class="attendance-badge checked-in">
                                        Checked In
                                    </span>
                                    <small class="attendance-time">
                                        <?= htmlspecialchars(
                                            date(
                                                "d M Y, h:i A",
                                                strtotime($member["open_check_in"])
                                            )
                                        ) ?>
                                    </small>
                                <?php else: ?>
                                    <span class="attendance-badge checked-out">
                                        Not Checked In
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <form method="POST" class="attendance-action-form">
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>"
                                    >
                                    <input
                                        type="hidden"
                                        name="member_id"
                                        value="<?= (int) $member["id"] ?>"
                                    >

                                    <?php if (!empty($member["open_attendance_id"])): ?>
                                        <button
                                            type="submit"
                                            name="action"
                                            value="check_out"
                                            class="attendance-action-button check-out-button"
                                        >
                                            Check Out
                                        </button>
                                    <?php else: ?>
                                        <button
                                            type="submit"
                                            name="action"
                                            value="check_in"
                                            class="attendance-action-button check-in-button"
                                        >
                                            Check In
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="attendance-panel attendance-history-panel">
        <div class="attendance-panel-heading">
            <div>
                <h2>Attendance History</h2>
                <p>View check-in and check-out records for your assigned members.</p>
            </div>

            <form method="GET" class="attendance-date-filter">
                <label for="attendanceDate">Select Date</label>
                <input
                    type="date"
                    name="date"
                    id="attendanceDate"
                    value="<?= htmlspecialchars($selected_date) ?>"
                    required
                >
                <button type="submit">Filter</button>
            </form>
        </div>

        <?php if (empty($attendance_records)): ?>
            <div class="attendance-empty-state compact">
                <h3>No Attendance Records</h3>
                <p>No attendance records were found for <?= htmlspecialchars($selected_date) ?>.</p>
            </div>
        <?php else: ?>
            <div class="attendance-table-wrapper">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Member Name</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                            <th>Record Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($attendance_records as $record): ?>
                        <tr>
                            <td class="attendance-member-name">
                                <?= htmlspecialchars($record["full_name"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    date("d M Y, h:i A", strtotime($record["check_in"]))
                                ) ?>
                            </td>

                            <td>
                                <?php if (!empty($record["check_out"])): ?>
                                    <?= htmlspecialchars(
                                        date("d M Y, h:i A", strtotime($record["check_out"]))
                                    ) ?>
                                <?php else: ?>
                                    <span class="attendance-pending">Not checked out</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (!empty($record["check_out"])): ?>
                                    <span class="attendance-badge checked-out">Completed</span>
                                <?php else: ?>
                                    <span class="attendance-badge checked-in">In Progress</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("memberSearch");
    const table = document.getElementById("membersTable");

    if (!searchInput || !table) {
        return;
    }

    searchInput.addEventListener("input", function () {
        const searchValue = this.value.toLowerCase().trim();

        table.querySelectorAll("tbody tr").forEach(function (row) {
            row.style.display = row.textContent.toLowerCase().includes(searchValue)
                ? ""
                : "none";
        });
    });
});
</script>
</main>
</body>
</html>
