<?php
require_once(__DIR__ . '/csrf.php');

if(!function_exists('contact_text_length')) {
    function contact_text_length(string $value): int
    {
        if(function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }
}

$contactMsg = null;

if(isset($_POST['submitContact'])) {
    if(!isset($conn)) {
        include_once('dbConnection.php');
    }

    if(!csrf_verify($_POST['csrf_token'] ?? null)) {
        $contactMsg = ['type' => 'error', 'text' => 'Phiên gửi biểu mẫu đã hết hạn. Vui lòng thử lại.'];
    } else {
        $c_name = trim((string) ($_POST['name'] ?? ''));
        $c_subject = trim((string) ($_POST['subject'] ?? ''));
        $c_email = trim((string) ($_POST['email'] ?? ''));
        $c_message = trim((string) ($_POST['message'] ?? ''));

        $nameLength = contact_text_length($c_name);
        $subjectLength = contact_text_length($c_subject);
        $messageLength = contact_text_length($c_message);
        $emailLength = contact_text_length($c_email);

        if($c_name === '' || $c_email === '' || $c_message === '') {
            $contactMsg = ['type' => 'error', 'text' => 'Vui lòng nhập đầy đủ Họ tên, Email và Nội dung.'];
        } elseif(!filter_var($c_email, FILTER_VALIDATE_EMAIL)) {
            $contactMsg = ['type' => 'error', 'text' => 'Email không hợp lệ. Vui lòng kiểm tra lại.'];
        } elseif($nameLength < 2 || $nameLength > 100 || $emailLength > 191 || $subjectLength > 150 || $messageLength < 10 || $messageLength > 2000) {
            $contactMsg = ['type' => 'error', 'text' => 'Nội dung liên hệ không hợp lệ (độ dài không phù hợp).'];
        } else {
            $stmt = $conn->prepare('INSERT INTO contact_message (name, subject, email, message) VALUES (?, ?, ?, ?)');
            if($stmt) {
                $stmt->bind_param('ssss', $c_name, $c_subject, $c_email, $c_message);
                if($stmt->execute()) {
                    $contactMsg = ['type' => 'success', 'text' => 'Gửi tin nhắn liên hệ thành công. Chúng tôi sẽ phản hồi sớm nhất!'];
                } else {
                    $contactMsg = ['type' => 'error', 'text' => 'Lỗi hệ thống khi gửi liên hệ!'];
                }
                $stmt->close();
            } else {
                $contactMsg = ['type' => 'error', 'text' => 'Lỗi hệ thống khi gửi liên hệ!'];
            }
        }
    }
}
?>
<section id="Contact" class="bg-background-light px-6 py-16">
  <div class="mx-auto max-w-7xl">
    <h2 class="mb-10 text-center text-3xl font-black tracking-tight text-primary">Liên hệ với chúng tôi</h2>

    <div class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-sm lg:grid lg:grid-cols-12">
      <div class="p-8 lg:col-span-7 lg:p-10">
        <h3 class="mb-6 text-2xl font-bold text-slate-900">Gửi thư góp ý/hỗ trợ</h3>
        <?php if($contactMsg): ?>
        <div class="mb-5 flex items-center gap-3 rounded-xl border px-4 py-3 text-sm font-medium <?php echo $contactMsg['type'] === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-600'; ?>">
          <i class="fas <?php echo $contactMsg['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
          <span><?php echo htmlspecialchars($contactMsg['text'], ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <?php endif; ?>
        <form action="" method="post" class="space-y-4">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

          <div>
            <label for="contactName" class="mb-1.5 block text-sm font-semibold text-slate-700">Họ và tên</label>
            <input id="contactName" type="text" name="name" placeholder="Họ và tên của bạn" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-800 outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/10">
          </div>

          <div>
            <label for="contactSubject" class="mb-1.5 block text-sm font-semibold text-slate-700">Chủ đề</label>
            <input id="contactSubject" type="text" name="subject" placeholder="Chủ đề quan tâm" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-800 outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/10">
          </div>

          <div>
            <label for="contactEmail" class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
            <input id="contactEmail" type="email" name="email" placeholder="Địa chỉ email" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-800 outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/10">
          </div>

          <div>
            <label for="contactMessage" class="mb-1.5 block text-sm font-semibold text-slate-700">Nội dung</label>
            <textarea id="contactMessage" name="message" placeholder="Bạn cần hỗ trợ gì?" rows="5" required class="w-full resize-none rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-800 outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/10"></textarea>
          </div>

          <button class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-primary/90" type="submit" name="submitContact">
            Gửi tin nhắn
            <i class="fas fa-paper-plane text-xs"></i>
          </button>
        </form>
      </div>

      <aside class="bg-gradient-to-br from-primary to-sky-700 p-8 text-white lg:col-span-5 lg:p-10">
        <h3 class="mb-6 text-2xl font-bold">Trường Đại học Cần Thơ</h3>

        <div class="space-y-5 text-sm leading-relaxed text-white/90">
          <p class="flex items-start gap-3">
            <i class="fas fa-map-marker-alt mt-0.5 text-base text-emerald-300"></i>
            <span>Tòa nhà Khoa CNTT &amp; Truyền Thông, Khu II, Đ. 3/2, Phường Xuân Khánh, Q. Ninh Kiều, TP. Cần Thơ</span>
          </p>
          <p class="flex items-start gap-3">
            <i class="fas fa-phone-alt mt-0.5 text-base text-emerald-300"></i>
            <span>Hotline: 0292 3831 301</span>
          </p>
          <p class="flex items-start gap-3">
            <i class="fas fa-envelope mt-0.5 text-base text-emerald-300"></i>
            <span>E-mail: dhct@ctu.edu.vn</span>
          </p>
        </div>

        <div class="mt-8">
          <p class="mb-3 text-sm font-semibold text-white/90">Mạng xã hội</p>
          <div class="flex items-center gap-3">
            <a href="#" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/30 text-white transition-colors hover:bg-white hover:text-primary"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/30 text-white transition-colors hover:bg-white hover:text-primary"><i class="fab fa-youtube"></i></a>
            <a href="#" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/30 text-white transition-colors hover:bg-white hover:text-primary"><i class="fab fa-instagram"></i></a>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>
