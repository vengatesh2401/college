<?php
/** Reusable read queries for club discovery. */
declare(strict_types=1);

const CLUB_SELECT = 'SELECT c.*, cat.name AS cat_name, cat.slug AS cat_slug, cat.icon AS cat_icon, cat.color AS cat_color,
        (SELECT COUNT(*) FROM club_members m WHERE m.club_id=c.id) AS member_count
     FROM clubs c JOIN club_categories cat ON cat.id=c.category_id';


function all_categories(): array
{
    return db()->query('SELECT cat.*, (SELECT COUNT(*) FROM clubs c WHERE c.category_id=cat.id AND c.status="active") AS club_count FROM club_categories cat ORDER BY cat.name')->fetchAll();
}

/** Filters: q, cats[], dept, skill, sort. Returns [rows, total]. */
function find_clubs(array $f, int $limit, int $offset): array
{
    $where = ['c.status="active"'];
    $p = [];
    if (($f['q'] ?? '') !== '') {
        $where[] = '(c.name LIKE ? OR c.tagline LIKE ? OR c.description LIKE ? OR c.skills LIKE ?)';
        $l = like_escape($f['q']);
        array_push($p, $l, $l, $l, $l);
    }
    if (!empty($f['cats'])) {
        $ids = array_map('intval', $f['cats']);
        $where[] = 'c.category_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
        $p = array_merge($p, $ids);
    }
    if (($f['dept'] ?? '') !== '') { $where[] = '(c.department = ? OR c.department IS NULL OR c.department = "")'; $p[] = $f['dept']; }
    if (($f['skill'] ?? '') !== '') { $where[] = 'FIND_IN_SET(?, c.skills) > 0'; $p[] = $f['skill']; }

    $order = ['popular' => 'member_count DESC, c.name', 'recent' => 'c.created_at DESC', 'name' => 'c.name'][$f['sort'] ?? 'popular'] ?? 'member_count DESC, c.name';
    $w = implode(' AND ', $where);

    $cnt = db()->prepare("SELECT COUNT(*) FROM clubs c WHERE $w");
    $cnt->execute($p);
    $total = (int)$cnt->fetchColumn();

    $st = db()->prepare(CLUB_SELECT . " WHERE $w ORDER BY $order LIMIT $limit OFFSET $offset");
    $st->execute($p);
    return [$st->fetchAll(), $total];
}

function popular_clubs(int $n = 6): array { return find_clubs(['sort' => 'popular'], $n, 0)[0]; }
function recent_clubs(int $n = 6): array { return find_clubs(['sort' => 'recent'], $n, 0)[0]; }

function all_skills(): array
{
    $skills = [];
    foreach (db()->query('SELECT skills FROM clubs WHERE status="active" AND skills IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN) as $s)
        foreach (tags_array($s) as $t) $skills[mb_strtolower($t)] = $t;
    ksort($skills);
    return array_values($skills);
}

/**
 * Personalised suggestions: MySQL supplies clubs + the student's interests/profile,
 * PHP scores them (category match +5, shared skill +2, same department +1, popularity tie-break).
 */
function suggest_clubs(int $userId, int $limit = 12): array
{
    $pdo = db();
    $i = $pdo->prepare('SELECT category_id FROM student_interests WHERE user_id=?');
    $i->execute([$userId]);
    $interestIds = array_map('intval', $i->fetchAll(PDO::FETCH_COLUMN));

    $pr = $pdo->prepare('SELECT department, skills FROM student_profiles WHERE user_id=?');
    $pr->execute([$userId]);
    $profile = $pr->fetch() ?: ['department' => null, 'skills' => null];
    $mySkills = array_map('mb_strtolower', tags_array($profile['skills']));

    $st = $pdo->prepare(CLUB_SELECT . ' WHERE c.status="active"
        AND c.id NOT IN (SELECT club_id FROM club_members WHERE user_id=?)
        AND c.id NOT IN (SELECT club_id FROM membership_requests WHERE user_id=? AND status="pending")');
    $st->execute([$userId, $userId]);

    $out = [];
    foreach ($st->fetchAll() as $c) {
        $score = 0; $why = [];
        if (in_array((int)$c['category_id'], $interestIds, true)) { $score += 5; $why[] = 'Matches your interest in ' . $c['cat_name']; }
        $shared = array_values(array_filter(tags_array($c['skills']), fn($t) => in_array(mb_strtolower($t), $mySkills, true)));
        if ($shared) { $score += 2 * count($shared); $why[] = 'Uses your skills: ' . implode(', ', array_slice($shared, 0, 3)); }
        if (!empty($profile['department']) && $c['department'] === $profile['department']) { $score += 1; $why[] = 'Popular in ' . $profile['department']; }
        if ($score > 0) { $c['score'] = $score; $c['why'] = $why; $out[] = $c; }
    }
    usort($out, fn($a, $b) => [$b['score'], $b['member_count']] <=> [$a['score'], $a['member_count']]);
    return array_slice($out, 0, $limit);
}

function club_by_slug(string $slug): ?array
{
    $s = db()->prepare(CLUB_SELECT . ' WHERE c.slug=? AND c.status="active"');
    $s->execute([$slug]);
    return $s->fetch() ?: null;
}

/** Upcoming events. $userId adds a `registered` flag. */
function upcoming_events(?int $userId, array $f = [], int $limit = 50): array
{
    $where = ['e.event_date >= NOW()', 'c.status="active"'];
    $p = [$userId ?? 0];
    if (!empty($f['club_id'])) { $where[] = 'e.club_id=?'; $p[] = (int)$f['club_id']; }
    if (!empty($f['cat'])) { $where[] = 'c.category_id=?'; $p[] = (int)$f['cat']; }
    if (!empty($f['q'])) { $where[] = '(e.title LIKE ? OR e.description LIKE ? OR c.name LIKE ?)'; $l = like_escape($f['q']); array_push($p, $l, $l, $l); }
    $sql = 'SELECT e.*, c.name AS club_name, c.slug AS club_slug, c.logo, cat.color AS cat_color,
              (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id=e.id) AS reg_count,
              (SELECT COUNT(*) FROM event_registrations r2 WHERE r2.event_id=e.id AND r2.user_id=?) AS registered
            FROM events e JOIN clubs c ON c.id=e.club_id JOIN club_categories cat ON cat.id=c.category_id
            WHERE ' . implode(' AND ', $where) . ' ORDER BY e.event_date ASC LIMIT ' . (int)$limit;
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st->fetchAll();
}

/** Membership state of a student for a club: member | pending | rejected | none */
function membership_state(int $userId, int $clubId): string
{
    $s = db()->prepare('SELECT 1 FROM club_members WHERE user_id=? AND club_id=?');
    $s->execute([$userId, $clubId]);
    if ($s->fetch()) return 'member';
    $s = db()->prepare('SELECT status FROM membership_requests WHERE user_id=? AND club_id=?');
    $s->execute([$userId, $clubId]);
    $r = $s->fetchColumn();
    return $r === 'pending' ? 'pending' : ($r === 'rejected' ? 'rejected' : 'none');
}
