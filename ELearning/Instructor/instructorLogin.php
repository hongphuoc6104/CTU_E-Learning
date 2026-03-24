<?php

require_once(__DIR__ . '/instructorInclude/auth.php');

if (instructor_is_logged_in() && instructor_current_profile($conn)) {
    header('Location: instructorDashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập giảng viên - CTU E-Learning</title>
  <link rel="stylesheet" href="../css/tailwind.css">
  <script defer src="../js/all.min.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      background:
        radial-gradient(circle at top left, rgba(0, 51, 102, 0.16), transparent 38%),
        radial-gradient(circle at bottom right, rgba(16, 185, 129, 0.12), transparent 34%),
        #f8fafc;
    }
  </style>
</head>
<body class="text-slate-900">
  <main class="mx-auto flex min-h-screen w-full max-w-6xl items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
    <div class="grid w-full overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-2xl shadow-slate-200/60 lg:grid-cols-[1.05fr_0.95fr]">
      <section class="relative overflow-hidden bg-gradient-to-br from-primary via-slate-900 to-slate-950 px-6 py-8 text-white sm:px-8 sm:py-10 lg:min-h-[680px] lg:px-10 lg:py-12">
        <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute -bottom-28 -left-24 h-72 w-72 rounded-full bg-emerald-400/15 blur-3xl"></div>

        <div class="relative z-10 flex h-full flex-col justify-between gap-10">
          <div>
            <a href="../index.php" class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-white/80 transition hover:bg-white/10 hover:text-white no-underline">
              <i class="fas fa-arrow-left text-[10px]"></i>
              <span>Về trang chủ</span>
            </a>

            <div class="mt-8 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 text-white shadow-lg shadow-black/10">
              <i class="fas fa-chalkboard-teacher text-lg"></i>
            </div>

            <p class="mb-0 mt-6 text-xs font-bold uppercase tracking-[0.25em] text-white/75" style="text-shadow:0 1px 8px rgba(15,23,42,.35);">Cổng giảng viên</p>
            <h1 class="mb-0 mt-4 text-3xl font-black leading-tight text-white sm:text-4xl">Quản lý khóa học và lớp live trong cùng một nơi</h1>
            <p class="mb-0 mt-4 max-w-xl text-sm leading-relaxed text-white/75 sm:text-base">
              Đăng nhập để tạo khóa học bản nháp, sắp xếp mục học, thêm nội dung học, lên lịch lớp trực tiếp và cập nhật replay sau buổi học.
            </p>
          </div>

          <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.18em] text-white/55">Khóa học</p>
                <p class="mb-0 text-sm font-semibold text-white/90">Tạo draft và gửi duyệt khi đã đủ nội dung.</p>
              </div>
              <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.18em] text-white/55">Lớp trực tiếp</p>
                <p class="mb-0 text-sm font-semibold text-white/90">Lên lịch lớp trực tiếp bằng link ngoài, không dùng streaming nội bộ.</p>
              </div>
              <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.18em] text-white/55">Xem lại</p>
                <p class="mb-0 text-sm font-semibold text-white/90">Gắn recording URL sau buổi học để học viên xem lại.</p>
              </div>
          </div>
        </div>
      </section>

      <section class="flex items-center bg-white px-5 py-8 sm:px-8 sm:py-10 lg:px-10 lg:py-12">
        <div class="mx-auto w-full max-w-md">
          <div class="mb-8">
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-primary/60">Giảng viên CTU E-Learning</p>
            <h2 class="mb-0 text-2xl font-black text-slate-900 sm:text-3xl">Đăng nhập giảng viên</h2>
            <p class="mb-0 mt-3 text-sm leading-relaxed text-slate-500">
              Chỉ tài khoản giảng viên đang hoạt động mới có thể truy cập khu vực quản lý khóa học và lớp trực tiếp.
            </p>
          </div>

          <form id="instructorLoginForm" class="space-y-5" novalidate>
            <div>
              <label for="insLogEmail" class="mb-2 block text-sm font-semibold text-slate-700">
                <i class="fas fa-envelope text-slate-400 mr-1"></i> Email
              </label>
              <input id="insLogEmail" type="email" name="insLogEmail" autocomplete="username" placeholder="Nhập email giảng viên" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
                <small class="mt-1 block text-xs text-slate-400">Ví dụ: chau.instructor@example.com</small>
            </div>

            <div>
              <label for="insLogPass" class="mb-2 block text-sm font-semibold text-slate-700">
                <i class="fas fa-key text-slate-400 mr-1"></i> Mật khẩu
              </label>
              <div class="relative">
                <input id="insLogPass" type="password" name="insLogPass" autocomplete="current-password" placeholder="Nhập mật khẩu" class="w-full rounded-2xl border border-slate-200 px-4 py-3 pr-12 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
                <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 border-0 bg-transparent p-1 text-slate-400 transition-colors hover:text-slate-600" id="toggleInstructorPass" aria-label="Hiện hoặc ẩn mật khẩu">
                  <i class="fas fa-eye" id="toggleInstructorPassIcon"></i>
                </button>
              </div>
              <small class="mt-1 block text-xs text-slate-400">Nhấn vào biểu tượng con mắt để xem mật khẩu.</small>
            </div>

            <button type="button" id="instructorLoginBtn" onclick="checkInstructorLogin()" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border-0 bg-primary px-5 py-3.5 text-sm font-extrabold text-white shadow-lg shadow-primary/20 transition hover:bg-primary/90">
              <i class="fas fa-sign-in-alt"></i>
              <span>Đăng nhập</span>
            </button>

            <div id="statusInstructorLogMsg" class="min-h-6 text-sm font-semibold"></div>
          </form>

          <div class="mt-8 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4 text-sm leading-relaxed text-slate-600">
            <p class="mb-0 font-semibold text-slate-800">Bạn sẽ làm được gì sau khi đăng nhập?</p>
            <ul class="mb-0 mt-3 space-y-2 pl-5 text-slate-600">
              <li>Tạo khóa học bản nháp và chỉnh sửa nội dung theo từng phần.</li>
                <li>Thêm nội dung học, lớp trực tiếp và đường dẫn replay.</li>
              <li>Gửi khóa học vào hàng chờ duyệt của admin.</li>
            </ul>
          </div>
        </div>
      </section>
    </div>
  </main>

  <script src="../js/instructorajaxrequest.js?v=1"></script>
  <script>
    (function () {
      const form = document.getElementById('instructorLoginForm');
      const toggleBtn = document.getElementById('toggleInstructorPass');
      const passInput = document.getElementById('insLogPass');
      const toggleIcon = document.getElementById('toggleInstructorPassIcon');
      if (!form) {
        return;
      }

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (typeof window.checkInstructorLogin === 'function') {
          window.checkInstructorLogin();
        }
      });

      form.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
          event.preventDefault();
          if (typeof window.checkInstructorLogin === 'function') {
            window.checkInstructorLogin();
          }
        }
      });

      if (toggleBtn && passInput && toggleIcon) {
        toggleBtn.addEventListener('click', function () {
          const isPassword = passInput.type === 'password';
          passInput.type = isPassword ? 'text' : 'password';
          toggleIcon.classList.toggle('fa-eye', !isPassword);
          toggleIcon.classList.toggle('fa-eye-slash', isPassword);
        });
      }
    })();
  </script>
</body>
</html>
