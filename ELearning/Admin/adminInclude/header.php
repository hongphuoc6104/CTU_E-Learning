<?php
// Start session if not started
if(!isset($_SESSION)) session_start();

// Redirect if not admin
if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>";
}
$adminEmail = $_SESSION['adminLogEmail'] ?? '';
$adminName  = explode('@', $adminEmail)[0] ?? 'Admin';

$pageTitle = defined('TITLE') ? TITLE : 'Admin';
$currentPage = defined('PAGE') ? PAGE : '';

// Include DB connection so $conn is available for sidebar badge
if(!isset($conn)) include(dirname(__DIR__, 1).'/../dbConnection.php');
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
            accentbg: '#064e3b',
          },
          fontFamily: { sans: ['Inter', 'sans-serif'] }
        }
      }
    }
  </script>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Font Awesome -->
  <script defer src="../js/all.min.js"></script>

  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; background: #f5f7f8; min-height: 100vh; }

    /* Sidebar link */
    .nav-link {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 14px; border-radius: 10px;
      font-size: .82rem; font-weight: 600; letter-spacing:.01em;
      color: rgba(255,255,255,.50); transition: all .18s ease;
      text-decoration: none; position: relative; overflow: hidden;
    }
    .nav-link:hover { color: #fff; background: rgba(255,255,255,.08); }
    .nav-link.active { color: #10b981; background: rgba(16,185,129,.15); }
    .nav-link.active::before {
      content:''; position:absolute; left:0; top:20%; bottom:20%;
      width:3px; border-radius:0 4px 4px 0; background:#10b981;
    }
    .nav-link i { width: 16px; text-align: center; font-size: .82rem; flex-shrink:0; }
    .nav-section { font-size:.65rem; font-weight:700; letter-spacing:.1em;
      text-transform:uppercase; color:rgba(255,255,255,.25); padding: 12px 14px 4px; }

    /* Card glow */
    .stat-card { position:relative; overflow:hidden; }
    .stat-card::after { content:''; position:absolute; inset:0; background:linear-gradient(135deg,rgba(255,255,255,.04),transparent); pointer-events:none; }

    @media print {
      aside, header { display: none !important; }
      .ml-64 { margin-left: 0 !important; }
      main { padding: 0 !important; }
    }
  </style>
</head>
<body>

<!-- LAYOUT -->
<div class="flex min-h-screen">

  <!-- ===== SIDEBAR ===== -->
  <aside class="w-64 bg-sidebar fixed top-0 left-0 h-screen flex flex-col z-40 shrink-0 print:hidden"
         style="background: linear-gradient(180deg,#001f40 0%,#002a55 100%); border-right:1px solid rgba(16,185,129,.12);">

    <!-- Brand -->
    <a href="adminDashboard.php" class="flex items-center gap-3 px-5 py-5 no-underline group"
       style="border-bottom:1px solid rgba(16,185,129,.12);">
      <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
           style="background:linear-gradient(135deg,#003366,#005299);">
        <i class="fas fa-graduation-cap text-white text-sm"></i>
      </div>
      <div>
        <p class="text-white font-extrabold text-sm leading-none tracking-tight">CTU E-Learning</p>
        <p class="text-xs mt-0.5" style="color:rgba(16,185,129,.7);">Admin Dashboard</p>
      </div>
    </a>

    <!-- Nav -->
    <nav class="flex-grow px-3 py-3 overflow-y-auto space-y-0.5">
      <p class="nav-section">Tổng quan</p>
      <a href="adminDashboard.php" class="nav-link <?php echo ($currentPage=='dashboard')?'active':''; ?>">
        <i class="fas fa-chart-pie"></i> Bảng điều khiển
      </a>

      <p class="nav-section">Quản lý nội dung</p>
      <a href="courses.php" class="nav-link <?php echo ($currentPage=='courses')?'active':''; ?>">
        <i class="fas fa-layer-group"></i> Khoá học
      </a>
      <a href="lessons.php" class="nav-link <?php echo ($currentPage=='lessons')?'active':''; ?>">
        <i class="fas fa-play-circle"></i> Bài học
      </a>

      <p class="nav-section">Người dùng & Phân tích</p>
      <a href="students.php" class="nav-link <?php echo ($currentPage=='students')?'active':''; ?>">
        <i class="fas fa-users"></i> Học viên
      </a>
      <a href="sellReport.php" class="nav-link <?php echo ($currentPage=='sellreport')?'active':''; ?>">
        <i class="fas fa-chart-line"></i> Doanh thu
      </a>
      <a href="feedback.php" class="nav-link <?php echo ($currentPage=='feedback')?'active':''; ?>">
        <i class="fas fa-comment-dots"></i> Đánh giá
      </a>
      <a href="contacts.php" class="nav-link <?php echo ($currentPage=='contacts')?'active':''; ?>">
        <i class="fas fa-envelope-open-text"></i> Hộp thư liên hệ
      </a>
      <?php
        // Trash count badge - only if $conn available
        $trashCount = 0;
        if(isset($conn)) {
            foreach(['course','lesson','student','feedback','courseorder','contact_message'] as $tbl) {
                $r = $conn->query("SELECT COUNT(*) as c FROM `$tbl` WHERE is_deleted=1");
                if($r) $trashCount += $r->fetch_assoc()['c'];
            }
        }
      ?>
      <a href="trash.php" class="nav-link <?php echo ($currentPage=='trash')?'active':''; ?>" style="color:rgba(239,68,68,.6);"
         onmouseover="this.style.color='#f87171';this.style.background='rgba(239,68,68,.07)'"
         onmouseout="this.style.color='rgba(239,68,68,.6)';this.style.background='transparent'"
         >
        <i class="fas fa-trash-alt"></i>
        <span>Thùng rác</span>
        <?php if($trashCount > 0): ?>
        <span class="ml-auto text-xs font-bold px-1.5 py-0.5 rounded-full bg-red-500/20 text-red-400"><?php echo $trashCount; ?></span>
        <?php endif; ?>
      </a>

      <div style="border-top:1px solid rgba(255,255,255,.06); margin: 10px 0;"></div>

      <p class="nav-section">Tài khoản</p>
      <a href="adminChangePass.php" class="nav-link <?php echo ($currentPage=='changepass')?'active':''; ?>">
        <i class="fas fa-lock"></i> Đổi mật khẩu
      </a>
      <a href="../logout.php" class="nav-link" style="color:rgba(239,68,68,.6);"
         onmouseover="this.style.color='#f87171';this.style.background='rgba(239,68,68,.1)'"
         onmouseout="this.style.color='rgba(239,68,68,.6)';this.style.background='transparent'">
        <i class="fas fa-sign-out-alt"></i> Đăng xuất
      </a>
    </nav>

    <!-- Admin profile -->
    <div style="border-top:1px solid rgba(16,185,129,.12);" class="px-4 py-4">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
             style="background:rgba(16,185,129,.15);">
          <i class="fas fa-user-shield text-xs" style="color:#10b981;"></i>
        </div>
        <div class="min-w-0">
          <p class="text-white text-xs font-bold truncate"><?php echo htmlspecialchars(ucfirst($adminName)); ?></p>
          <p class="text-xs truncate" style="color:rgba(16,185,129,.5);"><?php echo htmlspecialchars($adminEmail); ?></p>
        </div>
        <div class="ml-auto w-2 h-2 rounded-full bg-emerald-400 shrink-0" title="Online"></div>
      </div>
    </div>
  </aside>

  <!-- ===== MAIN CONTENT AREA ===== -->
  <div class="ml-64 flex-grow flex flex-col min-h-screen print:ml-0">

    <!-- Top bar -->
    <header class="bg-white/80 backdrop-blur-md sticky top-0 z-30 print:hidden"
            style="border-bottom:1px solid rgba(79,70,229,.08);">
      <div class="px-8 py-3.5 flex items-center justify-between">
        <div>
          <h1 class="text-base font-bold text-slate-800 leading-none"><?php echo $pageTitle; ?></h1>
          <p class="text-xs text-slate-400 mt-0.5"><?php echo date('l, d/m/Y'); ?></p>
        </div>
        <div class="flex items-center gap-3">
          <a href="../index.php" target="_blank"
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:text-primary hover:bg-primary/5 transition-colors">
            <i class="fas fa-external-link-alt text-xs"></i> Xem trang học viên
          </a>
        </div>
      </div>
    </header>

    <!-- Page content -->
    <main class="flex-grow p-8">