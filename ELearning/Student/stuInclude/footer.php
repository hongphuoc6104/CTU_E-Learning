<?php
require_once(__DIR__ . '/../../commerce_helpers.php');

if(isset($_SESSION['is_login'])) {
    $stuLogEmail = $_SESSION['stuLogEmail'];
    $cart_count = commerce_get_cart_count($conn, $stuLogEmail);
} else {
    $cart_count = 0;
}
?>
</div><!-- /.pt-20 wrapper -->

<!-- Pre-footer/Footer -->
<footer id="Contact" class="bg-slate-900 text-slate-300 pt-20 mt-auto">
<div class="max-w-7xl mx-auto px-6 grid md:grid-cols-3 gap-16 pb-16">
<div>
<div class="flex items-center gap-3 mb-6 text-white">
<i class="fas fa-graduation-cap text-3xl font-bold"></i>
<h2 class="text-xl font-bold tracking-tight m-0">CTU E-Learning</h2>
</div>
<p class="text-sm leading-relaxed mb-6">
                    Nền tảng đào tạo trực tuyến chuyên sâu về Multimedia Design. Chúng tôi cam kết mang lại giá trị thực tế và kiến thức cập nhật nhất cho học viên.
                </p>
<div class="flex gap-4">
<a class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center hover:bg-primary hover:text-white transition-colors" href="#">
<i class="fab fa-facebook-f text-lg"></i>
</a>
<a class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center hover:bg-primary hover:text-white transition-colors" href="#">
<i class="fab fa-twitter text-lg"></i>
</a>
<a class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center hover:bg-primary hover:text-white transition-colors" href="#">
<i class="fab fa-youtube text-lg"></i>
</a>
</div>
</div>
<div>
<h3 class="text-white font-bold mb-6">Danh mục</h3>
<ul class="space-y-4 text-sm list-none pl-0">
<li><a class="hover:text-primary transition-colors text-slate-300" href="../courses.php">Thiết kế đồ họa</a></li>
<li><a class="hover:text-primary transition-colors text-slate-300" href="../courses.php">Dựng phim &amp; Kỹ xảo</a></li>
<li><a class="hover:text-primary transition-colors text-slate-300" href="../courses.php">Thiết kế Website</a></li>
<li><a class="hover:text-primary transition-colors text-slate-300" href="../courses.php">Nhiếp ảnh chuyên nghiệp</a></li>
</ul>
</div>
<div>
<h3 class="text-white font-bold mb-6">Liên hệ</h3>
<ul class="space-y-4 text-sm list-none pl-0">
<li class="flex items-start gap-3">
<i class="fas fa-map-marker-alt text-primary text-xl"></i>
<span>Khu II, Đường 3/2, P. Xuân Khánh, Q. Ninh Kiều, TP. Cần Thơ</span>
</li>
<li class="flex items-center gap-3">
<i class="fas fa-envelope text-primary text-xl"></i>
<span>contact@ctu-elearning.edu.vn</span>
</li>
<li class="flex items-center gap-3">
<i class="fas fa-phone-alt text-primary text-xl"></i>
<span>(+84) 123 456 789</span>
</li>
</ul>
</div>
</div>
<!-- Bottom Footer -->
<div class="bg-primary py-6 px-6">
<div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
<p class="text-xs font-medium text-white/80 italic m-0">© 2026 CTU E-Learning. All rights reserved.</p>
<div class="flex gap-8 text-xs text-white/80 font-medium items-center">
<a class="hover:text-white transition-colors text-white/80" href="#">Điều khoản dịch vụ</a>
<a class="hover:text-white transition-colors text-white/80" href="#">Chính sách bảo mật</a>
</div>
</div>
</div>
</footer>
<!-- End Footer -->

<!-- Font Awesome JS -->
<script type="text/javascript" src="../js/all.min.js"></script>
<!-- Custom JS -->
<script type="text/javascript" src="../js/custom.js"></script>
<script>
// Hiển thị số đếm giỏ hàng
document.addEventListener('DOMContentLoaded', function(){
    var el = document.getElementById('cartCount');
    if(el) el.textContent = '<?php echo $cart_count; ?>';
});
</script>
</body>
</html>
