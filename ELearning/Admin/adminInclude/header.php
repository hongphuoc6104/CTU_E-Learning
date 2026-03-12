<?php
// Start session if not started
if(!isset($_SESSION)) session_start();

// Redirect if not admin
if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>";
}
$adminEmail = $_SESSION['adminLogEmail'] ?? '';
$adminName  = explode('@', $adminEmail)[0] ?? 'Admin';

// Cart count not needed for admin, just page title + current PAGE constant
$pageTitle = defined('TITLE') ? TITLE : 'Admin';
$currentPage = defined('PAGE') ? PAGE : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $pageTitle; ?> — CTU Admin</title>

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary:  '#003366',
            sidebar:  '#001f40',
            accent:   '#10b981',
          },
          fontFamily: { sans: ['Inter', 'sans-serif'] }
        }
      }
    }
  </script>

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome SVG (no webfonts needed) -->
  <script defer src="../js/all.min.js"></script>

  <style>
    body { font-family: 'Inter', sans-serif; background:#f5f7f8; }
    .sidebar-link { display:flex; align-items:center; gap:10px; padding:11px 20px; border-radius:10px; font-size:.875rem; font-weight:500; color:#94a3b8; transition:all .18s; }
    .sidebar-link:hover { background:rgba(255,255,255,.08); color:#fff; text-decoration:none; }
    .sidebar-link.active { background:rgba(16,185,129,.15); color:#10b981; }
    .sidebar-link i { width:18px; text-align:center; font-size:.9rem; }
  </style>
</head>
<body>

<!-- ===== LAYOUT WRAPPER ===== -->
<div class="flex min-h-screen">

  <!-- ===== SIDEBAR ===== -->
  <aside class="w-60 bg-sidebar fixed top-0 left-0 h-screen flex flex-col z-40 shrink-0">
    <!-- Logo -->
    <a href="adminDashboard.php" class="flex items-center gap-3 px-6 py-5 border-b border-white/10 hover:opacity-90 transition-opacity no-underline">
      <div class="w-8 h-8 bg-accent rounded-lg flex items-center justify-center">
        <i class="fas fa-graduation-cap text-white text-sm"></i>
      </div>
      <div>
        <p class="text-white font-bold text-sm leading-none">CTU Admin</p>
        <p class="text-white/40 text-xs mt-0.5">Quản trị hệ thống</p>
      </div>
    </a>

    <!-- Nav -->
    <nav class="flex-grow px-3 py-4 space-y-0.5 overflow-y-auto">
      <a href="adminDashboard.php" class="sidebar-link <?php echo ($currentPage=='dashboard')?'active':''; ?>">
        <i class="fas fa-tachometer-alt"></i> Bảng điều khiển
      </a>
      <a href="courses.php" class="sidebar-link <?php echo ($currentPage=='courses')?'active':''; ?>">
        <i class="fas fa-book"></i> Khoá học
      </a>
      <a href="lessons.php" class="sidebar-link <?php echo ($currentPage=='lessons')?'active':''; ?>">
        <i class="fas fa-play-circle"></i> Bài học
      </a>
      <a href="students.php" class="sidebar-link <?php echo ($currentPage=='students')?'active':''; ?>">
        <i class="fas fa-users"></i> Học viên
      </a>
      <a href="sellReport.php" class="sidebar-link <?php echo ($currentPage=='sellreport')?'active':''; ?>">
        <i class="fas fa-chart-bar"></i> Doanh thu
      </a>
      <a href="feedback.php" class="sidebar-link <?php echo ($currentPage=='feedback')?'active':''; ?>">
        <i class="fas fa-star"></i> Đánh giá
      </a>
      <div class="border-t border-white/10 mt-3 pt-3"></div>
      <a href="adminChangePass.php" class="sidebar-link <?php echo ($currentPage=='changepass')?'active':''; ?>">
        <i class="fas fa-key"></i> Đổi mật khẩu
      </a>
      <a href="../logout.php" class="sidebar-link hover:!text-red-400">
        <i class="fas fa-sign-out-alt"></i> Đăng xuất
      </a>
    </nav>

    <!-- Admin info -->
    <div class="px-4 py-4 border-t border-white/10">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-accent/20 flex items-center justify-center shrink-0">
          <i class="fas fa-user-shield text-accent text-xs"></i>
        </div>
        <div class="min-w-0">
          <p class="text-white text-xs font-semibold truncate"><?php echo htmlspecialchars($adminName); ?></p>
          <p class="text-white/40 text-xs truncate"><?php echo htmlspecialchars($adminEmail); ?></p>
        </div>
      </div>
    </div>
  </aside>

  <!-- ===== MAIN CONTENT AREA ===== -->
  <div class="ml-60 flex-grow flex flex-col min-h-screen">
    <!-- Top bar -->
    <header class="bg-white border-b border-slate-200 px-8 py-4 flex items-center justify-between sticky top-0 z-30">
      <h1 class="text-lg font-bold text-slate-800"><?php echo $pageTitle; ?></h1>
      <div class="flex items-center gap-3 text-sm text-slate-500">
        <i class="fas fa-clock text-xs"></i>
        <?php echo date('d/m/Y'); ?>
      </div>
    </header>

    <!-- Page content -->
    <main class="flex-grow p-8">