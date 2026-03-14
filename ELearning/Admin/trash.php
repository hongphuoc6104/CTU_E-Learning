<?php
if(!isset($_SESSION)) session_start();
define('TITLE', 'Thùng rác');
define('PAGE', 'trash');
include('./adminInclude/header.php');
include('../dbConnection.php');

if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>"; exit;
}

// ===== ACTIONS =====
$action = $_POST['action'] ?? '';
$type   = $_POST['type']   ?? '';
$id     = (int)($_POST['id'] ?? 0);

if($action && $type && $id) {
    $map = [
        'course'      => ['table' => 'course',      'pk' => 'course_id'],
        'lesson'      => ['table' => 'lesson',       'pk' => 'lesson_id'],
        'student'     => ['table' => 'student',      'pk' => 'stu_id'],
        'feedback'    => ['table' => 'feedback',     'pk' => 'f_id'],
        'courseorder' => ['table' => 'courseorder',  'pk' => 'co_id'],
        'contact'     => ['table' => 'contact_message','pk'=> 'c_id'],
    ];
    if(isset($map[$type])) {
        $t  = $map[$type]['table'];
        $pk = $map[$type]['pk'];
        if($action === 'restore') {
            $conn->query("UPDATE `$t` SET is_deleted=0 WHERE `$pk`=$id");
        } elseif($action === 'permanent') {
            $conn->query("DELETE FROM `$t` WHERE `$pk`=$id");
        }
    }
    echo "<script>location.href='trash.php';</script>"; exit;
}

// ===== FETCH deleted records =====
$deleted_courses  = $conn->query("SELECT course_id as id, course_name as name, 'course' as type FROM course WHERE is_deleted=1 ORDER BY course_id DESC");
$deleted_lessons  = $conn->query("SELECT lesson_id as id, lesson_name as name, 'lesson' as type FROM lesson WHERE is_deleted=1 ORDER BY lesson_id DESC");
$deleted_students = $conn->query("SELECT stu_id as id, CONCAT(stu_name,' (',stu_email,')') as name, 'student' as type FROM student WHERE is_deleted=1 ORDER BY stu_id DESC");
$deleted_feedback = $conn->query("SELECT f_id as id, CONCAT(LEFT(f_content,60),'…') as name, 'feedback' as type FROM feedback WHERE is_deleted=1 ORDER BY f_id DESC");
$deleted_orders   = $conn->query("SELECT co.co_id as id, CONCAT(co.order_id,' – ',COALESCE(c.course_name,'?'),' | ',co.stu_email) as name, 'courseorder' as type FROM courseorder co LEFT JOIN course c ON co.course_id=c.course_id WHERE co.is_deleted=1 ORDER BY co.co_id DESC");
$deleted_contacts = $conn->query("SELECT c_id as id, CONCAT(name,' - ',LEFT(message,40),'…') as name, 'contact' as type FROM contact_message WHERE is_deleted=1 ORDER BY c_id DESC");

$groups = [
    ['label' => 'Khoá học',    'icon' => 'fa-layer-group',    'color' => 'blue',    'result' => $deleted_courses],
    ['label' => 'Bài học',     'icon' => 'fa-play-circle',    'color' => 'violet',  'result' => $deleted_lessons],
    ['label' => 'Học viên',    'icon' => 'fa-users',          'color' => 'emerald', 'result' => $deleted_students],
    ['label' => 'Giao dịch',   'icon' => 'fa-receipt',        'color' => 'amber',   'result' => $deleted_orders],
    ['label' => 'Thư liên hệ', 'icon' => 'fa-envelope',       'color' => 'blue',    'result' => $deleted_contacts],
    ['label' => 'Đánh giá',    'icon' => 'fa-comment-dots',   'color' => 'rose',    'result' => $deleted_feedback],
];

$total = array_sum(array_map(fn($g) => $g['result'] ? $g['result']->num_rows : 0, $groups));
?>

