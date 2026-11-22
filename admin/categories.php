<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';
$user = require_role('admin');
$pdo = db();
$edit = null;
if ($eid = input_int('edit')) { $s = $pdo->prepare('SELECT * FROM club_categories WHERE id=?'); $s->execute([$eid]); $edit = $s->fetch() ?: null; }
if (is_post()) {
    csrf_check();
    $act = input('action');
    if ($act === 'delete') {
        $n = $pdo->prepare('SELECT COUNT(*) FROM clubs WHERE category_id=?'); $n->execute([input_int('id')]);
        if ((int)$n->fetchColumn() > 0) flash('error', 'Cannot delete a category that still has clubs. Move or delete those clubs first.');
        else { $pdo->prepare('DELETE FROM club_categories WHERE id=?')->execute([input_int('id')]); flash('success', 'Category deleted.'); }
    } else {
        $id = input_int('id'); $name = input('name'); $icon = input('icon') ?: 'fa-users'; $color = input('color') ?: '#6C3CE9'; $desc = input('description');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 80) flash('error', 'Name must be 2-80 characters.');
        elseif (!preg_match('/^fa-[a-z0-9-]{2,40}$/', $icon)) flash('error', 'Icon must be a Font Awesome class like fa-music.');
        elseif (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) flash('error', 'Colour must be a hex value.');
        else {
            $slug = slugify($name);
            try {
                if ($id) $pdo->prepare('UPDATE club_categories SET name=?,slug=?,icon=?,color=?,description=? WHERE id=?')->execute([$name, $slug, $icon, $color, $desc ?: null, $id]);
                else $pdo->prepare('INSERT INTO club_categories (name,slug,icon,color,description) VALUES (?,?,?,?,?)')->execute([$name, $slug, $icon, $color, $desc ?: null]);
                flash('success', 'Category saved.');
            } catch (PDOException $ex) { flash('error', 'A category with that name already exists.'); }
        }
    }
    redirect('admin/categories.php');
}
$cats = all_categories();
$pageTitle = 'Categories'; $active = 'categories';
include dirname(__DIR__) . '/includes/dash_header.php';
?>
<div class="two-col wide-left">
  <div class="table-wrap"><table class="table"><thead><tr><th>Category</th><th>Clubs</th><th></th></tr></thead><tbody>
  <?php foreach ($cats as $c): ?><tr><td><span class="chip chip-cat" style="--lc:<?= e($c['color']) ?>"><i class="fa-solid <?= e($c['icon']) ?>"></i> <?= e($c['name']) ?></span></td><td><?= (int)$c['club_count'] ?></td>
    <td class="right nowrap"><a class="btn btn-ghost btn-sm" href="?edit=<?= (int)$c['id'] ?>"><i class="fa-solid fa-pen"></i></a>
    <form method="post" class="inline" data-confirm="Delete this category?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button></form></td></tr><?php endforeach; ?></tbody></table></div>
  <form method="post" class="card form-stack"><h3><?= $edit ? 'Edit category' : 'New category' ?></h3><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <label class="field"><span>Name</span><input name="name" value="<?= e($edit['name'] ?? '') ?>" required maxlength="80"></label>
    <label class="field"><span>Icon <small>(Font Awesome, e.g. fa-music)</small></span><input name="icon" value="<?= e($edit['icon'] ?? 'fa-users') ?>"></label>
    <label class="field"><span>Colour</span><input type="color" name="color" value="<?= e($edit['color'] ?? '#6C3CE9') ?>"></label>
    <label class="field"><span>Description</span><input name="description" value="<?= e($edit['description'] ?? '') ?>" maxlength="255"></label>
    <div class="form-actions"><button class="btn btn-primary">Save</button><?php if ($edit): ?><a class="btn btn-ghost" href="<?= e(url('admin/categories.php')) ?>">Cancel</a><?php endif; ?></div></form>
</div>
<?php include dirname(__DIR__) . '/includes/dash_footer.php'; ?>
