<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../csrf.php');
require_once(__DIR__ . '/../upload_helpers.php');

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
    if(!csrf_verify($_POST['csrf_token'] ?? null)) {
        $msg = ['type'=>'error', 'text'=>'Phiên gửi biểu mẫu đã hết hạn. Vui lòng thử lại.'];
    } else {
        $lesson_name = trim($_POST['lesson_name'] ?? '');
        $lesson_desc = trim($_POST['lesson_desc'] ?? '');
        $course_id   = (int)($_POST['course_id'] ?? 0);
        $mode        = $_POST['video_mode'] ?? 'link'; // 'link' or 'upload'

        $lesson_link = '';

        $uploadedVideoDiskPath = null;
        if($mode === 'upload' && isset($_FILES['lesson_video']) && $_FILES['lesson_video']['error'] === UPLOAD_ERR_OK) {
            // Handle file upload
            $allowed_vid = ['mp4', 'webm', 'ogg', 'mov'];
            $vid_ext = strtolower(pathinfo($_FILES['lesson_video']['name'], PATHINFO_EXTENSION));
            if (!in_array($vid_ext, $allowed_vid, true)) {
                $msg = ['type'=>'error', 'text'=>'Định dạng video không hỗ trợ. Chỉ chấp nhận mp4, webm, ogg, mov.'];
            } elseif ($_FILES['lesson_video']['size'] > 500 * 1024 * 1024) { // 500MB
                $msg = ['type'=>'error', 'text'=>'Dung lượng video quá lớn (tối đa 500MB).'];
            } else {
                $uploadResult = app_upload_store_file(
                    (string) $_FILES['lesson_video']['tmp_name'],
                    (string) $_FILES['lesson_video']['name'],
                    __DIR__ . '/../lessonvid/',
                    '../lessonvid',
                    'thu muc video bai hoc',
                    'lesson'
                );
                if(($uploadResult['ok'] ?? false)) {
                    $lesson_link = (string) ($uploadResult['db_path'] ?? '');
                    $uploadedVideoDiskPath = (string) ($uploadResult['disk_path'] ?? '');
                } else {
                    $msg = ['type'=>'error', 'text'=>(string) ($uploadResult['message'] ?? 'Không thể lưu file video. Kiểm tra quyền ghi thư mục lessonvid/.')];
                }
            }
        } elseif ($mode === 'link') {
            $lesson_link = trim($_POST['lesson_link'] ?? '');
        }

        if(!$msg) {
            if(!$lesson_name || !$lesson_link || !$course_id){
                $msg = ['type'=>'error', 'text'=>'Vui lòng điền đầy đủ: tên bài học, video và khoá học.'];
            } else {
                $course_name = '';
                $courseStmt = $conn->prepare('SELECT course_name FROM course WHERE course_id = ? AND is_deleted = 0 LIMIT 1');
                if($courseStmt) {
                    $courseStmt->bind_param('i', $course_id);
                    $courseStmt->execute();
                    $cr = $courseStmt->get_result();
                    if($cr && $cr->num_rows > 0) {
                        $course_name = (string) $cr->fetch_assoc()['course_name'];
                    }
                    $courseStmt->close();
                }

                if($course_name === '') {
                    if($uploadedVideoDiskPath !== null && is_file($uploadedVideoDiskPath)) {
                        @unlink($uploadedVideoDiskPath);
                    }
                    $msg = ['type'=>'error', 'text'=>'Khoá học không hợp lệ hoặc đã bị xoá.'];
                } else {
                    $stmt = $conn->prepare('INSERT INTO lesson (lesson_name, lesson_desc, lesson_link, course_id, course_name, is_deleted) VALUES (?, ?, ?, ?, ?, 0)');
                    if($stmt) {
                        $stmt->bind_param('sssis', $lesson_name, $lesson_desc, $lesson_link, $course_id, $course_name);
                        if($stmt->execute()){
                            $msg = ['type'=>'success', 'text'=>'Thêm bài học thành công!'];
                        } else {
                            if($uploadedVideoDiskPath !== null && is_file($uploadedVideoDiskPath)) {
                                @unlink($uploadedVideoDiskPath);
                            }
                            $msg = ['type'=>'error', 'text'=>'Lỗi khi thêm bài học.'];
                        }
                        $stmt->close();
                    } else {
                        if($uploadedVideoDiskPath !== null && is_file($uploadedVideoDiskPath)) {
                            @unlink($uploadedVideoDiskPath);
                        }
                        $msg = ['type'=>'error', 'text'=>'Lỗi khi thêm bài học.'];
                    }
                }
            }
        }
    }
}

$courses = $conn->query("SELECT course_id, course_name FROM course WHERE is_deleted=0 ORDER BY course_name");
?>

