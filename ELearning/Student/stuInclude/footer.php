<?php
// Cập nhật số đếm giỏ hàng
if(isset($_SESSION['is_login'])) {
    $stuLogEmail = $_SESSION['stuLogEmail'];
    $cart_sql = "SELECT COUNT(*) as cnt FROM cart WHERE stu_email='$stuLogEmail'";
    $cart_res = $conn->query($cart_sql);
    $cart_count = ($cart_res && $cart_row = $cart_res->fetch_assoc()) ? $cart_row['cnt'] : 0;
} else {
    $cart_count = 0;
}
?>
</div><!-- /.pt-20 wrapper -->

<!-- ===== FOOTER ===== -->
<footer class="bg-primary text-white mt-16">
    <div class="max-w-7xl mx-auto px-6 py-10 text-center">
        <a href="../index.php" class="flex items-center justify-center gap-3 mb-4 hover:opacity-80 transition-opacity">
            <i class="fas fa-graduation-cap text-2xl"></i>
            <span class="text-lg font-bold tracking-tight">CTU E-Learning</span>
        </a>
        <p class="text-white/60 text-sm">© 2026 CTU E-Learning — Trường Đại học Cần Thơ. Bảo lưu mọi quyền.</p>
    </div>
</footer>

<!-- Bootstrap JS -->
<script type="text/javascript" src="../js/popper.min.js"></script>
<script type="text/javascript" src="../js/bootstrap.min.js"></script>
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