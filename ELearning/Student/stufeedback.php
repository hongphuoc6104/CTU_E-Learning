<?php
if(!isset($_SESSION)){ 
  session_start(); 
}
define('TITLE', 'Đánh giá của tôi');
define('PAGE', 'feedback');
include('./stuInclude/header.php'); 
include_once('../dbConnection.php');

 if(isset($_SESSION['is_login'])){
  $stuEmail = $_SESSION['stuLogEmail'];
 } else {
  echo "<script> location.href='../index.php'; </script>";
 }

 $sql = "SELECT * FROM student WHERE stu_email='$stuEmail'";
 $result = $conn->query($sql);
 if($result->num_rows == 1){
 $row = $result->fetch_assoc();
 $stuId = $row["stu_id"];
}

// Lấy danh sách khóa học đã mua để có thể gắn đánh giá theo khóa học
$courses_sql = "SELECT c.course_id, c.course_name FROM courseorder co JOIN course c ON co.course_id = c.course_id WHERE co.stu_email='$stuEmail'";
$courses_result = $conn->query($courses_sql);

$passmsg = '';
if(isset($_REQUEST['submitFeedbackBtn'])){
  if(($_REQUEST['f_content'] == "")){
   $passmsg = 'error:Vui lòng nhập nội dung đánh giá.';
  } else {
   $fcontent = htmlspecialchars($_REQUEST["f_content"]);
   $sql = "INSERT INTO feedback (f_content, stu_id) VALUES ('$fcontent', '$stuId')";
   
   if($conn->query($sql) == TRUE){
    $passmsg = 'success:Cảm ơn bạn đã chia sẻ đánh giá!';
   } else {
    $passmsg = 'error:Không thể gửi đánh giá. Vui lòng thử lại.';
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
        <span class="text-sm font-medium"><?php echo $parts[1]; ?></span>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
        <form method="POST" class="space-y-6">
            <!-- Mã học viên -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Mã học viên</label>
                <input type="text" value="<?php echo isset($stuId) ? $stuId : ''; ?>" 
                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-500 text-sm" readonly>
            </div>

            <!-- Khóa học (nếu có) -->
            <?php if($courses_result && $courses_result->num_rows > 0): ?>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Đánh giá cho khóa học (không bắt buộc)</label>
                <select name="course_id" class="w-full px-4 py-3 border border-slate-200 rounded-xl text-slate-800 text-sm focus:border-primary focus:ring-1 focus:ring-primary/30 outline-none transition-all">
                    <option value="">— Đánh giá chung —</option>
                    <?php while($c = $courses_result->fetch_assoc()): ?>
                    <option value="<?php echo $c['course_id']; ?>"><?php echo htmlspecialchars($c['course_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <?php endif; ?>

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
