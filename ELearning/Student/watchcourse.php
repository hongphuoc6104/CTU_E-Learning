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

$stuEmail = (string) $_SESSION['stuLogEmail'];
$studentProfile = learning_get_student_profile($conn, $stuEmail);
if (!$studentProfile || (int) $studentProfile['stu_id'] <= 0) {
    echo "<script>location.href='../index.php';</script>";
    exit;
}

$studentId = (int) $studentProfile['stu_id'];
$courseId = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
if (!$courseId) {
    echo "<script>location.href='myCourse.php';</script>";
    exit;
}

$courseStmt = $conn->prepare(
    'SELECT course_id, course_name, course_desc, course_img, course_author, course_duration '
    . 'FROM course WHERE course_id = ? AND is_deleted = 0 LIMIT 1'
);
if (!$courseStmt) {
    echo "<script>location.href='myCourse.php';</script>";
    exit;
}

$courseStmt->bind_param('i', $courseId);
$courseStmt->execute();
$courseRes = $courseStmt->get_result();
$course = $courseRes ? $courseRes->fetch_assoc() : null;
$courseStmt->close();

if (!$course) {
    echo "<script>location.href='myCourse.php';</script>";
    exit;
}

$hasAccess = learning_has_course_access($conn, $studentId, $courseId);
$csrfToken = csrf_token();

$feedback = isset($_GET['feedback']) ? trim((string) $_GET['feedback']) : '';
$feedbackType = isset($_GET['feedback_type']) ? trim((string) $_GET['feedback_type']) : 'info';
$allowedFeedbackTypes = ['success', 'error', 'warning', 'info'];
if (!in_array($feedbackType, $allowedFeedbackTypes, true)) {
    $feedbackType = 'info';
}

$feedbackClassMap = [
    'success' => 'border-emerald-400/30 bg-emerald-500/10 text-emerald-100',
    'error' => 'border-red-400/30 bg-red-500/10 text-red-100',
    'warning' => 'border-amber-400/30 bg-amber-500/10 text-amber-100',
    'info' => 'border-cyan-400/30 bg-cyan-500/10 text-cyan-100',
];

$itemTypeIconMap = [
    'video' => 'fa-circle-play',
    'article' => 'fa-newspaper',
    'document' => 'fa-file-lines',
    'quiz' => 'fa-circle-question',
    'live_session' => 'fa-tower-broadcast',
    'replay' => 'fa-repeat',
];

$itemTypeColorMap = [
    'video' => 'bg-blue-500/15 text-blue-200 border-blue-400/30',
    'article' => 'bg-cyan-500/15 text-cyan-200 border-cyan-400/30',
    'document' => 'bg-indigo-500/15 text-indigo-200 border-indigo-400/30',
    'quiz' => 'bg-fuchsia-500/15 text-fuchsia-200 border-fuchsia-400/30',
    'live_session' => 'bg-amber-500/15 text-amber-200 border-amber-400/30',
    'replay' => 'bg-emerald-500/15 text-emerald-200 border-emerald-400/30',
];

$progressLabelMap = [
    'not_started' => 'Chua bat dau',
    'in_progress' => 'Dang hoc',
    'completed' => 'Hoan thanh',
    'passed' => 'Da dat',
];

$progressColorMap = [
    'not_started' => 'bg-slate-700/60 text-slate-200 border-slate-500/40',
    'in_progress' => 'bg-amber-500/15 text-amber-200 border-amber-400/30',
    'completed' => 'bg-emerald-500/15 text-emerald-200 border-emerald-400/30',
    'passed' => 'bg-lime-500/15 text-lime-200 border-lime-400/30',
];

$sections = [];
$allItems = [];
$itemsById = [];
$progressSummary = [
    'required_total' => 0,
    'required_completed' => 0,
    'completed_total' => 0,
    'total_items' => 0,
    'progress_percent' => 0,
    'next_item' => null,
];
$selectedItemId = 0;
$currentItem = null;
$nextIncompleteItem = null;
$nextOrderedItem = null;
$relatedReplayItem = null;

$quizQuestions = [];
$quizAttempts = [];
$attemptsUsed = 0;
$attemptLimitReached = false;

