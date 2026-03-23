<?php
  include('./dbConnection.php');
  // Header Include from mainInclude 
  include('./mainInclude/header.php'); 
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
            $sql = "SELECT * FROM course WHERE is_deleted=0";
            $result = $conn->query($sql);
            if($result && $result->num_rows > 0){ 
                while($row = $result->fetch_assoc()){
                $course_id = $row['course_id'];
                $img_path = ltrim(str_replace('../', '', $row['course_img']), '/');
                $price = number_format($row['course_price']);
                $original_price = number_format($row['course_original_price']);
                $courseNameSafe = htmlspecialchars($row['course_name'], ENT_QUOTES, 'UTF-8');
                $courseDescSafe = htmlspecialchars($row['course_desc'], ENT_QUOTES, 'UTF-8');
                
                // Check if already purchased
                $isPurchased = false;
                if(isset($_SESSION['is_login']) && $_SESSION['is_login']){
                    $stuEmail = $_SESSION['stuLogEmail'];
                    $checkStmt = $conn->prepare("SELECT 1 FROM courseorder WHERE stu_email = ? AND course_id = ? AND status = 'TXN_SUCCESS' AND is_deleted = 0 LIMIT 1");
                    if($checkStmt) {
                        $checkStmt->bind_param('si', $stuEmail, $course_id);
                        $checkStmt->execute();
                        $checkRes = $checkStmt->get_result();
                        if($checkRes && $checkRes->num_rows > 0){
                            $isPurchased = true;
                        }
                        $checkStmt->close();
                    }
                }

                echo '
                    <div class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all border border-slate-100 group flex flex-col h-full relative">
                        <a href="coursedetails.php?course_id='.$course_id.'" class="aspect-video relative overflow-hidden block shrink-0">
                            <img class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" src="'.$img_path.'"/>
                            <div class="absolute inset-0 bg-primary/20 bg-opacity-0 transition-opacity"></div>
                        </a>
                        ';
                        if(!$isPurchased){
                            echo '<button onclick="addToCart('.$course_id.'); return false;" class="absolute top-4 right-4 p-2 bg-white/90 backdrop-blur rounded-full text-primary hover:bg-primary hover:text-white shadow-sm transition-colors cursor-pointer border-0 z-10 opacity-70 hover:opacity-100">
                                <i class="fas fa-shopping-cart text-[15px] block w-[15px] h-[15px] flex items-center justify-center"></i>
                            </button>';
                        } else {
                            echo '<div class="absolute top-4 right-4 px-3 py-1 bg-green-500/90 backdrop-blur rounded-full text-white text-[11px] font-bold shadow-sm z-10 flex items-center gap-1">
                                <i class="fas fa-check-circle"></i> Đã sở hữu
                            </div>';
                        }
                        echo '
                        <div class="p-5 flex flex-col flex-grow">
                            <h3 class="text-base font-bold text-slate-900 mb-2 line-clamp-2 leading-snug">'.$courseNameSafe.'</h3>
                            <p class="text-xs text-slate-500 mb-4 line-clamp-2 leading-relaxed flex-grow">'.$courseDescSafe.'</p>
                            <div class="flex items-end justify-between mt-auto pt-4 border-t border-slate-50">
                                <div>
                                    <p class="text-[11px] text-slate-400 line-through m-0">'.$original_price.' đ</p>
                                    <p class="text-lg font-black text-red-600 m-0 leading-none mt-1">'.$price.' đ</p>
                                </div>
                                ';
                                if($isPurchased){
                                    echo '<a href="Student/watchcourse.php?course_id='.$course_id.'" class="px-4 py-2 bg-green-50 text-green-600 text-xs font-bold rounded-lg hover:bg-green-500 hover:text-white transition-all no-underline shrink-0 flex items-center gap-1.5">
                                        <i class="fas fa-play"></i> Học ngay
                                    </a>';
                                } else {
                                    echo '<a href="coursedetails.php?course_id='.$course_id.'" class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-bold rounded-lg hover:bg-primary hover:text-white transition-all no-underline shrink-0">
                                        Chi tiết
                                    </a>';
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
