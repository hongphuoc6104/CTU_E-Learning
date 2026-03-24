<?php 
  include('./dbConnection.php');
  $redirectTarget = trim((string) ($_GET['redirect'] ?? ''));
  if ($redirectTarget !== '' && (str_contains($redirectTarget, '://') || str_starts_with($redirectTarget, '//') || str_contains($redirectTarget, "\n") || str_contains($redirectTarget, "\r"))) {
      $redirectTarget = '';
  }
  // Header Include from mainInclude 
  include('./mainInclude/header.php'); 
?>
    <div class="pt-24 sm:pt-32 pb-12 sm:pb-16 bg-gradient-to-br from-primary to-slate-900 border-b border-primary/20 relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.22),transparent_60%)]"></div>
        <div class="absolute inset-0 bg-primary/40"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 relative z-10 text-center">
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-white mb-3 sm:mb-4">Đăng nhập</h1>
            <p class="text-base sm:text-lg text-white/80 max-w-2xl mx-auto">Vui lòng đăng nhập để tiếp tục học tập và quản lý tài khoản của bạn.</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-20">
     <div class="flex justify-center">
      <div class="w-full sm:w-8/12 md:w-5/12">
        <div class="bg-white shadow-xl rounded-2xl p-6 sm:p-10 border border-slate-100">
          <h5 class="mb-6 sm:mb-8 text-xl sm:text-2xl font-black text-primary text-center flex justify-center items-center gap-3"><i class="fas fa-sign-in-alt"></i> Đăng nhập</h5>
          <form role="form" id="stuLoginForm" class="space-y-5 sm:space-y-6">
            <input type="hidden" id="stuLoginRedirect" value="<?php echo htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8'); ?>">
            <div>
              <label for="stuLogEmail" class="block text-sm font-semibold text-slate-700 mb-2">
                <i class="fas fa-envelope text-slate-400 mr-1"></i> Email
              </label>
              <input type="email" class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 transition-all" placeholder="Nhập email của bạn" name="stuLogEmail" id="stuLogEmail" autocomplete="email">
              <small id="statusLogMsg1" class="text-red-500 text-xs mt-1 block"></small>
            </div>
            <div>
              <label for="stuLogPass" class="block text-sm font-semibold text-slate-700 mb-2">
                <i class="fas fa-key text-slate-400 mr-1"></i> Mật khẩu
              </label>
              <div class="relative">
                <input type="password" class="w-full px-4 py-3 pr-12 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 transition-all" placeholder="Nhập mật khẩu" name="stuLogPass" id="stuLogPass" autocomplete="current-password">
                <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors border-0 bg-transparent p-1 cursor-pointer" id="toggleLoginPass" aria-label="Hiện/ẩn mật khẩu">
                  <i class="fas fa-eye" id="toggleLoginPassIcon"></i>
                </button>
              </div>
              <small class="text-slate-400 text-xs mt-1 block">Nhấn vào <i class="fas fa-eye text-xs"></i> để xem mật khẩu</small>
            </div>
            <button type="button" class="w-full px-6 py-3.5 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" id="stuLoginBtn" onclick="checkStuLogin()">
              Đăng nhập <i class="fas fa-arrow-right"></i>
            </button>
          </form>
          <div class="mt-4 text-center">
            <small id="statusLogMsg" class="font-semibold"></small>
          </div>
          <div class="text-center mt-6 pt-6 border-t border-slate-100">
              <span class="text-slate-600 text-sm">Chưa có tài khoản? </span>
              <a href="signup.php<?php echo $redirectTarget !== '' ? '?redirect=' . rawurlencode($redirectTarget) : ''; ?>" class="text-primary font-bold hover:underline text-sm">Đăng ký ngay</a>
           </div>
        </div>
      </div>
     </div>
    </div>

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
