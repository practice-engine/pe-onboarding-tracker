<?php
/**
 * One person's page: what they are carrying, and where.
 *
 * Open work is listed by default because that is what somebody comes
 * here to find. Completed work is a click away rather than absent,
 * since "what did they finish" is the second question.
 *
 * @var array  $person
 * @var array  $open_tasks
 * @var array  $closed_tasks
 * @var bool   $show_closed
 * @var array  $practices      practices they hold open work on
 * @var array  $default_tasks  library tasks they pick up by default
 * @var array  $work           open_count, blocked, overdue
 * @var bool   $is_me
 * @var bool   $is_admin
 */
$page_title = (string) $person['name'];
$pid        = (int) $person['id'];
$level      = (string) ($person['access_level'] ?? 'member');
?>

<p class="crumbs">
  <a href="<?= e(url('dashboard')) ?>">Dashboard</a> <span>/</span> <?= e($person['name']) ?>
</p>

<div class="page-head">
  <div class="person-head">
    <?= avatar($person['name'], false) ?>
    <div>
      <h1><?= e($person['name']) ?><?= $is_me ? ' <span class="pill">You</span>' : '' ?></h1>
      <p class="sub">
        <?php if (!empty($person['role_title'])): ?>
          <?= e($person['role_title']) ?> ·
        <?php endif; ?>
        <?= e(Auth::LEVELS[$level] ?? 'Team member') ?>
        <?php if (empty($person['is_active'])): ?>
          · <span class="pill pill-alert">Deactivated</span>
        <?php endif; ?>
        <?php if ($is_admin && !empty($person['last_login'])): ?>
          · last signed in <?= e(fmt_ago($person['last_login'])) ?>
        <?php endif; ?>
      </p>
    </div>
  </div>
  <div class="page-actions">
    <a class="btn btn-quiet" href="<?= e(url('tasks', ['assignee_id' => $pid, 'scope' => 'all'])) ?>">
      See in All tasks
    </a>
    <?php if ($is_admin): ?>
      <a class="btn btn-quiet" href="<?= e(url('admin/assignees')) ?>">Manage people</a>
    <?php endif; ?>
  </div>
</div>

<section class="metrics" aria-label="Workload">
  <div class="metric metric-hero">
    <div class="metric-top">
      <span class="metric-ico" aria-hidden="true"><svg><use href="#i-check"/></svg></span>
      <span class="metric-label">Open tasks</span>
    </div>
    <div class="metric-row">
      <span class="metric-value"><?= (int) $work['open_count'] ?></span>
    </div>
    <span class="metric-foot">
      <?= (int) $work['open_count'] === 0 ? 'Nothing outstanding' : 'across ' . count($practices) . ' practice' . (count($practices) === 1 ? '' : 's') ?>
    </span>
  </div>

  <div class="metric">
    <div class="metric-top">
      <span class="metric-ico <?= (int) $work['blocked'] > 0 ? 'ico-red' : 'ico-slate' ?>"
            aria-hidden="true"><svg><use href="#i-alert"/></svg></span>
      <span class="metric-label">Blocked</span>
    </div>
    <div class="metric-row"><span class="metric-value"><?= (int) $work['blocked'] ?></span></div>
    <span class="metric-foot"><?= (int) $work['blocked'] === 0 ? 'nothing blocked' : 'waiting on something' ?></span>
  </div>

  <div class="metric">
    <div class="metric-top">
      <span class="metric-ico <?= (int) $work['overdue'] > 0 ? 'ico-amber' : 'ico-slate' ?>"
            aria-hidden="true"><svg><use href="#i-clock"/></svg></span>
      <span class="metric-label">Overdue</span>
    </div>
    <div class="metric-row"><span class="metric-value"><?= (int) $work['overdue'] ?></span></div>
    <span class="metric-foot"><?= (int) $work['overdue'] === 0 ? 'nothing late' : 'past the date set' ?></span>
  </div>

  <div class="metric">
    <div class="metric-top">
      <span class="metric-ico ico-slate" aria-hidden="true"><svg><use href="#i-list"/></svg></span>
      <span class="metric-label">Picks up by default</span>
    </div>
    <div class="metric-row"><span class="metric-value"><?= count($default_tasks) ?></span></div>
    <span class="metric-foot">task<?= count($default_tasks) === 1 ? '' : 's' ?> in the library</span>
  </div>
</section>

