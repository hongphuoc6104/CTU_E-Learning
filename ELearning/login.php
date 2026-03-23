<?php 
  include('./dbConnection.php');
  // Header Include from mainInclude 
  include('./mainInclude/header.php'); 
?>
    <div class="pt-32 pb-16 bg-gradient-to-br from-primary to-slate-900 border-b border-primary/20 relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.22),transparent_60%)]"></div>
        <div class="absolute inset-0 bg-primary/40"></div>
        <div class="max-w-7xl mx-auto px-6 relative z-10 text-center">
            <h1 class="text-4xl md:text-5xl font-black text-white mb-4">Đăng nhập</h1>
            <p class="text-lg text-white/80 max-w-2xl mx-auto">Vui lòng đăng nhập để tiếp tục học tập và quản lý tài khoản của bạn.</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-20">
     <div class="flex justify-center">
      <div class="w-full md:w-5/12">
        <div class="bg-white shadow-xl rounded-2xl p-10 border border-slate-100">
          <h5 class="mb-8 text-2xl font-black text-primary text-center flex justify-center items-center gap-3"><i class="fas fa-sign-in-alt"></i> Đăng nhập</h5>
          <form role="form" id="stuLoginForm" class="space-y-6">
            <div>
              <label for="stuLogEmail" class="block text-sm font-semibold text-slate-700 mb-2">
                <i class="fas fa-envelope text-slate-400 mr-1"></i> Email
              </label>
              <input type="email" class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 transition-all" placeholder="Nhập email của bạn" name="stuLogEmail" id="stuLogEmail">
              <small id="statusLogMsg1" class="text-red-500 text-xs mt-1 block"></small>
            </div>
            <div>
              <label for="stuLogPass" class="block text-sm font-semibold text-slate-700 mb-2">
                <i class="fas fa-key text-slate-400 mr-1"></i> Mật khẩu
              </label>
              <input type="password" class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 transition-all" placeholder="Nhập mật khẩu" name="stuLogPass" id="stuLogPass">
            </div>
            <button type="button" class="w-full px-6 py-3.5 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex justify-center items-center gap-2" id="stuLoginBtn" onclick="checkStuLogin()">
              Đăng nhập <i class="fas fa-arrow-right"></i>
            </button>
          </form>
          <div class="mt-4 text-center">
            <small id="statusLogMsg" class="font-semibold"></small>
          </div>
          <div class="text-center mt-6 pt-6 border-t border-slate-100">
              <span class="text-slate-600 text-sm">Chưa có tài khoản? </span>
              <a href="signup.php" class="text-primary font-bold hover:underline text-sm">Đăng ký ngay</a>
          </div>
        </div>
      </div>
     </div>
    </div>

<?php 
// Contact Us
include('./contact.php'); 
?> 

<?php 
  // Footer Include from mainInclude 
  include('./mainInclude/footer.php'); 
?> 
