
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Escape output safely.
function dashboardEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Dashboard statistics.
$activeMembers = 0;
$attendance = 0;
$activeMembership = 0;
$totalpayment = 0;
$activeTrainers = 0;
$expiredMemberships = 0;

try {
    $activeMembers = (int)$pdo->query(
        "SELECT COUNT(*) FROM members WHERE status = 'active'"
    )->fetchColumn();

    $attendance = (int)$pdo->query(
        "SELECT COUNT(*) FROM attendance WHERE DATE(check_in) = CURDATE()"
    )->fetchColumn();

    $activeMembership = (int)$pdo->query(
        "SELECT COUNT(*) FROM memberships
         WHERE status = 'active' AND end_date >= CURDATE()"
    )->fetchColumn();

    $totalpayment = (float)$pdo->query(
        "SELECT COALESCE(SUM(amount), 0)
         FROM payments WHERE status = 'paid'"
    )->fetchColumn();

    $activeTrainers = (int)$pdo->query(
        "SELECT COUNT(*) FROM trainers WHERE status = 'active'"
    )->fetchColumn();

    $expiredMemberships = (int)$pdo->query(
        "SELECT COUNT(*) FROM memberships WHERE status = 'expired'"
    )->fetchColumn();

} catch (PDOException $e) {
    error_log('Dashboard statistics error: ' . $e->getMessage());
}

$username = $_SESSION['username'] ?? 'Admin';
$avatarLetter = strtoupper(substr(trim($username), 0, 1));

if ($avatarLetter === '') {
    $avatarLetter = 'A';
}

// Notifications.
$notifications = [];
$unreadCount = 0;
$userId = (int)($_SESSION['user_id'] ?? 0);