if ($hasAccess) {
    $outline = learning_fetch_course_outline($conn, $courseId, $studentId);
    $sections = $outline['sections'];
    $allItems = $outline['items'];
    $itemsById = $outline['items_by_id'];

    $selectedItemId = filter_input(INPUT_GET, 'item_id', FILTER_VALIDATE_INT);
    if (!$selectedItemId || !isset($itemsById[$selectedItemId])) {
        $selectedItemId = !empty($allItems) ? (int) $allItems[0]['item_id'] : 0;
    }

    $currentItem = $selectedItemId > 0 && isset($itemsById[$selectedItemId])
        ? $itemsById[$selectedItemId]
        : null;

    if ($currentItem && (string) $currentItem['progress_status'] === 'not_started') {
        learning_upsert_progress(
            $conn,
            $studentId,
            $courseId,
            (int) $currentItem['item_id'],
            'in_progress',
            false
        );

        $outline = learning_fetch_course_outline($conn, $courseId, $studentId);
        $sections = $outline['sections'];
        $allItems = $outline['items'];
        $itemsById = $outline['items_by_id'];

        $currentItem = $selectedItemId > 0 && isset($itemsById[$selectedItemId])
            ? $itemsById[$selectedItemId]
            : null;
    }

    $progressSummary = learning_get_progress_summary_from_items($allItems);
    learning_sync_enrollment_progress($conn, $studentId, $courseId, (float) $progressSummary['progress_percent']);

    $nextIncompleteItem = $progressSummary['next_item'];

    if ($currentItem) {
        $foundCurrent = false;
        foreach ($allItems as $item) {
            if ($foundCurrent) {
                $nextOrderedItem = $item;
                break;
            }

            if ((int) $item['item_id'] === (int) $currentItem['item_id']) {
                $foundCurrent = true;
            }
        }

        if ((string) $currentItem['item_type'] === 'live_session' && (int) $currentItem['live_session_id'] > 0) {
            foreach ($allItems as $candidate) {
                if (
                    (string) $candidate['item_type'] === 'replay'
                    && (int) $candidate['replay_live_session_id'] === (int) $currentItem['live_session_id']
                ) {
                    $relatedReplayItem = $candidate;
                    break;
                }
            }
        }

        if ((string) $currentItem['item_type'] === 'quiz' && (int) $currentItem['quiz_id'] > 0) {
            $quizId = (int) $currentItem['quiz_id'];

            $attemptCountStmt = $conn->prepare(
                'SELECT COUNT(*) AS total_attempts '
                . 'FROM quiz_attempt '
                . 'WHERE quiz_id = ? AND student_id = ? AND course_id = ? AND item_id = ? AND is_deleted = 0'
            );
            if ($attemptCountStmt) {
                $attemptCountStmt->bind_param('iiii', $quizId, $studentId, $courseId, $selectedItemId);
                $attemptCountStmt->execute();
                $attemptCountRes = $attemptCountStmt->get_result();
                $attemptCountRow = $attemptCountRes ? $attemptCountRes->fetch_assoc() : null;
                $attemptsUsed = isset($attemptCountRow['total_attempts']) ? (int) $attemptCountRow['total_attempts'] : 0;
                $attemptCountStmt->close();
            }

            $historyStmt = $conn->prepare(
                'SELECT attempt_number, score, max_score, passed, submitted_at '
                . 'FROM quiz_attempt '
                . 'WHERE quiz_id = ? AND student_id = ? AND course_id = ? AND item_id = ? AND is_deleted = 0 '
                . 'ORDER BY attempt_number DESC LIMIT 5'
            );
            if ($historyStmt) {
                $historyStmt->bind_param('iiii', $quizId, $studentId, $courseId, $selectedItemId);
                $historyStmt->execute();
                $historyRes = $historyStmt->get_result();
                while ($historyRes && ($attemptRow = $historyRes->fetch_assoc())) {
                    $quizAttempts[] = $attemptRow;
                }
                $historyStmt->close();
            }

            $questionStmt = $conn->prepare(
                'SELECT '
                . 'qq.question_id, qq.question_text, qq.points, '
                . 'qa.answer_id, qa.answer_text '
                . 'FROM quiz_question qq '
                . 'INNER JOIN quiz_answer qa '
                . 'ON qa.question_id = qq.question_id AND qa.is_deleted = 0 '
                . 'WHERE qq.quiz_id = ? AND qq.is_deleted = 0 '
                . 'ORDER BY qq.question_position ASC, qa.answer_position ASC'
            );
            if ($questionStmt) {
                $questionStmt->bind_param('i', $quizId);
                $questionStmt->execute();
                $questionRes = $questionStmt->get_result();
                $questionMap = [];

                while ($questionRes && ($questionRow = $questionRes->fetch_assoc())) {
                    $questionId = (int) ($questionRow['question_id'] ?? 0);
                    if (!isset($questionMap[$questionId])) {
                        $questionMap[$questionId] = [
                            'question_id' => $questionId,
                            'question_text' => (string) ($questionRow['question_text'] ?? ''),
                            'points' => (float) ($questionRow['points'] ?? 0),
                            'answers' => [],
                        ];
                    }

                    $questionMap[$questionId]['answers'][] = [
                        'answer_id' => (int) ($questionRow['answer_id'] ?? 0),
                        'answer_text' => (string) ($questionRow['answer_text'] ?? ''),
                    ];
                }

                $quizQuestions = array_values($questionMap);
                $questionStmt->close();
            }

            $maxAttempts = (int) ($currentItem['max_attempts'] ?? 0);
            $attemptLimitReached = $maxAttempts > 0 && $attemptsUsed >= $maxAttempts;
        }
    }
}

$courseName = (string) ($course['course_name'] ?? 'Khoa hoc');
$studentName = (string) ($studentProfile['stu_name'] ?? 'Hoc vien');
$studentImg = '../' . ltrim(str_replace('../', '', (string) ($studentProfile['stu_img'] ?? '')), '/');

$courseProgressPercent = max(0, min(100, (float) ($progressSummary['progress_percent'] ?? 0)));
$courseProgressPercentInt = (int) round($courseProgressPercent);

$currentItemType = $currentItem ? (string) ($currentItem['item_type'] ?? '') : '';
$currentProgressStatus = $currentItem ? (string) ($currentItem['progress_status'] ?? 'not_started') : 'not_started';
$currentIsCompleted = $currentItem
    ? learning_is_item_completed($currentItemType, $currentProgressStatus)
    : false;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($courseName, ENT_QUOTES, 'UTF-8'); ?> - CTU E-Learning</title>
    <link rel="stylesheet" href="../css/tailwind.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="../js/all.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at 0% 0%, #1e293b, #020617 60%);
        }

        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.6) transparent;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.5);
            border-radius: 9999px;
        }

        /* Mobile sidebar overlay */
        #sidebarOverlay {
            transition: opacity 0.3s ease;
        }
        #sidebarOverlay.hidden { display: none; }

        #mobileSidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        #mobileSidebar.open {
            transform: translateX(0);
        }
    </style>
