<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../csrf.php');
require_once(__DIR__ . '/../commerce_helpers.php');

define('TITLE', 'Xác minh thanh toán');
define('PAGE', 'payments');
include('./adminInclude/header.php');
include('../dbConnection.php');

if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>";
    exit;
}

$adminEmail = (string) ($_SESSION['adminLogEmail'] ?? '');
$adminId = null;
$adminStmt = $conn->prepare('SELECT admin_id FROM admin WHERE admin_email = ? LIMIT 1');
if ($adminStmt) {
    $adminStmt->bind_param('s', $adminEmail);
    $adminStmt->execute();
    $adminResult = $adminStmt->get_result();
    $adminRow = $adminResult ? $adminResult->fetch_assoc() : null;
    $adminId = $adminRow ? (int) $adminRow['admin_id'] : null;
    $adminStmt->close();
}

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_action'])) {
    if(!csrf_verify($_POST['csrf_token'] ?? null)) {
        $flash = ['type' => 'error', 'text' => 'Phiên gửi biểu mẫu đã hết hạn. Vui lòng thử lại.'];
    } else {
        $paymentAction = (string) $_POST['payment_action'];
        $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
        $adminNote = trim((string) ($_POST['admin_note'] ?? ''));

        if (!$orderId || !in_array($paymentAction, ['verify', 'reject'], true)) {
            $flash = ['type' => 'error', 'text' => 'Yêu cầu xác minh thanh toán không hợp lệ.'];
        } else {
            $conn->begin_transaction();

            try {
                $lockStmt = $conn->prepare(
                    'SELECT om.order_id, om.order_code, om.student_id, om.order_status, om.created_at, '
                    . 's.stu_email, p.payment_id, p.payment_status, p.notes '
                    . 'FROM order_master om '
                    . 'INNER JOIN student s ON s.stu_id = om.student_id '
                    . 'INNER JOIN payment p ON p.order_id = om.order_id '
                    . 'WHERE om.order_id = ? AND om.is_deleted = 0 LIMIT 1'
                );
                if (!$lockStmt) {
                    throw new RuntimeException('Không thể tải đơn hàng để xác minh.');
                }

                $lockStmt->bind_param('i', $orderId);
                $lockStmt->execute();
                $lockedResult = $lockStmt->get_result();
                $lockedOrder = $lockedResult ? $lockedResult->fetch_assoc() : null;
                $lockStmt->close();

                if (!$lockedOrder) {
                    throw new RuntimeException('Không tìm thấy đơn hàng cần xử lý.');
                }

                if ((string) $lockedOrder['order_status'] !== 'awaiting_verification' || (string) $lockedOrder['payment_status'] !== 'submitted') {
                    throw new RuntimeException('Đơn hàng này không còn ở trạng thái chờ xác minh.');
                }

                $paymentId = (int) $lockedOrder['payment_id'];
                $verifiedAt = date('Y-m-d H:i:s');

                if ($paymentAction === 'verify') {
                    $paymentStatus = 'verified';
                    $orderStatus = 'paid';
                    $paymentNote = $adminNote !== '' ? $adminNote : 'Admin đã xác minh thanh toán hợp lệ.';

                    $updatePaymentStmt = $conn->prepare(
                        'UPDATE payment SET payment_status = ?, verified_by_admin_id = ?, verified_at = ?, notes = ? WHERE payment_id = ? LIMIT 1'
                    );
                    if (!$updatePaymentStmt) {
                        throw new RuntimeException('Không thể cập nhật payment.');
                    }
                    $updatePaymentStmt->bind_param('sissi', $paymentStatus, $adminId, $verifiedAt, $paymentNote, $paymentId);
                    if (!$updatePaymentStmt->execute()) {
                        $updatePaymentStmt->close();
                        throw new RuntimeException('Không thể xác minh payment.');
                    }
                    $updatePaymentStmt->close();

                    $updateOrderStmt = $conn->prepare('UPDATE order_master SET order_status = ? WHERE order_id = ? LIMIT 1');
                    if (!$updateOrderStmt) {
                        throw new RuntimeException('Không thể cập nhật trạng thái order.');
                    }
                    $updateOrderStmt->bind_param('si', $orderStatus, $orderId);
                    if (!$updateOrderStmt->execute()) {
                        $updateOrderStmt->close();
                        throw new RuntimeException('Không thể đánh dấu order đã thanh toán.');
                    }
                    $updateOrderStmt->close();

                    $itemStmt = $conn->prepare('SELECT order_item_id, course_id, unit_price FROM order_item WHERE order_id = ? ORDER BY order_item_id ASC');
                    if (!$itemStmt) {
                        throw new RuntimeException('Không thể tải order_item để cấp quyền học.');
                    }
                    $itemStmt->bind_param('i', $orderId);
                    $itemStmt->execute();
                    $itemsResult = $itemStmt->get_result();

                    $insertEnrollmentStmt = $conn->prepare(
                        'INSERT INTO enrollment (student_id, course_id, order_id, enrollment_status, granted_at, progress_percent) VALUES (?, ?, ?, ?, ?, 0.00)'
                    );
                    $updateEnrollmentStmt = $conn->prepare(
                        'UPDATE enrollment SET order_id = ?, enrollment_status = ?, granted_at = ?, completed_at = NULL WHERE enrollment_id = ? LIMIT 1'
                    );
                    $findEnrollmentStmt = $conn->prepare('SELECT enrollment_id FROM enrollment WHERE student_id = ? AND course_id = ? LIMIT 1');
                    $findLegacyOrderStmt = $conn->prepare('SELECT 1 FROM courseorder WHERE order_id = ? AND course_id = ? LIMIT 1');
                    $insertLegacyOrderStmt = $conn->prepare(
                        'INSERT INTO courseorder (order_id, stu_email, course_id, status, respmsg, amount, order_date) VALUES (?, ?, ?, ?, ?, ?, ?)'
                    );

                    if (!$insertEnrollmentStmt || !$updateEnrollmentStmt || !$findEnrollmentStmt || !$findLegacyOrderStmt || !$insertLegacyOrderStmt) {
                        $itemStmt->close();
                        throw new RuntimeException('Không thể chuẩn bị câu lệnh cấp quyền học.');
                    }

                    $activeEnrollmentStatus = 'active';
                    $legacyStatus = 'TXN_SUCCESS';
                    $legacyResp = 'Verified payment';
                    $grantedAt = $verifiedAt;
                    $legacyOrderDate = date('Y-m-d', strtotime((string) $lockedOrder['created_at']));
                    $stuEmail = (string) $lockedOrder['stu_email'];
                    $lockedStudentId = (int) $lockedOrder['student_id'];
                    $itemIndex = 0;

                    while ($item = $itemsResult->fetch_assoc()) {
                        $itemIndex++;
                        $courseId = (int) $item['course_id'];
                        $unitPrice = (int) $item['unit_price'];

                        $findEnrollmentStmt->bind_param('ii', $lockedStudentId, $courseId);
                        $findEnrollmentStmt->execute();
                        $existingEnrollmentResult = $findEnrollmentStmt->get_result();
                        $existingEnrollment = $existingEnrollmentResult ? $existingEnrollmentResult->fetch_assoc() : null;

                        if ($existingEnrollment) {
                            $enrollmentId = (int) $existingEnrollment['enrollment_id'];
                            $updateEnrollmentStmt->bind_param('issi', $orderId, $activeEnrollmentStatus, $grantedAt, $enrollmentId);
                            if (!$updateEnrollmentStmt->execute()) {
                                throw new RuntimeException('Không thể cập nhật enrollment hiện có.');
                            }
                        } else {
                            $insertEnrollmentStmt->bind_param('iiiss', $lockedStudentId, $courseId, $orderId, $activeEnrollmentStatus, $grantedAt);
                            if (!$insertEnrollmentStmt->execute()) {
                                throw new RuntimeException('Không thể tạo enrollment mới.');
                            }
                        }

                        $legacyOrderCode = ((string) $lockedOrder['order_code']) . '-' . $itemIndex;
                        $findLegacyOrderStmt->bind_param('si', $legacyOrderCode, $courseId);
                        $findLegacyOrderStmt->execute();
                        $legacyResult = $findLegacyOrderStmt->get_result();
                        $hasLegacyOrder = $legacyResult && $legacyResult->num_rows > 0;

                        if (!$hasLegacyOrder) {
                            $insertLegacyOrderStmt->bind_param('ssissis', $legacyOrderCode, $stuEmail, $courseId, $legacyStatus, $legacyResp, $unitPrice, $legacyOrderDate);
                            if (!$insertLegacyOrderStmt->execute()) {
                                throw new RuntimeException('Không thể tạo bản ghi courseorder tương thích.');
                            }
                        }
                    }

                    $itemStmt->close();
                    $insertEnrollmentStmt->close();
                    $updateEnrollmentStmt->close();
                    $findEnrollmentStmt->close();
                    $findLegacyOrderStmt->close();
                    $insertLegacyOrderStmt->close();

                    $flash = ['type' => 'success', 'text' => 'Đã xác minh thanh toán và cấp quyền học thành công.'];
                } else {
                    $paymentStatus = 'rejected';
                    $orderStatus = 'failed';
                    $paymentNote = $adminNote !== '' ? $adminNote : 'Minh chứng thanh toán chưa hợp lệ. Học viên cần gửi lại thông tin chính xác hơn.';

                    $updatePaymentStmt = $conn->prepare(
                        'UPDATE payment SET payment_status = ?, verified_by_admin_id = ?, verified_at = ?, notes = ? WHERE payment_id = ? LIMIT 1'
                    );
                    if (!$updatePaymentStmt) {
                        throw new RuntimeException('Không thể cập nhật payment bị từ chối.');
                    }
                    $updatePaymentStmt->bind_param('sissi', $paymentStatus, $adminId, $verifiedAt, $paymentNote, $paymentId);
                    if (!$updatePaymentStmt->execute()) {
                        $updatePaymentStmt->close();
                        throw new RuntimeException('Không thể lưu kết quả từ chối payment.');
                    }
                    $updatePaymentStmt->close();

                    $updateOrderStmt = $conn->prepare('UPDATE order_master SET order_status = ? WHERE order_id = ? LIMIT 1');
                    if (!$updateOrderStmt) {
                        throw new RuntimeException('Không thể cập nhật trạng thái order bị từ chối.');
                    }
                    $updateOrderStmt->bind_param('si', $orderStatus, $orderId);
                    if (!$updateOrderStmt->execute()) {
                        $updateOrderStmt->close();
                        throw new RuntimeException('Không thể đánh dấu order thất bại.');
                    }
                    $updateOrderStmt->close();

                    $flash = ['type' => 'success', 'text' => 'Đã từ chối thanh toán và chuyển đơn hàng sang trạng thái thất bại.'];
                }

                $conn->commit();
            } catch (Throwable $exception) {
                $conn->rollback();
                $flash = ['type' => 'error', 'text' => $exception->getMessage() !== '' ? $exception->getMessage() : 'Không thể xử lý thanh toán này.'];
            }
        }
    }
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
    . 'GROUP BY om.order_id '
    . "ORDER BY FIELD(om.order_status, 'awaiting_verification', 'pending', 'failed', 'paid', 'cancelled', 'refunded'), om.created_at DESC, om.order_id DESC"
);
?>

<?php if($flash): ?>
<div class="mb-6 flex items-center gap-3 rounded-xl border px-4 py-3 text-sm font-medium <?php echo $flash['type'] === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
    <i class="fas <?php echo $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
    <span><?php echo htmlspecialchars($flash['text'], ENT_QUOTES, 'UTF-8'); ?></span>
</div>
<?php endif; ?>

<div class="rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden">
    <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-slate-800">Hàng chờ xác minh thanh toán</h2>
            <p class="text-sm text-slate-400 mt-1">Xử lý payment `submitted`, chuyển đơn sang `paid` hoặc `failed`, đồng thời tạo enrollment khi xác minh thành công.</p>
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
                    <td colspan="7" class="px-6 py-12 text-center text-slate-400">Chưa có đơn hàng nào để hiển thị.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('./adminInclude/footer.php'); ?>
