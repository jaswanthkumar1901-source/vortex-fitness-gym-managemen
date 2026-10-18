<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Cookie Policy | VORTEX FITNESS';

$pageTitle = 'Cookie Policy | VORTEX FITNESS';
$cookieBannerFile = __DIR__ . '/includes/cookie-banner.php';

// Support the alternative filename used in the earlier code.
if (!is_file($cookieBannerFile)) {
    $alternativeBannerFile = __DIR__ . '/includes/cookies-banner.php';

    if (is_file($alternativeBannerFile)) {
        $cookieBannerFile = $alternativeBannerFile;
    } else {
        $cookieBannerFile = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101010">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Cookie policy page styles -->
    <link
        rel="stylesheet"
        href="/gym-management/css/cookie-policy.css?v=2"
    >

    <!-- Cookie banner and preferences styles -->
    <link
        rel="stylesheet"
        href="/gym-management/css/cookie-consent.css?v=2"
    >

    <!-- Cookie consent functionality -->
    <script
        src="/gym-management/js/cookie-consent.js?v=2"
        defer
    ></script>
</head>

<body>

<header class="cookie-policy-header">
    <a
        class="cookie-policy-brand"
        href="/gym-management/index.php"
    >
        VORTEX <span>FITNESS</span>
    </a>

    <?php
        $dashboardUrl = '/gym-management/index.php';

        if (isset($_SESSION['role'])) {
            switch (strtolower($_SESSION['role'])) {
                case 'admin':
                case 'administrator':
                    $dashboardUrl = '/gym-management/admin/dashboard.php';
                    break;

                case 'member':
                    $dashboardUrl = '/gym-management/member/dashboard.php';
                    break;

                case 'trainer':
                    $dashboardUrl = '/gym-management/trainer/dashboard.php';
                    break;
            }
        }
    ?>

    <a class="cookie-policy-home" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">
        &larr; Return to Dashboard
    </a>
</header>

<main class="cookie-policy-container">

    <p class="cookie-policy-eyebrow">
        TRANSPARENCY &amp; CONTROL
    </p>

    <h1>Cookie Policy</h1>

    <p><strong>VORTEX FITNESS</strong></p>

    <p class="cookie-policy-updated">
        Last updated: October 18, 2026
    </p>

    <section>
        <h2>1. What are cookies?</h2>
        <p>
            Cookies are small files stored by a website in your browser.
            Similar technologies can help maintain sessions and remember
            preferences.
        </p>
    </section>

    <section>
        <h2>2. Essential cookies</h2>
        <p>
            Essential cookies may support login sessions, authentication,
            security, and core website functions. Blocking them may prevent
            some features from working correctly.
        </p>
    </section>

    <section>
        <h2>3. Optional analytics</h2>
        <p>
            Optional analytics should only be enabled after any consent
            required by applicable law has been obtained. This example
            consent component does not activate an analytics provider.
        </p>
    </section>

    <section>
        <h2>4. Managing your preferences</h2>
        <p>
            You can accept all optional categories, reject optional
            categories, or save your preferences using the consent
            controls. You can reopen the controls below to change
            your saved choice.
        </p>

        <button
            type="button"
            class="cookie-policy-preferences"
            onclick="if (typeof window.openCookiePreferences === 'function') {
                window.openCookiePreferences();
            } else {
                alert('Cookie preferences are unavailable. Please reload the page and try again.');
            }"
        >
            Manage Cookie Preferences
        </button>
    </section>

    <section>
        <h2>5. Third-party services</h2>
        <p>
            Before publishing, list the actual third-party services used
            by the website and explain their purposes and relevant
            privacy information.
        </p>
    </section>

    <section>
        <h2>6. Contact</h2>
        <p>
            Add your verified business contact details and privacy-request
            contact method before publishing this policy.
        </p>
    </section>

    <p>
        <a href="/gym-management/privacy-policy.php">
            Read our Privacy Policy
        </a>
    </p>

</main>

<footer class="cookie-policy-footer">
    &copy; <?= date('Y') ?> VORTEX FITNESS. All rights reserved.
</footer>

<?php
// Include the shared banner only if its file exists.
if ($cookieBannerFile !== null) {
    require $cookieBannerFile;
} else {
    error_log(
        'VORTEX cookie banner not found in: ' .
        __DIR__ . '/includes/'
    );
}
?>

</body>
</html>
