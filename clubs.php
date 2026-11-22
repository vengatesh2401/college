<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/queries.php';
require_once __DIR__ . '/includes/components.php';

$user = current_user();
$cats = all_categories();
$skills = all_skills();

$filters = [
    'q'     => mb_substr(input('q'), 0, 80),
    'cats'  => array_values(array_filter(array_map('intval', (array)($_GET['cat'] ?? [])))),
    'dept'  => in_array(input('dept'), DEPARTMENTS, true) ? input('dept') : '',
    'skill' => mb_substr(input('skill'), 0, 40),
    'sort'  => in_array(input('sort'), ['popular', 'recent', 'name'], true) ? input('sort') : 'popular',
];
$suggestedMode = input('suggested') === '1' && $user && $user['role'] === 'student';

$perPage = 9;
$page = max(1, input_int('page', 1));
if ($suggestedMode) {
    $rows = suggest_clubs((int)$user['id'], 30);
    $total = count($rows);
    $pg = paginate($total, $perPage, $page);
    $rows = array_slice($rows, $pg['offset'], $perPage);
} else {
    [, $total] = find_clubs($filters, 1, 0);
    $pg = paginate($total, $perPage, $page);
    [$rows] = find_clubs($filters, $perPage, $pg['offset']);
}
$qs = $_GET; unset($qs['page']);
$hasFilters = $filters['q'] || $filters['cats'] || $filters['dept'] || $filters['skill'];

$pageTitle = $suggestedMode ? 'Clubs for you' : 'Explore clubs';
include __DIR__ . '/includes/header.php';
?>
<section class="page-head">
  <div class="container">
    <h1><?= $suggestedMode ? 'Clubs picked for you' : 'Explore clubs' ?></h1>
    <p class="muted"><?= $suggestedMode ? 'Matched against your interests, skills and department.' : 'Filter by interest, skill or department, or search by name.' ?></p>
    <?php if ($user && $user['role'] === 'student'): ?>
      <div class="tabs"><a class="<?= !$suggestedMode ? 'active' : '' ?>" href="<?= e(url('clubs.php')) ?>">All clubs</a><a class="<?= $suggestedMode ? 'active' : '' ?>" href="<?= e(url('clubs.php?suggested=1')) ?>"><i class="fa-solid fa-wand-magic-sparkles"></i> For you</a></div>
    <?php endif; ?>
  </div>
</section>

<div class="container clubs-layout">
<?php if (!$suggestedMode): ?>
  <button class="btn btn-ghost filter-toggle" type="button"><i class="fa-solid fa-sliders"></i> Filters</button>
  <aside class="filters" id="filters">
    <form method="get" id="filter-form">
      <div class="filter-group"><label class="filter-title" for="fq">Search</label>
        <input id="fq" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Club name or keyword"></div>
      <div class="filter-group"><span class="filter-title">Interests / categories</span>
        <?php foreach ($cats as $c): ?>
          <label class="check"><input type="checkbox" name="cat[]" value="<?= (int)$c['id'] ?>" <?= in_array((int)$c['id'], $filters['cats'], true) ? 'checked' : '' ?>><span><?= e($c['name']) ?></span><small><?= (int)$c['club_count'] ?></small></label>
        <?php endforeach; ?></div>
      <div class="filter-group"><label class="filter-title" for="fd">Department</label>
        <select id="fd" name="dept"><option value="">All departments</option><?php foreach (DEPARTMENTS as $d): ?><option <?= $d === $filters['dept'] ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?></select></div>
      <div class="filter-group"><label class="filter-title" for="fs">Skill</label>
        <select id="fs" name="skill"><option value="">Any skill</option><?php foreach ($skills as $s): ?><option <?= strcasecmp($s, $filters['skill']) === 0 ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
      <div class="filter-group"><label class="filter-title" for="fo">Sort by</label>
        <select id="fo" name="sort"><option value="popular" <?= $filters['sort'] === 'popular' ? 'selected' : '' ?>>Most popular</option><option value="recent" <?= $filters['sort'] === 'recent' ? 'selected' : '' ?>>Recently added</option><option value="name" <?= $filters['sort'] === 'name' ? 'selected' : '' ?>>Name A–Z</option></select></div>
      <div class="filter-actions"><button class="btn btn-primary btn-block" type="submit">Apply filters</button>
        <?php if ($hasFilters || $filters['sort'] !== 'popular'): ?><a class="btn btn-ghost btn-block" href="<?= e(url('clubs.php')) ?>">Clear all</a><?php endif; ?></div>
    </form>
  </aside>
<?php endif; ?>
  <section class="results <?= $suggestedMode ? 'full' : '' ?>">
    <p class="result-count muted"><?= (int)$total ?> club<?= $total === 1 ? '' : 's' ?> found</p>
    <?php if ($rows): ?>
      <div class="card-grid cols-3"><?php foreach ($rows as $c) echo club_card($c, $c['why'] ?? []); ?></div>
      <?= pagination_html($pg, $qs) ?>
    <?php elseif ($suggestedMode): ?>
      <?= empty_state('wand-magic-sparkles', 'No suggestions yet', 'Select your interests and skills in your profile and we will match clubs for you.', '<a class="btn btn-primary" href="' . e(url('student/profile.php')) . '">Set my interests</a>') ?>
    <?php else: ?>
      <?= empty_state('magnifying-glass', 'No clubs match your filters', 'Try removing a filter or searching for something broader.', '<a class="btn btn-primary" href="' . e(url('clubs.php')) . '">Reset filters</a>') ?>
    <?php endif; ?>
  </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
