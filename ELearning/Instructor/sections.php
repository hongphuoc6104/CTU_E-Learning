<?php

define('TITLE', 'Quan ly sections');
define('PAGE', 'sections');

require_once(__DIR__ . '/instructorInclude/header.php');

$instructorId = instructor_current_id();

$ownedCourses = [];
$courseStmt = $conn->prepare(
    'SELECT course_id, course_name, course_status '
    . 'FROM course '
    . 'WHERE instructor_id = ? AND is_deleted = 0 '
    . 'ORDER BY updated_at DESC, course_id DESC'
);
if ($courseStmt) {
    $courseStmt->bind_param('i', $instructorId);
    $courseStmt->execute();
    $result = $courseStmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ownedCourses[] = $row;
        }
    }
    $courseStmt->close();
}

$selectedCourseId = (int) ($_GET['course_id'] ?? $_POST['course_id'] ?? 0);
if ($selectedCourseId <= 0 && count($ownedCourses) > 0) {
    $selectedCourseId = (int) ($ownedCourses[0]['course_id'] ?? 0);
}

$selectedCourse = null;
foreach ($ownedCourses as $ownedCourse) {
    if ((int) ($ownedCourse['course_id'] ?? 0) === $selectedCourseId) {
        $selectedCourse = $ownedCourse;
        break;
    }
}

if ($selectedCourseId > 0 && !$selectedCourse) {
    instructor_set_flash('error', 'Ban khong duoc phep thao tac voi khoa hoc nay.');
    header('Location: sections.php');
    exit;
}

