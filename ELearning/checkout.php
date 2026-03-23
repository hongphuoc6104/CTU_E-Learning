<?php
include('./dbConnection.php');
require_once(__DIR__ . '/session_bootstrap.php');
secure_session_start();

if (!isset($_SESSION['stuLogEmail'])) {
    header('Location: login.php');
    exit;
}

header('Pragma: no-cache');
header('Cache-Control: no-cache');
header('Expires: 0');

$stuEmail = (string) $_SESSION['stuLogEmail'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: courses.php');
    exit;
}

$checkoutType = isset($_POST['checkout_type']) ? (string) $_POST['checkout_type'] : '';
if ($checkoutType !== 'single' && $checkoutType !== 'cart') {
    header('Location: courses.php');
    exit;
}

$courseIds = [];
$totalAmount = 0;

if ($checkoutType === 'single') {
    $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    if (!$courseId) {
        header('Location: courses.php');
        exit;
    }

    $courseStmt = $conn->prepare('SELECT course_id, course_price FROM course WHERE course_id = ? AND is_deleted = 0 LIMIT 1');
    if (!$courseStmt) {
        header('Location: courses.php');
        exit;
    }

    $courseStmt->bind_param('i', $courseId);
    $courseStmt->execute();
    $courseResult = $courseStmt->get_result();
    $courseRow = $courseResult ? $courseResult->fetch_assoc() : null;
    $courseStmt->close();

    if (!$courseRow) {
        header('Location: courses.php');
        exit;
    }

    $ownedStmt = $conn->prepare('SELECT 1 FROM courseorder WHERE stu_email = ? AND course_id = ? AND status = ? AND is_deleted = 0 LIMIT 1');
    if (!$ownedStmt) {
        header('Location: courses.php');
        exit;
    }

    $successStatus = 'TXN_SUCCESS';
    $ownedStmt->bind_param('sis', $stuEmail, $courseId, $successStatus);
    $ownedStmt->execute();
    $ownedStmt->store_result();
    $isOwned = $ownedStmt->num_rows > 0;
    $ownedStmt->close();

    if ($isOwned) {
        header('Location: Student/myCourse.php');
        exit;
    }

    $courseIds[] = (int) $courseRow['course_id'];
    $totalAmount = (int) $courseRow['course_price'];
} else {
    $cartStmt = $conn->prepare(
        'SELECT c.course_id, c.course_price '
        . 'FROM cart ct '
        . 'INNER JOIN course c ON c.course_id = ct.course_id '
        . 'WHERE ct.stu_email = ? AND ct.is_deleted = 0 AND c.is_deleted = 0 '
        . 'ORDER BY ct.cart_id ASC'
    );

    if (!$cartStmt) {
        header('Location: Student/myCart.php');
        exit;
    }

    $cartStmt->bind_param('s', $stuEmail);
    $cartStmt->execute();
    $cartResult = $cartStmt->get_result();
    while ($row = $cartResult->fetch_assoc()) {
        $courseIds[] = (int) $row['course_id'];
        $totalAmount += (int) $row['course_price'];
    }
    $cartStmt->close();

    if (empty($courseIds)) {
        header('Location: Student/myCart.php');
        exit;
    }
}

$pendingToken = bin2hex(random_bytes(32));
$orderReference = 'ORDS' . random_int(100000, 99999999);

