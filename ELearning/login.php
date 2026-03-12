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
          <h5 class="mb-4 text-primary font-weight-bold"><i class="fas fa-sign-in-alt mr-2"></i>Đăng nhập</h5>
          <form role="form" id="stuLoginForm">
            <div class="form-group">
              <i class="fas fa-envelope"></i><label for="stuLogEmail" class="pl-2 font-weight-bold">Email</label><small id="statusLogMsg1"></small>
              <input type="email" class="form-control" placeholder="Nhập email của bạn" name="stuLogEmail" id="stuLogEmail">
            </div>
            <div class="form-group">
              <i class="fas fa-key"></i><label for="stuLogPass" class="pl-2 font-weight-bold">Mật khẩu</label>
              <input type="password" class="form-control" placeholder="Nhập mật khẩu" name="stuLogPass" id="stuLogPass">
            </div>
            <button type="button" class="btn btn-primary btn-block py-2 font-weight-bold" id="stuLoginBtn" onclick="checkStuLogin()">Đăng nhập</button>
          </form><br/>
          <small id="statusLogMsg"></small>
          <div class="text-center mt-3">
              <span>Chưa có tài khoản? </span><a href="signup.php" class="text-primary font-weight-bold">Đăng ký ngay</a>
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
