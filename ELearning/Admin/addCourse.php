<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../csrf.php');
require_once(__DIR__ . '/../upload_helpers.php');

define('TITLE', 'Thêm khoá học');
include('./adminInclude/header.php'); 
include('../dbConnection.php');

 if(isset($_SESSION['is_admin_login'])){
  $adminEmail = $_SESSION['adminLogEmail'];
 } else {
  echo "<script> location.href='../index.php'; </script>";
 }
 if(isset($_POST['courseSubmitBtn'])){
  if(!csrf_verify($_POST['csrf_token'] ?? null)) {
    $msg = ['type'=>'error', 'text'=>'Phiên gửi biểu mẫu đã hết hạn. Vui lòng thử lại.'];
  } else {
    $course_name = trim((string) ($_POST['course_name'] ?? ''));
    $course_desc = trim((string) ($_POST['course_desc'] ?? ''));
    $course_author = trim((string) ($_POST['course_author'] ?? ''));
    $course_duration = trim((string) ($_POST['course_duration'] ?? ''));
    $course_price = filter_input(INPUT_POST, 'course_price', FILTER_VALIDATE_INT);
    $course_original_price = filter_input(INPUT_POST, 'course_original_price', FILTER_VALIDATE_INT);

    if($course_name === '' || $course_desc === '' || $course_author === '' || $course_duration === '' || $course_price === false || $course_original_price === false){
      $msg = ['type'=>'warning', 'text'=>'Vui lòng điền đầy đủ tất cả các trường dữ liệu!'];
    } elseif($course_price < 0 || $course_original_price < 0) {
      $msg = ['type'=>'warning', 'text'=>'Giá khóa học không được là số âm.'];
    } elseif($course_original_price < $course_price) {
      $msg = ['type'=>'warning', 'text'=>'Giá gốc không được nhỏ hơn giá bán thực tế.'];
    } else {
      $course_image = $_FILES['course_img']['name'] ?? '';
      $course_image_temp = $_FILES['course_img']['tmp_name'] ?? '';
      $image_size = (int) ($_FILES['course_img']['size'] ?? 0);
      $image_error = (int) ($_FILES['course_img']['error'] ?? UPLOAD_ERR_NO_FILE);

      $allowed_types = array('jpg', 'jpeg', 'png', 'webp');
      $file_ext = strtolower(pathinfo($course_image, PATHINFO_EXTENSION));

      if ($image_error === UPLOAD_ERR_NO_FILE) {
          $msg = ['type'=>'warning', 'text'=>'Vui lòng tải lên một ảnh đại diện khoá học!'];
      } else if (!in_array($file_ext, $allowed_types, true)) {
          $msg = ['type'=>'error', 'text'=>'Định dạng ảnh không hỗ trợ. Chỉ chấp nhận jpg, jpeg, png, webp.'];
      } else if ($image_size > 2097152) { // 2MB
          $msg = ['type'=>'error', 'text'=>'Dung lượng ảnh lớn hơn mức cho phép (2MB).'];
      } else {
        $uploadResult = app_upload_store_file(
          $course_image_temp,
          $course_image,
          __DIR__ . '/../image/courseimg/',
          'image/courseimg',
          'thư mục ảnh khóa học',
          'course'
        );

        if(!($uploadResult['ok'] ?? false)) {
          $msg = ['type'=>'error', 'text'=>(string) ($uploadResult['message'] ?? 'Không thể lưu ảnh khoá học.')];
        } else {
          $img_disk = (string) ($uploadResult['disk_path'] ?? '');
          $img_db   = (string) ($uploadResult['db_path'] ?? '');
          $stmtInsert = $conn->prepare('INSERT INTO course (course_name, course_desc, course_author, course_img, course_duration, course_price, course_original_price, is_deleted) VALUES (?, ?, ?, ?, ?, ?, ?, 0)');
          if($stmtInsert) {
            $stmtInsert->bind_param('sssssii', $course_name, $course_desc, $course_author, $img_db, $course_duration, $course_price, $course_original_price);
            if($stmtInsert->execute()) {
              $msg = ['type'=>'success', 'text'=>'Thêm khoá học thành công!'];
            } else {
              if(is_file($img_disk)) {
                @unlink($img_disk);
              }
              $msg = ['type'=>'error', 'text'=>'Không thể thêm khoá học.'];
            }
            $stmtInsert->close();
          } else {
            if(is_file($img_disk)) {
              @unlink($img_disk);
            }
            $msg = ['type'=>'error', 'text'=>'Không thể thêm khoá học.'];
          }
        }
      }
    }
  }
}
?>

<?php if(isset($msg)): 
    $alertColors = ['success'=>'bg-green-50 border-green-200 text-green-700', 'error'=>'bg-red-50 border-red-200 text-red-600', 'warning'=>'bg-yellow-50 border-yellow-200 text-yellow-700'];
    $alertIcons  = ['success'=>'fa-check-circle', 'error'=>'fa-exclamation-circle', 'warning'=>'fa-exclamation-triangle'];
    $t = $msg['type'];
?>
<div class="mb-6 flex items-center gap-3 px-4 py-3 rounded-xl border <?php echo $alertColors[$t]; ?>">
    <i class="fas <?php echo $alertIcons[$t]; ?>"></i>
    <span class="text-sm font-medium"><?php echo htmlspecialchars($msg['text']); ?></span>
