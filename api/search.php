<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';
$q = input('q');
if (mb_strlen($q) < 2) json_out(['results' => []]);
$s = db()->prepare('SELECT c.name, c.slug, c.tagline, cat.name AS cat_name, cat.color, cat.icon
    FROM clubs c JOIN club_categories cat ON cat.id=c.category_id
    WHERE c.status="active" AND (c.name LIKE ? OR c.skills LIKE ? OR cat.name LIKE ?)
    ORDER BY (c.name LIKE ?) DESC, c.name LIMIT 6');
$l = like_escape($q);
$s->execute([$l, $l, $l, addcslashes($q, '%_\\') . '%']);
$rows = $s->fetchAll();
foreach ($rows as &$r) $r['url'] = url('club-details.php?slug=' . urlencode($r['slug']));
json_out(['results' => $rows]);