</head>
<body class="min-h-screen text-slate-100">
<header class="sticky top-0 z-20 border-b border-white/10 bg-slate-950/80 backdrop-blur-xl">
    <div class="mx-auto flex max-w-[1600px] items-center justify-between gap-3 px-4 py-3 md:px-6">
        <div class="flex min-w-0 items-center gap-3">
            <a
                href="myCourse.php"
                class="inline-flex items-center gap-2 rounded-lg border border-white/15 px-2 sm:px-3 py-2 text-xs font-semibold text-white/90 transition hover:border-white/30 hover:text-white"
            >
                <i class="fas fa-arrow-left"></i>
                <span class="hidden sm:inline">Khoa hoc cua toi</span>
            </a>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-white md:text-base"><?php echo htmlspecialchars($courseName, ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="truncate text-xs text-white/60"><?php echo htmlspecialchars((string) ($course['course_author'] ?? 'Giang vien'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            <!-- Mobile sidebar toggle -->
            <button type="button" id="toggleSidebarBtn" class="lg:hidden inline-flex items-center gap-2 rounded-lg border border-white/15 px-3 py-2 text-xs font-semibold text-white/90 transition hover:border-white/30 hover:text-white bg-transparent cursor-pointer">
                <i class="fas fa-list"></i>
                <span class="hidden sm:inline">Mục lục</span>
            </button>
            <img
                src="<?php echo htmlspecialchars($studentImg ?: '../image/stu/default_avatar.png', ENT_QUOTES, 'UTF-8'); ?>"
                onerror="this.onerror=null;this.src='../image/stu/default_avatar.png';"
                alt="Student avatar"
                class="h-8 w-8 sm:h-9 sm:w-9 rounded-full border border-white/20 object-cover"
            >
            <span class="hidden text-sm font-medium text-white/80 sm:block"><?php echo htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    </div>
</header>

