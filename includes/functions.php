<?php
declare(strict_types=1);

const DEPARTMENTS = [
    'Computer Science', 'Electronics', 'Mechanical', 'Civil', 'Electrical',
    'Management', 'English', 'Physics', 'Mathematics', 'Biotechnology',
];

/* ---------- output / routing ---------- */
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}
function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------- flash messages ---------- */
function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function flash_render(): string
{
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $icon = $f['type'] === 'success' ? 'fa-circle-check' : ($f['type'] === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info');
        $out .= '<div class="alert alert-' . e($f['type']) . '"><i class="fa-solid ' . $icon . '"></i><span>' . e($f['msg']) . '</span><button type="button" class="alert-x" aria-label="Dismiss">&times;</button></div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'; }
function csrf_check(bool $json = false): void
{
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        if ($json) json_out(['ok' => false, 'message' => 'Security token expired. Refresh the page.'], 419);
        http_response_code(419);
        exit('Invalid or expired security token. Go back, refresh the page and try again.');
    }
}
function is_post(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }

/* ---------- input helpers ---------- */
function input(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}
function input_int(string $key, int $default = 0): int { return (int)filter_var(input($key, (string)$default), FILTER_VALIDATE_INT, ['options' => ['default' => $default]]); }
function like_escape(string $s): string { return '%' . addcslashes($s, '%_\\') . '%'; }
function slugify(string $s): string
{
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
    return $s !== '' ? $s : 'item';
}
/** "a, b ,c" -> "a,b,c" (trimmed, unique, no empty) */
function normalize_tags(string $s): string
{
    $tags = array_filter(array_map('trim', explode(',', $s)), fn($t) => $t !== '');
    $seen = [];
    foreach ($tags as $t) $seen[mb_strtolower($t)] = mb_substr($t, 0, 40);
    return implode(',', array_values($seen));
}
function tags_array(?string $s): array { return $s ? array_values(array_filter(array_map('trim', explode(',', $s)))) : []; }
function valid_department(string $d): bool { return $d === '' || in_array($d, DEPARTMENTS, true); }

/* ---------- formatting ---------- */
function fmt_date(string $dt): string { return date('D, d M Y', strtotime($dt)); }
function fmt_time(string $dt): string { return date('h:i A', strtotime($dt)); }
function time_ago(string $dt): string
{
    $d = time() - strtotime($dt);
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    if ($d < 2592000) return floor($d / 86400) . 'd ago';
    return date('d M Y', strtotime($dt));
}
function status_badge(string $s): string { return '<span class="badge badge-' . e($s) . '">' . e(ucfirst($s)) . '</span>'; }

/* ---------- club visuals ---------- */
function club_initials(string $name): string
{
    $w = preg_split('/\s+/', trim($name));
    return strtoupper(mb_substr($w[0], 0, 1) . (isset($w[1]) ? mb_substr($w[1], 0, 1) : mb_substr($w[0], 1, 1)));
}
function club_logo(array $club, string $size = 'md'): string
{
    $color = $club['cat_color'] ?? '#6C3CE9';
    if (!empty($club['logo']) && is_file(UPLOAD_DIR . '/' . $club['logo'])) {
        return '<span class="logo logo-' . $size . '"><img src="' . e(url('uploads/' . $club['logo'])) . '" alt="' . e($club['name']) . ' logo" loading="lazy"></span>';
    }
    return '<span class="logo logo-' . $size . '" style="--lc:' . e($color) . '">' . e(club_initials($club['name'])) . '</span>';
}

/* ---------- uploads ---------- */
/** Validates and stores an uploaded image. Returns filename, null (no file) or throws RuntimeException. */
function save_logo(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed. Please try again.');
    if ($file['size'] > 2 * 1024 * 1024) throw new RuntimeException('Logo must be smaller than 2 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$mime] ?? null;
    if (!$ext) throw new RuntimeException('Logo must be a JPG, PNG, WEBP or GIF image.');
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name)) throw new RuntimeException('Could not save the uploaded file.');
    return $name;
}
function delete_logo(?string $name): void
{
    if ($name && preg_match('/^[a-f0-9]+\.(jpg|png|webp|gif)$/', $name) && is_file(UPLOAD_DIR . '/' . $name)) @unlink(UPLOAD_DIR . '/' . $name);
}

/* ---------- notifications & membership workflow ---------- */
function notify(int $userId, string $title, string $message = '', string $link = ''): void
{
    db()->prepare('INSERT INTO notifications (user_id,title,message,link) VALUES (?,?,?,?)')
        ->execute([$userId, $title, mb_substr($message, 0, 300), $link ?: null]);
}
function unread_count(int $userId): int
{
    $s = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
    $s->execute([$userId]);
    return (int)$s->fetchColumn();
}
/** Approve / reject a pending request. Authorisation must be checked by the caller. */
function process_request(int $requestId, string $action, int $reviewerId): bool
{
    if (!in_array($action, ['approved', 'rejected'], true)) return false;
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $s = $pdo->prepare('SELECT r.*, c.name AS club_name FROM membership_requests r JOIN clubs c ON c.id=r.club_id WHERE r.id=? AND r.status="pending" FOR UPDATE');
        $s->execute([$requestId]);
        $r = $s->fetch();
        if (!$r) { $pdo->rollBack(); return false; }
        $pdo->prepare('UPDATE membership_requests SET status=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?')->execute([$action, $reviewerId, $requestId]);
        if ($action === 'approved') {
            $pdo->prepare('INSERT IGNORE INTO club_members (club_id,user_id) VALUES (?,?)')->execute([$r['club_id'], $r['user_id']]);
        }
        $pdo->commit();
    } catch (Throwable $ex) { $pdo->rollBack(); throw $ex; }
    notify((int)$r['user_id'],
        $action === 'approved' ? 'Welcome to ' . $r['club_name'] . '!' : 'Request update: ' . $r['club_name'],
        $action === 'approved' ? 'Your membership request was approved.' : 'Your membership request was not approved this time.',
        'student/dashboard.php');
    return true;
}

/* ---------- pagination ---------- */
function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage, 'per' => $perPage, 'total' => $total];
}
function pagination_html(array $p, array $query = []): string
{
    if ($p['pages'] <= 1) return '';
    $out = '<nav class="pagination" aria-label="Pagination">';
    for ($i = 1; $i <= $p['pages']; $i++) {
        $q = http_build_query(array_merge($query, ['page' => $i]));
        $out .= '<a href="?' . e($q) . '" class="' . ($i === $p['page'] ? 'active' : '') . '">' . $i . '</a>';
    }
    return $out . '</nav>';
}
