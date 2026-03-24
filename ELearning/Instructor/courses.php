<?php

define('TITLE', 'Quản lý khóa học');
define('PAGE', 'courses');

require_once(__DIR__ . '/instructorInclude/header.php');

$instructorId = instructor_current_id();

if (isset($_POST['submit_for_review'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        instructor_set_flash('error', 'Phiên gửi biểu mẫu đã hết hạn.');
        header('Location: courses.php');
        exit;
    }

    $courseId = (int) ($_POST['course_id'] ?? 0);
    $course = instructor_find_owned_course($conn, $courseId, $instructorId);
    if (!$course) {
        instructor_set_flash('error', 'Ban khong duoc phep gui khoa hoc nay.');
        header('Location: courses.php');
        exit;
    }

    $status = (string) ($course['course_status'] ?? 'draft');
    if ($status !== 'draft') {
        instructor_set_flash('warning', 'Chỉ khóa học đang ở trạng thái draft mới được gửi duyệt.');
        header('Location: courses.php');
        exit;
    }

    $contentInfo = instructor_course_has_meaningful_content($conn, $courseId);
    $sectionCount = (int) ($contentInfo['section_count'] ?? 0);
    $meaningfulItemCount = (int) ($contentInfo['meaningful_item_count'] ?? 0);

    if ($sectionCount <= 0 || $meaningfulItemCount <= 0) {
        instructor_set_flash(
            'error',
            'Không thể gửi duyệt vì khóa học chưa có nội dung ý nghĩa. Vui lòng tạo section và learning item trước khi gửi.'
        );
        header('Location: courses.php');
        exit;
    }

    $submitStmt = $conn->prepare('UPDATE course SET course_status = \'pending_review\' WHERE course_id = ? AND instructor_id = ? AND is_deleted = 0');
    if (!$submitStmt) {
        instructor_set_flash('error', 'Không thể cập nhật trạng thái khóa học lúc này.');
        header('Location: courses.php');
        exit;
    }

    $submitStmt->bind_param('ii', $courseId, $instructorId);
    $ok = $submitStmt->execute();
    $submitStmt->close();

    if ($ok) {
        instructor_set_flash('success', 'Khóa học đã được gửi vào pipeline review.');
    } else {
        instructor_set_flash('error', 'Không thể gửi duyệt khóa học.');
    }

    header('Location: courses.php');
    exit;
}

$search = trim((string) ($_GET['q'] ?? ''));
$courses = [];

if ($search !== '') {
    $stmt = $conn->prepare(
        'SELECT c.course_id, c.course_name, c.course_status, c.course_type, c.course_price, c.course_original_price, c.updated_at, '
        . '(SELECT COUNT(*) FROM course_section cs WHERE cs.course_id = c.course_id AND cs.is_deleted = 0) AS section_count, '
        . '(SELECT COUNT(*) FROM learning_item li WHERE li.course_id = c.course_id AND li.is_deleted = 0) AS item_count, '
        . '(SELECT COUNT(*) FROM live_session ls WHERE ls.course_id = c.course_id AND ls.is_deleted = 0) AS live_count '
        . 'FROM course c '
        . 'WHERE c.instructor_id = ? AND c.is_deleted = 0 AND c.course_name LIKE ? '
        . 'ORDER BY c.updated_at DESC, c.course_id DESC'
    );
    if ($stmt) {
        $like = '%' . $search . '%';
        $stmt->bind_param('is', $instructorId, $like);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $courses[] = $row;
            }
        }
        $stmt->close();
    }
} else {
    $stmt = $conn->prepare(
        'SELECT c.course_id, c.course_name, c.course_status, c.course_type, c.course_price, c.course_original_price, c.updated_at, '
        . '(SELECT COUNT(*) FROM course_section cs WHERE cs.course_id = c.course_id AND cs.is_deleted = 0) AS section_count, '
        . '(SELECT COUNT(*) FROM learning_item li WHERE li.course_id = c.course_id AND li.is_deleted = 0) AS item_count, '
        . '(SELECT COUNT(*) FROM live_session ls WHERE ls.course_id = c.course_id AND ls.is_deleted = 0) AS live_count '
        . 'FROM course c '
        . 'WHERE c.instructor_id = ? AND c.is_deleted = 0 '
        . 'ORDER BY c.updated_at DESC, c.course_id DESC'
    );
    if ($stmt) {
        $stmt->bind_param('i', $instructorId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $courses[] = $row;
            }
        }
        $stmt->close();
    }
}
?>

<section class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
  <div>
    <h1 class="m-0 text-2xl font-black text-slate-900">Khóa học của tôi</h1>
    <p class="m-0 mt-1 text-sm text-slate-500">Quản lý khoa hoc so huu, cap nhat noi dung va gui duyet.</p>
  </div>
  <a href="addCourse.php" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white no-underline transition hover:bg-primary/90">
    <i class="fas fa-plus-circle"></i>
    <span>Tao khoa hoc draft</span>
  </a>
</section>