</div>
<?php endif; ?>

<div class="max-w-3xl">
    <!-- Page Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-slate-800">Thêm khoá học mới</h2>
            <p class="text-sm text-slate-500 mt-1">Điền đầy đủ thông tin để tạo một khoá học mới trên hệ thống.</p>
        </div>
        <a href="courses.php" class="flex items-center gap-2 px-4 py-2.5 bg-slate-100 text-slate-600 font-semibold text-sm rounded-xl hover:bg-slate-200 transition-all no-underline">
            <i class="fas fa-arrow-left text-xs"></i> Quay lại
        </a>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8">
        <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

            <!-- Tên khoá học -->
            <div>
                <label for="course_name" class="block text-sm font-semibold text-slate-700 mb-2">
                    Tên khoá học <span class="text-red-500">*</span>
                </label>
                <input type="text" id="course_name" name="course_name" required
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-800 outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all"
                       placeholder="VD: Thiết kế Đồ họa với Adobe Illustrator">
            </div>

            <!-- Mô tả -->
            <div>
                <label for="course_desc" class="block text-sm font-semibold text-slate-700 mb-2">
                    Mô tả khoá học <span class="text-red-500">*</span>
                </label>
                <textarea id="course_desc" name="course_desc" rows="4" required
                          class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-800 outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all resize-none"
                          placeholder="Mô tả ngắn gọn về nội dung và lợi ích của khoá học..."></textarea>
            </div>

            <!-- Tác giả & Thời lượng -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="course_author" class="block text-sm font-semibold text-slate-700 mb-2">
                        Tác giả / Giảng viên <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="course_author" name="course_author" required
                           class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-800 outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all"
                           placeholder="VD: Lê Văn A">
                </div>
                <div>
                    <label for="course_duration" class="block text-sm font-semibold text-slate-700 mb-2">
                        Thời lượng <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="course_duration" name="course_duration" required
                           class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-800 outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all"
                           placeholder="VD: 3 Tháng, 20 giờ...">
                </div>
            </div>

            <!-- Giá gốc & Giá bán -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="course_original_price" class="block text-sm font-semibold text-slate-700 mb-2">
                        Giá gốc (VNĐ) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-medium">₫</span>
                        <input type="text" id="course_original_price" name="course_original_price" required
                               onkeypress="isInputNumber(event)"
                               class="w-full pl-8 pr-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-800 outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all"
                               placeholder="500000">
                    </div>
                </div>
                <div>
                    <label for="course_price" class="block text-sm font-semibold text-slate-700 mb-2">
                        Giá bán thực tế (VNĐ) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-red-400 text-sm font-medium">₫</span>
                        <input type="text" id="course_price" name="course_price" required
                               onkeypress="isInputNumber(event)"
                               class="w-full pl-8 pr-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-800 outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all"
                               placeholder="399000">
                    </div>
                </div>
            </div>

            <!-- Ảnh đại diện -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    Ảnh đại diện khoá học <span class="text-red-500">*</span>
                    <span class="font-normal text-slate-400 ml-1">(JPG, PNG, WebP — tối đa 2MB)</span>
                </label>
                <div class="flex items-center gap-5">
                    <div class="w-24 h-24 rounded-xl bg-slate-100 border-2 border-dashed border-slate-300 flex items-center justify-center shrink-0 overflow-hidden">
                        <i class="fas fa-image text-slate-300 text-2xl" id="imgPlaceholderIcon"></i>
                        <img id="courseImgPreview" class="w-full h-full object-cover hidden" alt="Preview">
                    </div>
                    <label for="course_img" class="flex-grow cursor-pointer flex items-center gap-3 px-5 py-4 border-2 border-dashed border-slate-300 rounded-xl hover:border-primary hover:bg-primary/5 transition-all">
                        <i class="fas fa-cloud-upload-alt text-primary text-xl"></i>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Nhấn để chọn ảnh</p>
                            <p class="text-xs text-slate-400 mt-0.5" id="fileNameLabel">Chưa có file nào được chọn</p>
                        </div>
                        <input type="file" id="course_img" name="course_img" accept=".jpg,.jpeg,.png,.webp" required class="hidden"
                               onchange="previewCourseImg(this)">
                    </label>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                <button type="submit" name="courseSubmitBtn"
                        class="px-8 py-3 bg-primary text-white font-bold text-sm rounded-xl hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i> Thêm khoá học
                </button>
                <a href="courses.php" class="px-6 py-3 bg-slate-100 text-slate-600 font-semibold text-sm rounded-xl hover:bg-slate-200 transition-all no-underline">
                    Huỷ bỏ
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function isInputNumber(evt) {
    var ch = String.fromCharCode(evt.which);
    if (!(/[0-9]/.test(ch))) evt.preventDefault();
}
function previewCourseImg(input) {
    const file = input.files[0];
    if (file) {
        document.getElementById('fileNameLabel').textContent = file.name;
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById('courseImgPreview');
            const icon = document.getElementById('imgPlaceholderIcon');
            img.src = e.target.result;
            img.classList.remove('hidden');
            icon.classList.add('hidden');
        };
        reader.readAsDataURL(file);
    }
}
</script>

</main>
</div>
</div>

<?php
include('./adminInclude/footer.php'); 
?>
