// Ajax Call for admin Login Verification
function checkAdminLogin() {
  var adminLogEmail = $("#adminLogEmail").val();
  var adminLogPass = $("#adminLogPass").val();
  $.ajax({
    url: "Admin/admin.php",
    type: "post",
    data: {
      checkLogemail: "checklogmail",
      adminLogEmail: adminLogEmail,
      adminLogPass: adminLogPass
    },
    success: function(data) {
      console.log(data);
      if (data == 0) {
        $("#statusAdminLogMsg").html(
          '<small class="alert alert-danger"> Email hoặc mật khẩu không đúng! </small>'
        );
      } else if (data == 1) {
        $("#statusAdminLogMsg").html(
          '<small class="alert alert-success"> Đăng nhập thành công! </small>'
        );
        // Empty Login Fields
        clearAdminLoginField();
        setTimeout(() => {
          window.location.href = "Admin/adminDashboard.php";
        }, 1000);
      }
    }
  });
}

// Empty Login Fields
function clearAdminLoginField() {
  $("#adminLoginForm").trigger("reset");
}

// Empty Login Fields and Status Msg
function clearAdminLoginWithStatus() {
  $("#statusAdminLogMsg").html(" ");
  clearAdminLoginField();
}
