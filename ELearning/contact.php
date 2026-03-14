<?php
if(isset($_POST['submitContact'])) {
    if(!isset($conn)) { include_once('dbConnection.php'); }
    $c_name = $conn->real_escape_string($_POST['name'] ?? '');
    $c_subject = $conn->real_escape_string($_POST['subject'] ?? '');
    $c_email = $conn->real_escape_string($_POST['email'] ?? '');
    $c_message = $conn->real_escape_string($_POST['message'] ?? '');
    
    if(!empty($c_name) && !empty($c_email) && !empty($c_message)) {
        $sql_c = "INSERT INTO contact_message (name, subject, email, message) VALUES ('$c_name', '$c_subject', '$c_email', '$c_message')";
        if($conn->query($sql_c)) {
            echo "<script>alert('Gửi tin nhắn liên hệ thành công. Chúng tôi sẽ phản hồi sớm nhất!');</script>";
        } else {
            echo "<script>alert('Lỗi hệ thống khi gửi liên hệ!');</script>";
        }
    }
}
?>
<!--Start Contact Us-->
<div class="container mt-5 mb-5" id="Contact">
  <h2 class="text-center font-weight-bold text-primary mb-5" style="letter-spacing: 1px;">Liên hệ với chúng tôi</h2>
  <div class="row bg-white shadow-sm rounded overflow-hidden">
    <div class="col-md-7 p-5">
      <h4 class="mb-4 text-dark font-weight-bold">Gửi thư góp ý/hỗ trợ</h4>
      <form action="" method="post">
        <div class="form-group">
            <input type="text" class="form-control" name="name" placeholder="Họ và tên của bạn" required>
        </div>
        <div class="form-group">
            <input type="text" class="form-control" name="subject" placeholder="Chủ đề quan tâm">
        </div>
        <div class="form-group">
            <input type="email" class="form-control" name="email" placeholder="Địa chỉ Email" required>
        </div>
        <div class="form-group">
            <textarea class="form-control" name="message" placeholder="Bạn cần hỗ trợ gì?" style="height:120px;" required></textarea>
        </div>
        <button class="btn btn-primary px-5 py-2 font-weight-bold" type="submit" name="submitContact">Gửi tin nhắn <i class="fas fa-paper-plane ml-2"></i></button>
      </form>
    </div>

    <div class="col-md-5 text-white p-5 d-flex flex-column justify-content-center" style="background: linear-gradient(135deg, #003366, #0056b3);">
      <h3 class="font-weight-bold mb-4">Trường Đại học Cần Thơ</h3>
      <p class="mb-3"><i class="fas fa-map-marker-alt mr-3"></i> Tòa nhà Khoa CNTT & Truyền Thông, Khu II, Đ. 3/2, Phường Xuân Khánh, Q. Ninh Kiều, TP. Cần Thơ</p>
      <p class="mb-3"><i class="fas fa-phone-alt mr-3"></i> Hotline: 0292 3831 301</p>
      <p class="mb-3"><i class="fas fa-envelope mr-3"></i> E-mail: dhct@ctu.edu.vn</p>
      <p><i class="fas fa-globe mr-3"></i> Mạng xã hội:</p>
      <div class="d-flex gap-3">
          <a href="#" class="btn btn-outline-light rounded-circle mr-2"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="btn btn-outline-light rounded-circle mr-2"><i class="fab fa-youtube"></i></a>
          <a href="#" class="btn btn-outline-light rounded-circle"><i class="fab fa-instagram"></i></a>
      </div>
    </div>
  </div>
</div>
<!-- End Contact Us -->