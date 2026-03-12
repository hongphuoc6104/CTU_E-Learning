<?php
  include('./dbConnection.php');
  // Header Include from mainInclude 
  include('./mainInclude/header.php'); 
?>  
<!-- Page Header Header -->
<div class="pt-32 pb-16 bg-gradient-to-br from-primary to-slate-900 border-b border-primary/20 relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('image/coursebanner.jpg')] bg-cover bg-center opacity-20 mix-blend-overlay"></div>
    <div class="absolute inset-0 bg-primary/40"></div>
    <div class="max-w-7xl mx-auto px-6 relative z-10">
        <a href="courses.php" class="inline-flex items-center gap-2 text-white/80 hover:text-white mb-6 transition-colors font-medium">
            <i class="fas fa-arrow-left"></i> Quay lại Danh sách Khoá học
        </a>
        <h1 class="text-3xl md:text-5xl font-black text-white mb-4">Chi Tiết Khóa Học</h1>
    </div>
</div>

<section class="py-16 px-6 bg-background-light min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Course Details -->
        <?php
          if(isset($_GET['course_id'])){
           $course_id = $_GET['course_id'];
           $_SESSION['course_id'] = $course_id;
           $sql = "SELECT * FROM course WHERE course_id = '$course_id'";
          $result = $conn->query($sql);
          if($result->num_rows > 0){ 
            while($row = $result->fetch_assoc()){
                $img_path = str_replace('..', '.', $row['course_img']);
                $price = number_format($row['course_price']);
                $original_price = number_format($row['course_original_price']);
              echo ' 
                <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-slate-100 flex flex-col md:flex-row mb-16">
                    <div class="md:w-5/12 shrink-0">
                        <img src="'.$img_path.'" class="w-full h-full object-cover min-h-[300px]" alt="Course Image" />
                    </div>
                    <div class="md:w-7/12 p-8 md:p-12 flex flex-col justify-center">
                        <h2 class="text-3xl font-black text-slate-900 mb-4">'.$row['course_name'].'</h2>
                        <p class="text-slate-600 mb-6 leading-relaxed">'.$row['course_desc'].'</p>
                        
                        <div class="flex items-center gap-2 mb-8 text-slate-700 font-medium bg-slate-50 px-4 py-3 rounded-xl inline-flex w-fit">
                            <i class="fas fa-clock text-primary"></i> 
                            <span>Thời lượng: '.$row['course_duration'].'</span>
                        </div>
                        
                        <div class="flex items-end gap-4 border-t border-slate-100 pt-8 mb-8">
                            <p class="text-4xl font-black text-red-600 m-0 leading-none">'.$price.' đ</p>
                            <p class="text-lg text-slate-400 line-through m-0 font-medium">'.$original_price.' đ</p>
                        </div>
                        
                        <div class="flex flex-wrap gap-4 mt-auto">
                            <button type="button" class="px-6 py-3.5 bg-white border-2 border-primary text-primary font-bold rounded-xl hover:bg-primary/5 transition-colors flex items-center gap-2" onclick="addToCart('.$course_id.')">
                                <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                            </button>
                            <form action="checkout.php" method="post" class="m-0 flex-grow max-w-[200px]">
                              <input type="hidden" name="id" value="'.$row['course_price'].'"> 
                              <input type="hidden" name="checkout_type" value="single">
                              <button type="submit" class="w-full h-full px-6 py-3.5 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex items-center justify-center gap-2" name="buy">
                                  Đăng ký ngay <i class="fas fa-arrow-right"></i>
                              </button>
                            </form>
                        </div>
                    </div>
                </div>';
            }
          }
         }
        ?>   

        <!-- Lesson List -->
        <div class="bg-white rounded-3xl p-8 md:p-12 shadow-sm border border-slate-100">
            <h3 class="text-2xl font-black text-slate-900 mb-8 flex items-center gap-3">
                <i class="fas fa-list-ul text-primary"></i> Lộ trình bài học
            </h3>
            
            <div class="overflow-x-auto">
                <?php 
                    $sql = "SELECT * FROM lesson";
                    $result = $conn->query($sql);
                    if($result->num_rows > 0){
                        echo '
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b-2 border-slate-100">
                                    <th class="py-4 px-6 font-bold text-slate-700 w-24">Bài số</th>
                                    <th class="py-4 px-6 font-bold text-slate-700">Tên bài học</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">';
                        
                        $num = 0;
                        while($row = $result->fetch_assoc()){
                            if($row['course_id'] == $course_id) {
                                $num++;
                                echo ' 
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-4 px-6 font-semibold text-primary">'.$num.'</td>
                                    <td class="py-4 px-6 text-slate-700 font-medium">'. $row["lesson_name"].'</td>
                                </tr>';
                            }
                        }
                        
                        echo '
                            </tbody>
                        </table>';
                    } else {
                        echo '<p class="text-slate-500 text-center py-8">Chưa có bài học nào được tải lên.</p>';
                    }
                ?>         
            </div>
        </div>
    </div>  
</section>  
     <?php 
  // Footer Include from mainInclude 
  include('./mainInclude/footer.php'); 
?>  