if (isset($_POST['add_section'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        instructor_set_flash('error', 'Phien gui bieu mau da het han.');
        header('Location: sections.php?course_id=' . $selectedCourseId);
        exit;
    }

    if (!$selectedCourse) {
        instructor_set_flash('error', 'Vui long chon khoa hoc hop le.');
        header('Location: sections.php');
        exit;
    }

    $sectionTitle = trim((string) ($_POST['section_title'] ?? ''));
    $sectionPositionInput = trim((string) ($_POST['section_position'] ?? ''));

    if ($sectionTitle === '') {
        instructor_set_flash('error', 'Ten section khong duoc de trong.');
        header('Location: sections.php?course_id=' . $selectedCourseId);
        exit;
    }

    $position = 0;
    if ($sectionPositionInput !== '') {
        $position = (int) $sectionPositionInput;
        if ($position <= 0) {
            instructor_set_flash('error', 'Vi tri section phai lon hon 0.');
            header('Location: sections.php?course_id=' . $selectedCourseId);
            exit;
        }
    } else {
        $position = instructor_next_section_position($conn, $selectedCourseId);
    }

    $conn->begin_transaction();
    $dbError = false;

    if ($sectionPositionInput !== '') {
        $shiftStmt = $conn->prepare(
            'UPDATE course_section '
            . 'SET section_position = section_position + 1 '
            . 'WHERE course_id = ? AND is_deleted = 0 AND section_position >= ?'
        );
        if ($shiftStmt) {
            $shiftStmt->bind_param('ii', $selectedCourseId, $position);
            if (!$shiftStmt->execute()) {
                $dbError = true;
            }
            $shiftStmt->close();
        } else {
            $dbError = true;
        }
    }

    if (!$dbError) {
        $insertStmt = $conn->prepare(
            'INSERT INTO course_section (course_id, section_title, section_position, is_deleted) '
            . 'VALUES (?, ?, ?, 0)'
        );
        if ($insertStmt) {
            $insertStmt->bind_param('isi', $selectedCourseId, $sectionTitle, $position);
            if (!$insertStmt->execute()) {
                $dbError = true;
            }
            $insertStmt->close();
        } else {
            $dbError = true;
        }
    }

    if ($dbError) {
        $conn->rollback();
        instructor_set_flash('error', 'Khong the tao section luc nay.');
    } else {
        $conn->commit();
        instructor_set_flash('success', 'Da tao section moi thanh cong.');
    }

    header('Location: sections.php?course_id=' . $selectedCourseId);
    exit;
}

if (isset($_POST['delete_section'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        instructor_set_flash('error', 'Phien gui bieu mau da het han.');
        header('Location: sections.php?course_id=' . $selectedCourseId);
        exit;
    }

    $sectionId = (int) ($_POST['section_id'] ?? 0);
    if ($sectionId <= 0) {
        instructor_set_flash('error', 'Section khong hop le.');
        header('Location: sections.php?course_id=' . $selectedCourseId);
        exit;
    }

    $verifyStmt = $conn->prepare(
        'SELECT cs.section_id, cs.course_id '
        . 'FROM course_section cs '
        . 'INNER JOIN course c ON c.course_id = cs.course_id '
        . 'WHERE cs.section_id = ? AND cs.is_deleted = 0 AND c.instructor_id = ? AND c.is_deleted = 0 '
        . 'LIMIT 1'
    );

    $verifiedCourseId = 0;
    if ($verifyStmt) {
        $verifyStmt->bind_param('ii', $sectionId, $instructorId);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        $verifyRow = $verifyResult ? $verifyResult->fetch_assoc() : null;
        if ($verifyRow) {
            $verifiedCourseId = (int) ($verifyRow['course_id'] ?? 0);
        }
        $verifyStmt->close();
    }

    if ($verifiedCourseId <= 0) {
        instructor_set_flash('error', 'Ban khong duoc phep xoa section nay.');
        header('Location: sections.php?course_id=' . $selectedCourseId);
        exit;
    }

    $conn->begin_transaction();
    $dbError = false;

    $softDeleteSectionStmt = $conn->prepare('UPDATE course_section SET is_deleted = 1 WHERE section_id = ?');
    if ($softDeleteSectionStmt) {
        $softDeleteSectionStmt->bind_param('i', $sectionId);
        if (!$softDeleteSectionStmt->execute()) {
            $dbError = true;
        }
        $softDeleteSectionStmt->close();
    } else {
        $dbError = true;
    }

    if (!$dbError) {
        $softDeleteItemStmt = $conn->prepare('UPDATE learning_item SET is_deleted = 1 WHERE section_id = ? AND is_deleted = 0');
        if ($softDeleteItemStmt) {
            $softDeleteItemStmt->bind_param('i', $sectionId);
            if (!$softDeleteItemStmt->execute()) {
                $dbError = true;
            }
            $softDeleteItemStmt->close();
        } else {
            $dbError = true;
        }
    }

    if ($dbError) {
        $conn->rollback();
        instructor_set_flash('error', 'Khong the xoa section luc nay.');
    } else {
        $conn->commit();
        instructor_set_flash('success', 'Da xoa section va cac learning item lien quan.');
    }

    header('Location: sections.php?course_id=' . $verifiedCourseId);
    exit;
}

$sections = [];
if ($selectedCourse) {
    $sectionListStmt = $conn->prepare(
        'SELECT cs.section_id, cs.section_title, cs.section_position, cs.updated_at, '
        . '(SELECT COUNT(*) FROM learning_item li WHERE li.section_id = cs.section_id AND li.is_deleted = 0) AS item_count, '
        . '(SELECT COUNT(*) FROM live_session ls WHERE ls.section_id = cs.section_id AND ls.is_deleted = 0) AS live_count '
        . 'FROM course_section cs '
        . 'WHERE cs.course_id = ? AND cs.is_deleted = 0 '
        . 'ORDER BY cs.section_position ASC, cs.section_id ASC'
    );
    if ($sectionListStmt) {
        $sectionListStmt->bind_param('i', $selectedCourseId);
        $sectionListStmt->execute();
        $result = $sectionListStmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $sections[] = $row;
            }
        }
        $sectionListStmt->close();
    }
}
?>

<section class="mb-6">
  <h1 class="m-0 text-2xl font-black text-slate-900">Sections</h1>
  <p class="m-0 mt-1 text-sm text-slate-500">Tao va quan ly section theo tung khoa hoc so huu.</p>
</section>

<?php if (count($ownedCourses) === 0): ?>
  <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
    <p class="m-0 text-sm text-slate-500">Ban chua co khoa hoc nao. Hay tao khoa hoc truoc khi tao section.</p>
    <a href="addCourse.php" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white no-underline transition hover:bg-primary/90">
      <i class="fas fa-plus-circle"></i>
      <span>Tao khoa hoc</span>
    </a>
  </section>
