<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../csrf.php');

define('TITLE', 'Giảng viên');
define('PAGE', 'instructors');
include('./adminInclude/header.php');
include('../dbConnection.php');

if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_instructor'])) {
    if(!csrf_verify($_POST['csrf_token'] ?? null)) {
        admin_set_flash('error', 'Phiên gửi biểu mẫu đã hết hạn.');
        echo "<script>location.href='instructors.php';</script>";
        exit;
    }

    $instructorId = filter_input(INPUT_POST, 'instructor_id', FILTER_VALIDATE_INT);
    if (!$instructorId) {
        admin_set_flash('error', 'Không xác định được giảng viên cần cập nhật.');
        echo "<script>location.href='instructors.php';</script>";
        exit;
    }

    $lockStmt = $conn->prepare('SELECT ins_status FROM instructor WHERE ins_id = ? AND is_deleted = 0 LIMIT 1 FOR UPDATE');
    if (!$lockStmt) {
        admin_set_flash('error', 'Không thể tải trạng thái giảng viên lúc này.');
        echo "<script>location.href='instructors.php';</script>";
        exit;
    }

    $conn->begin_transaction();
    try {
        $lockStmt->bind_param('i', $instructorId);
        $lockStmt->execute();
        $lockResult = $lockStmt->get_result();
        $row = $lockResult ? $lockResult->fetch_assoc() : null;
        $lockStmt->close();

        if (!$row) {
            throw new RuntimeException('Giảng viên không tồn tại hoặc đã bị xoá.');
        }

        $currentStatus = (string) ($row['ins_status'] ?? 'active');
        $nextStatus = $currentStatus === 'active' ? 'blocked' : 'active';

        $updateStmt = $conn->prepare('UPDATE instructor SET ins_status = ? WHERE ins_id = ? LIMIT 1');
        if (!$updateStmt) {
            throw new RuntimeException('Không thể cập nhật trạng thái giảng viên.');
        }
        $updateStmt->bind_param('si', $nextStatus, $instructorId);
        if (!$updateStmt->execute()) {
            $updateStmt->close();
            throw new RuntimeException('Không thể lưu trạng thái giảng viên.');
        }
        $updateStmt->close();

        $conn->commit();
        admin_set_flash('success', $nextStatus === 'blocked' ? 'Đã khóa giảng viên.' : 'Đã mở khóa giảng viên.');
    } catch (Throwable $exception) {
        $conn->rollback();
        admin_set_flash('error', $exception->getMessage() !== '' ? $exception->getMessage() : 'Không thể cập nhật trạng thái giảng viên.');
    }

    echo "<script>location.href='instructors.php';</script>";
    exit;
}

