<?php

if (ob_get_level() === 0) {
    ob_start();
}
if (!defined('VORTEX_FOOTER_BUFFER_STARTED')) {
    define('VORTEX_FOOTER_BUFFER_STARTED', true);

    ob_start(static function (string $html): string {
        // Do not alter non-HTML responses.
        $isHtml = true;
        foreach (headers_list() as $header) {
            if (stripos($header, 'Content-Type:') === 0) {
                $isHtml = stripos($header, 'text/html') !== false;
                break;
            }
        }

        if (!$isHtml || trim($html) === '') {
            return $html;
        }

        // Prevent duplicate footer output if the page already includes it.
        if (strpos($html, '<!-- VORTEX-SHARED-FOOTER -->') !== false) {
            return $html;
        }

        $footerFile = __DIR__ . '/footer.php';
        if (!is_file($footerFile)) {
            error_log('VORTEX shared footer file not found: ' . $footerFile);
            return $html;
        }

        ob_start();
        require $footerFile;
        $footerHtml = (string) ob_get_clean();

        // Insert before the closing body tag; otherwise append to the response.
        if (preg_match('/<\/body\s*>/i', $html)) {
            return preg_replace(
                '/<\/body\s*>/i',
                $footerHtml . "\n</body>",
                $html,
                1
            ) ?? $html;
        }

        return $html . $footerHtml;
    });
}

// =====================================================
// VORTEX FITNESS - SHARED HEADER
// File: includes/header.php
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Current user
$userRole = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? 'User';
$headerUserId = (int) ($_SESSION['user_id'] ?? 0);

// Escape output
if (!function_exists('vortexEscape')) {
    function vortexEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

// Role-based dashboard
$dashboardUrl = match ($userRole) {
    'admin' => '/gym-management/admin/dashboard.php',
    'trainer' => '/gym-management/trainer_portal/dashboard.php',
    'member' => '/gym-management/member/dashboard.php',
    default => '/gym-management/auth/login.php'
};

// Active navigation link
$currentPath = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);

$currentPath = rtrim((string) $currentPath, '/');

$isActive = function (string $url) use ($currentPath): string {
    $urlPath = parse_url($url, PHP_URL_PATH);
    $urlPath = rtrim((string) $urlPath, '/');

    return $currentPath === $urlPath ? 'active' : '';
};

// User avatar
$avatarLetter = strtoupper(
    substr(trim($username), 0, 1)
);

if ($avatarLetter === '') {
    $avatarLetter = 'U';
}

$roleLabel = ucfirst($userRole ?: 'User');

// Notifications
$headerNotifications = [];
$unreadNotificationCount = 0;

if (
    $headerUserId > 0 &&
    isset($pdo) &&
    $pdo instanceof PDO
) {
    try {
        $notificationStmt = $pdo->prepare(
            "SELECT id, title, message, is_read, created_at
             FROM notifications
             WHERE user_id = ?
             ORDER BY created_at DESC, id DESC
             LIMIT 10"
        );

        $notificationStmt->execute([$headerUserId]);

        $headerNotifications = $notificationStmt->fetchAll(
            PDO::FETCH_ASSOC
        );

        foreach ($headerNotifications as $notification) {
            if ((int) $notification['is_read'] === 0) {
                $unreadNotificationCount++;
            }
        }
    } catch (PDOException $e) {
        error_log(
            'VORTEX header notification query failed: ' .
            $e->getMessage()
        );

        $headerNotifications = [];
        $unreadNotificationCount = 0;
    }
}
?>

<link
    rel="stylesheet"
    href="/gym-management/css/header.css?v=7"
>

<link
    rel="stylesheet"
    href="/gym-management/css/privacy-accessibility.css?v=1"
>
<link rel="stylesheet" href="/gym-management/css/footer.css?v=1">

