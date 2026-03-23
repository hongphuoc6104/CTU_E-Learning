<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();

define('TITLE', 'Trạng thái thanh toán');
define('PAGE', 'paymentstatus');
include('./adminInclude/header.php');
?>

<div class="max-w-3xl">
  <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8">
    <div class="w-14 h-14 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mb-5">
      <i class="fas fa-exclamation-triangle text-xl"></i>
    </div>
    <h2 class="text-xl font-black text-slate-900 mb-3">Màn hình Paytm cũ đã tắt</h2>
    <p class="text-slate-600 leading-relaxed mb-5">
      Khu vực này từng dùng để tra cứu trạng thái qua Paytm nhưng không còn khớp với luồng checkout hiện tại.
      Để tránh sai lệch dữ liệu, endpoint Paytm legacy đã được vô hiệu hoá.
    </p>
    <div class="text-sm text-slate-500 bg-slate-50 border border-slate-100 rounded-xl p-4">
      Theo dõi doanh thu và giao dịch tại trang <a href="sellReport.php" class="text-primary font-semibold hover:underline">Báo cáo doanh thu</a> hoặc
      <a href="adminDashboard.php" class="text-primary font-semibold hover:underline">Bảng điều khiển</a>.
    </div>
  </div>
</div>

<?php include('./adminInclude/footer.php'); ?>
