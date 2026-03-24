<?php
  require_once(__DIR__ . '/session_bootstrap.php');
  secure_session_start();
  include('./dbConnection.php');
  require_once('./commerce_helpers.php');
  $course_id = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

  if ($course_id <= 0) {
      header('Location: courses.php');
      exit;
  }

  $courseExists = false;
  $checkCourseStmt = $conn->prepare("SELECT 1 FROM course WHERE course_id = ? AND is_deleted = 0 AND course_status = 'published' LIMIT 1");
  if ($checkCourseStmt) {
      $checkCourseStmt->bind_param('i', $course_id);
      $checkCourseStmt->execute();
      $checkCourseStmt->store_result();
      $courseExists = $checkCourseStmt->num_rows > 0;
      $checkCourseStmt->close();
  }

  if (!$courseExists) {
      header('Location: courses.php');
      exit;
  }

  $stuEmail = isset($_SESSION['is_login'], $_SESSION['stuLogEmail']) ? (string) $_SESSION['stuLogEmail'] : '';
  $studentId = $stuEmail !== '' ? commerce_get_student_id($conn, $stuEmail) : null;
  $courseState = commerce_fetch_course_states($conn, $studentId, [$course_id]);
  $courseState = $courseState[$course_id] ?? ['is_enrolled' => false, 'has_open_order' => false, 'open_order_code' => null, 'open_order_status' => null];

  // Header Include from mainInclude 
  include('./mainInclude/header.php'); 
?>  
<!-- Page Header Header -->
<div class="pt-24 sm:pt-32 pb-12 sm:pb-16 bg-gradient-to-br from-primary to-slate-900 border-b border-primary/20 relative overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.22),transparent_60%)]"></div>
    <div class="absolute inset-0 bg-primary/40"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 relative z-10">
        <a href="courses.php" class="inline-flex items-center gap-2 text-white/80 hover:text-white mb-4 sm:mb-6 transition-colors font-medium text-sm">
            <i class="fas fa-arrow-left"></i> Quay lại Danh sách Khoá học
        </a>
        <h1 class="text-2xl sm:text-3xl md:text-5xl font-black text-white mb-3 sm:mb-4">Chi Tiết Khóa Học</h1>
    </div>
</div>

