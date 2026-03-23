<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../csrf.php');

define('TITLE', 'Hồ sơ của tôi');
define('PAGE', 'profile');
include_once('../dbConnection.php');

if(isset($_SESSION['is_login'])){
  $stuEmail = $_SESSION['stuLogEmail'];
} else {
  header('Location: ../index.php');
  exit;
}

$stuId = 0;
$stuName = '';
$stuOcc = '';
$stuImg = '';

$stmtStudent = $conn->prepare('SELECT stu_id, stu_name, stu_occ, stu_img FROM student WHERE stu_email = ? AND is_deleted = 0 LIMIT 1');
if($stmtStudent) {
  $stmtStudent->bind_param('s', $stuEmail);
  $stmtStudent->execute();
  $result = $stmtStudent->get_result();
  if($result && $result->num_rows === 1){
    $row = $result->fetch_assoc();
    $stuId = (int) $row['stu_id'];
    $stuName = $row['stu_name'];
    $stuOcc = $row['stu_occ'];
    $stuImg = $row['stu_img'];
    if(!empty($stuImg)) {
      $stuImg = "../" . ltrim(str_replace('../', '', $stuImg), '/');
    }
  }
  $stmtStudent->close();
}

if($stuId <= 0) {
  header('Location: ../logout.php');
  exit;
}

