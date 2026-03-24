<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../csrf.php');
require_once(__DIR__ . '/admin_helpers.php');

define('TITLE', 'Xác minh thanh toán');
define('PAGE', 'payments');
include('./adminInclude/header.php');
include('../dbConnection.php');

if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>";
    exit;
}

$adminId = admin_find_current_id($conn);
$statusFilter = trim((string) ($_GET['status'] ?? 'all'));
$validStatusFilter = ['all', 'awaiting_verification', 'paid', 'failed', 'pending'];
if (!in_array($statusFilter, $validStatusFilter, true)) {
    $statusFilter = 'all';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_action'])) {
    if(!csrf_verify($_POST['csrf_token'] ?? null)) {
        admin_set_flash('error', 'Phiên gửi biểu mẫu đã hết hạn. Vui lòng thử lại.');
    } else {
        $paymentAction = (string) $_POST['payment_action'];
        $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
        $adminNote = trim((string) ($_POST['admin_note'] ?? ''));
        $decision = admin_process_payment_decision($conn, (int) $orderId, $paymentAction, $adminId, $adminNote);
        admin_set_flash($decision['ok'] ? 'success' : 'error', (string) ($decision['message'] ?? 'Không thể xử lý thanh toán này.'));
    }

    $redirectStatus = in_array($statusFilter, $validStatusFilter, true) ? $statusFilter : 'all';
    echo "<script>location.href='payments.php?status=" . htmlspecialchars($redirectStatus, ENT_QUOTES, 'UTF-8') . "';</script>";
    exit;
}

$whereSql = '';
if ($statusFilter !== 'all') {
    $whereSql = " AND om.order_status = '" . $conn->real_escape_string($statusFilter) . "' ";
}

$payments = $conn->query(
    'SELECT om.order_id, om.order_code, om.order_total, om.order_status, om.created_at, '
    . 's.stu_name, s.stu_email, p.payment_status, p.payment_method, p.payment_reference, p.payment_proof_url, p.notes, '
    . 'GROUP_CONCAT(c.course_name ORDER BY oi.order_item_id SEPARATOR " | ") AS course_names '
    . 'FROM order_master om '
    . 'INNER JOIN student s ON s.stu_id = om.student_id '
    . 'INNER JOIN order_item oi ON oi.order_id = om.order_id '
    . 'INNER JOIN course c ON c.course_id = oi.course_id '
    . 'LEFT JOIN payment p ON p.order_id = om.order_id '
    . 'WHERE om.is_deleted = 0 '
    . $whereSql
    . 'GROUP BY om.order_id '
    . "ORDER BY FIELD(om.order_status, 'awaiting_verification', 'pending', 'failed', 'paid', 'cancelled', 'refunded'), om.created_at DESC, om.order_id DESC"
);

$pendingCount = 0;
$paidCount = 0;
$failedCount = 0;
$statsResult = $conn->query(
    "SELECT "
    . "SUM(CASE WHEN order_status = 'awaiting_verification' THEN 1 ELSE 0 END) AS pending_count, "
    . "SUM(CASE WHEN order_status = 'paid' THEN 1 ELSE 0 END) AS paid_count, "
    . "SUM(CASE WHEN order_status = 'failed' THEN 1 ELSE 0 END) AS failed_count "
    . 'FROM order_master WHERE is_deleted = 0'
);
if ($statsResult) {
    $statsRow = $statsResult->fetch_assoc();
    $pendingCount = (int) ($statsRow['pending_count'] ?? 0);
    $paidCount = (int) ($statsRow['paid_count'] ?? 0);
    $failedCount = (int) ($statsRow['failed_count'] ?? 0);
}
?>

<div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
    <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Đang chờ xử lý</p>
        <p class="mt-1 text-2xl font-black text-amber-600"><?php echo $pendingCount; ?></p>
    </div>
    <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Đã xác minh</p>
        <p class="mt-1 text-2xl font-black text-emerald-600"><?php echo $paidCount; ?></p>
    </div>
    <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Đã từ chối</p>
        <p class="mt-1 text-2xl font-black text-red-600"><?php echo $failedCount; ?></p>
    </div>
</div>

<div class="mb-5 flex flex-wrap gap-2">
    <?php
      $tabs = [
        'all' => 'Tất cả',
        'awaiting_verification' => 'Chờ xác minh',
        'paid' => 'Đã thanh toán',
        'failed' => 'Thất bại',
        'pending' => 'Chờ nộp',
      ];
      foreach ($tabs as $tabKey => $tabLabel):
        $isActive = $statusFilter === $tabKey;
    ?>
      <a href="payments.php?status=<?php echo urlencode($tabKey); ?>" class="inline-flex items-center rounded-xl border px-3 py-2 text-xs font-bold <?php echo $isActive ? 'border-primary bg-primary text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'; ?>">
        <?php echo htmlspecialchars($tabLabel, ENT_QUOTES, 'UTF-8'); ?>
      </a>
    <?php endforeach; ?>
</div>

