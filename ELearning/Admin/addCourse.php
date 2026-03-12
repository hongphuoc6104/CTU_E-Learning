<?php
if(!isset($_SESSION)){ 
  session_start(); 
}
define('TITLE', 'Thêm khoá học');
include('./adminInclude/header.php'); 
include('../dbConnection.php');

 if(isset($_SESSION['is_admin_login'])){
  $adminEmail = $_SESSION['adminLogEmail'];
 } else {
  echo "<script> location.href='../index.php'; </script>";
 }
 if(isset($_REQUEST['courseSubmitBtn'])){
  // Checking for Empty Fields
  if(($_REQUEST['course_name'] == "") || ($_REQUEST['course_desc'] == "") || ($_REQUEST['course_author'] == "") || ($_REQUEST['course_duration'] == "") || ($_REQUEST['course_price'] == "") || ($_REQUEST['course_original_price'] == "")){
   // msg displayed if required field missing
   $msg = '<div class="alert alert-warning col-sm-6 ml-5 mt-2" role="alert"> Vui lòng điền đầy đủ tất cả các trường dữ liệu! </div>';
  } else {
   // Assigning User Values to Variable
   $course_name = $_REQUEST['course_name'];
   $course_desc = $_REQUEST['course_desc'];
   $course_author = $_REQUEST['course_author'];
   $course_duration = $_REQUEST['course_duration'];
   $course_price = $_REQUEST['course_price'];
   $course_original_price = $_REQUEST['course_original_price'];
   
   $course_image = $_FILES['course_img']['name']; 
   $course_image_temp = $_FILES['course_img']['tmp_name'];
   $image_size = $_FILES['course_img']['size'];
   $image_error = $_FILES['course_img']['error'];

   // Image Validation
   $allowed_types = array("jpg", "jpeg", "png", "webp");
   $file_ext = strtolower(pathinfo($course_image, PATHINFO_EXTENSION));

   if ($image_error == UPLOAD_ERR_NO_FILE) {
       $msg = '<div class="alert alert-warning col-sm-6 ml-5 mt-2" role="alert"> Vui lòng tải lên một ảnh đại diện khoá học! </div>';
   } else if (!in_array($file_ext, $allowed_types)) {
       $msg = '<div class="alert alert-danger col-sm-6 ml-5 mt-2" role="alert"> Lỗi: Định dạng ảnh không hỗ trợ, chỉ chấp nhận jpg, jpeg, png, webp. </div>';
   } else if ($image_size > 2097152) { // 2MB
       $msg = '<div class="alert alert-danger col-sm-6 ml-5 mt-2" role="alert"> Lỗi: Dung lượng ảnh lớn hơn mức cho phép (2MB). </div>';
   } else {
     $filename = time() . '_' . basename($course_image);
     $img_disk = __DIR__ . '/../image/courseimg/' . $filename;
     $img_db   = 'image/courseimg/' . $filename;
     move_uploaded_file($course_image_temp, $img_disk);

     
     $sql = "INSERT INTO course (course_name, course_desc, course_author, course_img, course_duration, course_price, course_original_price) VALUES ('$course_name', '$course_desc','$course_author', '$img_db', '$course_duration', '$course_price', '$course_original_price')";
     if($conn->query($sql) == TRUE){
      // below msg display on form submit success
      $msg = '<div class="alert alert-success col-sm-6 ml-5 mt-2" role="alert"> Thêm khoá học thành công! </div>';
     } else {
      // below msg display on form submit failed
      $msg = '<div class="alert alert-danger col-sm-6 ml-5 mt-2" role="alert"> Không thể thêm khoá học </div>';
     }
   }
  }
}
 ?>
<div class="col-sm-6 mt-5  mx-3 jumbotron">
  <h3 class="text-center">Thêm Khoá học mới</h3>
  <form action="" method="POST" enctype="multipart/form-data">
    <div class="form-group">
      <label for="course_name">Tên khoá học</label>
      <input type="text" class="form-control" id="course_name" name="course_name" required>
    </div>
    <div class="form-group">
      <label for="course_desc">Mô tả khoá học</label>
      <textarea class="form-control" id="course_desc" name="course_desc" row=2 required></textarea>
    </div>
    <div class="form-group">
      <label for="course_author">Tác giả</label>
      <input type="text" class="form-control" id="course_author" name="course_author" required>
    </div>
    <div class="form-group">
      <label for="course_duration">Thời lượng khoá học</label>
      <input type="text" class="form-control" id="course_duration" name="course_duration" required>
    </div>
    <div class="form-group">
      <label for="course_original_price">Giá gốc (VNĐ)</label>
      <input type="text" class="form-control" id="course_original_price" name="course_original_price" onkeypress="isInputNumber(event)" required>
    </div>
    <div class="form-group">
      <label for="course_price">Giá bán thực tế (VNĐ)</label>
      <input type="text" class="form-control" id="course_price" name="course_price" onkeypress="isInputNumber(event)" required>
    </div>
    <div class="form-group">
      <label for="course_img">Ảnh đại diện (Tối đa 2MB)</label>
      <input type="file" class="form-control-file" id="course_img" name="course_img" accept=".jpg, .jpeg, .png, .webp" required>
    </div>
    <div class="text-center">
      <button type="submit" class="btn btn-primary" id="courseSubmitBtn" name="courseSubmitBtn">Gửi</button>
      <a href="courses.php" class="btn btn-secondary">Đóng</a>
    </div>
    <?php if(isset($msg)) {echo $msg; } ?>
  </form>
</div>
<!-- Only Number for input fields -->
<script>
  function isInputNumber(evt) {
    var ch = String.fromCharCode(evt.which);
    if (!(/[0-9]/.test(ch))) {
      evt.preventDefault();
    }
  }
</script>
</div>  <!-- div Row close from header -->
</div>  <!-- div Conatiner-fluid close from header -->

<?php
include('./adminInclude/footer.php'); 
?>
