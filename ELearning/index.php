<?php
  require_once(__DIR__ . '/session_bootstrap.php');
  secure_session_start();
  include('./dbConnection.php');
  require_once('./commerce_helpers.php');
  // Header Include from mainInclude 
  include('./mainInclude/header.php'); 

  $stuEmail = isset($_SESSION['is_login'], $_SESSION['stuLogEmail']) ? (string) $_SESSION['stuLogEmail'] : '';
  $studentId = $stuEmail !== '' ? commerce_get_student_id($conn, $stuEmail) : null;
?>  
<!-- Hero Section -->
<section class="pt-40 pb-24 hero-pattern px-6">
<div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 items-center">
<div class="space-y-8">
<div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider mb-2">
<i class="fas fa-star text-xs"></i>
                    Nền tảng học tập số 1
                </div>
<h1 class="text-5xl lg:text-6xl font-black text-slate-900 leading-[1.1] tracking-tight mb-4">
                    Chào mừng đến với<br><span class="text-primary whitespace-nowrap">CTU E-Learning</span>
</h1>
<p class="text-xl text-slate-600 leading-relaxed max-w-xl mb-8 mt-4">
                    Nâng cao kỹ năng thiết kế và sản xuất truyền thông đa phương tiện cùng đội ngũ chuyên gia hàng đầu. Bắt đầu hành trình sáng tạo của bạn ngay hôm nay.
                </p>
<div class="flex flex-wrap gap-4 mt-8">
    <?php    
        if(!isset($_SESSION['is_login'])){
        echo '<a class="px-8 py-4 bg-primary text-white font-bold rounded-xl hover:translate-y-[-2px] transition-all shadow-xl shadow-primary/30 flex items-center gap-2 hover:text-white no-underline border-0" href="signup.php">Bắt đầu ngay <i class="fas fa-arrow-right"></i></a>';
        } else {
        echo '<a class="px-8 py-4 bg-primary text-white font-bold rounded-xl hover:translate-y-[-2px] transition-all shadow-xl shadow-primary/30 flex items-center gap-2 hover:text-white no-underline border-0" href="Student/studentProfile.php">Hồ sơ của tôi <i class="fas fa-arrow-right"></i></a>';
        }
    ?> 
    <a class="px-8 py-4 bg-white border-2 border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition-all no-underline flex items-center justify-center" href="courses.php">Khám phá khóa học</a>
</div>
</div>
<div class="relative">
<div class="aspect-video rounded-2xl overflow-hidden shadow-2xl relative z-10">
<img class="w-full h-full object-cover object-top" data-alt="Sinh viên đang học tập trên máy tính" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCkiDXFKNiFPG2GtWHRZjJtBy8o_Gh5igtzw9ajqBEaSv0fHviBHlELdrvYpRTS1RPxQEVLko6Kl94kQNQ3Q_6tJ6mj5u28D7SVnajqu_7N2aIpCzsZ3lKA1rME4XiUxnJdSa-rTsPtf8GTryMVSGl4HraOQpo7GxxMV73EgngIg-rzaybDGqBKOwdSQS-Tnj2RQRQH3A0Zh4tps3-lgeU9DjRFnmnRbNl8d5veYowwQMqYbYbTJvG7N0YWZB6FUTU1-YBhzZOa3FK0"/>
<div class="absolute inset-0 bg-gradient-to-t from-primary/40 to-transparent"></div>
</div>
<div class="absolute -top-6 -right-6 w-32 h-32 bg-accent-green/20 rounded-full blur-3xl -z-10"></div>
<div class="absolute -bottom-10 -left-10 w-48 h-48 bg-primary/10 rounded-full blur-3xl -z-10"></div>
</div>
</div>
</section>

<!-- Features Band -->
<section class="py-12 bg-white border-y border-slate-100">
<div class="max-w-7xl mx-auto px-6">
<div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
<div class="flex items-center gap-4">
<div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0">
<i class="fas fa-video text-3xl"></i>
</div>
<div>
<h3 class="font-bold text-slate-900 leading-none m-0">100+ Khoá học</h3>
<p class="text-xs text-slate-500 mt-1 mb-0">Học mọi lúc mọi nơi</p>
</div>
</div>
<div class="flex items-center gap-4">
<div class="w-12 h-12 rounded-xl bg-accent-green/10 flex items-center justify-center text-accent-green shrink-0">
<i class="fas fa-award text-3xl"></i>
</div>
<div>
<h3 class="font-bold text-slate-900 leading-none m-0">Giảng viên</h3>
<p class="text-xs text-slate-500 mt-1 mb-0">Chuyên gia đầu ngành</p>
</div>
</div>
<div class="flex items-center gap-4">
<div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0">
<i class="fas fa-infinity text-3xl"></i>
</div>
<div>
<h3 class="font-bold text-slate-900 leading-none m-0">Trọn đời</h3>
<p class="text-xs text-slate-500 mt-1 mb-0">Truy cập không giới hạn</p>
</div>
</div>
<div class="flex items-center gap-4">
<div class="w-12 h-12 rounded-xl bg-accent-green/10 flex items-center justify-center text-accent-green shrink-0">
<i class="fas fa-certificate text-3xl"></i>
</div>
<div>
<h3 class="font-bold text-slate-900 leading-none m-0">Chứng chỉ</h3>
<p class="text-xs text-slate-500 mt-1 mb-0">Cấp sau khi hoàn thành</p>
</div>
</div>
</div>
</div>
</section>

