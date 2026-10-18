<?php
session_start();

require_once __DIR__ . '/config/database.php';

$message = '';
$messageType = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        $submittedToken = $_POST['csrf_token'] ?? '';

        if (
            !is_string($submittedToken) ||
            !hash_equals($_SESSION['csrf_token'], $submittedToken)
        ) {
            throw new RuntimeException(
                'Your session has expired. Please refresh the page and try again.'
            );
        }

        // Read and validate form fields
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $requestType = $_POST['request_type'] ?? '';
        $reason = trim($_POST['reason'] ?? '');
        $privacyAcknowledged = isset($_POST['privacy_acknowledged']);

        if ($fullName === '' || mb_strlen($fullName) > 100) {
            throw new RuntimeException('Please enter your name (maximum 100 characters).');
        }

        if (
            $email === '' ||
            mb_strlen($email) > 254 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            throw new RuntimeException('Please enter a valid email address.');
        }

        $allowedRequestTypes = ['delete', 'anonymize'];

        if (!in_array($requestType, $allowedRequestTypes, true)) {
            throw new RuntimeException('Please select a valid request type.');
        }

        if (mb_strlen($reason) > 1000) {
            throw new RuntimeException('The explanation must be 1,000 characters or fewer.');
        }

        if (!$privacyAcknowledged) {
            throw new RuntimeException(
                'Please acknowledge the information about reviewing deletion requests.'
            );
        }

        // Save the request. Do not delete member records here.
        $sql = "INSERT INTO data_deletion_requests
                    (full_name, email, request_type, reason, status, requested_at)
                VALUES
                    (:full_name, :email, :request_type, :reason, 'pending', NOW())";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':full_name'   => $fullName,
            ':email'       => $email,
            ':request_type' => $requestType,
            ':reason'      => $reason !== '' ? $reason : null,
        ]);

        // Rotate the token after a successful submission
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $message = 'Your request has been submitted successfully. The VORTEX FITNESS team will review it before taking action.';
        $messageType = 'success';

    } catch (RuntimeException $e) {
        $message = $e->getMessage();
        $messageType = 'error';

    } catch (PDOException $e) {
        error_log('Data deletion request database error: ' . $e->getMessage());

        $message = 'We could not submit your request right now. Please try again later.';
        $messageType = 'error';
    }
}

$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Request Data Deletion | VORTEX FITNESS</title>

    <link rel="stylesheet"
          href="/gym-management/css/request-data-deletion.css">
    <link rel="stylesheet"
          href="/gym-management/css/cookie-consent.css">
</head>

<body>

<header class="page-header">
    <a class="brand" href="/gym-management/index.php">
        VORTEX <span>FITNESS</span>
    </a>

    <?php
        $role = $_SESSION['role'] ?? '';

        $dashboardLinks = [
            'admin'   => '/gym-management/admin/dashboard.php',
            'member'  => '/gym-management/member/dashboard.php',
            'trainer' => '/gym-management/trainer/dashboard.php'
        ];

        $dashboardUrl = $dashboardLinks[$role] ?? '/gym-management/index.php';
    ?>

    <a class="back-link"
    href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">
        ← Return to Dashboard
    </a>
</header>

<main class="deletion-container">

    <section class="deletion-card">
        <div class="eyebrow">PRIVACY &amp; DATA RIGHTS</div>

        <h1>Request Data Deletion</h1>

        <p class="intro">
            You can use this form to ask VORTEX FITNESS to delete eligible
            personal information or anonymize it where appropriate.
        </p>

        <?php if ($message !== ''): ?>
            <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>"
                 role="status">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST"
              action="/gym-management/request-data-deletion.php">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="full_name">Full name *</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    maxlength="100"
                    autocomplete="name"
                    value="<?= htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Registered email address *</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="254"
                    autocomplete="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
                <small>
                    Enter the email address associated with your gym account, if applicable.
                </small>
            </div>

            <div class="form-group">
                <label for="request_type">What would you like us to do? *</label>
                <select id="request_type" name="request_type" required>
                    <option value="">Select a request</option>

                    <option value="delete"
                        <?= ($_POST['request_type'] ?? '') === 'delete' ? 'selected' : '' ?>>
                        Request deletion of eligible personal data
                    </option>

                    <option value="anonymize"
                        <?= ($_POST['request_type'] ?? '') === 'anonymize' ? 'selected' : '' ?>>
                        Request anonymization of personal data
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="reason">Additional details (optional)</label>
                <textarea
                    id="reason"
                    name="reason"
                    rows="4"
                    maxlength="1000"
                    placeholder="Tell us which account or personal information your request concerns."
                ><?= htmlspecialchars($_POST['reason'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="notice">
                <strong>What happens next?</strong>
                <p>
                    We will review your request and may contact you to verify
                    your identity. Some information may need to be retained
                    where required by law or for legitimate recordkeeping.
                    Submitting this form does not immediately delete your account
                    or payment records.
                </p>
            </div>

            <label class="checkbox-label">
                <input
                    type="checkbox"
                    name="privacy_acknowledged"
                    value="1"
                    required
                    <?= isset($_POST['privacy_acknowledged']) ? 'checked' : '' ?>
                >
                <span>
                    I understand that my request will be reviewed and that
                    identity verification may be required. *
                </span>
            </label>

            <button type="submit" class="submit-button">
                Submit Data Request
            </button>

        </form>

        <p class="policy-links">
            Read our
            <a href="/gym-management/privacy-policy.php">Privacy Policy</a>
            and
            <a href="/gym-management/cookies-policy.php">Cookies Policy</a>.
        </p>
    </section>

</main>

<footer class="site-footer">
    <p>&copy; <?= date('Y') ?> VORTEX FITNESS. All rights reserved.</p>
</footer>

<?php
// Include the cookie banner only if this file exists.
$cookieBanner = __DIR__ . '/includes/cookie-banner.php';

if (file_exists($cookieBanner)) {
    require $cookieBanner;
}
?>

</body>
</html>