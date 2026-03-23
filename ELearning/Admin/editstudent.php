<?php 
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../csrf.php');

define('TITLE', 'Sửa thông tin học viên');
include('./adminInclude/header.php'); 
include('../dbConnection.php');

 if(isset($_SESSION['is_admin_login'])){
  $adminEmail = $_SESSION['adminLogEmail'];
 } else {
  echo "<script> location.href='../index.php'; </script>";
 }
$msg = '';

function text_len(string $value): int
{
  if (function_exists('mb_strlen')) {
    return mb_strlen($value, 'UTF-8');
  }

  return strlen($value);
}

// Update
if(isset($_POST['requpdate'])){
  if(!csrf_verify($_POST['csrf_token'] ?? null)) {
    $msg = ['type' => 'error', 'text' => 'Phiên gửi biểu mẫu đã hết hạn.'];
  } else {
    $sid = filter_input(INPUT_POST, 'stu_id', FILTER_VALIDATE_INT);
    $sname = trim((string) ($_POST['stu_name'] ?? ''));
    $semail = trim((string) ($_POST['stu_email'] ?? ''));
    $spass = (string) ($_POST['stu_pass'] ?? '');
    $socc = trim((string) ($_POST['stu_occ'] ?? ''));

    $nameLen = text_len($sname);
    $occLen = text_len($socc);
    $emailLen = text_len($semail);

    if(!$sid || $sname === '' || $semail === '' || $socc === ''){
      $msg = ['type' => 'warning', 'text' => 'Vui lòng điền đầy đủ thông tin!'];
    } elseif(!filter_var($semail, FILTER_VALIDATE_EMAIL)) {
      $msg = ['type' => 'warning', 'text' => 'Email không hợp lệ.'];
    } elseif($nameLen < 2 || $nameLen > 100 || $occLen > 100 || $emailLen > 191) {
      $msg = ['type' => 'warning', 'text' => 'Độ dài dữ liệu không hợp lệ.'];
    } elseif($spass !== '' && strlen($spass) < 6) {
      $msg = ['type' => 'warning', 'text' => 'Mật khẩu mới phải có ít nhất 6 ký tự.'];
    } elseif($spass !== '' && strlen($spass) > 72) {
      $msg = ['type' => 'warning', 'text' => 'Mật khẩu mới quá dài.'];
    } else {
      $conn->begin_transaction();
      $hasError = false;
      $newEmail = $semail;

      $currentStmt = $conn->prepare('SELECT stu_email FROM student WHERE stu_id = ? AND is_deleted = 0 LIMIT 1');
      if(!$currentStmt) {
        $hasError = true;
      }

      $oldEmail = '';
      if(!$hasError) {
        $currentStmt->bind_param('i', $sid);
        if(!$currentStmt->execute()) {
          $hasError = true;
        } else {
          $currentResult = $currentStmt->get_result();
          $currentRow = $currentResult ? $currentResult->fetch_assoc() : null;
          if(!$currentRow) {
            $hasError = true;
          } else {
            $oldEmail = (string) $currentRow['stu_email'];
          }
        }
        $currentStmt->close();
      }

      if(!$hasError && !hash_equals($oldEmail, $newEmail)) {
        $dupStmt = $conn->prepare('SELECT 1 FROM student WHERE stu_email = ? AND stu_id <> ? LIMIT 1');
        if(!$dupStmt) {
          $hasError = true;
        } else {
          $dupStmt->bind_param('si', $newEmail, $sid);
          if(!$dupStmt->execute()) {
            $hasError = true;
          } else {
            $dupStmt->store_result();
            if($dupStmt->num_rows > 0) {
              $msg = ['type' => 'warning', 'text' => 'Email đã tồn tại, vui lòng chọn email khác.'];
              $hasError = true;
            }
          }
          $dupStmt->close();
        }
      }

      if(!$hasError) {
        if($spass !== '') {
          $hashedPass = password_hash($spass, PASSWORD_DEFAULT);
          $stmtUpdate = $conn->prepare('UPDATE student SET stu_name = ?, stu_email = ?, stu_pass = ?, stu_occ = ? WHERE stu_id = ?');
          if($stmtUpdate) {
            $stmtUpdate->bind_param('ssssi', $sname, $newEmail, $hashedPass, $socc, $sid);
            if(!$stmtUpdate->execute()) {
              $hasError = true;
            }
            $stmtUpdate->close();
          } else {
            $hasError = true;
          }
        } else {
          $stmtUpdate = $conn->prepare('UPDATE student SET stu_name = ?, stu_email = ?, stu_occ = ? WHERE stu_id = ?');
          if($stmtUpdate) {
            $stmtUpdate->bind_param('sssi', $sname, $newEmail, $socc, $sid);
            if(!$stmtUpdate->execute()) {
              $hasError = true;
            }
            $stmtUpdate->close();
          } else {
            $hasError = true;
          }
        }
      }

      if(!$hasError && !hash_equals($oldEmail, $newEmail)) {
        $orderStmt = $conn->prepare('UPDATE courseorder SET stu_email = ? WHERE stu_email = ?');
        if($orderStmt) {
          $orderStmt->bind_param('ss', $newEmail, $oldEmail);
          if(!$orderStmt->execute()) {
            $hasError = true;
          }
          $orderStmt->close();
        } else {
          $hasError = true;
        }
      }

      if(!$hasError && !hash_equals($oldEmail, $newEmail)) {
        $cartStmt = $conn->prepare('UPDATE cart SET stu_email = ? WHERE stu_email = ?');
        if($cartStmt) {
          $cartStmt->bind_param('ss', $newEmail, $oldEmail);
          if(!$cartStmt->execute()) {
            $hasError = true;
          }
          $cartStmt->close();
        } else {
          $hasError = true;
        }
      }

      if($hasError) {
        $conn->rollback();
        if(!is_array($msg)) {
          $msg = ['type' => 'error', 'text' => 'Cập nhật thất bại.'];
        }
      } else {
        $conn->commit();
        $msg = ['type' => 'success', 'text' => 'Cập nhật học viên thành công!'];
      }
    }
  }
}