<!-- Popular Courses -->
<section class="py-24 px-6 bg-background-light">
<div class="max-w-7xl mx-auto">
<div class="flex justify-between items-end mb-12">
<div>
<h2 class="text-3xl font-black text-slate-900 m-0">Khoá học nổi bật</h2>
<div class="h-1.5 w-20 bg-primary mt-4 rounded-full"></div>
</div>
<a class="text-primary font-bold hover:underline flex items-center gap-2" href="courses.php">
                    Xem tất cả <i class="fas fa-chevron-right"></i>
</a>
</div>

<div class="grid md:grid-cols-3 gap-8 mb-8">
<?php
$courses = [];
$courseIds = [];
$sql = "SELECT * FROM course WHERE is_deleted = 0 AND course_status = 'published' ORDER BY published_at DESC, course_id DESC LIMIT 6";
$result = $conn->query($sql);
if($result && $result->num_rows > 0){
  while($row = $result->fetch_assoc()){
    $courses[] = $row;
    $courseIds[] = (int) $row['course_id'];
  }
}

$courseStates = commerce_fetch_course_states($conn, $studentId, $courseIds);

if(!empty($courses)){
  foreach($courses as $row){
    $course_id = (int) $row['course_id'];
    $state = $courseStates[$course_id] ?? ['is_enrolled' => false, 'has_open_order' => false, 'open_order_code' => null, 'open_order_status' => null];
    $img_path = ltrim(str_replace('../', '', $row['course_img']), '/');
    $price = number_format((int) $row['course_price']);
    $original_price = number_format((int) $row['course_original_price']);
    $courseNameSafe = htmlspecialchars($row['course_name'], ENT_QUOTES, 'UTF-8');
    $courseDescSafe = htmlspecialchars($row['course_desc'], ENT_QUOTES, 'UTF-8');
    $detailLink = 'coursedetails.php?course_id=' . $course_id;
    $loginLink = 'login.php?redirect=' . rawurlencode($detailLink);

    echo '
        <div class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all border border-slate-100 group flex flex-col h-full relative">
            <a href="'.$detailLink.'" class="aspect-video relative overflow-hidden block">
                <img class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" src="'.$img_path.'"/>
                <div class="absolute inset-0 bg-primary/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            </a>
            ';
            if($state['is_enrolled']){
                echo '<div class="absolute top-4 right-4 px-3 py-1 bg-green-500/90 backdrop-blur rounded-full text-white text-[11px] font-bold shadow-sm z-10 flex items-center gap-1"><i class="fas fa-check-circle"></i> Đã sở hữu</div>';
            } elseif(isset($_SESSION['is_login']) && $_SESSION['is_login'] && !$state['has_open_order']) {
                echo '<button onclick="addToCart('.$course_id.'); return false;" class="absolute top-4 right-4 p-2 bg-white/90 backdrop-blur rounded-full text-primary hover:bg-primary hover:text-white shadow-sm transition-colors cursor-pointer border-0 z-10"><i class="fas fa-shopping-cart text-lg block"></i></button>';
            } elseif($state['has_open_order']) {
                echo '<div class="absolute top-4 right-4 px-3 py-1 bg-amber-500/90 backdrop-blur rounded-full text-white text-[11px] font-bold shadow-sm z-10 flex items-center gap-1"><i class="fas fa-receipt"></i> Có đơn chờ</div>';
            }
            echo '
            <div class="p-6 flex flex-col flex-grow">
                <h3 class="text-lg font-bold text-slate-900 mb-2 line-clamp-2">'.$courseNameSafe.'</h3>
                <p class="text-sm text-slate-600 mb-6 line-clamp-2 leading-relaxed flex-grow">'.$courseDescSafe.'</p>
                <div class="flex items-center justify-between mt-auto pt-4 border-t border-slate-50 gap-3">
                    <div>
                        <p class="text-xs text-slate-400 line-through m-0">'.$original_price.' đ</p>
                        <p class="text-xl font-black text-red-600 m-0 leading-none mt-1">'.$price.' đ</p>
                    </div>';

                    if($state['is_enrolled']){
                        echo '<a href="Student/watchcourse.php?course_id='.$course_id.'" class="px-5 py-2.5 bg-green-50 text-green-600 text-sm font-bold rounded-lg hover:bg-green-500 hover:text-white transition-all no-underline shrink-0 flex items-center gap-1.5"><i class="fas fa-play"></i> Học ngay</a>';
                    } elseif($state['has_open_order']) {
                        $openOrderLabel = $state['open_order_status'] === 'awaiting_verification' ? 'Theo dõi đơn' : 'Tiếp tục thanh toán';
                        echo '<a href="Student/orderDetails.php?order_code='.rawurlencode((string) $state['open_order_code']).'" class="px-5 py-2.5 bg-amber-50 text-amber-700 text-sm font-bold rounded-lg hover:bg-amber-500 hover:text-white transition-all no-underline shrink-0 flex items-center gap-1.5"><i class="fas fa-receipt"></i> '.$openOrderLabel.'</a>';
                    } elseif(isset($_SESSION['is_login']) && $_SESSION['is_login']) {
                        echo '<form action="checkout.php" method="post" class="m-0"><input type="hidden" name="csrf_token" value="'.htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8').'"><input type="hidden" name="course_id" value="'.$course_id.'"><input type="hidden" name="checkout_type" value="single"><button type="submit" class="px-5 py-2.5 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary/90 transition-all no-underline shrink-0 border-0 flex items-center gap-1.5"><i class="fas fa-credit-card"></i> Mua ngay</button></form>';
                    } else {
                        echo '<a href="'.$loginLink.'" class="px-5 py-2.5 bg-slate-100 text-slate-700 text-sm font-bold rounded-lg hover:bg-primary hover:text-white transition-all no-underline shrink-0 flex items-center gap-1.5"><i class="fas fa-arrow-right"></i> Đăng nhập để mua</a>';
                    }

                    echo '
                </div>
            </div>
        </div>
    ';
  }
} else {
  echo '<div class="col-span-full text-center py-16"><p class="text-slate-500">Hiện chưa có khóa học nổi bật.</p></div>';
}
?>
</div>
<div class="text-center mt-12">
    <a class="inline-flex items-center gap-2 px-8 py-3.5 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all hover:text-white no-underline border-0 shadow-lg shadow-primary/20" href="courses.php">Xem tất cả khoá học <i class="fas fa-arrow-right"></i></a> 
