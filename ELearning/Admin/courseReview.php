<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../csrf.php');

define('TITLE', 'Duyệt khóa học');
define('PAGE', 'coursereview');
include('./adminInclude/header.php');
include('../dbConnection.php');

if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_action'])) {
    if(!csrf_verify($_POST['csrf_token'] ?? null)) {
        admin_set_flash('error', 'Phiên gửi biểu mẫu đã hết hạn.');
        echo "<script>location.href='courseReview.php';</script>";
        exit;
    }

    $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    $reviewAction = (string) ($_POST['review_action'] ?? '');
    $reviewNote = trim((string) ($_POST['review_note'] ?? ''));

    if (!$courseId || !in_array($reviewAction, ['publish', 'reject'], true)) {
        admin_set_flash('error', 'Yêu cầu duyệt khóa học không hợp lệ.');
        echo "<script>location.href='courseReview.php';</script>";
        exit;
    }

    $conn->begin_transaction();

    try {
        $lockStmt = $conn->prepare(
            'SELECT c.course_id, c.course_name, c.course_status, c.course_desc '
            . 'FROM course c WHERE c.course_id = ? AND c.is_deleted = 0 LIMIT 1 FOR UPDATE'
        );
        if (!$lockStmt) {
            throw new RuntimeException('Không thể tải khóa học cần duyệt.');
        }

        $lockStmt->bind_param('i', $courseId);
        $lockStmt->execute();
        $courseResult = $lockStmt->get_result();
        $course = $courseResult ? $courseResult->fetch_assoc() : null;
        $lockStmt->close();

        if (!$course) {
            throw new RuntimeException('Không tìm thấy khóa học để xử lý.');
        }

        if ((string) ($course['course_status'] ?? '') !== 'pending_review') {
            throw new RuntimeException('Khóa học này không còn ở trạng thái chờ duyệt.');
        }

        if ($reviewAction === 'publish') {
            $validation = admin_validate_course_readiness($conn, (int) $courseId);
            if (!(bool) ($validation['ok'] ?? false)) {
                $errors = (array) ($validation['errors'] ?? []);
                $message = 'Không thể xuất bản khóa học: ' . implode(' ', $errors);
                throw new RuntimeException($message);
            }

            $publishedStatus = 'published';
            $publishedAt = date('Y-m-d H:i:s');
            $publishStmt = $conn->prepare('UPDATE course SET course_status = ?, published_at = ? WHERE course_id = ? LIMIT 1');
            if (!$publishStmt) {
                throw new RuntimeException('Không thể cập nhật trạng thái xuất bản.');
            }

            $publishStmt->bind_param('ssi', $publishedStatus, $publishedAt, $courseId);
            if (!$publishStmt->execute()) {
                $publishStmt->close();
                throw new RuntimeException('Không thể xuất bản khóa học lúc này.');
            }
            $publishStmt->close();

            admin_set_flash('success', 'Đã xuất bản khóa học thành công.');
        } else {
            $draftStatus = 'draft';
            $appendNote = '';
            if ($reviewNote !== '') {
                $appendNote = "\n\n[ADMIN_REVIEW_NOTE " . date('Y-m-d H:i') . '] ' . $reviewNote;
            }
            $updatedDesc = (string) ($course['course_desc'] ?? '') . $appendNote;

            $rejectStmt = $conn->prepare('UPDATE course SET course_status = ?, published_at = NULL, course_desc = ? WHERE course_id = ? LIMIT 1');
            if (!$rejectStmt) {
                throw new RuntimeException('Không thể cập nhật kết quả từ chối duyệt.');
            }

            $rejectStmt->bind_param('ssi', $draftStatus, $updatedDesc, $courseId);
            if (!$rejectStmt->execute()) {
                $rejectStmt->close();
                throw new RuntimeException('Không thể từ chối khóa học lúc này.');
            }
            $rejectStmt->close();

            admin_set_flash('success', 'Đã từ chối khóa học và trả về trạng thái bản nháp.');
        }

        $conn->commit();
    } catch (Throwable $exception) {
        $conn->rollback();
        admin_set_flash('error', $exception->getMessage() !== '' ? $exception->getMessage() : 'Không thể xử lý yêu cầu duyệt khóa học.');
    }

    echo "<script>location.href='courseReview.php';</script>";
    exit;
}

