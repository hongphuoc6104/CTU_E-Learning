<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../csrf.php');

define('TITLE', 'Sửa khoá học');
define('PAGE', 'courses');
include('./adminInclude/header.php');
include('../dbConnection.php');

if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>";
}

$cid = (int)($_GET['id'] ?? $_POST['cid'] ?? 0);
if(!$cid){ echo "<script>location.href='courses.php';</script>"; exit; }

// Load existing
$row = null;
$loadStmt = $conn->prepare('SELECT * FROM course WHERE course_id = ? LIMIT 1');
if($loadStmt) {
    $loadStmt->bind_param('i', $cid);
    $loadStmt->execute();
    $loadResult = $loadStmt->get_result();
    $row = $loadResult ? $loadResult->fetch_assoc() : null;
    $loadStmt->close();
}
if(!$row){ echo "<script>location.href='courses.php';</script>"; exit; }

$msg = '';
if(isset($_POST['updateCourseBtn'])){
    $name     = trim((string) ($_POST['course_name'] ?? ''));
    $desc     = trim((string) ($_POST['course_desc'] ?? ''));
    $author   = trim((string) ($_POST['course_author'] ?? ''));
    $duration = trim((string) ($_POST['course_duration'] ?? ''));
    $priceInput = filter_input(INPUT_POST, 'course_price', FILTER_VALIDATE_INT);
    $origInput = filter_input(INPUT_POST, 'course_original_price', FILTER_VALIDATE_INT);
    $price = $priceInput === false ? null : (int) $priceInput;
    $orig = $origInput === false ? null : (int) $origInput;

    if(!csrf_verify($_POST['csrf_token'] ?? null)) {
        $msg = ['type'=>'error', 'text'=>'Phiên gửi biểu mẫu đã hết hạn. Vui lòng thử lại.'];
    } elseif($name === '' || $desc === '' || $author === '' || $duration === '' || $price === null || $orig === null){
        $msg = ['type'=>'error', 'text'=>'Vui lòng điền đầy đủ thông tin.'];
    } elseif($price < 0 || $orig < 0) {
        $msg = ['type'=>'error', 'text'=>'Giá khóa học không được là số âm.'];
    } elseif($orig < $price) {
        $msg = ['type'=>'error', 'text'=>'Giá gốc không được nhỏ hơn giá bán thực tế.'];
    } else {
        // Handle image upload
        $img_db = $row['course_img']; // keep existing
        $newImageDiskPath = null;
        if(isset($_FILES['course_img']) && $_FILES['course_img']['error'] === UPLOAD_ERR_OK){
            $ext  = strtolower(pathinfo($_FILES['course_img']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if(!in_array($ext, $allowed)){
                $msg = ['type'=>'error', 'text'=>'Định dạng ảnh không hợp lệ.'];
                goto render;
            }
            if($_FILES['course_img']['size'] > 2097152){
                $msg = ['type'=>'error', 'text'=>'Ảnh vượt quá 2MB.'];
                goto render;
            }
            $filename = time().'_'.basename($_FILES['course_img']['name']);
            $disk = __DIR__.'/../image/courseimg/'.$filename;
            if(!move_uploaded_file($_FILES['course_img']['tmp_name'], $disk)) {
                $msg = ['type'=>'error', 'text'=>'Không thể lưu ảnh khoá học.'];
                goto render;
            }
            $newImageDiskPath = $disk;
            $img_db = 'image/courseimg/'.$filename;
        }

        $stmt = $conn->prepare("UPDATE course SET course_name=?, course_desc=?, course_author=?, course_duration=?, course_price=?, course_original_price=?, course_img=? WHERE course_id=?");
        if($stmt) {
            $stmt->bind_param('ssssiisi', $name, $desc, $author, $duration, $price, $orig, $img_db, $cid);
            if($stmt->execute()){
                $msg = ['type'=>'success', 'text'=>'Cập nhật khoá học thành công!'];
                $refreshStmt = $conn->prepare('SELECT * FROM course WHERE course_id = ? LIMIT 1');
                if($refreshStmt) {
                    $refreshStmt->bind_param('i', $cid);
                    $refreshStmt->execute();
                    $refreshResult = $refreshStmt->get_result();
                    $row = $refreshResult ? $refreshResult->fetch_assoc() : $row;
                    $refreshStmt->close();
                }
            } else {
                if($newImageDiskPath !== null && is_file($newImageDiskPath)) {
                    @unlink($newImageDiskPath);
                }
                $msg = ['type'=>'error', 'text'=>'Lỗi khi cập nhật.'];
            }
            $stmt->close();
        } else {
            if($newImageDiskPath !== null && is_file($newImageDiskPath)) {
                @unlink($newImageDiskPath);
            }
            $msg = ['type'=>'error', 'text'=>'Lỗi khi cập nhật.'];
        }
    }
}

render:
$img_display = ltrim(str_replace('../', '', $row['course_img'] ?? ''), '/');
?>

<div class="max-w-2xl">
  <?php if($msg): ?>
  <div class="mb-4 p-4 rounded-xl text-sm font-medium
    <?php echo $msg['type']==='success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?>">
    <i class="fas <?php echo $msg['type']==='success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
    <?php echo $msg['text']; ?>
  </div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8">
    <form method="POST" enctype="multipart/form-data" class="space-y-5">
      <input type="hidden" name="cid" value="<?php echo $cid; ?>">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

      <div><label class="block text-sm font-semibold text-slate-700 mb-2">Tên khoá học <span class="text-red-500">*</span></label>
        <input type="text" name="course_name" value="<?php echo htmlspecialchars($row['course_name']); ?>" required
               class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20">
      </div>
      <div><label class="block text-sm font-semibold text-slate-700 mb-2">Mô tả <span class="text-red-500">*</span></label>
        <textarea name="course_desc" rows="4" required
                  class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 resize-none"><?php echo htmlspecialchars($row['course_desc']); ?></textarea>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div><label class="block text-sm font-semibold text-slate-700 mb-2">Giảng viên <span class="text-red-500">*</span></label>
          <input type="text" name="course_author" value="<?php echo htmlspecialchars($row['course_author']); ?>" required
                 class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20">
        </div>
        <div><label class="block text-sm font-semibold text-slate-700 mb-2">Thời lượng <span class="text-red-500">*</span></label>
          <input type="text" name="course_duration" value="<?php echo htmlspecialchars($row['course_duration']); ?>" required
                 class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20" placeholder="VD: 10 giờ">
        </div>
        <div><label class="block text-sm font-semibold text-slate-700 mb-2">Giá gốc</label>
          <input type="number" name="course_original_price" value="<?php echo $row['course_original_price']; ?>"
                 class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20">
        </div>
        <div><label class="block text-sm font-semibold text-slate-700 mb-2">Giá bán <span class="text-red-500">*</span></label>
          <input type="number" name="course_price" value="<?php echo $row['course_price']; ?>" required
                 class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20">
        </div>
      </div>
      <!-- Image -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Ảnh đại diện</label>
        <?php if($img_display): ?>
        <img src="../<?php echo $img_display; ?>" class="w-32 h-20 object-cover rounded-xl border border-slate-200 mb-3">
        <?php endif; ?>
        <input type="file" name="course_img" accept=".jpg,.jpeg,.png,.webp"
               class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-medium hover:file:bg-primary/20">
        <p class="text-xs text-slate-400 mt-1">Để trống nếu không muốn thay ảnh. Tối đa 2MB.</p>
      </div>
      <!-- Buttons -->
      <div class="flex gap-3 pt-2">
        <button type="submit" name="updateCourseBtn"
                class="px-6 py-3 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition text-sm">
          <i class="fas fa-save mr-2"></i>Lưu thay đổi
        </button>
        <a href="courses.php" class="px-6 py-3 bg-slate-100 text-slate-600 font-semibold rounded-xl hover:bg-slate-200 transition text-sm">Huỷ</a>
      </div>
    </form>
  </div>
</div>

<?php include('./adminInclude/footer.php'); ?>
