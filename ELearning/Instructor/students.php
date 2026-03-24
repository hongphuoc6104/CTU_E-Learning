<?php

define('TITLE', 'Hoc vien theo khoa hoc');
define('PAGE', 'students');

require_once(__DIR__ . '/instructorInclude/header.php');

$instructorId = instructor_current_id();
$search = trim((string) ($_GET['q'] ?? ''));

$publishedCourses = [];
$courseStmt = $conn->prepare(
    'SELECT course_id, course_name '
    . 'FROM course '
    . 'WHERE instructor_id = ? AND is_deleted = 0 AND course_status = \'published\' '
    . 'ORDER BY course_name ASC'
);
if ($courseStmt) {
    $courseStmt->bind_param('i', $instructorId);
    $courseStmt->execute();
    $result = $courseStmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $publishedCourses[] = $row;
        }
    }
    $courseStmt->close();
}

$filterCourseId = (int) ($_GET['course_id'] ?? 0);
if ($filterCourseId <= 0 && count($publishedCourses) > 0) {
    $filterCourseId = (int) ($publishedCourses[0]['course_id'] ?? 0);
}

$selectedCourse = null;
foreach ($publishedCourses as $course) {
    if ((int) ($course['course_id'] ?? 0) === $filterCourseId) {
        $selectedCourse = $course;
        break;
    }
}

if ($filterCourseId > 0 && !$selectedCourse) {
    instructor_set_flash('error', 'Ban khong duoc phep xem hoc vien cua khoa hoc nay.');
    header('Location: students.php');
    exit;
}

$rows = [];
$summary = [
    'total_students' => 0,
    'avg_progress' => 0,
    'completed_count' => 0,
];

if ($selectedCourse) {
    if ($search !== '') {
        $stmt = $conn->prepare(
            'SELECT s.stu_id, s.stu_name, s.stu_email, s.stu_occ, s.stu_img, e.enrollment_status, e.progress_percent, e.granted_at, e.completed_at '
            . 'FROM enrollment e '
            . 'INNER JOIN student s ON s.stu_id = e.student_id '
            . 'INNER JOIN course c ON c.course_id = e.course_id '
            . 'WHERE c.instructor_id = ? AND c.course_id = ? AND c.course_status = \'published\' AND c.is_deleted = 0 '
            . 'AND e.enrollment_status = \'active\' '
            . 'AND (s.stu_name LIKE ? OR s.stu_email LIKE ?) '
            . 'ORDER BY e.granted_at DESC'
        );
        if ($stmt) {
            $like = '%' . $search . '%';
            $stmt->bind_param('iiss', $instructorId, $filterCourseId, $like, $like);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
            }
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare(
            'SELECT s.stu_id, s.stu_name, s.stu_email, s.stu_occ, s.stu_img, e.enrollment_status, e.progress_percent, e.granted_at, e.completed_at '
            . 'FROM enrollment e '
            . 'INNER JOIN student s ON s.stu_id = e.student_id '
            . 'INNER JOIN course c ON c.course_id = e.course_id '
            . 'WHERE c.instructor_id = ? AND c.course_id = ? AND c.course_status = \'published\' AND c.is_deleted = 0 '
            . 'AND e.enrollment_status = \'active\' '
            . 'ORDER BY e.granted_at DESC'
        );
        if ($stmt) {
            $stmt->bind_param('ii', $instructorId, $filterCourseId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
            }
            $stmt->close();
        }
    }

    if (count($rows) > 0) {
        $totalProgress = 0;
        $completedCount = 0;
        foreach ($rows as $row) {
            $progress = (float) ($row['progress_percent'] ?? 0);
            $totalProgress += $progress;
            if ($progress >= 100 || !empty($row['completed_at'])) {
                $completedCount++;
            }
        }
        $summary['total_students'] = count($rows);
        $summary['avg_progress'] = round($totalProgress / count($rows), 2);
        $summary['completed_count'] = $completedCount;
    }
}
?>

<section class="mb-6">
  <h1 class="m-0 text-2xl font-black text-slate-900">Hoc vien theo khoa hoc</h1>
  <p class="m-0 mt-1 text-sm text-slate-500">Chi hien thi hoc vien da enrollment vao khoa hoc published do ban so huu.</p>
</section>