$_SESSION['pending_checkout'] = [
    'token' => $pendingToken,
    'order_reference' => $orderReference,
    'checkout_type' => $checkoutType,
    'stu_email' => $stuEmail,
    'course_ids' => array_values(array_unique($courseIds)),
    'total_amount' => $totalAmount,
    'created_at' => time(),
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<!-- Custom Font -->
<link href="https://fonts.googleapis.com/css?family=Ubuntu" rel="stylesheet">
<!-- Compiled Tailwind CSS -->
<link rel="stylesheet" href="css/tailwind.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<!-- Font Awesome (local) -->
<link rel="stylesheet" type="text/css" href="css/all.min.css">
<style>
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
        <span class="text-sm text-slate-500">Mã đơn hàng</span>
        <p class="m-0 text-lg font-semibold text-slate-800"><?php echo htmlspecialchars($orderReference, ENT_QUOTES, 'UTF-8'); ?></p>
       </div>

       <div class="flex flex-col gap-1">
        <span class="text-sm text-slate-500">Email học viên</span>
        <p class="m-0 break-all text-lg font-semibold text-slate-800"><?php echo htmlspecialchars($stuEmail, ENT_QUOTES, 'UTF-8'); ?></p>
       </div>

       <div class="border-t border-slate-200 pt-4">
        <span class="mb-1 block text-sm text-slate-500">Tổng thanh toán (VNĐ)</span>
        <p class="m-0 text-4xl font-extrabold text-primary"><?php echo number_format((int) $totalAmount); ?></p>
       </div>

      <input type="hidden" id="QR_DATA" name="QR_DATA" value="">
      <input type="hidden" name="pending_token" value="<?php echo htmlspecialchars($pendingToken, ENT_QUOTES, 'UTF-8'); ?>">
       </div>
  </div>
  
  <div class="w-full md:w-[380px] p-10 md:p-12 bg-slate-50/80 flex flex-col justify-center gap-6">
    <div class="text-center mb-4">
     <div class="mb-4 inline-flex items-center justify-center rounded-full bg-emerald-100 p-4">
      <i class="fas fa-shield-alt text-4xl text-emerald-600"></i>
     </div>
    <p class="text-slate-600 text-sm leading-relaxed px-4">
        Vui lòng kiểm tra kỹ thông tin trước khi hoàn tất giao dịch.
    </p>
   </div>

   <!-- QR payment image -->
   <div class="bg-white/70 border border-slate-200 rounded-2xl p-4">
     <p class="text-slate-700 text-sm font-bold mb-3 text-center">Mã QR thanh toán</p>
     <div class="flex items-center justify-center">
       <img
         id="paymentQrImg"
         src="image/courseimg/QRTRAN.png"
         alt="QRTRAN"
         class="w-64 h-64 max-w-full max-h-[320px] object-contain rounded-xl border border-slate-200 bg-white"
       />
     </div>
     <p class="text-center text-slate-500 text-xs mt-3">
       Bấm <b>Quét QR khi thanh toán</b> để quét mã QR đang hiển thị ở đây.
     </p>
   </div>
   
   <div class="flex flex-col gap-4">
     <button type="submit" class="flex h-14 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 font-bold text-white shadow-lg shadow-emerald-500/20 transition-all active:scale-[0.98] hover:bg-emerald-700" style="border:none;">
     <span>Xác nhận thanh toán</span>
     <i class="fas fa-arrow-right text-sm"></i>
    </button>
    <button type="button" id="openQrScannerBtn" class="w-full h-14 bg-transparent border-2 border-emerald-200 text-emerald-800 font-semibold rounded-xl hover:bg-emerald-50 transition-all flex items-center justify-center gap-2" style="text-decoration: none;">
      <i class="fas fa-qrcode"></i>
      <span>Quét QR khi thanh toán</span>
    </button>
    <p id="qrStatusText" class="text-center text-slate-500 text-xs">
      Chưa quét QR
    </p>
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

<!-- QR Scanner Modal -->
<div id="qrScannerModal" class="hidden fixed inset-0 z-[1000] bg-black/60 items-center justify-center p-4">
  <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
    <div class="p-4 border-b border-slate-200 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <i class="fas fa-qrcode text-primary"></i>
        <h3 class="text-primary font-bold">Quét mã QR</h3>
      </div>
      <button type="button" id="closeQrScannerBtn" class="p-2 rounded-lg hover:bg-slate-100" aria-label="Đóng">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <div class="p-4">
      <div class="relative">
        <video id="qrVideo" class="w-full rounded-xl bg-slate-100 border border-slate-200" playsinline></video>
        <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
          <div class="w-56 h-56 rounded-xl border-2 border-emerald-500/80 shadow-[0_0_0_999px_rgba(16,185,129,0.08)]"></div>
        </div>
      </div>

      <div id="qrManualWrap" class="mt-3 hidden">
        <p class="text-slate-600 text-sm mb-2">Dán mã QR thủ công:</p>
        <textarea id="qrManualInput" rows="3" class="w-full border border-slate-200 rounded-lg p-2 text-sm" placeholder="Ví dụ: vietqr/.... hoặc chuỗi QR ..."></textarea>
        <div class="mt-2 flex gap-2">
          <button type="button" id="saveManualQrBtn" class="h-10 flex-1 rounded-lg bg-emerald-600 font-bold text-white hover:bg-emerald-700">Lưu mã</button>
        </div>
      </div>

      <p id="qrResultText" class="mt-3 text-center text-emerald-700 font-semibold text-sm"></p>
    </div>

    <div class="p-4 border-t border-slate-200 flex gap-2">
      <button type="button" id="stopQrScannerBtn" class="flex-1 h-10 bg-transparent border border-slate-300 text-slate-700 font-semibold rounded-lg hover:bg-slate-50">Dừng</button>
    </div>
  </div>
</div>

<footer class="py-6 px-10 border-t border-slate-200 bg-white/50 backdrop-blur-sm mt-auto">
 <div class="max-w-7xl mx-auto text-center">
  <p class="text-slate-500 text-sm">© 2026 CTU E-Learning. All rights reserved.</p>
 </div>
</footer>

</div>

<!-- Font Awesome JS -->
<script type="text/javascript" src="js/all.min.js"></script>
<!-- Custom JavaScript -->
<script type="text/javascript" src="js/custom.js"></script>

<!-- QR Scanner (VietQR/QR payload) -->
<script>
  (function () {
    const qrDataInput = document.getElementById("QR_DATA");
    if (!qrDataInput) return;

    const openBtn = document.getElementById("openQrScannerBtn");
    const stopBtn = document.getElementById("stopQrScannerBtn");
    const closeBtn = document.getElementById("closeQrScannerBtn");
    const manualWrap = document.getElementById("qrManualWrap");
    const manualInput = document.getElementById("qrManualInput");
    const saveManualBtn = document.getElementById("saveManualQrBtn");

    const modal = document.getElementById("qrScannerModal");
    const video = document.getElementById("qrVideo");
    const statusEl = document.getElementById("qrStatusText");
    const resultEl = document.getElementById("qrResultText");

    if (!modal || !video) return;

    let stream = null;
    let scanning = false;
    let detector = null;

    function setStatus(text) {
      if (statusEl) statusEl.textContent = text;
    }

    function showModal() {
      modal.classList.remove("hidden");
      modal.classList.add("flex");
    }

    function hideModal() {
      modal.classList.add("hidden");
      modal.classList.remove("flex");
      if (resultEl) resultEl.textContent = "";
    }

    async function stopScanner() {
      scanning = false;
      if (stream) {
        stream.getTracks().forEach((t) => t.stop());
        stream = null;
      }
    }

    async function startScanner() {
      if (!("mediaDevices" in navigator) || !navigator.mediaDevices.getUserMedia) {
        setStatus("Trình duyệt không hỗ trợ camera.");
        return;
      }

      // Reset previous state
      qrDataInput.value = "";
      if (manualWrap) manualWrap.classList.add("hidden");
      setStatus("Đang mở camera...");

      // If BarcodeDetector is not supported, fall back to manual paste.
      if (!("BarcodeDetector" in window)) {
        setStatus("Trình duyệt chưa hỗ trợ quét tự động (BarcodeDetector).");
        if (manualWrap) manualWrap.classList.remove("hidden");
        return;
      }

      try {
        detector = new BarcodeDetector({ formats: ["qr_code"] });
      } catch (e) {
        setStatus("Không thể khởi tạo BarcodeDetector.");
        if (manualWrap) manualWrap.classList.remove("hidden");
        return;
      }

      try {
        stream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: "environment" },
          audio: false
        });
      } catch (e) {
        setStatus("Chưa cấp quyền camera. Vui lòng kiểm tra lại quyền truy cập.");
        return;
      }

      video.srcObject = stream;
      await video.play();

      scanning = true;
      setStatus("Đang quét... Vui lòng đưa QR vào khung.");

      // Scan loop
      while (scanning) {
        try {
          const barcodes = await detector.detect(video);
          if (barcodes && barcodes.length > 0) {
            const rawValue = barcodes[0].rawValue || "";
            if (rawValue) {
              qrDataInput.value = rawValue;
              if (resultEl) resultEl.textContent = "Đã quét mã QR thành công!";
              setStatus("Đã quét xong.");
              await stopScanner();
              hideModal();
              return;
            }
          }
        } catch (e) {
          // Ignore detection errors and continue scanning
        }
        await new Promise((r) => setTimeout(r, 300));
      }
    }

    // Auto-detect QR payload from the QR image itself (no camera).
    async function autoDetectFromImage() {
      const img = document.getElementById("paymentQrImg");
      if (!img) return;

      // BarcodeDetector is required for decoding QR payload.
      if (!("BarcodeDetector" in window)) {
        setStatus("Trình duyệt chưa hỗ trợ đọc QR tự động. Bạn hãy quét bằng camera.");
        if (manualWrap) manualWrap.classList.remove("hidden");
        return;
      }

      try {
        const tmpDetector = new BarcodeDetector({ formats: ["qr_code"] });
        detector = tmpDetector; // reuse later for camera scanning

        // Ensure image is loaded before detecting.
        if (!img.complete) {
          await new Promise((resolve) => img.addEventListener("load", resolve, { once: true }));
        }

        setStatus("Đang đọc mã QR...");
        const barcodes = await tmpDetector.detect(img);
        if (barcodes && barcodes.length > 0) {
          const rawValue = barcodes[0].rawValue || "";
          if (rawValue) {
            qrDataInput.value = rawValue;
            if (resultEl) resultEl.textContent = "Đã tự đọc mã QR thành công!";
            setStatus("QR đã được tự điền.");
            if (manualWrap) manualWrap.classList.add("hidden");
          } else {
            setStatus("Không đọc được nội dung từ QR.");
            if (manualWrap) manualWrap.classList.remove("hidden");
          }
        } else {
          setStatus("Không tìm thấy mã QR trong ảnh.");
          if (manualWrap) manualWrap.classList.remove("hidden");
        }
      } catch (e) {
        setStatus("Không thể đọc QR từ ảnh. Bạn hãy quét bằng camera.");
        if (manualWrap) manualWrap.classList.remove("hidden");
      }
    }

    // Run auto-detect on page load.
    autoDetectFromImage();

    if (openBtn) {
      openBtn.addEventListener("click", async function () {
        showModal();
        // Start only after modal opens
        await startScanner();
      });
    }

    if (stopBtn) {
      stopBtn.addEventListener("click", async function () {
        await stopScanner();
        hideModal();
      });
    }

    if (closeBtn) {
      closeBtn.addEventListener("click", async function () {
        await stopScanner();
        hideModal();
      });
    }

    if (saveManualBtn) {
      saveManualBtn.addEventListener("click", function () {
        const val = (manualInput ? manualInput.value : "").trim();
        if (!val) {
          setStatus("Vui lòng nhập mã QR trước khi lưu.");
          return;
        }
        qrDataInput.value = val;
        setStatus("Đã lưu mã QR (nhập thủ công).");
        hideModal();
      });
    }
  })();
</script>

</body>
</html>

