<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

$stmt = $pdo->query(
    "SELECT
        payments.id,
        members.full_name,
        membership_plans.plan_name,
        payments.amount,
        payments.payment_date,
        payments.payment_method,
        payments.transaction_reference,
        payments.status
     FROM payments
     JOIN memberships
        ON payments.membership_id = memberships.id
     JOIN members
        ON memberships.member_id = members.id
     JOIN membership_plans
        ON memberships.plan_id = membership_plans.id
     ORDER BY payments.payment_date DESC"
);

$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="payments">
    <table border=1>
        <thead>
            <tr>
                <th>ID</th>
                <th>Member</th>
                <th>Plan</th>
                <th>Amount</th>
                <th>Payment Date</th>
                <th>Payment Method</th>
                <th>Transaction Reference</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments as $payment): ?>

            <tr>

                <td><?= $payment['id'] ?></td>

                <td><?= htmlspecialchars($payment['full_name']) ?></td>

                <td><?= htmlspecialchars($payment['plan_name']) ?></td>

                <td>₹<?= htmlspecialchars($payment['amount']) ?></td>

                <td><?= htmlspecialchars($payment['payment_date']) ?></td>

                <td><?= htmlspecialchars($payment['payment_method']) ?></td>

                <td><?= htmlspecialchars($payment['transaction_reference'] ?? '') ?></td>

                <td><?= htmlspecialchars($payment['status']) ?></td>

            </tr>

            <?php endforeach; ?>
        </tbody>
    </table>
</div>