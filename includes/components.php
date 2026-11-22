<?php
/** Small HTML component renderers shared by several pages. */
declare(strict_types=1);

function club_card(array $c, array $why = []): string
{
    ob_start(); ?>
    <article class="club-card reveal" style="--lc:<?= e($c['cat_color']) ?>">
      <div class="club-card-top">
        <?= club_logo($c, 'md') ?>
        <span class="chip chip-cat"><i class="fa-solid <?= e($c['cat_icon']) ?>"></i> <?= e($c['cat_name']) ?></span>
      </div>
      <h3><a href="<?= e(url('club-details.php?slug=' . urlencode($c['slug']))) ?>"><?= e($c['name']) ?></a></h3>
      <p class="tagline"><?= e($c['tagline'] ?: mb_strimwidth($c['description'], 0, 90, '…')) ?></p>
      <?php if ($why): ?><ul class="why"><?php foreach (array_slice($why, 0, 2) as $w): ?><li><i class="fa-solid fa-wand-magic-sparkles"></i> <?= e($w) ?></li><?php endforeach; ?></ul>
      <?php elseif (!empty($c['skills'])): ?><div class="tags"><?php foreach (array_slice(tags_array($c['skills']), 0, 3) as $t): ?><span class="tag"><?= e($t) ?></span><?php endforeach; ?></div><?php endif; ?>
      <div class="club-card-foot">
        <span><i class="fa-solid fa-user-group"></i> <?= (int)$c['member_count'] ?> members</span>
        <a class="link-arrow" href="<?= e(url('club-details.php?slug=' . urlencode($c['slug']))) ?>">View <i class="fa-solid fa-arrow-right"></i></a>
      </div>
    </article>
    <?php return ob_get_clean();
}

function event_card(array $ev, ?array $user): string
{
    $full = $ev['capacity'] !== null && (int)$ev['reg_count'] >= (int)$ev['capacity'] && !$ev['registered'];
    ob_start(); ?>
    <article class="event-card reveal" data-event="<?= (int)$ev['id'] ?>">
      <div class="date-tile"><b><?= date('d', strtotime($ev['event_date'])) ?></b><span><?= date('M', strtotime($ev['event_date'])) ?></span></div>
      <div class="event-body">
        <a class="event-club" href="<?= e(url('club-details.php?slug=' . urlencode($ev['club_slug']))) ?>"><?= e($ev['club_name']) ?></a>
        <h3><?= e($ev['title']) ?></h3>
        <p class="muted"><?= e(mb_strimwidth((string)$ev['description'], 0, 130, '…')) ?></p>
        <div class="meta">
          <span><i class="fa-regular fa-clock"></i> <?= e(fmt_date($ev['event_date'])) ?> · <?= e(fmt_time($ev['event_date'])) ?></span>
          <?php if ($ev['venue']): ?><span><i class="fa-solid fa-location-dot"></i> <?= e($ev['venue']) ?></span><?php endif; ?>
          <span><i class="fa-solid fa-users"></i> <b class="reg-count"><?= (int)$ev['reg_count'] ?></b><?= $ev['capacity'] ? ' / ' . (int)$ev['capacity'] : '' ?> going</span>
        </div>
      </div>
      <div class="event-action">
        <?php if (!$user): ?>
          <a class="btn btn-primary btn-sm" href="<?= e(url('auth/login.php')) ?>">Sign in to register</a>
        <?php elseif ($user['role'] !== 'student'): ?>
          <span class="muted small">Students only</span>
        <?php else: ?>
          <button class="btn btn-sm event-reg <?= $ev['registered'] ? 'btn-success is-on' : 'btn-primary' ?>" data-id="<?= (int)$ev['id'] ?>" <?= $full ? 'disabled' : '' ?>>
            <?= $ev['registered'] ? '<i class="fa-solid fa-check"></i> Registered' : ($full ? 'Event full' : 'Register') ?>
          </button>
        <?php endif; ?>
      </div>
    </article>
    <?php return ob_get_clean();
}

function empty_state(string $icon, string $title, string $text = '', string $cta = ''): string
{
    return '<div class="empty"><i class="fa-solid ' . e($icon) . '"></i><h3>' . e($title) . '</h3><p class="muted">' . e($text) . '</p>' . $cta . '</div>';
}
