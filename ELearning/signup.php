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
            <div class="absolute inset-0 bg-slate-950/15"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.14),transparent_28%),radial-gradient(circle_at_bottom_right,rgba(16,185,129,0.14),transparent_24%)]"></div>
            <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute -bottom-20 -left-20 h-72 w-72 rounded-full bg-emerald-400/15 blur-3xl"></div>

            <div class="relative z-10 flex h-full flex-col justify-between gap-10">
              <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.24em] text-white/80" style="text-shadow:0 1px 8px rgba(15,23,42,.45);">Bắt đầu học tập</p>
                <h1 class="mb-0 text-3xl font-black leading-tight text-white sm:text-4xl" style="text-shadow:0 4px 18px rgba(15,23,42,.45);">Tạo tài khoản để truy cập khóa học, đơn hàng và tiến độ học</h1>
                <p class="mb-0 mt-4 max-w-xl text-sm leading-relaxed text-white/95 sm:text-base" style="text-shadow:0 2px 10px rgba(15,23,42,.4);">
                  Sau khi đăng ký, bạn có thể mua khóa học, theo dõi tiến độ, tham gia lớp live và xem lại replay ngay trong cùng một tài khoản.
                </p>
              </div>

              <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                <div class="rounded-2xl border border-white/20 bg-slate-950/35 px-4 py-4 shadow-lg shadow-slate-950/25 backdrop-blur-md">
                  <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.18em] text-white/80" style="text-shadow:0 1px 8px rgba(15,23,42,.35);">Mua khóa học</p>
                  <p class="mb-0 text-sm font-semibold text-white" style="text-shadow:0 1px 8px rgba(15,23,42,.35);">Tạo giỏ hàng, theo dõi đơn và xác minh thanh toán rõ ràng.</p>
                </div>
                <div class="rounded-2xl border border-white/20 bg-slate-950/35 px-4 py-4 shadow-lg shadow-slate-950/25 backdrop-blur-md">
                  <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.18em] text-white/80" style="text-shadow:0 1px 8px rgba(15,23,42,.35);">Học tập</p>
                  <p class="mb-0 text-sm font-semibold text-white" style="text-shadow:0 1px 8px rgba(15,23,42,.35);">Học video, bài viết, tài liệu, quiz và lớp trực tiếp trong cùng player.</p>
                </div>
                <div class="rounded-2xl border border-white/20 bg-slate-950/35 px-4 py-4 shadow-lg shadow-slate-950/25 backdrop-blur-md">
                  <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.18em] text-white/80" style="text-shadow:0 1px 8px rgba(15,23,42,.35);">Tiến độ</p>
                  <p class="mb-0 text-sm font-semibold text-white" style="text-shadow:0 1px 8px rgba(15,23,42,.35);">Theo dõi bài đang học, bài tiếp theo và trạng thái hoàn thành theo từng khóa.</p>
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
                <h2 class="mb-0 text-2xl font-black text-slate-900 sm:text-3xl">Tạo tài khoản mới</h2>
                <p class="mb-0 mt-3 text-sm leading-relaxed text-slate-500">Đăng ký ngay hôm nay để truy cập hàng trăm khóa học chất lượng và theo dõi trọn vẹn hành trình học tập.</p>
              </div>

              <form role="form" id="stuRegForm" class="space-y-5 sm:space-y-6">
                <input type="hidden" id="stuSignupRedirect" value="<?php echo htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8'); ?>">
                <div>
                  <label for="stuname" class="mb-2 block text-sm font-semibold text-slate-700">
                    <i class="fas fa-user text-slate-400 mr-1"></i> Họ và tên
                  </label>
                  <input type="text" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="Nhập họ tên" name="stuname" id="stuname" autocomplete="name">
                  <small id="statusMsg1" class="mt-1 block text-xs text-red-500"></small>
                </div>
                <div>
                  <label for="stuemail" class="mb-2 block text-sm font-semibold text-slate-700">
                    <i class="fas fa-envelope text-slate-400 mr-1"></i> Email
                  </label>
                  <input type="email" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="Nhập email" name="stuemail" id="stuemail" autocomplete="email">
                  <small id="statusMsg2" class="mt-1 block text-xs text-red-500"></small>
                  <small class="mt-1 block text-xs text-slate-400">Chúng tôi không chia sẻ email của bạn cho bên thứ ba.</small>
                </div>
                <div>
                  <label for="stupass" class="mb-2 block text-sm font-semibold text-slate-700">
                    <i class="fas fa-key text-slate-400 mr-1"></i> Mật khẩu
                  </label>
                  <div class="relative">
                    <input type="password" class="w-full rounded-2xl border border-slate-200 px-4 py-3 pr-12 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="Tạo mật khẩu" name="stupass" id="stupass" autocomplete="new-password">
                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 border-0 bg-transparent p-1 text-slate-400 transition-colors hover:text-slate-600 cursor-pointer" id="toggleSignupPass" aria-label="Hiện hoặc ẩn mật khẩu">
                      <i class="fas fa-eye" id="toggleSignupPassIcon"></i>
                    </button>
                  </div>
                  <small id="statusMsg3" class="mt-1 block text-xs text-red-500"></small>
                  <div class="mt-2 hidden" id="passStrengthContainer">
                    <div class="mb-1 flex gap-1">
                      <div class="h-1 flex-1 rounded-full bg-slate-200 transition-colors" id="passBar1"></div>
                      <div class="h-1 flex-1 rounded-full bg-slate-200 transition-colors" id="passBar2"></div>
                      <div class="h-1 flex-1 rounded-full bg-slate-200 transition-colors" id="passBar3"></div>
                      <div class="h-1 flex-1 rounded-full bg-slate-200 transition-colors" id="passBar4"></div>
                    </div>
                    <small id="passStrengthText" class="text-xs text-slate-400"></small>
                  </div>
                </div>
                <button type="button" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-primary px-6 py-3.5 text-sm font-extrabold text-white shadow-lg shadow-primary/20 transition-all hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50" id="signup" onclick="addStu()">
                  Đăng ký <i class="fas fa-arrow-right"></i>
                </button>
              </form>

              <div class="mt-4 min-h-6 text-center">
                <small id="successMsg" class="font-semibold"></small>
              </div>

              <div class="mt-8 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4 text-sm leading-relaxed text-slate-600">
                <p class="mb-0 font-semibold text-slate-800">Lợi ích khi có tài khoản:</p>
                <ul class="mb-0 mt-3 space-y-2 pl-5 text-slate-600">
                  <li>Lưu lịch sử học tập và bài đã hoàn thành.</li>
                  <li>Quản lý đơn hàng, thanh toán và minh chứng dễ dàng.</li>
                  <li>Tham gia lớp live và xem lại replay sau khi buổi học kết thúc.</li>
                </ul>
              </div>

              <div class="mt-6 border-t border-slate-100 pt-6 text-center">
                <span class="text-sm text-slate-600">Đã có tài khoản? </span>
                <a href="login.php<?php echo $redirectTarget !== '' ? '?redirect=' . rawurlencode($redirectTarget) : ''; ?>" class="text-sm font-bold text-primary hover:underline">Đăng nhập ngay</a>
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
(function() {
    // Show/hide password toggle for signup
    const toggleBtn = document.getElementById('toggleSignupPass');
    const passInput = document.getElementById('stupass');
    const toggleIcon = document.getElementById('toggleSignupPassIcon');

    if (toggleBtn && passInput && toggleIcon) {
        toggleBtn.addEventListener('click', function() {
            const isPassword = passInput.type === 'password';
            passInput.type = isPassword ? 'text' : 'password';
            toggleIcon.classList.toggle('fa-eye', !isPassword);
            toggleIcon.classList.toggle('fa-eye-slash', isPassword);
        });
    }

    // Password strength indicator
    if (passInput) {
        const container = document.getElementById('passStrengthContainer');
        const bars = [
            document.getElementById('passBar1'),
            document.getElementById('passBar2'),
            document.getElementById('passBar3'),
            document.getElementById('passBar4')
        ];
        const strengthText = document.getElementById('passStrengthText');

        passInput.addEventListener('input', function() {
            const val = passInput.value;
            if (val.length === 0) {
                container.style.display = 'none';
                return;
            }
            container.style.display = 'block';

            let score = 0;
            if (val.length >= 6) score++;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
            if (/[0-9]/.test(val) || /[^A-Za-z0-9]/.test(val)) score++;

            const colors = ['bg-red-400', 'bg-orange-400', 'bg-yellow-400', 'bg-emerald-400'];
            const labels = ['Yếu', 'Trung bình', 'Khá', 'Mạnh'];
            const textColors = ['text-red-500', 'text-orange-500', 'text-yellow-600', 'text-emerald-600'];

            bars.forEach(function(bar, i) {
                bar.className = 'h-1 flex-1 rounded-full transition-colors ';
                bar.className += i < score ? colors[Math.min(score - 1, 3)] : 'bg-slate-200';
            });

            strengthText.className = 'text-xs ' + textColors[Math.min(score - 1, 3)];
            strengthText.textContent = labels[Math.min(score - 1, 3)];
        });
    }

    // Inline name validation
    const nameInput = document.getElementById('stuname');
    if (nameInput) {
        nameInput.addEventListener('blur', function() {
            const val = nameInput.value.trim();
            const msgEl = document.getElementById('statusMsg1');
            if (!msgEl) return;

            if (val === '') {
                msgEl.innerHTML = '<span class="text-red-500">Vui lòng nhập họ tên</span>';
                nameInput.classList.add('border-red-400');
                nameInput.classList.remove('border-emerald-400');
            } else if (val.length < 2) {
                msgEl.innerHTML = '<span class="text-red-500">Họ tên phải có ít nhất 2 ký tự</span>';
                nameInput.classList.add('border-red-400');
                nameInput.classList.remove('border-emerald-400');
            } else {
                msgEl.innerHTML = '';
                nameInput.classList.remove('border-red-400');
                nameInput.classList.add('border-emerald-400');
            }
        });

        nameInput.addEventListener('input', function() {
            const msgEl = document.getElementById('statusMsg1');
            if (msgEl) msgEl.innerHTML = '';
            nameInput.classList.remove('border-red-400', 'border-emerald-400');
        });
    }

    // Submit form on Enter key
    const form = document.getElementById('stuRegForm');
    if (form) {
        form.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const signupBtn = document.getElementById('signup');
                if (signupBtn && !signupBtn.disabled) signupBtn.click();
            }
        });
    }
})();
</script>
