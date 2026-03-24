<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../commerce_helpers.php');

define('TITLE', 'Giỏ hàng');
define('PAGE', 'myCart');
include('./stuInclude/header.php'); 
include_once('../dbConnection.php');

if(!isset($_SESSION['is_login'])){
  echo "<script> location.href='../index.php'; </script>";
}
$stuEmail = $_SESSION['stuLogEmail'];
$studentId = commerce_get_student_id($conn, $stuEmail);
if ($studentId === null) {
    commerce_set_flash('error', 'Không tìm thấy thông tin học viên để tải giỏ hàng.');
    echo "<script> location.href='../index.php'; </script>";
    exit;
}
commerce_cleanup_cart($conn, $stuEmail);
$commerceFlash = commerce_pull_flash();
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
    <!-- Page title -->
    <div class="mb-6 sm:mb-8">
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 flex items-center gap-3">
            <i class="fas fa-shopping-cart text-primary"></i> Giỏ hàng của bạn
        </h1>
        <p class="text-sm sm:text-base text-slate-500 mt-2">Kiểm tra lại các khóa học trước khi thanh toán.</p>
    </div>

    <?php if($commerceFlash): ?>
    <div class="mb-6 flex items-center gap-3 rounded-2xl border px-5 py-4 text-sm font-semibold <?php echo ($commerceFlash['type'] ?? '') === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
        <i class="fas <?php echo ($commerceFlash['type'] ?? '') === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <span><?php echo htmlspecialchars((string) ($commerceFlash['text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <?php endif; ?>

    <?php 
    $stmtCart = $conn->prepare(
        'SELECT c.cart_id, course.course_id, course.course_name, course.course_price, course.course_original_price, course.course_img '
        . 'FROM cart c '
        . 'JOIN course ON c.course_id = course.course_id '
        . 'LEFT JOIN enrollment e ON e.student_id = ? AND e.course_id = course.course_id AND e.enrollment_status = ? '
        . 'WHERE c.stu_email = ? AND c.is_deleted = 0 AND course.is_deleted = 0 AND course.course_status = ? AND e.enrollment_id IS NULL '
        . 'ORDER BY c.cart_id ASC'
    );
    $result = false;
    if($stmtCart) {
        $activeStatus = 'active';
        $publishedStatus = 'published';
        $stmtCart->bind_param('isss', $studentId, $activeStatus, $stuEmail, $publishedStatus);
        $stmtCart->execute();
        $result = $stmtCart->get_result();
    }
    $total = 0;
    
    if($result && $result->num_rows > 0): ?>
    <div class="grid md:grid-cols-[1fr_320px] gap-6">
    <div class="bg-white rounded-2xl sm:rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <!-- Cart items -->
        <div class="divide-y divide-slate-100">
        <?php while($row = $result->fetch_assoc()): 
            $total += $row['course_price'];
            $img_src = "../" . ltrim(str_replace('../', '', $row['course_img']), '/');
        ?>
        <div class="flex items-center gap-3 sm:gap-5 p-4 sm:p-6 hover:bg-slate-50/50 transition-colors group">
            <!-- Course Image -->
            <a href="../coursedetails.php?course_id=<?php echo $row['course_id']; ?>" class="shrink-0">
                <img src="<?php echo $img_src; ?>" 
                     class="w-16 h-12 sm:w-24 sm:h-16 rounded-lg sm:rounded-xl object-cover shadow-sm" alt="Course">
            </a>
            <!-- Name and price -->
            <div class="flex-grow min-w-0">
                <a href="../coursedetails.php?course_id=<?php echo $row['course_id']; ?>" 
                   class="font-bold text-slate-900 hover:text-primary transition-colors line-clamp-2 text-xs sm:text-sm md:text-base">
                    <?php echo htmlspecialchars($row['course_name']); ?>
                </a>
                <?php if(!empty($row['course_original_price'])): ?>
                <p class="text-[10px] sm:text-xs text-slate-400 line-through mt-1"><?php echo number_format($row['course_original_price']); ?> đ</p>
                <?php endif; ?>
            </div>
            <!-- Price -->
            <p class="text-sm sm:text-lg font-black text-red-600 shrink-0"><?php echo number_format($row['course_price']); ?> đ</p>
            <!-- Remove -->
            <button onclick="removeFromCart(<?php echo $row['cart_id']; ?>)" 
                    class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-full flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all border border-slate-100 hover:border-red-200"
                    title="Xóa khỏi giỏ">
                <i class="fas fa-trash-alt text-xs sm:text-sm"></i>
            </button>
        </div>
        <?php endwhile; ?>
        </div>
    </div>

        <!-- Summary sidebar -->
        <div class="sticky top-24">
        <div class="bg-white rounded-2xl sm:rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="bg-slate-50/80 p-5 sm:p-6">
            <h3 class="font-bold text-slate-900 text-lg mb-4">Tổng đơn hàng</h3>
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm text-slate-500">Tạm tính</p>
                <p class="text-sm font-bold text-slate-700"><?php echo number_format($total); ?> đ</p>
            </div>
            <div class="flex items-center justify-between pt-3 border-t border-slate-200 mt-3">
                <p class="text-base font-bold text-slate-900">Tổng thanh toán</p>
                <p class="text-2xl font-black text-primary"><?php echo number_format($total); ?> <span class="text-base">đ</span></p>
            </div>
            <form action="../checkout.php" method="post" class="mt-5">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="checkout_type" value="cart">
                <button type="submit" 
                        class="w-full px-6 py-3.5 bg-primary text-white font-black rounded-xl hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex items-center justify-center gap-2 border-0">
                    <i class="fas fa-credit-card"></i> Thanh toán ngay
                </button>
            </form>
            <div class="mt-4 space-y-2">
                <a href="myOrders.php" class="text-xs text-slate-500 hover:text-primary transition-colors flex items-center gap-1.5">
                    <i class="fas fa-receipt text-xs"></i> Xem đơn hàng của tôi
                </a>
                <a href="../courses.php" class="text-xs text-slate-500 hover:text-primary transition-colors flex items-center gap-1.5">
                    <i class="fas fa-plus text-xs"></i> Thêm khóa học
                </a>
            </div>
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
    <?php if($stmtCart) { $stmtCart->close(); } ?>
</div>

<script>
async function removeFromCart(cartId) {
    if (!confirm('Bạn có chắc muốn xóa khóa học này khỏi giỏ hàng?')) {
        return;
    }

    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';
    const body = new URLSearchParams({
        action: 'remove',
        cart_id: String(cartId),
        csrf_token: csrfToken
    });

    try {
        const response = await fetch('../cart_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        });

        const payload = await response.json();
        if (payload.status === 'success') {
            location.reload();
            return;
        }

        alert(payload.msg || 'Không thể xoá khoá học khỏi giỏ hàng.');
    } catch (_error) {
        alert('Không thể kết nối máy chủ. Vui lòng thử lại.');
    }
}
</script>

<?php include('./stuInclude/footer.php'); ?>
