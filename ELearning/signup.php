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
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-white mb-3 sm:mb-4">Đăng ký</h1>
            <p class="text-base sm:text-lg text-white/80 max-w-2xl mx-auto">Tạo tài khoản ngay hôm nay để truy cập hàng trăm khóa học chất lượng.</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-20">
     <div class="flex justify-center">
      <div class="w-full sm:w-8/12 md:w-5/12">
        <div class="bg-white shadow-xl rounded-2xl p-6 sm:p-10 border border-slate-100">
          <h5 class="mb-6 sm:mb-8 text-xl sm:text-2xl font-black text-primary text-center flex justify-center items-center gap-3"><i class="fas fa-user-plus"></i> Tạo tài khoản mới</h5>
          <form role="form" id="stuRegForm" class="space-y-5 sm:space-y-6">
            <input type="hidden" id="stuSignupRedirect" value="<?php echo htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8'); ?>">
            <div>
              <label for="stuname" class="block text-sm font-semibold text-slate-700 mb-2">
                <i class="fas fa-user text-slate-400 mr-1"></i> Họ và tên
              </label>
              <input type="text" class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 transition-all" placeholder="Nhập họ tên" name="stuname" id="stuname" autocomplete="name">
              <small id="statusMsg1" class="text-red-500 text-xs mt-1 block"></small>
            </div>
            <div>
              <label for="stuemail" class="block text-sm font-semibold text-slate-700 mb-2">
                <i class="fas fa-envelope text-slate-400 mr-1"></i> Email
              </label>
              <input type="email" class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 transition-all" placeholder="Nhập email" name="stuemail" id="stuemail" autocomplete="email">
              <small id="statusMsg2" class="text-red-500 text-xs mt-1 block"></small>
              <small class="text-slate-400 text-xs mt-1 block">Chúng tôi không chia sẻ email của bạn cho bên thứ ba.</small>
            </div>
            <div>
              <label for="stupass" class="block text-sm font-semibold text-slate-700 mb-2">
                <i class="fas fa-key text-slate-400 mr-1"></i> Mật khẩu
              </label>
              <div class="relative">
                <input type="password" class="w-full px-4 py-3 pr-12 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 transition-all" placeholder="Tạo mật khẩu" name="stupass" id="stupass" autocomplete="new-password">
                <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors border-0 bg-transparent p-1 cursor-pointer" id="toggleSignupPass" aria-label="Hiện/ẩn mật khẩu">
                  <i class="fas fa-eye" id="toggleSignupPassIcon"></i>
                </button>
              </div>
              <small id="statusMsg3" class="text-red-500 text-xs mt-1 block"></small>
              <!-- Password strength indicator -->
              <div class="mt-2" id="passStrengthContainer" style="display:none;">
                <div class="flex gap-1 mb-1">
                  <div class="h-1 flex-1 rounded-full bg-slate-200 transition-colors" id="passBar1"></div>
                  <div class="h-1 flex-1 rounded-full bg-slate-200 transition-colors" id="passBar2"></div>
                  <div class="h-1 flex-1 rounded-full bg-slate-200 transition-colors" id="passBar3"></div>
                  <div class="h-1 flex-1 rounded-full bg-slate-200 transition-colors" id="passBar4"></div>
                </div>
                <small id="passStrengthText" class="text-xs text-slate-400"></small>
              </div>
            </div>
            <button type="button" class="w-full px-6 py-3.5 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" id="signup" onclick="addStu()">
              Đăng ký <i class="fas fa-arrow-right"></i>
            </button>
          </form>
          <div class="mt-4 text-center">
            <small id="successMsg" class="font-semibold"></small>
          </div>
          <div class="text-center mt-6 pt-6 border-t border-slate-100">
              <span class="text-slate-600 text-sm">Đã có tài khoản? </span>
              <a href="login.php<?php echo $redirectTarget !== '' ? '?redirect=' . rawurlencode($redirectTarget) : ''; ?>" class="text-primary font-bold hover:underline text-sm">Đăng nhập ngay</a>
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
