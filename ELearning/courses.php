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
<!-- Page Header -->
<div class="pt-24 sm:pt-32 pb-12 sm:pb-16 bg-gradient-to-br from-primary to-slate-900 border-b border-primary/20 relative overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.22),transparent_60%)]"></div>
    <div class="absolute inset-0 bg-primary/40"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 relative z-10 text-center">
        <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-white mb-3 sm:mb-4">Danh Sách Khóa Học</h1>
        <p class="text-base sm:text-lg text-white/80 max-w-2xl mx-auto">Khám phá các khóa học thiết kế truyền thông đa phương tiện chất lượng từ CTU E-Learning.</p>
    </div>
</div>

<!-- All Courses -->
<section class="py-12 sm:py-20 px-4 sm:px-6 bg-background-light min-h-screen">
    <div class="max-w-7xl mx-auto">

        <!-- Search, Filter & Sort Bar -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 sm:p-6 mb-8" id="courseFilterBar">
            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                <!-- Search -->
                <div class="flex-grow relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="courseSearch" placeholder="Tìm kiếm khóa học..." 
                           class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 transition-all">
                </div>
                <!-- Sort -->
                <select id="courseSort" class="px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 transition-all bg-white min-w-[160px]">
                    <option value="newest">Mới nhất</option>
                    <option value="price-asc">Giá tăng dần</option>
                    <option value="price-desc">Giá giảm dần</option>
                    <option value="name-asc">Tên A-Z</option>
                </select>
            </div>
            <div class="mt-3 flex items-center justify-between">
                <p class="text-xs text-slate-400" id="courseResultCount">Đang tải...</p>
                <button type="button" class="text-xs text-primary font-semibold hover:underline border-0 bg-transparent cursor-pointer" id="resetFilter" style="display:none;">
                    <i class="fas fa-times"></i> Xóa bộ lọc
                </button>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6 lg:gap-8" id="courseGrid">
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
                    <div class="course-card bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all border border-slate-100 group flex flex-col h-full relative"
                         data-name="'.strtolower($courseNameSafe).'"
                         data-price="'.(int)$row['course_price'].'"
                         data-date="'.htmlspecialchars($row['published_at'] ?? '', ENT_QUOTES, 'UTF-8').'">
                        <a href="'.$detailLink.'" class="aspect-video relative overflow-hidden block shrink-0">
                            <img class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" src="'.$img_path.'"/>
                            <div class="absolute inset-0 bg-primary/20 bg-opacity-0 transition-opacity"></div>
                        </a>
                        ';
                        if($state['is_enrolled']){
                            echo '<div class="absolute top-3 right-3 px-2.5 py-1 bg-green-500/90 backdrop-blur rounded-full text-white text-[10px] font-bold shadow-sm z-10 flex items-center gap-1"><i class="fas fa-check-circle"></i> Đã sở hữu</div>';
                        } elseif(isset($_SESSION['is_login']) && $_SESSION['is_login'] && !$state['has_open_order']){
                            echo '<button onclick="addToCart('.$course_id.'); return false;" class="absolute top-3 right-3 p-2 bg-white/90 backdrop-blur rounded-full text-primary hover:bg-primary hover:text-white shadow-sm transition-colors cursor-pointer border-0 z-10 opacity-70 hover:opacity-100"><i class="fas fa-shopping-cart text-[15px] block w-[15px] h-[15px] flex items-center justify-center"></i></button>';
                        } elseif($state['has_open_order']) {
                            echo '<div class="absolute top-3 right-3 px-2.5 py-1 bg-amber-500/90 backdrop-blur rounded-full text-white text-[10px] font-bold shadow-sm z-10 flex items-center gap-1"><i class="fas fa-receipt"></i> Có đơn chờ</div>';
                        }
                        echo '
                        <div class="p-4 sm:p-5 flex flex-col flex-grow">
                            <h3 class="text-sm sm:text-base font-bold text-slate-900 mb-2 line-clamp-2 leading-snug">'.$courseNameSafe.'</h3>
                            <p class="text-xs text-slate-500 mb-4 line-clamp-2 leading-relaxed flex-grow">'.$courseDescSafe.'</p>
                            <div class="flex items-end justify-between mt-auto pt-3 sm:pt-4 border-t border-slate-50 gap-2 sm:gap-3">
                                <div>
                                    <p class="text-[10px] sm:text-[11px] text-slate-400 line-through m-0">'.$original_price.' đ</p>
                                    <p class="text-base sm:text-lg font-black text-red-600 m-0 leading-none mt-1">'.$price.' đ</p>
                                </div>';

                                if($state['is_enrolled']){
                                    echo '<a href="Student/watchcourse.php?course_id='.$course_id.'" class="px-3 sm:px-4 py-2 bg-green-50 text-green-600 text-xs font-bold rounded-lg hover:bg-green-500 hover:text-white transition-all no-underline shrink-0 flex items-center gap-1.5"><i class="fas fa-play"></i> <span class="hidden sm:inline">Học ngay</span></a>';
                                } elseif($state['has_open_order']) {
                                    $openOrderLabel = $state['open_order_status'] === 'awaiting_verification' ? 'Theo dõi' : 'Thanh toán';
                                    echo '<a href="Student/orderDetails.php?order_code='.rawurlencode((string) $state['open_order_code']).'" class="px-3 sm:px-4 py-2 bg-amber-50 text-amber-700 text-xs font-bold rounded-lg hover:bg-amber-500 hover:text-white transition-all no-underline shrink-0 flex items-center gap-1.5"><i class="fas fa-receipt"></i> '.$openOrderLabel.'</a>';
                                } elseif(isset($_SESSION['is_login']) && $_SESSION['is_login']) {
                                    echo '<form action="checkout.php" method="post" class="m-0"><input type="hidden" name="csrf_token" value="'.htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8').'"><input type="hidden" name="course_id" value="'.$course_id.'"><input type="hidden" name="checkout_type" value="single"><button type="submit" class="px-3 sm:px-4 py-2 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary/90 transition-all no-underline shrink-0 border-0 flex items-center gap-1.5"><i class="fas fa-credit-card"></i> Mua ngay</button></form>';
                                } else {
                                    echo '<a href="'.$loginLink.'" class="px-3 sm:px-4 py-2 bg-slate-100 text-slate-700 text-xs font-bold rounded-lg hover:bg-primary hover:text-white transition-all no-underline shrink-0 flex items-center gap-1.5"><i class="fas fa-arrow-right"></i> <span class="hidden sm:inline">Đăng nhập</span></a>';
                                }
                                echo '
                            </div>
                        </div>
                    </div>
                ';
                }
            } else {
                echo '<div class="col-span-full text-center py-16">
                    <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-book-open text-3xl text-primary/50"></i>
                    </div>
                    <h2 class="text-xl font-bold text-slate-700 mb-2">Chưa có khóa học nào</h2>
                    <p class="text-slate-400 mb-8">Các khóa học sẽ sớm được cập nhật. Hãy quay lại sau nhé!</p>
                </div>';
            }
        ?> 
        </div>

        <!-- Empty state for search results -->
        <div class="col-span-full text-center py-16 hidden" id="emptySearchState">
            <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-search text-3xl text-primary/50"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-700 mb-2">Không tìm thấy khóa học</h2>
            <p class="text-slate-400 mb-6">Hãy thử từ khóa khác hoặc xóa bộ lọc.</p>
            <button type="button" class="px-6 py-2.5 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary/90 transition-all border-0 cursor-pointer" onclick="document.getElementById('courseSearch').value='';filterCourses();">
                <i class="fas fa-times"></i> Xóa bộ lọc
            </button>
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

