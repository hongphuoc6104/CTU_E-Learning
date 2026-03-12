<?php 
include('./dbConnection.php');
session_start();
 if(!isset($_SESSION['stuLogEmail'])) {
  header("Location: login.php");
  exit;
 } else {
  header("Pragma: no-cache");
  header("Cache-Control: no-cache");
  header("Expires: 0"); 
  $stuEmail = $_SESSION['stuLogEmail'];
  ?>
  <!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<!-- Bootstrap CSS cho các class kế thừa cũ nếu có -->
<link rel="stylesheet" href="css/bootstrap.min.css">
<!-- Custom Font -->
<link href="https://fonts.googleapis.com/css?family=Ubuntu" rel="stylesheet">
<!-- Tailwind CSS từ AI Design -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<!-- Font Awesome (local) -->
<link rel="stylesheet" type="text/css" href="css/all.min.css">
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#003366",
                        "background-light": "#f5f7f8",
                        "accent-success": "#10b981"
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    }
                }
            }
        }
</script>
<style>
  /* Ghi đè xung đột CSS Bootstrap vs Tailwind */
  a { text-decoration: none !important; }
</style>
<title>Thanh Toán - CTU E-Learning</title>
</head>
<body class="bg-background-light font-display text-slate-900 min-h-screen">
<div class="relative flex h-full min-h-screen w-full flex-col overflow-x-hidden">

<header class="flex items-center justify-between border-b border-slate-200 px-10 py-4 bg-white/50 backdrop-blur-md sticky top-0 z-50 shadow-sm">
 <div class="flex items-center gap-4">
  <div class="text-primary">
   <i class="fas fa-graduation-cap text-4xl"></i>
  </div>
  <h2 class="text-slate-900 text-xl font-bold leading-tight tracking-tight">CTU E-Learning</h2>
 </div>
 <nav class="hidden md:flex flex-1 justify-end gap-8 items-center">
  <div class="flex items-center gap-9">
   <a class="text-slate-600 text-sm font-medium hover:text-primary transition-colors" href="index.php">Trang chủ</a>
   <a class="text-slate-600 text-sm font-medium hover:text-primary transition-colors" href="courses.php">Khóa học</a>
  </div>
 </nav>
</header>

<main class="flex-1 flex items-center justify-center p-6 relative overflow-hidden">
 <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white -z-10"></div>
 <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-primary/5 rounded-full blur-3xl -z-10"></div>
 <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-primary/10 rounded-full blur-3xl -z-10"></div>
 
 <div class="w-full max-w-4xl bg-white/60 backdrop-blur-xl border border-white/50 rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row">
  
  <div class="flex-1 p-10 md:p-12 border-b md:border-b-0 md:border-r border-slate-200">
   <div class="flex items-center gap-2 mb-8">
    <i class="fas fa-shopping-cart text-primary"></i>
    <h3 class="text-slate-500 uppercase tracking-widest text-xs font-bold">Xác nhận thanh toán</h3>
   </div>
   <h1 class="text-3xl font-bold text-slate-900 mb-8">Thông tin đơn hàng</h1>
   
   <form method="post" action="checkout_action.php" id="checkoutForm">
     <div class="space-y-6">
      <div class="flex flex-col gap-1">
       <span class="text-slate-500 text-sm">Mã Đơn Hàng</span>
       <input type="text" id="ORDER_ID" name="ORDER_ID" class="text-lg font-semibold text-slate-800 bg-transparent border-0 p-0 focus:ring-0" value="<?php echo "ORDS" . rand(10000,99999999)?>" readonly>
      </div>
      
      <div class="flex flex-col gap-1">
       <span class="text-slate-500 text-sm">Email Học Viên</span>
       <input type="text" id="CUST_ID" name="CUST_ID" class="text-lg font-semibold text-slate-800 bg-transparent border-0 p-0 focus:ring-0 w-full" value="<?php if(isset($stuEmail)){echo $stuEmail; }?>" readonly>
      </div>
      
      <div class="pt-4 border-t border-slate-200">
       <span class="text-slate-500 text-sm block mb-1">Tổng Thanh Toán (VNĐ)</span>
       <input type="text" title="TXN_AMOUNT" name="TXN_AMOUNT" class="text-4xl font-extrabold text-primary bg-transparent border-0 p-0 focus:ring-0 w-full" value="<?php if(isset($_POST['id'])){echo $_POST['id']; }?>" readonly>
      </div>
      
      <!-- Hidden Fields -->
      <input type="hidden" id="INDUSTRY_TYPE_ID" name="INDUSTRY_TYPE_ID" value="Retail">
      <input type="hidden" id="CHANNEL_ID" name="CHANNEL_ID" value="WEB">
      <input type="hidden" name="checkout_type" value="<?php echo isset($_POST['checkout_type']) ? $_POST['checkout_type'] : 'single'; ?>">
     </div>
  </div>
  
  <div class="w-full md:w-[380px] p-10 md:p-12 bg-slate-50/80 flex flex-col justify-center gap-6">
   <div class="text-center mb-4">
    <div class="inline-flex items-center justify-center p-4 bg-accent-success/10 rounded-full mb-4">
     <i class="fas fa-shield-alt text-accent-success text-4xl"></i>
    </div>
    <p class="text-slate-600 text-sm leading-relaxed px-4">
        Vui lòng kiểm tra kỹ thông tin trước khi hoàn tất giao dịch.
    </p>
   </div>
   
   <div class="flex flex-col gap-4">
    <button type="submit" class="w-full h-14 bg-accent-success hover:bg-emerald-600 text-white font-bold rounded-xl shadow-lg shadow-emerald-500/20 transition-all active:scale-[0.98] flex items-center justify-center gap-2" style="border:none;">
     <span>Xác nhận thanh toán</span>
     <i class="fas fa-arrow-right text-sm"></i>
    </button>
    <a href="javascript:history.back()" class="w-full h-14 bg-transparent border-2 border-slate-200 text-slate-600 font-semibold rounded-xl hover:bg-slate-100 transition-all flex items-center justify-center" style="text-decoration: none;">
        Trở về
    </a>
   </div>
   
   <div class="mt-8 flex items-center justify-center gap-2 text-slate-400">
    <i class="fas fa-lock text-lg"></i>
    <p class="text-[11px] font-medium leading-tight text-center">
        Giao dịch của bạn được mã hoá<br/>và bảo vệ an toàn 256-bit
    </p>
   </div>
   </form>
   
  </div>
 </div>
</main>

<footer class="py-6 px-10 border-t border-slate-200 bg-white/50 backdrop-blur-sm mt-auto">
 <div class="max-w-7xl mx-auto text-center">
  <p class="text-slate-500 text-sm">© 2026 CTU E-Learning. All rights reserved.</p>
 </div>
</footer>

</div>

<!-- Jquery and Boostrap JavaScript -->
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/popper.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<!-- Font Awesome JS -->
<script type="text/javascript" src="js/all.min.js"></script>
<!-- Custom JavaScript -->
<script type="text/javascript" src="js/custom.js"></script>

</body>
</html>
 <?php } ?>