<?php if (count($publishedCourses) === 0): ?>
  <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
    <p class="m-0 text-sm text-slate-500">Ban chua co khoa hoc published nao de xem danh sach hoc vien.</p>
    <a href="courses.php" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white no-underline transition hover:bg-primary/90">
      <i class="fas fa-layer-group"></i>
      <span>Quan ly khoa hoc</span>
    </a>
  </section>
<?php else: ?>
  <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <form method="get" class="grid gap-3 md:grid-cols-[1fr_1fr_auto] md:items-end">
      <div>
        <label for="course_id" class="mb-2 block text-sm font-bold text-slate-700">Khoa hoc published</label>
        <select id="course_id" name="course_id" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
          <?php foreach ($publishedCourses as $course): ?>
            <option value="<?php echo (int) ($course['course_id'] ?? 0); ?>" <?php echo ((int) ($course['course_id'] ?? 0) === $filterCourseId) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars((string) ($course['course_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="q" class="mb-2 block text-sm font-bold text-slate-700">Tim hoc vien</label>
        <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ten hoac email" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
      </div>
      <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl border-0 bg-primary px-4 py-2.5 text-sm font-bold text-white transition hover:bg-primary/90">
        <i class="fas fa-filter"></i>
        <span>Ap dung</span>
      </button>
    </form>
  </section>

  <?php if ($selectedCourse): ?>
    <section class="mb-5 grid gap-4 md:grid-cols-3">
      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="m-0 text-xs font-semibold uppercase tracking-wide text-slate-400">Tong hoc vien active</p>
        <p class="m-0 mt-2 text-3xl font-black text-slate-900"><?php echo (int) $summary['total_students']; ?></p>
      </article>
      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="m-0 text-xs font-semibold uppercase tracking-wide text-slate-400">Tien do trung binh</p>
        <p class="m-0 mt-2 text-3xl font-black text-slate-900"><?php echo number_format((float) $summary['avg_progress'], 2); ?>%</p>
      </article>
      <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="m-0 text-xs font-semibold uppercase tracking-wide text-slate-400">Hoan thanh khoa hoc</p>
        <p class="m-0 mt-2 text-3xl font-black text-slate-900"><?php echo (int) $summary['completed_count']; ?></p>
      </article>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[920px] text-sm">
          <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
              <th class="px-4 py-3 text-left font-bold">Hoc vien</th>
              <th class="px-4 py-3 text-left font-bold">Email</th>
              <th class="px-4 py-3 text-left font-bold">Nghe nghiep</th>
              <th class="px-4 py-3 text-center font-bold">Tien do</th>
              <th class="px-4 py-3 text-left font-bold">Enrollment</th>
              <th class="px-4 py-3 text-left font-bold">Completed at</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php if (count($rows) > 0): ?>
              <?php foreach ($rows as $row): ?>
                <?php
                  $avatar = ltrim(str_replace('../', '', (string) ($row['stu_img'] ?? '')), '/');
                  if ($avatar === '') {
                      $avatar = 'image/stu/default_avatar.png';
                  }
                  $progress = (float) ($row['progress_percent'] ?? 0);
                ?>
                <tr>
                  <td class="px-4 py-4">
                    <div class="flex items-center gap-3">
                      <img src="../<?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar" class="h-9 w-9 rounded-full border border-slate-200 object-cover" onerror="this.onerror=null;this.src='../image/stu/default_avatar.png'">
                      <div>
                        <p class="m-0 font-bold text-slate-800"><?php echo htmlspecialchars((string) ($row['stu_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="m-0 mt-0.5 text-xs text-slate-500">ID: <?php echo (int) ($row['stu_id'] ?? 0); ?></p>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-4 text-slate-600"><?php echo htmlspecialchars((string) ($row['stu_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="px-4 py-4 text-slate-600"><?php echo htmlspecialchars((string) ($row['stu_occ'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="px-4 py-4 text-center">
                    <span class="inline-flex rounded-lg bg-primary/10 px-2 py-1 text-xs font-bold text-primary"><?php echo number_format($progress, 2); ?>%</span>
                  </td>
                  <td class="px-4 py-4 text-slate-600"><?php echo htmlspecialchars((string) ($row['granted_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="px-4 py-4 text-slate-600"><?php echo htmlspecialchars((string) (($row['completed_at'] ?? '') !== '' ? $row['completed_at'] : '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="px-4 py-14 text-center text-sm text-slate-400">Chua co hoc vien active cho khoa hoc nay.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>
<?php endif; ?>

<?php require_once(__DIR__ . '/instructorInclude/footer.php'); ?>
