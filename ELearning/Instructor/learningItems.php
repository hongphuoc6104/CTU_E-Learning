<?php

define('TITLE', 'Learning items');
define('PAGE', 'items');

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
    header('Location: learningItems.php');
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
        header('Location: learningItems.php?course_id=' . $filterCourseId);
        exit;
    }
}

if (isset($_POST['delete_item'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        instructor_set_flash('error', 'Phien gui bieu mau da het han.');
        header('Location: learningItems.php?course_id=' . $filterCourseId . '&section_id=' . $filterSectionId);
        exit;
    }

    $itemId = (int) ($_POST['item_id'] ?? 0);
    if ($itemId <= 0) {
        instructor_set_flash('error', 'Learning item khong hop le.');
        header('Location: learningItems.php?course_id=' . $filterCourseId . '&section_id=' . $filterSectionId);
        exit;
    }

    $verifyStmt = $conn->prepare(
        'SELECT li.item_id, li.course_id '
        . 'FROM learning_item li '
        . 'INNER JOIN course c ON c.course_id = li.course_id '
        . 'WHERE li.item_id = ? AND li.is_deleted = 0 AND c.instructor_id = ? AND c.is_deleted = 0 '
        . 'LIMIT 1'
    );
    $verifiedCourseId = 0;
    if ($verifyStmt) {
        $verifyStmt->bind_param('ii', $itemId, $instructorId);
        $verifyStmt->execute();
        $result = $verifyStmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        if ($row) {
            $verifiedCourseId = (int) ($row['course_id'] ?? 0);
        }
        $verifyStmt->close();
    }

    if ($verifiedCourseId <= 0) {
        instructor_set_flash('error', 'Ban khong duoc phep xoa learning item nay.');
        header('Location: learningItems.php?course_id=' . $filterCourseId . '&section_id=' . $filterSectionId);
        exit;
    }

    $deleteStmt = $conn->prepare('UPDATE learning_item SET is_deleted = 1 WHERE item_id = ?');
    if ($deleteStmt) {
        $deleteStmt->bind_param('i', $itemId);
        $ok = $deleteStmt->execute();
        $deleteStmt->close();
        if ($ok) {
            instructor_set_flash('success', 'Da xoa learning item.');
        } else {
            instructor_set_flash('error', 'Khong the xoa learning item luc nay.');
        }
    } else {
        instructor_set_flash('error', 'Khong the xoa learning item luc nay.');
    }

    $redirectCourseId = $verifiedCourseId > 0 ? $verifiedCourseId : $filterCourseId;
    header('Location: learningItems.php?course_id=' . $redirectCourseId . '&section_id=' . $filterSectionId);
    exit;
}

if (isset($_POST['create_item'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        instructor_set_flash('error', 'Phien gui bieu mau da het han.');
        header('Location: learningItems.php?course_id=' . $filterCourseId . '&section_id=' . $filterSectionId);
        exit;
    }

    $createCourseId = (int) ($_POST['create_course_id'] ?? 0);
    $createSectionId = (int) ($_POST['create_section_id'] ?? 0);
    $itemTitle = trim((string) ($_POST['item_title'] ?? ''));
    $itemType = trim((string) ($_POST['item_type'] ?? 'video'));
    $itemPositionInput = trim((string) ($_POST['item_position'] ?? ''));
    $contentStatus = trim((string) ($_POST['content_status'] ?? 'draft'));
    $isPreview = isset($_POST['is_preview']) ? 1 : 0;
    $isRequired = isset($_POST['is_required']) ? 1 : 0;

    $createCourse = instructor_find_owned_course($conn, $createCourseId, $instructorId);
    if (!$createCourse) {
        instructor_set_flash('error', 'Ban khong duoc phep tao learning item cho khoa hoc nay.');
        header('Location: learningItems.php?course_id=' . $filterCourseId . '&section_id=' . $filterSectionId);
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
        header('Location: learningItems.php?course_id=' . $createCourseId);
        exit;
    }

    if ($itemTitle === '') {
        instructor_set_flash('error', 'Tieu de learning item khong duoc de trong.');
        header('Location: learningItems.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
        exit;
    }

    $allowedTypes = ['video', 'article', 'document', 'quiz', 'live_session', 'replay'];
    if (!in_array($itemType, $allowedTypes, true)) {
        instructor_set_flash('error', 'Loai learning item khong hop le.');
        header('Location: learningItems.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
        exit;
    }

    if (!in_array($contentStatus, ['draft', 'published'], true)) {
        instructor_set_flash('error', 'Trang thai learning item khong hop le.');
        header('Location: learningItems.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
        exit;
    }

    $videoUrl = null;
    $articleContent = null;
    $documentUrl = null;
    $quizId = null;
    $liveSessionId = null;
    $replayId = null;

    if ($itemType === 'video') {
        $videoUrlInput = trim((string) ($_POST['video_url'] ?? ''));
        if (!instructor_is_valid_url($videoUrlInput)) {
            instructor_set_flash('error', 'Video URL khong hop le.');
            header('Location: learningItems.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
            exit;
        }
        $videoUrl = $videoUrlInput;
    } elseif ($itemType === 'article') {
        $articleInput = trim((string) ($_POST['article_content'] ?? ''));
        if ($articleInput === '') {
            instructor_set_flash('error', 'Noi dung article khong duoc de trong.');
            header('Location: learningItems.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
            exit;
        }
        $articleContent = $articleInput;
    } elseif ($itemType === 'document') {
        $documentUrlInput = trim((string) ($_POST['document_url'] ?? ''));
        if (!instructor_is_valid_url($documentUrlInput)) {
            instructor_set_flash('error', 'Document URL khong hop le.');
            header('Location: learningItems.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
            exit;
        }
        $documentUrl = $documentUrlInput;
    } elseif ($itemType === 'quiz') {
        $quizInput = (int) ($_POST['quiz_id'] ?? 0);
        if ($quizInput <= 0) {
            instructor_set_flash('error', 'Vui long chon quiz hop le.');
            header('Location: learningItems.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
            exit;
        }

        $quizValidStmt = $conn->prepare('SELECT quiz_id FROM quiz WHERE quiz_id = ? AND course_id = ? AND is_deleted = 0 LIMIT 1');
        $quizValid = false;
        if ($quizValidStmt) {
            $quizValidStmt->bind_param('ii', $quizInput, $createCourseId);
            $quizValidStmt->execute();
            $quizValidStmt->store_result();
            $quizValid = $quizValidStmt->num_rows > 0;
            $quizValidStmt->close();
        }
        if (!$quizValid) {
            instructor_set_flash('error', 'Quiz khong hop le cho khoa hoc nay.');
            header('Location: learningItems.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
            exit;
        }
        $quizId = $quizInput;
    } elseif ($itemType === 'live_session') {
        $liveInput = (int) ($_POST['live_session_id'] ?? 0);
        if ($liveInput <= 0) {
            instructor_set_flash('error', 'Vui long chon live session hop le.');
            header('Location: learningItems.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
            exit;
        }

        $liveValidStmt = $conn->prepare(
            'SELECT live_session_id '
            . 'FROM live_session '
            . 'WHERE live_session_id = ? AND course_id = ? AND section_id = ? AND instructor_id = ? AND is_deleted = 0 '
            . 'LIMIT 1'
        );
        $liveValid = false;
        if ($liveValidStmt) {
            $liveValidStmt->bind_param('iiii', $liveInput, $createCourseId, $createSectionId, $instructorId);
            $liveValidStmt->execute();
            $liveValidStmt->store_result();
            $liveValid = $liveValidStmt->num_rows > 0;
            $liveValidStmt->close();
        }
        if (!$liveValid) {
            instructor_set_flash('error', 'Live session khong hop le hoac khong thuoc section nay.');
            header('Location: learningItems.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
            exit;
        }

        $duplicateStmt = $conn->prepare('SELECT item_id FROM learning_item WHERE live_session_id = ? AND is_deleted = 0 LIMIT 1');
        $duplicated = false;
        if ($duplicateStmt) {
            $duplicateStmt->bind_param('i', $liveInput);
            $duplicateStmt->execute();
            $duplicateStmt->store_result();
            $duplicated = $duplicateStmt->num_rows > 0;
            $duplicateStmt->close();
        }
        if ($duplicated) {
            instructor_set_flash('error', 'Live session nay da ton tai trong timeline.');
            header('Location: learningItems.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
            exit;
        }

        $liveSessionId = $liveInput;
    } elseif ($itemType === 'replay') {
        $replayInput = (int) ($_POST['replay_id'] ?? 0);
        if ($replayInput <= 0) {
            instructor_set_flash('error', 'Vui long chon replay hop le.');
            header('Location: learningItems.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
            exit;
        }

        $replayValidStmt = $conn->prepare(
            'SELECT ra.replay_id '
            . 'FROM replay_asset ra '
            . 'INNER JOIN live_session ls ON ls.live_session_id = ra.live_session_id '
            . 'WHERE ra.replay_id = ? AND ra.is_deleted = 0 '
            . 'AND ls.course_id = ? AND ls.section_id = ? AND ls.instructor_id = ? AND ls.is_deleted = 0 '
            . 'AND TRIM(COALESCE(ra.recording_url, \'\')) <> \'\' '
            . 'LIMIT 1'
        );
        $replayValid = false;
        if ($replayValidStmt) {
            $replayValidStmt->bind_param('iiii', $replayInput, $createCourseId, $createSectionId, $instructorId);
            $replayValidStmt->execute();
            $replayValidStmt->store_result();
            $replayValid = $replayValidStmt->num_rows > 0;
            $replayValidStmt->close();
        }
        if (!$replayValid) {
            instructor_set_flash('error', 'Replay khong hop le hoac chua co recording URL.');
            header('Location: learningItems.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
            exit;
        }

        $duplicateReplayStmt = $conn->prepare('SELECT item_id FROM learning_item WHERE replay_id = ? AND is_deleted = 0 LIMIT 1');
        $duplicatedReplay = false;
        if ($duplicateReplayStmt) {
            $duplicateReplayStmt->bind_param('i', $replayInput);
            $duplicateReplayStmt->execute();
            $duplicateReplayStmt->store_result();
            $duplicatedReplay = $duplicateReplayStmt->num_rows > 0;
            $duplicateReplayStmt->close();
        }
        if ($duplicatedReplay) {
            instructor_set_flash('error', 'Replay nay da ton tai trong timeline.');
            header('Location: learningItems.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
            exit;
        }

        $replayId = $replayInput;
    }

    $position = 0;
    if ($itemPositionInput === '') {
        $position = instructor_next_item_position($conn, $createCourseId, $createSectionId);
    } else {
        $position = (int) $itemPositionInput;
        if ($position <= 0) {
            instructor_set_flash('error', 'Vi tri learning item phai lon hon 0.');
            header('Location: learningItems.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
            exit;
        }
    }

    $conn->begin_transaction();
    $dbError = false;

    if ($itemPositionInput !== '') {
        $shiftStmt = $conn->prepare(
            'UPDATE learning_item '
            . 'SET item_position = item_position + 1 '
            . 'WHERE course_id = ? AND section_id = ? AND is_deleted = 0 AND item_position >= ?'
        );
        if ($shiftStmt) {
            $shiftStmt->bind_param('iii', $createCourseId, $createSectionId, $position);
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
            'INSERT INTO learning_item '
            . '(course_id, section_id, item_title, item_type, item_position, is_preview, is_required, content_status, '
            . 'video_url, article_content, document_url, quiz_id, live_session_id, replay_id, is_deleted) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)'
        );
        if ($insertStmt) {
            $insertStmt->bind_param(
                'iissiiissssiii',
                $createCourseId,
                $createSectionId,
                $itemTitle,
                $itemType,
                $position,
                $isPreview,
                $isRequired,
                $contentStatus,
                $videoUrl,
                $articleContent,
                $documentUrl,
                $quizId,
                $liveSessionId,
                $replayId
            );

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
        instructor_set_flash('error', 'Khong the tao learning item luc nay.');
    } else {
        $conn->commit();
        instructor_set_flash('success', 'Da tao learning item thanh cong.');
    }

    header('Location: learningItems.php?course_id=' . $createCourseId . '&section_id=' . $createSectionId);
    exit;
}

$quizOptions = [];
$liveOptions = [];
$replayOptions = [];

if ($selectedCourse) {
    $quizStmt = $conn->prepare(
        'SELECT quiz_id, quiz_title '
        . 'FROM quiz '
        . 'WHERE course_id = ? AND is_deleted = 0 '
        . 'ORDER BY quiz_id DESC'
    );
    if ($quizStmt) {
        $quizStmt->bind_param('i', $filterCourseId);
        $quizStmt->execute();
        $result = $quizStmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $quizOptions[] = $row;
            }
        }
        $quizStmt->close();
    }

    if ($filterSectionId > 0) {
        $liveStmt = $conn->prepare(
            'SELECT live_session_id, session_title '
            . 'FROM live_session '
            . 'WHERE course_id = ? AND section_id = ? AND instructor_id = ? AND is_deleted = 0 '
            . 'ORDER BY start_at DESC'
        );
        if ($liveStmt) {
            $liveStmt->bind_param('iii', $filterCourseId, $filterSectionId, $instructorId);
            $liveStmt->execute();
            $result = $liveStmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $liveOptions[] = $row;
                }
            }
            $liveStmt->close();
        }

        $replayStmt = $conn->prepare(
            'SELECT ra.replay_id, ls.session_title '
            . 'FROM replay_asset ra '
            . 'INNER JOIN live_session ls ON ls.live_session_id = ra.live_session_id '
            . 'WHERE ls.course_id = ? AND ls.section_id = ? AND ls.instructor_id = ? '
            . 'AND ls.is_deleted = 0 AND ra.is_deleted = 0 '
            . 'AND TRIM(COALESCE(ra.recording_url, \'\')) <> \'\' '
            . 'ORDER BY ra.available_at DESC'
        );
        if ($replayStmt) {
            $replayStmt->bind_param('iii', $filterCourseId, $filterSectionId, $instructorId);
            $replayStmt->execute();
            $result = $replayStmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $replayOptions[] = $row;
                }
            }
            $replayStmt->close();
        }
    } else {
        $liveStmt = $conn->prepare(
            'SELECT live_session_id, session_title '
            . 'FROM live_session '
            . 'WHERE course_id = ? AND instructor_id = ? AND is_deleted = 0 '
            . 'ORDER BY start_at DESC'
        );
        if ($liveStmt) {
            $liveStmt->bind_param('ii', $filterCourseId, $instructorId);
            $liveStmt->execute();
            $result = $liveStmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $liveOptions[] = $row;
                }
            }
            $liveStmt->close();
        }

        $replayStmt = $conn->prepare(
            'SELECT ra.replay_id, ls.session_title '
            . 'FROM replay_asset ra '
            . 'INNER JOIN live_session ls ON ls.live_session_id = ra.live_session_id '
            . 'WHERE ls.course_id = ? AND ls.instructor_id = ? '
            . 'AND ls.is_deleted = 0 AND ra.is_deleted = 0 '
            . 'AND TRIM(COALESCE(ra.recording_url, \'\')) <> \'\' '
            . 'ORDER BY ra.available_at DESC'
        );
        if ($replayStmt) {
            $replayStmt->bind_param('ii', $filterCourseId, $instructorId);
            $replayStmt->execute();
            $result = $replayStmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $replayOptions[] = $row;
                }
            }
            $replayStmt->close();
        }
    }
}

$items = [];
if ($selectedCourse) {
    if ($filterSectionId > 0) {
        $itemsStmt = $conn->prepare(
            'SELECT li.item_id, li.item_title, li.item_type, li.item_position, li.content_status, li.is_preview, li.is_required, '
            . 'li.video_url, li.document_url, li.quiz_id, li.live_session_id, li.replay_id, cs.section_title '
            . 'FROM learning_item li '
            . 'INNER JOIN course c ON c.course_id = li.course_id '
            . 'INNER JOIN course_section cs ON cs.section_id = li.section_id '
            . 'WHERE c.instructor_id = ? AND c.is_deleted = 0 AND li.is_deleted = 0 '
            . 'AND li.course_id = ? AND li.section_id = ? '
            . 'ORDER BY li.item_position ASC, li.item_id ASC'
        );
        if ($itemsStmt) {
            $itemsStmt->bind_param('iii', $instructorId, $filterCourseId, $filterSectionId);
            $itemsStmt->execute();
            $result = $itemsStmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $items[] = $row;
                }
            }
            $itemsStmt->close();
        }
    } else {
        $itemsStmt = $conn->prepare(
            'SELECT li.item_id, li.item_title, li.item_type, li.item_position, li.content_status, li.is_preview, li.is_required, '
            . 'li.video_url, li.document_url, li.quiz_id, li.live_session_id, li.replay_id, cs.section_title '
            . 'FROM learning_item li '
            . 'INNER JOIN course c ON c.course_id = li.course_id '
            . 'INNER JOIN course_section cs ON cs.section_id = li.section_id '
            . 'WHERE c.instructor_id = ? AND c.is_deleted = 0 AND li.is_deleted = 0 AND li.course_id = ? '
            . 'ORDER BY cs.section_position ASC, li.item_position ASC, li.item_id ASC'
        );
        if ($itemsStmt) {
            $itemsStmt->bind_param('ii', $instructorId, $filterCourseId);
            $itemsStmt->execute();
            $result = $itemsStmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $items[] = $row;
                }
            }
            $itemsStmt->close();
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
  <h1 class="m-0 text-2xl font-black text-slate-900">Learning items</h1>
  <p class="m-0 mt-1 text-sm text-slate-500">Them noi dung theo type: video, article, document, quiz, live session, replay.</p>
</section>

<?php if (count($ownedCourses) === 0): ?>
  <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
    <p class="m-0 text-sm text-slate-500">Ban chua co khoa hoc nao. Hay tao khoa hoc truoc.</p>
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
    <h2 class="m-0 mb-4 text-base font-black text-slate-800">Tao learning item moi</h2>
    <form method="post" class="space-y-4" id="createItemForm">
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

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label for="item_title" class="mb-2 block text-sm font-bold text-slate-700">Tieu de item</label>
          <input type="text" id="item_title" name="item_title" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="VD: Workshop live 1 - Routing">
        </div>
        <div>
          <label for="item_type" class="mb-2 block text-sm font-bold text-slate-700">Loai item</label>
          <select id="item_type" name="item_type" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" required>
            <option value="video">Video</option>
            <option value="article">Article</option>
            <option value="document">Document</option>
            <option value="quiz">Quiz</option>
            <option value="live_session">Live session</option>
            <option value="replay">Replay</option>
          </select>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label for="item_position" class="mb-2 block text-sm font-bold text-slate-700">Vi tri (tuỳ chon)</label>
          <input type="number" min="1" id="item_position" name="item_position" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="De trong de tu dong xep cuoi">
        </div>
        <div>
          <label for="content_status" class="mb-2 block text-sm font-bold text-slate-700">Trang thai item</label>
          <select id="content_status" name="content_status" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
            <option value="draft">Draft</option>
            <option value="published">Published</option>
          </select>
        </div>
      </div>

      <div class="grid gap-3 sm:grid-cols-2">
        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600">
          <input type="checkbox" name="is_preview" value="1" class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary/30">
          <span>Cho phep preview</span>
        </label>
        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600">
          <input type="checkbox" name="is_required" value="1" checked class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary/30">
          <span>Danh dau bat buoc</span>
        </label>
      </div>

      <div id="panel-video" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
        <label for="video_url" class="mb-2 block text-sm font-bold text-slate-700">Video URL</label>
        <input type="url" id="video_url" name="video_url" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="https://youtube.com/watch?v=...">
      </div>

      <div id="panel-article" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-3">
        <label for="article_content" class="mb-2 block text-sm font-bold text-slate-700">Noi dung article</label>
        <textarea id="article_content" name="article_content" rows="6" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="Nhap noi dung article..."></textarea>
      </div>

      <div id="panel-document" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-3">
        <label for="document_url" class="mb-2 block text-sm font-bold text-slate-700">Document URL</label>
        <input type="url" id="document_url" name="document_url" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="https://example.com/document.pdf">
      </div>

      <div id="panel-quiz" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-3">
        <label for="quiz_id" class="mb-2 block text-sm font-bold text-slate-700">Chon quiz</label>
        <select id="quiz_id" name="quiz_id" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
          <option value="">-- Chon quiz --</option>
          <?php foreach ($quizOptions as $quiz): ?>
            <option value="<?php echo (int) ($quiz['quiz_id'] ?? 0); ?>"><?php echo htmlspecialchars((string) ($quiz['quiz_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="panel-live_session" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-3">
        <label for="live_session_id" class="mb-2 block text-sm font-bold text-slate-700">Chon live session</label>
        <select id="live_session_id" name="live_session_id" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
          <option value="">-- Chon live session --</option>
          <?php foreach ($liveOptions as $live): ?>
            <option value="<?php echo (int) ($live['live_session_id'] ?? 0); ?>"><?php echo htmlspecialchars((string) ($live['session_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="panel-replay" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-3">
        <label for="replay_id" class="mb-2 block text-sm font-bold text-slate-700">Chon replay</label>
        <select id="replay_id" name="replay_id" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
          <option value="">-- Chon replay --</option>
          <?php foreach ($replayOptions as $replay): ?>
            <option value="<?php echo (int) ($replay['replay_id'] ?? 0); ?>">Replay #<?php echo (int) ($replay['replay_id'] ?? 0); ?> - <?php echo htmlspecialchars((string) ($replay['session_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" name="create_item" class="inline-flex items-center gap-2 rounded-xl border-0 bg-primary px-5 py-3 text-sm font-extrabold text-white transition hover:bg-primary/90">
        <i class="fas fa-plus-circle"></i>
        <span>Tao learning item</span>
      </button>
    </form>
  </section>

  <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[980px] text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
          <tr>
            <th class="px-4 py-3 text-left font-bold">Section</th>
            <th class="px-4 py-3 text-left font-bold">Item</th>
            <th class="px-4 py-3 text-left font-bold">Type</th>
            <th class="px-4 py-3 text-center font-bold">Pos</th>
            <th class="px-4 py-3 text-center font-bold">Status</th>
            <th class="px-4 py-3 text-center font-bold">Rule</th>
            <th class="px-4 py-3 text-left font-bold">Ref</th>
            <th class="px-4 py-3 text-right font-bold">Thao tac</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (count($items) > 0): ?>
            <?php foreach ($items as $item): ?>
              <tr>
                <td class="px-4 py-4 text-slate-600"><?php echo htmlspecialchars((string) ($item['section_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="px-4 py-4">
                  <p class="m-0 max-w-[280px] truncate font-bold text-slate-800"><?php echo htmlspecialchars((string) ($item['item_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                </td>
                <td class="px-4 py-4">
                  <span class="inline-flex rounded-lg bg-slate-100 px-2 py-1 text-[11px] font-bold text-slate-700"><?php echo htmlspecialchars((string) ($item['item_type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                </td>
                <td class="px-4 py-4 text-center font-bold text-primary"><?php echo (int) ($item['item_position'] ?? 0); ?></td>
                <td class="px-4 py-4 text-center">
                  <?php if ((string) ($item['content_status'] ?? 'draft') === 'published'): ?>
                    <span class="inline-flex rounded-lg bg-emerald-100 px-2 py-1 text-[11px] font-bold text-emerald-700">Published</span>
                  <?php else: ?>
                    <span class="inline-flex rounded-lg bg-amber-100 px-2 py-1 text-[11px] font-bold text-amber-700">Draft</span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-4 text-center text-xs text-slate-500">
                  <?php
                    $ruleParts = [];
                    if ((int) ($item['is_preview'] ?? 0) === 1) {
                        $ruleParts[] = 'preview';
                    }
                    if ((int) ($item['is_required'] ?? 0) === 1) {
                        $ruleParts[] = 'required';
                    }
                    echo htmlspecialchars(count($ruleParts) > 0 ? implode(', ', $ruleParts) : '-', ENT_QUOTES, 'UTF-8');
                  ?>
                </td>
                <td class="px-4 py-4 text-xs text-slate-500">
                  <?php
                    $itemType = (string) ($item['item_type'] ?? '');
                    $ref = '-';
                    if ($itemType === 'video') {
                        $ref = (string) ($item['video_url'] ?? '-');
                    } elseif ($itemType === 'document') {
                        $ref = (string) ($item['document_url'] ?? '-');
                    } elseif ($itemType === 'quiz') {
                        $quizRef = (int) ($item['quiz_id'] ?? 0);
                        $ref = $quizRef > 0 ? ('Quiz #' . $quizRef) : '-';
                    } elseif ($itemType === 'live_session') {
                        $liveRef = (int) ($item['live_session_id'] ?? 0);
                        $ref = $liveRef > 0 ? ('Live #' . $liveRef) : '-';
                    } elseif ($itemType === 'replay') {
                        $replayRef = (int) ($item['replay_id'] ?? 0);
                        $ref = $replayRef > 0 ? ('Replay #' . $replayRef) : '-';
                    } elseif ($itemType === 'article') {
                        $ref = 'Article content';
                    }
                    echo htmlspecialchars(strlen($ref) > 60 ? substr($ref, 0, 57) . '...' : $ref, ENT_QUOTES, 'UTF-8');
                  ?>
                </td>
                <td class="px-4 py-4 text-right">
                  <form method="post" class="m-0 inline-block" onsubmit="return confirm('Xoa learning item nay?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="item_id" value="<?php echo (int) ($item['item_id'] ?? 0); ?>">
                    <button type="submit" name="delete_item" class="inline-flex items-center gap-1 rounded-lg border-0 bg-red-500 px-2.5 py-1.5 text-xs font-extrabold text-white transition hover:bg-red-600">
                      <i class="fas fa-trash"></i>
                      <span>Xoa</span>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="px-4 py-14 text-center text-sm text-slate-400">Chua co learning item nao trong bo loc hien tai.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <script>
    (function () {
      const courseSelect = document.getElementById('create_course_id');
      const sectionSelect = document.getElementById('create_section_id');
      const itemTypeSelect = document.getElementById('item_type');

      const panelIds = ['video', 'article', 'document', 'quiz', 'live_session', 'replay'];

      function syncSectionOptions() {
        if (!courseSelect || !sectionSelect) {
          return;
        }

        const selectedCourse = courseSelect.value;
        const options = Array.from(sectionSelect.options);
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

        const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
        const selectedOptionCourse = selectedOption ? selectedOption.getAttribute('data-course-id') : '';
        if (selectedOptionCourse !== selectedCourse && firstVisibleValue !== '') {
          sectionSelect.value = firstVisibleValue;
        }
      }

      function syncPanels() {
        if (!itemTypeSelect) {
          return;
        }
        const currentType = itemTypeSelect.value;
        panelIds.forEach((id) => {
          const panel = document.getElementById('panel-' + id);
          if (!panel) {
            return;
          }
          if (id === currentType) {
            panel.classList.remove('hidden');
          } else {
            panel.classList.add('hidden');
          }
        });
      }

      if (courseSelect) {
        courseSelect.addEventListener('change', syncSectionOptions);
      }
      if (itemTypeSelect) {
        itemTypeSelect.addEventListener('change', syncPanels);
      }

      syncSectionOptions();
      syncPanels();
    })();
  </script>
<?php endif; ?>

<?php require_once(__DIR__ . '/instructorInclude/footer.php'); ?>
