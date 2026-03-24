<?php

define('TITLE', 'Tạo khóa học');
define('PAGE', 'add-course');

require_once(__DIR__ . '/instructorInclude/header.php');
require_once(__DIR__ . '/../upload_helpers.php');

$instructorId = instructor_current_id();
$profileName = (string) ($instructorProfile['ins_name'] ?? '');

$form = [
    'course_name' => '',
    'course_desc' => '',
    'course_duration' => '',
    'course_price' => '',
    'course_original_price' => '',
    'course_type' => 'self_paced',
];
$error = '';

if (isset($_POST['create_course'])) {
    $form['course_name'] = trim((string) ($_POST['course_name'] ?? ''));
    $form['course_desc'] = trim((string) ($_POST['course_desc'] ?? ''));
    $form['course_duration'] = trim((string) ($_POST['course_duration'] ?? ''));
    $form['course_price'] = trim((string) ($_POST['course_price'] ?? ''));
    $form['course_original_price'] = trim((string) ($_POST['course_original_price'] ?? ''));
    $form['course_type'] = trim((string) ($_POST['course_type'] ?? 'self_paced'));

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Phien gui bieu mau da het han. Vui long thu lai.';
    }

    if ($error === '' && ($form['course_name'] === '' || $form['course_desc'] === '' || $form['course_duration'] === '')) {
        $error = 'Vui lòng nhập đầy đủ tên, mô tả và thời lượng khóa học.';
    }

    $coursePrice = filter_var($form['course_price'], FILTER_VALIDATE_INT);
    $courseOriginalPrice = filter_var($form['course_original_price'], FILTER_VALIDATE_INT);

    if ($error === '' && ($coursePrice === false || $courseOriginalPrice === false)) {
        $error = 'Giá khóa học phải là số nguyên hợp lệ.';
    }

    if ($error === '' && ((int) $coursePrice < 0 || (int) $courseOriginalPrice < 0)) {
        $error = 'Giá khóa học không được là số âm.';
    }

    if ($error === '' && (int) $courseOriginalPrice < (int) $coursePrice) {
        $error = 'Gia goc khong duoc nho hon gia ban.';
    }

    if ($error === '' && !in_array($form['course_type'], ['self_paced', 'blended'], true)) {
        $error = 'Loại khóa học không hợp lệ.';
    }

    $courseImageName = (string) ($_FILES['course_img']['name'] ?? '');
    $courseImageTmp = (string) ($_FILES['course_img']['tmp_name'] ?? '');
    $courseImageSize = (int) ($_FILES['course_img']['size'] ?? 0);
    $courseImageError = (int) ($_FILES['course_img']['error'] ?? UPLOAD_ERR_NO_FILE);
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    $courseExt = strtolower(pathinfo($courseImageName, PATHINFO_EXTENSION));

    if ($error === '' && $courseImageError === UPLOAD_ERR_NO_FILE) {
        $error = 'Vui long tai len anh dai dien khoa hoc.';
    }

    if ($error === '' && !in_array($courseExt, $allowedExt, true)) {
        $error = 'Dinh dang anh khong hop le. Chi chap nhan jpg, jpeg, png, webp.';
    }

    if ($error === '' && $courseImageSize > 2 * 1024 * 1024) {
        $error = 'Anh qua lon. Kich thuoc toi da 2MB.';
    }

    if ($error === '') {
        $uploadResult = app_upload_store_file(
            $courseImageTmp,
            $courseImageName,
            __DIR__ . '/../image/courseimg/',
            'image/courseimg',
            'thư mục ảnh khóa học',
            'course'
        );

        if (!($uploadResult['ok'] ?? false)) {
            $error = (string) ($uploadResult['message'] ?? 'Khong the luu anh khoa hoc. Vui long thu lai.');
        } else {
            $imageDisk = (string) ($uploadResult['disk_path'] ?? '');
            $imageDb = (string) ($uploadResult['db_path'] ?? '');
            $stmt = $conn->prepare(
                'INSERT INTO course (course_name, course_desc, course_author, course_img, course_duration, course_price, course_original_price, instructor_id, course_status, course_type, published_at, is_deleted) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'draft\', ?, NULL, 0)'
            );

            if (!$stmt) {
                if (is_file($imageDisk)) {
                    @unlink($imageDisk);
                }
                $error = 'Khong the tao khoa hoc luc nay.';
            } else {
                $author = $profileName !== '' ? $profileName : (string) ($instructorProfile['ins_email'] ?? 'Instructor');
                $priceVal = (int) $coursePrice;
                $origVal = (int) $courseOriginalPrice;
                $stmt->bind_param(
                    'sssssiiis',
                    $form['course_name'],
                    $form['course_desc'],
                    $author,
                    $imageDb,
                    $form['course_duration'],
                    $priceVal,
                    $origVal,
                    $instructorId,
                    $form['course_type']
                );

                $ok = $stmt->execute();
                $stmt->close();

                if (!$ok) {
                    if (is_file($imageDisk)) {
                        @unlink($imageDisk);
                    }
                    $error = 'Khong the tao khoa hoc luc nay.';
                } else {
                    instructor_set_flash('success', 'Da tao khoa hoc draft thanh cong.');
                    header('Location: courses.php');
                    exit;
                }
            }
        }
    }
}
?>