<section class="py-10 sm:py-16 px-4 sm:px-6 bg-background-light min-h-screen pb-24 md:pb-16">
    <div class="max-w-7xl mx-auto">
        <?php $commerceFlash = commerce_pull_flash(); ?>
        <?php if($commerceFlash): ?>
        <div class="mb-6 flex items-center gap-3 rounded-2xl border px-5 py-4 text-sm font-semibold <?php echo ($commerceFlash['type'] ?? '') === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
            <i class="fas <?php echo ($commerceFlash['type'] ?? '') === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <span><?php echo htmlspecialchars((string) ($commerceFlash['text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <?php endif; ?>
        <!-- Course Details -->
        <?php
          $stmtCourse = $conn->prepare("SELECT * FROM course WHERE course_id = ? AND is_deleted = 0 AND course_status = 'published' LIMIT 1");
          if($stmtCourse) {
            $stmtCourse->bind_param('i', $course_id);
            $stmtCourse->execute();
            $result = $stmtCourse->get_result();
            if($result && $result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $img_path = ltrim(str_replace('../', '', $row['course_img']), '/');
                $price = number_format($row['course_price']);
                $original_price = number_format($row['course_original_price']);
                $courseNameSafe = htmlspecialchars($row['course_name'], ENT_QUOTES, 'UTF-8');
                $courseDescSafe = htmlspecialchars($row['course_desc'], ENT_QUOTES, 'UTF-8');
                $courseDurationSafe = htmlspecialchars($row['course_duration'], ENT_QUOTES, 'UTF-8');
              echo ' 
                <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-slate-100 flex flex-col md:flex-row mb-16">
                    <div class="md:w-5/12 shrink-0">
                        <img src="'.$img_path.'" class="w-full h-full object-cover min-h-[300px]" alt="Course Image" />
                    </div>
                    <div class="md:w-7/12 p-8 md:p-12 flex flex-col justify-center">
                        <h2 class="text-3xl font-black text-slate-900 mb-4">'.$courseNameSafe.'</h2>
                        <p class="text-slate-600 mb-6 leading-relaxed">'.$courseDescSafe.'</p>
                        
                        <div class="flex items-center gap-2 mb-8 text-slate-700 font-medium bg-slate-50 px-4 py-3 rounded-xl inline-flex w-fit">
                            <i class="fas fa-clock text-primary"></i> 
                            <span>Thời lượng: '.$courseDurationSafe.'</span>
                        </div>
                        
                        <!-- Actions & Price -->
                        <div class="flex items-end gap-4 border-t border-slate-100 pt-8 mb-8">
                            <p class="text-4xl font-black text-red-600 m-0 leading-none">'.$price.' đ</p>
                            <p class="text-lg text-slate-400 line-through m-0 font-medium">'.$original_price.' đ</p>
                        </div>
                        
                        <div class="flex flex-wrap gap-4 mt-auto">
                            ';
                            if($courseState['is_enrolled']){
                                echo '
                                <a href="Student/watchcourse.php?course_id='.$course_id.'" class="w-full md:w-auto px-8 py-3.5 bg-green-500 text-white font-bold rounded-xl hover:bg-green-600 transition-all shadow-lg shadow-green-500/20 flex items-center justify-center gap-2 No-underline">
                                    <i class="fas fa-play-circle text-lg"></i> Học ngay
                                </a>
                                <div class="flex items-center text-green-600 font-semibold gap-2 ml-4">
                                    <i class="fas fa-check-circle"></i> Đã sở hữu khoá học
                                </div>
                                ';
                            } elseif($courseState['has_open_order']) {
                                $openOrderLabel = $courseState['open_order_status'] === 'awaiting_verification' ? 'Theo dõi đơn hàng' : 'Tiếp tục thanh toán';
                                echo '
                                <a href="Student/orderDetails.php?order_code='.rawurlencode((string) $courseState['open_order_code']).'" class="w-full md:w-auto px-8 py-3.5 bg-amber-500 text-white font-bold rounded-xl hover:bg-amber-600 transition-all shadow-lg shadow-amber-500/20 flex items-center justify-center gap-2 No-underline">
                                    <i class="fas fa-receipt text-lg"></i> '.$openOrderLabel.'
                                </a>
                                <div class="flex items-center text-amber-700 font-semibold gap-2 ml-4">
                                    <i class="fas fa-info-circle"></i> Khóa học này đang có đơn hàng chưa hoàn tất
                                </div>
                                ';
                            } elseif(!isset($_SESSION['is_login']) || !$_SESSION['is_login']) {
                                $loginLink = 'login.php?redirect=' . rawurlencode('coursedetails.php?course_id=' . $course_id);
                                $signupLink = 'signup.php?redirect=' . rawurlencode('coursedetails.php?course_id=' . $course_id);
                                echo '
                                <a href="'.$loginLink.'" class="w-full md:w-auto px-8 py-3.5 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex items-center justify-center gap-2 No-underline">
                                    <i class="fas fa-sign-in-alt text-lg"></i> Đăng nhập để mua
                                </a>
                                <a href="'.$signupLink.'" class="w-full md:w-auto px-8 py-3.5 bg-white border-2 border-primary text-primary font-bold rounded-xl hover:bg-primary/5 transition-colors flex items-center justify-center gap-2 No-underline">
                                    <i class="fas fa-user-plus text-lg"></i> Tạo tài khoản
                                </a>
                                ';
                            } else {
                                echo '
                                <button type="button" class="px-6 py-3.5 bg-white border-2 border-primary text-primary font-bold rounded-xl hover:bg-primary/5 transition-colors flex items-center gap-2" onclick="addToCart('.$course_id.')">
                                    <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                </button>
                                <form action="checkout.php" method="post" class="m-0 flex-grow max-w-[200px]">
                                  <input type="hidden" name="csrf_token" value="'.htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8').'"> 
                                  <input type="hidden" name="course_id" value="'.$course_id.'"> 
                                  <input type="hidden" name="checkout_type" value="single">
                                  <button type="submit" class="w-full h-full px-6 py-3.5 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex items-center justify-center gap-2" name="buy">
                                      Đăng ký ngay <i class="fas fa-arrow-right"></i>
                                  </button>
                                </form>
                                ';
                            }
                            
                            echo '
                        </div>
                    </div>
                </div>';
            }
          }
          $stmtCourse->close();
          }

        ?>   

        <!-- Lesson List (Accordion) -->
        <div class="bg-white rounded-2xl sm:rounded-3xl p-5 sm:p-8 md:p-12 shadow-sm border border-slate-100">
            <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-6 sm:mb-8 flex items-center gap-3">
                <i class="fas fa-list-ul text-primary"></i> Lộ trình bài học
            </h3>
            
            <div class="space-y-2" id="lessonAccordion">
                <?php 
                    $lessonStmt = $conn->prepare('SELECT lesson_name FROM lesson WHERE course_id = ? AND is_deleted = 0 ORDER BY lesson_id');
                    if($lessonStmt) {
                        $lessonStmt->bind_param('i', $course_id);
                        $lessonStmt->execute();
                        $result = $lessonStmt->get_result();
                    } else {
                        $result = false;
                    }
                    if($result && $result->num_rows > 0){
                        $num = 0;
                        $totalLessons = $result->num_rows;
                        while($row = $result->fetch_assoc()){
                            $num++;
                            $lessonNameSafe = htmlspecialchars($row['lesson_name'], ENT_QUOTES, 'UTF-8');
                            echo '
                            <div class="border border-slate-100 rounded-xl hover:border-slate-200 transition-colors">
                                <div class="flex items-center gap-3 sm:gap-4 px-4 sm:px-5 py-3 sm:py-4">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary font-bold text-sm shrink-0">
                                        '.$num.'
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <p class="text-sm sm:text-base font-semibold text-slate-800 truncate">'.$lessonNameSafe.'</p>
                                    </div>
                                    <i class="fas fa-check-circle text-slate-200 shrink-0"></i>
                                </div>
                            </div>';
                        }
                        echo '<div class="mt-4 text-sm text-slate-500 text-center">
                            <i class="fas fa-book-open mr-1"></i> Tổng cộng '.$totalLessons.' bài học
                        </div>';
                    } else {
                        echo '<div class="text-center py-10">
                            <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-inbox text-2xl text-primary/50"></i>
                            </div>
                            <p class="text-slate-500 font-medium">Chưa có bài học nào được tải lên.</p>
                            <p class="text-xs text-slate-400 mt-1">Nội dung sẽ sớm được cập nhật.</p>
                        </div>';
                    }
                    if($lessonStmt) {
                        $lessonStmt->close();
                    }
                ?>         
            </div>
        </div>

        <!-- Sticky CTA on mobile -->
        <?php
        // Rebuild state for sticky bar
        if(!$courseState['is_enrolled'] && !$courseState['has_open_order'] && isset($_SESSION['is_login']) && $_SESSION['is_login']):
        ?>
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] px-4 py-3 z-40 md:hidden">
            <div class="flex items-center justify-between gap-3 max-w-7xl mx-auto">
                <div>
                    <p class="text-xs text-slate-400 line-through"><?php echo $original_price; ?> đ</p>
                    <p class="text-lg font-black text-red-600 leading-none"><?php echo $price; ?> đ</p>
                </div>
                <form action="checkout.php" method="post" class="m-0">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                    <input type="hidden" name="checkout_type" value="single">
                    <button type="submit" class="px-6 py-3 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex items-center gap-2 border-0 text-sm">
                        Đăng ký ngay <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>  
</section>  
     <?php 
  // Footer Include from mainInclude 
  include('./mainInclude/footer.php'); 
?>  