<div class="rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden">
    <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-slate-800">Hàng chờ xác minh thanh toán</h2>
            <p class="text-sm text-slate-400 mt-1">Xử lý payment `submitted`, chuyển đơn sang `paid` hoặc `failed`, và tạo enrollment theo transaction.</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold">Đơn hàng</th>
                    <th class="px-6 py-3 text-left font-semibold">Học viên</th>
                    <th class="px-6 py-3 text-left font-semibold">Khóa học</th>
                    <th class="px-6 py-3 text-left font-semibold">Thanh toán</th>
                    <th class="px-6 py-3 text-left font-semibold">Trạng thái</th>
                    <th class="px-6 py-3 text-right font-semibold">Tổng tiền</th>
                    <th class="px-6 py-3 text-left font-semibold">Xử lý</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php if($payments && $payments->num_rows > 0): ?>
                <?php while($payment = $payments->fetch_assoc()): ?>
                    <?php
                    $orderMeta = commerce_get_order_status_meta((string) ($payment['order_status'] ?? 'pending'));
                    $paymentMeta = commerce_get_payment_status_meta((string) ($payment['payment_status'] ?? 'pending'));
                    $canReview = (string) ($payment['order_status'] ?? '') === 'awaiting_verification' && (string) ($payment['payment_status'] ?? '') === 'submitted';
                    $proofUrl = trim((string) ($payment['payment_proof_url'] ?? ''));
                    ?>
                    <tr class="align-top hover:bg-slate-50/70">
                        <td class="px-6 py-4">
                            <p class="font-mono text-xs font-semibold text-slate-700"><?php echo htmlspecialchars((string) $payment['order_code']); ?></p>
                            <p class="mt-1 text-xs text-slate-400"><?php echo htmlspecialchars(date('H:i d/m/Y', strtotime((string) $payment['created_at']))); ?></p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-semibold text-slate-800"><?php echo htmlspecialchars((string) ($payment['stu_name'] ?? '')); ?></p>
                            <p class="mt-1 text-xs text-slate-500"><?php echo htmlspecialchars((string) ($payment['stu_email'] ?? '')); ?></p>
                        </td>
                        <td class="px-6 py-4 max-w-sm">
                            <p class="font-medium text-slate-700 leading-relaxed"><?php echo htmlspecialchars((string) ($payment['course_names'] ?? '')); ?></p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-semibold text-slate-700"><?php echo htmlspecialchars((string) ($payment['payment_reference'] ?? '—')); ?></p>
                            <p class="mt-1 text-xs text-slate-400"><?php echo htmlspecialchars((string) ($payment['payment_method'] ?? 'pending')); ?></p>
                            <?php if($proofUrl !== ''): ?>
                            <a href="../<?php echo htmlspecialchars(ltrim($proofUrl, '/'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="mt-2 inline-flex items-center gap-2 text-xs font-semibold text-primary hover:underline">
                                <i class="fas fa-paperclip"></i> Xem minh chứng
                            </a>
                            <?php endif; ?>
                            <a href="paymentDetails.php?order_id=<?php echo (int) ($payment['order_id'] ?? 0); ?>" class="mt-2 inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-primary">
                                <i class="fas fa-eye"></i> Chi tiết
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <span class="mb-2 inline-flex rounded-full border px-3 py-1 text-[11px] font-bold <?php echo $orderMeta['class']; ?>"><?php echo htmlspecialchars($orderMeta['label']); ?></span>
                            <span class="inline-flex rounded-full border px-3 py-1 text-[11px] font-bold <?php echo $paymentMeta['class']; ?>"><?php echo htmlspecialchars($paymentMeta['label']); ?></span>
                            <?php if(!empty($payment['notes'])): ?>
                            <p class="mt-3 max-w-xs text-xs leading-relaxed text-slate-500"><?php echo nl2br(htmlspecialchars((string) $payment['notes'], ENT_QUOTES, 'UTF-8')); ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right font-black text-primary"><?php echo number_format((int) ($payment['order_total'] ?? 0)); ?> đ</td>
                        <td class="px-6 py-4 min-w-[280px]">
                            <?php if($canReview): ?>
                            <form method="POST" class="space-y-3">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="order_id" value="<?php echo (int) $payment['order_id']; ?>">
                                <textarea name="admin_note" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="Ghi chú cho học viên nếu cần..."></textarea>
                                <div class="flex gap-2">
                                    <button type="submit" name="payment_action" value="verify" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors">
                                        <i class="fas fa-check"></i> Xác minh
                                    </button>
                                    <button type="submit" name="payment_action" value="reject" class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-red-700 transition-colors">
                                        <i class="fas fa-times"></i> Từ chối
                                    </button>
                                </div>
                            </form>
                            <?php else: ?>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-xs leading-relaxed text-slate-500">
                                <?php if((string) ($payment['order_status'] ?? '') === 'pending'): ?>
                                    Học viên chưa gửi đủ thông tin thanh toán để admin xử lý.
                                <?php elseif((string) ($payment['order_status'] ?? '') === 'paid'): ?>
                                    Đơn hàng này đã được xác minh và enrollment đã được cấp.
                                <?php elseif((string) ($payment['order_status'] ?? '') === 'failed'): ?>
                                    Đơn hàng đã bị từ chối. Học viên có thể gửi lại minh chứng từ khu vực đơn hàng của họ.
                                <?php else: ?>
                                    Hiện không có thao tác admin nào cần thực hiện cho đơn này.
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-slate-400">Không có đơn hàng phù hợp bộ lọc hiện tại.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('./adminInclude/footer.php'); ?>