<script>
(function() {
    const searchInput = document.getElementById('courseSearch');
    const sortSelect = document.getElementById('courseSort');
    const courseGrid = document.getElementById('courseGrid');
    const emptyState = document.getElementById('emptySearchState');
    const resultCount = document.getElementById('courseResultCount');
    const resetBtn = document.getElementById('resetFilter');
    const allCards = Array.from(document.querySelectorAll('.course-card'));
    const totalCourses = allCards.length;

    function filterCourses() {
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const sort = sortSelect ? sortSelect.value : 'newest';
        
        let visibleCards = allCards.filter(function(card) {
            const name = card.getAttribute('data-name') || '';
            return query === '' || name.indexOf(query) !== -1;
        });

        // Sort
        visibleCards.sort(function(a, b) {
            if (sort === 'price-asc') {
                return parseInt(a.getAttribute('data-price') || '0') - parseInt(b.getAttribute('data-price') || '0');
            }
            if (sort === 'price-desc') {
                return parseInt(b.getAttribute('data-price') || '0') - parseInt(a.getAttribute('data-price') || '0');
            }
            if (sort === 'name-asc') {
                return (a.getAttribute('data-name') || '').localeCompare(b.getAttribute('data-name') || '', 'vi');
            }
            return 0; // newest = default order
        });

        // Hide all first
        allCards.forEach(function(card) {
            card.style.display = 'none';
        });

        // Show filtered in order
        visibleCards.forEach(function(card) {
            card.style.display = '';
            courseGrid.appendChild(card);
        });

        // Update count
        if (resultCount) {
            resultCount.textContent = 'Hiển thị ' + visibleCards.length + ' / ' + totalCourses + ' khóa học';
        }

        // Toggle empty state
        if (emptyState) {
            emptyState.classList.toggle('hidden', visibleCards.length > 0);
        }

        // Toggle reset button
        if (resetBtn) {
            resetBtn.style.display = (query !== '' || sort !== 'newest') ? '' : 'none';
        }
    }

    if (searchInput) {
        let debounce = null;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounce);
            debounce = setTimeout(filterCourses, 200);
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', filterCourses);
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            if (sortSelect) sortSelect.value = 'newest';
            filterCourses();
        });
    }

    // Init
    filterCourses();

    window.filterCourses = filterCourses;
})();
</script>
