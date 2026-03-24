<?php

define('TITLE', 'Instructor Dashboard');
define('PAGE', 'dashboard');

require_once(__DIR__ . '/instructorInclude/header.php');

$instructorId = instructor_current_id();
instructor_refresh_live_session_statuses($conn, $instructorId);

$stats = [
    'total_courses' => 0,
    'draft_courses' => 0,
    'pending_courses' => 0,
    'published_courses' => 0,
    'total_sections' => 0,
    'total_items' => 0,
    'scheduled_sessions' => 0,
    'live_sessions' => 0,
    'ended_sessions' => 0,
    'replay_sessions' => 0,
];

$courseStatStmt = $conn->prepare(
    'SELECT '
    . 'COUNT(*) AS total_courses, '
    . 'SUM(CASE WHEN course_status = \'draft\' THEN 1 ELSE 0 END) AS draft_courses, '
    . 'SUM(CASE WHEN course_status = \'pending_review\' THEN 1 ELSE 0 END) AS pending_courses, '
    . 'SUM(CASE WHEN course_status = \'published\' THEN 1 ELSE 0 END) AS published_courses '
    . 'FROM course WHERE instructor_id = ? AND is_deleted = 0'
);
if ($courseStatStmt) {
    $courseStatStmt->bind_param('i', $instructorId);
    $courseStatStmt->execute();
    $result = $courseStatStmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    if ($row) {
        $stats['total_courses'] = (int) ($row['total_courses'] ?? 0);
        $stats['draft_courses'] = (int) ($row['draft_courses'] ?? 0);
        $stats['pending_courses'] = (int) ($row['pending_courses'] ?? 0);
        $stats['published_courses'] = (int) ($row['published_courses'] ?? 0);
    }
    $courseStatStmt->close();
}

$sectionStmt = $conn->prepare(
    'SELECT COUNT(*) AS c '
    . 'FROM course_section cs '
    . 'INNER JOIN course c ON c.course_id = cs.course_id '
    . 'WHERE c.instructor_id = ? AND c.is_deleted = 0 AND cs.is_deleted = 0'
);
if ($sectionStmt) {
    $sectionStmt->bind_param('i', $instructorId);
    $sectionStmt->execute();
    $result = $sectionStmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stats['total_sections'] = (int) ($row['c'] ?? 0);
    $sectionStmt->close();
}

$itemStmt = $conn->prepare(
    'SELECT COUNT(*) AS c '
    . 'FROM learning_item li '
    . 'INNER JOIN course c ON c.course_id = li.course_id '
    . 'WHERE c.instructor_id = ? AND c.is_deleted = 0 AND li.is_deleted = 0'
);
if ($itemStmt) {
    $itemStmt->bind_param('i', $instructorId);
    $itemStmt->execute();
    $result = $itemStmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stats['total_items'] = (int) ($row['c'] ?? 0);
    $itemStmt->close();
}

$liveStatStmt = $conn->prepare(
    'SELECT '
    . 'SUM(CASE WHEN session_status = \'scheduled\' THEN 1 ELSE 0 END) AS scheduled_sessions, '
    . 'SUM(CASE WHEN session_status = \'live\' THEN 1 ELSE 0 END) AS live_sessions, '
    . 'SUM(CASE WHEN session_status = \'ended\' THEN 1 ELSE 0 END) AS ended_sessions, '
    . 'SUM(CASE WHEN session_status = \'replay_available\' THEN 1 ELSE 0 END) AS replay_sessions '
    . 'FROM live_session WHERE instructor_id = ? AND is_deleted = 0'
);
if ($liveStatStmt) {
    $liveStatStmt->bind_param('i', $instructorId);
    $liveStatStmt->execute();
    $result = $liveStatStmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    if ($row) {
        $stats['scheduled_sessions'] = (int) ($row['scheduled_sessions'] ?? 0);
        $stats['live_sessions'] = (int) ($row['live_sessions'] ?? 0);
        $stats['ended_sessions'] = (int) ($row['ended_sessions'] ?? 0);
        $stats['replay_sessions'] = (int) ($row['replay_sessions'] ?? 0);
    }
    $liveStatStmt->close();
}

$recentCourses = [];
$recentCourseStmt = $conn->prepare(
    'SELECT course_id, course_name, course_status, updated_at '
    . 'FROM course '
    . 'WHERE instructor_id = ? AND is_deleted = 0 '
    . 'ORDER BY updated_at DESC LIMIT 6'
);
if ($recentCourseStmt) {
    $recentCourseStmt->bind_param('i', $instructorId);
    $recentCourseStmt->execute();
    $result = $recentCourseStmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recentCourses[] = $row;
        }
    }
    $recentCourseStmt->close();
}

$liveTimeline = [];
$liveTimelineStmt = $conn->prepare(
    'SELECT ls.live_session_id, ls.session_title, ls.start_at, ls.end_at, ls.session_status, c.course_name '
    . 'FROM live_session ls '
    . 'INNER JOIN course c ON c.course_id = ls.course_id '
    . 'WHERE ls.instructor_id = ? AND ls.is_deleted = 0 AND c.is_deleted = 0 '
    . 'ORDER BY ls.start_at DESC LIMIT 8'
);
if ($liveTimelineStmt) {
    $liveTimelineStmt->bind_param('i', $instructorId);
    $liveTimelineStmt->execute();
    $result = $liveTimelineStmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $liveTimeline[] = $row;
        }
    }
    $liveTimelineStmt->close();
}
?>

