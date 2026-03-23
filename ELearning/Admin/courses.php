<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../csrf.php');

define('TITLE', 'Khoá học');
define('PAGE', 'courses');
include('./adminInclude/header.php');
include('../dbConnection.php');

if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>";
}

// Soft-delete course
if(isset($_POST['delete_course'])){
    if(!csrf_verify($_POST['csrf_token'] ?? null)) {
        echo "<script>alert('Phiên gửi biểu mẫu đã hết hạn.'); location.href='courses.php';</script>";
        exit;
    }

    $cid = (int)$_POST['cid'];
    $stmtDelete = $conn->prepare('UPDATE course SET is_deleted = 1 WHERE course_id = ?');
    if($stmtDelete) {
        $stmtDelete->bind_param('i', $cid);
        $stmtDelete->execute();
        $stmtDelete->close();
    }
    echo "<script>location.href='courses.php';</script>"; exit;
}

// Search
$search = trim($_GET['q'] ?? '');
$result = false;
$coursesStmt = null;

if($search !== '') {
    $coursesStmt = $conn->prepare(
        'SELECT c.*, (SELECT COUNT(*) FROM lesson l WHERE l.course_id = c.course_id) as lesson_count, '
        . '(SELECT COUNT(*) FROM courseorder o WHERE o.course_id = c.course_id AND o.is_deleted = 0) as order_count '
        . 'FROM course c WHERE c.is_deleted = 0 AND (c.course_name LIKE ? OR c.course_author LIKE ?) '
        . 'ORDER BY c.course_id DESC'
    );
    if($coursesStmt) {
        $searchLike = '%' . $search . '%';
        $coursesStmt->bind_param('ss', $searchLike, $searchLike);
    }
} else {
    $coursesStmt = $conn->prepare(
        'SELECT c.*, (SELECT COUNT(*) FROM lesson l WHERE l.course_id = c.course_id) as lesson_count, '
        . '(SELECT COUNT(*) FROM courseorder o WHERE o.course_id = c.course_id AND o.is_deleted = 0) as order_count '
        . 'FROM course c WHERE c.is_deleted = 0 ORDER BY c.course_id DESC'
    );
}

if($coursesStmt) {
    $coursesStmt->execute();
    $result = $coursesStmt->get_result();
}
?>

<!-- Toolbar -->
<div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-6">
  <form class="flex gap-2 flex-grow" method="GET">
    <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm kiếm khoá học..."
           class="flex-grow px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20">
    <button type="submit" class="px-4 py-2.5 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-primary/90 transition">
      <i class="fas fa-search"></i>
    </button>
    <?php if($search): ?>
    <a href="courses.php" class="px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl text-sm hover:bg-slate-200 transition">Xoá lọc</a>
    <?php endif; ?>
  </form>
  <a href="addCourse.php" class="px-5 py-2.5 bg-accent text-white rounded-xl text-sm font-bold hover:bg-emerald-600 transition flex items-center gap-2 whitespace-nowrap">
    <i class="fas fa-plus"></i> Thêm khoá học
  </a>
</div>



<!-- Table -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
        <tr>
          <th class="px-6 py-3 text-left">Ảnh</th>
          <th class="px-6 py-3 text-left">Tên khoá học</th>
          <th class="px-6 py-3 text-left">Giảng viên</th>
          <th class="px-6 py-3 text-left">Giá</th>
          <th class="px-6 py-3 text-center">Bài học</th>
          <th class="px-6 py-3 text-center">Đã bán</th>
          <th class="px-6 py-3 text-center">Thao tác</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()):
        $img = $row['course_img'];
        // normalize path
        $img = ltrim(str_replace('../', '', $img), '/');
      ?>
        <tr class="hover:bg-slate-50 transition-colors">
          <td class="px-6 py-3">
            <img src="../<?php echo $img; ?>" onerror="this.src='../image/courseimg/Banner1.jpeg'"
                 class="w-16 h-10 object-cover rounded-lg border border-slate-100">
          </td>
          <td class="px-6 py-3 font-semibold text-slate-800 max-w-xs">
            <?php echo htmlspecialchars($row['course_name']); ?>
          </td>
          <td class="px-6 py-3 text-slate-500"><?php echo htmlspecialchars($row['course_author']); ?></td>
          <td class="px-6 py-3 font-bold text-primary"><?php echo number_format($row['course_price']); ?> đ</td>
          <td class="px-6 py-3 text-center"><span class="px-2 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs font-semibold"><?php echo $row['lesson_count']; ?></span></td>
          <td class="px-6 py-3 text-center"><span class="px-2 py-1 bg-green-50 text-green-700 rounded-lg text-xs font-semibold"><?php echo $row['order_count']; ?></span></td>
          <td class="px-6 py-3 text-center">
            <div class="flex items-center justify-center gap-2">
              <a href="editcourse.php?id=<?php echo $row['course_id']; ?>"
                 class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center hover:bg-blue-100 transition" title="Sửa">
                <i class="fas fa-pen text-xs"></i>
              </a>
              <a href="addLesson.php?course_id=<?php echo $row['course_id']; ?>"
                 class="w-8 h-8 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center hover:bg-emerald-100 transition" title="Thêm bài học">
                <i class="fas fa-plus text-xs"></i>
              </a>
              <form method="POST" onsubmit="return confirm('Xoá khoá học này?')">
                <input type="hidden" name="cid" value="<?php echo $row['course_id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" name="delete_course"
                        class="w-8 h-8 bg-red-50 text-red-500 rounded-lg flex items-center justify-center hover:bg-red-100 transition" title="Xoá">
                  <i class="fas fa-trash text-xs"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="7" class="px-6 py-12 text-center text-slate-400">Chưa có khoá học nào.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if($coursesStmt) { $coursesStmt->close(); } ?>

<?php include('./adminInclude/footer.php'); ?>
