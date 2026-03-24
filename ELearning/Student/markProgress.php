<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../csrf.php');
require_once(__DIR__ . '/learning_helpers.php');
include('../dbConnection.php');

if (!isset($_SESSION['is_login'], $_SESSION['stuLogEmail'])) {
    echo "<script>location.href='../index.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>location.href='myCourse.php';</script>";
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    echo "<script>location.href='myCourse.php';</script>";
    exit;
}

$stuEmail = (string) $_SESSION['stuLogEmail'];
$courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
$action = isset($_POST['action']) ? trim((string) $_POST['action']) : '';

if (!$courseId || !$itemId || $action === '') {
    echo "<script>location.href='myCourse.php';</script>";
    exit;
}

$studentProfile = learning_get_student_profile($conn, $stuEmail);
if (!$studentProfile || (int) $studentProfile['stu_id'] <= 0) {
    echo "<script>location.href='../index.php';</script>";
    exit;
}

$studentId = (int) $studentProfile['stu_id'];

$redirectBase = 'watchcourse.php?course_id=' . $courseId . '&item_id=' . $itemId;
$redirectWithFeedback = function ($message, $type = 'info') use ($redirectBase) {
    $safeType = in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info';
    $url = $redirectBase
        . '&feedback=' . rawurlencode($message)
        . '&feedback_type=' . rawurlencode($safeType);

    echo "<script>location.href='" . $url . "';</script>";
    exit;
};

if (!learning_has_course_access($conn, $studentId, $courseId)) {
    $redirectWithFeedback('Ban chua duoc cap quyen hoc khoa nay.', 'error');
}

$itemStmt = $conn->prepare(
    'SELECT item_id, course_id, item_type, live_session_id, replay_id '
    . 'FROM learning_item '
    . "WHERE item_id = ? AND course_id = ? AND is_deleted = 0 AND content_status = 'published' LIMIT 1"
);
if (!$itemStmt) {
    $redirectWithFeedback('Khong the cap nhat tien do luc nay.', 'error');
}

$itemStmt->bind_param('ii', $itemId, $courseId);
$itemStmt->execute();
$itemRes = $itemStmt->get_result();
$item = $itemRes ? $itemRes->fetch_assoc() : null;
$itemStmt->close();

if (!$item) {
    $redirectWithFeedback('Learning item khong ton tai hoac chua duoc xuat ban.', 'error');
}

$itemType = (string) ($item['item_type'] ?? '');
$targetStatus = 'in_progress';
$markCompleted = false;

if ($action === 'complete') {
    if ($itemType === 'quiz') {
        $redirectWithFeedback('Quiz can nop bai de duoc cham diem, khong danh dau thu cong.', 'warning');
    }

    $targetStatus = $itemType === 'quiz' ? 'passed' : 'completed';
    $markCompleted = true;
} elseif ($action === 'join_live') {
    if ($itemType !== 'live_session') {
        $redirectWithFeedback('Hanh dong tham gia chi ap dung cho live session.', 'warning');
    }

    $targetStatus = 'completed';
    $markCompleted = true;
} elseif ($action === 'start') {
    $targetStatus = 'in_progress';
    $markCompleted = false;
} else {
    $redirectWithFeedback('Hanh dong cap nhat tien do khong hop le.', 'error');
}

$ok = learning_upsert_progress(
    $conn,
    $studentId,
    $courseId,
    $itemId,
    $targetStatus,
    $markCompleted
);

if (!$ok) {
    $redirectWithFeedback('Cap nhat tien do that bai, vui long thu lai.', 'error');
}

