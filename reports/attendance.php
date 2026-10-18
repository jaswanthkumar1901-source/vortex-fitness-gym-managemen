<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

$stmt = $pdo->query(
    "SELECT
        attendance.id,
        members.full_name,
        attendance.check_in,
        attendance.check_out
     FROM attendance
     JOIN members
        ON attendance.member_id = members.id
     ORDER BY attendance.check_in DESC"
);

$attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="attendance">

    <h1>Attendance Report</h1>

    <table border="1">

        <thead>
            <tr>
                <th>ID</th>
                <th>Member</th>
                <th>Check In</th>
                <th>Check Out</th>
            </tr>
        </thead>

        <tbody>

            <?php foreach ($attendance as $record): ?>

                <tr>

                    <td><?= $record['id'] ?></td>

                    <td><?= htmlspecialchars($record['full_name']) ?></td>

                    <td><?= htmlspecialchars($record['check_in']) ?></td>

                    <td><?= htmlspecialchars($record['check_out'] ?? '') ?></td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

</div>