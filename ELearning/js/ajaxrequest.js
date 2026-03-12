$(document).ready(function() {
  // Ajax Call for Already Exists Email Verification
  $("#stuemail").on("keypress blur", function() {
    var reg = /^[A-Z0-9._%+-]+@([A-Z0-9-]+\.)+[A-Z]{2,4}$/i;
    var stuemail = $("#stuemail").val();
    $.ajax({
      url: "Student/addstudent.php",
      type: "post",
      data: { checkemail: "checkmail", stuemail: stuemail },
      success: function(data) {
        if (data != 0) {
          $("#statusMsg2").html('<small style="color:red;"> Email đã được đăng ký! </small>');
          $("#signup").attr("disabled", true);
        } else if (data == 0 && reg.test(stuemail)) {
          $("#statusMsg2").html('<small style="color:green;"> Email hợp lệ! </small>');
          $("#signup").attr("disabled", false);
        } else if (!reg.test(stuemail)) {
          $("#statusMsg2").html('<small style="color:red;"> Vui lòng nhập email hợp lệ (vd: example@mail.com) </small>');
          $("#signup").attr("disabled", false);
        }
        if (stuemail == "") {
          $("#statusMsg2").html('<small style="color:red;"> Vui lòng nhập Email! </small>');
        }
      }
    });
  });

  // Load Cart count when page ready
  if($('#cartCount').length > 0) {
      updateCartCount();
  }
});

// Thêm sản phẩm vào giỏ hàng
function addToCart(courseId) {
    $.ajax({
        url: 'cart_api.php',
        method: 'POST',
        data: { action: 'add', course_id: courseId },
        success: function(resp) {
            console.log(resp);
            if(resp.status == 'success') {
                showToast("Thêm vào giỏ hàng thành công!", "success");
                updateCartCount();
            } else if(resp.status == 'info') {
                showToast(resp.msg, "info");
            } else {
                showToast(resp.msg, "error");
            }
        },
        error: function() {
            showToast("Lỗi kết nối. Vui lòng đăng nhập!", "error");
        }
    });
}

function updateCartCount() {
    $.ajax({
        url: 'cart_api.php',
        method: 'POST',
        data: { action: 'count' },
        success: function(resp) {
            if(resp.status == 'success') {
                $('#cartCount').text(resp.count);
            }
        }
    });
}

// Hàm hiển thị thông báo Toast
function showToast(message, type) {
    var icon = type == 'success' ? 'fa-check-circle text-success' : 'fa-info-circle text-warning';
    var toastHtml = `<div class="custom-toast">
                        <span><i class="fas ${icon} mr-2"></i> ${message}</span>
                     </div>`;
    
    if($('#toast-container').length == 0) {
        $('body').append('<div id="toast-container"></div>');
    }
    
    var $toast = $(toastHtml);
    $('#toast-container').append($toast);
    
    setTimeout(function() {
        $toast.fadeOut(400, function() { $(this).remove(); });
    }, 3000);
}

// Các hàm gốc khác đã được rút gọn để dễ nhìn
function addStu() {
  // Logic cũ giữ nguyên, chỉ dịch thông báo
  var stuname = $("#stuname").val();
  var stuemail = $("#stuemail").val();
  var stupass = $("#stupass").val();
  
  if (stuname.trim() == "") { $("#statusMsg1").html('<small style="color:red;"> Nhập Họ Tên! </small>'); return false; }
  else if (stuemail.trim() == "") { $("#statusMsg2").html('<small style="color:red;"> Nhập Email! </small>'); return false; }
  else if (stupass.trim() == "") { $("#statusMsg3").html('<small style="color:red;"> Nhập Password! </small>'); return false; }
  else {
    $.ajax({
      url: "Student/addstudent.php",
      type: "post",
      data: { stusignup: "stusignup", stuname: stuname, stuemail: stuemail, stupass: stupass },
      success: function(data) {
        if (data == "OK") {
          $("#successMsg").html('<span class="alert alert-success d-inline-block mt-3"> Đăng ký thành công! Đang chuyển đến trang đăng nhập... </span>');
          clearStuRegField();
          setTimeout(() => { window.location.href = "login.php"; }, 2000);
        } else if (data == "Failed") {
          $("#successMsg").html('<span class="alert alert-danger d-inline-block mt-3"> Đăng ký thất bại! </span>');
        }
      }
    });
  }
}

function checkStuLogin() {
  var stuLogEmail = $("#stuLogEmail").val();
  var stuLogPass = $("#stuLogPass").val();
  $.ajax({
    url: "Student/addstudent.php",
    type: "post",
    data: { checkLogemail: "checklogmail", stuLogEmail: stuLogEmail, stuLogPass: stuLogPass },
    success: function(data) {
      if (data == 0) {
        $("#statusLogMsg").html('<small class="alert alert-danger"> Email hoặc mật khẩu không đúng! </small>');
      } else if (data == 1) {
        $("#statusLogMsg").html('<div class="spinner-border text-success" role="status"></div>');
        setTimeout(() => { window.location.href = "index.php"; }, 1000);
      }
    }
  });
}

function clearStuRegField() {
  $("#stuRegForm").trigger("reset");
  $("#statusMsg1").html(" ");
  $("#statusMsg2").html(" ");
  $("#statusMsg3").html(" ");
}
function clearAllStuReg() {
  $("#successMsg").html(" ");
  clearStuRegField();
}
function clearStuLoginField() {
  $("#stuLoginForm").trigger("reset");
}
function clearStuLoginWithStatus() {
  $("#statusLogMsg").html(" ");
  clearStuLoginField();
}
