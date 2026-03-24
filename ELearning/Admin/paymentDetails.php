<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../csrf.php');

define('TITLE', 'Chi tiết thanh toán');
define('PAGE', 'payments');
include('./adminInclude/header.php');
include('../dbConnection.php');

if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>";
    exit;
}

$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
if (!$orderId) {
    admin_set_flash('error', 'Đơn hàng không hợp lệ.');
    echo "<script>location.href='payments.php';</script>";
    exit;
}

$adminId = admin_find_current_id($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_action'])) {
    if(!csrf_verify($_POST['csrf_token'] ?? null)) {
        admin_set_flash('error', 'Phiên gửi biểu mẫu đã hết hạn.');
    } else {
        $paymentAction = (string) ($_POST['payment_action'] ?? '');
        $adminNote = trim((string) ($_POST['admin_note'] ?? ''));
        $decision = admin_process_payment_decision($conn, (int) $orderId, $paymentAction, $adminId, $adminNote);
        admin_set_flash($decision['ok'] ? 'success' : 'error', (string) ($decision['message'] ?? 'Không thể xử lý thanh toán này.'));
    }

    echo "<script>location.href='paymentDetails.php?order_id=" . (int) $orderId . "';</script>";
    exit;
}

$orderStmt = $conn->prepare(
    'SELECT om.order_id, om.order_code, om.order_total, om.order_status, om.created_at, om.updated_at, '
    . 's.stu_name, s.stu_email, p.payment_status, p.payment_method, p.payment_reference, p.payment_proof_url, p.notes, p.verified_at '
    . 'FROM order_master om '
    . 'INNER JOIN student s ON s.stu_id = om.student_id '
    . 'LEFT JOIN payment p ON p.order_id = om.order_id '
    . 'WHERE om.order_id = ? AND om.is_deleted = 0 LIMIT 1'
);

$order = null;
if ($orderStmt) {
    $orderStmt->bind_param('i', $orderId);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    $order = $orderResult ? $orderResult->fetch_assoc() : null;
    $orderStmt->close();
}

if (!$order) {
    admin_set_flash('error', 'Không tìm thấy đơn hàng.');
    echo "<script>location.href='payments.php';</script>";
    exit;
}

$itemsStmt = $conn->prepare(
    'SELECT oi.order_item_id, oi.course_id, oi.unit_price, c.course_name '
    . 'FROM order_item oi '
    . 'INNER JOIN course c ON c.course_id = oi.course_id '
    . 'WHERE oi.order_id = ? ORDER BY oi.order_item_id ASC'
);

$items = [];
if ($itemsStmt) {
    $itemsStmt->bind_param('i', $orderId);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    if ($itemsResult) {
        while ($row = $itemsResult->fetch_assoc()) {
            $items[] = $row;
        }
    }
    $itemsStmt->close();
}

$orderMeta = commerce_get_order_status_meta((string) ($order['order_status'] ?? 'pending'));
$paymentMeta = commerce_get_payment_status_meta((string) ($order['payment_status'] ?? 'pending'));
$canReview = (string) ($order['order_status'] ?? '') === 'awaiting_verification' && (string) ($order['payment_status'] ?? '') === 'submitted';
$proofUrl = trim((string) ($order['payment_proof_url'] ?? ''));
?>

<div class="mb-6">
  <a href="payments.php" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
    <i class="fas fa-arrow-left"></i> Quay lại danh sách thanh toán
  </a>
</div>

<div class="grid gap-6 lg:grid-cols-3">
  <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm lg:col-span-2">
    <h2 class="text-base font-black text-slate-800">Thông tin đơn hàng</h2>
    <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
      <p class="m-0"><span class="font-bold text-slate-600">Mã đơn:</span> <span class="font-mono text-slate-700"><?php echo htmlspecialchars((string) ($order['order_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></p>
      <p class="m-0"><span class="font-bold text-slate-600">Ngày tạo:</span> <?php echo htmlspecialchars((string) ($order['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
      <p class="m-0"><span class="font-bold text-slate-600">Học viên:</span> <?php echo htmlspecialchars((string) ($order['stu_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
      <p class="m-0"><span class="font-bold text-slate-600">Email:</span> <?php echo htmlspecialchars((string) ($order['stu_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
      <p class="m-0"><span class="font-bold text-slate-600">Tổng tiền:</span> <span class="font-black text-primary"><?php echo number_format((int) ($order['order_total'] ?? 0)); ?> đ</span></p>
      <p class="m-0"><span class="font-bold text-slate-600">Cập nhật:</span> <?php echo htmlspecialchars((string) ($order['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
      <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold <?php echo htmlspecialchars((string) ($orderMeta['class'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars((string) ($orderMeta['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
      </span>
      <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold <?php echo htmlspecialchars((string) ($paymentMeta['class'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars((string) ($paymentMeta['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
      </span>
    </div>

    <h3 class="mt-6 text-sm font-black uppercase tracking-wide text-slate-500">Danh sách khóa học trong đơn</h3>
    <div class="mt-2 overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
          <tr>
            <th class="px-4 py-2 text-left">Khóa học</th>
            <th class="px-4 py-2 text-right">Giá</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        <?php if(count($items) > 0): ?>
          <?php foreach($items as $item): ?>
            <tr>
              <td class="px-4 py-2 text-slate-700"><?php echo htmlspecialchars((string) ($item['course_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="px-4 py-2 text-right font-semibold text-slate-700"><?php echo number_format((int) ($item['unit_price'] ?? 0)); ?> đ</td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="2" class="px-4 py-6 text-center text-slate-400">Đơn hàng chưa có order item hợp lệ.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
    <h2 class="text-base font-black text-slate-800">Thông tin payment</h2>
    <div class="mt-4 space-y-2 text-sm text-slate-600">
      <p class="m-0"><span class="font-bold">Phương thức:</span> <?php echo htmlspecialchars((string) ($order['payment_method'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?></p>
      <p class="m-0"><span class="font-bold">Mã tham chiếu:</span> <?php echo htmlspecialchars((string) ($order['payment_reference'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></p>
      <p class="m-0"><span class="font-bold">Xác minh lúc:</span> <?php echo htmlspecialchars((string) ($order['verified_at'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></p>
      <?php if($proofUrl !== ''): ?>
        <p class="m-0">
          <a href="../<?php echo htmlspecialchars(ltrim($proofUrl, '/'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="inline-flex items-center gap-2 text-primary font-semibold hover:underline">
            <i class="fas fa-paperclip"></i> Xem minh chứng thanh toán
          </a>
        </p>
      <?php endif; ?>
      <?php if(!empty($order['notes'])): ?>
        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-xs leading-relaxed text-slate-600">
          <?php echo nl2br(htmlspecialchars((string) $order['notes'], ENT_QUOTES, 'UTF-8')); ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="mt-5 rounded-xl border border-slate-100 bg-slate-50 p-3">
      <?php if($canReview): ?>
        <form method="POST" class="space-y-3">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
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
        <p class="m-0 text-xs text-slate-500">Đơn này không còn ở trạng thái chờ xác minh nên không thể xử lý thêm.</p>
      <?php endif; ?>
    </div>
  </section>
</div>

<?php include('./adminInclude/footer.php'); ?>
