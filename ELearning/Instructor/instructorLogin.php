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
  <title>Dang nhap giang vien - CTU E-Learning</title>
  <link rel="stylesheet" href="../css/tailwind.css">
  <script defer src="../js/all.min.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      background: radial-gradient(circle at top, rgba(0, 51, 102, 0.12), transparent 55%), #f8fafc;
    }
  </style>
</head>
<body class="text-slate-900">
  <main class="mx-auto flex min-h-screen w-full max-w-5xl items-center justify-center px-4 py-10">
    <div class="grid w-full overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-200/50 md:grid-cols-2">
      <section class="relative hidden overflow-hidden bg-primary p-8 text-white md:flex md:flex-col md:justify-between">
        <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/15 blur-2xl"></div>
        <div class="absolute -bottom-20 -left-20 h-72 w-72 rounded-full bg-emerald-400/20 blur-3xl"></div>
        <div class="relative z-10">
          <div class="mb-5 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
            <i class="fas fa-chalkboard-teacher"></i>
          </div>
          <p class="m-0 text-xs font-bold uppercase tracking-[0.2em] text-white/70">Instructor Portal</p>
          <h1 class="m-0 mt-3 text-3xl font-black leading-tight">Quan ly khoa hoc va lop live</h1>
          <p class="m-0 mt-4 text-sm leading-relaxed text-white/80">
            Tao noi dung khoa hoc, sap xep section, dat lich live session va cap nhat replay sau buoi hoc.
          </p>
        </div>
        <ul class="relative z-10 m-0 mt-10 list-none space-y-3 p-0 text-sm text-white/85">
          <li class="flex items-center gap-2"><i class="fas fa-check-circle text-emerald-200"></i> Tao khoa hoc o trang thai draft</li>
          <li class="flex items-center gap-2"><i class="fas fa-check-circle text-emerald-200"></i> Gui khoa hoc vao pipeline review</li>
          <li class="flex items-center gap-2"><i class="fas fa-check-circle text-emerald-200"></i> Quan ly live session bang link ngoai</li>
        </ul>
      </section>

      <section class="p-7 sm:p-10">
        <a href="../index.php" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-primary no-underline">
          <i class="fas fa-arrow-left text-xs"></i>
          <span>Ve trang chu</span>
        </a>

        <h2 class="m-0 mt-5 text-2xl font-black text-slate-900">Dang nhap giang vien</h2>
        <p class="m-0 mt-2 text-sm text-slate-500">Chi tai khoan instructor dang hoat dong moi co the truy cap khu vuc nay.</p>

        <form id="instructorLoginForm" class="mt-7 space-y-5" novalidate>
          <div>
            <label for="insLogEmail" class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
            <input id="insLogEmail" type="email" name="insLogEmail" autocomplete="username" placeholder="example@domain.com" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
          </div>
          <div>
            <label for="insLogPass" class="mb-2 block text-sm font-semibold text-slate-700">Mat khau</label>
            <input id="insLogPass" type="password" name="insLogPass" autocomplete="current-password" placeholder="Nhap mat khau" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
          </div>

          <button type="button" id="instructorLoginBtn" onclick="checkInstructorLogin()" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border-0 bg-primary px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-primary/20 transition hover:bg-primary/90">
            <i class="fas fa-sign-in-alt"></i>
            <span>Dang nhap</span>
          </button>

          <div id="statusInstructorLogMsg" class="min-h-6 text-sm font-semibold"></div>
        </form>
      </section>
    </div>
  </main>

  <script src="../js/instructorajaxrequest.js?v=1"></script>
  <script>
    (function () {
      const form = document.getElementById('instructorLoginForm');
      if (!form) {
        return;
      }

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (typeof window.checkInstructorLogin === 'function') {
          window.checkInstructorLogin();
        }
      });
    })();
  </script>
</body>
</html>
