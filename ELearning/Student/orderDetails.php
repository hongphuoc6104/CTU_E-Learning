<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../commerce_helpers.php');

define('TITLE', 'Chi tiết đơn hàng');
define('PAGE', 'myOrders');
include('./stuInclude/header.php');
include_once('../dbConnection.php');

if (!isset($_SESSION['is_login'], $_SESSION['stuLogEmail'])) {
    echo "<script> location.href='../index.php'; </script>";
    exit;
}

$stuEmail = (string) $_SESSION['stuLogEmail'];
$studentId = commerce_get_student_id($conn, $stuEmail);
$orderCode = trim((string) ($_GET['order_code'] ?? ''));

if ($studentId === null || $orderCode === '') {
    commerce_set_flash('error', 'Thiếu mã đơn hàng để hiển thị chi tiết.');
    header('Location: myOrders.php');
    exit;
}

$orderStmt = $conn->prepare(
    'SELECT om.order_id, om.order_code, om.order_total, om.order_status, om.created_at, om.updated_at, '
    . 'p.payment_id, p.payment_method, p.payment_reference, p.payment_proof_url, p.payment_status, p.notes, p.verified_at '
    . 'FROM order_master om '
    . 'LEFT JOIN payment p ON p.order_id = om.order_id '
    . 'WHERE om.order_code = ? AND om.student_id = ? AND om.is_deleted = 0 LIMIT 1'
);

if (!$orderStmt) {
    commerce_set_flash('error', 'Không thể tải chi tiết đơn hàng lúc này.');
    header('Location: myOrders.php');
    exit;
}

$orderStmt->bind_param('si', $orderCode, $studentId);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();
$order = $orderResult ? $orderResult->fetch_assoc() : null;
$orderStmt->close();

if (!$order) {
    commerce_set_flash('error', 'Không tìm thấy đơn hàng này trong tài khoản của bạn.');
    header('Location: myOrders.php');
    exit;
}

$itemStmt = $conn->prepare(
    'SELECT oi.order_item_id, oi.unit_price, oi.item_status, c.course_id, c.course_name, c.course_img, c.course_duration '
    . 'FROM order_item oi '
    . 'INNER JOIN course c ON c.course_id = oi.course_id '
    . 'WHERE oi.order_id = ? '
    . 'ORDER BY oi.order_item_id ASC'
);
$items = false;
if ($itemStmt) {
    $orderId = (int) $order['order_id'];
    $itemStmt->bind_param('i', $orderId);
    $itemStmt->execute();
    $items = $itemStmt->get_result();
}

