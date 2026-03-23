<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../csrf.php');

define('TITLE', 'Học viên');
define('PAGE', 'students');
include('./adminInclude/header.php');
include('../dbConnection.php');

if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>";
}

// Delete student
if(isset($_POST['delete_stu'])){
    if(!csrf_verify($_POST['csrf_token'] ?? null)) {
        echo "<script>alert('Phiên gửi biểu mẫu đã hết hạn.'); location.href='students.php';</script>";
        exit;
    }

    $sid = (int)$_POST['sid'];
    $stuEmailDelete = '';
    $emailStmt = $conn->prepare('SELECT stu_email FROM student WHERE stu_id = ? LIMIT 1');
    if($emailStmt) {
        $emailStmt->bind_param('i', $sid);
        $emailStmt->execute();
        $emailResult = $emailStmt->get_result();
        if($emailResult && $emailResult->num_rows === 1) {
            $stuEmailDelete = (string) $emailResult->fetch_assoc()['stu_email'];
        }
        $emailStmt->close();
    }

    if($stuEmailDelete !== '') {
        $cartStmt = $conn->prepare('UPDATE cart SET is_deleted = 1 WHERE stu_email = ?');
        if($cartStmt) {
            $cartStmt->bind_param('s', $stuEmailDelete);
            $cartStmt->execute();
            $cartStmt->close();
        }
    }

    $stuStmt = $conn->prepare('UPDATE student SET is_deleted = 1 WHERE stu_id = ?');
    if($stuStmt) {
        $stuStmt->bind_param('i', $sid);
        $stuStmt->execute();
        $stuStmt->close();
    }
    echo "<script>location.href='students.php';</script>"; exit;
}

$search = trim($_GET['q'] ?? '');
$result = false;
$studentsStmt = null;

if($search !== '') {
    $studentsStmt = $conn->prepare(
        "SELECT s.*, (SELECT COUNT(*) FROM courseorder o WHERE o.stu_email = s.stu_email AND o.status = 'TXN_SUCCESS' AND o.is_deleted = 0) AS course_count "
        . 'FROM student s WHERE s.is_deleted = 0 AND (s.stu_name LIKE ? OR s.stu_email LIKE ?) '
        . 'ORDER BY s.stu_id DESC'
    );
    if($studentsStmt) {
        $searchLike = '%' . $search . '%';
        $studentsStmt->bind_param('ss', $searchLike, $searchLike);
    }
} else {
    $studentsStmt = $conn->prepare(
        "SELECT s.*, (SELECT COUNT(*) FROM courseorder o WHERE o.stu_email = s.stu_email AND o.status = 'TXN_SUCCESS' AND o.is_deleted = 0) AS course_count "
        . 'FROM student s WHERE s.is_deleted = 0 ORDER BY s.stu_id DESC'
    );
}

if($studentsStmt) {
    $studentsStmt->execute();
    $result = $studentsStmt->get_result();
}
?>

<!-- Toolbar -->
<div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-6">
  <form class="flex gap-2 flex-grow" method="GET">
    <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm theo tên hoặc email..."
           class="flex-grow px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20">
    <button type="submit" class="px-4 py-2.5 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-primary/90 transition">
      <i class="fas fa-search"></i>
    </button>
    <?php if($search): ?>
    <a href="students.php" class="px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl text-sm hover:bg-slate-200 transition">Xoá lọc</a>
    <?php endif; ?>
  </form>
</div>

<!-- Table -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
        <tr>
          <th class="px-6 py-3 text-left">Học viên</th>
          <th class="px-6 py-3 text-left">Email</th>
          <th class="px-6 py-3 text-left">Nghề nghiệp</th>
          <th class="px-6 py-3 text-center">Khoá đã mua</th>
          <th class="px-6 py-3 text-center">Sửa</th>
          <th class="px-6 py-3 text-center">Xoá</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()):
        $img = ltrim(str_replace('../','', $row['stu_img'] ?? ''), '/');
      ?>
        <tr class="hover:bg-slate-50 transition-colors">
          <td class="px-6 py-3">
            <div class="flex items-center gap-3">
              <img src="../<?php echo $img ?: 'image/stu/student1.jpg'; ?>"
                   onerror="this.onerror=null;this.src='../image/stu/student1.jpg'"
                   class="w-9 h-9 rounded-full object-cover border border-slate-200">
              <span class="font-semibold text-slate-800"><?php echo htmlspecialchars($row['stu_name']); ?></span>
            </div>
          </td>
          <td class="px-6 py-3 text-slate-500"><?php echo htmlspecialchars($row['stu_email']); ?></td>
          <td class="px-6 py-3 text-slate-500"><?php echo htmlspecialchars($row['stu_occ'] ?: '—'); ?></td>
          <td class="px-6 py-3 text-center">
            <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs font-semibold"><?php echo $row['course_count']; ?></span>
          </td>
          <td class="px-6 py-3 text-center">
            <a href="editstudent.php?view=1&id=<?php echo $row['stu_id']; ?>"
               class="mx-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition hover:bg-blue-100"
               title="Sửa học viên">
              <i class="fas fa-pen text-xs"></i>
            </a>
          </td>
          <td class="px-6 py-3 text-center">
            <form method="POST" onsubmit="return confirm('Xoá học viên <?php echo addslashes(htmlspecialchars($row['stu_name'])); ?>?')">
              <input type="hidden" name="sid" value="<?php echo $row['stu_id']; ?>">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
              <button type="submit" name="delete_stu"
                      class="w-8 h-8 bg-red-50 text-red-500 rounded-lg flex items-center justify-center hover:bg-red-100 transition mx-auto">
                <i class="fas fa-trash text-xs"></i>
              </button>
            </form>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="6" class="px-6 py-12 text-center text-slate-400">Chưa có học viên nào.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if($studentsStmt) { $studentsStmt->close(); } ?>

<?php include('./adminInclude/footer.php'); ?>
