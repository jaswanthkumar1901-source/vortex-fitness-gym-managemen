
<?php
require_once "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || strlen($password) < 8) {
        $message = "Enter a username and a password of at least 8 characters.";
    } else {
        $check = $pdo->prepare(
            "SELECT id FROM users WHERE username = ?"
        );
        $check->execute([$username]);

        if ($check->fetch()) {
            $message = "Username already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO users (username, password, role)
                 VALUES (?, ?, 'admin')"
            );
            $stmt->execute([$username, $hash]);

            $message = "Admin account created. Delete setup_admin.php now.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Admin</title>
</head>
<body>
    <h2>Create Admin Account</h2>

    <form method="POST">
        <label>Username</label><br>
        <input type="text" name="username" required><br><br>

        <label>Password (minimum 8 characters)</label><br>
        <input type="password" name="password"
               minlength="8" required><br><br>

        <button type="submit">Create Admin</button>
    </form>

    <p><?= htmlspecialchars($message) ?></p>
</body>
</html>