include('./stuInclude/header.php');

 if(isset($_POST['updateStuNameBtn'])){
  if(!csrf_verify($_POST['csrf_token'] ?? null)) {
   $passmsg = 'error:Phiên gửi biểu mẫu đã hết hạn. Vui lòng thử lại.';
  } else {
   $newStuName = trim((string) ($_POST['stuName'] ?? ''));
   $newStuOcc = trim((string) ($_POST['stuOcc'] ?? ''));

   if($newStuName === ''){
   $passmsg = 'error:Vui lòng điền đầy đủ thông tin.';
   } else {
    $newAvatarPath = null;
    $newAvatarDiskPath = null;
    $uploadInfo = $_FILES['stuImg'] ?? null;

   if($uploadInfo && (int) ($uploadInfo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
     if((int) $uploadInfo['error'] !== UPLOAD_ERR_OK) {
       $passmsg = 'error:Tải ảnh đại diện thất bại. Vui lòng thử lại.';
     } elseif((int) $uploadInfo['size'] > 2 * 1024 * 1024) {
       $passmsg = 'error:Ảnh đại diện vượt quá 2MB.';
     } else {
       $tmpName = (string) $uploadInfo['tmp_name'];
       $finfo = new finfo(FILEINFO_MIME_TYPE);
       $mimeType = $finfo->file($tmpName);
       $allowedTypes = [
         'image/jpeg' => 'jpg',
         'image/png' => 'png',
         'image/webp' => 'webp',
       ];

       if(!isset($allowedTypes[$mimeType])) {
         $passmsg = 'error:Chỉ hỗ trợ ảnh JPG, PNG hoặc WebP.';
       } else {
         $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
         $imgDisk = __DIR__ . '/../image/stu/' . $filename;
          if(!move_uploaded_file($tmpName, $imgDisk)) {
            $passmsg = 'error:Không thể lưu ảnh đại diện.';
          } else {
            $newAvatarPath = 'image/stu/' . $filename;
            $newAvatarDiskPath = $imgDisk;
          }
        }
      }
   }

    if(!isset($passmsg)) {
      $stmtUpdate = null;
      if($newAvatarPath !== null) {
        $stmtUpdate = $conn->prepare('UPDATE student SET stu_name = ?, stu_occ = ?, stu_img = ? WHERE stu_email = ?');
        if($stmtUpdate) {
         $stmtUpdate->bind_param('ssss', $newStuName, $newStuOcc, $newAvatarPath, $stuEmail);
       }
     } else {
       $stmtUpdate = $conn->prepare('UPDATE student SET stu_name = ?, stu_occ = ? WHERE stu_email = ?');
       if($stmtUpdate) {
         $stmtUpdate->bind_param('sss', $newStuName, $newStuOcc, $stuEmail);
       }
     }

     if($stmtUpdate && $stmtUpdate->execute()) {
       $stmtUpdate->close();

        $refreshStmt = $conn->prepare('SELECT stu_name, stu_occ, stu_img FROM student WHERE stu_email = ? LIMIT 1');
        if($refreshStmt) {
          $refreshStmt->bind_param('s', $stuEmail);
          $refreshStmt->execute();
          $result_refresh = $refreshStmt->get_result();
          if($result_refresh && $result_refresh->num_rows === 1){
            $row_refresh = $result_refresh->fetch_assoc();
            $stuName = $row_refresh['stu_name'];
            $stuOcc = $row_refresh['stu_occ'];
            $stuImg = $row_refresh['stu_img'];
            if(!empty($stuImg)) {
              $stuImg = "../" . ltrim(str_replace('../', '', $stuImg), '/');
            }
          }
          $refreshStmt->close();
        }
        $passmsg = 'success:Cập nhật thông tin thành công!';
     } else {
        if($stmtUpdate) {
          $stmtUpdate->close();
        }

        if($newAvatarDiskPath !== null && is_file($newAvatarDiskPath)) {
          @unlink($newAvatarDiskPath);
        }

        $passmsg = 'error:Không thể cập nhật thông tin.';
     }
    }
   }
  }
 }

// Fetch owned courses
$my_courses_result = false;
$myCoursesStmt = $conn->prepare(
  'SELECT c.*, co.order_date FROM courseorder co '
  . 'JOIN course c ON co.course_id = c.course_id '
  . "WHERE co.stu_email = ? AND co.status = 'TXN_SUCCESS' AND co.is_deleted = 0 "
  . 'ORDER BY co.order_date DESC'
);
if($myCoursesStmt) {
  $myCoursesStmt->bind_param('s', $stuEmail);
  $myCoursesStmt->execute();
  $my_courses_result = $myCoursesStmt->get_result();
}

// Fetch my feedback (bảng feedback không có course_id, bỏ JOIN)
$my_feedback_result = false;
$myFeedbackStmt = $conn->prepare('SELECT * FROM feedback WHERE stu_id = ? AND is_deleted = 0 ORDER BY f_id DESC');
if($myFeedbackStmt) {
  $myFeedbackStmt->bind_param('i', $stuId);
  $myFeedbackStmt->execute();
  $my_feedback_result = $myFeedbackStmt->get_result();
}
?>

<!-- ====== PROFILE PAGE ====== -->
<div class="max-w-5xl mx-auto px-6 py-12">

    <!-- Profile Header Card -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden mb-8">
        <div class="h-28 bg-gradient-to-r from-primary to-slate-700 relative"></div>
        <div class="px-8 pb-8 flex flex-col sm:flex-row gap-6 items-start sm:items-end -mt-14">
            <!-- Avatar -->
            <div class="relative shrink-0">
                <img src="<?php echo !empty($stuImg) ? $stuImg : '../image/stu/default_avatar.png'; ?>" 
                     onerror="this.onerror=null;this.src='../image/stu/default_avatar.png'"
                     class="w-28 h-28 rounded-full object-cover border-4 border-white shadow-lg" 
                     alt="Avatar" id="avatarPreview">
            </div>
            <!-- Name + Email -->
            <div class="flex-grow pb-1">
                <h1 class="text-2xl font-black text-slate-900"><?php echo !empty($stuName) ? htmlspecialchars($stuName) : 'Học viên'; ?></h1>
                <p class="text-slate-500 text-sm mt-1">
                    <i class="fas fa-envelope text-primary mr-1"></i><?php echo htmlspecialchars($stuEmail); ?>
                    <?php if(!empty($stuOcc)): ?>
                    &nbsp;·&nbsp;<i class="fas fa-briefcase text-primary mr-1"></i><?php echo htmlspecialchars($stuOcc); ?>
                    <?php endif; ?>
                </p>
            </div>
            <!-- Cart button -->
            <a href="myCart.php" class="px-5 py-2.5 border-2 border-primary text-primary text-sm font-bold rounded-xl hover:bg-primary hover:text-white transition-all flex items-center gap-2 self-end mb-1">
                <i class="fas fa-shopping-cart"></i> Giỏ hàng
            </a>
        </div>
    </div>

    <!-- ======= TAB NAVIGATION ======= -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <!-- Tab Bar -->
        <div class="flex border-b border-slate-100 px-2 overflow-x-auto">
            <button onclick="switchTab('tab-profile', this)" 
                    class="stu-tab px-6 py-4 text-sm font-semibold text-slate-500 whitespace-nowrap flex items-center gap-2 active-tab" id="btn-profile">
                <i class="fas fa-user"></i> Thông tin cá nhân
            </button>
            <button onclick="switchTab('tab-courses', this)" 
                    class="stu-tab px-6 py-4 text-sm font-semibold text-slate-500 whitespace-nowrap flex items-center gap-2" id="btn-courses">
                <i class="fas fa-book-reader"></i> Khóa học của tôi
            </button>
            <button onclick="switchTab('tab-feedback', this)" 
                    class="stu-tab px-6 py-4 text-sm font-semibold text-slate-500 whitespace-nowrap flex items-center gap-2" id="btn-feedback">
                <i class="fas fa-star"></i> Đánh giá của tôi
            </button>
            <button onclick="switchTab('tab-password', this)" 
                    class="stu-tab px-6 py-4 text-sm font-semibold text-slate-500 whitespace-nowrap flex items-center gap-2" id="btn-password">
                <i class="fas fa-key"></i> Đổi mật khẩu
            </button>
        </div>

        <!-- ========== TAB 1: Thông tin cá nhân ========== -->
        <div id="tab-profile" class="tab-content p-8">
            <?php if(isset($passmsg)): 
                $parts = explode(':', $passmsg, 2);
                $msgType = $parts[0]; $msgText = $parts[1];
                $alertClass = $msgType === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-600';
                $icon = $msgType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            ?>
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl border <?php echo $alertClass; ?> mb-6">
                <i class="fas <?php echo $icon; ?>"></i>
                <span class="text-sm font-medium"><?php echo htmlspecialchars($msgText); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6 max-w-2xl">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="grid sm:grid-cols-2 gap-6">
                    <!-- Mã học viên (readonly) -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Mã học viên</label>
                        <input type="text" value="<?php echo isset($stuId) ? $stuId : ''; ?>" 
                               class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-500 text-sm" readonly>
                    </div>
                    <!-- Email (readonly) -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                        <input type="email" value="<?php echo $stuEmail; ?>" 
                               class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-500 text-sm" readonly>
                    </div>
                    <!-- Họ và tên -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Họ và tên <span class="text-red-500">*</span></label>
                        <input type="text" name="stuName" id="stuName"
                               value="<?php echo isset($stuName) ? htmlspecialchars($stuName) : ''; ?>"
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl text-slate-800 text-sm focus:border-primary focus:ring-1 focus:ring-primary/30 outline-none transition-all">
                    </div>
                    <!-- Nghề nghiệp -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nghề nghiệp</label>
                        <input type="text" name="stuOcc" id="stuOcc"
                               value="<?php echo isset($stuOcc) ? htmlspecialchars($stuOcc) : ''; ?>"
                               placeholder="VD: Sinh viên, Lập trình viên..."
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl text-slate-800 text-sm focus:border-primary focus:ring-1 focus:ring-primary/30 outline-none transition-all">
                    </div>
                </div>

                <!-- Cập nhật ảnh đại diện -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-3">Ảnh đại diện</label>
                    <div class="flex items-center gap-4">
                        <img src="<?php echo !empty($stuImg) ? $stuImg : '../image/stu/default_avatar.png'; ?>" 
                             onerror="this.onerror=null;this.src='../image/stu/default_avatar.png'"
                             class="w-16 h-16 rounded-full object-cover border-2 border-slate-200 shadow-sm" id="smallAvatarPreview">
                        <div class="flex-grow">
                            <label for="stuImg" 
                                   class="cursor-pointer flex items-center gap-2 px-4 py-2.5 border-2 border-dashed border-slate-300 rounded-xl text-sm text-slate-500 hover:border-primary hover:text-primary transition-all">
                                <i class="fas fa-cloud-upload-alt"></i> Chọn ảnh mới (JPG, PNG, WebP)
                            </label>
                            <input type="file" name="stuImg" id="stuImg" class="hidden" accept=".jpg,.jpeg,.png,.webp"
                                   onchange="previewAvatar(this)">
                        </div>
                    </div>
                </div>

                <button type="submit" name="updateStuNameBtn"
                        class="px-8 py-3.5 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
            </form>
        </div>

        <!-- ========== TAB 2: Khóa học của tôi ========== -->
        <div id="tab-courses" class="tab-content p-8 hidden">
            <h2 class="text-xl font-black text-slate-900 mb-6 flex items-center gap-3">
                <i class="fas fa-book-reader text-primary"></i> Khóa học đã mua
            </h2>
            <?php if($my_courses_result && $my_courses_result->num_rows > 0): ?>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while($c = $my_courses_result->fetch_assoc()): 
                    $c_img = '../' . ltrim(str_replace('../', '', $c['course_img']), '/');
                ?>
                <div class="bg-slate-50 rounded-2xl overflow-hidden border border-slate-100 hover:shadow-md transition-all group">
                     <a href="watchcourse.php?course_id=<?php echo $c['course_id']; ?>" class="block aspect-video overflow-hidden">
                        <img src="<?php echo $c_img; ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="Course image" onerror="this.onerror=null;this.src='../image/courseimg/course5.jpg'">
                    </a>
                    <div class="p-4">
                        <h3 class="font-bold text-slate-900 text-sm line-clamp-2 mb-2"><?php echo htmlspecialchars($c['course_name']); ?></h3>
                        <p class="text-xs text-slate-400 mb-3"><i class="fas fa-calendar-alt mr-1"></i><?php echo $c['order_date']; ?></p>
                        <a href="watchcourse.php?course_id=<?php echo $c['course_id']; ?>" 
                           class="w-full flex items-center justify-center gap-2 py-2.5 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary/90 transition-all">
                            <i class="fas fa-play-circle"></i> Học ngay
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-20">
                <i class="fas fa-book-open text-5xl text-slate-200 mb-4 block"></i>
                <p class="text-slate-500 mb-6">Bạn chưa có khóa học nào.</p>
                <a href="../courses.php" class="px-6 py-3 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all">
                    <i class="fas fa-search mr-2"></i>Khám phá khóa học
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- ========== TAB 3: Đánh giá của tôi ========== -->
        <div id="tab-feedback" class="tab-content p-8 hidden">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-black text-slate-900 flex items-center gap-3">
                    <i class="fas fa-star text-primary"></i> Đánh giá của tôi
                </h2>
                <a href="stufeedback.php" class="px-5 py-2.5 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary/90 transition-all flex items-center gap-2">
                    <i class="fas fa-plus"></i> Viết đánh giá mới
                </a>
            </div>
            <?php if($my_feedback_result && $my_feedback_result->num_rows > 0): ?>
            <div class="space-y-4">
                <?php while($fb = $my_feedback_result->fetch_assoc()): ?>
                <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100">
                    <p class="text-slate-700 italic text-sm leading-relaxed">"<?php echo htmlspecialchars($fb['f_content']); ?>"</p>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-16">
                <i class="fas fa-star text-5xl text-slate-200 mb-4 block"></i>
                <p class="text-slate-500">Bạn chưa có đánh giá nào.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- ========== TAB 4: Đổi mật khẩu ========== -->
        <div id="tab-password" class="tab-content p-8 hidden">
            <h2 class="text-xl font-black text-slate-900 mb-6 flex items-center gap-3">
                <i class="fas fa-key text-primary"></i> Đổi mật khẩu
            </h2>
            <?php
            // Handle password change
            $passMsgChange = '';
            if(isset($_POST['changePassBtn'])){
                $oldPass = $_POST['oldPass'] ?? '';
                $newPass = $_POST['newPass'] ?? '';
                $confirmPass = $_POST['confirmPass'] ?? '';
                $rowCheck = null;

                $checkStmt = $conn->prepare('SELECT stu_pass FROM student WHERE stu_email = ? AND is_deleted = 0 LIMIT 1');
                if($checkStmt) {
                    $checkStmt->bind_param('s', $stuEmail);
                    $checkStmt->execute();
                    $resCheck = $checkStmt->get_result();
                    $rowCheck = $resCheck ? $resCheck->fetch_assoc() : null;
                    $checkStmt->close();
                }
                
                if(!csrf_verify($_POST['csrf_token'] ?? null)){
                    $passMsgChange = 'error:Phiên gửi biểu mẫu đã hết hạn. Vui lòng thử lại.';
                } elseif(!$rowCheck || !password_verify($oldPass, $rowCheck['stu_pass'])){
                    $passMsgChange = 'error:Mật khẩu cũ không đúng.';
                } elseif($newPass !== $confirmPass){
                    $passMsgChange = 'error:Mật khẩu mới không khớp nhau.';
                } elseif(strlen($newPass) < 6){
                    $passMsgChange = 'error:Mật khẩu mới phải có ít nhất 6 ký tự.';
                } else {
                    $hashedNewPass = password_hash($newPass, PASSWORD_DEFAULT);
                    $updatePassStmt = $conn->prepare('UPDATE student SET stu_pass = ? WHERE stu_email = ?');
                    if($updatePassStmt){
                        $updatePassStmt->bind_param('ss', $hashedNewPass, $stuEmail);
                    }

                    if($updatePassStmt && $updatePassStmt->execute()){
                        $passMsgChange = 'success:Đổi mật khẩu thành công!';
                    } else {
                        $passMsgChange = 'error:Không thể đổi mật khẩu. Vui lòng thử lại.';
                    }

                    if($updatePassStmt) {
                        $updatePassStmt->close();
                    }
                }
                // Switch to the password tab on reload
                echo '<script>document.addEventListener("DOMContentLoaded", function(){ switchTab("tab-password", document.getElementById("btn-password")); });</script>';
            }
            if($passMsgChange):
                $pcp = explode(':', $passMsgChange, 2);
                $pc_type = $pcp[0]; $pc_text = $pcp[1];
                $pc_cls = $pc_type==='success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-600';
                $pc_icon = $pc_type==='success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            ?>
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl border <?php echo $pc_cls; ?> mb-6">
                <i class="fas <?php echo $pc_icon; ?>"></i>
                <span class="text-sm font-medium"><?php echo htmlspecialchars($pc_text); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5 max-w-md">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Mật khẩu hiện tại</label>
                    <input type="password" name="oldPass" required
                           class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:border-primary focus:ring-1 focus:ring-primary/30 outline-none transition-all" 
                           placeholder="Nhập mật khẩu hiện tại">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Mật khẩu mới</label>
                    <input type="password" name="newPass" required minlength="6"
                           class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:border-primary focus:ring-1 focus:ring-primary/30 outline-none transition-all"
                           placeholder="Ít nhất 6 ký tự">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Xác nhận mật khẩu mới</label>
                    <input type="password" name="confirmPass" required
                           class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:border-primary focus:ring-1 focus:ring-primary/30 outline-none transition-all"
                           placeholder="Nhập lại mật khẩu mới">
                </div>
                <button type="submit" name="changePassBtn"
                        class="px-8 py-3.5 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                    <i class="fas fa-lock"></i> Cập nhật mật khẩu
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Tab switching
function switchTab(tabId, btnEl) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.stu-tab').forEach(el => el.classList.remove('active-tab', 'text-primary'));
    document.getElementById(tabId).classList.remove('hidden');
    btnEl.classList.add('active-tab', 'text-primary');
}

// Avatar preview
function previewAvatar(input) {
    if(input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
            document.getElementById('smallAvatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php
if(isset($myCoursesStmt) && $myCoursesStmt) {
    $myCoursesStmt->close();
}
if(isset($myFeedbackStmt) && $myFeedbackStmt) {
    $myFeedbackStmt->close();
}
?>

<?php include('./stuInclude/footer.php'); ?>
