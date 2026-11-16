<?php
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) audit('LOGOUT', 'user', (int)$_SESSION['user']['user_id'], 'User logged out');
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
session_start();
flash('info', 'You have been logged out.');
redirect('login.php');
