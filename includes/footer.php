</main>
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <a href="<?= e(url('index.php')) ?>" class="brand"><span class="brand-mark"><i class="fa-solid fa-compass"></i></span><span><?= e(APP_NAME) ?></span></a>
      <p class="muted">Find the clubs that fit your interests, skills and career goals.</p>
    </div>
    <div><h4>Explore</h4><a href="<?= e(url('clubs.php')) ?>">All clubs</a><a href="<?= e(url('events.php')) ?>">Upcoming events</a><a href="<?= e(url('clubs.php?sort=recent')) ?>">New clubs</a></div>
    <div><h4>Account</h4><a href="<?= e(url('auth/login.php')) ?>">Student sign in</a><a href="<?= e(url('auth/register.php')) ?>">Register</a><a href="<?= e(url('auth/admin-login.php')) ?>">Staff portal</a></div>
  </div>
  <div class="container footer-bottom">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> · College Club Finder</div>
</footer>
<?php $__u = current_user(); $__c = basename($_SERVER['SCRIPT_NAME']); ?>
<nav class="bottom-nav" aria-label="Mobile navigation">
  <a href="<?= e(url('index.php')) ?>" class="<?= $__c === 'index.php' ? 'active' : '' ?>"><i class="fa-solid fa-house"></i>Home</a>
  <a href="<?= e(url('clubs.php')) ?>" class="<?= in_array($__c, ['clubs.php', 'club-details.php']) ? 'active' : '' ?>"><i class="fa-solid fa-people-group"></i>Clubs</a>
  <a href="<?= e(url('events.php')) ?>" class="<?= $__c === 'events.php' ? 'active' : '' ?>"><i class="fa-solid fa-calendar-days"></i>Events</a>
  <?php if ($__u): ?>
  <a href="<?= e(url(home_for($__u['role']))) ?>" class="<?= $__c === 'dashboard.php' ? 'active' : '' ?>"><i class="fa-solid fa-user"></i><?= $__u['role'] === 'student' ? 'My Clubs' : 'Dashboard' ?></a>
  <?php else: ?>
  <a href="<?= e(url('auth/login.php')) ?>" class="<?= $__c === 'login.php' ? 'active' : '' ?>"><i class="fa-solid fa-right-to-bracket"></i>Sign in</a>
  <?php endif; ?>
</nav>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
