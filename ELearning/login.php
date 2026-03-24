<?php 
  include('./dbConnection.php');
  $redirectTarget = trim((string) ($_GET['redirect'] ?? ''));
  if ($redirectTarget !== '' && (str_contains($redirectTarget, '://') || str_starts_with($redirectTarget, '//') || str_contains($redirectTarget, "\n") || str_contains($redirectTarget, "\r"))) {
      $redirectTarget = '';
  }
  // Header Include from mainInclude 
  include('./mainInclude/header.php'); 
?>
    <section class="py-8 sm:py-12 lg:py-16">
      <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-2xl shadow-slate-200/60 lg:grid lg:grid-cols-[1.05fr_0.95fr]">
          <div class="relative overflow-hidden bg-gradient-to-br from-primary via-slate-900 to-slate-950 px-6 py-10 text-white sm:px-8 lg:px-10 lg:py-12">
            <div class="absolute -right-20 -top-20 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute -bottom-20 -left-16 h-72 w-72 rounded-full bg-emerald-400/15 blur-3xl"></div>

            <div class="relative z-10 flex h-full flex-col justify-between gap-10">
              <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.24em] text-white/60">Cổng học viên</p>
                <h1 class="mb-0 text-3xl font-black leading-tight text-white sm:text-4xl">Đăng nhập để tiếp tục học tập và theo dõi tiến độ</h1>
                <p class="mb-0 mt-4 max-w-xl text-sm leading-relaxed text-white/75 sm:text-base">
                  Truy cập khóa học đã mua, xem tiến độ học, theo dõi đơn hàng và tiếp tục bài học đang làm dở chỉ trong một nơi.
                </p>
              </div>

              <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                  <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.18em] text-white/55">Khóa học</p>
                  <p class="mb-0 text-sm font-semibold text-white/90">Tiếp tục học từ bài gần nhất mà không phải tìm lại nội dung.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                  <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.18em] text-white/55">Đơn hàng</p>
                  <p class="mb-0 text-sm font-semibold text-white/90">Theo dõi trạng thái thanh toán và minh chứng ngay trong tài khoản.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                  <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.18em] text-white/55">Tiến độ</p>
                  <p class="mb-0 text-sm font-semibold text-white/90">Xem nhanh bài học đã hoàn thành, bài tiếp theo và các khóa đang học.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center bg-white px-5 py-8 sm:px-8 sm:py-10 lg:px-10 lg:py-12">
            <div class="mx-auto w-full max-w-md">
              <a href="index.php" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-500 transition hover:border-primary hover:text-primary no-underline">
                <i class="fas fa-arrow-left text-[10px]"></i>
                <span>Về trang chủ</span>
              </a>

              <div class="mt-6 mb-8">
                <h2 class="mb-0 text-2xl font-black text-slate-900 sm:text-3xl">Đăng nhập</h2>
                <p class="mb-0 mt-3 text-sm leading-relaxed text-slate-500">Vui lòng đăng nhập để tiếp tục học tập và quản lý tài khoản của bạn.</p>
              </div>

              <form role="form" id="stuLoginForm" class="space-y-5 sm:space-y-6">
                <input type="hidden" id="stuLoginRedirect" value="<?php echo htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8'); ?>">
                <div>
                  <label for="stuLogEmail" class="mb-2 block text-sm font-semibold text-slate-700">
                    <i class="fas fa-envelope text-slate-400 mr-1"></i> Email
                  </label>
                  <input type="email" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="Nhập email của bạn" name="stuLogEmail" id="stuLogEmail" autocomplete="email">
                  <small id="statusLogMsg1" class="mt-1 block text-xs text-red-500"></small>
                </div>
                <div>
                  <label for="stuLogPass" class="mb-2 block text-sm font-semibold text-slate-700">
                    <i class="fas fa-key text-slate-400 mr-1"></i> Mật khẩu
                  </label>
                  <div class="relative">
                    <input type="password" class="w-full rounded-2xl border border-slate-200 px-4 py-3 pr-12 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="Nhập mật khẩu" name="stuLogPass" id="stuLogPass" autocomplete="current-password">
                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 border-0 bg-transparent p-1 text-slate-400 transition-colors hover:text-slate-600 cursor-pointer" id="toggleLoginPass" aria-label="Hiện hoặc ẩn mật khẩu">
                      <i class="fas fa-eye" id="toggleLoginPassIcon"></i>
                    </button>
                  </div>
                  <small class="mt-1 block text-xs text-slate-400">Nhấn vào biểu tượng con mắt để xem mật khẩu.</small>
                </div>
                <button type="button" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-primary px-6 py-3.5 text-sm font-extrabold text-white shadow-lg shadow-primary/20 transition-all hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50" id="stuLoginBtn" onclick="checkStuLogin()">
                  Đăng nhập <i class="fas fa-arrow-right"></i>
                </button>
              </form>

              <div class="mt-4 min-h-6 text-center">
                <small id="statusLogMsg" class="font-semibold"></small>
              </div>

              <div class="mt-8 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4 text-sm leading-relaxed text-slate-600">
                <p class="mb-0 font-semibold text-slate-800">Sau khi đăng nhập, bạn có thể:</p>
                <ul class="mb-0 mt-3 space-y-2 pl-5 text-slate-600">
                  <li>Tiếp tục học các khóa đã được cấp quyền.</li>
                  <li>Xem lại đơn hàng và trạng thái thanh toán.</li>
                  <li>Theo dõi tiến độ học tập và bài tiếp theo.</li>
                </ul>
              </div>

              <div class="mt-6 border-t border-slate-100 pt-6 text-center">
                <span class="text-sm text-slate-600">Chưa có tài khoản? </span>
                <a href="signup.php<?php echo $redirectTarget !== '' ? '?redirect=' . rawurlencode($redirectTarget) : ''; ?>" class="text-sm font-bold text-primary hover:underline">Đăng ký ngay</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

