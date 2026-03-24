    </div><!-- /.pt-16 wrapper -->

<!-- Pre-footer/Footer -->
<footer id="Contact" class="mt-auto bg-slate-900 pt-20 text-slate-300">
  <div class="mx-auto grid max-w-7xl gap-16 px-6 pb-16 md:grid-cols-3">
    <div>
      <div class="mb-6 flex items-center gap-3 text-white">
        <i class="fas fa-graduation-cap text-3xl font-bold"></i>
        <h2 class="m-0 text-xl font-bold tracking-tight">CTU E-Learning</h2>
      </div>
      <p class="mb-6 text-sm leading-relaxed">
        Nền tảng đào tạo trực tuyến chuyên sâu về Multimedia Design. Chúng tôi cam kết mang lại giá trị thực tế và kiến thức cập nhật nhất cho học viên.
      </p>
      <div class="flex gap-4">
        <a class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/5 transition-colors hover:bg-primary hover:text-white" href="#">
          <i class="fab fa-facebook-f text-lg"></i>
        </a>
        <a class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/5 transition-colors hover:bg-primary hover:text-white" href="#">
          <i class="fab fa-twitter text-lg"></i>
        </a>
        <a class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/5 transition-colors hover:bg-primary hover:text-white" href="#">
          <i class="fab fa-youtube text-lg"></i>
        </a>
      </div>
    </div>

    <div>
      <h3 class="mb-6 font-bold text-white">Danh mục</h3>
      <ul class="list-none space-y-4 pl-0 text-sm">
        <li><a class="text-slate-300 transition-colors hover:text-primary" href="courses.php">Thiết kế đồ họa</a></li>
        <li><a class="text-slate-300 transition-colors hover:text-primary" href="courses.php">Dựng phim &amp; Kỹ xảo</a></li>
        <li><a class="text-slate-300 transition-colors hover:text-primary" href="courses.php">Thiết kế Website</a></li>
        <li><a class="text-slate-300 transition-colors hover:text-primary" href="courses.php">Nhiếp ảnh chuyên nghiệp</a></li>
      </ul>
    </div>

    <div>
      <h3 class="mb-6 font-bold text-white">Liên hệ</h3>
      <ul class="list-none space-y-4 pl-0 text-sm">
        <li class="flex items-start gap-3">
          <i class="fas fa-map-marker-alt text-xl text-primary"></i>
          <span>Khu II, Đường 3/2, P. Xuân Khánh, Q. Ninh Kiều, TP. Cần Thơ</span>
        </li>
        <li class="flex items-center gap-3">
          <i class="fas fa-envelope text-xl text-primary"></i>
          <span>contact@ctu-elearning.edu.vn</span>
        </li>
        <li class="flex items-center gap-3">
          <i class="fas fa-phone-alt text-xl text-primary"></i>
          <span>(+84) 123 456 789</span>
        </li>
      </ul>
    </div>
  </div>

  <div class="bg-primary px-6 py-6">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 md:flex-row">
      <p class="m-0 text-xs font-medium italic text-white/80">© 2026 CTU E-Learning. All rights reserved.</p>
      <div class="flex items-center gap-8 text-xs font-medium text-white/80">
        <a class="text-white/80 transition-colors hover:text-white" href="#">Điều khoản dịch vụ</a>
        <a class="text-white/80 transition-colors hover:text-white" href="#">Chính sách bảo mật</a>
        <?php
        if (isset($_SESSION['is_admin_login'])) {
          echo '<a href="Admin/adminDashboard.php" class="border-l border-white/20 pl-4 font-bold text-white/80 transition-colors hover:text-white">Quản trị</a>';
        } else {
          echo '<button type="button" id="openAdminLoginModal" class="border-0 border-l border-white/20 bg-transparent pl-4 font-bold text-white/80 transition-colors hover:text-white">Quản trị viên</button>';
        }
        ?>
      </div>
    </div>
  </div>
</footer>
<!-- End Footer -->

