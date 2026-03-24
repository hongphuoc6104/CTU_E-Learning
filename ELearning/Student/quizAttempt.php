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
$quizId = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);
$submittedAnswers = isset($_POST['answers']) && is_array($_POST['answers']) ? $_POST['answers'] : [];

if (!$courseId || !$itemId || !$quizId) {
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

$quizItemStmt = $conn->prepare(
    'SELECT '
    . 'li.item_id, li.item_type, li.quiz_id, '
    . 'q.pass_score, q.max_attempts '
    . 'FROM learning_item li '
    . 'INNER JOIN quiz q ON q.quiz_id = li.quiz_id '
    . 'WHERE li.item_id = ? AND li.course_id = ? '
    . "AND li.is_deleted = 0 AND li.content_status = 'published' "
    . "AND li.item_type = 'quiz' "
    . "AND q.quiz_id = ? AND q.is_deleted = 0 AND q.content_status = 'published' "
    . 'LIMIT 1'
);
if (!$quizItemStmt) {
    $redirectWithFeedback('Khong the xu ly quiz luc nay.', 'error');
}

$quizItemStmt->bind_param('iii', $itemId, $courseId, $quizId);
$quizItemStmt->execute();
$quizItemRes = $quizItemStmt->get_result();
$quizItem = $quizItemRes ? $quizItemRes->fetch_assoc() : null;
$quizItemStmt->close();

if (!$quizItem) {
    $redirectWithFeedback('Quiz khong hop le hoac chua xuat ban.', 'error');
}

$passScore = isset($quizItem['pass_score']) ? (float) $quizItem['pass_score'] : 70.00;
$maxAttempts = isset($quizItem['max_attempts']) ? (int) $quizItem['max_attempts'] : 3;

$attemptCountStmt = $conn->prepare(
    'SELECT COUNT(*) AS total_attempts '
    . 'FROM quiz_attempt '
    . 'WHERE quiz_id = ? AND student_id = ? AND course_id = ? AND item_id = ? AND is_deleted = 0'
);
if (!$attemptCountStmt) {
    $redirectWithFeedback('Khong the kiem tra luot lam quiz.', 'error');
}

$attemptCountStmt->bind_param('iiii', $quizId, $studentId, $courseId, $itemId);
$attemptCountStmt->execute();
$attemptCountRes = $attemptCountStmt->get_result();
$attemptCountRow = $attemptCountRes ? $attemptCountRes->fetch_assoc() : null;
$attemptCountStmt->close();

$attemptsUsed = isset($attemptCountRow['total_attempts']) ? (int) $attemptCountRow['total_attempts'] : 0;
if ($maxAttempts > 0 && $attemptsUsed >= $maxAttempts) {
    $redirectWithFeedback('Ban da dung het so luot lam quiz nay.', 'warning');
}

$questionStmt = $conn->prepare(
    'SELECT '
    . 'qq.question_id, qq.points, '
    . 'qa.answer_id, qa.is_correct '
    . 'FROM quiz_question qq '
    . 'INNER JOIN quiz_answer qa '
    . 'ON qa.question_id = qq.question_id AND qa.is_deleted = 0 '
    . 'WHERE qq.quiz_id = ? AND qq.is_deleted = 0 '
    . 'ORDER BY qq.question_position ASC, qa.answer_position ASC'
);
if (!$questionStmt) {
    $redirectWithFeedback('Khong the doc cau hoi quiz.', 'error');
}

$questionStmt->bind_param('i', $quizId);
$questionStmt->execute();
$questionRes = $questionStmt->get_result();

$questionMap = [];
while ($questionRes && ($row = $questionRes->fetch_assoc())) {
    $questionId = (int) ($row['question_id'] ?? 0);
    if (!isset($questionMap[$questionId])) {
        $questionMap[$questionId] = [
            'points' => (float) ($row['points'] ?? 0),
            'answer_ids' => [],
            'correct_answer_ids' => [],
        ];
    }

    $answerId = (int) ($row['answer_id'] ?? 0);
    if ($answerId > 0) {
        $questionMap[$questionId]['answer_ids'][] = $answerId;
        if ((int) ($row['is_correct'] ?? 0) === 1) {
            $questionMap[$questionId]['correct_answer_ids'][] = $answerId;
        }
    }
}

$questionStmt->close();

if (empty($questionMap)) {
    $redirectWithFeedback('Quiz chua co cau hoi hop le de cham diem.', 'error');
}

$normalizedAnswers = [];
foreach ($submittedAnswers as $qId => $aId) {
    $normalizedQuestionId = filter_var($qId, FILTER_VALIDATE_INT);
    $normalizedAnswerId = filter_var($aId, FILTER_VALIDATE_INT);
    if ($normalizedQuestionId && $normalizedAnswerId) {
        $normalizedAnswers[(int) $normalizedQuestionId] = (int) $normalizedAnswerId;
    }
}

$malformed = false;
$totalPoints = 0.0;
$earnedPoints = 0.0;

foreach ($questionMap as $questionId => $meta) {
    $totalPoints += (float) ($meta['points'] ?? 0);

    if (!isset($normalizedAnswers[$questionId])) {
        $malformed = true;
        continue;
    }

    $selectedAnswerId = (int) $normalizedAnswers[$questionId];
    if (!in_array($selectedAnswerId, $meta['answer_ids'], true)) {
        $malformed = true;
        continue;
    }

    if (in_array($selectedAnswerId, $meta['correct_answer_ids'], true)) {
        $earnedPoints += (float) ($meta['points'] ?? 0);
    }
}

if ($malformed) {
    $redirectWithFeedback('Bai nop chua hop le. Vui long chon day du dap an cho tat ca cau hoi.', 'warning');
}

if ($totalPoints <= 0) {
    $redirectWithFeedback('Quiz khong co diem so hop le de cham.', 'error');
}

$scorePercent = round(($earnedPoints / $totalPoints) * 100, 2);
$scorePercent = max(0, min(100, $scorePercent));
$passed = $scorePercent >= $passScore ? 1 : 0;
$attemptNumber = $attemptsUsed + 1;

$insertAttemptStmt = $conn->prepare(
    'INSERT INTO quiz_attempt '
    . '(quiz_id, student_id, course_id, item_id, attempt_number, score, max_score, passed, started_at, submitted_at, is_deleted) '
    . 'VALUES (?, ?, ?, ?, ?, ?, 100.00, ?, NOW(), NOW(), 0)'
);
if (!$insertAttemptStmt) {
    $redirectWithFeedback('Khong the luu ket qua quiz.', 'error');
}

$insertAttemptStmt->bind_param(
    'iiiiidi',
    $quizId,
    $studentId,
    $courseId,
    $itemId,
    $attemptNumber,
    $scorePercent,
    $passed
);

$insertOk = $insertAttemptStmt->execute();
$insertAttemptStmt->close();

if (!$insertOk) {
    $redirectWithFeedback('Khong the ghi nhan bai nop quiz. Vui long thu lai.', 'error');
}

if ($passed === 1) {
    learning_upsert_progress($conn, $studentId, $courseId, $itemId, 'passed', true);
} else {
    learning_upsert_progress($conn, $studentId, $courseId, $itemId, 'in_progress', false);
}

$summary = learning_recalculate_course_progress($conn, $studentId, $courseId);
$progressPercent = isset($summary['progress_percent']) ? (float) $summary['progress_percent'] : 0;
$attemptsLeft = $maxAttempts > 0 ? max(0, $maxAttempts - $attemptNumber) : null;

$scoreText = rtrim(rtrim(number_format($scorePercent, 2, '.', ''), '0'), '.');

if ($passed === 1) {
    $message = 'Ban da pass quiz voi ' . $scoreText . '%. Tien do khoa hoc hien tai: ' . round($progressPercent) . '%.';
    $redirectWithFeedback($message, 'success');
}

$message = 'Ban chua dat quiz (' . $scoreText . '%).';
if ($attemptsLeft !== null) {
    $message .= ' So luot con lai: ' . $attemptsLeft . '.';
}
$redirectWithFeedback($message, 'warning');
