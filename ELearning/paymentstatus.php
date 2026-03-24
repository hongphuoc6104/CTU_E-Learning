<?php
include('./dbConnection.php');
include('./mainInclude/header.php');
?>

<div class="pt-12 sm:pt-16 pb-16 bg-gradient-to-br from-primary to-slate-900 border-b border-primary/20 relative overflow-hidden">
  <div class="absolute inset-0 bg-primary/40"></div>
  <div class="max-w-7xl mx-auto px-6 relative z-10 text-center">
    <h1 class="text-4xl md:text-5xl font-black text-white mb-4">Trạng thái thanh toán</h1>
    <p class="text-lg text-white/80 max-w-2xl mx-auto">Màn hình Paytm cũ đã được tắt để tránh hiểu nhầm với luồng thanh toán mô phỏng hiện tại.</p>
  </div>
</div>

<section class="py-16 px-6 bg-background-light min-h-[50vh]">
  <div class="max-w-3xl mx-auto bg-white rounded-2xl border border-slate-100 shadow-sm p-8 md:p-10 text-center">
    <div class="w-16 h-16 rounded-full bg-amber-50 text-amber-600 mx-auto mb-5 flex items-center justify-center">
      <i class="fas fa-info-circle text-2xl"></i>
    </div>
    <h2 class="text-2xl font-black text-slate-900 mb-3">Trang này đã ngừng sử dụng</h2>
    <p class="text-slate-600 leading-relaxed mb-8">
      Dự án hiện dùng checkout nội bộ (mock checkout) với xác thực phía server.
      Kết quả giao dịch được phản ánh trực tiếp trong trang khoá học của học viên.
    </p>
    <a href="Student/myCourse.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-primary text-white font-bold hover:bg-primary/90 transition">
      <i class="fas fa-book-reader"></i> Xem khoá học của tôi
    </a>
  </div>
</section>

<?php include('./mainInclude/footer.php'); ?>
