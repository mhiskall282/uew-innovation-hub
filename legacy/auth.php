<?php
/* Shared login helpers. Include at the top of every protected page. */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function db() {
    static $c = null;
    if (!$c) {
        $c = new mysqli('localhost', 'root', 'mysql', 'project_mysql');
        $c->set_charset('utf8mb4');
    }
    return $c;
}

function is_logged_in() { return !empty($_SESSION['user_id']); }

function is_admin() { return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin'; }

function auth_fail($json, $code, $message, $redirect = null) {
    http_response_code($code);
    if ($json) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
    } elseif ($redirect) {
        header('Location: ' . $redirect);
    } else {
        echo htmlspecialchars($message);
    }
    exit;
}

/* $json = true for AJAX endpoints (returns JSON instead of redirecting) */
function require_login($json = false) {
    if (!is_logged_in()) {
        auth_fail($json, 401, 'Your session has expired. Please log in again.', 'login.php');
    }
}

function require_admin($json = false) {
    require_login($json);
    if (!is_admin()) {
        auth_fail($json, 403, 'You do not have permission to do this.');
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_check() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        auth_fail(false, 400, 'Invalid form token. Go back and try again.');
    }
}