<!-- Header -->
<div class="flex items-center justify-between mb-8">
  <div>
    <h2 class="text-xl font-bold text-slate-800">Thùng rác</h2>
    <p class="text-sm text-slate-400 mt-0.5">
      Các bản ghi đã xoá mềm. Khôi phục hoặc xoá vĩnh viễn.
      <span class="ml-2 px-2 py-0.5 bg-red-50 text-red-600 rounded-full text-xs font-semibold"><?php echo $total; ?> mục</span>
    </p>
  </div>
</div>

<?php if($total === 0): ?>
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm py-20 flex flex-col items-center text-slate-300">
  <i class="fas fa-trash-alt text-5xl mb-4"></i>
  <p class="text-base font-semibold">Thùng rác trống!</p>
  <p class="text-sm mt-1">Không có bản ghi nào bị xoá.</p>
</div>
<?php else: ?>

<?php foreach($groups as $g):
    $res = $g['result'];
    if(!$res || $res->num_rows === 0) continue;
    $c = $g['color'];
    // Map color name → tailwind classes
    $badgeCls = match($c) {
        'blue'    => 'bg-blue-50 text-blue-700',
        'violet'  => 'bg-violet-50 text-violet-700',
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'amber'   => 'bg-amber-50 text-amber-700',
        'rose'    => 'bg-rose-50 text-rose-700',
        default   => 'bg-slate-50 text-slate-700',
    };
    $iconCls = match($c) {
        'blue'    => 'text-blue-500',
        'violet'  => 'text-violet-500',
        'emerald' => 'text-emerald-500',
        'amber'   => 'text-amber-500',
        'rose'    => 'text-rose-500',
        default   => 'text-slate-500',
    };
?>
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-5">
  <!-- Section header -->
  <div class="px-6 py-4 flex items-center gap-3 border-b border-slate-50">
    <i class="fas <?php echo $g['icon']; ?> <?php echo $iconCls; ?>"></i>
    <h3 class="font-bold text-slate-700 text-sm"><?php echo $g['label']; ?></h3>
    <span class="px-2 py-0.5 <?php echo $badgeCls; ?> rounded-full text-xs font-semibold ml-auto"><?php echo $res->num_rows; ?> mục</span>
  </div>

  <div class="divide-y divide-slate-50">
  <?php $res->data_seek(0); while($row = $res->fetch_assoc()): ?>
    <div class="px-6 py-3 flex items-center gap-4 hover:bg-slate-50/60 transition-colors">
      <!-- Icon -->
      <div class="w-8 h-8 rounded-lg <?php echo $badgeCls; ?> flex items-center justify-center shrink-0">
        <i class="fas fa-trash-restore text-xs"></i>
      </div>
      <!-- Name -->
      <p class="text-sm text-slate-700 font-medium flex-grow min-w-0 truncate"><?php echo htmlspecialchars($row['name']); ?></p>
      <!-- Actions -->
      <div class="flex items-center gap-2 shrink-0">
        <!-- Restore -->
        <form method="POST" class="inline">
          <input type="hidden" name="action" value="restore">
          <input type="hidden" name="type"   value="<?php echo $row['type']; ?>">
          <input type="hidden" name="id"     value="<?php echo $row['id']; ?>">
          <button type="submit"
                  class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-semibold hover:bg-emerald-100 transition-colors">
            <i class="fas fa-undo text-xs"></i> Khôi phục
          </button>
        </form>
        <!-- Permanent delete -->
        <form method="POST" class="inline"
              onsubmit="return confirm('Xoá VĨNH VIỄN? Không thể khôi phục!')">
          <input type="hidden" name="action" value="permanent">
          <input type="hidden" name="type"   value="<?php echo $row['type']; ?>">
          <input type="hidden" name="id"     value="<?php echo $row['id']; ?>">
          <button type="submit"
                  class="flex items-center gap-1.5 px-3 py-1.5 bg-red-50 text-red-600 rounded-lg text-xs font-semibold hover:bg-red-100 transition-colors">
            <i class="fas fa-times text-xs"></i> Xoá vĩnh viễn
          </button>
        </form>
      </div>
    </div>
  <?php endwhile; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php include('./adminInclude/footer.php'); ?>
