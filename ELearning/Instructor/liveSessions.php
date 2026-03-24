<?php

define('TITLE', 'Live sessions');
define('PAGE', 'live');

require_once(__DIR__ . '/instructorInclude/header.php');

$instructorId = instructor_current_id();
instructor_refresh_live_session_statuses($conn, $instructorId);

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

$allSections = [];
$sectionMapStmt = $conn->prepare(
    'SELECT cs.section_id, cs.course_id, cs.section_title, cs.section_position '
    . 'FROM course_section cs '
    . 'INNER JOIN course c ON c.course_id = cs.course_id '
    . 'WHERE c.instructor_id = ? AND c.is_deleted = 0 AND cs.is_deleted = 0 '
    . 'ORDER BY cs.course_id ASC, cs.section_position ASC, cs.section_id ASC'
);
if ($sectionMapStmt) {
    $sectionMapStmt->bind_param('i', $instructorId);
    $sectionMapStmt->execute();
    $result = $sectionMapStmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $courseKey = (int) ($row['course_id'] ?? 0);
            if (!isset($allSections[$courseKey])) {
                $allSections[$courseKey] = [];
            }
            $allSections[$courseKey][] = $row;
        }
    }
    $sectionMapStmt->close();
}

$filterCourseId = (int) ($_GET['course_id'] ?? 0);
if ($filterCourseId <= 0 && count($ownedCourses) > 0) {
    $filterCourseId = (int) ($ownedCourses[0]['course_id'] ?? 0);
}

$selectedCourse = null;
foreach ($ownedCourses as $ownedCourse) {
    if ((int) ($ownedCourse['course_id'] ?? 0) === $filterCourseId) {
        $selectedCourse = $ownedCourse;
        break;
    }
}

if ($filterCourseId > 0 && !$selectedCourse) {
    instructor_set_flash('error', 'Ban khong duoc phep truy cap khoa hoc nay.');
    header('Location: liveSessions.php');
    exit;
}

$filterSectionId = (int) ($_GET['section_id'] ?? 0);
if ($filterSectionId > 0) {
    $sectionMatched = false;
    foreach (($allSections[$filterCourseId] ?? []) as $sectionInfo) {
        if ((int) ($sectionInfo['section_id'] ?? 0) === $filterSectionId) {
            $sectionMatched = true;
            break;
        }
    }
    if (!$sectionMatched) {
        instructor_set_flash('error', 'Section khong hop le voi khoa hoc da chon.');
        header('Location: liveSessions.php?course_id=' . $filterCourseId);
        exit;
    }
}

if (!function_exists('parse_local_datetime')) {
    function parse_local_datetime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $value);
        if (!$dt) {
            $dt = DateTime::createFromFormat('Y-m-d H:i:s', $value);
        }
        if (!$dt) {
            $dt = DateTime::createFromFormat('Y-m-d H:i', $value);
        }

        return $dt ? $dt->format('Y-m-d H:i:s') : null;
    }
}