</div>
</div>
</section>

<!-- Student Feedback -->
<section class="py-24 px-6 bg-white overflow-hidden" id="Feedback">
<div class="max-w-7xl mx-auto">
<div class="text-center mb-16">
<h2 class="text-3xl font-black text-slate-900 m-0">Góp ý của học viên</h2>
<p class="text-slate-500 mt-4 text-lg">Chia sẻ từ những học viên đã thay đổi sự nghiệp cùng CTU E-Learning</p>
</div>
<div class="grid md:grid-cols-3 gap-8 gap-y-16 relative">
<?php 
  $sql = "SELECT s.stu_name, s.stu_occ, s.stu_img, f.f_content FROM student AS s JOIN feedback AS f ON s.stu_id = f.stu_id WHERE f.is_deleted=0 AND s.is_deleted=0 LIMIT 6";
  $result = $conn->query($sql);
  if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
      $n_img = str_replace('../','',$row['stu_img']);
      if(empty($n_img) || !file_exists($n_img)) { $n_img = 'image/stu/student.png'; } // Fallback
?>
    <div class="bg-slate-50 p-8 rounded-3xl relative mt-4 shadow-sm border border-slate-100/50">
        <div class="absolute -top-10 left-8 w-20 h-20 rounded-full overflow-hidden border-4 border-white shadow-lg bg-white flex items-center justify-center">
            <img class="w-full h-full object-cover" src="<?php echo $n_img; ?>" onerror="this.onerror=null;this.src='image/stu/student1.jpg'"/>
        </div>
        <div class="pt-6">
            <i class="fas fa-quote-right text-4xl text-slate-200 absolute top-6 right-6"></i>
            <p class="text-slate-600 italic leading-relaxed relative z-10 text-[15px]">
                "<?php echo htmlspecialchars($row['f_content'], ENT_QUOTES, 'UTF-8');?>"
            </p>
            <div class="mt-8 pt-6 border-t border-slate-200 border-dashed">
                <p class="font-bold text-slate-900 text-lg m-0"><?php echo htmlspecialchars($row['stu_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="text-sm text-primary font-semibold m-0 mt-1"><?php echo htmlspecialchars($row['stu_occ'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
    </div>
<?php }} else { ?>
    <div class="md:col-span-3 text-center py-12">
        <p class="text-slate-500">Chưa có góp ý nào để hiển thị.</p>
    </div>
<?php } ?>
</div>
</div>
</section>

  <?php 
    // Footer Include from mainInclude 
    include('./mainInclude/footer.php'); 
    
  ?>  