try {
    if ($userId > 0) {
        $stmt = $pdo->prepare(
            "SELECT id, title, message, is_read, created_at
             FROM notifications
             WHERE user_id = ?
             ORDER BY created_at DESC, id DESC
             LIMIT 10"
        );

        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($notifications as $item) {
            if ((int)($item['is_read'] ?? 0) === 0) {
                $unreadCount++;
            }
        }
    }
} catch (PDOException $e) {
    // The dashboard can still work without a notifications table.
    $notifications = [];
    $unreadCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | VORTEX FITNESS</title>

    <link rel="stylesheet"
          href="/gym-management/css/admin-dashboard.css">
    <script src="/gym-management/js/admin-dashboard.js" defer></script>
</head>

<body>
<div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="adminSidebar">

        <div class="sidebar-logo">
            <a href="/gym-management/admin/dashboard.php">
                VORTEX<span>FITNESS</span>
            </a>
            <p>ADMINISTRATION</p>
        </div>

        <div class="nav-section-label">WORKSPACE</div>

        <nav class="sidebar-nav">
            <a href="/gym-management/admin/dashboard.php"
               class="nav-item active">
                <span class="nav-icon">▦</span>
                <span>Dashboard</span>
            </a>

            <a href="/gym-management/members/index.php" class="nav-item">
                <span class="nav-icon">♙</span>
                <span>Members</span>
            </a>

            <a href="/gym-management/trainer/index.php" class="nav-item">
                <span class="nav-icon">✦</span>
                <span>Trainers</span>
            </a>

            <a href="/gym-management/plans/index.php" class="nav-item">
                <span class="nav-icon">▤</span>
                <span>Membership Plans</span>
            </a>

            <a href="/gym-management/membership/index.php" class="nav-item">
                <span class="nav-icon">▣</span>
                <span>Memberships</span>
            </a>

            <a href="/gym-management/payments/index.php" class="nav-item">
                <span class="nav-icon">₹</span>
                <span>Payments</span>
            </a>

            <a href="/gym-management/attendance/index.php" class="nav-item">
                <span class="nav-icon">✓</span>
                <span>Attendance</span>
            </a>

            <a href="/gym-management/member_trainer/index.php"
               class="nav-item">
                <span class="nav-icon">⇄</span>
                <span>Trainer Assignments</span>
            </a>

            <a href="/gym-management/reports/index.php" class="nav-item">
                <span class="nav-icon">▥</span>
                <span>Reports</span>
            </a>

            <div class="nav-divider"></div>

            <div class="nav-section-label">SYSTEM</div>

            <a href="/gym-management/users/index.php" class="nav-item">
                <span class="nav-icon">⚙</span>
                <span>User Management</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <a href="/gym-management/index.php" class="nav-item">
                <span class="nav-icon">↗</span>
                <span>View Website</span>
            </a>

            <a href="/gym-management/auth/logout.php"
               class="nav-item logout-item">
                <span class="nav-icon">⇥</span>
                <span>Logout</span>
            </a>

            <div class="sidebar-footer">
                <span class="footer-dot"></span>
                System Online
            </div>
        </div>
    </aside>

    <!-- MAIN AREA -->
    <main class="main-area">

        <!-- TOP BAR -->
        <header class="top-bar">
            <div class="topbar-left">
                <button type="button"
                        class="mobile-menu"
                        id="mobileMenuToggle"
                        aria-label="Toggle navigation"
                        aria-expanded="false">
                    ☰
                </button>

                <div class="breadcrumb">
                    <span>Workspace</span>
                    <span class="breadcrumb-divider">/</span>
                    <strong>Dashboard</strong>
                </div>
            </div>

            <div class="admin-profile">

                <!-- NOTIFICATIONS -->
                <div class="header-dropdown">
                    <button type="button"
                            class="header-icon-btn"
                            id="notificationToggle"
                            aria-label="Notifications"
                            aria-expanded="false"
                            aria-controls="notificationPanel">
                        <span aria-hidden="true">♧</span>

                        <?php if ($unreadCount > 0): ?>
                            <span class="notification-count">
                                <?= $unreadCount > 99 ? '99+' : $unreadCount ?>
                            </span>
                        <?php endif; ?>
                    </button>

                    <div class="header-dropdown-panel notification-panel"
                         id="notificationPanel" hidden>

                        <div class="dropdown-heading">
                            <div>
                                <strong>Notifications</strong>
                                <p>Your latest updates</p>
                            </div>
                            <span class="unread-label">
                                <?= $unreadCount ?> unread
                            </span>
                        </div>

                        <div class="notification-list">
                            <?php if (empty($notifications)): ?>
                                <div class="notification-empty">
                                    <span class="empty-bell">♧</span>
                                    <strong>All caught up</strong>
                                    <p>No notifications yet.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="notification-item <?= (int)($notification['is_read'] ?? 0) === 0 ? 'unread' : '' ?>">
                                        <span class="notification-dot"></span>
                                        <div class="notification-copy">
                                            <strong>
                                                <?= dashboardEscape($notification['title'] ?? '') ?>
                                            </strong>
                                            <p>
                                                <?= dashboardEscape($notification['message'] ?? '') ?>
                                            </p>
                                            <small>
                                                <?= dashboardEscape($notification['created_at'] ?? '') ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- PROFILE -->
                <div class="header-dropdown">
                    <button type="button"
                            class="profile-trigger"
                            id="profileToggle"
                            aria-label="User profile"
                            aria-expanded="false"
                            aria-controls="profilePanel">

                        <span class="profile-info">
                            <strong><?= dashboardEscape($username) ?></strong>
                            <small>Administrator</small>
                        </span>

                        <span class="profile-avatar">
                            <?= dashboardEscape($avatarLetter) ?>
                        </span>

                        <span class="profile-chevron">⌄</span>
                    </button>

                    <div class="header-dropdown-panel profile-panel"
                         id="profilePanel" hidden>

                        <div class="profile-panel-heading">
                            <span class="profile-avatar">
                                <?= dashboardEscape($avatarLetter) ?>
                            </span>
                            <div>
                                <strong><?= dashboardEscape($username) ?></strong>
                                <small>Administrator</small>
                            </div>
                        </div>

                        <div class="profile-menu">
                            <a href="/gym-management/change_password.php">
                                <span>⚿</span> Change Password
                            </a>

                            <a href="/gym-management/auth/logout.php"
                               class="logout-link">
                                <span>⇥</span> Logout
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </header>

        <!-- DASHBOARD CONTENT -->
        <section class="dashboard-content">

            <div class="page-heading">
                <div>
                    <div class="page-label">
                        <span class="heading-dot"></span>
                        ADMIN OVERVIEW
                    </div>

                    <h1>Dashboard<span class="heading-period">.</span></h1>

                    <p>
                        Welcome back,
                        <strong><?= dashboardEscape($username) ?></strong>.
                        Here's what's happening in your gym today.
                    </p>
                </div>

                <div class="today-badge">
                    <span class="calendar-icon">▦</span>
                    <?= date('d M Y') ?>
                </div>
            </div>

            <!-- STATISTICS -->
            <div class="stats-grid">

                <article class="stat-card members-card">
                    <div class="stat-card-top">
                        <div class="stat-icon members-icon">♙</div>
                        <span class="stat-status"><i></i> Active</span>
                    </div>

                    <p class="stat-label">Active Members</p>
                    <h2><?= number_format($activeMembers) ?></h2>
                    <a href="/gym-management/members/index.php">
                        View members <span>→</span>
                    </a>
                </article>

                <article class="stat-card attendance-card">
                    <div class="stat-card-top">
                        <div class="stat-icon attendance-icon">✓</div>
                        <span class="stat-status today-status">Today</span>
                    </div>

                    <p class="stat-label">Today's Attendance</p>
                    <h2><?= number_format($attendance) ?></h2>
                    <a href="/gym-management/attendance/index.php">
                        View attendance <span>→</span>
                    </a>
                </article>

                <article class="stat-card membership-card">
                    <div class="stat-card-top">
                        <div class="stat-icon membership-icon">▣</div>
                        <span class="stat-status"><i></i> Active</span>
                    </div>

                    <p class="stat-label">Active Memberships</p>
                    <h2><?= number_format($activeMembership) ?></h2>
                    <a href="/gym-management/membership/index.php">
                        View memberships <span>→</span>
                    </a>
                </article>

                <article class="stat-card payment-card">
                    <div class="stat-card-top">
                        <div class="stat-icon payment-icon">₹</div>
                        <span class="stat-status paid-status">Paid</span>
                    </div>

                    <p class="stat-label">Total Payments</p>
                    <h2 class="payment-value">
                        ₹<?= number_format($totalpayment, 2) ?>
                    </h2>
                    <a href="/gym-management/payments/index.php">
                        View payments <span>→</span>
                    </a>
                </article>

                <article class="stat-card trainer-card">
                    <div class="stat-card-top">
                        <div class="stat-icon trainer-icon">✦</div>
                        <span class="stat-status"><i></i> Active</span>
                    </div>

                    <p class="stat-label">Active Trainers</p>
                    <h2><?= number_format($activeTrainers) ?></h2>
                    <a href="/gym-management/trainer/index.php">
                        View trainers <span>→</span>
                    </a>
                </article>

                <article class="stat-card expired-card">
                    <div class="stat-card-top">
                        <div class="stat-icon expired-icon">!</div>
                        <span class="stat-status expired-status">Attention</span>
                    </div>

                    <p class="stat-label">Expired Memberships</p>
                    <h2><?= number_format($expiredMemberships) ?></h2>
                    <a href="/gym-management/membership/index.php">
                        Review memberships <span>→</span>
                    </a>
                </article>

            </div>

            <!-- QUICK ACTIONS AND OVERVIEW -->
            <div class="dashboard-lower">

                <section class="dashboard-panel quick-actions-panel">
                    <div class="panel-heading">
                        <div>
                            <p class="panel-eyebrow">MANAGEMENT</p>
                            <h2>Quick Actions</h2>
                            <span>Common tasks, all in one place.</span>
                        </div>
                        <span class="panel-heading-icon">↗</span>
                    </div>

                    <div class="quick-actions-grid">
                        <a href="/gym-management/members/add.php"
                           class="quick-card">
                            <span class="quick-icon quick-green">♙</span>
                            <span class="quick-copy">
                                <strong>Add Member</strong>
                                <small>Register a new member</small>
                            </span>
                            <span class="quick-arrow">→</span>
                        </a>

                        <a href="/gym-management/trainer/add.php"
                           class="quick-card">
                            <span class="quick-icon quick-blue">✦</span>
                            <span class="quick-copy">
                                <strong>Add Trainer</strong>
                                <small>Register a trainer</small>
                            </span>
                            <span class="quick-arrow">→</span>
                        </a>

                        <a href="/gym-management/membership/add.php"
                           class="quick-card">
                            <span class="quick-icon quick-purple">▣</span>
                            <span class="quick-copy">
                                <strong>Add Membership</strong>
                                <small>Create a membership</small>
                            </span>
                            <span class="quick-arrow">→</span>
                        </a>

                        <a href="/gym-management/payments/add.php"
                           class="quick-card">
                            <span class="quick-icon quick-orange">₹</span>
                            <span class="quick-copy">
                                <strong>Add Payment</strong>
                                <small>Record a payment</small>
                            </span>
                            <span class="quick-arrow">→</span>
                        </a>
                    </div>
                </section>

                <section class="dashboard-panel overview-panel">
                    <div class="panel-heading">
                        <div>
                            <p class="panel-eyebrow">AT A GLANCE</p>
                            <h2>Gym Overview</h2>
                            <span>Current operational totals.</span>
                        </div>
                        <span class="overview-status">Live</span>
                    </div>

                    <div class="overview-item">
                        <span><i class="overview-dot dot-green"></i> Active Members</span>
                        <strong><?= number_format($activeMembers) ?></strong>
                    </div>

                    <div class="overview-item">
                        <span><i class="overview-dot dot-blue"></i> Active Trainers</span>
                        <strong><?= number_format($activeTrainers) ?></strong>
                    </div>

                    <div class="overview-item">
                        <span><i class="overview-dot dot-purple"></i> Memberships</span>
                        <strong><?= number_format($activeMembership) ?></strong>
                    </div>

                    <div class="overview-item">
                        <span><i class="overview-dot dot-orange"></i> Expired Memberships</span>
                        <strong class="expired-text">
                            <?= number_format($expiredMemberships) ?>
                        </strong>
                    </div>

                    <div class="overview-footer">
                        <span class="footer-dot"></span>
                        Statistics from your database
                    </div>
                </section>

            </div>

            <footer class="dashboard-footer">
                <span>© <?= date('Y') ?> VORTEX FITNESS</span>
                <span>Built for better gym management.</span>
            </footer>

        </section>
    </main>
</div>
</body>
</html>
<script>
    
/* =====================================================
   VORTEX FITNESS — ADMIN DASHBOARD INTERACTIONS
   ===================================================== */

document.addEventListener("DOMContentLoaded", function () {
    const notificationButton = document.getElementById("notificationToggle");
    const notificationPanel = document.getElementById("notificationPanel");

    const profileButton = document.getElementById("profileToggle");
    const profilePanel = document.getElementById("profilePanel");

    const menuButton = document.getElementById("mobileMenuToggle");
    const sidebar = document.getElementById("adminSidebar");

    // Safely open or close a dropdown.
    function setDropdown(button, panel, isOpen) {
        if (!button || !panel) return;

        panel.hidden = !isOpen;
        button.setAttribute("aria-expanded", String(isOpen));
    }

    function closeDropdowns() {
        setDropdown(notificationButton, notificationPanel, false);
        setDropdown(profileButton, profilePanel, false);
    }

    // Notification dropdown.
    if (notificationButton && notificationPanel) {
        notificationButton.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();

            const shouldOpen = notificationPanel.hidden;

            closeDropdowns();
            setDropdown(notificationButton, notificationPanel, shouldOpen);
        });

        notificationPanel.addEventListener("click", function (event) {
            event.stopPropagation();
        });
    }

    // Profile dropdown.
    if (profileButton && profilePanel) {
        profileButton.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();

            const shouldOpen = profilePanel.hidden;

            closeDropdowns();
            setDropdown(profileButton, profilePanel, shouldOpen);
        });

        profilePanel.addEventListener("click", function (event) {
            event.stopPropagation();
        });
    }

    // Close dropdowns when clicking outside.
    document.addEventListener("click", function (event) {
        const clickedNotification =
            notificationButton?.contains(event.target) ||
            notificationPanel?.contains(event.target);

        const clickedProfile =
            profileButton?.contains(event.target) ||
            profilePanel?.contains(event.target);

        if (!clickedNotification && !clickedProfile) {
            closeDropdowns();
        }

        // Close the mobile sidebar when clicking outside it.
        if (
            sidebar &&
            menuButton &&
            sidebar.classList.contains("sidebar-open") &&
            !sidebar.contains(event.target) &&
            !menuButton.contains(event.target)
        ) {
            closeSidebar();
        }
    });

    // Escape closes open menus.
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeDropdowns();
            closeSidebar();
        }
    });

    // Mobile sidebar.
    function closeSidebar() {
        if (!sidebar || !menuButton) return;

        sidebar.classList.remove("sidebar-open");
        menuButton.setAttribute("aria-expanded", "false");
    }

    if (menuButton && sidebar) {
        menuButton.addEventListener("click", function (event) {
            event.stopPropagation();

            const isOpen = sidebar.classList.toggle("sidebar-open");

            menuButton.setAttribute("aria-expanded", String(isOpen));
        });

        // Close the sidebar after choosing a navigation link on mobile.
        sidebar.querySelectorAll(".nav-item").forEach(function (link) {
            link.addEventListener("click", function () {
                if (window.innerWidth <= 760) {
                    closeSidebar();
                }
            });
        });

        // Reset the sidebar state when returning to desktop size.
        window.addEventListener("resize", function () {
            if (window.innerWidth > 760) {
                closeSidebar();
            }
        });
    }
});

</script>