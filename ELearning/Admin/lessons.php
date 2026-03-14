<?php
if(!isset($_SESSION)) session_start();
define('TITLE', 'Bài học');
define('PAGE', 'lessons');
include('./adminInclude/header.php');
include('../dbConnection.php');

if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>";
}

// Delete lesson
if(isset($_POST['delete_lesson'])){
    $lid = (int)$_POST['lid'];
    $conn->query("UPDATE lesson SET is_deleted=1 WHERE lesson_id=$lid");
    echo "<script>location.href='lessons.php';</script>"; exit;
}

// Filter by course
$filter_course = (int)($_GET['course_id'] ?? 0);
$search = trim($_GET['q'] ?? '');

$sql = "SELECT l.*, c.course_name FROM lesson l LEFT JOIN course c ON l.course_id=c.course_id WHERE l.is_deleted=0";
if($filter_course) $sql .= " AND l.course_id=$filter_course";
if($search) $sql .= " AND (l.lesson_name LIKE '%".addslashes($search)."%')";
$sql .= " ORDER BY l.course_id, l.lesson_id";
$result = $conn->query($sql);

// All courses for dropdown (non-deleted)
$courses_all = $conn->query("SELECT course_id, course_name FROM course WHERE is_deleted=0 ORDER BY course_name");
?>

<!-- Toolbar -->
<div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-6">
  <form class="flex flex-wrap gap-2 flex-grow" method="GET">
    <!-- Course filter -->
    <select name="course_id" class="px-3 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-primary bg-white">
      <option value="">— Tất cả khoá học —</option>
      <?php $courses_all->data_seek(0); while($c = $courses_all->fetch_assoc()): ?>
      <option value="<?php echo $c['course_id']; ?>" <?php echo ($filter_course==$c['course_id'])?'selected':''; ?>>
        <?php echo htmlspecialchars($c['course_name']); ?>
      </option>
      <?php endwhile; ?>
    </select>
    <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm tên bài học..."
           class="flex-grow min-w-[160px] px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20">
    <button type="submit" class="px-4 py-2.5 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-primary/90 transition">
      <i class="fas fa-search"></i>
    </button>
    <?php if($filter_course || $search): ?>
    <a href="lessons.php" class="px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl text-sm hover:bg-slate-200 transition">Xoá lọc</a>
    <?php endif; ?>
  </form>
  <a href="addLesson.php<?php echo $filter_course ? '?course_id='.$filter_course : ''; ?>"
     class="px-5 py-2.5 bg-accent text-white rounded-xl text-sm font-bold hover:bg-emerald-600 transition flex items-center gap-2 whitespace-nowrap">
    <i class="fas fa-plus"></i> Thêm bài học
  </a>
</div>

<!-- Table -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
        <tr>
          <th class="px-6 py-3 text-left">Tên bài học</th>
          <th class="px-6 py-3 text-left">Khoá học</th>
          <th class="px-6 py-3 text-left">Link video</th>
          <th class="px-6 py-3 text-center">Thao tác</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php if($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
        <tr class="hover:bg-slate-50 transition-colors">
          <td class="px-6 py-3 font-semibold text-slate-800"><?php echo htmlspecialchars($row['lesson_name']); ?></td>
          <td class="px-6 py-3">
            <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs font-medium"><?php echo htmlspecialchars($row['course_name'] ?? '—'); ?></span>
          </td>
          <td class="px-6 py-3 text-slate-500 max-w-xs">
            <?php if($row['lesson_link']): ?>
              <a href="<?php echo htmlspecialchars($row['lesson_link']); ?>" target="_blank" class="text-primary hover:underline truncate block">
                <i class="fas fa-play text-xs mr-1"></i><?php echo htmlspecialchars(substr($row['lesson_link'], 0, 40)).'...'; ?>
              </a>
            <?php else: echo '—'; endif; ?>
          </td>
          <td class="px-6 py-3 text-center">
            <div class="flex items-center justify-center gap-2">
              <a href="editlesson.php?id=<?php echo $row['lesson_id']; ?>"
                 class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center hover:bg-blue-100 transition" title="Sửa">
                <i class="fas fa-pen text-xs"></i>
              </a>
              <form method="POST" onsubmit="return confirm('Xoá bài học này?')">
                <input type="hidden" name="lid" value="<?php echo $row['lesson_id']; ?>">
                <button type="submit" name="delete_lesson"
                        class="w-8 h-8 bg-red-50 text-red-500 rounded-lg flex items-center justify-center hover:bg-red-100 transition">
                  <i class="fas fa-trash text-xs"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="4" class="px-6 py-12 text-center text-slate-400">Chưa có bài học nào.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include('./adminInclude/footer.php'); ?>