<?php if ($practices): ?>
  <section class="card">
    <h2 class="card-h">Practices they are working on</h2>
    <?php
      $chart_rows = array_map(static fn($p) => [
        'label'      => $p['name'],
        'open_count' => (int) $p['open_count'],
        'blocked'    => (int) $p['blocked'],
        'overdue'    => (int) $p['overdue'],
        'href'       => url('practice', ['id' => (int) $p['id']]),
      ], $practices);
      $chart_empty = 'No open work on any practice.';
      require APP_ROOT . '/templates/partials/bar_chart.php';
    ?>
    <p class="table-note">Open tasks only, counted per practice.</p>
  </section>
<?php endif; ?>

<h2 class="section-h">
  <?= $show_closed ? 'Completed and not applicable' : 'Open tasks' ?>
</h2>

<div class="toolbar">
  <span class="toolbar-label">
    <?= $show_closed ? count($closed_tasks) : count($open_tasks) ?>
    task<?= ($show_closed ? count($closed_tasks) : count($open_tasks)) === 1 ? '' : 's' ?>
  </span>
  <a class="btn btn-quiet btn-sm"
     href="<?= e(url('person', ['id' => $pid, 'closed' => $show_closed ? null : 1])) ?>">
    <?= $show_closed ? 'Show open tasks' : 'Show completed' ?>
  </a>
</div>

<?php $list = $show_closed ? $closed_tasks : $open_tasks; ?>

<?php if (!$list): ?>
  <div class="empty">
    <h3><?= $show_closed ? 'Nothing finished yet' : 'Nothing outstanding' ?></h3>
    <p>
      <?php if ($show_closed): ?>
        No task assigned to <?= e($person['name']) ?> has been completed or marked not applicable.
      <?php else: ?>
        <?= e($person['name']) ?> has no open tasks. Either their work is done, or nothing
        has been handed to them yet.
      <?php endif; ?>
    </p>
  </div>
<?php else: ?>

<?php foreach (Repo::groupRowsByCategory($list) as $cat): ?>
  <div class="cat-group" style="--cat: <?= e($cat['category_color'] ?: '#94A3B8') ?>">
    <h4 class="cat-h cat-h-tinted">
      <span class="cat-swatch"></span>
      <?= e($cat['category_name']) ?>
      <span class="cat-count"><?= count($cat['rows']) ?></span>
    </h4>

    <div class="table-scroll">
    <table class="grid all-grid">
      <thead>
        <tr>
          <th>Practice</th><th>Task</th><th>Product</th>
          <th>Status</th><th>Due</th><th>Notes</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cat['rows'] as $r):
            $cls = '';
            if ($r['status'] === 'completed')      { $cls = 'task-done'; }
            if ($r['status'] === 'not_applicable') { $cls = 'task-na'; }
            if ($r['status'] === 'blocked')        { $cls = 'task-blocked'; }
            if (!empty($r['is_overdue']))          { $cls .= ' task-overdue'; }
        ?>
          <tr class="<?= trim($cls) ?>">
            <td class="c-name">
              <a href="<?= e(url('practice', ['id' => (int) $r['practice_id']])) ?>"><?= e($r['practice_name']) ?></a>
            </td>
            <td class="c-task"><?= e($r['task_name']) ?></td>
            <td>
              <span class="chip">
                <?php if (!empty($r['product_color'])): ?>
                  <span class="chip-dot" style="background: <?= e($r['product_color']) ?>"></span>
                <?php endif; ?>
                <?= e($r['product_name']) ?>
              </span>
            </td>
            <td><?php $status = (string) $r['status']; require APP_ROOT . '/templates/partials/status_badge.php'; ?></td>
            <td class="c-due">
              <?= e(fmt_date($r['due_date'] ?? null)) ?>
              <?php if (!empty($r['is_overdue'])): ?><span class="chip chip-alert">Overdue</span><?php endif; ?>
            </td>
            <td class="c-notes-ro"><?= $r['notes'] ? e($r['notes']) : '<span class="muted">—</span>' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
<?php endforeach; ?>

<p class="table-note">
  Editing happens on a practice's own page, where the whole product context is visible.
</p>

<?php endif; ?>

<?php if ($default_tasks): ?>
  <section class="card">
    <h2 class="card-h">Picks these up by default</h2>
    <p class="muted">
      Library tasks that fall to <?= e($person['name']) ?> on every practice, unless that
      practice names somebody else.
    </p>
    <ul class="mini-list">
      <?php foreach ($default_tasks as $d): ?>
        <li>
          <div class="mini-row">
            <span class="mini-name"><?= e($d['name']) ?></span>
            <span class="mini-meta">
              <span class="chip">
                <?php if (!empty($d['product_color'])): ?>
                  <span class="chip-dot" style="background: <?= e($d['product_color']) ?>"></span>
                <?php endif; ?>
                <?= e($d['product_name']) ?>
              </span>
              <span class="muted"><?= e($d['category_name']) ?></span>
            </span>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>
