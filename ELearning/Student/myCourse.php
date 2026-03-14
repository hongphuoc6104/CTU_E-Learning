<?php
if(!isset($_SESSION)){ 
  session_start(); 
}
define('TITLE', 'Khóa học của tôi');
define('PAGE', 'mycourse');
include('./stuInclude/header.php'); 
include_once('../dbConnection.php');

 if(isset($_SESSION['is_login'])){
  $stuLogEmail = $_SESSION['stuLogEmail'];
 } else {
  echo "<script> location.href='../index.php'; </script>";
 }
?>

<div class="max-w-5xl mx-auto px-6 py-12">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-black text-slate-900 flex items-center gap-3">
            <i class="fas fa-book-reader text-primary"></i> Khóa học của tôi
        </h1>
        <p class="text-slate-500 mt-2">Các khóa học bạn đã đăng ký và sở hữu.</p>
    </div>

    <?php 
    if(isset($stuLogEmail)){
        $sql = "SELECT co.order_id, c.course_id, c.course_name, c.course_duration, c.course_desc, c.course_img, c.course_author, c.course_original_price, c.course_price, co.order_date 
                FROM courseorder AS co 
                JOIN course AS c ON c.course_id = co.course_id 
                WHERE co.stu_email = '$stuLogEmail' AND co.is_deleted=0 AND c.is_deleted=0
                ORDER BY co.order_date DESC";
        $result = $conn->query($sql);
        
        if($result->num_rows > 0): ?>
        <div class="space-y-5">
        <?php while($row = $result->fetch_assoc()): 
            $img_src = "../" . ltrim(str_replace('../', '', $row['course_img']), '/');
        ?>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-md transition-all flex flex-col md:flex-row">
            <!-- Course image -->
            <a href="watchcourse.php?course_id=<?php echo $row['course_id']; ?>" class="md:w-56 shrink-0">
                <img src="<?php echo $img_src; ?>" class="w-full h-40 md:h-full object-cover" alt="Course image">
            </a>
            <!-- Info -->
            <div class="flex flex-col flex-grow p-6">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div class="flex-grow">
                        <h2 class="text-lg font-bold text-slate-900 line-clamp-2 mb-2">
                            <?php echo htmlspecialchars($row['course_name']); ?>
                        </h2>
                        <p class="text-sm text-slate-500 line-clamp-2 leading-relaxed mb-4">
                            <?php echo htmlspecialchars(substr($row['course_desc'], 0, 150)) . '...'; ?>
                        </p>
                        <div class="flex flex-wrap gap-4 text-xs text-slate-500">
                            <span class="flex items-center gap-1.5">
                                <i class="fas fa-clock text-primary"></i> <?php echo $row['course_duration']; ?>
                            </span>
                            <?php if(!empty($row['course_author'])): ?>
                            <span class="flex items-center gap-1.5">
                                <i class="fas fa-chalkboard-teacher text-primary"></i> <?php echo htmlspecialchars($row['course_author']); ?>
                            </span>
                            <?php endif; ?>
                            <span class="flex items-center gap-1.5">
                                <i class="fas fa-calendar-alt text-primary"></i> Đăng ký: <?php echo $row['order_date']; ?>
                            </span>
                        </div>
                    </div>
                    <!-- Price tag + button -->
                    <div class="flex flex-col items-start md:items-end gap-3 shrink-0">
                        <div class="text-right">
                            <p class="text-xs text-slate-400 line-through"><?php echo number_format($row['course_original_price']); ?> đ</p>
                            <p class="text-lg font-black text-red-600"><?php echo number_format($row['course_price']); ?> đ</p>
                        </div>
                        <a href="watchcourse.php?course_id=<?php echo $row['course_id']; ?>" 
                           class="px-5 py-2.5 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary/90 transition-all shadow-md shadow-primary/20 flex items-center gap-2">
                            <i class="fas fa-play-circle"></i> Học ngay
                        </a>
                    </div>
                </div>
                <!-- Progress bar (decorative) -->
                <div class="mt-5 pt-4 border-t border-slate-100">
                    <div class="flex justify-between text-xs text-slate-400 mb-1.5">
                        <span>Tiến độ học</span>
                        <span>Đang cập nhật...</span>
                    </div>
                    <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-accent-green rounded-full w-0"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        </div>
        
        <?php else: ?>
        <!-- Empty state -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-16 text-center">
            <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-book-open text-3xl text-primary/50"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-700 mb-2">Chưa có khóa học nào</h2>
            <p class="text-slate-400 mb-8">Hãy khám phá và đăng ký khóa học đầu tiên của bạn!</p>
            <a href="../courses.php" class="px-8 py-3.5 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 inline-flex items-center gap-2">
                <i class="fas fa-search"></i> Khám phá khóa học
            </a>
        </div>
        <?php endif;
    } ?>
</div>

<?php include('./stuInclude/footer.php'); ?>