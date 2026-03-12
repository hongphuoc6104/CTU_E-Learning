<?php
if(!isset($_SESSION)) session_start();
if(!isset($_SESSION['is_login'])){ echo "<script>location.href='../index.php';</script>"; exit; }

$stuEmail = $_SESSION['stuLogEmail'];
include('../dbConnection.php');

// Verify student owns this course
if(!isset($_GET['course_id'])) { echo "<script>location.href='myCourse.php';</script>"; exit; }
$course_id = (int)$_GET['course_id'];

$own = $conn->query("SELECT 1 FROM courseorder WHERE stu_email='$stuEmail' AND course_id=$course_id LIMIT 1");
if($own->num_rows === 0) { echo "<script>location.href='myCourse.php';</script>"; exit; }

// Get course info
$course = $conn->query("SELECT * FROM course WHERE course_id=$course_id")->fetch_assoc();
if(!$course) { echo "<script>location.href='myCourse.php';</script>"; exit; }

// Get lessons
$lessons = $conn->query("SELECT * FROM lesson WHERE course_id=$course_id ORDER BY lesson_id");

// Student info
$stu = $conn->query("SELECT stu_name, stu_img FROM student WHERE stu_email='$stuEmail'")->fetch_assoc();
$stuImg = ltrim(str_replace('../', '', $stu['stu_img'] ?? ''), '/');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($course['course_name']); ?> — CTU E-Learning</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <script>
    tailwind.config = {
      theme: { extend: { colors: { primary: '#003366', accent: '#10b981' }, fontFamily: { sans: ['Inter','sans-serif'] } } }
    }
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script defer src="../js/all.min.js"></script>
  <style>
    body { font-family: 'Inter', sans-serif; background:#0f172a; }
    .lesson-item { cursor:pointer; border-left:3px solid transparent; transition:all .18s; }
    .lesson-item:hover { background:rgba(255,255,255,.05); }
    .lesson-item.active { border-left-color:#10b981; background:rgba(16,185,129,.1); }
  </style>
</head>
<body class="min-h-screen">

<!-- Top bar -->
<header class="bg-primary border-b border-white/10 px-5 py-3 flex items-center justify-between">
  <div class="flex items-center gap-3">
    <a href="myCourse.php" class="text-white/60 hover:text-white transition text-sm flex items-center gap-2">
      <i class="fas fa-arrow-left text-xs"></i> Khoá học của tôi
    </a>
    <span class="text-white/20">|</span>
    <span class="text-white font-semibold text-sm truncate max-w-xs"><?php echo htmlspecialchars($course['course_name']); ?></span>
  </div>
  <div class="flex items-center gap-3">
    <img src="../<?php echo $stuImg ?: 'image/stu/student1.jpg'; ?>"
         onerror="this.onerror=null;this.src='../image/stu/student1.jpg'"
         class="w-8 h-8 rounded-full object-cover border-2 border-white/20">
    <span class="text-white/80 text-sm hidden sm:block"><?php echo htmlspecialchars($stu['stu_name'] ?? ''); ?></span>
  </div>
</header>

<!-- Main layout -->
<div class="flex h-[calc(100vh-56px)]">

  <!-- Sidebar: playlist -->
  <aside class="w-72 bg-slate-900 border-r border-white/5 flex flex-col overflow-hidden shrink-0">
    <div class="px-4 py-4 border-b border-white/10">
      <h2 class="text-white font-bold text-sm">Danh sách bài học</h2>
      <p class="text-white/40 text-xs mt-0.5">
        <i class="fas fa-play-circle mr-1"></i><?php echo $lessons->num_rows; ?> bài học
      </p>
    </div>
    <ul class="flex-grow overflow-y-auto py-2" id="playlist">
      <?php if($lessons->num_rows > 0):
            $first = null;
            $lessons->data_seek(0);
            $idx = 0;
            while($l = $lessons->fetch_assoc()):
              if($idx === 0) $first = $l;
              $idx++;
      ?>
      <li class="lesson-item px-4 py-3 <?php echo $idx===1?'active':''; ?>"
          data-url="<?php echo htmlspecialchars($l['lesson_link']); ?>"
          data-name="<?php echo htmlspecialchars($l['lesson_name']); ?>">
        <div class="flex items-start gap-3">
          <div class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center shrink-0 mt-0.5">
            <i class="fas fa-play text-white/50 text-xs lesson-icon"></i>
          </div>
          <div class="min-w-0">
            <p class="text-white/80 text-sm font-medium leading-snug truncate"><?php echo htmlspecialchars($l['lesson_name']); ?></p>
            <?php if($l['lesson_desc']): ?>
            <p class="text-white/30 text-xs mt-0.5 truncate"><?php echo htmlspecialchars(substr($l['lesson_desc'],0,50)); ?></p>
            <?php endif; ?>
          </div>
        </div>
      </li>
      <?php endwhile; else: ?>
      <li class="px-4 py-8 text-center text-white/30 text-sm">Chưa có bài học nào.</li>
      <?php endif; ?>
    </ul>
  </aside>

  <!-- Video area -->
  <main class="flex-grow flex flex-col bg-slate-950">
    <!-- Video player -->
    <div class="relative bg-black flex-grow flex items-center justify-center">
      <video id="videoarea" controls
             class="max-h-full max-w-full w-full"
             src="<?php echo htmlspecialchars($first['lesson_link'] ?? ''); ?>">
        Trình duyệt của bạn không hỗ trợ video HTML5.
      </video>
      <!-- Empty state -->
      <?php if($lessons->num_rows === 0): ?>
      <div class="absolute inset-0 flex flex-col items-center justify-center text-white/30">
        <i class="fas fa-play-circle text-5xl mb-4"></i>
        <p class="text-lg">Chưa có bài học nào trong khoá học này.</p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Lesson info bar -->
    <div class="bg-slate-900 border-t border-white/10 px-6 py-4">
      <h3 id="lessonTitle" class="text-white font-semibold text-sm">
        <?php echo htmlspecialchars($first['lesson_name'] ?? 'Chọn bài học để bắt đầu'); ?>
      </h3>
      <p class="text-white/40 text-xs mt-1"><?php echo htmlspecialchars($course['course_name']); ?></p>
    </div>
  </main>
</div>

<script>
  const items    = document.querySelectorAll('.lesson-item');
  const videoEl  = document.getElementById('videoarea');
  const titleEl  = document.getElementById('lessonTitle');

  items.forEach(item => {
    item.addEventListener('click', () => {
      // Update active state
      items.forEach(i => { i.classList.remove('active'); });
      item.classList.add('active');

      const url  = item.dataset.url;
      const name = item.dataset.name;

      // Update video
      videoEl.src = url;
      videoEl.load();
      videoEl.play().catch(()=>{});

      // Update title
      titleEl.textContent = name;
    });
  });
</script>

<script src="../js/jquery.min.js"></script>
</body>
</html>