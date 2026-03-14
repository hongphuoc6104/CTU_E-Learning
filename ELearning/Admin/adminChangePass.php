<?php
if(!isset($_SESSION)) session_start();
define('TITLE', 'Đổi mật khẩu');
define('PAGE', 'changepass');
include('./adminInclude/header.php');
include('../dbConnection.php');

if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>";
}

$adminEmail = $_SESSION['adminLogEmail'];
$msg = '';

if(isset($_POST['adminPassUpdatebtn'])){
    $oldPass  = $_POST['oldPass']  ?? '';
    $newPass  = $_POST['newPass']  ?? '';
    $confPass = $_POST['confPass'] ?? '';

    if(!$oldPass || !$newPass || !$confPass){
        $msg = ['type'=>'error','text'=>'Vui lòng điền đầy đủ tất cả các trường.'];
    } elseif($newPass !== $confPass){
        $msg = ['type'=>'error','text'=>'Mật khẩu mới và xác nhận không khớp.'];
    } elseif(strlen($newPass) < 6){
        $msg = ['type'=>'error','text'=>'Mật khẩu mới phải có ít nhất 6 ký tự.'];
    } else {
        $r = $conn->query("SELECT admin_pass FROM admin WHERE admin_email='$adminEmail'");
        if($r->num_rows === 0){
            $msg = ['type'=>'error','text'=>'Không tìm thấy tài khoản.'];
        } else {
            $row = $r->fetch_assoc();
            if(!password_verify($oldPass, $row['admin_pass']) && $row['admin_pass'] !== $oldPass) {
                $msg = ['type'=>'error','text'=>'Mật khẩu cũ không đúng.'];
            } else {
                $hashedNewPass = password_hash($newPass, PASSWORD_DEFAULT);
                $conn->query("UPDATE admin SET admin_pass='$hashedNewPass' WHERE admin_email='$adminEmail'");
                $msg = ['type'=>'success','text'=>'Đổi mật khẩu thành công!'];
            }
        }
    }
}
?>

<div class="max-w-md">
  <?php if($msg): ?>
  <div class="mb-4 p-4 rounded-xl text-sm font-medium
    <?php echo $msg['type']==='success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?>">
    <i class="fas <?php echo $msg['type']==='success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
    <?php echo $msg['text']; ?>
  </div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8">
    <div class="mb-6 pb-5 border-b border-slate-100">
      <p class="text-sm text-slate-500">Tài khoản: <span class="font-semibold text-slate-800"><?php echo htmlspecialchars($adminEmail); ?></span></p>
    </div>
    <form method="POST" class="space-y-5">
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Mật khẩu hiện tại</label>
        <input type="password" name="oldPass" required
               class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20"
               placeholder="Nhập mật khẩu hiện tại">
      </div>
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Mật khẩu mới</label>
        <input type="password" name="newPass" required minlength="6"
               class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20"
               placeholder="Tối thiểu 6 ký tự">
      </div>
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Xác nhận mật khẩu mới</label>
        <input type="password" name="confPass" required
               class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20"
               placeholder="Nhập lại mật khẩu mới">
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" name="adminPassUpdatebtn"
                class="px-6 py-3 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition text-sm">
          <i class="fas fa-key mr-2"></i>Cập nhật mật khẩu
        </button>
        <button type="reset" class="px-6 py-3 bg-slate-100 text-slate-600 font-semibold rounded-xl hover:bg-slate-200 transition text-sm">Đặt lại</button>
      </div>
    </form>
  </div>
</div>

<?php include('./adminInclude/footer.php'); ?>