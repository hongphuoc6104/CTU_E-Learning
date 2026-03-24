<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/admin_helpers.php');

define('TITLE', 'Báo cáo doanh thu');
define('PAGE', 'sellreport');
include('./adminInclude/header.php');
include('../dbConnection.php');

if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>";
}

$startdate = $_POST['startdate'] ?? date('Y-m-01');
$enddate   = $_POST['enddate']   ?? date('Y-m-d');
$searched  = isset($_POST['searchsubmit']);

$result = null;
$total = 0;
$enrollmentTotal = 0;
$coursePopularity = [];
$instructorPerformance = [];
$reportError = '';
if($searched){
    $startDateObj = DateTime::createFromFormat('Y-m-d', $startdate);
    $endDateObj = DateTime::createFromFormat('Y-m-d', $enddate);
    $isStartValid = $startDateObj && $startDateObj->format('Y-m-d') === $startdate;
    $isEndValid = $endDateObj && $endDateObj->format('Y-m-d') === $enddate;

    if(!$isStartValid || !$isEndValid) {
        $reportError = 'Định dạng ngày không hợp lệ.';
    } elseif($startdate > $enddate) {
        $reportError = 'Khoảng ngày không hợp lệ: "Từ ngày" phải nhỏ hơn hoặc bằng "Đến ngày".';
    } else {
        $sql = "SELECT om.order_id, om.order_code, om.order_total, om.order_status, om.created_at,
                       s.stu_name, s.stu_email,
                       GROUP_CONCAT(c.course_name ORDER BY oi.order_item_id SEPARATOR ' | ') AS course_names
                FROM order_master om
                INNER JOIN student s ON s.stu_id = om.student_id
                INNER JOIN order_item oi ON oi.order_id = om.order_id
                INNER JOIN course c ON c.course_id = oi.course_id
                WHERE DATE(om.created_at) BETWEEN ? AND ? AND om.order_status = 'paid' AND om.is_deleted = 0
                GROUP BY om.order_id
                ORDER BY om.created_at DESC";
        $stmt = $conn->prepare($sql);
        if($stmt) {
            $stmt->bind_param('ss', $startdate, $enddate);
            if($stmt->execute()) {
                $result = $stmt->get_result();
            } else {
                $reportError = 'Không thể tải dữ liệu báo cáo lúc này.';
            }
            $stmt->close();
        } else {
            $reportError = 'Không thể tải dữ liệu báo cáo lúc này.';
        }

        if($reportError === '') {
            $sumStmt = $conn->prepare("SELECT COALESCE(SUM(order_total),0) as s FROM order_master WHERE DATE(created_at) BETWEEN ? AND ? AND order_status='paid' AND is_deleted=0");
            if($sumStmt) {
                $sumStmt->bind_param('ss', $startdate, $enddate);
                if($sumStmt->execute()) {
                    $sum = $sumStmt->get_result();
                    $sumRow = $sum ? $sum->fetch_assoc() : ['s' => 0];
                    $total = (int) ($sumRow['s'] ?? 0);
                } else {
                    $reportError = 'Không thể tính tổng doanh thu lúc này.';
                }
                $sumStmt->close();
            } else {
                $reportError = 'Không thể tính tổng doanh thu lúc này.';
            }
        }

        if($reportError === '') {
            $enrollmentStmt = $conn->prepare(
                'SELECT COUNT(*) AS c '
                . 'FROM enrollment e '
                . 'INNER JOIN order_master om ON om.order_id = e.order_id '
                . 'WHERE DATE(om.created_at) BETWEEN ? AND ? '
                . "AND om.order_status = 'paid' "
                . "AND e.enrollment_status = 'active'"
            );
            if($enrollmentStmt) {
                $enrollmentStmt->bind_param('ss', $startdate, $enddate);
                if($enrollmentStmt->execute()) {
                    $enrollmentResult = $enrollmentStmt->get_result();
                    $enrollmentRow = $enrollmentResult ? $enrollmentResult->fetch_assoc() : ['c' => 0];
                    $enrollmentTotal = (int) ($enrollmentRow['c'] ?? 0);
                }
                $enrollmentStmt->close();
            }
        }

        if($reportError === '') {
            $popularStmt = $conn->prepare(
                'SELECT c.course_name, COUNT(*) AS enrollments '
                . 'FROM enrollment e '
                . 'INNER JOIN course c ON c.course_id = e.course_id '
                . 'INNER JOIN order_master om ON om.order_id = e.order_id '
                . 'WHERE DATE(om.created_at) BETWEEN ? AND ? '
                . "AND om.order_status = 'paid' "
                . "AND e.enrollment_status = 'active' "
                . 'GROUP BY e.course_id '
                . 'ORDER BY enrollments DESC, c.course_name ASC '
                . 'LIMIT 10'
            );
            if($popularStmt) {
                $popularStmt->bind_param('ss', $startdate, $enddate);
                if($popularStmt->execute()) {
                    $popularResult = $popularStmt->get_result();
                    if($popularResult) {
                        while($row = $popularResult->fetch_assoc()) {
                            $coursePopularity[] = $row;
                        }
                    }
                }
                $popularStmt->close();
            }
        }

        if($reportError === '') {
            $insStmt = $conn->prepare(
                'SELECT i.ins_name, COUNT(*) AS paid_enrollments, COALESCE(SUM(oi.unit_price), 0) AS gross_revenue '
                . 'FROM order_master om '
                . 'INNER JOIN order_item oi ON oi.order_id = om.order_id '
                . 'INNER JOIN course c ON c.course_id = oi.course_id '
                . 'LEFT JOIN instructor i ON i.ins_id = c.instructor_id '
                . 'WHERE DATE(om.created_at) BETWEEN ? AND ? '
                . "AND om.order_status = 'paid' "
                . 'GROUP BY c.instructor_id '
                . 'ORDER BY paid_enrollments DESC, gross_revenue DESC '
                . 'LIMIT 10'
            );
            if($insStmt) {
                $insStmt->bind_param('ss', $startdate, $enddate);
                if($insStmt->execute()) {
                    $insResult = $insStmt->get_result();
                    if($insResult) {
                        while($row = $insResult->fetch_assoc()) {
                            $instructorPerformance[] = $row;
                        }
                    }
                }
                $insStmt->close();
            }
        }
    }
}
?>

