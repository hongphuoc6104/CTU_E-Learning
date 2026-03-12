<?php
if(!isset($_SESSION)) session_start();
define('TITLE', 'Thêm bài học');
define('PAGE', 'lessons');
include('./adminInclude/header.php');
include('../dbConnection.php');

if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>";
}

$prefill_course = (int)($_GET['course_id'] ?? 0);
$msg = '';

if(isset($_POST['addLessonBtn'])){
    $lesson_name = trim($_POST['lesson_name'] ?? '');
    $lesson_desc = trim($_POST['lesson_desc'] ?? '');
    $lesson_link = trim($_POST['lesson_link'] ?? '');
    $course_id   = (int)($_POST['course_id'] ?? 0);

    if(!$lesson_name || !$lesson_link || !$course_id){
        $msg = ['type'=>'error', 'text'=>'Vui lòng điền đầy đủ tên bài học, link video và chọn khoá học.'];
    } else {
        // Get course_name for denormalized field
        $cr = $conn->query("SELECT course_name FROM course WHERE course_id=$course_id");
        $course_name = $cr->num_rows ? $cr->fetch_assoc()['course_name'] : '';

        $stmt = $conn->prepare("INSERT INTO lesson (lesson_name, lesson_desc, lesson_link, course_id, course_name) VALUES (?,?,?,?,?)");
        $stmt->bind_param('sssis', $lesson_name, $lesson_desc, $lesson_link, $course_id, $course_name);
        if($stmt->execute()){
            $msg = ['type'=>'success', 'text'=>'Thêm bài học thành công!'];
        } else {
            $msg = ['type'=>'error', 'text'=>'Lỗi khi thêm bài học.'];
        }
        $stmt->close();
    }
}

$courses = $conn->query("SELECT course_id, course_name FROM course ORDER BY course_name");
?>

<div class="max-w-xl">
  <?php if($msg): ?>
  <div class="mb-4 p-4 rounded-xl text-sm font-medium
    <?php echo $msg['type']==='success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?>">
    <i class="fas <?php echo $msg['type']==='success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
    <?php echo $msg['text']; ?>
  </div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8">
    <form method="POST" class="space-y-5">
      <!-- Course -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Khoá học <span class="text-red-500">*</span></label>
        <select name="course_id" required class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 bg-white">
          <option value="">— Chọn khoá học —</option>
          <?php while($c = $courses->fetch_assoc()): ?>
          <option value="<?php echo $c['course_id']; ?>" <?php echo ($prefill_course==$c['course_id'])?'selected':''; ?>>
            <?php echo htmlspecialchars($c['course_name']); ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>
      <!-- Name -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Tên bài học <span class="text-red-500">*</span></label>
        <input type="text" name="lesson_name" required
               class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20"
               placeholder="VD: Bài 1: Giới thiệu khoá học">
      </div>
      <!-- Desc -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Mô tả bài học</label>
        <textarea name="lesson_desc" rows="3"
                  class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 resize-none"
                  placeholder="Nội dung bài học này..."></textarea>
      </div>
      <!-- Link -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Link video <span class="text-red-500">*</span></label>
        <input type="url" name="lesson_link" required
               class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20"
               placeholder="https://... (YouTube, MP4, v.v.)">
        <p class="text-xs text-slate-400 mt-1">Hỗ trợ link YouTube, video MP4 trực tiếp.</p>
      </div>
      <!-- Buttons -->
      <div class="flex gap-3 pt-2">
        <button type="submit" name="addLessonBtn"
                class="px-6 py-3 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition text-sm">
          <i class="fas fa-plus mr-2"></i>Thêm bài học
        </button>
        <a href="lessons.php" class="px-6 py-3 bg-slate-100 text-slate-600 font-semibold rounded-xl hover:bg-slate-200 transition text-sm">Huỷ</a>
      </div>
    </form>
  </div>
</div>

<?php include('./adminInclude/footer.php'); ?>
