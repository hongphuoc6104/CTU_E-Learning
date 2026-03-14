<?php
include('./dbConnection.php');
session_start();
if(!isset($_SESSION['stuLogEmail'])) {
  header("Location: login.php");
  exit;
} else {
  $stuEmail = $_SESSION['stuLogEmail'];
  if(isset($_POST['ORDER_ID']) && isset($_POST['TXN_AMOUNT'])) {
    
    $order_id = $_POST['ORDER_ID'];
    $stu_email = $_POST['CUST_ID'];
    $amount = $_POST['TXN_AMOUNT'];
    $checkout_type = isset($_POST['checkout_type']) ? $_POST['checkout_type'] : 'single';
    $status = "TXN_SUCCESS";
    $respmsg = "Txn Success";
    $date = date('Y-m-d');
    
    $success = false;

    // Function to check if student already purchased a course
    function checkAlreadyPurchased($conn, $stu_email, $course_id) {
       $sql = "SELECT order_id FROM courseorder WHERE stu_email = '$stu_email' AND course_id = '$course_id' AND status = 'TXN_SUCCESS'";
       $result = $conn->query($sql);
       return $result->num_rows > 0;
    }

    if($checkout_type == 'cart') {
      // Logic for shopping cart checkout
      $sql = "SELECT course_id FROM cart WHERE stu_email = '$stu_email'";
      $result = $conn->query($sql);
      if($result->num_rows > 0) {
        $count = 1;
        while($row = $result->fetch_assoc()) {
          $course_id = $row['course_id'];
          
          if (!checkAlreadyPurchased($conn, $stu_email, $course_id)) {
            // Generate a unique order_id for each item to prevent primary key conflict
            $unique_order_id = $order_id . '-' . $count;
            $insert_sql = "INSERT INTO courseorder (order_id, stu_email, course_id, status, respmsg, amount, order_date) VALUES ('$unique_order_id', '$stu_email', '$course_id', '$status', '$respmsg', '$amount', '$date')";
            $conn->query($insert_sql);
          }
          $count++;
        }
        // Clear student's cart after successful purchase
        $del_sql = "UPDATE cart SET is_deleted = 1 WHERE stu_email = '$stu_email'";
        $conn->query($del_sql);
        $success = true;
      }
    } else {
      // Logic for single course checkout
      if(isset($_SESSION['course_id'])) {
        $course_id = $_SESSION['course_id'];
        
        if (!checkAlreadyPurchased($conn, $stu_email, $course_id)) {
          $sql_insert = "INSERT INTO courseorder (order_id, stu_email, course_id, status, respmsg, amount, order_date) VALUES ('$order_id', '$stu_email', '$course_id', '$status', '$respmsg', '$amount', '$date')";
          
          if($conn->query($sql_insert) == TRUE){
            $success = true;
            // Dọn dẹp giỏ hàng nếu khoá này tình cờ đang nằm trong đó
            $del_single_sql = "UPDATE cart SET is_deleted=1 WHERE stu_email = '$stu_email' AND course_id = '$course_id'";
            $conn->query($del_single_sql);
          }
        } else {
           // Đã mua rồi, vẫn cho query = true để đi tiếp, giả bộ thành công nhưng ko insert thêm db
           $success = true; 
        }
      }
    }
    
    if($success){
      // Beautiful Success UI before redirect
      echo '<!DOCTYPE html>
      <html lang="en">
      <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>Thanh Toán Thành Công</title>
          <link rel="stylesheet" href="css/bootstrap.min.css">
          <link rel="stylesheet" href="css/all.min.css">
          <style>
              body { background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; }
              .success-card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; max-width: 400px; width: 100%; transform: scale(0.9); animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
              @keyframes popIn { 100% { transform: scale(1); } }
              .success-icon { display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; background-color: #e8f5e9; color: #28a745; border-radius: 50%; font-size: 40px; margin-bottom: 20px; animation: checkmark 0.8s ease-in-out forwards; }
              @keyframes checkmark { 0% { transform: scale(0); } 50% { transform: scale(1.2); } 100% { transform: scale(1); } }
              h4 { color: #343a40; font-weight: 600; margin-bottom: 10px; }
              p { color: #6c757d; font-size: 15px; margin-bottom: 20px; }
              .loader { border: 3px solid #f3f3f3; border-top: 3px solid #007bff; border-radius: 50%; width: 24px; height: 24px; animation: spin 1s linear infinite; margin: 0 auto; }
              @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
          </style>
      </head>
      <body>
          <div class="success-card">
              <div class="success-icon">
                  <i class="fas fa-check"></i>
              </div>
              <h4>Thanh toán hợp lệ!</h4>
              <p>Khóa học đã được thêm vào tài khoản của bạn. Đang tự động chuyển hướng...</p>
              <div class="loader"></div>
          </div>
          <script>
              setTimeout(() => {
                  window.location.href = "./Student/myCourse.php";
              }, 3000);
          </script>
      </body>
      </html>';
      exit();
    } else {
      echo '<!DOCTYPE html>
      <html lang="en">
      <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>Thanh Toán Thất Bại</title>
          <link rel="stylesheet" href="css/bootstrap.min.css">
          <link rel="stylesheet" href="css/all.min.css">
          <style>
              body { background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; }
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
              <p class="text-muted">Hoặc giỏ hàng trống, hoặc hệ thống gặp sự cố mạng. Vui lòng thử lại.</p>
              <a href="javascript:history.back()" class="btn btn-primary mt-3 px-4">Quay lại</a>
          </div>
      </body>
      </html>';
      exit();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Processing...</title>
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
</head>
<body class="bg-light">
  <div class="container mt-5 text-center">
    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
      <span class="sr-only">Đang xử lý...</span>
    </div>
    <h3 class="mt-3 text-primary">Đang xử lý giao dịch ảo...</h3>
    <p>Vui lòng đợi trong giây lát, hệ thống đang tự động đăng ký khoá học cho bạn.</p>
  </div>
</body>
</html>