<section class="mb-8 rounded-2xl bg-gradient-to-r from-primary to-slate-900 px-6 py-7 text-white shadow-xl shadow-primary/20">
  <p class="m-0 text-xs font-bold uppercase tracking-[0.2em] text-white/70">Instructor area</p>
  <h1 class="m-0 mt-2 text-3xl font-black">Xin chao, <?php echo htmlspecialchars((string) ($instructorProfile['ins_name'] ?? 'Giang vien'), ENT_QUOTES, 'UTF-8'); ?></h1>
  <p class="m-0 mt-2 text-sm text-white/80">Quan ly khoa hoc, noi dung va live session cua rieng ban.</p>
  <div class="mt-4 flex flex-wrap gap-2">
    <a href="addCourse.php" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-xs font-bold text-primary no-underline transition hover:bg-slate-100">
      <i class="fas fa-plus-circle"></i>
      <span>Tao khoa hoc draft</span>
    </a>
    <a href="liveSessions.php" class="inline-flex items-center gap-2 rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-xs font-bold text-white no-underline transition hover:bg-white/20">
      <i class="fas fa-video"></i>
      <span>Quan ly live session</span>
    </a>
  </div>
</section>

<section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
  <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <p class="m-0 text-xs font-semibold uppercase tracking-wide text-slate-400">Khoa hoc</p>
    <p class="m-0 mt-2 text-3xl font-black text-slate-900"><?php echo $stats['total_courses']; ?></p>
    <p class="m-0 mt-2 text-xs text-slate-500"><?php echo $stats['draft_courses']; ?> draft · <?php echo $stats['pending_courses']; ?> cho duyet</p>
  </article>
  <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <p class="m-0 text-xs font-semibold uppercase tracking-wide text-slate-400">Sections</p>
    <p class="m-0 mt-2 text-3xl font-black text-slate-900"><?php echo $stats['total_sections']; ?></p>
    <p class="m-0 mt-2 text-xs text-slate-500">Tong section cua khoa hoc so huu</p>
  </article>
  <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <p class="m-0 text-xs font-semibold uppercase tracking-wide text-slate-400">Learning items</p>
    <p class="m-0 mt-2 text-3xl font-black text-slate-900"><?php echo $stats['total_items']; ?></p>
    <p class="m-0 mt-2 text-xs text-slate-500">Video, article, document, quiz, live, replay</p>
  </article>
  <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <p class="m-0 text-xs font-semibold uppercase tracking-wide text-slate-400">Live sessions</p>
    <p class="m-0 mt-2 text-3xl font-black text-slate-900"><?php echo $stats['scheduled_sessions'] + $stats['live_sessions'] + $stats['ended_sessions'] + $stats['replay_sessions']; ?></p>
    <p class="m-0 mt-2 text-xs text-slate-500"><?php echo $stats['scheduled_sessions']; ?> sap dien ra · <?php echo $stats['replay_sessions']; ?> co replay</p>
  </article>
</section>

<section class="mt-6 grid gap-4 lg:grid-cols-2">
  <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center justify-between">
      <h2 class="m-0 text-base font-black text-slate-800">Khoa hoc gan day</h2>
      <a href="courses.php" class="text-xs font-bold text-primary hover:underline">Xem tat ca</a>
    </div>
    <div class="space-y-3">
      <?php if (count($recentCourses) > 0): ?>
        <?php foreach ($recentCourses as $course): ?>
          <?php $courseStatusMeta = instructor_course_status_meta((string) ($course['course_status'] ?? 'draft')); ?>
          <div class="rounded-xl border border-slate-100 p-3">
            <div class="flex items-center justify-between gap-3">
              <p class="m-0 truncate text-sm font-bold text-slate-700"><?php echo htmlspecialchars((string) ($course['course_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
              <span class="inline-flex rounded-lg px-2 py-1 text-[11px] font-bold <?php echo htmlspecialchars((string) $courseStatusMeta['class'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars((string) $courseStatusMeta['label'], ENT_QUOTES, 'UTF-8'); ?>
              </span>
            </div>
            <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
              <span>Cap nhat: <?php echo htmlspecialchars((string) ($course['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
              <a href="editCourse.php?id=<?php echo (int) ($course['course_id'] ?? 0); ?>" class="font-semibold text-primary hover:underline">Chinh sua</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="m-0 rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-400">Ban chua tao khoa hoc nao.</p>
      <?php endif; ?>
    </div>
  </article>

  <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center justify-between">
      <h2 class="m-0 text-base font-black text-slate-800">Live session timeline</h2>
      <a href="liveSessions.php" class="text-xs font-bold text-primary hover:underline">Quan ly</a>
    </div>
    <div class="space-y-3">
      <?php if (count($liveTimeline) > 0): ?>
        <?php foreach ($liveTimeline as $live): ?>
          <?php $statusMeta = instructor_live_status_meta((string) ($live['session_status'] ?? 'scheduled')); ?>
          <div class="rounded-xl border border-slate-100 p-3">
            <div class="flex items-center justify-between gap-3">
              <p class="m-0 truncate text-sm font-bold text-slate-700"><?php echo htmlspecialchars((string) ($live['session_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
              <span class="inline-flex rounded-lg px-2 py-1 text-[11px] font-bold <?php echo htmlspecialchars((string) $statusMeta['class'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars((string) $statusMeta['label'], ENT_QUOTES, 'UTF-8'); ?>
              </span>
            </div>
            <p class="m-0 mt-1 truncate text-xs text-slate-500"><?php echo htmlspecialchars((string) ($live['course_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="m-0 mt-2 text-xs text-slate-500"><?php echo htmlspecialchars((string) ($live['start_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars((string) ($live['end_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="m-0 rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-400">Chua co live session nao duoc tao.</p>
      <?php endif; ?>
    </div>
  </article>
</section>

<?php require_once(__DIR__ . '/instructorInclude/footer.php'); ?>
