<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/functions.php';

if (PHP_SAPI !== 'cli' && ob_get_level() === 0) ob_start();   // allow redirects after layout output

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']),
    ]);
    session_name('ccf_session');
    session_start();
}

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $s = db()->prepare('SELECT id,name,email,role,status FROM users WHERE id=?');
            $s->execute([$_SESSION['user_id']]);
            $u = $s->fetch();
            if ($u && $u['status'] === 'active') $user = $u;
            else { session_unset(); }
        }
    }
    return $user;
}
function is_logged_in(): bool { return current_user() !== null; }
function has_role(string ...$roles): bool { $u = current_user(); return $u && in_array($u['role'], $roles, true); }

function login_user(array $u): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$u['id'];
    $_SESSION['role'] = $u['role'];
}
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}
function home_for(string $role): string
{
    return ['admin' => 'admin/index.php', 'coordinator' => 'coordinator/index.php'][$role] ?? 'student/dashboard.php';
}

/** Role-based access control: call at the top of every protected page. */
function require_role(string ...$roles): array
{
    $u = current_user();
    if (!$u) {
        flash('info', 'Please sign in to continue.');
        $staff = in_array('admin', $roles, true) || in_array('coordinator', $roles, true);
        redirect($staff ? 'auth/admin-login.php' : 'auth/login.php');
    }
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        exit('<h2>403 - Access denied</h2><p>You do not have permission to view this page. <a href="' . e(url(home_for($u['role']))) . '">Go to your dashboard</a></p>');
    }
    return $u;
}

/** Returns the club row if the coordinator owns it, else null. */
function coordinator_club(int $userId): ?array
{
    $s = db()->prepare('SELECT c.*, cat.name AS cat_name, cat.color AS cat_color FROM clubs c JOIN club_categories cat ON cat.id=c.category_id WHERE c.coordinator_user_id=? LIMIT 1');
    $s->execute([$userId]);
    return $s->fetch() ?: null;
}

/** Brute-force throttle: 5 failed attempts -> 5 minute lock (per session). */
function login_throttled(): bool
{
    $t = $_SESSION['login_fail'] ?? ['n' => 0, 'at' => 0];
    if ($t['n'] >= 5 && time() - $t['at'] < 300) return true;
    if ($t['n'] >= 5) $_SESSION['login_fail'] = ['n' => 0, 'at' => 0];
    return false;
}
function login_failed(): void
{
    $t = $_SESSION['login_fail'] ?? ['n' => 0, 'at' => 0];
    $_SESSION['login_fail'] = ['n' => $t['n'] + 1, 'at' => time()];
}