<!-- Start Admin Login Modal -->
<div id="adminLoginModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/70 p-4" role="dialog" aria-modal="true" aria-labelledby="adminLoginModalTitle">
  <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
    <div class="flex items-center justify-between bg-primary px-5 py-4 text-white">
      <h3 class="m-0 text-lg font-bold" id="adminLoginModalTitle">Đăng nhập Quản trị</h3>
      <button type="button" id="closeAdminLoginModal" class="rounded-lg border-0 bg-white/10 p-2 text-white transition-colors hover:bg-white/20" aria-label="Đóng">
        <i class="fas fa-times text-sm"></i>
      </button>
    </div>
    <div class="px-5 py-5">
      <form id="adminLoginForm" class="space-y-4" novalidate>
        <div>
          <label for="adminLogEmail" class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
          <input type="email" id="adminLogEmail" name="adminLogEmail" placeholder="Nhập email quản trị" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/10">
        </div>
        <div>
          <label for="adminLogPass" class="mb-1.5 block text-sm font-semibold text-slate-700">Mật khẩu</label>
          <div class="relative">
            <input type="password" id="adminLogPass" name="adminLogPass" placeholder="Nhập mật khẩu" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 pr-10 text-sm outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/10">
            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors border-0 bg-transparent p-1 cursor-pointer" id="toggleAdminPass" aria-label="Hiện/ẩn mật khẩu">
              <i class="fas fa-eye" id="toggleAdminPassIcon"></i>
            </button>
          </div>
        </div>
      </form>
      <small id="statusAdminLogMsg" class="mt-4 block min-h-5 text-sm font-medium text-slate-500"></small>
    </div>
    <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-4">
      <button type="button" id="adminLoginBtn" class="rounded-xl border-0 bg-primary px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-primary/90" onclick="checkAdminLogin()">Đăng nhập</button>
      <button type="button" id="cancelAdminLoginBtn" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50">Huỷ</button>
    </div>
  </div>
</div>
<!-- End Admin Login Modal -->

<!-- Font Awesome JS -->
<script src="js/all.min.js"></script>

<!-- Student Ajax Call JavaScript -->
<script src="js/ajaxrequest.js"></script>

<!-- Admin Ajax Call JavaScript -->
<script src="js/adminajaxrequest.js?v=3"></script>

<!-- Custom JavaScript -->
<script src="js/custom.js"></script>

<script>
  (function () {
    const modal = document.getElementById('adminLoginModal');
    const openBtn = document.getElementById('openAdminLoginModal');
    const closeBtn = document.getElementById('closeAdminLoginModal');
    const cancelBtn = document.getElementById('cancelAdminLoginBtn');
    const loginBtn = document.getElementById('adminLoginBtn');
    const loginForm = document.getElementById('adminLoginForm');

    if (!modal) {
      return;
    }

    const resetModalState = () => {
      if (typeof window.clearAdminLoginWithStatus === 'function') {
        window.clearAdminLoginWithStatus();
      }
    };

    const closeModal = () => {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
      resetModalState();
    };

    const openModal = () => {
      modal.classList.remove('hidden');
      modal.classList.add('flex');
      const emailInput = document.getElementById('adminLogEmail');
      if (emailInput) {
        emailInput.focus();
      }
    };

    if (openBtn) {
      openBtn.addEventListener('click', openModal);
    }

    [closeBtn, cancelBtn].forEach((btn) => {
      if (btn) {
        btn.addEventListener('click', closeModal);
      }
    });

    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
        closeModal();
      }
    });

    if (loginForm && loginBtn) {
      loginForm.addEventListener('submit', (event) => {
        event.preventDefault();
        loginBtn.click();
      });
    }

    // Admin password show/hide toggle
    const toggleAdminPass = document.getElementById('toggleAdminPass');
    const adminPassInput = document.getElementById('adminLogPass');
    const toggleAdminPassIcon = document.getElementById('toggleAdminPassIcon');
    if (toggleAdminPass && adminPassInput && toggleAdminPassIcon) {
      toggleAdminPass.addEventListener('click', () => {
        const isPassword = adminPassInput.type === 'password';
        adminPassInput.type = isPassword ? 'text' : 'password';
        toggleAdminPassIcon.classList.toggle('fa-eye', !isPassword);
        toggleAdminPassIcon.classList.toggle('fa-eye-slash', isPassword);
      });
    }
  })();
</script>

</body>
</html>
