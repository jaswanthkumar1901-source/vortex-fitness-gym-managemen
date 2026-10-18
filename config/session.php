
<?php
/*
=========================================================
 GYMFIT SESSION CONFIGURATION
 Session timeout: 120 minutes of inactivity
=========================================================
*/

if (session_status() === PHP_SESSION_NONE) {

    // Keep PHP session data for at least 2 hours.
    ini_set("session.gc_maxlifetime", "7200");

    // Session cookie security settings.
    ini_set("session.use_strict_mode", "1");
    ini_set("session.use_only_cookies", "1");

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "secure" => !empty($_SERVER["HTTPS"])
            && $_SERVER["HTTPS"] !== "off",
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}

$sessionTimeout = 120*60;
$sessionHomeUrl = "/gym-management/index.php";

// Enforce inactivity timeout for logged-in users.
if (isset($_SESSION["user_id"])) {

    $lastActivity = (int) (
        $_SESSION["last_activity"] ?? time()
    );

    if ((time() - $lastActivity) >= $sessionTimeout) {

        // Clear session variables.
        $_SESSION = [];

        // Remove the session cookie.
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

        // Send AJAX requests an expiry response.
        if (
            !empty($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
            strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])
                === "xmlhttprequest"
        ) {
            http_response_code(401);
            exit("Session expired");
        }

        header("Location: " . $sessionHomeUrl);
        exit;
    }

    // Record the time of this valid request.
    $_SESSION["last_activity"] = time();
}
