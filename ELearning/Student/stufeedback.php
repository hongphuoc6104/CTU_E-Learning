<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../csrf.php');

define('TITLE', 'Đánh giá của tôi');
define('PAGE', 'feedback');
include('./stuInclude/header.php'); 
include_once('../dbConnection.php');

 if(isset($_SESSION['is_login'])){
  $stuEmail = $_SESSION['stuLogEmail'];
 } else {
  echo "<script> location.href='../index.php'; </script>";
 }

$stuId = 0;
$stuStmt = $conn->prepare('SELECT stu_id FROM student WHERE stu_email = ? AND is_deleted = 0 LIMIT 1');
if($stuStmt) {
  $stuStmt->bind_param('s', $stuEmail);
  $stuStmt->execute();
  $stuResult = $stuStmt->get_result();
  if($stuResult && $stuResult->num_rows === 1) {
    $row = $stuResult->fetch_assoc();
    $stuId = (int) $row['stu_id'];
  }
  $stuStmt->close();
}

$passmsg = '';
if(isset($_POST['submitFeedbackBtn'])){
  if(!csrf_verify($_POST['csrf_token'] ?? null)) {
   $passmsg = 'error:Phiên gửi biểu mẫu đã hết hạn. Vui lòng thử lại.';
  } elseif($stuId <= 0) {
   $passmsg = 'error:Không tìm thấy thông tin học viên.';
  } elseif(trim((string) ($_POST['f_content'] ?? '')) === ''){
   $passmsg = 'error:Vui lòng nhập nội dung đánh giá.';
  } else {
   $fcontent = trim((string) $_POST['f_content']);
   if(strlen($fcontent) < 10) {
    $passmsg = 'error:Nội dung đánh giá cần ít nhất 10 ký tự.';
   } elseif(strlen($fcontent) > 1000) {
    $passmsg = 'error:Nội dung đánh giá tối đa 1000 ký tự.';
   } else {
   $insertStmt = $conn->prepare('INSERT INTO feedback (f_content, stu_id) VALUES (?, ?)');

    if($insertStmt) {
      $insertStmt->bind_param('si', $fcontent, $stuId);
      if($insertStmt->execute()){
        $passmsg = 'success:Cảm ơn bạn đã chia sẻ đánh giá!';
      } else {
        $passmsg = 'error:Không thể gửi đánh giá. Vui lòng thử lại.';
      }
      $insertStmt->close();
    } else {
      $passmsg = 'error:Không thể gửi đánh giá. Vui lòng thử lại.';
   }
}
  }
}
?>

<div class="max-w-2xl mx-auto px-6 py-12">
    <div class="mb-8">
        <h1 class="text-3xl font-black text-slate-900 flex items-center gap-3">
            <i class="fas fa-star text-primary"></i> Viết đánh giá
        </h1>
        <p class="text-slate-500 mt-2">Chia sẻ cảm nhận của bạn về khóa học tại CTU E-Learning.</p>
    </div>

    <?php if($passmsg): 
        $parts = explode(':', $passmsg, 2);
        $cls = $parts[0]==='success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-600';
        $icon = $parts[0]==='success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    ?>
    <div class="flex items-center gap-3 px-4 py-3 rounded-xl border <?php echo $cls; ?> mb-6">
        <i class="fas <?php echo $icon; ?>"></i>
        <span class="text-sm font-medium"><?php echo htmlspecialchars($parts[1], ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
        <form method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <!-- Mã học viên -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Mã học viên</label>
                <input type="text" value="<?php echo isset($stuId) ? $stuId : ''; ?>" 
                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-500 text-sm" readonly>
            </div>

            <!-- Nội dung đánh giá -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Nội dung đánh giá <span class="text-red-500">*</span></label>
                <textarea name="f_content" rows="5" required
                          class="w-full px-4 py-3 border border-slate-200 rounded-xl text-slate-800 text-sm focus:border-primary focus:ring-1 focus:ring-primary/30 outline-none transition-all resize-none"
                          placeholder="Chia sẻ cảm nhận của bạn về chất lượng khóa học, giảng viên, nội dung..."></textarea>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" name="submitFeedbackBtn"
                        class="px-8 py-3.5 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                    <i class="fas fa-paper-plane"></i> Gửi đánh giá
                </button>
                <a href="studentProfile.php" class="text-sm text-slate-500 hover:text-primary transition-colors flex items-center gap-1.5">
                    <i class="fas fa-arrow-left text-xs"></i> Quay lại hồ sơ
                </a>
            </div>
        </form>
    </div>
</div>

<?php include('./stuInclude/footer.php'); ?>
