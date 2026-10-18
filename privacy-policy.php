
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "Privacy Policy | VORTEX FITNESS";

/*
|--------------------------------------------------------------------------
| Role-based dashboard redirect
|--------------------------------------------------------------------------
| IMPORTANT:
| The session key and role values below must match your login code.
*/

$role = strtolower(trim($_SESSION['role'] ?? ''));

$dashboardRoutes = [
    'admin'   => '/gym-management/admin/dashboard.php',
    'member'  => '/gym-management/member/dashboard.php',
    'trainer' => '/gym-management/trainer/dashboard.php',
];

$dashboardUrl = $dashboardRoutes[$role]
    ?? '/gym-management/login.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#000000">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link
        rel="stylesheet"
        href="/gym-management/css/privacy-policy.css?v=2"
    >
</head>

<body>

    <!-- Header -->
    <header class="privacy-header">

        <a
            href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>"
            class="brand"
            aria-label="Return to your dashboard"
        >
            <span class="brand-icon" aria-hidden="true">✦</span>
            <span>VORTEX <strong>FITNESS</strong></span>
        </a>

        <a
            href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>"
            class="back-link"
        >
            &larr; Return to Dashboard
        </a>

    </header>

    <!-- Main Content -->
    <main class="privacy-container">

        <div class="privacy-label">
            <span class="status-dot"></span>
            YOUR PRIVACY MATTERS
        </div>

        <h1>Privacy <span>Policy</span></h1>

        <p class="privacy-intro">
            At VORTEX FITNESS, we respect your privacy and are committed
            to handling your personal information responsibly.
            This policy explains what information may be collected,
            how it may be used, and how it is protected when you use
            our gym management system.
        </p>

        <p class="last-updated">
            LAST UPDATED:
            <strong><?= date('F j, Y') ?></strong>
        </p>

        <div class="privacy-notice">
            <strong>Important:</strong>
            This is a starter privacy policy template. Update it to reflect
            your actual data practices, services, retention periods, and
            applicable legal requirements before publishing it for real users.
        </div>

        <!-- Section 01 -->
        <section class="policy-section">
            <span class="section-number">01</span>

            <div>
                <h2>Information We Collect</h2>

                <p>
                    Depending on the features you use, VORTEX FITNESS may process:
                </p>

                <ul>
                    <li>Identity and contact details, such as name, email address, and phone number.</li>
                    <li>Account information, such as username, role, and authentication records.</li>
                    <li>Membership details, including plan, joining date, and membership status.</li>
                    <li>Attendance and payment records needed to administer gym services.</li>
                    <li>Technical information generated when you access the website, where applicable.</li>
                </ul>

                <p>
                    We should collect only information needed for legitimate,
                    stated purposes.
                </p>
            </div>
        </section>

        <!-- Section 02 -->
        <section class="policy-section">
            <span class="section-number">02</span>

            <div>
                <h2>How We Use Information</h2>

                <p>Information may be used to:</p>

                <ul>
                    <li>Create and manage member and staff accounts.</li>
                    <li>Administer memberships, attendance, and payments.</li>
                    <li>Provide authorized users with relevant dashboard information.</li>
                    <li>Maintain system security and troubleshoot technical problems.</li>
                    <li>Respond to support requests and meet applicable legal obligations.</li>
                </ul>
            </div>
        </section>

        <!-- Section 03 -->
        <section class="policy-section">
            <span class="section-number">03</span>

            <div>
                <h2>Passwords and Account Security</h2>

                <p>
                    Accounts should be protected using appropriate security controls.
                    Passwords should be stored using secure password hashing, never
                    as plain text. Access to personal records should be limited to
                    authorized users according to their roles.
                </p>

                <p>
                    No website or storage system can be guaranteed completely secure.
                    Users should report suspected unauthorized access promptly.
                </p>
            </div>
        </section>

        <!-- Section 04 -->
        <section class="policy-section">
            <span class="section-number">04</span>

            <div>
                <h2>Sharing and Disclosure</h2>

                <p>
                    Personal information should not be sold or disclosed to third
                    parties except as described in the final policy. Information may
                    need to be shared with authorized service providers who support
                    hosting, system operations, payments, or legal compliance.
                </p>

                <p>
                    Before publishing this policy, list the actual providers used by
                    your application and explain when and why information is shared.
                </p>
            </div>
        </section>

        <!-- Section 05 -->
        <section class="policy-section">
            <span class="section-number">05</span>

            <div>
                <h2>Data Retention and Deletion</h2>

                <p>
                    Personal information should be retained only for as long as
                    necessary for the purposes for which it was collected, subject
                    to applicable legal and operational requirements.
                </p>

                <p>
                    Establish retention periods for member accounts, attendance
                    history, and financial records. Requests for correction or
                    deletion should be handled according to applicable law.
                </p>
            </div>
        </section>

        <!-- Section 06 -->
        <section class="policy-section">
            <span class="section-number">06</span>

            <div>
                <h2>Cookies and Technical Data</h2>

                <p>
                    The application may use session cookies or similar technologies
                    to support login sessions and essential functionality. If analytics,
                    advertising, or other tracking tools are added, this policy should
                    describe them and explain the available user choices.
                </p>
            </div>
        </section>

        <!-- Section 07 -->
        <section class="policy-section">
            <span class="section-number">07</span>

            <div>
                <h2>Your Privacy Rights</h2>

                <p>
                    Depending on applicable law, you may have rights to request access
                    to your personal information, correct inaccurate details, request
                    deletion, or raise concerns about how your data is handled.
                </p>

                <p>
                    Requests should be verified and handled through the contact
                    channel designated by the gym.
                </p>
            </div>
        </section>

        <!-- Section 08 -->
        <section class="policy-section">
            <span class="section-number">08</span>

            <div>
                <h2>Children's Privacy</h2>

                <p>
                    If the gym accepts members under the age of 18, appropriate
                    consent, account management, and data handling procedures
                    should be established in accordance with applicable law.
                </p>
            </div>
        </section>

        <!-- Section 09 -->
        <section class="policy-section">
            <span class="section-number">09</span>

            <div>
                <h2>Changes to This Policy</h2>

                <p>
                    This policy may be updated when our services, data practices,
                    or legal obligations change. The revised version should include
                    an updated date and be made available to affected users.
                </p>
            </div>
        </section>

        <!-- Section 10 -->
        <section class="policy-contact">
            <span class="section-number">10</span>

            <div>
                <h2>Contact Us</h2>

                <p>
                    If you have questions about this Privacy Policy or the handling
                    of your personal information, contact the gym using its official
                    contact details.
                </p>

                <p class="contact-placeholder">
                    Official email and contact details: Add your verified details here.
                </p>
            </div>
        </section>

        <div class="privacy-bottom">
            <span class="bottom-dot"></span>
            YOUR TRUST. OUR RESPONSIBILITY.
        </div>

    </main>

    <!-- Footer -->
    <footer class="privacy-footer">
        <span>VORTEX <strong>FITNESS</strong></span>

        <span>STRENGTH · DISCIPLINE · PROGRESS</span>

        <a href="/gym-management/index.php">
            Back to Home ↑
        </a>
    </footer>

</body>
</html>
