<?php
if(!isset($_SESSION)) session_start();
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

$result = null; $total = 0;
if($searched){
    $sql = "SELECT co.*, c.course_name, s.stu_name
            FROM courseorder co
            LEFT JOIN course c ON co.course_id=c.course_id
            LEFT JOIN student s ON co.stu_email=s.stu_email
            WHERE co.order_date BETWEEN ? AND ?
            ORDER BY co.order_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $startdate, $enddate);
    $stmt->execute();
    $result = $stmt->get_result();
    $sum = $conn->query("SELECT COALESCE(SUM(amount),0) as s FROM courseorder WHERE order_date BETWEEN '$startdate' AND '$enddate'");
    $total = $sum->fetch_assoc()['s'];
}
?>

<!-- Date filter -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
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

<?php if($searched): ?>
<!-- Summary -->
<div class="grid grid-cols-2 gap-4 mb-6">
  <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex items-center gap-4">
    <div class="w-11 h-11 bg-blue-500/10 rounded-xl flex items-center justify-center">
      <i class="fas fa-receipt text-blue-500"></i>
    </div>
    <div>
      <p class="text-xs text-slate-400">Số giao dịch</p>
      <p class="text-2xl font-black text-slate-900"><?php echo $result->num_rows; ?></p>
    </div>
  </div>
  <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex items-center gap-4">
    <div class="w-11 h-11 bg-emerald-500/10 rounded-xl flex items-center justify-center">
      <i class="fas fa-coins text-emerald-500"></i>
    </div>
    <div>
      <p class="text-xs text-slate-400">Tổng doanh thu</p>
      <p class="text-2xl font-black text-primary"><?php echo number_format($total); ?> đ</p>
    </div>
  </div>
</div>

<!-- Table -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
        <tr>
          <th class="px-6 py-3 text-left">Mã đơn</th>
          <th class="px-6 py-3 text-left">Khoá học</th>
          <th class="px-6 py-3 text-left">Học viên</th>
          <th class="px-6 py-3 text-left">Ngày</th>
          <th class="px-6 py-3 text-left">Trạng thái</th>
          <th class="px-6 py-3 text-right">Số tiền</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php if($result->num_rows > 0): $result->data_seek(0); while($r = $result->fetch_assoc()): ?>
        <tr class="hover:bg-slate-50">
          <td class="px-6 py-3 font-mono text-xs text-slate-400"><?php echo htmlspecialchars($r['order_id']); ?></td>
          <td class="px-6 py-3 font-medium text-slate-800"><?php echo htmlspecialchars($r['course_name'] ?? '—'); ?></td>
          <td class="px-6 py-3 text-slate-500">
            <div><?php echo htmlspecialchars($r['stu_name'] ?? ''); ?></div>
            <div class="text-xs text-slate-400"><?php echo htmlspecialchars($r['stu_email']); ?></div>
          </td>
          <td class="px-6 py-3 text-slate-500"><?php echo $r['order_date']; ?></td>
          <td class="px-6 py-3">
            <span class="px-2 py-1 rounded-lg text-xs font-medium bg-green-50 text-green-700"><?php echo htmlspecialchars($r['status']); ?></span>
          </td>
          <td class="px-6 py-3 text-right font-bold text-primary"><?php echo number_format($r['amount']); ?> đ</td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">Không có giao dịch nào trong khoảng thời gian này.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include('./adminInclude/footer.php'); ?>