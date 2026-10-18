<?php
    require_once "../includes/auth.php";
    require_once "../config/database.php";

    /* =========================================================
    ALLOW MEMBERS ONLY
    ========================================================= */

    if (($_SESSION["role"] ?? "") !== "member") {
        header("Location: ../auth/login.php");
        exit;
    }

    $user_id = $_SESSION["user_id"];


    /* =========================================================
    GET MEMBER
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
        die("Member profile not found.");
    }

    $member_id = $member["id"];


    /* =========================================================
    MEMBERSHIP PERIOD AND ATTENDANCE CALCULATION
    ========================================================= */
    $stmt = $pdo->prepare("
        SELECT start_date, end_date, status
        FROM memberships
        WHERE member_id = ?
        ORDER BY start_date DESC
        LIMIT 1
    ");
    $stmt->execute([$member_id]);
    $membership = $stmt->fetch(PDO::FETCH_ASSOC);

    $today = new DateTimeImmutable(date("Y-m-d"));
    $periodStart = null;
    $periodEnd = null;
    $totalMembershipDays = 0;
    $totalPresentDays = 0;
    $overallPercentage = 0;

    /* Use a valid membership period that has started. */
    if ($membership && !empty($membership["start_date"]) && !empty($membership["end_date"])) {
        $membershipStart = new DateTimeImmutable($membership["start_date"]);
        $membershipEnd = new DateTimeImmutable($membership["end_date"]);
        if ($membershipStart <= $today && $membershipEnd >= $membershipStart) {
            $periodStart = $membershipStart;
            $periodEnd = $membershipEnd > $today ? $today : $membershipEnd;
        }
    }

    /* If membership dates are missing, invalid for today, or in the future,
       use the first attendance date through today so existing data is visible. */
    if ($periodStart === null || $periodEnd === null || $periodStart > $periodEnd) {
        $stmt = $pdo->prepare("
            SELECT MIN(DATE(check_in))
            FROM attendance
            WHERE member_id = ? AND check_in IS NOT NULL
        ");
        $stmt->execute([$member_id]);
        $firstAttendanceDate = $stmt->fetchColumn();
        if ($firstAttendanceDate) {
            $periodStart = new DateTimeImmutable($firstAttendanceDate);
            $periodEnd = $today;
        }
    }

    if ($periodStart !== null && $periodEnd !== null && $periodStart <= $periodEnd) {
        $totalMembershipDays = $periodStart->diff($periodEnd)->days + 1;
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT DATE(check_in))
            FROM attendance
            WHERE member_id = ?
              AND check_in IS NOT NULL
              AND DATE(check_in) BETWEEN ? AND ?
        ");
        $stmt->execute([$member_id, $periodStart->format("Y-m-d"), $periodEnd->format("Y-m-d")]);
        $totalPresentDays = (int) $stmt->fetchColumn();

        if ($totalMembershipDays > 0) {
            $overallPercentage = round(($totalPresentDays / $totalMembershipDays) * 100, 1);
            $overallPercentage = max(0, min(100, $overallPercentage));
        }
    }

    $gaugePercent = max(0, min(100, (float) $overallPercentage));
    $gaugePercentAttr = number_format($gaugePercent, 1, ".", "");
    $gaugeAngleAttr = number_format($gaugePercent * 1.8, 2, ".", "");

    /* =========================================================
    TODAY'S ATTENDANCE
    ========================================================= */
    $stmt = $pdo->prepare("
        SELECT check_in, check_out
        FROM attendance
        WHERE member_id = ? AND DATE(check_in) = CURDATE()
        ORDER BY check_in DESC
        LIMIT 1
    ");
    $stmt->execute([$member_id]);
    $todayAttendance = $stmt->fetch(PDO::FETCH_ASSOC);

    /* =========================================================
    MONTH-WISE ATTENDANCE
    ========================================================= */
    $monthlyAttendance = [];
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(check_in, '%Y-%m') AS month_key,
            DATE_FORMAT(check_in, '%M %Y') AS month_name,
            COUNT(DISTINCT DATE(check_in)) AS present_days
        FROM attendance
        WHERE member_id = ?
          AND check_in IS NOT NULL
          AND DATE(check_in) <= CURDATE()
        GROUP BY DATE_FORMAT(check_in, '%Y-%m'), DATE_FORMAT(check_in, '%M %Y')
        ORDER BY month_key DESC
    ");
    $stmt->execute([$member_id]);
    $monthlyRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($monthlyRecords as $record) {
        $monthStart = new DateTimeImmutable($record["month_key"] . "-01");
        $monthEnd = new DateTimeImmutable($monthStart->format("Y-m-t"));
        if ($monthEnd > $today) {
            $monthEnd = $today;
        }

        $rangeStart = $monthStart;
        $rangeEnd = $monthEnd;
        if ($periodStart !== null && $periodEnd !== null) {
            if ($rangeStart < $periodStart) {
                $rangeStart = $periodStart;
            }
            if ($rangeEnd > $periodEnd) {
                $rangeEnd = $periodEnd;
            }
        }

        $totalDays = ($rangeStart <= $rangeEnd) ? $rangeStart->diff($rangeEnd)->days + 1 : 0;
        $presentDays = 0;
        if ($totalDays > 0) {
            $monthAttendanceStmt = $pdo->prepare("
                SELECT COUNT(DISTINCT DATE(check_in))
                FROM attendance
                WHERE member_id = ?
                  AND check_in IS NOT NULL
                  AND DATE(check_in) BETWEEN ? AND ?
            ");
            $monthAttendanceStmt->execute([
                $member_id,
                $rangeStart->format("Y-m-d"),
                $rangeEnd->format("Y-m-d")
            ]);
            $presentDays = (int) $monthAttendanceStmt->fetchColumn();
        }
        $percentage = $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 0;
        $percentage = max(0, min(100, round($percentage, 1)));

        $monthlyAttendance[] = [
            "month_name" => $record["month_name"],
            "present_days" => $presentDays,
            "total_days" => $totalDays,
            "percentage" => $percentage
        ];
    }

    /* =========================================================
       ATTENDANCE HISTORY
       PAGINATION + DATE RANGE SEARCH
    ========================================================= */

    $startDateFilter = trim($_GET["start_date"] ?? "");
    $endDateFilter   = trim($_GET["end_date"] ?? "");

    // Pagination settings
    $currentPage = isset($_GET["page"]) ? (int) $_GET["page"] : 1;
    $recordsPerPage = 10;

    if ($currentPage < 1) {
        $currentPage = 1;
    }

    $attendanceRecords = [];
    $totalAttendanceRecords = 0;
    $totalPages = 0;
    $offset = 0;

    $filterError = "";


    /* =========================================================
       VALIDATE DATE SEARCH
    ========================================================= */

    if (
        ($startDateFilter !== "" && $endDateFilter === "") ||
        ($startDateFilter === "" && $endDateFilter !== "")
    ) {

        $filterError =
            "Please select both start date and end date.";

    } elseif (
        $startDateFilter !== "" &&
        $endDateFilter !== "" &&
        $startDateFilter > $endDateFilter
    ) {

        $filterError =
            "Start date cannot be later than end date.";
    }


    /* =========================================================
       BUILD FILTER
    ========================================================= */

    $historyWhere = "WHERE member_id = ?";
    $historyParams = [$member_id];

    if (
        $filterError === "" &&
        $startDateFilter !== "" &&
        $endDateFilter !== ""
    ) {

        $historyWhere .=
            " AND DATE(check_in) BETWEEN ? AND ?";

        $historyParams[] = $startDateFilter;
        $historyParams[] = $endDateFilter;
    }


    /* =========================================================
       COUNT RECORDS
    ========================================================= */

    if ($filterError === "") {

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM attendance
            $historyWhere
        ");

        $stmt->execute($historyParams);

        $totalAttendanceRecords =
            (int) $stmt->fetchColumn();

        $totalPages = max(
            1,
            (int) ceil(
                $totalAttendanceRecords /
                $recordsPerPage
            )
        );

        // Keep the requested page safely inside the valid range.
        $currentPage = max(1, min($currentPage, $totalPages));

        /* =====================================================
           FETCH CURRENT PAGE
        ===================================================== */

        $offset = ($currentPage - 1) * $recordsPerPage;

        $stmt = $pdo->prepare("
            SELECT
                id,
                check_in,
                check_out
            FROM attendance
            $historyWhere
            ORDER BY check_in DESC
            LIMIT $recordsPerPage OFFSET $offset
        ");

        $stmt->execute($historyParams);

        $attendanceRecords =
            $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /* =========================================================
       KEEP SEARCH VALUES DURING PAGINATION
    ========================================================= */

    $paginationParams = [];

    if ($startDateFilter !== "") {
        $paginationParams["start_date"] =
            $startDateFilter;
    }

    if ($endDateFilter !== "") {
        $paginationParams["end_date"] =
            $endDateFilter;
    }

    function attendancePageUrl(
        int $page,
        array $params = []
    ): string {

        $params["page"] = $page;

        return "attendance.php?" .
            http_build_query($params);
    }


    /* =========================================================
    HEADER
    ========================================================= */

    require_once "../includes/header.php";

?>

<link
    rel="stylesheet"
    href="/gym-management/css/member-attendance.css?v=8"
>

<style>
/* =========================================================
   MEMBER ATTENDANCE HISTORY
   Search + Pagination
========================================================= */

.attendance-history-search {
    margin: 0 0 22px;
    padding: 18px 20px;
    background: linear-gradient(135deg, #f8fafc 0%, #eef4ff 100%);
    border: 1px solid #dbe5f2;
    border-radius: 14px;
}

.history-search-title {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.history-search-icon {
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: #0f172a;
    color: #ffffff;
    font-size: 17px;
}

.history-search-title strong {
    display: block;
    color: #0f172a;
    font-size: 14px;
}

.history-search-title span {
    display: block;
    margin-top: 3px;
    color: #64748b;
    font-size: 12px;
}

.history-search-form {
    display: grid;
    grid-template-columns: minmax(170px, 1fr) 34px minmax(170px, 1fr) auto;
    align-items: end;
    gap: 12px;
}

.history-date-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.history-date-field label {
    color: #475569;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .8px;
}

.history-date-field input {
    width: 100%;
    height: 42px;
    box-sizing: border-box;
    padding: 0 12px;
    border: 1px solid #d5deea;
    border-radius: 9px;
    background: #ffffff;
    color: #1e293b;
    font-size: 13px;
    outline: none;
}

.history-date-field input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);
}

.history-date-separator {
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 10px;
    font-weight: 800;
}

.history-search-actions {
    display: flex;
    gap: 8px;
}

.history-search-button,
.history-clear-button {
    height: 42px;
    padding: 0 17px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none;
    cursor: pointer;
    white-space: nowrap;
}

.history-search-button {
    border: 1px solid #2563eb;
    background: #2563eb;
    color: #ffffff;
}

.history-search-button:hover {
    background: #1d4ed8;
}

.history-clear-button {
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
}

.history-clear-button:hover {
    background: #f1f5f9;
}

.attendance-history-summary {
    min-height: 42px;
    margin-bottom: 12px;
    padding: 9px 13px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 9px;
    color: #64748b;
    font-size: 12px;
}

.attendance-history-summary strong {
    color: #334155;
}

.history-record-count {
    padding: 5px 10px;
    border-radius: 20px;
    background: #e0edff;
    color: #2563eb;
    font-size: 11px;
    font-weight: 800;
    white-space: nowrap;
}

.attendance-history-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 22px;
}

.history-page-number,
.history-page-arrow {
    min-width: 36px;
    height: 36px;
    box-sizing: border-box;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 10px;
    border: 1px solid #dbe2ea;
    border-radius: 8px;
    background: #ffffff;
    color: #475569;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
}

.history-page-number:hover,
.history-page-arrow:hover {
    background: #eff6ff;
    border-color: #93c5fd;
    color: #2563eb;
}

.history-page-number.active {
    border-color: #2563eb;
    background: #2563eb;
    color: #ffffff;
}

.history-page-arrow.disabled {
    background: #f8fafc;
    color: #cbd5e1;
    border-color: #e5e7eb;
}

.history-page-dots {
    min-width: 22px;
    text-align: center;
    color: #94a3b8;
}

.history-pagination-info {
    margin-top: 9px;
    text-align: center;
    color: #94a3b8;
    font-size: 11px;
}

.history-pagination-info strong {
    color: #475569;
}

.history-pagination-info span {
    margin: 0 5px;
}

@media (max-width: 700px) {

    .history-search-form {
        grid-template-columns: 1fr;
    }

    .history-date-separator {
        display: none;
    }

    .history-search-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }

    .history-search-button,
    .history-clear-button {
        width: 100%;
    }

    .attendance-history-summary {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>



<div class="member-attendance-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="page-header">

        <div>

            <span class="page-label">
                MEMBER PORTAL
            </span>

            <h1>
                My Attendance
            </h1>

            <p>
                Monitor your gym attendance and monthly progress.
            </p>

        </div>

    </div>



    <!-- =====================================================
         ATTENDANCE OVERVIEW
    ====================================================== -->

    <div class="attendance-overview">


        <!-- =================================================
             SPEEDOMETER
        ================================================== -->

        <div class="attendance-gauge-card">


            <div class="gauge-title">

                <span class="card-label">
                    ATTENDANCE PERFORMANCE
                </span>

                <h2>
                    Overall Attendance
                </h2>

            </div>


            <div class="speedometer">

                <!--
                    data-percent = real percentage calculated by PHP (0-100).
                    --gauge-progress-angle is pre-set to the final angle so the
                    gauge is already correct even if JavaScript is disabled.
                    JavaScript animates this single variable; the needle, the
                    coloured arc and the number all follow it.
                -->
                <div class="gauge"
                     id="attendanceGauge"
                     data-percent="<?= $gaugePercentAttr ?>"
                     style="--gauge-progress-angle: <?= $gaugeAngleAttr ?>deg;">

                    <!-- SCALE -->
                    <div class="gauge-scale">
                        <span class="gauge-number n0">0</span>
                        <span class="gauge-number n25">25</span>
                        <span class="gauge-number n50">50</span>
                        <span class="gauge-number n75">75</span>
                        <span class="gauge-number n100">100</span>
                    </div>

                    <!-- ACTIVE METER: SAME ARC AS THE GAUGE -->
                    <div class="gauge-active-meter" aria-hidden="true"></div>

                    <!-- CLOCK STYLE HAND -->
                    <div class="gauge-hand" id="gaugeNeedle"></div>

                    <!-- DIGITAL PERCENTAGE -->
                    <div class="gauge-value">
                        <strong id="attendanceValue"><?= $gaugePercentAttr ?>%</strong>
                        <span>ATTENDANCE</span>
                    </div>

                </div>

            </div>


            <p class="gauge-description">

                Your attendance percentage based on
                your membership period.

            </p>


        </div>



        <!-- =================================================
             ATTENDANCE STATISTICS
        ================================================== -->

        <div class="attendance-statistics">


            <!-- DAYS PRESENT -->

            <div class="stat-card">

                <div class="stat-icon">
                    ✓
                </div>

                <div>

                    <span>
                        Days Present
                    </span>

                    <strong>
                        <?= $totalPresentDays ?>
                    </strong>

                </div>

            </div>


            <!-- MEMBERSHIP DAYS -->

            <div class="stat-card">

                <div class="stat-icon">
                    ▣
                </div>

                <div>

                    <span>
                        Membership Days
                    </span>

                    <strong>
                        <?= $totalMembershipDays ?>
                    </strong>

                </div>

            </div>


            <!-- TODAY -->

            <div class="stat-card">

                <div class="stat-icon">
                    ◷
                </div>

                <div>

                    <span>
                        Today's Status
                    </span>

                    <strong>

                        <?= $todayAttendance
                            ? "Present"
                            : "Not Checked In"
                        ?>

                    </strong>

                </div>

            </div>


        </div>

    </div>



    <!-- =====================================================
         TODAY'S ATTENDANCE
    ====================================================== -->

    <?php if ($todayAttendance): ?>

        <div class="today-attendance-card">


            <div class="today-header">

                <div>

                    <span class="card-label">
                        TODAY
                    </span>

                    <h2>
                        Today's Attendance
                    </h2>

                </div>


                <span class="today-status">
                    Present
                </span>

            </div>


            <div class="today-details">


                <div>

                    <span>
                        Check In
                    </span>

                    <strong>

                        <?= date(
                            "h:i A",
                            strtotime(
                                $todayAttendance["check_in"]
                            )
                        ) ?>

                    </strong>

                </div>


                <div>

                    <span>
                        Check Out
                    </span>

                    <strong>

                        <?php if (
                            !empty(
                                $todayAttendance["check_out"]
                            )
                        ): ?>

                            <?= date(
                                "h:i A",
                                strtotime(
                                    $todayAttendance["check_out"]
                                )
                            ) ?>

                        <?php else: ?>

                            Not Checked Out

                        <?php endif; ?>

                    </strong>

                </div>


            </div>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         MONTH-WISE MONITORING
    ====================================================== -->

    <div class="monthly-attendance-card">


        <div class="section-header">

            <div>

                <span class="card-label">
                    MONTHLY MONITORING
                </span>

                <h2>
                    Month-wise Attendance
                </h2>

                <p>
                    Track your attendance performance month by month.
                </p>

            </div>

        </div>


        <?php if (count($monthlyAttendance) > 0): ?>


            <div class="monthly-table-wrapper">

                <table class="monthly-table">

                    <thead>

                        <tr>

                            <th>
                                Month
                            </th>

                            <th>
                                Present Days
                            </th>

                            <th>
                                Membership Days
                            </th>

                            <th>
                                Attendance
                            </th>

                            <th>
                                Progress
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach (
                            $monthlyAttendance
                            as $month
                        ): ?>

                            <tr>

                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $month["month_name"]
                                        ) ?>
                                    </strong>

                                </td>


                                <td>

                                    <?= $month["present_days"] ?>

                                </td>


                                <td>

                                    <?= $month["total_days"] ?>

                                </td>


                                <td>

                                    <span
                                        class="monthly-percentage"
                                    >

                                        <?= number_format(
                                            $month["percentage"],
                                            1
                                        ) ?>%

                                    </span>

                                </td>


                                <td>

                                    <div class="progress-wrapper">

                                        <div
                                            class="progress-bar"
                                            style="
                                                width:
                                                <?= $month["percentage"] ?>%;
                                            "
                                        ></div>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <div class="empty-monthly">

                <div class="empty-icon">
                    ▣
                </div>

                <h2>
                    No Monthly Data
                </h2>

                <p>
                    Monthly attendance information will appear
                    here once attendance is recorded.
                </p>

            </div>


        <?php endif; ?>


    </div>



    <!-- =====================================================
         ATTENDANCE HISTORY
    ====================================================== -->

    <div class="attendance-history-card">


        <div class="section-header">

            <div>

                <span class="card-label">
                    HISTORY
                </span>

                <h2>
                    Attendance History
                </h2>

                <p>
                    Browse your attendance records and search by date.
                </p>

            </div>

        </div>


        <!-- =================================================
             DIFFERENT SEARCH STYLE
        ================================================== -->

        <div class="attendance-history-search">

            <div class="history-search-title">

                <div class="history-search-icon">
                    🔎
                </div>

                <div>

                    <strong>
                        Find Attendance
                    </strong>

                    <span>
                        Select a date range to view specific check-ins.
                    </span>

                </div>

            </div>


            <form
                method="GET"
                action="attendance.php"
                class="history-search-form"
            >

                <div class="history-date-field">

                    <label for="start_date">
                        FROM
                    </label>

                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        value="<?= htmlspecialchars($startDateFilter) ?>"
                    >

                </div>


                <div class="history-date-separator">
                    TO
                </div>


                <div class="history-date-field">

                    <label for="end_date">
                        TO
                    </label>

                    <input
                        type="date"
                        id="end_date"
                        name="end_date"
                        value="<?= htmlspecialchars($endDateFilter) ?>"
                    >

                </div>


                <div class="history-search-actions">

                    <button
                        type="submit"
                        class="history-search-button"
                    >
                        Search
                    </button>

                    <a
                        href="attendance.php"
                        class="history-clear-button"
                    >
                        Clear
                    </a>

                </div>

            </form>

        </div>


        <?php if ($filterError !== ""): ?>

            <div class="attendance-no-data">

                <div class="empty-icon">
                    !
                </div>

                <h2>
                    Invalid Date Range
                </h2>

                <p>
                    <?= htmlspecialchars($filterError) ?>
                </p>

            </div>


        <?php elseif (count($attendanceRecords) > 0): ?>


            <div class="attendance-history-summary">

                <div>

                    <?php if (
                        $startDateFilter !== "" &&
                        $endDateFilter !== ""
                    ): ?>

                        Showing records from

                        <strong>
                            <?= date(
                                "d M Y",
                                strtotime($startDateFilter)
                            ) ?>
                        </strong>

                        to

                        <strong>
                            <?= date(
                                "d M Y",
                                strtotime($endDateFilter)
                            ) ?>
                        </strong>

                    <?php else: ?>

                        Showing latest attendance records

                    <?php endif; ?>

                </div>


                <span class="history-record-count">

                    <?= $totalAttendanceRecords ?>

                    <?= $totalAttendanceRecords == 1
                        ? "Record"
                        : "Records" ?>

                </span>

            </div>


            <div class="attendance-table-wrapper">

                <table class="attendance-table">

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Date</th>

                            <th>Check In</th>

                            <th>Check Out</th>

                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php
                        // Page 1 = 1-10, Page 2 = 11-20, etc.
                        $count = (int) $offset + 1;
                        ?>

                        <?php foreach (
                            $attendanceRecords
                            as $record
                        ): ?>

                            <tr>

                                <td>
                                    <?= $count++ ?>
                                </td>


                                <td>

                                    <?= date(
                                        "d M Y",
                                        strtotime(
                                            $record["check_in"]
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <?= date(
                                        "h:i A",
                                        strtotime(
                                            $record["check_in"]
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <?php if (
                                        !empty(
                                            $record["check_out"]
                                        )
                                    ): ?>

                                        <?= date(
                                            "h:i A",
                                            strtotime(
                                                $record["check_out"]
                                            )
                                        ) ?>

                                    <?php else: ?>

                                        <span class="not-available">
                                            —
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php if (
                                        !empty(
                                            $record["check_out"]
                                        )
                                    ): ?>

                                        <span class="status-completed">
                                            Completed
                                        </span>

                                    <?php else: ?>

                                        <span class="status-present">
                                            Present
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <!-- =================================================
                 PAGINATION
            ================================================== -->

            <?php if ($totalPages > 1): ?>

                <div class="attendance-history-pagination">

                    <?php if ($currentPage > 1): ?>
                        <a class="history-page-arrow"
                           href="<?= htmlspecialchars(attendancePageUrl($currentPage - 1, $paginationParams)) ?>"
                           aria-label="Previous page">
                            ← Previous
                        </a>
                    <?php else: ?>
                        <span class="history-page-arrow disabled">
                            ← Previous
                        </span>
                    <?php endif; ?>

                    <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                        <?php if ($page === $currentPage): ?>
                            <span class="history-page-number active">
                                <?= $page ?>
                            </span>
                        <?php else: ?>
                            <a class="history-page-number"
                               href="<?= htmlspecialchars(attendancePageUrl($page, $paginationParams)) ?>">
                                <?= $page ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a class="history-page-arrow"
                           href="<?= htmlspecialchars(attendancePageUrl($currentPage + 1, $paginationParams)) ?>"
                           aria-label="Next page">
                            Next →
                        </a>
                    <?php else: ?>
                        <span class="history-page-arrow disabled">
                            Next →
                        </span>
                    <?php endif; ?>

                </div>

                <div class="history-pagination-info">
                    Page <strong><?= $currentPage ?></strong>
                    of <strong><?= $totalPages ?></strong>
                    <span>•</span>
                    <?= $totalAttendanceRecords ?> total records
                </div>

            <?php endif; ?>


        <?php else: ?>


            <div class="attendance-no-data">

                <div class="empty-icon">
                    ✓
                </div>

                <h2>
                    No Check-In Happened
                </h2>

                <p>

                    <?php if (
                        $startDateFilter !== "" &&
                        $endDateFilter !== ""
                    ): ?>

                        No attendance records were found between

                        <strong>
                            <?= date(
                                "d M Y",
                                strtotime($startDateFilter)
                            ) ?>
                        </strong>

                        and

                        <strong>
                            <?= date(
                                "d M Y",
                                strtotime($endDateFilter)
                            ) ?>
                        </strong>.

                    <?php else: ?>

                        No attendance records are available
                        for this member.

                    <?php endif; ?>

                </p>

            </div>


        <?php endif; ?>


    </div>


</div>



<script>
(function () {
    function startGauge() {
        var gauge = document.getElementById("attendanceGauge");
        var value = document.getElementById("attendanceValue");
        if (!gauge || !value) return;

        var target = Number.parseFloat(gauge.getAttribute("data-percent"));
        if (!Number.isFinite(target)) target = 0;
        target = Math.max(0, Math.min(100, target));

        var RISE_MS = 2200;
        var PAUSE_MS = 400;
        var FALL_MS = 1800;
        var reduceMotion = window.matchMedia &&
            window.matchMedia("(prefers-reduced-motion: reduce)").matches;

        function render(percent) {
            var p = Math.max(0, Math.min(100, percent));
            var angle = p * 1.8;
            gauge.style.setProperty("--gauge-progress-angle", angle.toFixed(3) + "deg");
            value.textContent = p.toFixed(1) + "%";
            value.setAttribute("aria-label", "Attendance " + p.toFixed(1) + " percent");
        }

        function ease(t) {
            return t < 0.5
                ? 4 * t * t * t
                : 1 - Math.pow(-2 * t + 2, 3) / 2;
        }

        if (reduceMotion) {
            render(target);
            return;
        }

        render(0);
        var startTime = null;

        function frame(now) {
            if (startTime === null) startTime = now;
            var elapsed = now - startTime;

            if (elapsed < RISE_MS) {
                render(100 * ease(elapsed / RISE_MS));
                requestAnimationFrame(frame);
                return;
            }
            if (elapsed < RISE_MS + PAUSE_MS) {
                render(100);
                requestAnimationFrame(frame);
                return;
            }

            var fallElapsed = elapsed - RISE_MS - PAUSE_MS;
            if (fallElapsed < FALL_MS) {
                var progress = ease(fallElapsed / FALL_MS);
                render(100 + (target - 100) * progress);
                requestAnimationFrame(frame);
                return;
            }

            render(target);
        }

        requestAnimationFrame(frame);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", startGauge, { once: true });
    } else {
        startGauge();
    }
})();
</script>