<header class="admin-header">

    <!-- BRAND -->
    <a
        href="<?= vortexEscape($dashboardUrl) ?>"
        class="admin-brand"
        aria-label="VORTEX FITNESS dashboard"
    >
        <span class="brand-icon" aria-hidden="true">💪</span>
        <span class="brand-text">VORTEX</span>
    </a>

    <!-- ROLE-BASED NAVIGATION -->
    <nav class="admin-nav" aria-label="Main navigation">

        <?php if ($userRole === 'admin'): ?>

            <a
                href="/gym-management/admin/dashboard.php"
                class="<?= $isActive('/gym-management/admin/dashboard.php') ?>"
            >Dashboard</a>

            <a
                href="/gym-management/members/index.php"
                class="<?= $isActive('/gym-management/members/index.php') ?>"
            >Members</a>

            <a
                href="/gym-management/plans/index.php"
                class="<?= $isActive('/gym-management/plans/index.php') ?>"
            >Plans</a>

            <a
                href="/gym-management/membership/index.php"
                class="<?= $isActive('/gym-management/membership/index.php') ?>"
            >Memberships</a>

            <a
                href="/gym-management/attendance/index.php"
                class="<?= $isActive('/gym-management/attendance/index.php') ?>"
            >Attendance</a>

            <a
                href="/gym-management/payments/index.php"
                class="<?= $isActive('/gym-management/payments/index.php') ?>"
            >Payments</a>

            <a
                href="/gym-management/trainer/index.php"
                class="<?= $isActive('/gym-management/trainer/index.php') ?>"
            >Trainers</a>

            <a
                href="/gym-management/users/index.php"
                class="<?= $isActive('/gym-management/users/index.php') ?>"
            >Users</a>

            <a
                href="/gym-management/reports/index.php"
                class="<?= $isActive('/gym-management/reports/index.php') ?>"
            >Reports</a>

        <?php elseif ($userRole === 'trainer'): ?>

            <a
                href="/gym-management/trainer_portal/dashboard.php"
                class="<?= $isActive('/gym-management/trainer_portal/dashboard.php') ?>"
            >Dashboard</a>

            <a
                href="/gym-management/trainer_portal/my_members.php"
                class="<?= $isActive('/gym-management/trainer_portal/my_members.php') ?>"
            >My Members</a>

            <a
                href="/gym-management/trainer_portal/attendance.php"
                class="<?= $isActive('/gym-management/trainer_portal/attendance.php') ?>"
            >Attendance</a>

        <?php elseif ($userRole === 'member'): ?>

            <a
                href="/gym-management/member/dashboard.php"
                class="<?= $isActive('/gym-management/member/dashboard.php') ?>"
            >Dashboard</a>

            <a
                href="/gym-management/member/membership.php"
                class="<?= $isActive('/gym-management/member/membership.php') ?>"
            >My Membership</a>

            <a
                href="/gym-management/member/attendance.php"
                class="<?= $isActive('/gym-management/member/attendance.php') ?>"
            >My Attendance</a>

            <a
                href="/gym-management/member/payments.php"
                class="<?= $isActive('/gym-management/member/payments.php') ?>"
            >My Payments</a>

        <?php endif; ?>

    </nav>

    <!-- USER ACTIONS -->
    <div class="admin-user">

        <!-- NOTIFICATIONS -->
        <div class="header-dropdown">

            <button
                type="button"
                class="header-icon-btn"
                id="notificationToggle"
                aria-label="Notifications"
                aria-expanded="false"
                aria-controls="notificationPanel"
            >
                <span aria-hidden="true">🔔</span>

                <?php if ($unreadNotificationCount > 0): ?>
                    <span class="notification-count">
                        <?= $unreadNotificationCount > 99
                            ? '99+'
                            : $unreadNotificationCount ?>
                    </span>
                <?php endif; ?>
            </button>

            <div
                class="header-dropdown-panel notification-panel"
                id="notificationPanel"
                hidden
            >
                <div class="dropdown-heading">
                    <strong>Notifications</strong>
                    <span>
                        <?= $unreadNotificationCount ?> unread
                    </span>
                </div>

                <div class="notification-list">

                    <?php if (empty($headerNotifications)): ?>

                        <div class="notification-empty">
                            <span aria-hidden="true">🔔</span>
                            <p>No notifications yet.</p>
                        </div>

                    <?php else: ?>

                        <?php foreach ($headerNotifications as $notification): ?>

                            <div class="notification-item <?= (int) $notification['is_read'] === 0 ? 'unread' : '' ?>">

                                <div class="notification-item-title">
                                    <?= vortexEscape($notification['title'] ?? '') ?>
                                </div>

                                <p>
                                    <?= vortexEscape($notification['message'] ?? '') ?>
                                </p>

                                <small>
                                    <?= vortexEscape($notification['created_at'] ?? '') ?>
                                </small>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>
            </div>

        </div>

        <!-- PROFILE -->
        <div class="header-dropdown">

            <button
                type="button"
                class="profile-trigger"
                id="profileToggle"
                aria-label="User profile"
                aria-expanded="false"
                aria-controls="profilePanel"
            >
                <span class="profile-avatar" aria-hidden="true">
                    <?= vortexEscape($avatarLetter) ?>
                </span>

                <span class="user-info">
                    <strong><?= vortexEscape($username) ?></strong>
                    <small><?= vortexEscape($roleLabel) ?></small>
                </span>

                <span class="profile-chevron" aria-hidden="true">▾</span>
            </button>

            <div
                class="header-dropdown-panel profile-panel"
                id="profilePanel"
                hidden
            >
                <div class="profile-panel-heading">

                    <span class="profile-avatar" aria-hidden="true">
                        <?= vortexEscape($avatarLetter) ?>
                    </span>

                    <div>
                        <strong><?= vortexEscape($username) ?></strong>
                        <small><?= vortexEscape($roleLabel) ?></small>
                    </div>

                </div>

                <div class="profile-menu">

                    <a href="/gym-management/auth/change_password.php">
                        <span aria-hidden="true">🔑</span>
                        Change Password
                    </a>

                    <a href="/gym-management/auth/logout.php" class="logout-link">
                        <span aria-hidden="true">↪</span>
                        Logout
                    </a>

                </div>
            </div>

        </div>

    </div>

