<?php
/**
 * Database + application configuration.
 * Edit the DB_* values if your MySQL credentials differ from XAMPP defaults.
 */
declare(strict_types=1);

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'college_club_finder');
define('DB_USER', 'root');
define('DB_PASS', '');            // XAMPP default is an empty password

define('APP_NAME', 'ClubQuest');
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_DIR', ROOT_PATH . '/uploads');

// Auto-detect the URL path of the project (e.g. "/college-club-finder").
// If auto-detection fails on your server, hard-code it: define('BASE_URL', '/college-club-finder');
if (!defined('BASE_URL')) {
    $doc  = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $root = realpath(ROOT_PATH);
    $base = '';
    if ($doc && $root && strpos(str_replace('\\', '/', $root), str_replace('\\', '/', $doc)) === 0) {
        $base = substr(str_replace('\\', '/', $root), strlen(str_replace('\\', '/', $doc)));
    }
    define('BASE_URL', rtrim($base, '/'));
}

/** Shared PDO connection (prepared statements, exceptions, real prepares). */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('<h2>Database connection failed.</h2><p>Import <code>database.sql</code> and check <code>config/database.php</code>.</p>');
        }
    }
    return $pdo;
}
