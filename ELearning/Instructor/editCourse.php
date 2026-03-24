<?php

define('TITLE', 'Chinh sua khoa hoc');
define('PAGE', 'courses');

require_once(__DIR__ . '/instructorInclude/header.php');

$instructorId = instructor_current_id();
$courseId = (int) ($_GET['id'] ?? $_POST['course_id'] ?? 0);

if ($courseId <= 0) {
    instructor_set_flash('error', 'Khoa hoc khong hop le.');
    header('Location: courses.php');
    exit;
}

$course = instructor_find_owned_course($conn, $courseId, $instructorId);
if (!$course) {
    instructor_set_flash('error', 'Ban khong duoc phep chinh sua khoa hoc nay.');
    header('Location: courses.php');
    exit;
}

$form = [
    'course_name' => (string) ($course['course_name'] ?? ''),
    'course_desc' => (string) ($course['course_desc'] ?? ''),
    'course_duration' => (string) ($course['course_duration'] ?? ''),
    'course_price' => (string) ($course['course_price'] ?? '0'),
    'course_original_price' => (string) ($course['course_original_price'] ?? '0'),
    'course_type' => (string) ($course['course_type'] ?? 'self_paced'),
];
$error = '';

if (isset($_POST['update_course'])) {
    $form['course_name'] = trim((string) ($_POST['course_name'] ?? ''));
    $form['course_desc'] = trim((string) ($_POST['course_desc'] ?? ''));
    $form['course_duration'] = trim((string) ($_POST['course_duration'] ?? ''));
    $form['course_price'] = trim((string) ($_POST['course_price'] ?? ''));
    $form['course_original_price'] = trim((string) ($_POST['course_original_price'] ?? ''));
    $form['course_type'] = trim((string) ($_POST['course_type'] ?? 'self_paced'));

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Phien gui bieu mau da het han.';
    }

    if ($error === '' && ($form['course_name'] === '' || $form['course_desc'] === '' || $form['course_duration'] === '')) {
        $error = 'Vui long nhap day du ten, mo ta va thoi luong khoa hoc.';
    }

    $priceVal = filter_var($form['course_price'], FILTER_VALIDATE_INT);
    $origVal = filter_var($form['course_original_price'], FILTER_VALIDATE_INT);

    if ($error === '' && ($priceVal === false || $origVal === false)) {
        $error = 'Gia khoa hoc phai la so nguyen hop le.';
    }

    if ($error === '' && ((int) $priceVal < 0 || (int) $origVal < 0)) {
        $error = 'Gia khoa hoc khong duoc la so am.';
    }

    if ($error === '' && (int) $origVal < (int) $priceVal) {
        $error = 'Gia goc khong duoc nho hon gia ban.';
    }

    if ($error === '' && !in_array($form['course_type'], ['self_paced', 'blended'], true)) {
        $error = 'Loai khoa hoc khong hop le.';
    }

    $newImageDiskPath = null;
    $courseImageDb = (string) ($course['course_img'] ?? '');

    $courseImageName = (string) ($_FILES['course_img']['name'] ?? '');
    $courseImageTmp = (string) ($_FILES['course_img']['tmp_name'] ?? '');
    $courseImageSize = (int) ($_FILES['course_img']['size'] ?? 0);
    $courseImageError = (int) ($_FILES['course_img']['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error === '' && $courseImageError === UPLOAD_ERR_OK) {
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
        $courseExt = strtolower(pathinfo($courseImageName, PATHINFO_EXTENSION));

        if (!in_array($courseExt, $allowedExt, true)) {
            $error = 'Dinh dang anh khong hop le. Chi chap nhan jpg, jpeg, png, webp.';
        } elseif ($courseImageSize > 2 * 1024 * 1024) {
            $error = 'Anh qua lon. Kich thuoc toi da 2MB.';
        } else {
            $filename = time() . '_' . basename($courseImageName);
            $newImageDiskPath = __DIR__ . '/../image/courseimg/' . $filename;
            $courseImageDb = 'image/courseimg/' . $filename;
            if (!move_uploaded_file($courseImageTmp, $newImageDiskPath)) {
                $error = 'Khong the luu anh moi cho khoa hoc.';
            }
        }
    }

    if ($error === '') {
        $stmt = $conn->prepare(
            'UPDATE course SET course_name = ?, course_desc = ?, course_duration = ?, course_price = ?, course_original_price = ?, course_type = ?, course_author = ?, course_img = ? '
            . 'WHERE course_id = ? AND instructor_id = ? AND is_deleted = 0'
        );

        if (!$stmt) {
            if ($newImageDiskPath !== null && is_file($newImageDiskPath)) {
                @unlink($newImageDiskPath);
            }
            $error = 'Khong the cap nhat khoa hoc luc nay.';
        } else {
            $author = (string) ($instructorProfile['ins_name'] ?? 'Instructor');
            $priceNum = (int) $priceVal;
            $origNum = (int) $origVal;
            $stmt->bind_param(
                'sssiisssii',
                $form['course_name'],
                $form['course_desc'],
                $form['course_duration'],
                $priceNum,
                $origNum,
                $form['course_type'],
                $author,
                $courseImageDb,
                $courseId,
                $instructorId
            );
            $ok = $stmt->execute();
            $stmt->close();

            if (!$ok) {
                if ($newImageDiskPath !== null && is_file($newImageDiskPath)) {
                    @unlink($newImageDiskPath);
                }
                $error = 'Khong the cap nhat khoa hoc luc nay.';
            } else {
                instructor_set_flash('success', 'Da cap nhat khoa hoc thanh cong.');
                header('Location: editCourse.php?id=' . $courseId);
                exit;
            }
        }
    }
}