</header>

<!-- HEADER DROPDOWNS -->
<script>
(function () {
    "use strict";

    const notificationToggle =
        document.getElementById("notificationToggle");

    const notificationPanel =
        document.getElementById("notificationPanel");

    const profileToggle =
        document.getElementById("profileToggle");

    const profilePanel =
        document.getElementById("profilePanel");

    if (
        !notificationToggle ||
        !notificationPanel ||
        !profileToggle ||
        !profilePanel
    ) {
        return;
    }

    function closeDropdowns() {
        notificationPanel.hidden = true;
        profilePanel.hidden = true;

        notificationToggle.setAttribute("aria-expanded", "false");
        profileToggle.setAttribute("aria-expanded", "false");
    }

    function toggleDropdown(button, panel, otherButton, otherPanel) {
        const shouldOpen = panel.hidden;

        closeDropdowns();

        if (shouldOpen) {
            panel.hidden = false;
            button.setAttribute("aria-expanded", "true");
        }

        if (shouldOpen) {
            otherPanel.hidden = true;
            otherButton.setAttribute("aria-expanded", "false");
        }
    }

    notificationToggle.addEventListener("click", function () {
        toggleDropdown(
            notificationToggle,
            notificationPanel,
            profileToggle,
            profilePanel
        );
    });

    profileToggle.addEventListener("click", function () {
        toggleDropdown(
            profileToggle,
            profilePanel,
            notificationToggle,
            notificationPanel
        );
    });

    document.addEventListener("click", function (event) {
        const clickedInside =
            notificationToggle.contains(event.target) ||
            notificationPanel.contains(event.target) ||
            profileToggle.contains(event.target) ||
            profilePanel.contains(event.target);

        if (!clickedInside) {
            closeDropdowns();
        }
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeDropdowns();
        }
    });
})();
</script>

