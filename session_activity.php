
<?php
require_once __DIR__ . "/config/session.php";

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

$homeUrl = "/gym-management/index.php";

// Allow the browser idle timer to end the session.
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "logout"
) {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    http_response_code(200);
    exit("Session ended");
}

// Only logged-in users can keep a session active.
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    exit("Not logged in");
}

header("Content-Type: application/json; charset=utf-8");

echo json_encode([
    "success" => true,
    "last_activity" => $_SESSION["last_activity"]
]);
