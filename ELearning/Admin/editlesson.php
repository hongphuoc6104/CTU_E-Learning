<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../csrf.php');

define('TITLE', 'Sửa bài học');
define('PAGE', 'lessons');
include('./adminInclude/header.php');
include('../dbConnection.php');

if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>";
}

$lid = (int)($_GET['id'] ?? $_POST['lid'] ?? 0);
if(!$lid){ echo "<script>location.href='lessons.php';</script>"; exit; }

$row = null;
$loadStmt = $conn->prepare('SELECT * FROM lesson WHERE lesson_id = ? LIMIT 1');
if($loadStmt) {
    $loadStmt->bind_param('i', $lid);
    $loadStmt->execute();
    $loadResult = $loadStmt->get_result();
    $row = $loadResult ? $loadResult->fetch_assoc() : null;
    $loadStmt->close();
}
if(!$row){ echo "<script>location.href='lessons.php';</script>"; exit; }

$msg = '';
if(isset($_POST['editLessonBtn'])){
    $name   = trim($_POST['lesson_name'] ?? '');
    $desc   = trim($_POST['lesson_desc'] ?? '');
    $link   = trim($_POST['lesson_link'] ?? '');
    $cid    = (int)($_POST['course_id'] ?? 0);

    if(!csrf_verify($_POST['csrf_token'] ?? null)) {
        $msg = ['type'=>'error', 'text'=>'Phiên gửi biểu mẫu đã hết hạn. Vui lòng thử lại.'];
    } elseif(!$name || !$link || !$cid){
        $msg = ['type'=>'error', 'text'=>'Vui lòng điền đầy đủ thông tin bắt buộc.'];
    } else {
        $course_name = '';
        $courseStmt = $conn->prepare('SELECT course_name FROM course WHERE course_id = ? AND is_deleted = 0 LIMIT 1');
        if($courseStmt) {
            $courseStmt->bind_param('i', $cid);
            $courseStmt->execute();
            $cr = $courseStmt->get_result();
            if($cr && $cr->num_rows > 0) {
                $course_name = (string) $cr->fetch_assoc()['course_name'];
            }
            $courseStmt->close();
        }

        if($course_name === '') {
            $msg = ['type'=>'error', 'text'=>'Khoá học không hợp lệ hoặc đã bị xoá.'];
        } else {
            $stmt = $conn->prepare("UPDATE lesson SET lesson_name=?, lesson_desc=?, lesson_link=?, course_id=?, course_name=? WHERE lesson_id=?");
            if($stmt) {
                $stmt->bind_param('sssisi', $name, $desc, $link, $cid, $course_name, $lid);
                if($stmt->execute()){
                    $msg = ['type'=>'success', 'text'=>'Cập nhật bài học thành công!'];
                    $refreshStmt = $conn->prepare('SELECT * FROM lesson WHERE lesson_id = ? LIMIT 1');
                    if($refreshStmt) {
                        $refreshStmt->bind_param('i', $lid);
                        $refreshStmt->execute();
                        $refreshResult = $refreshStmt->get_result();
                        $row = $refreshResult ? $refreshResult->fetch_assoc() : $row;
                        $refreshStmt->close();
                    }
                } else {
                    $msg = ['type'=>'error', 'text'=>'Lỗi khi cập nhật.'];
                }
                $stmt->close();
            } else {
                $msg = ['type'=>'error', 'text'=>'Lỗi khi cập nhật.'];
            }
        }
    }
}

$courses = $conn->query("SELECT course_id, course_name FROM course WHERE is_deleted=0 ORDER BY course_name");
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
      <input type="hidden" name="lid" value="<?php echo $lid; ?>">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
      <!-- Course -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Khoá học <span class="text-red-500">*</span></label>
        <select name="course_id" required class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary bg-white">
          <?php while($c = $courses->fetch_assoc()): ?>
          <option value="<?php echo $c['course_id']; ?>" <?php echo ($row['course_id']==$c['course_id'])?'selected':''; ?>>
            <?php echo htmlspecialchars($c['course_name']); ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Tên bài học <span class="text-red-500">*</span></label>
        <input type="text" name="lesson_name" value="<?php echo htmlspecialchars($row['lesson_name']); ?>" required
               class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20">
      </div>
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Mô tả</label>
        <textarea name="lesson_desc" rows="3"
                  class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 resize-none"><?php echo htmlspecialchars($row['lesson_desc']); ?></textarea>
      </div>
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Link video <span class="text-red-500">*</span></label>
        <input type="url" name="lesson_link" value="<?php echo htmlspecialchars($row['lesson_link']); ?>" required
               class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20">
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" name="editLessonBtn"
                class="px-6 py-3 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition text-sm">
          <i class="fas fa-save mr-2"></i>Lưu thay đổi
        </button>
        <a href="lessons.php" class="px-6 py-3 bg-slate-100 text-slate-600 font-semibold rounded-xl hover:bg-slate-200 transition text-sm">Huỷ</a>
      </div>
    </form>
  </div>
</div>

<?php include('./adminInclude/footer.php'); ?>
