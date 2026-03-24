<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../commerce_helpers.php');

define('TITLE', 'Đơn hàng của tôi');
define('PAGE', 'myOrders');
include('./stuInclude/header.php');
include_once('../dbConnection.php');

if (!isset($_SESSION['is_login'], $_SESSION['stuLogEmail'])) {
    echo "<script> location.href='../index.php'; </script>";
    exit;
}

$stuEmail = (string) $_SESSION['stuLogEmail'];
$studentId = commerce_get_student_id($conn, $stuEmail);
$commerceFlash = commerce_pull_flash();
$orders = false;

if ($studentId !== null) {
    $orderStmt = $conn->prepare(
        'SELECT om.order_id, om.order_code, om.order_total, om.order_status, om.created_at, '
        . 'p.payment_status, p.payment_proof_url, p.notes, '
        . 'GROUP_CONCAT(c.course_name ORDER BY oi.order_item_id SEPARATOR " | ") AS course_names '
        . 'FROM order_master om '
        . 'INNER JOIN order_item oi ON oi.order_id = om.order_id '
        . 'INNER JOIN course c ON c.course_id = oi.course_id '
        . 'LEFT JOIN payment p ON p.order_id = om.order_id '
        . 'WHERE om.student_id = ? AND om.is_deleted = 0 '
        . 'GROUP BY om.order_id '
        . 'ORDER BY om.created_at DESC, om.order_id DESC'
    );
    if ($orderStmt) {
        $orderStmt->bind_param('i', $studentId);
        $orderStmt->execute();
        $orders = $orderStmt->get_result();
    }
}
?>

<div class="max-w-6xl mx-auto px-6 py-12">
    <div class="mb-8 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-black text-slate-900 flex items-center gap-3">
                <i class="fas fa-receipt text-primary"></i> Đơn hàng của tôi
            </h1>
            <p class="text-slate-500 mt-2">Theo dõi trạng thái thanh toán, minh chứng và quyền truy cập khóa học của bạn.</p>
        </div>
        <a href="../courses.php" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition-colors hover:border-primary hover:text-primary">
            <i class="fas fa-plus"></i> Mua thêm khóa học
        </a>
    </div>

    <?php if($commerceFlash): ?>
    <div class="mb-6 flex items-center gap-3 rounded-2xl border px-5 py-4 text-sm font-semibold <?php echo ($commerceFlash['type'] ?? '') === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
        <i class="fas <?php echo ($commerceFlash['type'] ?? '') === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <span><?php echo htmlspecialchars((string) ($commerceFlash['text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <?php endif; ?>

    <?php if($orders && $orders->num_rows > 0): ?>
    <div class="space-y-5">
        <?php while($order = $orders->fetch_assoc()): ?>
            <?php
            $orderMeta = commerce_get_order_status_meta((string) ($order['order_status'] ?? 'pending'));
            $paymentMeta = commerce_get_payment_status_meta((string) ($order['payment_status'] ?? 'pending'));
            $hasProof = !empty($order['payment_proof_url']);
            ?>
            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-3 mb-3">
                            <span class="rounded-full border px-3 py-1 text-xs font-bold text-slate-700 border-slate-200 bg-slate-50">
                                <?php echo htmlspecialchars($order['order_code']); ?>
                            </span>
                            <span class="rounded-full border px-3 py-1 text-xs font-bold <?php echo $orderMeta['class']; ?>">
                                <?php echo htmlspecialchars($orderMeta['label']); ?>
                            </span>
                            <span class="rounded-full border px-3 py-1 text-xs font-bold <?php echo $paymentMeta['class']; ?>">
                                <?php echo htmlspecialchars($paymentMeta['label']); ?>
                            </span>
                            <span class="rounded-full border px-3 py-1 text-xs font-bold <?php echo $hasProof ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 bg-slate-100 text-slate-600'; ?>">
                                <?php echo $hasProof ? 'Đã có minh chứng' : 'Chưa có minh chứng'; ?>
                            </span>
                        </div>

                        <h2 class="text-lg font-black text-slate-900 line-clamp-2"><?php echo htmlspecialchars((string) ($order['course_names'] ?? 'Đơn hàng khóa học'), ENT_QUOTES, 'UTF-8'); ?></h2>
                        <div class="mt-3 flex flex-wrap gap-4 text-xs text-slate-500">
                            <span class="flex items-center gap-1.5"><i class="fas fa-calendar-alt text-primary"></i> <?php echo htmlspecialchars(date('H:i d/m/Y', strtotime((string) $order['created_at']))); ?></span>
                            <span class="flex items-center gap-1.5"><i class="fas fa-money-bill-wave text-primary"></i> <?php echo number_format((int) ($order['order_total'] ?? 0)); ?> đ</span>
                        </div>

                        <?php if(!empty($order['notes'])): ?>
                        <div class="mt-4 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            <span class="font-semibold text-slate-800">Ghi chú:</span>
                            <?php echo nl2br(htmlspecialchars((string) $order['notes'], ENT_QUOTES, 'UTF-8')); ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex flex-col items-start gap-3 lg:items-end lg:text-right">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Tổng thanh toán</p>
                            <p class="mt-1 text-2xl font-black text-primary"><?php echo number_format((int) ($order['order_total'] ?? 0)); ?> đ</p>
                        </div>
                        <a href="orderDetails.php?order_code=<?php echo rawurlencode((string) $order['order_code']); ?>" class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-primary/90">
                            <i class="fas fa-eye"></i> Xem chi tiết đơn hàng
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="rounded-3xl border border-slate-100 bg-white p-16 text-center shadow-sm">
        <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-primary/10">
            <i class="fas fa-receipt text-3xl text-primary/50"></i>
        </div>
        <h2 class="text-xl font-bold text-slate-700">Bạn chưa có đơn hàng nào</h2>
        <p class="mt-2 text-slate-400">Khi tạo đơn từ giỏ hàng hoặc mua ngay, trạng thái thanh toán sẽ xuất hiện tại đây.</p>
        <a href="../courses.php" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-primary px-8 py-3.5 text-sm font-bold text-white transition-colors hover:bg-primary/90">
            <i class="fas fa-search"></i> Khám phá khóa học
        </a>
    </div>
    <?php endif; ?>
</div>

<?php
if(isset($orderStmt) && $orderStmt) {
    $orderStmt->close();
}
include('./stuInclude/footer.php');
?>
