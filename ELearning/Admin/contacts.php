<?php
if(!isset($_SESSION)) session_start();
define('TITLE', 'Tin nhắn Liên hệ');
define('PAGE', 'contacts');
include('./adminInclude/header.php');
include('../dbConnection.php');

if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>"; exit;
}

// Xoá mềm tin nhắn
if(isset($_POST['delete_contact'])) {
    $cid = (int)$_POST['cid'];
    $conn->query("UPDATE contact_message SET is_deleted=1 WHERE c_id = $cid");
    echo "<script>location.href='contacts.php';</script>"; exit;
}

// Lấy danh sách tin nhắn
$sql = "SELECT * FROM contact_message WHERE is_deleted=0 ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
  <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
    <h2 class="font-bold text-slate-800">Tin nhắn nhận được</h2>
    <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-xs font-semibold"><?php echo $result ? $result->num_rows : 0; ?> tin nhắn</span>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
        <tr>
           <th class="px-6 py-3 text-left">Người gửi</th>
           <th class="px-6 py-3 text-left">Thời gian</th>
           <th class="px-6 py-3 text-left">Chủ đề</th>
           <th class="px-6 py-3 text-left w-2/5">Nội dung</th>
           <th class="px-6 py-3 text-center">Xoá</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
        <tr class="hover:bg-slate-50 transition-colors align-top">
          <td class="px-6 py-4">
             <div class="font-bold text-slate-800"><?php echo htmlspecialchars($row['name']); ?></div>
             <div class="text-xs text-slate-500 mt-0.5"><i class="fas fa-envelope mr-1"></i><?php echo htmlspecialchars($row['email']); ?></div>
          </td>
          <td class="px-6 py-4 text-xs text-slate-400 whitespace-nowrap">
             <?php echo date('H:i d/m/Y', strtotime($row['created_at'])); ?>
          </td>
          <td class="px-6 py-4 font-semibold text-slate-700">
             <?php echo htmlspecialchars($row['subject'] ? $row['subject'] : '—'); ?>
          </td>
          <td class="px-6 py-4 text-slate-600 leading-relaxed">
             <?php echo nl2br(htmlspecialchars($row['message'])); ?>
          </td>
          <td class="px-6 py-4 text-center">
            <form method="POST" onsubmit="return confirm('Xóa tin nhắn này?');">
               <input type="hidden" name="cid" value="<?php echo $row['c_id']; ?>">
               <button type="submit" name="delete_contact" class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition-colors mx-auto flex items-center justify-center">
                 <i class="fas fa-trash text-xs"></i>
               </button>
            </form>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400">Chưa có tin nhắn liên hệ nào.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include('./adminInclude/footer.php'); ?>