$search = trim((string) ($_GET['q'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? 'all'));
$validStatusFilter = ['all', 'active', 'blocked'];
if (!in_array($statusFilter, $validStatusFilter, true)) {
    $statusFilter = 'all';
}

$where = ' WHERE i.is_deleted = 0 ';
$types = '';
$params = [];

if ($statusFilter !== 'all') {
    $where .= ' AND i.ins_status = ? ';
    $types .= 's';
    $params[] = $statusFilter;
}

if ($search !== '') {
    $where .= ' AND (i.ins_name LIKE ? OR i.ins_email LIKE ?) ';
    $types .= 'ss';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}

$sql =
    'SELECT i.ins_id, i.ins_name, i.ins_email, i.ins_img, i.ins_status, i.created_at, '
    . '(SELECT COUNT(*) FROM course c WHERE c.instructor_id = i.ins_id AND c.is_deleted = 0) AS total_courses, '
    . "(SELECT COUNT(*) FROM course c WHERE c.instructor_id = i.ins_id AND c.is_deleted = 0 AND c.course_status = 'published') AS published_courses, "
    . "(SELECT COUNT(*) FROM course c WHERE c.instructor_id = i.ins_id AND c.is_deleted = 0 AND c.course_status = 'pending_review') AS pending_courses "
    . 'FROM instructor i '
    . $where
    . 'ORDER BY i.ins_id DESC';

$stmt = $conn->prepare($sql);
$result = false;
if ($stmt) {
    if ($types !== '') {
        $bindValues = [$types];
        foreach ($params as $key => $value) {
            $bindValues[] = &$params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $bindValues);
    }
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
  <form class="flex flex-wrap items-center gap-2" method="GET">
    <select name="status" class="px-3 py-2.5 rounded-xl border border-slate-200 bg-white text-sm outline-none focus:border-primary">
      <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
      <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Đang hoạt động</option>
      <option value="blocked" <?php echo $statusFilter === 'blocked' ? 'selected' : ''; ?>>Đã khóa</option>
    </select>
    <input type="text" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Tìm tên hoặc email giảng viên..."
           class="min-w-[260px] px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-primary">
    <button type="submit" class="px-4 py-2.5 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-primary/90 transition">
      <i class="fas fa-search"></i>
    </button>
    <?php if($search !== '' || $statusFilter !== 'all'): ?>
      <a href="instructors.php" class="px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl text-sm hover:bg-slate-200 transition">Xoá lọc</a>
    <?php endif; ?>
  </form>
</div>

<div class="rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-500">
        <tr>
          <th class="px-6 py-3 text-left font-semibold">Giảng viên</th>
          <th class="px-6 py-3 text-left font-semibold">Trạng thái</th>
          <th class="px-6 py-3 text-center font-semibold">Tổng khóa</th>
          <th class="px-6 py-3 text-center font-semibold">Đã xuất bản</th>
          <th class="px-6 py-3 text-center font-semibold">Chờ duyệt</th>
          <th class="px-6 py-3 text-left font-semibold">Thao tác</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php if($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
          <?php
            $img = ltrim(str_replace('../', '', (string) ($row['ins_img'] ?? '')), '/');
            $statusMeta = admin_instructor_status_meta((string) ($row['ins_status'] ?? 'active'));
            $isBlocked = (string) ($row['ins_status'] ?? 'active') === 'blocked';
          ?>
          <tr class="hover:bg-slate-50 transition-colors">
            <td class="px-6 py-3">
              <div class="flex items-center gap-3">
                <img src="../<?php echo htmlspecialchars($img !== '' ? $img : 'image/stu/student1.jpg', ENT_QUOTES, 'UTF-8'); ?>"
                     onerror="this.onerror=null;this.src='../image/stu/student1.jpg'"
                     class="w-9 h-9 rounded-full object-cover border border-slate-200">
                <div>
                  <p class="font-semibold text-slate-800"><?php echo htmlspecialchars((string) ($row['ins_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                  <p class="text-xs text-slate-500"><?php echo htmlspecialchars((string) ($row['ins_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
              </div>
            </td>
            <td class="px-6 py-3">
              <span class="inline-flex rounded-lg px-2 py-1 text-xs font-semibold <?php echo htmlspecialchars((string) $statusMeta['class'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars((string) $statusMeta['label'], ENT_QUOTES, 'UTF-8'); ?>
              </span>
            </td>
            <td class="px-6 py-3 text-center"><span class="px-2 py-1 rounded-lg bg-slate-100 text-slate-700 text-xs font-bold"><?php echo (int) ($row['total_courses'] ?? 0); ?></span></td>
            <td class="px-6 py-3 text-center"><span class="px-2 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold"><?php echo (int) ($row['published_courses'] ?? 0); ?></span></td>
            <td class="px-6 py-3 text-center"><span class="px-2 py-1 rounded-lg bg-amber-50 text-amber-700 text-xs font-bold"><?php echo (int) ($row['pending_courses'] ?? 0); ?></span></td>
            <td class="px-6 py-3">
              <form method="POST" class="inline-flex items-center gap-2">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="instructor_id" value="<?php echo (int) ($row['ins_id'] ?? 0); ?>">
                <button type="submit" name="toggle_instructor"
                        class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-white <?php echo $isBlocked ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700'; ?> transition-colors"
                        onclick="return confirm('<?php echo $isBlocked ? 'Mở khóa' : 'Khóa'; ?> giảng viên này?')">
                  <i class="fas <?php echo $isBlocked ? 'fa-lock-open' : 'fa-ban'; ?>"></i>
                  <?php echo $isBlocked ? 'Mở khóa' : 'Khóa'; ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="6" class="px-6 py-12 text-center text-slate-400">Không có giảng viên phù hợp bộ lọc hiện tại.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if($stmt) { $stmt->close(); } ?>
<?php include('./adminInclude/footer.php'); ?>