$course = instructor_find_owned_course($conn, $courseId, $instructorId);
if (!$course) {
    instructor_set_flash('error', 'Khoa hoc khong con ton tai hoac ban khong con quyen truy cap.');
    header('Location: courses.php');
    exit;
}

$statusMeta = instructor_course_status_meta((string) ($course['course_status'] ?? 'draft'));
$courseImageDisplay = ltrim(str_replace('../', '', (string) ($course['course_img'] ?? '')), '/');
if ($courseImageDisplay === '') {
    $courseImageDisplay = 'image/courseimg/Banner1.jpeg';
}
?>

<section class="mb-6 flex flex-wrap items-start justify-between gap-3">
  <div>
    <h1 class="m-0 text-2xl font-black text-slate-900">Chinh sua khoa hoc</h1>
    <p class="m-0 mt-1 text-sm text-slate-500">Ban chi duoc cap nhat khoa hoc do minh so huu.</p>
  </div>
  <div class="flex items-center gap-2">
    <span class="inline-flex rounded-lg px-3 py-1 text-xs font-bold <?php echo htmlspecialchars((string) $statusMeta['class'], ENT_QUOTES, 'UTF-8'); ?>">
      <?php echo htmlspecialchars((string) $statusMeta['label'], ENT_QUOTES, 'UTF-8'); ?>
    </span>
    <a href="courses.php" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 no-underline">
      <i class="fas fa-arrow-left"></i>
      <span>Ve danh sach</span>
    </a>
  </div>
</section>

<?php if ($error !== ''): ?>
  <section class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
    <i class="fas fa-exclamation-circle mr-1"></i>
    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
  </section>
<?php endif; ?>

