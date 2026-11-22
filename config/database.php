<?php


// Database settings from Render Environment Variables
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '4000');
define('DB_NAME', getenv('DB_NAME') ?: 'college_club_finder');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

define('APP_NAME', 'ClubQuest');
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_DIR', ROOT_PATH . '/uploads');

// Automatically detect project URL path
if (!defined('BASE_URL')) {
    $doc = isset($_SERVER['DOCUMENT_ROOT'])
        ? realpath($_SERVER['DOCUMENT_ROOT']) : false;

    $root = realpath(ROOT_PATH);
    $base = '';

    if ($doc && $root &&
        strpos(str_replace('\\', '/', $root),
        str_replace('\\', '/', $doc)) === 0) {
        $base = substr(
            str_replace('\\', '/', $root),
            strlen(str_replace('\\', '/', $doc))
        );
    }

    define('BASE_URL', rtrim($base, '/'));
}

// Shared PDO database connection
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST .
               ';port=' . DB_PORT .
               ';dbname=' . DB_NAME .
               ';charset=utf8mb4';

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,

                PDO::MYSQL_ATTR_SSL_CA =>
                    getenv('DB_SSL_CA') ?: '/etc/ssl/certs/ca-certificates.crt',

                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
            ]);
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            http_response_code(500);
            exit('Database connection failed. Please check server configuration.');
        }
    }

    return $pdo;
}
