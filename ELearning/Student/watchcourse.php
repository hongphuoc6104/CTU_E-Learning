<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();

if(!isset($_SESSION['is_login'])){ echo "<script>location.href='../index.php';</script>"; exit; }

$stuEmail = $_SESSION['stuLogEmail'];
include('../dbConnection.php');

// Verify student owns this course
if(!isset($_GET['course_id'])) { echo "<script>location.href='myCourse.php';</script>"; exit; }
$course_id = (int)$_GET['course_id'];

$ownStmt = $conn->prepare("SELECT 1 FROM courseorder WHERE stu_email = ? AND course_id = ? AND status = 'TXN_SUCCESS' AND is_deleted = 0 LIMIT 1");
if(!$ownStmt) { echo "<script>location.href='myCourse.php';</script>"; exit; }
$ownStmt->bind_param('si', $stuEmail, $course_id);
$ownStmt->execute();
$own = $ownStmt->get_result();
if(!$own || $own->num_rows === 0) { $ownStmt->close(); echo "<script>location.href='myCourse.php';</script>"; exit; }
$ownStmt->close();

// Get course info
$courseStmt = $conn->prepare('SELECT * FROM course WHERE course_id = ? LIMIT 1');
if(!$courseStmt) { echo "<script>location.href='myCourse.php';</script>"; exit; }
$courseStmt->bind_param('i', $course_id);
$courseStmt->execute();
$courseResult = $courseStmt->get_result();
$course = $courseResult ? $courseResult->fetch_assoc() : null;
$courseStmt->close();
if(!$course) { echo "<script>location.href='myCourse.php';</script>"; exit; }

// Get lessons
$lessonStmt = $conn->prepare('SELECT * FROM lesson WHERE course_id = ? AND is_deleted = 0 ORDER BY lesson_id');
if(!$lessonStmt) { echo "<script>location.href='myCourse.php';</script>"; exit; }
$lessonStmt->bind_param('i', $course_id);
$lessonStmt->execute();
$lessons = $lessonStmt->get_result();

// Student info
$stuStmt = $conn->prepare('SELECT stu_name, stu_img FROM student WHERE stu_email = ? AND is_deleted = 0 LIMIT 1');
$stu = [];
if($stuStmt) {
    $stuStmt->bind_param('s', $stuEmail);
    $stuStmt->execute();
    $stuResult = $stuStmt->get_result();
    $stu = $stuResult ? ($stuResult->fetch_assoc() ?: []) : [];
    $stuStmt->close();
}
$stuImg = ltrim(str_replace('../', '', $stu['stu_img'] ?? ''), '/');

// Helper: convert any YouTube URL → embed URL
function getYTEmbedUrl($url) {
    $id = '';
    if (str_contains($url, 'youtube.com/embed/')) { $p = explode('youtube.com/embed/', $url); $id = explode('?', $p[1])[0]; }
    elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_\-]+)/', $url, $m)) $id = $m[1];
    elseif (preg_match('/[?&]v=([a-zA-Z0-9_\-]+)/', $url, $m)) $id = $m[1];
    elseif (preg_match('/youtube\.com\/shorts\/([a-zA-Z0-9_\-]+)/', $url, $m)) $id = $m[1];
    
    if ($id) return 'https://www.youtube.com/embed/'.$id.'?enablejsapi=1&rel=0';
    return null;
}
function isYouTube($url) {
    return str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be');
}