<?php 
// Contact Us
include('./contact.php'); 
?> 

<?php 
  // Footer Include from mainInclude 
  include('./mainInclude/footer.php'); 
?> 

<script>
// Show/hide password toggle for login
(function() {
    const toggleBtn = document.getElementById('toggleLoginPass');
    const passInput = document.getElementById('stuLogPass');
    const toggleIcon = document.getElementById('toggleLoginPassIcon');

    if (toggleBtn && passInput && toggleIcon) {
        toggleBtn.addEventListener('click', function() {
            const isPassword = passInput.type === 'password';
            passInput.type = isPassword ? 'text' : 'password';
            toggleIcon.classList.toggle('fa-eye', !isPassword);
            toggleIcon.classList.toggle('fa-eye-slash', isPassword);
        });
    }

    // Inline email validation on login page
    const emailInput = document.getElementById('stuLogEmail');
    const emailPattern = /^[A-Z0-9._%+-]+@([A-Z0-9-]+\.)+[A-Z]{2,}$/i;

    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const val = emailInput.value.trim();
            const msgEl = document.getElementById('statusLogMsg1');
            if (!msgEl) return;

            if (val === '') {
                msgEl.innerHTML = '<span class="text-red-500">Vui lòng nhập email</span>';
                emailInput.classList.add('border-red-400');
                emailInput.classList.remove('border-emerald-400');
            } else if (!emailPattern.test(val)) {
                msgEl.innerHTML = '<span class="text-red-500">Email không hợp lệ (vd: example@mail.com)</span>';
                emailInput.classList.add('border-red-400');
                emailInput.classList.remove('border-emerald-400');
            } else {
                msgEl.innerHTML = '';
                emailInput.classList.remove('border-red-400');
                emailInput.classList.add('border-emerald-400');
            }
        });

        emailInput.addEventListener('input', function() {
            const msgEl = document.getElementById('statusLogMsg1');
            if (msgEl) msgEl.innerHTML = '';
            emailInput.classList.remove('border-red-400', 'border-emerald-400');
        });
    }

    // Submit form on Enter key
    const form = document.getElementById('stuLoginForm');
    if (form) {
        form.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const loginBtn = document.getElementById('stuLoginBtn');
                if (loginBtn && !loginBtn.disabled) loginBtn.click();
            }
        });
    }
})();
</script>
