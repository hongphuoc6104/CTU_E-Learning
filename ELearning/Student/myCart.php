<?php
if(!isset($_SESSION)){ 
  session_start(); 
}
define('TITLE', 'Giỏ hàng');
define('PAGE', 'myCart');
include('./stuInclude/header.php'); 
include_once('../dbConnection.php');

if(!isset($_SESSION['is_login'])){
  echo "<script> location.href='../index.php'; </script>";
}
$stuEmail = $_SESSION['stuLogEmail'];
?>

<div class="max-w-4xl mx-auto px-6 py-12">
    <!-- Page title -->
    <div class="mb-8">
        <h1 class="text-3xl font-black text-slate-900 flex items-center gap-3">
            <i class="fas fa-shopping-cart text-primary"></i> Giỏ hàng của bạn
        </h1>
        <p class="text-slate-500 mt-2">Kiểm tra lại các khóa học trước khi thanh toán.</p>
    </div>

    <?php 
    $sql = "SELECT c.cart_id, course.course_id, course.course_name, course.course_price, course.course_original_price, course.course_img 
            FROM cart c 
            JOIN course ON c.course_id = course.course_id 
            WHERE c.stu_email = '$stuEmail'";
    $result = $conn->query($sql);
    $total = 0;
    
    if($result->num_rows > 0): ?>
    
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <!-- Cart items -->
        <div class="divide-y divide-slate-100">
        <?php while($row = $result->fetch_assoc()): 
            $total += $row['course_price'];
            $img_src = $row['course_img']; // path đã đúng relative từ Student/
        ?>
        <div class="flex items-center gap-5 p-6 hover:bg-slate-50/50 transition-colors group">
            <!-- Course Image -->
            <a href="../coursedetails.php?course_id=<?php echo $row['course_id']; ?>" class="shrink-0">
                <img src="<?php echo $img_src; ?>" 
                     class="w-24 h-16 rounded-xl object-cover shadow-sm" alt="Course">
            </a>
            <!-- Name and price -->
            <div class="flex-grow min-w-0">
                <a href="../coursedetails.php?course_id=<?php echo $row['course_id']; ?>" 
                   class="font-bold text-slate-900 hover:text-primary transition-colors line-clamp-2 text-sm md:text-base">
                    <?php echo htmlspecialchars($row['course_name']); ?>
                </a>
                <?php if(!empty($row['course_original_price'])): ?>
                <p class="text-xs text-slate-400 line-through mt-1"><?php echo number_format($row['course_original_price']); ?> đ</p>
                <?php endif; ?>
            </div>
            <!-- Price -->
            <p class="text-lg font-black text-red-600 shrink-0"><?php echo number_format($row['course_price']); ?> đ</p>
            <!-- Remove -->
            <button onclick="removeFromCart(<?php echo $row['cart_id']; ?>)" 
                    class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all border border-slate-100 hover:border-red-200"
                    title="Xóa khỏi giỏ">
                <i class="fas fa-trash-alt text-sm"></i>
            </button>
        </div>
        <?php endwhile; ?>
        </div>

        <!-- Summary -->
        <div class="bg-slate-50/80 p-6 border-t border-slate-100">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <p class="text-sm text-slate-500">Tổng thanh toán</p>
                    <p class="text-3xl font-black text-primary mt-1"><?php echo number_format($total); ?> <span class="text-xl">đ</span></p>
                </div>
                <div class="text-right">
                    <a href="../courses.php" class="text-sm text-slate-500 hover:text-primary transition-colors flex items-center gap-1 justify-end mb-3">
                        <i class="fas fa-plus text-xs"></i> Thêm khóa học
                    </a>
                    <form action="../checkout.php" method="post">
                        <input type="hidden" name="id" value="<?php echo $total; ?>">
                        <input type="hidden" name="checkout_type" value="cart">
                        <button type="submit" 
                                class="px-8 py-3.5 bg-primary text-white font-black rounded-xl hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                            <i class="fas fa-credit-card"></i> Thanh toán ngay
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Empty cart state -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-16 text-center">
        <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-shopping-cart text-3xl text-primary/50"></i>
        </div>
        <h2 class="text-xl font-bold text-slate-700 mb-2">Giỏ hàng đang trống</h2>
        <p class="text-slate-400 mb-8">Hãy thêm một vài khóa học bạn yêu thích vào giỏ nhé!</p>
        <a href="../courses.php" class="px-8 py-3.5 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 inline-flex items-center gap-2">
            <i class="fas fa-search"></i> Khám phá khóa học
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
function removeFromCart(cartId) {
    if(confirm('Bạn có chắc muốn xóa khóa học này khỏi giỏ hàng?')) {
        $.ajax({
            url: '../cart_api.php',
            method: 'POST',
            data: { action: 'remove', cart_id: cartId },
            success: function(resp) {
                if(resp.status == 'success') {
                    location.reload();
                } else {
                    alert(resp.msg);
                }
            }
        });
    }
}
</script>

<?php include('./stuInclude/footer.php'); ?>