<!-- SESSION INACTIVITY HANDLING -->
<script>
(function () {
    "use strict";

    const TIMEOUT_MS = 120 * 60 * 1000;
    const ACTIVITY_PING_MS = 60 * 1000;

    let idleTimer;
    let lastPing = Date.now();
    let activitySincePing = false;
    let requestInProgress = false;
    let sessionEnded = false;

    function redirectHome() {
        if (sessionEnded) return;

        sessionEnded = true;

        const data = new URLSearchParams();
        data.set("action", "logout");

        fetch("/gym-management/session_activity.php", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: data.toString(),
            keepalive: true
        }).finally(function () {
            window.location.replace("/gym-management/index.php");
        });
    }

    function resetIdleTimer() {
        if (sessionEnded) return;

        activitySincePing = true;
        clearTimeout(idleTimer);

        idleTimer = setTimeout(function () {
            redirectHome();
        }, TIMEOUT_MS);
    }

    async function sendActivityPing() {
        if (
            sessionEnded ||
            requestInProgress ||
            !activitySincePing
        ) {
            return;
        }

        requestInProgress = true;
        activitySincePing = false;

        try {
            const response = await fetch(
                "/gym-management/session_activity.php",
                {
                    method: "GET",
                    credentials: "same-origin",
                    cache: "no-store",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    }
                }
            );

            if (response.status === 401) {
                window.location.replace("/gym-management/index.php");
                return;
            }

            if (!response.ok) {
                activitySincePing = true;
            } else {
                lastPing = Date.now();
            }

        } catch (error) {
            activitySincePing = true;

        } finally {
            requestInProgress = false;
        }
    }

    [
        "mousemove",
        "mousedown",
        "keydown",
        "scroll",
        "touchstart",
        "click"
    ].forEach(function (eventName) {
        document.addEventListener(
            eventName,
            resetIdleTimer,
            { passive: true }
        );
    });

    setInterval(function () {
        if (Date.now() - lastPing >= ACTIVITY_PING_MS) {
            sendActivityPing();
        }
    }, 5000);

    resetIdleTimer();
})();
</script>



<?php
// VORTEX FITNESS - GLOBAL FOOTER OUTPUT HANDLER
// Must run before any page HTML is output.

if (!defined('VORTEX_FOOTER_HANDLER_REGISTERED')) {
    define('VORTEX_FOOTER_HANDLER_REGISTERED', true);

    ob_start();

    register_shutdown_function(static function (): void {
        $pageHtml = ob_get_clean();

        if ($pageHtml === false) {
            return;
        }

        // Do not append HTML to JSON or other non-HTML responses.
        foreach (headers_list() as $header) {
            if (stripos($header, 'Content-Type:') === 0
                && stripos($header, 'text/html') === false) {
                echo $pageHtml;
                return;
            }
        }

        // Do not add the footer twice.
        if (strpos($pageHtml, '<!-- VORTEX-SHARED-FOOTER -->') !== false) {
            echo $pageHtml;
            return;
        }

        $footerFile = __DIR__ . '/footer.php';

        if (!is_file($footerFile)) {
            error_log('VORTEX footer missing: ' . $footerFile);
            echo $pageHtml;
            return;
        }

        // Capture footer markup without sending it immediately.
        ob_start();
        require $footerFile;
        $footerHtml = ob_get_clean();

        if ($footerHtml === false) {
            $footerHtml = '';
        }

        // Insert the footer inside the document body.
        if (stripos($pageHtml, '</body>') !== false) {
            $pageHtml = preg_replace(
                '/<\/body\s*>/i',
                $footerHtml . "\n</body>",
                $pageHtml,
                1
            );
        } else {
            $pageHtml .= $footerHtml;
        }

        echo $pageHtml;
    });
}

