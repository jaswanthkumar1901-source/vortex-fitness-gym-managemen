<?php

require_once "../includes/auth.php";
require_once "../config/database.php";


/* Fetch trainer assignments */

$stmt = $pdo->query("
    SELECT
        member_trainer.id,
        members.full_name AS member_name,
        trainers.full_name AS trainer_name,
        member_trainer.assigned_date
    FROM member_trainer
    JOIN members
        ON member_trainer.member_id = members.id
    JOIN trainers
        ON member_trainer.trainer_id = trainers.id
    ORDER BY member_trainer.id DESC
");

$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* Assignment count */

$totalAssignments = count($assignments);


/* Header */

require_once "../includes/header.php";

?>

<link rel="stylesheet" href="/gym-management/css/member_trainer.css?v=2">


<div class="assignments-page">

    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <h1>Trainer Assignments</h1>

            <p>
                Manage members and their assigned trainers.
            </p>

        </div>


        <a
            href="/gym-management/member_trainer/add.php"
            class="add-assignment-btn"
        >
            + Assign Trainer
        </a>

    </div>


    <!-- SUCCESS MESSAGES -->

    <?php if (isset($_GET["added"]) && $_GET["added"] == "1"): ?>

        <div class="success-message">
            ✓ Trainer assignment added successfully!
        </div>

    <?php endif; ?>


    <?php if (isset($_GET["updated"]) && $_GET["updated"] == "1"): ?>

        <div class="success-message">
            ✓ Trainer assignment updated successfully!
        </div>

    <?php endif; ?>


    <?php if (isset($_GET["deleted"]) && $_GET["deleted"] == "1"): ?>

        <div class="success-message">
            ✓ Trainer assignment deleted successfully!
        </div>

    <?php endif; ?>


    <!-- SUMMARY CARD -->

    <div class="assignment-summary">

        <div class="summary-card">

            <div class="summary-icon">
                ⇄
            </div>

            <div>

                <span>Total Assignments</span>

                <strong>
                    <?= $totalAssignments ?>
                </strong>

            </div>

        </div>


        <div class="summary-card">

            <div class="summary-icon active-icon">
                ✓
            </div>

            <div>

                <span>Assigned Members</span>

                <strong>
                    <?= $totalAssignments ?>
                </strong>

            </div>

        </div>


        <div class="summary-card">

            <div class="summary-icon trainer-icon">
                ★
            </div>

            <div>

                <span>Trainer Assignments</span>

                <strong>
                    <?= $totalAssignments ?>
                </strong>

            </div>

        </div>

    </div>


    <!-- ASSIGNMENT LIST -->

    <div class="assignments-card">

        <div class="card-header">

            <div>

                <h2>Assignment List</h2>

                <p>
                    View and manage trainer assignments.
                </p>

            </div>


            <span class="assignment-count">
                <?= $totalAssignments ?> Assignments
            </span>

        </div>


        <div class="table-wrapper">

            <?php if (empty($assignments)): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        ⇄
                    </div>

                    <h3>No Trainer Assignments</h3>

                    <p>
                        Assign a trainer to a gym member to get started.
                    </p>

                    <a
                        href="/gym-management/member_trainer/add.php"
                        class="add-assignment-btn"
                    >
                        + Assign Trainer
                    </a>

                </div>

            <?php else: ?>

                <table class="assignments-table">

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Member</th>

                            <th>Trainer</th>

                            <th>Assigned Date</th>

                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($assignments as $assignment): ?>

                            <tr>

                                <!-- ID -->

                                <td>

                                    <span class="assignment-id">
                                        #<?= htmlspecialchars(
                                            $assignment["id"]
                                        ) ?>
                                    </span>

                                </td>


                                <!-- MEMBER -->

                                <td>

                                    <div class="person-info">

                                        <div class="person-avatar member-avatar">

                                            <?= strtoupper(
                                                substr(
                                                    $assignment["member_name"],
                                                    0,
                                                    1
                                                )
                                            ) ?>

                                        </div>

                                        <div>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $assignment["member_name"]
                                                ) ?>
                                            </strong>

                                            <small>
                                                Gym Member
                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <!-- TRAINER -->

                                <td>

                                    <div class="person-info">

                                        <div class="person-avatar trainer-avatar">

                                            <?= strtoupper(
                                                substr(
                                                    $assignment["trainer_name"],
                                                    0,
                                                    1
                                                )
                                            ) ?>

                                        </div>

                                        <div>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $assignment["trainer_name"]
                                                ) ?>
                                            </strong>

                                            <small>
                                                Gym Trainer
                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <!-- ASSIGNED DATE -->

                                <td>

                                    <span class="assigned-date">
                                        <?= htmlspecialchars(
                                            $assignment["assigned_date"]
                                        ) ?>
                                    </span>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="action-buttons">

                                        <a
                                            href="/gym-management/member_trainer/edit.php?id=<?= $assignment['id'] ?>"
                                            class="action-edit"
                                        >
                                            Edit
                                        </a>

                                        <a
                                            href="/gym-management/member_trainer/delete.php?id=<?= $assignment['id'] ?>"
                                            class="action-delete"
                                        >
                                            Delete
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

        </div>

    </div>

</div>