<!-- Date filter -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6 print:hidden">
  <form method="POST" class="flex flex-wrap items-end gap-4">
    <div>
      <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wide">Từ ngày</label>
      <input type="date" name="startdate" value="<?php echo $startdate; ?>" required
             class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary">
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wide">Đến ngày</label>
      <input type="date" name="enddate" value="<?php echo $enddate; ?>" required
             class="px-4 py-2.5 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary">
    </div>
    <button type="submit" name="searchsubmit"
            class="px-6 py-2.5 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition text-sm">
      <i class="fas fa-search mr-2"></i>Lọc báo cáo
    </button>
    <?php if($searched): ?>
    <button type="button" onclick="window.print()"
            class="px-6 py-2.5 bg-slate-100 text-slate-600 font-semibold rounded-xl hover:bg-slate-200 transition text-sm d-print-none">
      <i class="fas fa-print mr-2"></i>In báo cáo
    </button>
    <?php endif; ?>
  </form>
</div>

<?php if($reportError !== ''): ?>
<div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600">
  <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($reportError, ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>

<?php if($searched): ?>
<!-- Summary -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex items-center gap-4 print:border-none print:shadow-none print:p-2">
    <div class="w-11 h-11 bg-blue-500/10 rounded-xl flex items-center justify-center print:hidden">
      <i class="fas fa-receipt text-blue-500"></i>
    </div>
    <div>
      <p class="text-xs text-slate-400 print:text-black">Số giao dịch</p>
      <p class="text-2xl font-black text-slate-900 print:text-lg"><?php echo $result ? $result->num_rows : 0; ?></p>
    </div>
  </div>
  <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex items-center gap-4 print:border-none print:shadow-none print:p-2">
    <div class="w-11 h-11 bg-emerald-500/10 rounded-xl flex items-center justify-center print:hidden">
      <i class="fas fa-coins text-emerald-500"></i>
    </div>
    <div>
      <p class="text-xs text-slate-400 print:text-black">Tổng doanh thu</p>
      <p class="text-2xl font-black text-primary print:text-lg"><?php echo number_format($total); ?> đ</p>
    </div>
  </div>
  <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex items-center gap-4 print:border-none print:shadow-none print:p-2">
    <div class="w-11 h-11 bg-violet-500/10 rounded-xl flex items-center justify-center print:hidden">
      <i class="fas fa-user-graduate text-violet-500"></i>
    </div>
    <div>
      <p class="text-xs text-slate-400 print:text-black">Số enrollment</p>
      <p class="text-2xl font-black text-violet-600 print:text-lg"><?php echo $enrollmentTotal; ?></p>
    </div>
  </div>
  <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex items-center gap-4 print:border-none print:shadow-none print:p-2">
    <div class="w-11 h-11 bg-amber-500/10 rounded-xl flex items-center justify-center print:hidden">
      <i class="fas fa-layer-group text-amber-500"></i>
    </div>
    <div>
      <p class="text-xs text-slate-400 print:text-black">Khoá có paid enrollment</p>
      <p class="text-2xl font-black text-amber-600 print:text-lg"><?php echo count($coursePopularity); ?></p>
    </div>
  </div>
</div>

<!-- Table -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden print:border-none print:shadow-none">
  <div class="overflow-x-auto print:overflow-visible">
    <table class="w-full text-sm print:text-xs">
      <thead class="bg-slate-50 text-xs text-slate-500 uppercase print:bg-transparent print:text-black print:border-b print:border-black">
        <tr>
          <th class="px-6 py-3 text-left print:px-2 print:py-2">Mã đơn</th>
          <th class="px-6 py-3 text-left print:px-2 print:py-2">Khoá học</th>
          <th class="px-6 py-3 text-left print:px-2 print:py-2">Học viên</th>
          <th class="px-6 py-3 text-left print:px-2 print:py-2">Ngày tạo</th>
          <th class="px-6 py-3 text-left print:px-2 print:py-2">Trạng thái</th>
          <th class="px-6 py-3 text-right print:px-2 print:py-2">Số tiền</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 print:divide-slate-300">
      <?php if($result && $result->num_rows > 0): $result->data_seek(0); while($r = $result->fetch_assoc()): ?>
        <tr class="hover:bg-slate-50 print:hover:bg-transparent">
          <td class="px-6 py-3 font-mono text-xs text-slate-400 print:px-2 print:py-2 print:text-black"><?php echo htmlspecialchars((string) ($r['order_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="px-6 py-3 font-medium text-slate-800 print:px-2 print:py-2 print:text-black"><?php echo htmlspecialchars((string) ($r['course_names'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="px-6 py-3 text-slate-500 print:px-2 print:py-2 print:text-black">
            <div><?php echo htmlspecialchars((string) ($r['stu_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="text-xs text-slate-400 print:text-black"><?php echo htmlspecialchars((string) ($r['stu_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
          </td>
          <td class="px-6 py-3 text-slate-500 print:px-2 print:py-2 print:text-black"><?php echo htmlspecialchars((string) ($r['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="px-6 py-3 print:px-2 print:py-2">
            <span class="px-2 py-1 rounded-lg text-xs font-medium bg-green-50 text-green-700 print:bg-transparent print:p-0 print:text-black"><?php echo htmlspecialchars((string) ($r['order_status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
          </td>
          <td class="px-6 py-3 text-right font-bold text-primary print:px-2 print:py-2 print:text-black"><?php echo number_format((int) ($r['order_total'] ?? 0)); ?> đ</td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400 print:text-black">Không có giao dịch nào trong khoảng thời gian này.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2 print:mt-4">
  <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm print:border-none print:shadow-none print:p-2">
    <h3 class="text-sm font-black uppercase tracking-wide text-slate-500 print:text-black">Top khóa học theo paid enrollment</h3>
    <div class="mt-3 space-y-2">
      <?php if(count($coursePopularity) > 0): ?>
        <?php foreach($coursePopularity as $row): ?>
          <div class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2">
            <p class="m-0 text-sm font-semibold text-slate-700"><?php echo htmlspecialchars((string) ($row['course_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <span class="text-xs font-black text-primary"><?php echo (int) ($row['enrollments'] ?? 0); ?> enrollments</span>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-sm text-slate-400">Không có dữ liệu phổ biến trong khoảng đã chọn.</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm print:border-none print:shadow-none print:p-2">
    <h3 class="text-sm font-black uppercase tracking-wide text-slate-500 print:text-black">Tóm tắt hiệu suất giảng viên</h3>
    <div class="mt-3 space-y-2">
      <?php if(count($instructorPerformance) > 0): ?>
        <?php foreach($instructorPerformance as $row): ?>
          <div class="rounded-xl border border-slate-100 px-3 py-2">
            <p class="m-0 text-sm font-semibold text-slate-700"><?php echo htmlspecialchars((string) ($row['ins_name'] ?? 'Chưa gán instructor'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="m-0 mt-1 text-xs text-slate-500">
              <?php echo (int) ($row['paid_enrollments'] ?? 0); ?> paid enrollments · <?php echo number_format((int) ($row['gross_revenue'] ?? 0)); ?> đ gross
            </p>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-sm text-slate-400">Không có dữ liệu giảng viên trong khoảng đã chọn.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include('./adminInclude/footer.php'); ?>