<section class="mb-6">
  <h1 class="m-0 text-2xl font-black text-slate-900">Tao khoa hoc draft</h1>
  <p class="m-0 mt-1 text-sm text-slate-500">Giảng viên duoc tao draft, sau do bo sung noi dung va gui duyet.</p>
</section>

<?php if ($error !== ''): ?>
  <section class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
    <i class="fas fa-exclamation-circle mr-1"></i>
    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
  </section>
<?php endif; ?>

<section class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
  <form method="post" enctype="multipart/form-data" class="space-y-5">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

    <div>
      <label for="course_name" class="mb-2 block text-sm font-bold text-slate-700">Tên khóa học</label>
      <input type="text" id="course_name" name="course_name" value="<?php echo htmlspecialchars($form['course_name'], ENT_QUOTES, 'UTF-8'); ?>" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="VD: React Frontend Live Bootcamp">
    </div>

    <div>
      <label for="course_desc" class="mb-2 block text-sm font-bold text-slate-700">Mô tả khóa học</label>
      <textarea id="course_desc" name="course_desc" rows="5" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="Mo ta noi dung va gia tri khoa hoc..."><?php echo htmlspecialchars($form['course_desc'], ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label for="course_duration" class="mb-2 block text-sm font-bold text-slate-700">Thoi luong</label>
        <input type="text" id="course_duration" name="course_duration" value="<?php echo htmlspecialchars($form['course_duration'], ENT_QUOTES, 'UTF-8'); ?>" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="VD: 6 tuan">
      </div>
      <div>
        <label for="course_type" class="mb-2 block text-sm font-bold text-slate-700">Loại khóa học</label>
        <select id="course_type" name="course_type" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
          <option value="self_paced" <?php echo $form['course_type'] === 'self_paced' ? 'selected' : ''; ?>>Self paced</option>
          <option value="blended" <?php echo $form['course_type'] === 'blended' ? 'selected' : ''; ?>>Blended (co live session)</option>
        </select>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label for="course_original_price" class="mb-2 block text-sm font-bold text-slate-700">Gia goc (VND)</label>
        <input type="number" min="0" id="course_original_price" name="course_original_price" value="<?php echo htmlspecialchars($form['course_original_price'], ENT_QUOTES, 'UTF-8'); ?>" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="1200000">
      </div>
      <div>
        <label for="course_price" class="mb-2 block text-sm font-bold text-slate-700">Gia ban (VND)</label>
        <input type="number" min="0" id="course_price" name="course_price" value="<?php echo htmlspecialchars($form['course_price'], ENT_QUOTES, 'UTF-8'); ?>" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="899000">
      </div>
    </div>

    <div>
      <label for="course_img" class="mb-2 block text-sm font-bold text-slate-700">Anh dai dien (jpg, jpeg, png, webp - toi da 2MB)</label>
      <input type="file" id="course_img" name="course_img" accept=".jpg,.jpeg,.png,.webp" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-2 file:text-xs file:font-bold file:text-primary hover:file:bg-primary/20">
    </div>

    <div class="flex flex-wrap items-center gap-3 pt-2">
      <button type="submit" name="create_course" class="inline-flex items-center gap-2 rounded-xl border-0 bg-primary px-5 py-3 text-sm font-extrabold text-white transition hover:bg-primary/90">
        <i class="fas fa-plus-circle"></i>
        <span>Tao draft</span>
      </button>
      <a href="courses.php" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 no-underline">
        <i class="fas fa-arrow-left"></i>
        <span>Quay lại</span>
      </a>
    </div>
  </form>
</section>

<?php require_once(__DIR__ . '/instructorInclude/footer.php'); ?>