<div class="max-w-2xl">

  <?php if($msg): ?>
  <div class="mb-5 flex items-center gap-3 px-4 py-3 rounded-xl border text-sm font-medium
    <?php echo $msg['type']==='success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-600'; ?>">
    <i class="fas <?php echo $msg['type']==='success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
    <?php echo htmlspecialchars($msg['text']); ?>
  </div>
  <?php endif; ?>

  <!-- Header -->
  <div class="mb-6 flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-black text-slate-800">Thêm bài học mới</h2>
      <p class="text-sm text-slate-500 mt-1">Hỗ trợ upload video hoặc dán link từ bất kỳ trang nào.</p>
    </div>
    <a href="lessons.php" class="flex items-center gap-2 px-4 py-2.5 bg-slate-100 text-slate-600 font-semibold text-sm rounded-xl hover:bg-slate-200 transition-all no-underline">
      <i class="fas fa-arrow-left text-xs"></i> Quay lại
    </a>
  </div>

  <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8">
    <form method="POST" enctype="multipart/form-data" class="space-y-6">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="video_mode" id="videoModeInput" value="link">

      <!-- Khoá học -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Khoá học <span class="text-red-500">*</span></label>
        <select name="course_id" required class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 bg-white">
          <option value="">— Chọn khoá học —</option>
          <?php while($c = $courses->fetch_assoc()): ?>
          <option value="<?php echo $c['course_id']; ?>" <?php echo ($prefill_course==$c['course_id'])?'selected':''; ?>>
            <?php echo htmlspecialchars($c['course_name']); ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>

      <!-- Tên bài học -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Tên bài học <span class="text-red-500">*</span></label>
        <input type="text" name="lesson_name" required
               class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
               placeholder="VD: Bài 1: Giới thiệu khoá học">
      </div>

      <!-- Mô tả -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Mô tả bài học</label>
        <textarea name="lesson_desc" rows="3"
                  class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 resize-none"
                  placeholder="Tóm tắt nội dung bài học..."></textarea>
      </div>

      <!-- === Video Source Tabs === -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-3">Nguồn video <span class="text-red-500">*</span></label>

        <!-- Tab switcher -->
        <div class="flex rounded-xl border border-slate-200 overflow-hidden mb-4">
          <button type="button" id="tabLink"
                  onclick="switchVideoMode('link')"
                  class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-semibold bg-primary text-white transition-all">
            <i class="fas fa-link text-xs"></i> Dán link (URL)
          </button>
          <button type="button" id="tabUpload"
                  onclick="switchVideoMode('upload')"
                  class="flex-1 flex items-center justify-center gap-2 py-2.5 text-sm font-semibold bg-white text-slate-500 transition-all">
            <i class="fas fa-upload text-xs"></i> Upload file video
          </button>
        </div>

        <!-- Panel: Link URL -->
        <div id="panelLink">
          <input type="url" name="lesson_link" id="lessonLinkInput"
                 class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
                 placeholder="https://youtube.com/watch?v=... hoặc link mp4 trực tiếp">
          <p class="text-xs text-slate-400 mt-2 flex items-center gap-1.5">
            <i class="fab fa-youtube text-red-400"></i> YouTube
            <span class="text-slate-200">·</span>
            <i class="fas fa-video text-blue-400"></i> Vimeo
            <span class="text-slate-200">·</span>
            <i class="fab fa-google-drive text-green-400"></i> Google Drive (link embed)
            <span class="text-slate-200">·</span>
            <i class="fas fa-link text-slate-400"></i> Và nhiều nguồn khác
          </p>
        </div>

        <!-- Panel: Upload File -->
        <div id="panelUpload" class="hidden">
          <label for="lessonVideoFile"
                 class="flex items-center gap-4 px-5 py-4 border-2 border-dashed border-slate-300 rounded-xl hover:border-primary hover:bg-primary/5 transition-all cursor-pointer">
            <i class="fas fa-cloud-upload-alt text-primary text-2xl shrink-0"></i>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-slate-700">Nhấn để chọn file video</p>
              <p class="text-xs text-slate-400 mt-0.5" id="uploadFileLabel">Chưa chọn file — MP4, WebM, OGG, MOV (tối đa 500MB)</p>
            </div>
            <input type="file" name="lesson_video" id="lessonVideoFile" accept=".mp4,.webm,.ogg,.mov" class="hidden"
                   onchange="document.getElementById('uploadFileLabel').textContent = this.files[0]?.name || 'Chưa chọn file'">
          </label>
          <p class="text-xs text-orange-500 mt-2 flex items-center gap-1.5">
            <i class="fas fa-exclamation-triangle"></i>
            Đảm bảo thư mục <strong>lessonvid/</strong> trong ELearning có quyền ghi (writable).
          </p>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-3 pt-2 border-t border-slate-100">
        <button type="submit" name="addLessonBtn"
                class="px-8 py-3 bg-primary text-white font-bold text-sm rounded-xl hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
          <i class="fas fa-plus-circle"></i> Thêm bài học
        </button>
        <a href="lessons.php" class="px-6 py-3 bg-slate-100 text-slate-600 font-semibold text-sm rounded-xl hover:bg-slate-200 transition-all no-underline">Huỷ</a>
      </div>
    </form>
  </div>
</div>

<script>
function switchVideoMode(mode) {
    document.getElementById('videoModeInput').value = mode;

    const panelLink   = document.getElementById('panelLink');
    const panelUpload = document.getElementById('panelUpload');
    const tabLink     = document.getElementById('tabLink');
    const tabUpload   = document.getElementById('tabUpload');
    const linkInput   = document.getElementById('lessonLinkInput');
    const fileInput   = document.getElementById('lessonVideoFile');

    if (mode === 'link') {
        panelLink.classList.remove('hidden');
        panelUpload.classList.add('hidden');
        tabLink.classList.add('bg-primary','text-white');
        tabLink.classList.remove('bg-white','text-slate-500');
        tabUpload.classList.add('bg-white','text-slate-500');
        tabUpload.classList.remove('bg-primary','text-white');
        linkInput.required = true;
        fileInput.required = false;
    } else {
        panelUpload.classList.remove('hidden');
        panelLink.classList.add('hidden');
        tabUpload.classList.add('bg-primary','text-white');
        tabUpload.classList.remove('bg-white','text-slate-500');
        tabLink.classList.add('bg-white','text-slate-500');
        tabLink.classList.remove('bg-primary','text-white');
        fileInput.required = true;
        linkInput.required = false;
        linkInput.value = '';
    }
}
</script>

<?php include('./adminInclude/footer.php'); ?>


