<?php
include('./dbConnection.php');
require_once(__DIR__ . '/session_bootstrap.php');
secure_session_start();

if (!isset($_SESSION['stuLogEmail'])) {
    header('Location: login.php');
    exit;
}

$stuEmail = (string) $_SESSION['stuLogEmail'];
$success = false;
$resultMessage = 'Đơn chờ thanh toán đã hết hạn hoặc dữ liệu không hợp lệ. Vui lòng thử lại.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pendingToken = (string) ($_POST['pending_token'] ?? '');
    $pendingOrder = $_SESSION['pending_checkout'] ?? null;

    $isPendingValid = is_array($pendingOrder)
        && isset($pendingOrder['token'])
        && hash_equals((string) $pendingOrder['token'], $pendingToken)
        && isset($pendingOrder['stu_email'])
        && hash_equals((string) $pendingOrder['stu_email'], $stuEmail)
        && isset($pendingOrder['created_at'])
        && (time() - (int) $pendingOrder['created_at'] <= 900);

    if ($isPendingValid) {
        $checkoutType = (string) ($pendingOrder['checkout_type'] ?? '');
        $courseIds = array_values(array_unique(array_map('intval', $pendingOrder['course_ids'] ?? [])));

        if (($checkoutType === 'single' || $checkoutType === 'cart') && !empty($courseIds)) {
            $status = 'TXN_SUCCESS';
            $respmsg = 'Txn Success';
            $qrData = trim((string) ($_POST['QR_DATA'] ?? ''));
            if ($qrData !== '') {
                $qrData = str_replace(["\r", "\n"], ' ', $qrData);
                $qrData = substr($qrData, 0, 200);
                $respmsg = 'QR: ' . $qrData . ' | Txn Success';
            }

            $date = date('Y-m-d');
            $baseOrderRef = preg_replace('/[^A-Za-z0-9\-]/', '', (string) ($pendingOrder['order_reference'] ?? ''));
            if ($baseOrderRef === '') {
                $baseOrderRef = 'ORDS' . random_int(100000, 99999999);
            }

            $courseStmt = $conn->prepare('SELECT course_price FROM course WHERE course_id = ? AND is_deleted = 0 LIMIT 1');
            $ownedStmt = $conn->prepare('SELECT 1 FROM courseorder WHERE stu_email = ? AND course_id = ? AND status = ? AND is_deleted = 0 LIMIT 1');
            $insertStmt = $conn->prepare('INSERT INTO courseorder (order_id, stu_email, course_id, status, respmsg, amount, order_date) VALUES (?, ?, ?, ?, ?, ?, ?)');

            $cartExistsStmt = null;
            $cartSoftDeleteStmt = null;
            if ($checkoutType === 'cart') {
                $cartExistsStmt = $conn->prepare('SELECT 1 FROM cart WHERE stu_email = ? AND course_id = ? AND is_deleted = 0 LIMIT 1');
                $cartSoftDeleteStmt = $conn->prepare('UPDATE cart SET is_deleted = 1 WHERE stu_email = ? AND course_id = ? AND is_deleted = 0');
            }

            if ($courseStmt && $ownedStmt && $insertStmt && ($checkoutType !== 'cart' || ($cartExistsStmt && $cartSoftDeleteStmt))) {
                $conn->begin_transaction();
                $dbError = false;
                $processableCount = 0;
                $alreadyOwnedCount = 0;
                $insertedCount = 0;

                foreach ($courseIds as $idx => $courseId) {
                    if ($courseId <= 0) {
                        continue;
                    }

                    if ($checkoutType === 'cart') {
                        $cartExistsStmt->bind_param('si', $stuEmail, $courseId);
                        if (!$cartExistsStmt->execute()) {
                            $dbError = true;
                            break;
                        }

                        $cartExistsStmt->store_result();
                        $isInCart = $cartExistsStmt->num_rows > 0;
                        $cartExistsStmt->free_result();
                        if (!$isInCart) {
                            continue;
                        }
                    }

                    $courseStmt->bind_param('i', $courseId);
                    if (!$courseStmt->execute()) {
                        $dbError = true;
                        break;
                    }

                    $courseResult = $courseStmt->get_result();
                    $courseRow = $courseResult ? $courseResult->fetch_assoc() : null;
                    if (!$courseRow) {
                        continue;
                    }

                    $processableCount++;
                    $courseAmount = (int) $courseRow['course_price'];

                    $ownedStmt->bind_param('sis', $stuEmail, $courseId, $status);
                    if (!$ownedStmt->execute()) {
                        $dbError = true;
                        break;
                    }

                    $ownedStmt->store_result();
                    $isOwned = $ownedStmt->num_rows > 0;
                    $ownedStmt->free_result();

                    if ($isOwned) {
                        $alreadyOwnedCount++;
                        if ($checkoutType === 'cart') {
                            $cartSoftDeleteStmt->bind_param('si', $stuEmail, $courseId);
                            if (!$cartSoftDeleteStmt->execute()) {
                                $dbError = true;
                                break;
                            }
                        }
                        continue;
                    }

                    $orderId = $checkoutType === 'cart' ? $baseOrderRef . '-' . ($idx + 1) : $baseOrderRef;
                    $insertStmt->bind_param('ssissis', $orderId, $stuEmail, $courseId, $status, $respmsg, $courseAmount, $date);
                    if (!$insertStmt->execute()) {
                        $dbError = true;
                        break;
                    }

                    $insertedCount++;

                    if ($checkoutType === 'cart') {
                        $cartSoftDeleteStmt->bind_param('si', $stuEmail, $courseId);
                        if (!$cartSoftDeleteStmt->execute()) {
                            $dbError = true;
                            break;
                        }
                    }
                }

                if (!$dbError && $processableCount > 0 && ($insertedCount > 0 || $alreadyOwnedCount === $processableCount)) {
                    $conn->commit();
                    $success = true;
                    if ($insertedCount > 0 && $alreadyOwnedCount > 0) {
                        $resultMessage = 'Một phần giao dịch đã được thêm thành công. Các khoá học đã sở hữu trước đó được tự động bỏ qua.';
                    } elseif ($insertedCount > 0) {
                        $resultMessage = 'Khóa học đã được thêm vào tài khoản của bạn. Đang tự động chuyển hướng...';
                    } elseif ($insertedCount === 0 && $alreadyOwnedCount === $processableCount) {
                        $resultMessage = 'Bạn đã sở hữu tất cả khoá học trong giao dịch này. Không có khoá học mới được thêm.';
                    }
                } else {
                    $conn->rollback();
                    if ($dbError) {
                        $resultMessage = 'Có lỗi hệ thống khi xử lý giao dịch. Vui lòng thử lại.';
                    } elseif ($processableCount === 0) {
                        $resultMessage = 'Không có khoá học hợp lệ để thanh toán. Vui lòng kiểm tra lại giỏ hàng.';
                    } else {
                        $resultMessage = 'Không thể hoàn tất giao dịch do dữ liệu đã thay đổi. Vui lòng thử lại.';
                    }
                }

                $courseStmt->close();
                $ownedStmt->close();
                $insertStmt->close();
                if ($cartExistsStmt) {
                    $cartExistsStmt->close();
                }
                if ($cartSoftDeleteStmt) {
                    $cartSoftDeleteStmt->close();
                }
            }
        }
    }

    unset($_SESSION['pending_checkout']);
}

