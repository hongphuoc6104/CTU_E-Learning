<form id="stuRegForm" class="space-y-5">
  <div>
    <label for="stuname" class="mb-1.5 block text-sm font-semibold text-slate-700">
      <i class="fas fa-user mr-1 text-slate-400"></i>
      Họ và tên
    </label>
    <input type="text" id="stuname" name="stuname" placeholder="Nhập họ tên" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-800 outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/10">
    <small id="statusMsg1" class="mt-1.5 block text-xs text-red-500"></small>
  </div>

  <div>
    <label for="stuemail" class="mb-1.5 block text-sm font-semibold text-slate-700">
      <i class="fas fa-envelope mr-1 text-slate-400"></i>
      Email
    </label>
    <input type="email" id="stuemail" name="stuemail" placeholder="Nhập email" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-800 outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/10">
    <small id="statusMsg2" class="mt-1.5 block text-xs text-red-500"></small>
    <small class="mt-1.5 block text-xs text-slate-400">Chúng tôi không chia sẻ email của bạn cho bên thứ ba.</small>
  </div>

  <div>
    <label for="stupass" class="mb-1.5 block text-sm font-semibold text-slate-700">
      <i class="fas fa-key mr-1 text-slate-400"></i>
      Mật khẩu
    </label>
    <input type="password" id="stupass" name="stupass" placeholder="Tạo mật khẩu" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-800 outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/10">
    <small id="statusMsg3" class="mt-1.5 block text-xs text-red-500"></small>
  </div>
</form>