<?php else: ?>
  <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <form method="get" class="flex flex-col gap-3 sm:flex-row sm:items-center">
      <label for="course_id" class="text-sm font-bold text-slate-700">Khoa hoc</label>
      <select id="course_id" name="course_id" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" onchange="this.form.submit()">
        <?php foreach ($ownedCourses as $ownedCourse): ?>
          <option value="<?php echo (int) ($ownedCourse['course_id'] ?? 0); ?>" <?php echo ((int) ($ownedCourse['course_id'] ?? 0) === $selectedCourseId) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars((string) ($ownedCourse['course_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  </section>

  <?php if ($selectedCourse): ?>
    <?php $selectedStatusMeta = instructor_course_status_meta((string) ($selectedCourse['course_status'] ?? 'draft')); ?>
    <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div>
          <p class="m-0 text-sm font-extrabold text-slate-800"><?php echo htmlspecialchars((string) ($selectedCourse['course_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
          <p class="m-0 mt-1 text-xs text-slate-500">Section moi duoc them vao khoa hoc dang chon.</p>
        </div>
        <span class="inline-flex rounded-lg px-2 py-1 text-[11px] font-bold <?php echo htmlspecialchars((string) $selectedStatusMeta['class'], ENT_QUOTES, 'UTF-8'); ?>">
          <?php echo htmlspecialchars((string) $selectedStatusMeta['label'], ENT_QUOTES, 'UTF-8'); ?>
        </span>
      </div>

      <form method="post" class="grid gap-3 sm:grid-cols-[1fr_140px_auto] sm:items-end">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="course_id" value="<?php echo $selectedCourseId; ?>">
        <div>
          <label for="section_title" class="mb-2 block text-sm font-bold text-slate-700">Ten section</label>
          <input type="text" id="section_title" name="section_title" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="VD: Workshop live so 1 va 2">
        </div>
        <div>
          <label for="section_position" class="mb-2 block text-sm font-bold text-slate-700">Vi tri (tuỳ chon)</label>
          <input type="number" min="1" id="section_position" name="section_position" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="Tu dong">
        </div>
        <button type="submit" name="add_section" class="inline-flex items-center justify-center gap-2 rounded-xl border-0 bg-primary px-4 py-2.5 text-sm font-extrabold text-white transition hover:bg-primary/90">
          <i class="fas fa-plus"></i>
          <span>Them section</span>
        </button>
      </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-sm">
          <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
              <th class="px-4 py-3 text-left font-bold">Vi tri</th>
              <th class="px-4 py-3 text-left font-bold">Ten section</th>
              <th class="px-4 py-3 text-center font-bold">Items</th>
              <th class="px-4 py-3 text-center font-bold">Live</th>
              <th class="px-4 py-3 text-right font-bold">Thao tac</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php if (count($sections) > 0): ?>
              <?php foreach ($sections as $section): ?>
                <tr>
                  <td class="px-4 py-4 font-black text-primary">#<?php echo (int) ($section['section_position'] ?? 0); ?></td>
                  <td class="px-4 py-4">
                    <p class="m-0 font-bold text-slate-800"><?php echo htmlspecialchars((string) ($section['section_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="m-0 mt-1 text-xs text-slate-500">Cap nhat: <?php echo htmlspecialchars((string) ($section['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                  </td>
                  <td class="px-4 py-4 text-center font-semibold text-slate-600"><?php echo (int) ($section['item_count'] ?? 0); ?></td>
                  <td class="px-4 py-4 text-center font-semibold text-slate-600"><?php echo (int) ($section['live_count'] ?? 0); ?></td>
                  <td class="px-4 py-4 text-right">
                    <div class="flex flex-wrap items-center justify-end gap-2">
                      <a href="learningItems.php?course_id=<?php echo $selectedCourseId; ?>&section_id=<?php echo (int) ($section['section_id'] ?? 0); ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-bold text-slate-700 no-underline transition hover:bg-slate-100">
                        <i class="fas fa-book-open"></i>
                        <span>Items</span>
                      </a>
                      <a href="liveSessions.php?course_id=<?php echo $selectedCourseId; ?>&section_id=<?php echo (int) ($section['section_id'] ?? 0); ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-bold text-slate-700 no-underline transition hover:bg-slate-100">
                        <i class="fas fa-video"></i>
                        <span>Live</span>
                      </a>
                      <form method="post" class="m-0" onsubmit="return confirm('Xoa section nay? Learning item ben trong se bi an.');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="course_id" value="<?php echo $selectedCourseId; ?>">
                        <input type="hidden" name="section_id" value="<?php echo (int) ($section['section_id'] ?? 0); ?>">
                        <button type="submit" name="delete_section" class="inline-flex items-center gap-1 rounded-lg border-0 bg-red-500 px-2.5 py-1.5 text-xs font-extrabold text-white transition hover:bg-red-600">
                          <i class="fas fa-trash"></i>
                          <span>Xoa</span>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="px-4 py-14 text-center text-sm text-slate-400">Chua co section nao trong khoa hoc nay.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>
<?php endif; ?>

<?php require_once(__DIR__ . '/instructorInclude/footer.php'); ?>