<section class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
  <form method="get" class="flex flex-col gap-3 sm:flex-row sm:items-center">
    <input type="text" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Tim ten khoa hoc..." class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
    <div class="flex items-center gap-2">
      <button type="submit" class="inline-flex items-center gap-2 rounded-xl border-0 bg-primary px-4 py-2.5 text-sm font-bold text-white transition hover:bg-primary/90">
        <i class="fas fa-search"></i>
        <span>Tim</span>
      </button>
      <?php if ($search !== ''): ?>
        <a href="courses.php" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 no-underline">
          <i class="fas fa-rotate-left"></i>
          <span>Xoa loc</span>
        </a>
      <?php endif; ?>
    </div>
  </form>
</section>

<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
  <div class="overflow-x-auto">
    <table class="w-full min-w-[920px] text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-500">
        <tr>
          <th class="px-4 py-3 text-left font-bold">Khóa học</th>
          <th class="px-4 py-3 text-left font-bold">Trạng thái</th>
          <th class="px-4 py-3 text-center font-bold">Sections</th>
          <th class="px-4 py-3 text-center font-bold">Items</th>
          <th class="px-4 py-3 text-center font-bold">Live</th>
          <th class="px-4 py-3 text-right font-bold">Gia</th>
          <th class="px-4 py-3 text-right font-bold">Thao tác</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (count($courses) > 0): ?>
          <?php foreach ($courses as $course): ?>
            <?php $statusMeta = instructor_course_status_meta((string) ($course['course_status'] ?? 'draft')); ?>
            <tr class="align-top">
              <td class="px-4 py-4">
                <p class="m-0 max-w-[320px] truncate font-bold text-slate-800"><?php echo htmlspecialchars((string) ($course['course_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="m-0 mt-1 text-xs text-slate-500">Cập nhật: <?php echo htmlspecialchars((string) ($course['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="m-0 mt-1 text-[11px] font-semibold uppercase text-slate-400">Loai: <?php echo htmlspecialchars((string) ($course['course_type'] ?? 'self_paced'), ENT_QUOTES, 'UTF-8'); ?></p>
              </td>
              <td class="px-4 py-4">
                <span class="inline-flex rounded-lg px-2 py-1 text-[11px] font-bold <?php echo htmlspecialchars((string) $statusMeta['class'], ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo htmlspecialchars((string) $statusMeta['label'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
              </td>
              <td class="px-4 py-4 text-center font-semibold text-slate-600"><?php echo (int) ($course['section_count'] ?? 0); ?></td>
              <td class="px-4 py-4 text-center font-semibold text-slate-600"><?php echo (int) ($course['item_count'] ?? 0); ?></td>
              <td class="px-4 py-4 text-center font-semibold text-slate-600"><?php echo (int) ($course['live_count'] ?? 0); ?></td>
              <td class="px-4 py-4 text-right">
                <p class="m-0 text-xs text-slate-400 line-through"><?php echo number_format((int) ($course['course_original_price'] ?? 0)); ?> đ</p>
                <p class="m-0 mt-1 font-black text-red-600"><?php echo number_format((int) ($course['course_price'] ?? 0)); ?> đ</p>
              </td>
              <td class="px-4 py-4 text-right">
                <div class="flex flex-wrap items-center justify-end gap-2">
                  <a href="editCourse.php?id=<?php echo (int) ($course['course_id'] ?? 0); ?>" class="inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-bold text-blue-700 no-underline transition hover:bg-blue-100">
                    <i class="fas fa-pen"></i>
                    <span>Sua</span>
                  </a>
                  <a href="sections.php?course_id=<?php echo (int) ($course['course_id'] ?? 0); ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-bold text-slate-700 no-underline transition hover:bg-slate-100">
                    <i class="fas fa-list-ol"></i>
                    <span>Section</span>
                  </a>
                  <a href="learningItems.php?course_id=<?php echo (int) ($course['course_id'] ?? 0); ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-bold text-slate-700 no-underline transition hover:bg-slate-100">
                    <i class="fas fa-book-open"></i>
                    <span>Items</span>
                  </a>
                  <a href="liveSessions.php?course_id=<?php echo (int) ($course['course_id'] ?? 0); ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-bold text-slate-700 no-underline transition hover:bg-slate-100">
                    <i class="fas fa-video"></i>
                    <span>Live</span>
                  </a>
                  <?php if ((string) ($course['course_status'] ?? '') === 'draft'): ?>
                    <form method="post" class="m-0">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="course_id" value="<?php echo (int) ($course['course_id'] ?? 0); ?>">
                      <button type="submit" name="submit_for_review" class="inline-flex items-center gap-1 rounded-lg border-0 bg-amber-500 px-2.5 py-1.5 text-xs font-extrabold text-white transition hover:bg-amber-600">
                        <i class="fas fa-paper-plane"></i>
                        <span>Gửi duyệt</span>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="px-4 py-14 text-center text-sm text-slate-400">Ban chua co khoa hoc nao. Hay tao draft dau tien.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require_once(__DIR__ . '/instructorInclude/footer.php'); ?>