<div class="mx-auto max-w-[1600px] px-4 py-4 md:px-6 md:py-6">
    <?php if ($feedback !== ''): ?>
        <div class="mb-4 rounded-xl border px-4 py-3 text-sm <?php echo $feedbackClassMap[$feedbackType]; ?>">
            <?php echo htmlspecialchars($feedback, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (!$hasAccess): ?>
        <div class="mx-auto mt-8 max-w-2xl rounded-2xl border border-red-400/30 bg-red-500/10 p-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-500/20 text-red-200">
                <i class="fas fa-lock"></i>
            </div>
            <h1 class="text-xl font-bold text-white">Khong the truy cap khoa hoc</h1>
            <p class="mt-2 text-sm text-red-100/90">
                Tai khoan cua ban chua co enrollment hop le cho khoa hoc nay. Vui long quay lai trang Khoa hoc cua toi.
            </p>
            <a
                href="myCourse.php"
                class="mt-6 inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-bold text-slate-900 transition hover:bg-slate-100"
            >
                <i class="fas fa-book-reader"></i>
                <span>Ve khoa hoc cua toi</span>
            </a>
        </div>
    <?php else: ?>
        <section class="mb-4 grid gap-3 md:grid-cols-4">
            <div class="rounded-2xl border border-white/10 bg-slate-900/80 p-4">
                <p class="text-xs uppercase tracking-wide text-white/60">Tien do khoa hoc</p>
                <p class="mt-2 text-2xl font-black text-white"><?php echo $courseProgressPercentInt; ?>%</p>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/10">
                    <div class="h-full rounded-full bg-emerald-400" style="width: <?php echo $courseProgressPercentInt; ?>%;"></div>
                </div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900/80 p-4">
                <p class="text-xs uppercase tracking-wide text-white/60">Muc bat buoc</p>
                <p class="mt-2 text-2xl font-black text-white">
                    <?php echo (int) ($progressSummary['required_completed'] ?? 0); ?>/<?php echo (int) ($progressSummary['required_total'] ?? 0); ?>
                </p>
                <p class="mt-2 text-xs text-white/60">Tinh theo quy tac progress</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900/80 p-4">
                <p class="text-xs uppercase tracking-wide text-white/60">Tong muc da xong</p>
                <p class="mt-2 text-2xl font-black text-white">
                    <?php echo (int) ($progressSummary['completed_total'] ?? 0); ?>/<?php echo (int) ($progressSummary['total_items'] ?? 0); ?>
                </p>
                <p class="mt-2 text-xs text-white/60">Bao gom completed va passed</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900/80 p-4">
                <p class="text-xs uppercase tracking-wide text-white/60">Hanh dong tiep theo</p>
                <?php if ($nextIncompleteItem): ?>
                    <a
                        href="watchcourse.php?course_id=<?php echo $courseId; ?>&item_id=<?php echo (int) $nextIncompleteItem['item_id']; ?>"
                        class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-3 py-2 text-sm font-bold text-white transition hover:bg-primary/90"
                    >
                        <i class="fas fa-forward"></i>
                        <span>Tiep tuc hoc</span>
                    </a>
                    <p class="mt-2 line-clamp-2 text-xs text-white/70">
                        <?php echo htmlspecialchars((string) ($nextIncompleteItem['item_title'] ?? 'Muc tiep theo'), ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                <?php else: ?>
                    <p class="mt-2 text-sm font-semibold text-emerald-200">Ban da hoan thanh toan bo muc bat buoc.</p>
                <?php endif; ?>
            </div>
        </section>

        <?php if (empty($allItems)): ?>
            <section class="rounded-2xl border border-white/10 bg-slate-900/80 p-10 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-white/10 text-white/70">
                    <i class="fas fa-inbox text-2xl"></i>
                </div>
                <h2 class="text-xl font-bold text-white">Khoa hoc chua co noi dung</h2>
                <p class="mt-2 text-sm text-white/70">
                    Chua co section hoac learning item nao duoc xuat ban cho khoa hoc nay.
                </p>
            </section>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-[360px,1fr]">
                <!-- Mobile sidebar overlay -->
                <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden" onclick="closeSidebar()"></div>

                <!-- Sidebar (desktop: inline, mobile: overlay) -->
                <aside id="mobileSidebar" class="fixed inset-y-0 left-0 z-50 w-[320px] flex flex-col overflow-hidden bg-slate-900 lg:static lg:z-auto lg:w-auto lg:transform-none lg:rounded-2xl lg:border lg:border-white/10 lg:bg-slate-900/80 max-h-screen lg:max-h-[calc(100vh-250px)]">
                    <div class="border-b border-white/10 px-4 py-3 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-white">Lo trinh hoc theo section</h2>
                            <p class="mt-1 text-xs text-white/50"><?php echo count($allItems); ?> learning item</p>
                        </div>
                        <button type="button" class="lg:hidden text-white/60 hover:text-white p-1 bg-transparent border-0 cursor-pointer" onclick="closeSidebar()">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>
                    <div class="custom-scrollbar flex-1 overflow-y-auto px-2 py-2">
                        <?php foreach ($sections as $section): ?>
                            <div class="mb-3 rounded-xl border border-white/5 bg-slate-950/40">
                                <div class="flex items-center justify-between border-b border-white/5 px-3 py-2">
                                    <p class="truncate pr-3 text-xs font-semibold uppercase tracking-wide text-white/70">
                                        <?php echo htmlspecialchars((string) ($section['section_title'] ?? 'Section'), ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                    <span class="text-[11px] text-white/40"><?php echo count($section['items']); ?> muc</span>
                                </div>
                                <ul class="space-y-1 px-1 py-2">
                                    <?php foreach ($section['items'] as $item): ?>
                                        <?php
                                        $itemId = (int) ($item['item_id'] ?? 0);
                                        $itemType = (string) ($item['item_type'] ?? '');
                                        $itemStatus = (string) ($item['progress_status'] ?? 'not_started');
                                        $isActiveItem = $currentItem && (int) $currentItem['item_id'] === $itemId;
                                        $typeIcon = $itemTypeIconMap[$itemType] ?? 'fa-circle-exclamation';
                                        $typeBadgeClass = $itemTypeColorMap[$itemType] ?? 'bg-rose-500/15 text-rose-200 border-rose-400/30';
                                        $statusBadgeClass = $progressColorMap[$itemStatus] ?? 'bg-slate-700/60 text-slate-200 border-slate-500/40';
                                        ?>
                                        <li>
                                            <a
                                                href="watchcourse.php?course_id=<?php echo $courseId; ?>&item_id=<?php echo $itemId; ?>"
                                                class="group flex items-start gap-3 rounded-lg border px-2.5 py-2 transition <?php echo $isActiveItem ? 'border-emerald-400/40 bg-emerald-500/10' : 'border-transparent hover:border-white/15 hover:bg-white/5'; ?>"
                                            >
                                                <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border <?php echo $typeBadgeClass; ?>">
                                                    <i class="fas <?php echo $typeIcon; ?> text-xs"></i>
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block truncate text-sm font-semibold text-white/90 group-hover:text-white">
                                                        <?php echo htmlspecialchars((string) ($item['item_title'] ?? 'Learning item'), ENT_QUOTES, 'UTF-8'); ?>
                                                    </span>
                                                    <span class="mt-1 flex flex-wrap gap-1.5 text-[11px]">
                                                        <span class="rounded border px-1.5 py-0.5 <?php echo $typeBadgeClass; ?>">
                                                            <?php echo htmlspecialchars(learning_get_item_type_label($itemType), ENT_QUOTES, 'UTF-8'); ?>
                                                        </span>
                                                        <?php if ((int) ($item['is_required'] ?? 0) === 1): ?>
                                                            <span class="rounded border border-white/20 bg-white/10 px-1.5 py-0.5 text-white/80">Bat buoc</span>
                                                        <?php endif; ?>
                                                        <span class="rounded border px-1.5 py-0.5 <?php echo $statusBadgeClass; ?>">
                                                            <?php echo htmlspecialchars($progressLabelMap[$itemStatus] ?? 'Chua xac dinh', ENT_QUOTES, 'UTF-8'); ?>
                                                        </span>
                                                    </span>
                                                </span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </aside>

                <main class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/80">
                    <?php if ($currentItem): ?>
                        <?php
                        $currentTypeBadgeClass = $itemTypeColorMap[$currentItemType] ?? 'bg-rose-500/15 text-rose-200 border-rose-400/30';
                        $currentStatusBadgeClass = $progressColorMap[$currentProgressStatus] ?? 'bg-slate-700/60 text-slate-200 border-slate-500/40';
                        ?>
                        <div class="border-b border-white/10 px-5 py-4 md:px-6">
                            <div class="mb-2 flex flex-wrap items-center gap-2 text-xs">
                                <span class="rounded-full border px-2 py-1 <?php echo $currentTypeBadgeClass; ?>">
                                    <?php echo htmlspecialchars(learning_get_item_type_label($currentItemType), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <?php if ((int) ($currentItem['is_required'] ?? 0) === 1): ?>
                                    <span class="rounded-full border border-white/20 bg-white/10 px-2 py-1 text-white/80">Muc bat buoc</span>
                                <?php endif; ?>
                                <span class="rounded-full border px-2 py-1 <?php echo $currentStatusBadgeClass; ?>">
                                    <?php echo htmlspecialchars($progressLabelMap[$currentProgressStatus] ?? 'Chua xac dinh', ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                            <h1 class="text-xl font-black text-white md:text-2xl">
                                <?php echo htmlspecialchars((string) ($currentItem['item_title'] ?? 'Learning item'), ENT_QUOTES, 'UTF-8'); ?>
                            </h1>
                            <p class="mt-1 text-xs text-white/60">
                                <?php echo htmlspecialchars((string) ($currentItem['section_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>

                        <div class="custom-scrollbar max-h-[calc(100vh-310px)] overflow-y-auto px-5 py-5 md:px-6 md:py-6">
                            <?php if ($currentItemType === 'video'): ?>
                                <?php $videoUrl = trim((string) ($currentItem['video_url'] ?? '')); ?>
                                <?php if ($videoUrl === ''): ?>
                                    <div class="rounded-xl border border-amber-400/30 bg-amber-500/10 p-5 text-amber-100">
                                        Video URL chua duoc cau hinh cho muc nay.
                                    </div>
                                <?php else: ?>
                                    <?php if (learning_is_youtube_url($videoUrl)): ?>
                                        <?php $embedUrl = learning_get_youtube_embed_url($videoUrl); ?>
                                        <?php if ($embedUrl): ?>
                                            <div class="overflow-hidden rounded-xl border border-white/10 bg-black">
                                                <iframe
                                                    class="aspect-video w-full"
                                                    src="<?php echo htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                                    allowfullscreen
                                                ></iframe>
                                            </div>
                                        <?php else: ?>
                                            <div class="rounded-xl border border-amber-400/30 bg-amber-500/10 p-5 text-amber-100">
                                                Khong the chuyen doi YouTube URL sang embed URL.
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="overflow-hidden rounded-xl border border-white/10 bg-black">
                                            <video class="aspect-video w-full" controls preload="metadata">
                                                <source src="<?php echo htmlspecialchars($videoUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                                Trinh duyet cua ban khong ho tro video HTML5.
                                            </video>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-4 flex flex-wrap gap-3">
                                        <?php if (!$currentIsCompleted): ?>
                                            <form method="post" action="markProgress.php" class="m-0">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                                                <input type="hidden" name="item_id" value="<?php echo (int) $currentItem['item_id']; ?>">
                                                <input type="hidden" name="action" value="complete">
                                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-4 py-2 text-sm font-bold text-white transition hover:bg-emerald-600">
                                                    <i class="fas fa-check"></i>
                                                    <span>Danh dau da hoc xong</span>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-2 rounded-lg border border-emerald-400/40 bg-emerald-500/10 px-4 py-2 text-sm font-semibold text-emerald-200">
                                                <i class="fas fa-circle-check"></i>
                                                <span>Muc nay da hoan thanh</span>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($nextOrderedItem): ?>
                                            <a
                                                href="watchcourse.php?course_id=<?php echo $courseId; ?>&item_id=<?php echo (int) $nextOrderedItem['item_id']; ?>"
                                                class="inline-flex items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20"
                                            >
                                                <span>Bai tiep theo</span>
                                                <i class="fas fa-arrow-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                            <?php elseif ($currentItemType === 'article'): ?>
                                <?php $articleContent = trim((string) ($currentItem['article_content'] ?? '')); ?>
                                <?php if ($articleContent === ''): ?>
                                    <div class="rounded-xl border border-amber-400/30 bg-amber-500/10 p-5 text-amber-100">
                                        Noi dung bai viet chua duoc cap nhat.
                                    </div>
                                <?php else: ?>
                                    <article class="rounded-xl border border-white/10 bg-slate-950/40 p-6 leading-relaxed text-slate-100">
                                        <?php echo $articleContent; ?>
                                    </article>

                                    <div class="mt-4 flex flex-wrap gap-3">
                                        <?php if (!$currentIsCompleted): ?>
                                            <form method="post" action="markProgress.php" class="m-0">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                                                <input type="hidden" name="item_id" value="<?php echo (int) $currentItem['item_id']; ?>">
                                                <input type="hidden" name="action" value="complete">
                                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-4 py-2 text-sm font-bold text-white transition hover:bg-emerald-600">
                                                    <i class="fas fa-check"></i>
                                                    <span>Danh dau da doc xong</span>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-2 rounded-lg border border-emerald-400/40 bg-emerald-500/10 px-4 py-2 text-sm font-semibold text-emerald-200">
                                                <i class="fas fa-circle-check"></i>
                                                <span>Bai viet da hoan thanh</span>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($nextOrderedItem): ?>
                                            <a
                                                href="watchcourse.php?course_id=<?php echo $courseId; ?>&item_id=<?php echo (int) $nextOrderedItem['item_id']; ?>"
                                                class="inline-flex items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20"
                                            >
                                                <span>Bai tiep theo</span>
                                                <i class="fas fa-arrow-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                            <?php elseif ($currentItemType === 'document'): ?>
                                <?php $documentUrl = trim((string) ($currentItem['document_url'] ?? '')); ?>
                                <?php if ($documentUrl === ''): ?>
                                    <div class="rounded-xl border border-amber-400/30 bg-amber-500/10 p-5 text-amber-100">
                                        Tai lieu chua co URL, vui long thu lai sau.
                                    </div>
                                <?php else: ?>
                                    <div class="rounded-xl border border-white/10 bg-slate-950/40 p-6">
                                        <p class="text-sm text-white/80">Mo tai lieu hoac tai xuong de doc offline.</p>
                                        <div class="mt-4 flex flex-wrap gap-3">
                                            <a
                                                href="<?php echo htmlspecialchars($documentUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white transition hover:bg-primary/90"
                                            >
                                                <i class="fas fa-eye"></i>
                                                <span>Xem tai lieu</span>
                                            </a>
                                            <a
                                                href="<?php echo htmlspecialchars($documentUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                download
                                                class="inline-flex items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20"
                                            >
                                                <i class="fas fa-download"></i>
                                                <span>Tai xuong</span>
                                            </a>
                                        </div>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-3">
                                        <?php if (!$currentIsCompleted): ?>
                                            <form method="post" action="markProgress.php" class="m-0">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                                                <input type="hidden" name="item_id" value="<?php echo (int) $currentItem['item_id']; ?>">
                                                <input type="hidden" name="action" value="complete">
                                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-4 py-2 text-sm font-bold text-white transition hover:bg-emerald-600">
                                                    <i class="fas fa-check"></i>
                                                    <span>Danh dau da hoan thanh</span>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-2 rounded-lg border border-emerald-400/40 bg-emerald-500/10 px-4 py-2 text-sm font-semibold text-emerald-200">
                                                <i class="fas fa-circle-check"></i>
                                                <span>Tai lieu da hoan thanh</span>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($nextOrderedItem): ?>
                                            <a
                                                href="watchcourse.php?course_id=<?php echo $courseId; ?>&item_id=<?php echo (int) $nextOrderedItem['item_id']; ?>"
                                                class="inline-flex items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20"
                                            >
                                                <span>Bai tiep theo</span>
                                                <i class="fas fa-arrow-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                            <?php elseif ($currentItemType === 'quiz'): ?>
                                <?php
                                $passScore = (float) ($currentItem['pass_score'] ?? 70);
                                $maxAttempts = (int) ($currentItem['max_attempts'] ?? 0);
                                $attemptsLeft = $maxAttempts > 0 ? max(0, $maxAttempts - $attemptsUsed) : null;
                                ?>
                                <?php if ((int) ($currentItem['quiz_id'] ?? 0) <= 0): ?>
                                    <div class="rounded-xl border border-red-400/30 bg-red-500/10 p-5 text-red-100">
                                        Quiz chua duoc lien ket dung voi learning item nay.
                                    </div>
                                <?php elseif (empty($quizQuestions)): ?>
                                    <div class="rounded-xl border border-amber-400/30 bg-amber-500/10 p-5 text-amber-100">
                                        Quiz chua co cau hoi hoac chua xuat ban cau tra loi.
                                    </div>
                                <?php else: ?>
                                    <div class="grid gap-3 md:grid-cols-3">
                                        <div class="rounded-xl border border-white/10 bg-slate-950/40 p-4">
                                            <p class="text-xs uppercase tracking-wide text-white/50">Diem dat</p>
                                            <p class="mt-1 text-xl font-black text-white"><?php echo rtrim(rtrim(number_format($passScore, 2, '.', ''), '0'), '.'); ?>%</p>
                                        </div>
                                        <div class="rounded-xl border border-white/10 bg-slate-950/40 p-4">
                                            <p class="text-xs uppercase tracking-wide text-white/50">So lan da nop</p>
                                            <p class="mt-1 text-xl font-black text-white"><?php echo $attemptsUsed; ?></p>
                                        </div>
                                        <div class="rounded-xl border border-white/10 bg-slate-950/40 p-4">
                                            <p class="text-xs uppercase tracking-wide text-white/50">Luot con lai</p>
                                            <p class="mt-1 text-xl font-black text-white">
                                                <?php echo $attemptsLeft === null ? 'Khong gioi han' : (string) $attemptsLeft; ?>
                                            </p>
                                        </div>
                                    </div>

                                    <?php if ($attemptLimitReached): ?>
                                        <div class="mt-4 rounded-xl border border-amber-400/30 bg-amber-500/10 p-4 text-sm text-amber-100">
                                            Ban da dung het so luot lam quiz cho muc nay.
                                        </div>
                                    <?php endif; ?>

                                    <form method="post" action="quizAttempt.php" class="mt-4 space-y-4">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                                        <input type="hidden" name="item_id" value="<?php echo (int) $currentItem['item_id']; ?>">
                                        <input type="hidden" name="quiz_id" value="<?php echo (int) $currentItem['quiz_id']; ?>">

                                        <?php foreach ($quizQuestions as $index => $question): ?>
                                            <fieldset class="rounded-xl border border-white/10 bg-slate-950/40 p-4">
                                                <legend class="px-2 text-sm font-bold text-white">
                                                    Cau <?php echo $index + 1; ?> (<?php echo rtrim(rtrim(number_format((float) ($question['points'] ?? 0), 2, '.', ''), '0'), '.'); ?> diem)
                                                </legend>
                                                <p class="mb-3 text-sm text-white/90"><?php echo htmlspecialchars((string) ($question['question_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                                <div class="space-y-2">
                                                    <?php foreach ($question['answers'] as $answer): ?>
                                                        <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2 text-sm text-white/85 transition hover:border-white/25 hover:bg-white/[0.06]">
                                                            <input
                                                                type="radio"
                                                                class="mt-0.5"
                                                                name="answers[<?php echo (int) $question['question_id']; ?>]"
                                                                value="<?php echo (int) $answer['answer_id']; ?>"
                                                                <?php echo $attemptLimitReached ? 'disabled' : ''; ?>
                                                            >
                                                            <span><?php echo htmlspecialchars((string) ($answer['answer_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </fieldset>
                                        <?php endforeach; ?>

                                        <div class="flex flex-wrap items-center gap-3">
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                                                <?php echo $attemptLimitReached ? 'disabled' : ''; ?>
                                            >
                                                <i class="fas fa-paper-plane"></i>
                                                <span>Nop bai quiz</span>
                                            </button>

                                            <?php if ($currentProgressStatus === 'passed'): ?>
                                                <span class="inline-flex items-center gap-2 rounded-lg border border-lime-400/40 bg-lime-500/10 px-4 py-2 text-sm font-semibold text-lime-200">
                                                    <i class="fas fa-award"></i>
                                                    <span>Quiz da dat yeu cau</span>
                                                </span>
                                            <?php endif; ?>

                                            <?php if ($nextOrderedItem && $currentProgressStatus === 'passed'): ?>
                                                <a
                                                    href="watchcourse.php?course_id=<?php echo $courseId; ?>&item_id=<?php echo (int) $nextOrderedItem['item_id']; ?>"
                                                    class="inline-flex items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20"
                                                >
                                                    <span>Bai tiep theo</span>
                                                    <i class="fas fa-arrow-right"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                <?php endif; ?>

                                <div class="mt-5 rounded-xl border border-white/10 bg-slate-950/40 p-4">
                                    <h3 class="text-sm font-bold text-white">Lich su nop gan nhat</h3>
                                    <?php if (!empty($quizAttempts)): ?>
                                        <div class="mt-3 overflow-x-auto">
                                            <table class="min-w-full text-left text-sm text-white/85">
                                                <thead>
                                                <tr class="text-xs uppercase tracking-wide text-white/50">
                                                    <th class="px-2 py-2">Lan</th>
                                                    <th class="px-2 py-2">Diem</th>
                                                    <th class="px-2 py-2">Ket qua</th>
                                                    <th class="px-2 py-2">Thoi gian nop</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach ($quizAttempts as $attempt): ?>
                                                    <tr class="border-t border-white/10">
                                                        <td class="px-2 py-2">#<?php echo (int) ($attempt['attempt_number'] ?? 0); ?></td>
                                                        <td class="px-2 py-2">
                                                            <?php echo rtrim(rtrim(number_format((float) ($attempt['score'] ?? 0), 2, '.', ''), '0'), '.'); ?>
                                                            /<?php echo rtrim(rtrim(number_format((float) ($attempt['max_score'] ?? 100), 2, '.', ''), '0'), '.'); ?>
                                                        </td>
                                                        <td class="px-2 py-2">
                                                            <?php if ((int) ($attempt['passed'] ?? 0) === 1): ?>
                                                                <span class="rounded border border-emerald-400/30 bg-emerald-500/10 px-2 py-1 text-xs text-emerald-200">Passed</span>
                                                            <?php else: ?>
                                                                <span class="rounded border border-amber-400/30 bg-amber-500/10 px-2 py-1 text-xs text-amber-200">Chua dat</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="px-2 py-2 text-xs text-white/60">
                                                            <?php echo htmlspecialchars((string) ($attempt['submitted_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="mt-2 text-xs text-white/60">Chua co luot nop quiz nao.</p>
                                    <?php endif; ?>
                                </div>

                            <?php elseif ($currentItemType === 'live_session'): ?>
                                <?php
                                $sessionStatus = (string) ($currentItem['session_status'] ?? 'scheduled');
                                $sessionLabelMap = [
                                    'scheduled' => 'Da len lich',
                                    'live' => 'Dang dien ra',
                                    'ended' => 'Da ket thuc',
                                    'replay_available' => 'Co replay',
                                    'cancelled' => 'Da huy',
                                ];
                                $sessionStatusClassMap = [
                                    'scheduled' => 'border-cyan-400/30 bg-cyan-500/10 text-cyan-200',
                                    'live' => 'border-red-400/30 bg-red-500/10 text-red-200',
                                    'ended' => 'border-slate-500/40 bg-slate-500/20 text-slate-200',
                                    'replay_available' => 'border-emerald-400/30 bg-emerald-500/10 text-emerald-200',
                                    'cancelled' => 'border-rose-400/30 bg-rose-500/10 text-rose-200',
                                ];
                                $sessionStatusClass = $sessionStatusClassMap[$sessionStatus] ?? 'border-slate-500/40 bg-slate-500/20 text-slate-200';
                                $sessionStatusLabel = $sessionLabelMap[$sessionStatus] ?? 'Khong xac dinh';
                                $sessionTitle = (string) ($currentItem['session_title'] ?? $currentItem['item_title']);
                                $sessionDesc = trim((string) ($currentItem['session_description'] ?? ''));
                                $joinUrl = trim((string) ($currentItem['join_url'] ?? ''));
                                $startAt = trim((string) ($currentItem['start_at'] ?? ''));
                                $endAt = trim((string) ($currentItem['end_at'] ?? ''));
                                $platformName = trim((string) ($currentItem['platform_name'] ?? ''));
                                $showJoinButton = in_array($sessionStatus, ['scheduled', 'live', 'replay_available'], true);
                                ?>
                                <div class="rounded-xl border border-white/10 bg-slate-950/40 p-6">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full border px-2 py-1 text-xs <?php echo $sessionStatusClass; ?>">
                                            <?php echo htmlspecialchars($sessionStatusLabel, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <?php if ($platformName !== ''): ?>
                                            <span class="rounded-full border border-white/20 bg-white/10 px-2 py-1 text-xs text-white/80">
                                                <?php echo htmlspecialchars($platformName, ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <h3 class="mt-3 text-lg font-bold text-white"><?php echo htmlspecialchars($sessionTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p class="mt-2 text-sm text-white/75">
                                        <?php echo $sessionDesc !== '' ? htmlspecialchars($sessionDesc, ENT_QUOTES, 'UTF-8') : 'Buoi hoc truc tiep danh cho hoc vien da enrollment.'; ?>
                                    </p>

                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-lg border border-white/10 bg-white/[0.03] p-3">
                                            <p class="text-xs uppercase tracking-wide text-white/50">Bat dau</p>
                                            <p class="mt-1 text-sm font-semibold text-white/90"><?php echo htmlspecialchars($startAt !== '' ? $startAt : 'Chua cap nhat', ENT_QUOTES, 'UTF-8'); ?></p>
                                        </div>
                                        <div class="rounded-lg border border-white/10 bg-white/[0.03] p-3">
                                            <p class="text-xs uppercase tracking-wide text-white/50">Ket thuc</p>
                                            <p class="mt-1 text-sm font-semibold text-white/90"><?php echo htmlspecialchars($endAt !== '' ? $endAt : 'Chua cap nhat', ENT_QUOTES, 'UTF-8'); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-3">
                                    <?php if ($showJoinButton && $joinUrl !== ''): ?>
                                        <a
                                            href="<?php echo htmlspecialchars($joinUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-2 rounded-lg <?php echo $sessionStatus === 'live' ? 'bg-red-500 hover:bg-red-600' : 'bg-primary hover:bg-primary/90'; ?> px-4 py-2 text-sm font-bold text-white transition"
                                        >
                                            <i class="fas fa-video"></i>
                                            <span><?php echo $sessionStatus === 'live' ? 'Tham gia ngay' : 'Mo phong hoc'; ?></span>
                                        </a>
                                    <?php elseif ($showJoinButton): ?>
                                        <span class="inline-flex items-center gap-2 rounded-lg border border-amber-400/30 bg-amber-500/10 px-4 py-2 text-sm font-semibold text-amber-200">
                                            <i class="fas fa-triangle-exclamation"></i>
                                            <span>Buoi live chua co join URL</span>
                                        </span>
                                    <?php elseif ($sessionStatus === 'ended'): ?>
                                        <span class="inline-flex items-center gap-2 rounded-lg border border-slate-400/30 bg-slate-500/20 px-4 py-2 text-sm font-semibold text-slate-200">
                                            <i class="fas fa-hourglass-end"></i>
                                            <span>Buoi live da ket thuc</span>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!$currentIsCompleted && $showJoinButton): ?>
                                        <form method="post" action="markProgress.php" class="m-0">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                                            <input type="hidden" name="item_id" value="<?php echo (int) $currentItem['item_id']; ?>">
                                            <input type="hidden" name="action" value="join_live">
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-emerald-400/40 bg-emerald-500/10 px-4 py-2 text-sm font-semibold text-emerald-200 transition hover:bg-emerald-500/20">
                                                <i class="fas fa-user-check"></i>
                                                <span>Danh dau da tham gia</span>
                                            </button>
                                        </form>
                                    <?php elseif ($currentIsCompleted): ?>
                                        <span class="inline-flex items-center gap-2 rounded-lg border border-emerald-400/40 bg-emerald-500/10 px-4 py-2 text-sm font-semibold text-emerald-200">
                                            <i class="fas fa-circle-check"></i>
                                            <span>Buoi live da duoc tinh hoan thanh</span>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($sessionStatus === 'replay_available' && $relatedReplayItem): ?>
                                        <a
                                            href="watchcourse.php?course_id=<?php echo $courseId; ?>&item_id=<?php echo (int) $relatedReplayItem['item_id']; ?>"
                                            class="inline-flex items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20"
                                        >
                                            <i class="fas fa-repeat"></i>
                                            <span>Xem replay lien quan</span>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($nextOrderedItem): ?>
                                        <a
                                            href="watchcourse.php?course_id=<?php echo $courseId; ?>&item_id=<?php echo (int) $nextOrderedItem['item_id']; ?>"
                                            class="inline-flex items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20"
                                        >
                                            <span>Bai tiep theo</span>
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>

                            <?php elseif ($currentItemType === 'replay'): ?>
                                <?php $recordingUrl = trim((string) ($currentItem['recording_url'] ?? '')); ?>
                                <?php if ($recordingUrl === ''): ?>
                                    <div class="rounded-xl border border-amber-400/30 bg-amber-500/10 p-5 text-amber-100">
                                        Replay chua co recording URL kha dung.
                                    </div>
                                <?php else: ?>
                                    <?php if (learning_is_youtube_url($recordingUrl)): ?>
                                        <?php $replayEmbedUrl = learning_get_youtube_embed_url($recordingUrl); ?>
                                        <?php if ($replayEmbedUrl): ?>
                                            <div class="overflow-hidden rounded-xl border border-white/10 bg-black">
                                                <iframe
                                                    class="aspect-video w-full"
                                                    src="<?php echo htmlspecialchars($replayEmbedUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                                    allowfullscreen
                                                ></iframe>
                                            </div>
                                        <?php else: ?>
                                            <div class="rounded-xl border border-amber-400/30 bg-amber-500/10 p-5 text-amber-100">
                                                Khong the hien thi replay dang embed.
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="rounded-xl border border-white/10 bg-slate-950/40 p-6">
                                            <p class="text-sm text-white/80">Recording URL khong phai YouTube, mo truc tiep tai lien ket ben duoi:</p>
                                            <a
                                                href="<?php echo htmlspecialchars($recordingUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="mt-3 inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white transition hover:bg-primary/90"
                                            >
                                                <i class="fas fa-up-right-from-square"></i>
                                                <span>Mo replay</span>
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-4 flex flex-wrap gap-3">
                                        <?php if (!$currentIsCompleted): ?>
                                            <form method="post" action="markProgress.php" class="m-0">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                                                <input type="hidden" name="item_id" value="<?php echo (int) $currentItem['item_id']; ?>">
                                                <input type="hidden" name="action" value="complete">
                                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-4 py-2 text-sm font-bold text-white transition hover:bg-emerald-600">
                                                    <i class="fas fa-check"></i>
                                                    <span>Danh dau da xem replay</span>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-2 rounded-lg border border-emerald-400/40 bg-emerald-500/10 px-4 py-2 text-sm font-semibold text-emerald-200">
                                                <i class="fas fa-circle-check"></i>
                                                <span>Replay da hoan thanh</span>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($nextOrderedItem): ?>
                                            <a
                                                href="watchcourse.php?course_id=<?php echo $courseId; ?>&item_id=<?php echo (int) $nextOrderedItem['item_id']; ?>"
                                                class="inline-flex items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20"
                                            >
                                                <span>Bai tiep theo</span>
                                                <i class="fas fa-arrow-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                            <?php else: ?>
                                <div class="rounded-xl border border-rose-400/30 bg-rose-500/10 p-5 text-rose-100">
                                    Loai learning item khong hop le hoac chua duoc ho tro.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="px-6 py-10 text-center text-white/70">
                            Khong tim thay learning item phu hop.
                        </div>
                    <?php endif; ?>
                </main>
            </div>
        <?php endif; ?>
    <?php endif; ?>
<script>
function openSidebar() {
    var sidebar = document.getElementById('mobileSidebar');
    var overlay = document.getElementById('sidebarOverlay');
    if (sidebar) sidebar.classList.add('open');
    if (overlay) overlay.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    var sidebar = document.getElementById('mobileSidebar');
    var overlay = document.getElementById('sidebarOverlay');
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.add('hidden');
    document.body.style.overflow = '';
}
var toggleBtn = document.getElementById('toggleSidebarBtn');
if (toggleBtn) {
    toggleBtn.addEventListener('click', openSidebar);
}
</script>
</body>
</html>