$row = null;
if(isset($_GET['view'])){
  $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
  if($id) {
    $stmtView = $conn->prepare('SELECT stu_id, stu_name, stu_email, stu_occ FROM student WHERE stu_id = ? LIMIT 1');
    if($stmtView) {
      $stmtView->bind_param('i', $id);
      $stmtView->execute();
      $result = $stmtView->get_result();
      $row = $result ? $result->fetch_assoc() : null;
      $stmtView->close();
    }
  }
}

if (isset($_POST['requpdate']) && $row) {
  $row['stu_name'] = $_POST['stu_name'] ?? $row['stu_name'];
  $row['stu_email'] = $_POST['stu_email'] ?? $row['stu_email'];
  $row['stu_occ'] = $_POST['stu_occ'] ?? $row['stu_occ'];
}
 ?>
<div class="max-w-3xl">
  <div class="mb-8 flex items-center justify-between gap-4">
    <div>
      <h2 class="text-2xl font-black text-slate-900">Cập nhật thông tin học viên</h2>
      <p class="mt-1 text-sm text-slate-500">Chỉnh sửa hồ sơ học viên theo dữ liệu mới nhất.</p>
    </div>
    <a href="students.php" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50">
      <i class="fas fa-arrow-left text-xs"></i>
      Danh sách học viên
    </a>
  </div>

  <?php
  if (isset($msg) && is_array($msg)) {
    $toneMap = [
      'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
      'error' => 'border-red-200 bg-red-50 text-red-600',
      'warning' => 'border-amber-200 bg-amber-50 text-amber-700'
    ];
    $iconMap = [
      'success' => 'fa-check-circle',
      'error' => 'fa-exclamation-circle',
      'warning' => 'fa-exclamation-triangle'
    ];
    $msgType = $msg['type'];
    $msgTone = isset($toneMap[$msgType]) ? $toneMap[$msgType] : $toneMap['error'];
    $msgIcon = isset($iconMap[$msgType]) ? $iconMap[$msgType] : $iconMap['error'];
    echo '<div class="mb-6 flex items-center gap-3 rounded-xl border px-4 py-3 text-sm font-semibold ' . $msgTone . '">';
    echo '<i class="fas ' . $msgIcon . '"></i>';
    echo '<span>' . htmlspecialchars($msg['text'], ENT_QUOTES, 'UTF-8') . '</span>';
    echo '</div>';
  }
  ?>

  <?php if(!$row): ?>
  <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-800">
    Không tìm thấy học viên cần chỉnh sửa hoặc dữ liệu không hợp lệ.
  </div>
  <?php else: ?>
  <form action="" method="POST" class="space-y-5 rounded-2xl border border-slate-100 bg-white p-8 shadow-sm">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

    <div>
      <label for="stu_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Mã học viên</label>
      <input type="text" id="stu_id" name="stu_id" value="<?php echo (int) $row['stu_id']; ?>" readonly class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500 outline-none">
    </div>

    <div>
      <label for="stu_name" class="mb-1.5 block text-sm font-semibold text-slate-700">Họ và tên</label>
      <input type="text" id="stu_name" name="stu_name" value="<?php echo htmlspecialchars($row['stu_name'], ENT_QUOTES, 'UTF-8'); ?>" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-800 outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/10">
    </div>

    <div>
      <label for="stu_email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
      <input type="email" id="stu_email" name="stu_email" value="<?php echo htmlspecialchars($row['stu_email'], ENT_QUOTES, 'UTF-8'); ?>" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-800 outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/10">
    </div>

    <div>
      <label for="stu_pass" class="mb-1.5 block text-sm font-semibold text-slate-700">Mật khẩu mới (để trống nếu không đổi)</label>
      <input type="password" id="stu_pass" name="stu_pass" value="" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-800 outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/10">
    </div>

    <div>
      <label for="stu_occ" class="mb-1.5 block text-sm font-semibold text-slate-700">Nghề nghiệp</label>
      <input type="text" id="stu_occ" name="stu_occ" value="<?php echo htmlspecialchars($row['stu_occ'], ENT_QUOTES, 'UTF-8'); ?>" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-800 outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/10">
    </div>

    <div class="flex flex-wrap items-center gap-3 pt-2">
      <button type="submit" id="requpdate" name="requpdate" class="rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-primary/90">Cập nhật</button>
      <a href="students.php" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50">Đóng</a>
    </div>
  </form>
  <?php endif; ?>
</div>

<?php
include('./adminInclude/footer.php'); 
?>