if (isset($_POST['create_live_session'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        instructor_set_flash('error', 'Phien gui bieu mau da het han.');
        header('Location: liveSessions.php?course_id=' . $filterCourseId . '&section_id=' . $filterSectionId);
        exit;
    }

    $createCourseId = (int) ($_POST['create_course_id'] ?? 0);
    $createSectionId = (int) ($_POST['create_section_id'] ?? 0);
    $sessionTitle = trim((string) ($_POST['session_title'] ?? ''));
    $sessionDescription = trim((string) ($_POST['session_description'] ?? ''));
    $startAtInput = (string) ($_POST['start_at'] ?? '');
    $endAtInput = (string) ($_POST['end_at'] ?? '');
    $platformName = trim((string) ($_POST['platform_name'] ?? ''));
    $joinUrl = trim((string) ($_POST['join_url'] ?? ''));

    $createCourse = instructor_find_owned_course($conn, $createCourseId, $instructorId);
    if (!$createCourse) {
        instructor_set_flash('error', 'Ban khong duoc phep tao live session cho khoa hoc nay.');
        header('Location: liveSessions.php?course_id=' . $filterCourseId . '&section_id=' . $filterSectionId);
        exit;
    }

    $sectionValidStmt = $conn->prepare('SELECT section_id FROM course_section WHERE section_id = ? AND course_id = ? AND is_deleted = 0 LIMIT 1');
    $sectionValid = false;
    if ($sectionValidStmt) {
        $sectionValidStmt->bind_param('ii', $createSectionId, $createCourseId);
        $sectionValidStmt->execute();
        $sectionValidStmt->store_result();
        $sectionValid = $sectionValidStmt->num_rows > 0;
        $sectionValidStmt->close();
    }

    if (!$sectionValid) {
        instructor_set_flash('error', 'Section khong hop le cho khoa hoc da chon.');
        header('Location: liveSessions.php?course_id=' . $createCourseId);
        exit;
    }

    if ($sessionTitle === '') {
        instructor_set_flash('error', 'Tieu de live session khong duoc de trong.');
        header('Location: liveSessions.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
        exit;
    }

    $startAt = parse_local_datetime($startAtInput);
    $endAt = parse_local_datetime($endAtInput);
    if ($startAt === null || $endAt === null) {
        instructor_set_flash('error', 'Thoi gian bat dau va ket thuc khong hop le.');
        header('Location: liveSessions.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
        exit;
    }

    if (strtotime($endAt) <= strtotime($startAt)) {
        instructor_set_flash('error', 'Thoi gian ket thuc phai lon hon thoi gian bat dau.');
        header('Location: liveSessions.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
        exit;
    }

    if ($platformName === '') {
        instructor_set_flash('error', 'Vui long nhap ten nen tang live (Zoom, Google Meet, ...).');
        header('Location: liveSessions.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
        exit;
    }

    if (!instructor_is_valid_url($joinUrl)) {
        instructor_set_flash('error', 'Join URL khong hop le. Live session bat buoc phai co link hop.');
        header('Location: liveSessions.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
        exit;
    }

    $conn->begin_transaction();
    $dbError = false;
    $liveSessionId = 0;

    $insertLiveStmt = $conn->prepare(
        'INSERT INTO live_session '
        . '(course_id, section_id, instructor_id, session_title, session_description, start_at, end_at, join_url, platform_name, session_status, is_deleted) '
        . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'scheduled\', 0)'
    );
    if ($insertLiveStmt) {
        $insertLiveStmt->bind_param(
            'iiissssss',
            $createCourseId,
            $createSectionId,
            $instructorId,
            $sessionTitle,
            $sessionDescription,
            $startAt,
            $endAt,
            $joinUrl,
            $platformName
        );
        if ($insertLiveStmt->execute()) {
            $liveSessionId = (int) $insertLiveStmt->insert_id;
        } else {
            $dbError = true;
        }
        $insertLiveStmt->close();
    } else {
        $dbError = true;
    }

    if (!$dbError && $liveSessionId > 0) {
        $itemPosition = instructor_next_item_position($conn, $createCourseId, $createSectionId);
        $itemStatus = ((string) ($createCourse['course_status'] ?? 'draft') === 'published') ? 'published' : 'draft';

        $insertItemStmt = $conn->prepare(
            'INSERT INTO learning_item '
            . '(course_id, section_id, item_title, item_type, item_position, is_preview, is_required, content_status, live_session_id, is_deleted) '
            . 'VALUES (?, ?, ?, \'live_session\', ?, 0, 1, ?, ?, 0)'
        );
        if ($insertItemStmt) {
            $insertItemStmt->bind_param('iisisi', $createCourseId, $createSectionId, $sessionTitle, $itemPosition, $itemStatus, $liveSessionId);
            if (!$insertItemStmt->execute()) {
                $dbError = true;
            }
            $insertItemStmt->close();
        } else {
            $dbError = true;
        }
    }

    if ($dbError) {
        $conn->rollback();
        instructor_set_flash('error', 'Khong the tao live session luc nay.');
    } else {
        $conn->commit();
        instructor_set_flash('success', 'Da tao live session o trang thai scheduled. Session da duoc gan vao timeline khoa hoc.');
    }

    header('Location: liveSessions.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
    exit;
}

if (isset($_POST['save_replay'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        instructor_set_flash('error', 'Phien gui bieu mau da het han.');
        header('Location: liveSessions.php?course_id=' . $filterCourseId . '&section_id=' . $filterSectionId);
        exit;
    }

    $liveSessionId = (int) ($_POST['live_session_id'] ?? 0);
    $replayUrl = trim((string) ($_POST['recording_url'] ?? ''));
    $replayProvider = trim((string) ($_POST['recording_provider'] ?? ''));
    if ($replayProvider === '') {
        $replayProvider = 'External Replay';
    }

    $liveStmt = $conn->prepare(
        'SELECT ls.live_session_id, ls.course_id, ls.section_id, ls.end_at, ls.session_title, ls.session_status, c.course_status '
        . 'FROM live_session ls '
        . 'INNER JOIN course c ON c.course_id = ls.course_id '
        . 'WHERE ls.live_session_id = ? AND ls.instructor_id = ? AND ls.is_deleted = 0 AND c.is_deleted = 0 '
        . 'LIMIT 1'
    );

    $liveRow = null;
    if ($liveStmt) {
        $liveStmt->bind_param('ii', $liveSessionId, $instructorId);
        $liveStmt->execute();
        $result = $liveStmt->get_result();
        $liveRow = $result ? $result->fetch_assoc() : null;
        $liveStmt->close();
    }

    if (!$liveRow) {
        instructor_set_flash('error', 'Live session khong ton tai hoac ban khong duoc phep cap nhat.');
        header('Location: liveSessions.php?course_id=' . $filterCourseId . '&section_id=' . $filterSectionId);
        exit;
    }

    $courseId = (int) ($liveRow['course_id'] ?? 0);
    $sectionId = (int) ($liveRow['section_id'] ?? 0);
    $endTimestamp = strtotime((string) ($liveRow['end_at'] ?? ''));
    if ($endTimestamp === false || time() <= $endTimestamp) {
        instructor_set_flash('error', 'Chi duoc cap nhat replay sau khi live session ket thuc.');
        header('Location: liveSessions.php?course_id=' . $courseId . '&section_id=' . $sectionId);
        exit;
    }

    if ($replayUrl === '') {
        $setEndedStmt = $conn->prepare('UPDATE live_session SET session_status = \'ended\' WHERE live_session_id = ? AND is_deleted = 0');
        if ($setEndedStmt) {
            $setEndedStmt->bind_param('i', $liveSessionId);
            $setEndedStmt->execute();
            $setEndedStmt->close();
        }

        instructor_set_flash('error', 'Replay URL dang de trong. Session duoc giu trang thai ended.');
        header('Location: liveSessions.php?course_id=' . $courseId . '&section_id=' . $sectionId);
        exit;
    }

    if (!instructor_is_valid_url($replayUrl)) {
        instructor_set_flash('error', 'Replay URL khong hop le.');
        header('Location: liveSessions.php?course_id=' . $courseId . '&section_id=' . $sectionId);
        exit;
    }

    $conn->begin_transaction();
    $dbError = false;
    $replayId = 0;

    $existingReplayStmt = $conn->prepare('SELECT replay_id FROM replay_asset WHERE live_session_id = ? LIMIT 1');
    if ($existingReplayStmt) {
        $existingReplayStmt->bind_param('i', $liveSessionId);
        $existingReplayStmt->execute();
        $result = $existingReplayStmt->get_result();
        $existingReplay = $result ? $result->fetch_assoc() : null;
        $existingReplayStmt->close();

        if ($existingReplay) {
            $replayId = (int) ($existingReplay['replay_id'] ?? 0);
            $updateReplayStmt = $conn->prepare(
                'UPDATE replay_asset '
                . 'SET recording_url = ?, recording_provider = ?, available_at = NOW(), is_deleted = 0 '
                . 'WHERE replay_id = ?'
            );
            if ($updateReplayStmt) {
                $updateReplayStmt->bind_param('ssi', $replayUrl, $replayProvider, $replayId);
                if (!$updateReplayStmt->execute()) {
                    $dbError = true;
                }
                $updateReplayStmt->close();
            } else {
                $dbError = true;
            }
        } else {
            $insertReplayStmt = $conn->prepare(
                'INSERT INTO replay_asset (live_session_id, recording_url, recording_provider, available_at, is_deleted) '
                . 'VALUES (?, ?, ?, NOW(), 0)'
            );
            if ($insertReplayStmt) {
                $insertReplayStmt->bind_param('iss', $liveSessionId, $replayUrl, $replayProvider);
                if ($insertReplayStmt->execute()) {
                    $replayId = (int) $insertReplayStmt->insert_id;
                } else {
                    $dbError = true;
                }
                $insertReplayStmt->close();
            } else {
                $dbError = true;
            }
        }
    } else {
        $dbError = true;
    }

    if (!$dbError) {
        $updateLiveStmt = $conn->prepare('UPDATE live_session SET session_status = \'replay_available\' WHERE live_session_id = ?');
        if ($updateLiveStmt) {
            $updateLiveStmt->bind_param('i', $liveSessionId);
            if (!$updateLiveStmt->execute()) {
                $dbError = true;
            }
            $updateLiveStmt->close();
        } else {
            $dbError = true;
        }
    }

    if (!$dbError && $replayId > 0 && $sectionId > 0) {
        $existingReplayItemStmt = $conn->prepare(
            'SELECT item_id FROM learning_item '
            . 'WHERE course_id = ? AND replay_id = ? AND is_deleted = 0 '
            . 'LIMIT 1'
        );
        $hasReplayItem = false;
        if ($existingReplayItemStmt) {
            $existingReplayItemStmt->bind_param('ii', $courseId, $replayId);
            $existingReplayItemStmt->execute();
            $existingReplayItemStmt->store_result();
            $hasReplayItem = $existingReplayItemStmt->num_rows > 0;
            $existingReplayItemStmt->close();
        }

        if (!$hasReplayItem) {
            $itemPosition = instructor_next_item_position($conn, $courseId, $sectionId);
            $replayItemTitle = 'Replay - ' . (string) ($liveRow['session_title'] ?? 'Live session');
            $itemStatus = ((string) ($liveRow['course_status'] ?? 'draft') === 'published') ? 'published' : 'draft';

            $insertReplayItemStmt = $conn->prepare(
                'INSERT INTO learning_item '
                . '(course_id, section_id, item_title, item_type, item_position, is_preview, is_required, content_status, replay_id, is_deleted) '
                . 'VALUES (?, ?, ?, \'replay\', ?, 0, 1, ?, ?, 0)'
            );
            if ($insertReplayItemStmt) {
                $insertReplayItemStmt->bind_param('iisisi', $courseId, $sectionId, $replayItemTitle, $itemPosition, $itemStatus, $replayId);
                if (!$insertReplayItemStmt->execute()) {
                    $dbError = true;
                }
                $insertReplayItemStmt->close();
            } else {
                $dbError = true;
            }
        }
    }

    if ($dbError) {
        $conn->rollback();
        instructor_set_flash('error', 'Khong the cap nhat replay luc nay.');
    } else {
        $conn->commit();
        instructor_set_flash('success', 'Da luu replay URL va cap nhat session sang replay_available.');
    }

    header('Location: liveSessions.php?course_id=' . $courseId . '&section_id=' . $sectionId);
    exit;
}

$liveSessions = [];
if ($selectedCourse) {
    if ($filterSectionId > 0) {
        $liveListStmt = $conn->prepare(
            'SELECT ls.live_session_id, ls.course_id, ls.section_id, ls.session_title, ls.session_description, ls.start_at, ls.end_at, ls.join_url, ls.platform_name, ls.session_status, '
            . 'c.course_name, cs.section_title, ra.replay_id, ra.recording_url, ra.recording_provider, ra.available_at '
            . 'FROM live_session ls '
            . 'INNER JOIN course c ON c.course_id = ls.course_id '
            . 'LEFT JOIN course_section cs ON cs.section_id = ls.section_id '
            . 'LEFT JOIN replay_asset ra ON ra.live_session_id = ls.live_session_id AND ra.is_deleted = 0 '
            . 'WHERE ls.instructor_id = ? AND ls.is_deleted = 0 AND c.is_deleted = 0 '
            . 'AND ls.course_id = ? AND ls.section_id = ? '
            . 'ORDER BY ls.start_at DESC, ls.live_session_id DESC'
        );
        if ($liveListStmt) {
            $liveListStmt->bind_param('iii', $instructorId, $filterCourseId, $filterSectionId);
            $liveListStmt->execute();
            $result = $liveListStmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $liveSessions[] = $row;
                }
            }
            $liveListStmt->close();
        }
    } else {
        $liveListStmt = $conn->prepare(
            'SELECT ls.live_session_id, ls.course_id, ls.section_id, ls.session_title, ls.session_description, ls.start_at, ls.end_at, ls.join_url, ls.platform_name, ls.session_status, '
            . 'c.course_name, cs.section_title, ra.replay_id, ra.recording_url, ra.recording_provider, ra.available_at '
            . 'FROM live_session ls '
            . 'INNER JOIN course c ON c.course_id = ls.course_id '
            . 'LEFT JOIN course_section cs ON cs.section_id = ls.section_id '
            . 'LEFT JOIN replay_asset ra ON ra.live_session_id = ls.live_session_id AND ra.is_deleted = 0 '
            . 'WHERE ls.instructor_id = ? AND ls.is_deleted = 0 AND c.is_deleted = 0 AND ls.course_id = ? '
            . 'ORDER BY ls.start_at DESC, ls.live_session_id DESC'
        );
        if ($liveListStmt) {
            $liveListStmt->bind_param('ii', $instructorId, $filterCourseId);
            $liveListStmt->execute();
            $result = $liveListStmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $liveSessions[] = $row;
                }
            }
            $liveListStmt->close();
        }
    }
}

$createDefaultCourseId = $filterCourseId;
if ($createDefaultCourseId <= 0 && count($ownedCourses) > 0) {
    $createDefaultCourseId = (int) ($ownedCourses[0]['course_id'] ?? 0);
}

$createDefaultSectionId = $filterSectionId;
if ($createDefaultSectionId <= 0 && isset($allSections[$createDefaultCourseId][0]['section_id'])) {
    $createDefaultSectionId = (int) $allSections[$createDefaultCourseId][0]['section_id'];
}
?>

<section class="mb-6">
  <h1 class="m-0 text-2xl font-black text-slate-900">Live sessions</h1>
  <p class="m-0 mt-1 text-sm text-slate-500">Len lich lop live bang link ngoai va cap nhat replay sau khi buoi hoc ket thuc.</p>
</section>

<?php if (count($ownedCourses) === 0): ?>
  <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
    <p class="m-0 text-sm text-slate-500">Ban chua co khoa hoc nao de tao live session.</p>
    <a href="addCourse.php" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white no-underline transition hover:bg-primary/90">
      <i class="fas fa-plus-circle"></i>
      <span>Tao khoa hoc</span>
    </a>
  </section>
<?php else: ?>
  <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <form method="get" class="grid gap-3 md:grid-cols-[1fr_1fr_auto] md:items-end">
      <div>
        <label for="course_id" class="mb-2 block text-sm font-bold text-slate-700">Loc theo khoa hoc</label>
        <select id="course_id" name="course_id" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
          <?php foreach ($ownedCourses as $ownedCourse): ?>
            <option value="<?php echo (int) ($ownedCourse['course_id'] ?? 0); ?>" <?php echo ((int) ($ownedCourse['course_id'] ?? 0) === $filterCourseId) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars((string) ($ownedCourse['course_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="section_id" class="mb-2 block text-sm font-bold text-slate-700">Loc theo section</label>
        <select id="section_id" name="section_id" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
          <option value="0">Tat ca section</option>
          <?php foreach (($allSections[$filterCourseId] ?? []) as $sectionInfo): ?>
            <option value="<?php echo (int) ($sectionInfo['section_id'] ?? 0); ?>" <?php echo ((int) ($sectionInfo['section_id'] ?? 0) === $filterSectionId) ? 'selected' : ''; ?>>
              #<?php echo (int) ($sectionInfo['section_position'] ?? 0); ?> - <?php echo htmlspecialchars((string) ($sectionInfo['section_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl border-0 bg-primary px-4 py-2.5 text-sm font-bold text-white transition hover:bg-primary/90">
        <i class="fas fa-filter"></i>
        <span>Ap dung</span>
      </button>
    </form>
  </section>

  <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="m-0 mb-4 text-base font-black text-slate-800">Tao live session moi</h2>
    <form method="post" class="space-y-4" id="createLiveSessionForm">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label for="create_course_id" class="mb-2 block text-sm font-bold text-slate-700">Khoa hoc</label>
          <select id="create_course_id" name="create_course_id" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" required>
            <?php foreach ($ownedCourses as $ownedCourse): ?>
              <option value="<?php echo (int) ($ownedCourse['course_id'] ?? 0); ?>" <?php echo ((int) ($ownedCourse['course_id'] ?? 0) === $createDefaultCourseId) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars((string) ($ownedCourse['course_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="create_section_id" class="mb-2 block text-sm font-bold text-slate-700">Section</label>
          <select id="create_section_id" name="create_section_id" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" required>
            <?php foreach ($allSections as $courseId => $sectionRows): ?>
              <?php foreach ($sectionRows as $sectionRow): ?>
                <option value="<?php echo (int) ($sectionRow['section_id'] ?? 0); ?>" data-course-id="<?php echo (int) $courseId; ?>" <?php echo ((int) ($sectionRow['section_id'] ?? 0) === $createDefaultSectionId) ? 'selected' : ''; ?>>
                  #<?php echo (int) ($sectionRow['section_position'] ?? 0); ?> - <?php echo htmlspecialchars((string) ($sectionRow['section_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div>
        <label for="session_title" class="mb-2 block text-sm font-bold text-slate-700">Tieu de live session</label>
        <input type="text" id="session_title" name="session_title" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="VD: Workshop 1 - Routing va layout">
      </div>

      <div>
        <label for="session_description" class="mb-2 block text-sm font-bold text-slate-700">Mo ta (tuỳ chon)</label>
        <textarea id="session_description" name="session_description" rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="Mo ta noi dung buoi live..."></textarea>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label for="start_at" class="mb-2 block text-sm font-bold text-slate-700">Bat dau luc</label>
          <input type="datetime-local" id="start_at" name="start_at" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
        </div>
        <div>
          <label for="end_at" class="mb-2 block text-sm font-bold text-slate-700">Ket thuc luc</label>
          <input type="datetime-local" id="end_at" name="end_at" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label for="platform_name" class="mb-2 block text-sm font-bold text-slate-700">Nen tang</label>
          <input type="text" id="platform_name" name="platform_name" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="Zoom / Google Meet / Teams">
        </div>
        <div>
          <label for="join_url" class="mb-2 block text-sm font-bold text-slate-700">External join URL</label>
          <input type="url" id="join_url" name="join_url" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="https://meet.google.com/...">
        </div>
      </div>

      <button type="submit" name="create_live_session" id="createLiveSessionBtn" class="inline-flex items-center gap-2 rounded-xl border-0 bg-primary px-5 py-3 text-sm font-extrabold text-white transition hover:bg-primary/90">
        <i class="fas fa-calendar-plus"></i>
        <span>Tao live session</span>
      </button>
    </form>
  </section>

  <section class="space-y-4">
    <?php if (count($liveSessions) > 0): ?>
      <?php foreach ($liveSessions as $live): ?>
        <?php
          $resolvedStatus = instructor_resolve_live_status($live);
          $statusMeta = instructor_live_status_meta($resolvedStatus);
          $recordingUrl = trim((string) ($live['recording_url'] ?? ''));
          $isReplayFormEnabled = in_array($resolvedStatus, ['ended', 'replay_available'], true);
          $stateNote = '';
          if ($resolvedStatus === 'scheduled') {
              $stateNote = 'Truoc gio hoc: hien thi upcoming state.';
          } elseif ($resolvedStatus === 'live') {
              $stateNote = 'Dang den khung gio hoc: hien thi join CTA.';
          } elseif ($resolvedStatus === 'ended') {
              $stateNote = 'Buoi hoc da ket thuc: cho replay URL.';
          } elseif ($resolvedStatus === 'replay_available') {
              $stateNote = 'Replay da san sang cho hoc vien xem lai.';
          }
        ?>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h3 class="m-0 text-base font-black text-slate-800"><?php echo htmlspecialchars((string) ($live['session_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
              <p class="m-0 mt-1 text-xs text-slate-500"><?php echo htmlspecialchars((string) ($live['course_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?><?php echo !empty($live['section_title']) ? ' · ' . htmlspecialchars((string) $live['section_title'], ENT_QUOTES, 'UTF-8') : ''; ?></p>
            </div>
            <span class="inline-flex rounded-lg px-2.5 py-1 text-[11px] font-bold <?php echo htmlspecialchars((string) $statusMeta['class'], ENT_QUOTES, 'UTF-8'); ?>">
              <?php echo htmlspecialchars((string) $statusMeta['label'], ENT_QUOTES, 'UTF-8'); ?>
            </span>
          </div>

          <div class="mt-3 grid gap-2 text-xs text-slate-600 sm:grid-cols-2">
            <p class="m-0"><span class="font-bold">Thoi gian:</span> <?php echo htmlspecialchars((string) ($live['start_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars((string) ($live['end_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="m-0"><span class="font-bold">Nen tang:</span> <?php echo htmlspecialchars((string) ($live['platform_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="m-0 sm:col-span-2">
              <span class="font-bold">Join link:</span>
              <a href="<?php echo htmlspecialchars((string) ($live['join_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline">
                <?php echo htmlspecialchars((string) ($live['join_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
              </a>
            </p>
          </div>

          <?php if (!empty($live['session_description'])): ?>
            <p class="m-0 mt-3 rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-600"><?php echo htmlspecialchars((string) $live['session_description'], ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>

          <?php if ($stateNote !== ''): ?>
            <p class="m-0 mt-3 text-xs font-semibold text-slate-500"><?php echo htmlspecialchars($stateNote, ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>

          <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="mb-2 flex items-center justify-between gap-2">
              <p class="m-0 text-xs font-extrabold uppercase tracking-wide text-slate-500">Replay</p>
              <?php if ($recordingUrl !== ''): ?>
                <a href="<?php echo htmlspecialchars($recordingUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-primary hover:underline">Mo replay</a>
              <?php endif; ?>
            </div>

            <form method="post" class="grid gap-2 md:grid-cols-[1fr_180px_auto] md:items-end">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="live_session_id" value="<?php echo (int) ($live['live_session_id'] ?? 0); ?>">

              <div>
                <label class="mb-1 block text-xs font-bold text-slate-600">Recording URL</label>
                <input type="url" name="recording_url" value="<?php echo htmlspecialchars($recordingUrl, ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="https://www.youtube.com/watch?v=..." <?php echo $isReplayFormEnabled ? '' : 'disabled'; ?>>
              </div>

              <div>
                <label class="mb-1 block text-xs font-bold text-slate-600">Provider</label>
                <input type="text" name="recording_provider" value="<?php echo htmlspecialchars((string) ($live['recording_provider'] ?? 'YouTube'), ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="YouTube" <?php echo $isReplayFormEnabled ? '' : 'disabled'; ?>>
              </div>

              <button type="submit" name="save_replay" class="inline-flex items-center justify-center gap-2 rounded-xl border-0 bg-emerald-600 px-4 py-2 text-xs font-extrabold text-white transition hover:bg-emerald-700 <?php echo $isReplayFormEnabled ? '' : 'opacity-50'; ?>" <?php echo $isReplayFormEnabled ? '' : 'disabled'; ?>>
                <i class="fas fa-save"></i>
                <span>Luu replay</span>
              </button>
            </form>

            <?php if (!$isReplayFormEnabled): ?>
              <p class="m-0 mt-2 text-xs text-slate-500">Replay chi duoc cap nhat sau khi live session ket thuc.</p>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    <?php else: ?>
      <article class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm text-slate-400">
        Chua co live session nao trong bo loc hien tai.
      </article>
    <?php endif; ?>
  </section>

  <script>
    (function () {
      const createCourseSelect = document.getElementById('create_course_id');
      const createSectionSelect = document.getElementById('create_section_id');
      const createBtn = document.getElementById('createLiveSessionBtn');

      function syncCreateSections() {
        if (!createCourseSelect || !createSectionSelect) {
          return;
        }

        const selectedCourse = createCourseSelect.value;
        const options = Array.from(createSectionSelect.options);
        let firstVisibleValue = '';

        options.forEach((option) => {
          const optionCourse = option.getAttribute('data-course-id');
          const visible = optionCourse === selectedCourse;
          option.hidden = !visible;
          option.disabled = !visible;
          if (visible && firstVisibleValue === '') {
            firstVisibleValue = option.value;
          }
        });

        const selectedOption = createSectionSelect.options[createSectionSelect.selectedIndex];
        const selectedOptionCourse = selectedOption ? selectedOption.getAttribute('data-course-id') : '';
        if (selectedOptionCourse !== selectedCourse && firstVisibleValue !== '') {
          createSectionSelect.value = firstVisibleValue;
        }

        if (createBtn) {
          const hasVisibleOption = firstVisibleValue !== '';
          createBtn.disabled = !hasVisibleOption;
          createBtn.classList.toggle('opacity-50', !hasVisibleOption);
        }
      }

      if (createCourseSelect) {
        createCourseSelect.addEventListener('change', syncCreateSections);
      }

      syncCreateSections();
    })();
  </script>
<?php endif; ?>

<?php require_once(__DIR__ . '/instructorInclude/footer.php'); ?>
