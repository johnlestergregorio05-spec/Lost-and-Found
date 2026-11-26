<?php



define('DB_HOST', 'localhost');
define('DB_NAME', 'campus_lostfound');
define('DB_USER', 'root');
define('DB_PASS', '');          

define('APP_NAME', 'Campus Lost & Found');
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024); 


$docRoot = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: ''), '/');
$appRoot = str_replace('\\', '/', realpath(dirname(__DIR__)));
define('BASE_URL', $docRoot !== '' ? str_replace($docRoot, '', $appRoot) : '/lostfound');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Check includes/config.php and make sure MySQL is running.');
}