$firstLink  = '';
$firstName  = '';
$firstIsYT  = false;
$firstEmbed = '';
if ($lessons->num_rows > 0) {
    $lessons->data_seek(0);
    $tmp = $lessons->fetch_assoc();
    $lessons->data_seek(0);
    $firstLink  = $tmp['lesson_link'] ?? '';
    $firstName  = $tmp['lesson_name'] ?? '';
    $firstIsYT  = isYouTube($firstLink);
    $firstEmbed = $firstIsYT ? getYTEmbedUrl($firstLink) : '';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($course['course_name']); ?> — CTU E-Learning</title>
  <link rel="stylesheet" href="../css/tailwind.css">
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
            $lessons->data_seek(0);
            $idx = 0;
            while($l = $lessons->fetch_assoc()):
              $idx++;
              $isYT = isYouTube($l['lesson_link']);
              $embedUrl = $isYT ? getYTEmbedUrl($l['lesson_link']) : $l['lesson_link'];
      ?>
      <li class="lesson-item px-4 py-3 <?php echo $idx===1?'active':''; ?>"
          data-url="<?php echo htmlspecialchars($embedUrl); ?>"
          data-name="<?php echo htmlspecialchars($l['lesson_name']); ?>"
          data-type="<?php echo $isYT ? 'youtube' : 'local'; ?>">
        <div class="flex items-start gap-3">
          <div class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center shrink-0 mt-0.5">
            <?php if($isYT): ?>
            <i class="fab fa-youtube text-red-400 text-xs lesson-icon"></i>
            <?php else: ?>
            <i class="fas fa-play text-white/50 text-xs lesson-icon"></i>
            <?php endif; ?>
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
    <!-- Player wrapper -->
    <div class="relative bg-black flex-grow flex items-center justify-center" id="playerWrap">
      <!-- YouTube iframe (hidden by default unless first is YT) -->
      <iframe id="ytPlayer"
              class="w-full h-full <?php echo $firstIsYT ? '' : 'hidden'; ?>"
              src="<?php echo htmlspecialchars($firstEmbed); ?>"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowfullscreen>
      </iframe>

      <!-- Local video tag -->
      <video id="localPlayer" controls
             class="max-h-full max-w-full w-full <?php echo $firstIsYT ? 'hidden' : ''; ?>"
             src="<?php echo !$firstIsYT ? htmlspecialchars($firstLink) : ''; ?>">
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
        <?php echo htmlspecialchars($firstName ?: 'Chọn bài học để bắt đầu'); ?>
      </h3>
      <p class="text-white/40 text-xs mt-1"><?php echo htmlspecialchars($course['course_name']); ?></p>
    </div>
  </main>
</div>

<script src="https://www.youtube.com/iframe_api"></script>
<script>
  const items      = document.querySelectorAll('.lesson-item');
  const ytPlayerEl = document.getElementById('ytPlayer');
  const localPlayer= document.getElementById('localPlayer');
  const titleEl    = document.getElementById('lessonTitle');

  // Func to auto play next
  function playNextLesson() {
      let nextItem = null;
      let foundCurrent = false;
      items.forEach(i => {
          if (foundCurrent && !nextItem) nextItem = i;
          if (i.classList.contains('active')) foundCurrent = true;
      });
      if (nextItem) nextItem.click();
  }

  // Local Video End Event
  localPlayer.addEventListener('ended', playNextLesson);

  // YouTube API Hook
  let ytPlayerObj;
  function onYouTubeIframeAPIReady() {
      ytPlayerObj = new YT.Player('ytPlayer', {
          events: {
              'onStateChange': function(event) {
                  if (event.data === YT.PlayerState.ENDED) {
                      playNextLesson();
                  }
              }
          }
      });
  }

  items.forEach(item => {
    item.addEventListener('click', () => {
      // Active state
      items.forEach(i => i.classList.remove('active'));
      item.classList.add('active');

      let url  = item.dataset.url;
      const name = item.dataset.name;
      const type = item.dataset.type; // 'youtube' or 'local'

      titleEl.textContent = name;

      if (type === 'youtube') {
        // Ensure enablejsapi=1 is in URL if it isn't
        if (!url.includes('enablejsapi=1')) {
            url += (url.includes('?') ? '&' : '?') + 'enablejsapi=1';
        }
        
        localPlayer.pause();
        localPlayer.src = '';
        localPlayer.classList.add('hidden');
        
        ytPlayerEl.src = url;
        ytPlayerEl.classList.remove('hidden');
      } else {
        // Show local video, hide iframe
        ytPlayerEl.src = '';
        ytPlayerEl.classList.add('hidden');
        localPlayer.src = url;
        localPlayer.classList.remove('hidden');
        localPlayer.load();
        localPlayer.play().catch(()=>{});
      }
    });
  });
</script>

<?php if(isset($lessonStmt) && $lessonStmt) { $lessonStmt->close(); } ?>
</body>
</html>