$orderMeta = commerce_get_order_status_meta((string) ($order['order_status'] ?? 'pending'));
$paymentMeta = commerce_get_payment_status_meta((string) ($order['payment_status'] ?? 'pending'));
$commerceFlash = commerce_pull_flash();
$canSubmitPayment = commerce_can_submit_payment((string) ($order['order_status'] ?? 'pending'), (string) ($order['payment_status'] ?? 'pending'));
$proofUrl = (string) ($order['payment_proof_url'] ?? '');
$proofPath = $proofUrl !== '' ? '../' . ltrim($proofUrl, '/') : '';
$isImageProof = $proofUrl !== '' && preg_match('/\.(jpg|jpeg|png|webp)$/i', $proofUrl) === 1;
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
    <div class="mb-6 sm:mb-8 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <a href="myOrders.php" class="inline-flex items-center gap-2 text-xs sm:text-sm font-semibold text-slate-500 transition-colors hover:text-primary">
                <i class="fas fa-arrow-left"></i> Quay lại danh sách đơn hàng
            </a>
            <h1 class="mt-2 sm:mt-3 text-2xl sm:text-3xl font-black text-slate-900 flex items-center gap-3">
                <i class="fas fa-file-invoice-dollar text-primary"></i> Đơn hàng <?php echo htmlspecialchars((string) $order['order_code']); ?>
            </h1>
            <p class="mt-1 sm:mt-2 text-sm text-slate-500">Theo dõi trạng thái thanh toán và hoàn tất việc gửi minh chứng.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <span class="rounded-full border px-3 py-1 text-xs font-bold <?php echo $orderMeta['class']; ?>"><?php echo htmlspecialchars($orderMeta['label']); ?></span>
            <span class="rounded-full border px-3 py-1 text-xs font-bold <?php echo $paymentMeta['class']; ?>"><?php echo htmlspecialchars($paymentMeta['label']); ?></span>
        </div>
    </div>

    <?php if($commerceFlash): ?>
    <div class="mb-6 flex items-center gap-3 rounded-2xl border px-5 py-4 text-sm font-semibold <?php echo ($commerceFlash['type'] ?? '') === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
        <i class="fas <?php echo ($commerceFlash['type'] ?? '') === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <span><?php echo htmlspecialchars((string) ($commerceFlash['text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <?php endif; ?>

    <div class="grid gap-4 sm:gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(300px,0.9fr)]">
        <section class="space-y-4 sm:space-y-6">
            <div class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Ngày tạo</p>
                    <p class="mt-2 text-lg font-black text-slate-900"><?php echo htmlspecialchars(date('H:i d/m/Y', strtotime((string) $order['created_at']))); ?></p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Tổng thanh toán</p>
                    <p class="mt-2 text-lg font-black text-primary"><?php echo number_format((int) ($order['order_total'] ?? 0)); ?> đ</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Mã tham chiếu</p>
                    <p class="mt-2 break-all text-sm font-bold text-slate-700"><?php echo htmlspecialchars((string) ($order['payment_reference'] ?? 'Chưa cập nhật'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3 mb-5">
                    <h2 class="text-xl font-black text-slate-900">Khóa học trong đơn</h2>
                    <span class="text-sm text-slate-400"><?php echo $items ? (int) $items->num_rows : 0; ?> mục</span>
                </div>
                <div class="space-y-4">
                    <?php if($items && $items->num_rows > 0): ?>
                        <?php while($item = $items->fetch_assoc()): ?>
                <div class="flex flex-col gap-3 sm:gap-4 rounded-2xl border border-slate-100 p-3 sm:p-4 md:flex-row md:items-center">
                            <a href="../coursedetails.php?course_id=<?php echo (int) $item['course_id']; ?>" class="shrink-0">
                                <img src="../<?php echo ltrim(str_replace('../', '', (string) $item['course_img']), '/'); ?>" class="h-20 w-32 rounded-xl object-cover" alt="Course image">
                            </a>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base font-bold text-slate-900 line-clamp-2"><?php echo htmlspecialchars((string) $item['course_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="mt-1 text-xs text-slate-500 flex items-center gap-1.5"><i class="fas fa-clock text-primary"></i> <?php echo htmlspecialchars((string) $item['course_duration'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Đơn giá</p>
                                <p class="mt-1 text-lg font-black text-red-600"><?php echo number_format((int) ($item['unit_price'] ?? 0)); ?> đ</p>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-sm text-slate-500">Không thể tải danh sách khóa học của đơn hàng này.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black text-slate-900">Trạng thái và lưu ý</h2>
                <div class="mt-4 space-y-4 text-sm text-slate-600">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <span class="font-semibold text-slate-800">Trạng thái đơn hàng:</span>
                        <?php echo htmlspecialchars($orderMeta['label']); ?>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                        <span class="font-semibold text-slate-800">Trạng thái thanh toán:</span>
                        <?php echo htmlspecialchars($paymentMeta['label']); ?>
                    </div>
                    <?php if(!empty($order['notes'])): ?>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 leading-relaxed">
                        <span class="font-semibold text-slate-800">Ghi chú hiện tại:</span>
                        <?php echo nl2br(htmlspecialchars((string) $order['notes'], ENT_QUOTES, 'UTF-8')); ?>
                    </div>
                    <?php endif; ?>
                    <?php if((string) ($order['order_status'] ?? '') === 'awaiting_verification'): ?>
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 leading-relaxed text-amber-800">
                        Admin đang đối soát minh chứng thanh toán của bạn. Khi xác minh thành công, khóa học sẽ tự động xuất hiện ở mục <strong>Khóa học của tôi</strong>.
                    </div>
                    <?php elseif((string) ($order['order_status'] ?? '') === 'paid'): ?>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 leading-relaxed text-emerald-800">
                        Đơn hàng đã được xác minh thành công. Bạn có thể học ngay từ mục <strong>Khóa học của tôi</strong>.
                    </div>
                    <?php elseif((string) ($order['order_status'] ?? '') === 'failed'): ?>
                    <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 leading-relaxed text-red-800">
                        Đơn hàng này đã bị từ chối hoặc thanh toán thất bại. Bạn có thể cập nhật lại tham chiếu hoặc tải minh chứng mới ngay bên phải.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <aside class="space-y-6">
            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                        <i class="fas fa-university text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-900">Hướng dẫn thanh toán</h2>
                        <p class="text-xs text-slate-500">Chuyển khoản thủ công và gửi minh chứng để admin xác minh</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    <p><span class="font-semibold text-slate-800">Ngân hàng:</span> MB Bank</p>
                    <p class="mt-2"><span class="font-semibold text-slate-800">Số tài khoản:</span> 1234 567 889</p>
                    <p class="mt-2"><span class="font-semibold text-slate-800">Chủ tài khoản:</span> CTU E-Learning Demo</p>
                    <p class="mt-2"><span class="font-semibold text-slate-800">Nội dung gợi ý:</span> <?php echo htmlspecialchars((string) ($order['order_code'] ?? '')); ?></p>
                </div>

                <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-4 text-center">
                    <p class="mb-3 text-sm font-bold text-slate-700">QR chuyển khoản demo</p>
                    <img src="../image/courseimg/QRTRAN.png" alt="QR thanh toán" class="mx-auto h-64 w-64 max-w-full rounded-xl border border-slate-200 bg-white object-contain">
                    <p class="mt-3 text-xs leading-relaxed text-slate-500">Sau khi chuyển khoản, bạn có thể gửi mã tham chiếu hoặc ảnh minh chứng. Hệ thống không xử lý real gateway trong plan này.</p>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-900">Minh chứng thanh toán</h2>
                <?php if($proofUrl !== ''): ?>
                <div class="mt-4 rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-700">Tệp hiện tại</p>
                    <?php if($isImageProof): ?>
                    <img src="<?php echo htmlspecialchars($proofPath, ENT_QUOTES, 'UTF-8'); ?>" alt="Minh chứng thanh toán" class="mt-3 w-full rounded-xl border border-slate-200 object-cover">
                    <?php endif; ?>
                    <a href="<?php echo htmlspecialchars($proofPath, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline">
                        <i class="fas fa-paperclip"></i> Xem minh chứng đã gửi
                    </a>
                </div>
                <?php endif; ?>

                <?php if($canSubmitPayment): ?>
                <form action="../checkout_action.php" method="post" enctype="multipart/form-data" class="mt-5 space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="order_code" value="<?php echo htmlspecialchars((string) $order['order_code'], ENT_QUOTES, 'UTF-8'); ?>">

                    <div>
                        <label for="paymentMethod" class="mb-2 block text-sm font-semibold text-slate-700">Phương thức thanh toán</label>
                        <select id="paymentMethod" name="payment_method" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/10">
                            <option value="bank_transfer" <?php echo ((string) ($order['payment_method'] ?? '') === 'bank_transfer') ? 'selected' : ''; ?>>Chuyển khoản ngân hàng</option>
                            <option value="qr_transfer" <?php echo ((string) ($order['payment_method'] ?? '') === 'qr_transfer') ? 'selected' : ''; ?>>Quét QR chuyển khoản</option>
                            <option value="momo" <?php echo ((string) ($order['payment_method'] ?? '') === 'momo') ? 'selected' : ''; ?>>Ví MoMo / ví điện tử</option>
                        </select>
                    </div>

                    <div>
                        <label for="paymentReference" class="mb-2 block text-sm font-semibold text-slate-700">Mã tham chiếu giao dịch</label>
                        <input id="paymentReference" type="text" name="payment_reference" value="<?php echo htmlspecialchars((string) ($order['payment_reference'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="VD: MB-20260324-7788">
                        <p class="mt-1 text-xs text-slate-400">Bạn có thể bỏ trống trường này nếu đã tải lên ảnh hoặc PDF minh chứng.</p>
                    </div>

                    <div>
                        <label for="paymentProof" class="mb-2 block text-sm font-semibold text-slate-700">Tải minh chứng thanh toán</label>
                        <input id="paymentProof" type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition-colors file:mr-4 file:rounded-xl file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-primary/90">
                        <p class="mt-1 text-xs text-slate-400">Hỗ trợ JPG, JPEG, PNG, WebP hoặc PDF. Dung lượng tối đa 3MB.</p>
                    </div>

                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-primary px-6 py-3.5 text-sm font-bold text-white transition-colors hover:bg-primary/90">
                        <i class="fas fa-paper-plane"></i> Gửi minh chứng thanh toán
                    </button>
                </form>
                <?php else: ?>
                <div class="mt-5 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4 text-sm leading-relaxed text-slate-600">
                    <?php if((string) ($order['order_status'] ?? '') === 'awaiting_verification'): ?>
                        Bạn đã gửi thông tin thanh toán. Hiện tại hệ thống đang khóa form để tránh tạo bản ghi trùng trong lúc admin xác minh.
                    <?php elseif((string) ($order['order_status'] ?? '') === 'paid'): ?>
                        Đơn hàng này đã hoàn tất, không cần gửi thêm minh chứng. Hãy vào mục <strong>Khóa học của tôi</strong> để bắt đầu học.
                    <?php else: ?>
                        Form thanh toán tạm thời không khả dụng cho trạng thái đơn hàng hiện tại.
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>

<?php
if(isset($itemStmt) && $itemStmt) {
    $itemStmt->close();
}
include('./stuInclude/footer.php');
?>
