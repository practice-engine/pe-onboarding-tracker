<?php
/**
 * What has happened to this person's work while they were away.
 *
 * Read from the activity log rather than a separate feed, so there is
 * one history. Entries the person caused themselves are left out: being
 * told what you just did is noise.
 *
 * @var array $rows    newest first, each with is_new
 * @var int   $unread
 */
$page_title = 'Notifications';
?>

<div class="page-head">
  <div>
    <h1>Notifications</h1>
    <p class="sub">
      <?php if ($unread > 0): ?>
        <?= (int) $unread ?> new since you last looked
      <?php else: ?>
        Changes to your tasks, and to the practices you are working on
      <?php endif; ?>
    </p>
  </div>
  <?php if ($unread > 0): ?>
    <div class="page-actions">
      <form method="post" action="<?= e(url('notifications-seen')) ?>">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-quiet">Mark all as read</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<?php if (!$rows): ?>
  <div class="empty">
    <h2>Nothing to catch up on</h2>
    <p>
      When somebody changes one of your tasks, or anything on a practice you are
      working on, it will appear here. Your own changes are not listed.
    </p>
  </div>
<?php else: ?>

<section class="card">
  <ul class="notif-list">
    <?php foreach ($rows as $n): ?>
      <li class="notif <?= !empty($n['is_new']) ? 'is-new' : '' ?>">
        <span class="notif-dot" aria-hidden="true"></span>

        <span class="notif-body">
          <span class="notif-head">
            <?php if (!empty($n['task_name'])): ?>
              <strong><?= e($n['task_name']) ?></strong>
            <?php elseif (!empty($n['practice_name'])): ?>
              <strong><?= e($n['practice_name']) ?></strong>
            <?php else: ?>
              <strong><?= e(Activity::label($n['entity'])) ?></strong>
            <?php endif; ?>

            <?php if (!empty($n['is_new'])): ?>
              <span class="pill pill-new">New</span>
            <?php endif; ?>
          </span>

          <span class="notif-what">
            <?= e($n['summary'] ?? (Activity::label($n['action']) . ' ' . Activity::label($n['entity']))) ?>
          </span>

          <span class="notif-meta">
            <?= e($n['actor']) ?>
            <?php if (!empty($n['practice_name'])): ?>
              ·
              <a href="<?= e(url('practice', ['id' => (int) $n['practice_id']])) ?>"><?= e($n['practice_name']) ?></a>
            <?php endif; ?>
            <?php if (!empty($n['product_name'])): ?>
              <span class="chip">
                <?php if (!empty($n['product_color'])): ?>
                  <span class="chip-dot" style="background: <?= e($n['product_color']) ?>"></span>
                <?php endif; ?>
                <?= e($n['product_name']) ?>
              </span>
            <?php endif; ?>
          </span>
        </span>

        <span class="notif-when" title="<?= e($n['created_at']) ?>"><?= e(fmt_ago($n['created_at'])) ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
</section>

<p class="table-note">
  The most recent <?= count($rows) ?> change<?= count($rows) === 1 ? '' : 's' ?> relevant to you.
  <?php if ($unread > count($rows)): ?>
    Your <?= (int) $unread ?> unread go further back than this page shows; marking them
    as read clears the whole count.
  <?php endif; ?>
  <?php if ($can_activity): ?>
    The <a href="<?= e(url('admin/activity')) ?>">Activity log</a> has the full history, including your own changes.
  <?php endif; ?>
</p>

<?php endif; ?>
