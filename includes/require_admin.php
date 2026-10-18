
<?php

require_once __DIR__ . '/auth.php';

// Only administrators can access admin pages.
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('403 Forbidden: You are not authorized to access this page.');
}
