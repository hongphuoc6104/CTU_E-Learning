<?php 
  include('./dbConnection.php');
  // Header Include from mainInclude 
  include('./mainInclude/header.php'); 
?>
    <div class="container-fluid bg-dark"> <!-- Start Course Page Banner -->
      <div class="row">
        <img src="./image/coursebanner.jpg" alt="courses" style="height:300px; width:100%; object-fit:cover; box-shadow:10px;"/>
      </div> 
    </div> <!-- End Course Page Banner -->

    <div class="container mt-5 mb-5">
     <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="bg-white shadow-sm rounded p-4">
          <h5 class="mb-4 text-primary font-weight-bold"><i class="fas fa-user-plus mr-2"></i>Đăng ký tài khoản mới</h5>
          <form role="form" id="stuRegForm">
            <div class="form-group">
              <i class="fas fa-user"></i><label for="stuname" class="pl-2 font-weight-bold">Họ và tên</label><small id="statusMsg1"></small>
              <input type="text" class="form-control" placeholder="Nhập họ tên" name="stuname" id="stuname">
            </div>
            <div class="form-group">
              <i class="fas fa-envelope"></i><label for="stuemail" class="pl-2 font-weight-bold">Email</label><small id="statusMsg2"></small>
              <input type="email" class="form-control" placeholder="Nhập email" name="stuemail" id="stuemail">
              <small class="form-text text-muted">Chúng tôi không chia sẻ email của bạn cho bên thứ ba.</small>
            </div>
            <div class="form-group">
              <i class="fas fa-key"></i><label for="stupass" class="pl-2 font-weight-bold">Mật khẩu</label><small id="statusMsg3"></small>
              <input type="password" class="form-control" placeholder="Tạo mật khẩu" name="stupass" id="stupass">
            </div>
            <button type="button" class="btn btn-primary btn-block py-2 font-weight-bold" id="signup" onclick="addStu()">Đăng ký</button>
          </form><br/>
          <small id="successMsg"></small>
          <div class="text-center mt-3">
              <span>Đã có tài khoản? </span><a href="login.php" class="text-primary font-weight-bold">Đăng nhập ngay</a>
          </div>
        </div>
      </div>
     </div>
    </div>
    <hr/>

<?php 
// Contact Us
include('./contact.php'); 
?> 

<?php 
  // Footer Include from mainInclude 
  include('./mainInclude/footer.php'); 
?> 
