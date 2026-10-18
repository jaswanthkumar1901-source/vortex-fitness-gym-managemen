<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

$stmt = $pdo->query(
    "SELECT
        memberships.id,
        members.full_name,
        membership_plans.plan_name,
        memberships.start_date,
        memberships.end_date,
        memberships.status
     FROM memberships
     JOIN members
        ON memberships.member_id = members.id
     JOIN membership_plans
        ON memberships.plan_id = membership_plans.id
     ORDER BY memberships.id DESC"
);

$memberships = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="memberships">

    <h1>Membership Report</h1>

    <table border="1">

        <thead>
            <tr>
                <th>ID</th>
                <th>Member</th>
                <th>Plan</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>

            <?php foreach ($memberships as $membership): ?>

                <tr>

                    <td><?= $membership['id'] ?></td>

                    <td><?= htmlspecialchars($membership['full_name']) ?></td>

                    <td><?= htmlspecialchars($membership['plan_name']) ?></td>

                    <td><?= htmlspecialchars($membership['start_date']) ?></td>

                    <td><?= htmlspecialchars($membership['end_date']) ?></td>

                    <td><?= htmlspecialchars($membership['status']) ?></td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

</div>