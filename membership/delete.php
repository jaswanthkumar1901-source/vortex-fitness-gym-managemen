<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit("Invalid membership ID.");
}

$stmt = $pdo->prepare("
    SELECT
        memberships.id,
        members.full_name AS member_name,
        membership_plans.plan_name,
        memberships.start_date,
        memberships.end_date,
        memberships.status
    FROM memberships
    JOIN members ON memberships.member_id = members.id
    JOIN membership_plans ON memberships.plan_id = membership_plans.id
    WHERE memberships.id = ?
");
$stmt->execute([$id]);
$membership = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$membership) {
    http_response_code(404);
    exit("Membership not found.");
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $stmt = $pdo->prepare("DELETE FROM memberships WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: index.php?deleted=1");
        exit;
    } catch (PDOException $e) {
        $error_message = "Unable to delete membership. Please try again.";
    }
}

require_once "../includes/header.php";
?>
<link rel="stylesheet" href="/gym-management/css/membership-form.css?v=7">

<div class="membership-form-page">
    <div class="membership-form-header">
        <div>
            <span class="membership-form-label">MEMBERSHIP MANAGEMENT</span>
            <h1>Delete Membership</h1>
            <p>Review the membership information before deleting.</p>
        </div>
        <a href="/gym-management/membership/index.php" class="membership-back-button">← Back to Memberships</a>
    </div>

    <?php if ($error_message !== ""): ?>
        <div class="membership-delete-error"><?= htmlspecialchars($error_message, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <div class="membership-delete-card">
        <div class="membership-delete-icon" aria-hidden="true">!</div>
        <h2>Delete Membership?</h2>
        <p class="membership-delete-message">Are you sure you want to delete this membership?</p>

        <div class="membership-delete-warning">
            <strong>Warning:</strong> This action cannot be undone.
        </div>

        <div class="membership-delete-info">
            <div class="membership-delete-detail">
                <span>Member</span>
                <strong><?= htmlspecialchars($membership["member_name"], ENT_QUOTES, "UTF-8") ?></strong>
            </div>
            <div class="membership-delete-detail">
                <span>Membership Plan</span>
                <strong><?= htmlspecialchars($membership["plan_name"], ENT_QUOTES, "UTF-8") ?></strong>
            </div>
            <div class="membership-delete-detail">
                <span>Start Date</span>
                <strong><?= htmlspecialchars($membership["start_date"], ENT_QUOTES, "UTF-8") ?></strong>
            </div>
            <div class="membership-delete-detail">
                <span>End Date</span>
                <strong><?= htmlspecialchars($membership["end_date"], ENT_QUOTES, "UTF-8") ?></strong>
            </div>
            <div class="membership-delete-detail">
                <span>Status</span>
                <strong><?= htmlspecialchars(ucfirst($membership["status"]), ENT_QUOTES, "UTF-8") ?></strong>
            </div>
        </div>

        <form method="POST" class="membership-delete-actions">
            <a href="/gym-management/membership/index.php" class="membership-cancel-button">Cancel</a>
            <button type="submit" class="membership-delete-button" onclick="return confirm('Are you sure you want to permanently delete this membership?');">Delete Membership</button>
        </form>
    </div>
</div>
