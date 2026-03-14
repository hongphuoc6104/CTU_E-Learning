<?php
if(!isset($_SESSION)) session_start();
define('TITLE', 'Đánh giá học viên');
define('PAGE', 'feedback');
include('./adminInclude/header.php');
include('../dbConnection.php');

if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>";
}

// Delete feedback
if(isset($_POST['delete_fb'])){
    $fid = (int)$_POST['fid'];
    $conn->query("UPDATE feedback SET is_deleted=1 WHERE f_id=$fid");
    echo "<script>location.href='feedback.php';</script>"; exit;
}

$result = $conn->query("SELECT f.*, s.stu_name, s.stu_email FROM feedback f LEFT JOIN student s ON f.stu_id=s.stu_id WHERE f.is_deleted=0 ORDER BY f.f_id DESC");
?>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
  <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
    <h2 class="font-bold text-slate-800">Tất cả đánh giá</h2>
    <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-semibold"><?php echo $result->num_rows; ?> đánh giá</span>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
        <tr>
          <th class="px-6 py-3 text-left">Học viên</th>
          <th class="px-6 py-3 text-left">Nội dung đánh giá</th>
          <th class="px-6 py-3 text-center">Xoá</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php if($result->num_rows > 0): while($r = $result->fetch_assoc()): ?>
        <tr class="hover:bg-slate-50">
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="font-semibold text-slate-800"><?php echo htmlspecialchars($r['stu_name'] ?? 'Ẩn danh'); ?></div>
            <div class="text-xs text-slate-400"><?php echo htmlspecialchars($r['stu_email'] ?? ''); ?></div>
          </td>
          <td class="px-6 py-4 text-slate-600 max-w-lg"><?php echo htmlspecialchars($r['f_content']); ?></td>
          <td class="px-6 py-4 text-center">
            <form method="POST" onsubmit="return confirm('Xoá đánh giá này?')">
              <input type="hidden" name="fid" value="<?php echo $r['f_id']; ?>">
              <button type="submit" name="delete_fb"
                      class="w-8 h-8 bg-red-50 text-red-500 rounded-lg flex items-center justify-center hover:bg-red-100 transition mx-auto">
                <i class="fas fa-trash text-xs"></i>
              </button>
            </form>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="3" class="px-6 py-12 text-center text-slate-400">Chưa có đánh giá nào.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include('./adminInclude/footer.php'); ?>