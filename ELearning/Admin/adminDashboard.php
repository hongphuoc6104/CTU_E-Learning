<?php
if(!isset($_SESSION)) session_start();
define('TITLE', 'Bảng điều khiển');
define('PAGE', 'dashboard');
include('./adminInclude/header.php');
include('../dbConnection.php');

if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>";
}

// Stats
$totalCourse  = $conn->query("SELECT COUNT(*) as c FROM course")->fetch_assoc()['c'];
$totalStu     = $conn->query("SELECT COUNT(*) as c FROM student")->fetch_assoc()['c'];
$totalOrders  = $conn->query("SELECT COUNT(*) as c FROM courseorder")->fetch_assoc()['c'];
$totalRevenue = $conn->query("SELECT COALESCE(SUM(amount),0) as s FROM courseorder")->fetch_assoc()['s'];

// Delete order
if(isset($_POST['delete_order'])){
    $oid = (int)$_POST['oid'];
    $conn->query("DELETE FROM courseorder WHERE co_id=$oid");
    echo "<script>location.href='adminDashboard.php';</script>"; exit;
}

// Recent orders
$recent = $conn->query("SELECT co.*, c.course_name FROM courseorder co LEFT JOIN course c ON co.course_id=c.course_id ORDER BY co.order_date DESC LIMIT 10");
?>

<!-- Stats Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
  <?php
  $cards = [
    ['Khoá học',   $totalCourse,                  'fas fa-book',        'bg-blue-500'],
    ['Học viên',   $totalStu,                     'fas fa-users',       'bg-emerald-500'],
    ['Giao dịch',  $totalOrders,                  'fas fa-shopping-bag','bg-violet-500'],
    ['Doanh thu',  number_format($totalRevenue).' đ', 'fas fa-coins',   'bg-amber-500'],
  ];
  foreach($cards as [$label, $val, $icon, $bg]): ?>
  <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex items-center gap-4">
    <div class="w-12 h-12 <?php echo $bg; ?>/10 rounded-xl flex items-center justify-center shrink-0">
      <i class="<?php echo $icon; ?> <?php echo str_replace('bg-', 'text-', $bg); ?> text-lg"></i>
    </div>
    <div>
      <p class="text-xs text-slate-400 font-medium"><?php echo $label; ?></p>
      <p class="text-xl font-black text-slate-900"><?php echo $val; ?></p>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Recent Transactions -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
  <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
    <h2 class="font-bold text-slate-800">Giao dịch gần nhất</h2>
    <a href="sellReport.php" class="text-sm text-primary hover:underline">Xem tất cả →</a>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
        <tr>
          <th class="px-6 py-3 text-left font-semibold">Mã đơn</th>
          <th class="px-6 py-3 text-left font-semibold">Khoá học</th>
          <th class="px-6 py-3 text-left font-semibold">Học viên</th>
          <th class="px-6 py-3 text-left font-semibold">Ngày</th>
          <th class="px-6 py-3 text-right font-semibold">Số tiền</th>
          <th class="px-6 py-3 text-center font-semibold">Xoá</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php if($recent->num_rows > 0): while($r = $recent->fetch_assoc()): ?>
        <tr class="hover:bg-slate-50 transition-colors">
          <td class="px-6 py-3 text-slate-500 font-mono text-xs"><?php echo htmlspecialchars($r['order_id']); ?></td>
          <td class="px-6 py-3 font-medium text-slate-800 max-w-xs truncate"><?php echo htmlspecialchars($r['course_name'] ?? '—'); ?></td>
          <td class="px-6 py-3 text-slate-600"><?php echo htmlspecialchars($r['stu_email']); ?></td>
          <td class="px-6 py-3 text-slate-500"><?php echo $r['order_date']; ?></td>
          <td class="px-6 py-3 text-right font-bold text-primary"><?php echo number_format($r['amount']); ?> đ</td>
          <td class="px-6 py-3 text-center">
            <form method="POST" onsubmit="return confirm('Xoá giao dịch này?')">
              <input type="hidden" name="oid" value="<?php echo $r['co_id']; ?>">
              <button type="submit" name="delete_order" class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition-colors">
                <i class="fas fa-trash text-xs"></i>
              </button>
            </form>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">Chưa có giao dịch nào.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include('./adminInclude/footer.php'); ?>