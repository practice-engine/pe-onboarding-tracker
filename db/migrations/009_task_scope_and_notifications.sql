-- Three additions: who a library task applies to, tasks belonging to one
-- practice, and the marker that drives the notifications badge.
--
-- Paste into cPanel > phpMyAdmin > SQL if you cannot run migrate.php.

-- ---------------------------------------------------------------------
-- 1. Which practices a library task applies to.
--
-- Until now every active task applied to every practice on the product,
-- derived at read time with nothing stored. That stays the default
-- ('all'). The other three scopes are resolved against scope_from, the
-- moment the task was added:
--
--   all             every practice on the product, now and later
--   open_now        practices that existed at scope_from and were not
--                   already completed. Pins the task to that set.
--   open_and_future those practices, plus any created afterwards
--   future_only     only practices created after scope_from
--
-- "Open" is judged once, when the task is added, rather than re-read
-- each time the list is drawn. A practice finishing its onboarding
-- should not make a task it already worked on vanish from its history.
-- ---------------------------------------------------------------------

ALTER TABLE tasks
  ADD COLUMN applies_to VARCHAR(20) NOT NULL DEFAULT 'all' AFTER default_assignee_id,
  ADD COLUMN scope_from DATETIME DEFAULT NULL AFTER applies_to,
  -- Set when the task belongs to one practice alone, which is how a
  -- task added from inside a practice is kept to that practice.
  ADD COLUMN practice_id INT UNSIGNED DEFAULT NULL AFTER scope_from,
  ADD KEY ix_tasks_practice (practice_id),
  ADD CONSTRAINT fk_tasks_practice FOREIGN KEY (practice_id)
      REFERENCES practices (id) ON DELETE CASCADE;

-- Every task that already exists applies everywhere, which is what it
-- did before this column existed. The DEFAULT above already gives each
-- existing row 'all', so there is nothing to backfill.
--
-- scope_from stays NULL on those rows. Only the two "future" branches
-- read it, and both test IS NOT NULL first, so an old task is never
-- treated as having a cut-off date.

-- ---------------------------------------------------------------------
-- 2. Practices a scoped task was pinned to.
--
-- Written once, when a task is added with the open_now or
-- open_and_future scope. Membership is then a fact rather than a
-- re-evaluated query, so the set cannot drift as practices complete.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS task_practices (
  task_id     INT UNSIGNED NOT NULL,
  practice_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (task_id, practice_id),
  KEY ix_tp_practice (practice_id),
  CONSTRAINT fk_tp_task     FOREIGN KEY (task_id)     REFERENCES tasks (id)     ON DELETE CASCADE,
  CONSTRAINT fk_tp_practice FOREIGN KEY (practice_id) REFERENCES practices (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. How far through the activity log each person has read.
--
-- Notifications are derived from activity_log rather than copied into a
-- table of their own, so there is one history and no chance of the two
-- disagreeing. All that needs storing is each person's place in it.
-- ---------------------------------------------------------------------

ALTER TABLE assignees
  ADD COLUMN notifications_seen_at DATETIME DEFAULT NULL AFTER last_login;

-- Start everyone from the moment they upgrade rather than showing a
-- badge with every change ever made.
UPDATE assignees SET notifications_seen_at = NOW();

-- ---------------------------------------------------------------------
-- 4. Who a log entry is about.
--
-- The log already records who DID something, as a name in `actor`.
-- Notifications need the other side of it: whose work was affected.
-- Stored as an id rather than a name because it is matched against the
-- signed-in person, and a renamed person must not lose their history.
--
-- actor stays a name. It has to describe the administrator too, who is
-- not a row in assignees, and it must still read correctly after the
-- person who did it is deleted.
-- ---------------------------------------------------------------------

ALTER TABLE activity_log
  ADD COLUMN subject_id INT UNSIGNED DEFAULT NULL AFTER actor,
  -- Leads with the filtered column and follows with created_at, so one
  -- index serves both the lookup and its ORDER BY created_at DESC.
  ADD KEY ix_log_subject (subject_id, created_at);