$pendingCourses = $conn->query(
    'SELECT c.course_id, c.course_name, c.course_author, c.course_type, c.updated_at, i.ins_name, i.ins_email, '
    . '(SELECT COUNT(*) FROM course_section cs WHERE cs.course_id = c.course_id AND cs.is_deleted = 0) AS section_count, '
    . '(SELECT COUNT(*) FROM learning_item li WHERE li.course_id = c.course_id AND li.is_deleted = 0) AS item_count, '
    . '(SELECT COUNT(*) FROM live_session ls WHERE ls.course_id = c.course_id AND ls.is_deleted = 0) AS live_count '
    . 'FROM course c '
    . 'LEFT JOIN instructor i ON i.ins_id = c.instructor_id '
    . "WHERE c.is_deleted = 0 AND c.course_status = 'pending_review' "
    . 'ORDER BY c.updated_at ASC, c.course_id ASC'
);
?>

<div class="rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden">
  <div class="border-b border-slate-100 px-6 py-4">
    <h2 class="text-lg font-bold text-slate-800">Hàng chờ duyệt khóa học</h2>
    <p class="mt-1 text-sm text-slate-400">Chỉ admin được duyệt xuất bản hoặc từ chối khóa học instructor gửi lên.</p>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-500">
        <tr>
          <th class="px-6 py-3 text-left font-semibold">Khóa học</th>
          <th class="px-6 py-3 text-left font-semibold">Instructor</th>
          <th class="px-6 py-3 text-center font-semibold">Sections</th>
          <th class="px-6 py-3 text-center font-semibold">Items</th>
          <th class="px-6 py-3 text-center font-semibold">Live</th>
          <th class="px-6 py-3 text-left font-semibold">Hành động</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php if($pendingCourses && $pendingCourses->num_rows > 0): ?>
        <?php while($course = $pendingCourses->fetch_assoc()): ?>
          <?php
            $validation = admin_validate_course_readiness($conn, (int) ($course['course_id'] ?? 0));
            $validationErrors = (array) ($validation['errors'] ?? []);
          ?>
          <tr class="align-top hover:bg-slate-50/70">
            <td class="px-6 py-4">
              <p class="font-bold text-slate-800"><?php echo htmlspecialchars((string) ($course['course_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
              <p class="mt-1 text-xs text-slate-500">Tác giả hiển thị: <?php echo htmlspecialchars((string) ($course['course_author'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
              <p class="mt-1 text-xs font-semibold uppercase text-slate-400">Loại: <?php echo htmlspecialchars((string) ($course['course_type'] ?? 'self_paced'), ENT_QUOTES, 'UTF-8'); ?></p>
              <p class="mt-1 text-xs text-slate-400">Cập nhật: <?php echo htmlspecialchars((string) ($course['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            </td>
            <td class="px-6 py-4">
              <p class="font-semibold text-slate-700"><?php echo htmlspecialchars((string) ($course['ins_name'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></p>
              <p class="mt-1 text-xs text-slate-500"><?php echo htmlspecialchars((string) ($course['ins_email'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></p>
            </td>
            <td class="px-6 py-4 text-center"><span class="inline-flex rounded-lg bg-blue-50 px-2 py-1 text-xs font-bold text-blue-700"><?php echo (int) ($course['section_count'] ?? 0); ?></span></td>
            <td class="px-6 py-4 text-center"><span class="inline-flex rounded-lg bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700"><?php echo (int) ($course['item_count'] ?? 0); ?></span></td>
            <td class="px-6 py-4 text-center"><span class="inline-flex rounded-lg bg-violet-50 px-2 py-1 text-xs font-bold text-violet-700"><?php echo (int) ($course['live_count'] ?? 0); ?></span></td>
            <td class="px-6 py-4 min-w-[320px]">
              <?php if(count($validationErrors) > 0): ?>
                <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700">
                  <?php echo htmlspecialchars(implode(' ', $validationErrors), ENT_QUOTES, 'UTF-8'); ?>
                </div>
              <?php endif; ?>
              <form method="POST" class="space-y-2">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="course_id" value="<?php echo (int) ($course['course_id'] ?? 0); ?>">
                <textarea name="review_note" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="Ghi chú khi từ chối (khuyến nghị)"></textarea>
                <div class="flex gap-2">
                  <button type="submit" name="review_action" value="publish" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition-colors">
                    <i class="fas fa-check"></i> Xuất bản
                  </button>
                  <button type="submit" name="review_action" value="reject" class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-xs font-bold text-white hover:bg-red-700 transition-colors">
                    <i class="fas fa-times"></i> Từ chối
                  </button>
                </div>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="6" class="px-6 py-12 text-center text-slate-400">Không có khóa học nào trong hàng chờ duyệt.</td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include('./adminInclude/footer.php'); ?>
