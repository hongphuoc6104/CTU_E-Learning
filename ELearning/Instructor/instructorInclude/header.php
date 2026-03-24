<?php

require_once(__DIR__ . '/auth.php');

instructor_require_login();
$instructorProfile = instructor_current_profile($conn);
if (!$instructorProfile) {
    header('Location: instructorLogin.php');
    exit;
}

$pageTitle = defined('TITLE') ? TITLE : 'Instructor';
$currentPage = defined('PAGE') ? PAGE : '';
$flash = instructor_get_flash();

$navLinks = [
    ['page' => 'dashboard', 'href' => 'instructorDashboard.php', 'label' => 'Tổng quan', 'icon' => 'fa-chart-pie'],
    ['page' => 'courses', 'href' => 'courses.php', 'label' => 'Khóa học', 'icon' => 'fa-layer-group'],
    ['page' => 'add-course', 'href' => 'addCourse.php', 'label' => 'Tạo khóa học', 'icon' => 'fa-plus-circle'],
    ['page' => 'sections', 'href' => 'sections.php', 'label' => 'Mục học', 'icon' => 'fa-list-ol'],
    ['page' => 'items', 'href' => 'learningItems.php', 'label' => 'Nội dung học', 'icon' => 'fa-book-open'],
    ['page' => 'live', 'href' => 'liveSessions.php', 'label' => 'Lớp trực tiếp', 'icon' => 'fa-video'],
    ['page' => 'students', 'href' => 'students.php', 'label' => 'Học viên', 'icon' => 'fa-users'],
];

$flashMeta = [
    'success' => ['class' => 'border-emerald-200 bg-emerald-50 text-emerald-700', 'icon' => 'fa-check-circle'],
    'error' => ['class' => 'border-red-200 bg-red-50 text-red-700', 'icon' => 'fa-exclamation-circle'],
    'warning' => ['class' => 'border-amber-200 bg-amber-50 text-amber-700', 'icon' => 'fa-exclamation-triangle'],
    'info' => ['class' => 'border-sky-200 bg-sky-50 text-sky-700', 'icon' => 'fa-info-circle'],
];

$resolvedFlashType = $flash ? (string) ($flash['type'] ?? 'info') : 'info';
$resolvedFlashMeta = $flashMeta[$resolvedFlashType] ?? $flashMeta['info'];

$profileName = (string) ($instructorProfile['ins_name'] ?? 'Instructor');
$profileEmail = (string) ($instructorProfile['ins_email'] ?? '');
$profileImg = ltrim(str_replace('../', '', (string) ($instructorProfile['ins_img'] ?? '')), '/');
if ($profileImg === '') {
    $profileImg = 'image/stu/default_avatar.png';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> - CTU Instructor</title>
  <link rel="stylesheet" href="../css/tailwind.css">
  <script defer src="../js/all.min.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #f8fafc;
    }
    .instructor-nav-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border-radius: 10px;
      padding: 8px 12px;
      color: #475569;
      font-size: 13px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.15s ease;
      white-space: nowrap;
    }
    .instructor-nav-link:hover {
      background: rgba(0, 51, 102, 0.08);
      color: #003366;
    }
    .instructor-nav-link.active {
      background: #003366;
      color: #ffffff;
      box-shadow: 0 10px 24px rgba(0, 51, 102, 0.22);
    }
  </style>
</head>
<body>
<div class="min-h-screen bg-slate-50">
  <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex w-full max-w-7xl items-center gap-4 px-4 py-3 md:px-6">
      <a href="instructorDashboard.php" class="flex items-center gap-3 no-underline">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white shadow-md shadow-primary/25">
          <i class="fas fa-chalkboard-teacher"></i>
        </div>
        <div>
          <p class="m-0 text-sm font-extrabold leading-none text-slate-900">CTU Instructor</p>
          <p class="m-0 mt-1 text-xs font-medium text-slate-500">Quản lý khóa học và lớp live</p>
        </div>
      </a>

      <div class="ml-auto hidden items-center gap-3 md:flex">
        <img src="../<?php echo htmlspecialchars($profileImg, ENT_QUOTES, 'UTF-8'); ?>" alt="Instructor avatar" class="h-9 w-9 rounded-full border border-slate-200 object-cover" onerror="this.onerror=null;this.src='../image/stu/default_avatar.png'">
        <div class="min-w-0 text-right">
          <p class="m-0 truncate text-sm font-bold text-slate-700"><?php echo htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8'); ?></p>
          <p class="m-0 truncate text-xs text-slate-400"><?php echo htmlspecialchars($profileEmail, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <a href="logout.php" class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-600 transition hover:bg-red-100 no-underline">
          <i class="fas fa-sign-out-alt"></i>
          <span>Đăng xuất</span>
        </a>
      </div>
    </div>

    <div class="border-t border-slate-100 bg-white">
      <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-3 overflow-x-auto px-4 py-2 md:px-6">
        <nav class="flex items-center gap-2">
          <?php foreach ($navLinks as $item): ?>
            <a href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>" class="instructor-nav-link <?php echo $currentPage === $item['page'] ? 'active' : ''; ?>">
              <i class="fas <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?> text-xs"></i>
              <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
          <?php endforeach; ?>
        </nav>
        <a href="../index.php" class="inline-flex items-center gap-2 whitespace-nowrap rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 no-underline">
          <i class="fas fa-external-link-alt"></i>
          <span>Trang học viên</span>
        </a>
      </div>

      <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-3 px-4 pb-2 md:hidden">
        <div class="flex min-w-0 items-center gap-2">
          <img src="../<?php echo htmlspecialchars($profileImg, ENT_QUOTES, 'UTF-8'); ?>" alt="Instructor avatar" class="h-8 w-8 rounded-full border border-slate-200 object-cover" onerror="this.onerror=null;this.src='../image/stu/default_avatar.png'">
          <div class="min-w-0">
            <p class="m-0 truncate text-xs font-bold text-slate-700"><?php echo htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="m-0 truncate text-[11px] text-slate-400"><?php echo htmlspecialchars($profileEmail, ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
        </div>
        <a href="logout.php" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-[11px] font-bold text-red-600 transition hover:bg-red-100 no-underline">
          <i class="fas fa-sign-out-alt"></i>
          <span>Đăng xuất</span>
        </a>
      </div>
    </div>
  </header>

  <main class="mx-auto w-full max-w-7xl px-4 py-6 md:px-6">
    <?php if ($flash): ?>
      <div class="mb-6 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm font-semibold <?php echo $resolvedFlashMeta['class']; ?>">
        <i class="fas <?php echo htmlspecialchars($resolvedFlashMeta['icon'], ENT_QUOTES, 'UTF-8'); ?> mt-0.5"></i>
        <span><?php echo htmlspecialchars((string) ($flash['text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    <?php endif; ?>
