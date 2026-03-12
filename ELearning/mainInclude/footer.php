<!-- Pre-footer/Footer -->
<footer class="bg-slate-900 text-slate-300 pt-20 mt-auto">
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
<li><a class="hover:text-primary transition-colors text-slate-300" href="courses.php">Thiết kế đồ họa</a></li>
<li><a class="hover:text-primary transition-colors text-slate-300" href="courses.php">Dựng phim &amp; Kỹ xảo</a></li>
<li><a class="hover:text-primary transition-colors text-slate-300" href="courses.php">Thiết kế Website</a></li>
<li><a class="hover:text-primary transition-colors text-slate-300" href="courses.php">Nhiếp ảnh chuyên nghiệp</a></li>
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
<?php   
    if (isset($_SESSION['is_admin_login'])){
       echo '<a href="admin/adminDashboard.php" class="hover:text-white transition-colors text-white/80 font-bold border-l border-white/20 pl-4">Quản trị</a>';
    } else {
       echo '<a href="#login" data-toggle="modal" data-target="#adminLoginModalCenter" class="hover:text-white transition-colors text-white/80 font-bold border-l border-white/20 pl-4">Quản trị viên</a>';
    }
?>
</div>
</div>
</div>
</footer>
<!-- End Footer -->


  <!-- Start Admin Login Modal -->
  <div class="modal fade" id="adminLoginModalCenter" tabindex="-1" role="dialog" aria-labelledby="adminLoginModalCenterTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header" style="background-color: #003366; color: #fff;">
            <h5 class="modal-title" id="adminLoginModalCenterTitle">Đăng nhập Quản trị</h5>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" onClick="clearAdminLoginWithStatus()">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form role="form" id="adminLoginForm">
              <div class="form-group">
                <i class="fas fa-envelope"></i><label for="adminLogEmail" class="pl-2 font-weight-bold">Email</label><input type="email"
                    class="form-control" placeholder="Nhập email" name="adminLogEmail" id="adminLogEmail">
                </div>
                <div class="form-group">
                  <i class="fas fa-key"></i><label for="adminLogPass" class="pl-2 font-weight-bold">Mật khẩu</label><input type="password" class="form-control" placeholder="Nhập mật khẩu" name="adminLogPass" id="adminLogPass">
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <small id="statusAdminLogMsg"></small>
            <button type="button" class="btn btn-primary" id="adminLoginBtn" onclick="checkAdminLogin()">Đăng nhập</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal" onClick="clearAdminLoginWithStatus()">Huỷ</button>
          </div>
        </div>
      </div>
    </div> <!-- End Admin Login Modal -->

    <!-- Jquery and Boostrap JavaScript -->
    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/popper.min.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>

    <!-- Font Awesome JS -->
    <script type="text/javascript" src="js/all.min.js"></script>

    <!-- Student Testimonial Owl Slider JS  -->
    <script type="text/javascript" src="js/owl.min.js"></script>
    <script type="text/javascript" src="js/testyslider.js"></script>

    <!-- Student Ajax Call JavaScript -->
    <script type="text/javascript" src="js/ajaxrequest.js"></script>

    <!-- Admin Ajax Call JavaScript -->
    <script type="text/javascript" src="js/adminajaxrequest.js?v=2"></script>

    <!-- Custom JavaScript -->
    <script type="text/javascript" src="js/custom.js"></script>

  </body>

</html>