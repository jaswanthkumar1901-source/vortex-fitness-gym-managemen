
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Terms & Conditions | VORTEX FITNESS';


$role = strtolower(trim((string) ($_SESSION['role'] ?? '')));

$dashboardUrl = '/gym-management/index.php';

switch ($role) {
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



$cookieBannerPath = __DIR__ . '/includes/cookie-banner.php';
if (!is_file($cookieBannerPath)) {
    $cookieBannerPath = __DIR__ . '/includes/cookies-banner.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#080a08">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="stylesheet" href="/gym-management/css/terms.css?v=2">
    <link rel="stylesheet" href="/gym-management/css/cookie-consent.css?v=2">

    <script src="/gym-management/js/cookie-consent.js?v=2" defer></script>
</head>

<body>

<header class="terms-header">
    <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>"
       class="brand"
       aria-label="VORTEX FITNESS Dashboard">

        <span class="brand-icon" aria-hidden="true">✦</span>
        <span>VORTEX <strong>FITNESS</strong></span>
    </a>

    <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>"
       class="home-link">
        <span aria-hidden="true">←</span> Return to Dashboard
    </a>
</header>

<main class="terms-container">

    <div class="terms-label">
        <span class="status-dot"></span>
        PLEASE READ CAREFULLY
    </div>

    <h1>Terms &amp; <span>Conditions</span></h1>

    <p class="terms-intro">
        Welcome to VORTEX FITNESS. These Terms and Conditions explain
        the general rules for using our gym services and gym management
        system. By accessing or using the service, you agree to the
        terms applicable to you.
    </p>

    <p class="last-updated">
        LAST UPDATED: <strong><?= date('F j, Y') ?></strong>
    </p>

    <div class="terms-notice">
        <strong>Important:</strong>
        This is a starter template. Before using it for actual gym members,
        customize it to reflect your real membership contracts, pricing,
        cancellation rules, facilities, and applicable laws.
    </div>

    <section class="terms-section">
        <span class="section-number">01</span>
        <div>
            <h2>Acceptance of Terms</h2>
            <p>
                By using the VORTEX FITNESS website, management system,
                or gym services, you agree to comply with these Terms
                and Conditions and any applicable gym rules communicated
                to you. If you do not agree, please do not use the
                relevant service.
            </p>
        </div>
    </section>

    <section class="terms-section">
        <span class="section-number">02</span>
        <div>
            <h2>Account Registration and Security</h2>
            <ul>
                <li>Provide accurate and up-to-date information when registering.</li>
                <li>Keep your login credentials confidential.</li>
                <li>Do not share your account or use another person's account without authorization.</li>
                <li>Notify the gym administrator if you suspect unauthorized account access.</li>
                <li>Administrators may restrict access when necessary for security or legitimate operational reasons.</li>
            </ul>
        </div>
    </section>

    <section class="terms-section">
        <span class="section-number">03</span>
        <div>
            <h2>Membership and Access</h2>
            <p>
                Membership access is subject to the selected plan, its
                duration, payment status, and the gym's published rules.
                Members are responsible for checking their membership
                details and expiry dates.
            </p>
            <p>
                Membership benefits, opening hours, access restrictions,
                renewal arrangements, and eligibility requirements should
                be communicated clearly by the gym.
            </p>
        </div>
    </section>

    <section class="terms-section">
        <span class="section-number">04</span>
        <div>
            <h2>Fees, Payments and Refunds</h2>
            <p>
                Membership fees and any additional charges should be
                disclosed before purchase. Members are responsible for
                paying applicable fees according to the agreed payment
                schedule.
            </p>
            <p>
                Refunds, cancellations, failed payments, and membership
                pauses will be handled according to the specific policy
                disclosed at the time of enrollment and applicable law.
                Refund eligibility depends on the actual policy and
                applicable legal requirements.
            </p>
        </div>
    </section>

    <section class="terms-section">
        <span class="section-number">05</span>
        <div>
            <h2>Gym Rules and Conduct</h2>
            <ul>
                <li>Use equipment responsibly and follow posted instructions.</li>
                <li>Respect gym staff, trainers, and other members.</li>
                <li>Return equipment to its designated place after use.</li>
                <li>Maintain appropriate hygiene and wear suitable workout attire.</li>
                <li>Do not damage equipment, harass others, or interfere with another member's safe use of the facilities.</li>
            </ul>
            <p>
                Serious or repeated violations may lead to reasonable
                restrictions or suspension, subject to the membership
                agreement and applicable law.
            </p>
        </div>
    </section>

    <section class="terms-section">
        <span class="section-number">06</span>
        <div>
            <h2>Health, Fitness and Safety</h2>
            <p>
                Exercise involves physical effort and may carry risks.
                Members should assess their fitness to exercise and seek
                appropriate medical advice when needed, particularly if
                they have a health concern.
            </p>
            <p>
                Follow equipment instructions and trainer guidance, stop
                exercising if you feel unwell, and promptly report hazards
                or injuries to gym staff.
            </p>
            <p>
                Nothing in these terms excludes liability where exclusion
                is prohibited by applicable law.
            </p>
        </div>
    </section>

    <section class="terms-section">
        <span class="section-number">07</span>
        <div>
            <h2>Attendance and Records</h2>
            <p>
                The management system may record attendance, membership
                status, payment details, and other information needed to
                administer gym operations. Members should notify authorized
                staff of errors in their records.
            </p>
            <p>
                The handling of personal information is described in our
                Privacy Policy.
            </p>
            <a class="policy-link"
               href="/gym-management/privacy-policy.php">
                Read our Privacy Policy <span aria-hidden="true">↗</span>
            </a>
        </div>
    </section>

    <section class="terms-section">
        <span class="section-number">08</span>
        <div>
            <h2>Acceptable Use of the System</h2>
            <p>
                Users must not attempt to access records without
                authorization, bypass security controls, interfere with
                system operations, upload malicious content, or misuse
                another user's information.
            </p>
            <p>
                Access to administrative features is limited to authorized
                personnel. Users must follow the permissions assigned to
                their accounts.
            </p>
        </div>
    </section>

    <section class="terms-section">
        <span class="section-number">09</span>
        <div>
            <h2>Service Availability and Changes</h2>
            <p>
                We aim to keep our management system and services available,
                but temporary interruptions may occur because of maintenance,
                technical problems, or circumstances outside our control.
            </p>
            <p>
                Features and operating procedures may change when reasonably
                necessary. Material changes to membership arrangements
                should be communicated as required by applicable law
                and the relevant agreement.
            </p>
        </div>
    </section>

    <section class="terms-section">
        <span class="section-number">10</span>
        <div>
            <h2>Suspension and Termination</h2>
            <p>
                Access may be suspended or terminated for security reasons,
                serious rule violations, misuse of the system, or other
                legitimate reasons consistent with the membership agreement
                and applicable law.
            </p>
            <p>
                Any outstanding payments, cancellation rights, refunds,
                and continuing obligations will be handled according to
                the applicable agreement and legal requirements.
            </p>
        </div>
    </section>

    <section class="terms-section">
        <span class="section-number">11</span>
        <div>
            <h2>Intellectual Property</h2>
            <p>
                Unless otherwise stated, the VORTEX FITNESS branding,
                website design, and original system content are owned
                by or used with permission by their respective rights
                holders. You may not reproduce or commercially exploit
                protected material without appropriate authorization.
            </p>
        </div>
    </section>

    <section class="terms-section">
        <span class="section-number">12</span>
        <div>
            <h2>Changes to These Terms</h2>
            <p>
                These Terms and Conditions may be revised when our services
                or legal requirements change. The current version should
                be published on this page with an updated date. Where
                required, affected users will be notified of material changes.
            </p>
        </div>
    </section>

    <section class="terms-contact">
        <span class="section-number">13</span>
        <div>
            <h2>Contact Information</h2>
            <p>
                For questions about these Terms and Conditions, membership
                rules, or gym services, contact the gym using its official
                contact details.
            </p>
            <div class="contact-placeholder">
                Official gym email, phone number, and business address:
                Add your verified details here.
            </div>
        </div>
    </section>

    <div class="terms-bottom">
        <span class="bottom-dot"></span>
        STRENGTH · DISCIPLINE · PROGRESS
    </div>

</main>

<footer class="terms-footer">
    <span class="footer-brand">
        VORTEX <strong>FITNESS</strong>
    </span>

    <div class="footer-links">
        <a href="/gym-management/privacy-policy.php">Privacy Policy</a>
        <a href="/gym-management/cookie-policy.php">Cookie Policy</a>
        <a href="/gym-management/terms-and-conditions.php">Terms &amp; Conditions</a>
    </div>

    <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">
        Return to Dashboard ↑
    </a>
</footer>

<?php
if (is_file($cookieBannerPath)) {
    require_once $cookieBannerPath;
}
?>

</body>
</html>