if ($success) {
    echo '<!DOCTYPE html>
      <html lang="en">
      <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>Thanh Toán Thành Công</title>
          <link rel="stylesheet" href="css/tailwind.css">
          <link rel="stylesheet" href="css/all.min.css">
          <style>
              body { background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; font-family: "Inter", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; }
              .success-card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; max-width: 400px; width: 100%; transform: scale(0.9); animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
              @keyframes popIn { 100% { transform: scale(1); } }
              .success-icon { display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; background-color: #e8f5e9; color: #28a745; border-radius: 50%; font-size: 40px; margin-bottom: 20px; animation: checkmark 0.8s ease-in-out forwards; }
              @keyframes checkmark { 0% { transform: scale(0); } 50% { transform: scale(1.2); } 100% { transform: scale(1); } }
              h4 { color: #343a40; font-weight: 600; margin-bottom: 10px; }
              p { color: #6c757d; font-size: 15px; margin-bottom: 20px; }
              .loader { border: 3px solid #f3f3f3; border-top: 3px solid #007bff; border-radius: 50%; width: 24px; height: 24px; animation: spin 1s linear infinite; margin: 0 auto; }
              @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
              .success-link { display: inline-flex; margin-top: 16px; padding: 10px 16px; border-radius: 12px; background: #003366; color: #fff; text-decoration: none; font-weight: 600; }
          </style>
      </head>
      <body>
          <div class="success-card">
              <div class="success-icon">
                  <i class="fas fa-check"></i>
              </div>
              <h4>Thanh toán hợp lệ!</h4>
          <p>' . htmlspecialchars($resultMessage, ENT_QUOTES, 'UTF-8') . '</p>
              <div class="loader"></div>
              <a class="success-link" href="./Student/myCourse.php">Đến khoá học của tôi</a>
          </div>
          <script>
              setTimeout(() => {
                  window.location.href = "./Student/myCourse.php";
              }, 3000);
          </script>
      </body>
      </html>';
    exit;
}

echo '<!DOCTYPE html>
  <html lang="en">
  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Thanh Toán Thất Bại</title>
      <link rel="stylesheet" href="css/tailwind.css">
      <link rel="stylesheet" href="css/all.min.css">
      <style>
          body { background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; font-family: "Inter", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; }
          .error-card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; max-width: 400px; width: 100%; }
          .error-icon { display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; background-color: #fde8e8; color: #dc3545; border-radius: 50%; font-size: 40px; margin-bottom: 20px; }
      </style>
  </head>
  <body>
      <div class="error-card">
          <div class="error-icon">
              <i class="fas fa-times"></i>
          </div>
          <h4>Không thể xử lý giao dịch</h4>
          <p class="text-slate-500">' . htmlspecialchars($resultMessage, ENT_QUOTES, 'UTF-8') . '</p>
          <a href="javascript:history.back()" class="mt-3 inline-flex rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white">Quay lại</a>
      </div>
  </body>
  </html>';
exit;