if ($action === 'join_live' && (int) ($item['live_session_id'] ?? 0) > 0) {
    $liveSessionId = (int) $item['live_session_id'];

    $attendanceStmt = $conn->prepare(
        'SELECT attendance_id, joined_at '
        . 'FROM session_attendance '
        . 'WHERE live_session_id = ? AND student_id = ? '
        . 'ORDER BY attendance_id DESC LIMIT 1'
    );

    if ($attendanceStmt) {
        $attendanceStmt->bind_param('ii', $liveSessionId, $studentId);
        $attendanceStmt->execute();
        $attendanceRes = $attendanceStmt->get_result();
        $attendanceRow = $attendanceRes ? $attendanceRes->fetch_assoc() : null;
        $attendanceStmt->close();

        if ($attendanceRow) {
            $attendanceId = (int) ($attendanceRow['attendance_id'] ?? 0);
            $updateAttendanceStmt = $conn->prepare(
                "UPDATE session_attendance "
                . "SET joined_at = COALESCE(joined_at, NOW()), attendance_status = 'attended' "
                . 'WHERE attendance_id = ?'
            );
            if ($updateAttendanceStmt) {
                $updateAttendanceStmt->bind_param('i', $attendanceId);
                $updateAttendanceStmt->execute();
                $updateAttendanceStmt->close();
            }
        } else {
            $insertAttendanceStmt = $conn->prepare(
                'INSERT INTO session_attendance (live_session_id, student_id, joined_at, attendance_status) '
                . "VALUES (?, ?, NOW(), 'attended')"
            );
            if ($insertAttendanceStmt) {
                $insertAttendanceStmt->bind_param('ii', $liveSessionId, $studentId);
                $insertAttendanceStmt->execute();
                $insertAttendanceStmt->close();
            }
        }
    }
}

if ($action === 'complete' && $itemType === 'replay' && (int) ($item['replay_id'] ?? 0) > 0) {
    $replayId = (int) $item['replay_id'];

    $replayStmt = $conn->prepare('SELECT live_session_id FROM replay_asset WHERE replay_id = ? AND is_deleted = 0 LIMIT 1');
    if ($replayStmt) {
        $replayStmt->bind_param('i', $replayId);
        $replayStmt->execute();
        $replayRes = $replayStmt->get_result();
        $replayRow = $replayRes ? $replayRes->fetch_assoc() : null;
        $replayStmt->close();

        $linkedLiveSessionId = isset($replayRow['live_session_id']) ? (int) $replayRow['live_session_id'] : 0;
        if ($linkedLiveSessionId > 0) {
            $linkedLiveItemStmt = $conn->prepare(
                'SELECT item_id FROM learning_item '
                . "WHERE course_id = ? AND item_type = 'live_session' AND live_session_id = ? "
                . "AND is_deleted = 0 AND content_status = 'published' LIMIT 1"
            );
            if ($linkedLiveItemStmt) {
                $linkedLiveItemStmt->bind_param('ii', $courseId, $linkedLiveSessionId);
                $linkedLiveItemStmt->execute();
                $linkedLiveItemRes = $linkedLiveItemStmt->get_result();
                $linkedLiveItem = $linkedLiveItemRes ? $linkedLiveItemRes->fetch_assoc() : null;
                $linkedLiveItemStmt->close();

                if ($linkedLiveItem && isset($linkedLiveItem['item_id'])) {
                    $linkedLiveItemId = (int) $linkedLiveItem['item_id'];
                    if ($linkedLiveItemId > 0) {
                        learning_upsert_progress(
                            $conn,
                            $studentId,
                            $courseId,
                            $linkedLiveItemId,
                            'completed',
                            true
                        );
                    }
                }
            }
        }
    }
}

$summary = learning_recalculate_course_progress($conn, $studentId, $courseId);
$progressPercent = isset($summary['progress_percent']) ? (float) $summary['progress_percent'] : 0;

$feedbackMessage = 'Tien do da duoc cap nhat.';
if ($action === 'join_live') {
    $feedbackMessage = 'Da ghi nhan tham gia live session va cap nhat tien do.';
} elseif ($action === 'complete') {
    $feedbackMessage = 'Da danh dau hoan thanh muc hoc. Tien do hien tai: ' . round($progressPercent) . '%.';
}

$redirectWithFeedback($feedbackMessage, 'success');