<section class="grid gap-5 lg:grid-cols-[2fr_1fr]">
  <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="post" enctype="multipart/form-data" class="space-y-5">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">

      <div>
        <label for="course_name" class="mb-2 block text-sm font-bold text-slate-700">Ten khoa hoc</label>
        <input type="text" id="course_name" name="course_name" value="<?php echo htmlspecialchars($form['course_name'], ENT_QUOTES, 'UTF-8'); ?>" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
      </div>

      <div>
        <label for="course_desc" class="mb-2 block text-sm font-bold text-slate-700">Mo ta khoa hoc</label>
        <textarea id="course_desc" name="course_desc" rows="5" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10"><?php echo htmlspecialchars($form['course_desc'], ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label for="course_duration" class="mb-2 block text-sm font-bold text-slate-700">Thoi luong</label>
          <input type="text" id="course_duration" name="course_duration" value="<?php echo htmlspecialchars($form['course_duration'], ENT_QUOTES, 'UTF-8'); ?>" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
        </div>
        <div>
          <label for="course_type" class="mb-2 block text-sm font-bold text-slate-700">Loai khoa hoc</label>
          <select id="course_type" name="course_type" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
            <option value="self_paced" <?php echo $form['course_type'] === 'self_paced' ? 'selected' : ''; ?>>Self paced</option>
            <option value="blended" <?php echo $form['course_type'] === 'blended' ? 'selected' : ''; ?>>Blended</option>
          </select>
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label for="course_original_price" class="mb-2 block text-sm font-bold text-slate-700">Gia goc</label>
          <input type="number" min="0" id="course_original_price" name="course_original_price" value="<?php echo htmlspecialchars($form['course_original_price'], ENT_QUOTES, 'UTF-8'); ?>" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
        </div>
        <div>
          <label for="course_price" class="mb-2 block text-sm font-bold text-slate-700">Gia ban</label>
          <input type="number" min="0" id="course_price" name="course_price" value="<?php echo htmlspecialchars($form['course_price'], ENT_QUOTES, 'UTF-8'); ?>" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
        </div>
      </div>

      <div>
        <label for="course_img" class="mb-2 block text-sm font-bold text-slate-700">Cap nhat anh dai dien (tuỳ chon)</label>
        <input type="file" id="course_img" name="course_img" accept=".jpg,.jpeg,.png,.webp" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-2 file:text-xs file:font-bold file:text-primary hover:file:bg-primary/20">
        <p class="m-0 mt-1 text-xs text-slate-400">Neu de trong, he thong giu nguyen anh cu.</p>
      </div>

      <div class="flex flex-wrap items-center gap-3 pt-2">
        <button type="submit" name="update_course" class="inline-flex items-center gap-2 rounded-xl border-0 bg-primary px-5 py-3 text-sm font-extrabold text-white transition hover:bg-primary/90">
          <i class="fas fa-save"></i>
          <span>Luu thay doi</span>
        </button>
        <a href="sections.php?course_id=<?php echo $courseId; ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 no-underline">
          <i class="fas fa-list-ol"></i>
          <span>Quan ly section</span>
        </a>
      </div>
    </form>
  </article>

  <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <p class="m-0 text-sm font-bold text-slate-700">Preview anh hien tai</p>
    <img src="../<?php echo htmlspecialchars($courseImageDisplay, ENT_QUOTES, 'UTF-8'); ?>" alt="Course image" class="mt-3 h-48 w-full rounded-xl border border-slate-200 object-cover" onerror="this.onerror=null;this.src='../image/courseimg/Banner1.jpeg'">
    <dl class="mt-4 space-y-2 text-xs text-slate-500">
      <div class="flex items-center justify-between gap-3">
        <dt>Course ID</dt>
        <dd class="font-bold text-slate-700">#<?php echo $courseId; ?></dd>
      </div>
      <div class="flex items-center justify-between gap-3">
        <dt>Trang thai</dt>
        <dd class="font-bold text-slate-700"><?php echo htmlspecialchars((string) $statusMeta['label'], ENT_QUOTES, 'UTF-8'); ?></dd>
      </div>
      <div class="flex items-center justify-between gap-3">
        <dt>Updated at</dt>
        <dd class="font-bold text-slate-700"><?php echo htmlspecialchars((string) ($course['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></dd>
      </div>
    </dl>
  </aside>
</section>

<?php require_once(__DIR__ . '/instructorInclude/footer.php'); ?>
