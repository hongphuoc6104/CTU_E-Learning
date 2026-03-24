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
<!-- Page Header Header -->
<div class="pt-32 pb-16 bg-gradient-to-br from-primary to-slate-900 border-b border-primary/20 relative overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.22),transparent_60%)]"></div>
    <div class="absolute inset-0 bg-primary/40"></div>
    <div class="max-w-7xl mx-auto px-6 relative z-10 text-center">
        <h1 class="text-4xl md:text-5xl font-black text-white mb-4">Danh Sách Khóa Học</h1>
        <p class="text-lg text-white/80 max-w-2xl mx-auto">Khám phá các khóa học thiết kế truyền thông đa phương tiện chất lượng từ CTU E-Learning. Nâng cao kỹ năng của bạn ngay hôm nay.</p>
    </div>
</div>

<!-- All Courses -->
<section class="py-20 px-6 bg-background-light min-h-screen">
    <div class="max-w-7xl mx-auto">
        
        <div class="grid md:grid-cols-3 lg:grid-cols-4 gap-8">
        <?php
            $courses = [];
            $courseIds = [];
            $sql = "SELECT * FROM course WHERE is_deleted = 0 AND course_status = 'published' ORDER BY published_at DESC, course_id DESC";
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
                        <a href="'.$detailLink.'" class="aspect-video relative overflow-hidden block shrink-0">
                            <img class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" src="'.$img_path.'"/>
                            <div class="absolute inset-0 bg-primary/20 bg-opacity-0 transition-opacity"></div>
                        </a>
                        ';
                        if($state['is_enrolled']){
                            echo '<div class="absolute top-4 right-4 px-3 py-1 bg-green-500/90 backdrop-blur rounded-full text-white text-[11px] font-bold shadow-sm z-10 flex items-center gap-1"><i class="fas fa-check-circle"></i> Đã sở hữu</div>';
                        } elseif(isset($_SESSION['is_login']) && $_SESSION['is_login'] && !$state['has_open_order']){
                            echo '<button onclick="addToCart('.$course_id.'); return false;" class="absolute top-4 right-4 p-2 bg-white/90 backdrop-blur rounded-full text-primary hover:bg-primary hover:text-white shadow-sm transition-colors cursor-pointer border-0 z-10 opacity-70 hover:opacity-100"><i class="fas fa-shopping-cart text-[15px] block w-[15px] h-[15px] flex items-center justify-center"></i></button>';
                        } elseif($state['has_open_order']) {
                            echo '<div class="absolute top-4 right-4 px-3 py-1 bg-amber-500/90 backdrop-blur rounded-full text-white text-[11px] font-bold shadow-sm z-10 flex items-center gap-1"><i class="fas fa-receipt"></i> Có đơn chờ</div>';
                        }
                        echo '
                        <div class="p-5 flex flex-col flex-grow">
                            <h3 class="text-base font-bold text-slate-900 mb-2 line-clamp-2 leading-snug">'.$courseNameSafe.'</h3>
                            <p class="text-xs text-slate-500 mb-4 line-clamp-2 leading-relaxed flex-grow">'.$courseDescSafe.'</p>
                            <div class="flex items-end justify-between mt-auto pt-4 border-t border-slate-50 gap-3">
                                <div>
                                    <p class="text-[11px] text-slate-400 line-through m-0">'.$original_price.' đ</p>
                                    <p class="text-lg font-black text-red-600 m-0 leading-none mt-1">'.$price.' đ</p>
                                </div>';

                                if($state['is_enrolled']){
                                    echo '<a href="Student/watchcourse.php?course_id='.$course_id.'" class="px-4 py-2 bg-green-50 text-green-600 text-xs font-bold rounded-lg hover:bg-green-500 hover:text-white transition-all no-underline shrink-0 flex items-center gap-1.5"><i class="fas fa-play"></i> Học ngay</a>';
                                } elseif($state['has_open_order']) {
                                    $openOrderLabel = $state['open_order_status'] === 'awaiting_verification' ? 'Theo dõi đơn' : 'Tiếp tục thanh toán';
                                    echo '<a href="Student/orderDetails.php?order_code='.rawurlencode((string) $state['open_order_code']).'" class="px-4 py-2 bg-amber-50 text-amber-700 text-xs font-bold rounded-lg hover:bg-amber-500 hover:text-white transition-all no-underline shrink-0 flex items-center gap-1.5"><i class="fas fa-receipt"></i> '.$openOrderLabel.'</a>';
                                } elseif(isset($_SESSION['is_login']) && $_SESSION['is_login']) {
                                    echo '<form action="checkout.php" method="post" class="m-0"><input type="hidden" name="csrf_token" value="'.htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8').'"><input type="hidden" name="course_id" value="'.$course_id.'"><input type="hidden" name="checkout_type" value="single"><button type="submit" class="px-4 py-2 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary/90 transition-all no-underline shrink-0 border-0 flex items-center gap-1.5"><i class="fas fa-credit-card"></i> Mua ngay</button></form>';
                                } else {
                                    echo '<a href="'.$loginLink.'" class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-bold rounded-lg hover:bg-primary hover:text-white transition-all no-underline shrink-0 flex items-center gap-1.5"><i class="fas fa-arrow-right"></i> Đăng nhập để mua</a>';
                                }
                                echo '
                            </div>
                        </div>
                    </div>
                ';
                }
            } else {
                echo '<div class="col-span-full text-center py-20"><p class="text-slate-500">Hiện chưa có khóa học nào.</p></div>';
            }
        ?> 
        </div>
    </div>
</section>

<?php 
  // Contact Us
  include('./contact.php'); 
?> 

<?php 
  // Footer Include from mainInclude 
  include('./mainInclude/footer.php'); 
?>  
