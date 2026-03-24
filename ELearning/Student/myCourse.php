<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();

define('TITLE', 'Khoa hoc cua toi');
define('PAGE', 'mycourse');
include('./stuInclude/header.php');
include_once('../dbConnection.php');
require_once(__DIR__ . '/learning_helpers.php');

if (isset($_SESSION['is_login'])) {
    $stuLogEmail = $_SESSION['stuLogEmail'];
} else {
    echo "<script> location.href='../index.php'; </script>";
    exit;
}

$studentProfile = learning_get_student_profile($conn, (string) $stuLogEmail);
$studentId = $studentProfile ? (int) $studentProfile['stu_id'] : 0;

if ($studentId <= 0) {
    echo "<script> location.href='../index.php'; </script>";
    exit;
}

if (!function_exists('learning_text_preview')) {
    function learning_text_preview($text, $maxLength)
    {
        $value = (string) $text;
        $limit = (int) $maxLength;
        if ($limit <= 0) {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($value, 'UTF-8') <= $limit) {
                return $value;
            }

            return mb_substr($value, 0, $limit, 'UTF-8') . '...';
        }

        if (strlen($value) <= $limit) {
            return $value;
        }

        return substr($value, 0, $limit) . '...';
    }
}

$cards = [];

if (isset($stuLogEmail)) {
    $stmt = $conn->prepare(
        'SELECT '
        . 'e.enrollment_id, e.granted_at, '
        . 'c.course_id, c.course_name, c.course_duration, c.course_desc, c.course_img, c.course_author, c.course_original_price, c.course_price, '
        . 'e.progress_percent '
        . 'FROM enrollment e '
        . 'INNER JOIN course c ON c.course_id = e.course_id AND c.is_deleted = 0 '
        . "WHERE e.student_id = ? AND e.enrollment_status = 'active' "
        . 'ORDER BY e.granted_at DESC, e.enrollment_id DESC'
    );

    $result = false;
    if ($stmt) {
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
    }

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $courseId = (int) ($row['course_id'] ?? 0);

            $summary = learning_recalculate_course_progress($conn, $studentId, $courseId);
            $nextItem = $summary['next_item'] ?? null;

            $enrollmentProgress = isset($row['progress_percent']) ? (float) $row['progress_percent'] : null;
            $computedProgress = isset($summary['progress_percent']) ? (float) $summary['progress_percent'] : 0;
            $finalProgress = $enrollmentProgress !== null ? $enrollmentProgress : $computedProgress;
            $finalProgress = max(0, min(100, $finalProgress));

            $cards[] = [
                'course' => $row,
                'summary' => $summary,
                'next_item' => $nextItem,
                'progress_percent' => $finalProgress,
            ];
        }
    }

    if ($stmt) {
        $stmt->close();
    }
}
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
    <div class="mb-6 sm:mb-8">
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 flex items-center gap-3">
            <i class="fas fa-book-reader text-primary"></i> Khoa hoc cua toi
        </h1>
        <p class="text-sm sm:text-base text-slate-500 mt-2">Theo doi tien do hoc tap, tiep tuc bai tiep theo va hoan thanh khoa hoc cua ban.</p>
    </div>

    <?php if (!empty($cards)): ?>
        <div class="space-y-4 sm:space-y-5">
            <?php foreach ($cards as $card): ?>
                <?php
                $course = $card['course'];
                $summary = $card['summary'];
                $nextItem = $card['next_item'];

                $courseId = (int) ($course['course_id'] ?? 0);
                $imgSrc = '../' . ltrim(str_replace('../', '', (string) ($course['course_img'] ?? '')), '/');
                $courseName = (string) ($course['course_name'] ?? 'Khoa hoc');
                $courseDesc = trim((string) ($course['course_desc'] ?? ''));
                $courseDescShort = learning_text_preview($courseDesc, 150);

                $completedTotal = (int) ($summary['completed_total'] ?? 0);
                $totalItems = (int) ($summary['total_items'] ?? 0);
                $requiredCompleted = (int) ($summary['required_completed'] ?? 0);
                $requiredTotal = (int) ($summary['required_total'] ?? 0);
                $progressPercent = (float) ($card['progress_percent'] ?? 0);
                $progressPercentRounded = (int) round($progressPercent);

                $nextItemTitle = $nextItem ? (string) ($nextItem['item_title'] ?? '') : '';
                $nextItemId = $nextItem ? (int) ($nextItem['item_id'] ?? 0) : 0;

                $nextUrl = 'watchcourse.php?course_id=' . $courseId;
                if ($nextItemId > 0) {
                    $nextUrl .= '&item_id=' . $nextItemId;
                }

                $isCompletedCourse = $requiredTotal > 0 && $requiredCompleted >= $requiredTotal;
                ?>

                <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-md transition-all flex flex-col md:flex-row">
                    <a href="watchcourse.php?course_id=<?php echo $courseId; ?>" class="md:w-56 shrink-0">
                        <img
                            src="<?php echo htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8'); ?>"
                            class="w-full h-40 md:h-full object-cover"
                            alt="Course image"
                            onerror="this.onerror=null;this.src='../image/courseimg/Banner1.jpeg';"
                        >
                    </a>

                    <div class="flex flex-col flex-grow p-4 sm:p-6">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                            <div class="flex-grow">
                                <h2 class="text-base sm:text-lg font-bold text-slate-900 line-clamp-2 mb-2">
                                    <?php echo htmlspecialchars($courseName, ENT_QUOTES, 'UTF-8'); ?>
                                </h2>
                                <p class="text-sm text-slate-500 line-clamp-2 leading-relaxed mb-4">
                                    <?php echo htmlspecialchars($courseDescShort, ENT_QUOTES, 'UTF-8'); ?>
                                </p>

                                <div class="flex flex-wrap gap-4 text-xs text-slate-500">
                                    <span class="flex items-center gap-1.5">
                                        <i class="fas fa-clock text-primary"></i>
                                        <?php echo htmlspecialchars((string) ($course['course_duration'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <?php if (!empty($course['course_author'])): ?>
                                        <span class="flex items-center gap-1.5">
                                            <i class="fas fa-chalkboard-teacher text-primary"></i>
                                            <?php echo htmlspecialchars((string) $course['course_author'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="flex items-center gap-1.5">
                                        <i class="fas fa-calendar-alt text-primary"></i>
                                        Ghi danh: <?php echo htmlspecialchars((string) ($course['granted_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-col items-start md:items-end gap-3 shrink-0">
                                <div class="text-right">
                                    <p class="text-xs text-slate-400 line-through">
                                        <?php echo number_format((int) ($course['course_original_price'] ?? 0)); ?> đ
                                    </p>
                                    <p class="text-lg font-black text-red-600">
                                        <?php echo number_format((int) ($course['course_price'] ?? 0)); ?> đ
                                    </p>
                                </div>

                                <a
                                    href="<?php echo htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="px-5 py-2.5 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary/90 transition-all shadow-md shadow-primary/20 flex items-center gap-2"
                                >
                                    <i class="fas fa-play-circle"></i>
                                    <?php echo $isCompletedCourse ? 'Xem lai khoa hoc' : 'Tiep tuc hoc'; ?>
                                </a>
                            </div>
                        </div>

                        <div class="mt-4 sm:mt-5 pt-3 sm:pt-4 border-t border-slate-100">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3 text-xs">
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                    <p class="text-slate-500">Tien do</p>
                                    <p class="text-slate-900 font-bold mt-1"><?php echo $progressPercentRounded; ?>%</p>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                    <p class="text-slate-500">Da hoan thanh</p>
                                    <p class="text-slate-900 font-bold mt-1"><?php echo $completedTotal; ?>/<?php echo $totalItems; ?> muc</p>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                    <p class="text-slate-500">Muc bat buoc</p>
                                    <p class="text-slate-900 font-bold mt-1"><?php echo $requiredCompleted; ?>/<?php echo $requiredTotal; ?> muc</p>
                                </div>
                            </div>

                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div
                                    class="h-full <?php echo $isCompletedCourse ? 'bg-emerald-500' : 'bg-primary'; ?> rounded-full"
                                    style="width: <?php echo $progressPercentRounded; ?>%;"
                                ></div>
                            </div>

                            <?php if ($nextItemId > 0 && !$isCompletedCourse): ?>
                                <div class="mt-3 text-xs text-slate-600 flex items-center gap-2">
                                    <i class="fas fa-forward text-primary"></i>
                                    <span class="font-semibold text-slate-700">Next:</span>
                                    <span class="line-clamp-1"><?php echo htmlspecialchars($nextItemTitle, ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            <?php elseif ($isCompletedCourse): ?>
                                <div class="mt-3 text-xs text-emerald-700 flex items-center gap-2">
                                    <i class="fas fa-circle-check"></i>
                                    <span class="font-semibold">Ban da hoan thanh cac muc bat buoc cua khoa hoc.</span>
                                </div>
                            <?php else: ?>
                                <div class="mt-3 text-xs text-slate-500">Dang cap nhat learning item tiep theo.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-16 text-center">
            <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-book-open text-3xl text-primary/50"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-700 mb-2">Chua co khoa hoc nao</h2>
            <p class="text-slate-400 mb-8">Hay kham pha va dang ky khoa hoc dau tien cua ban!</p>
            <a href="../courses.php" class="px-8 py-3.5 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 inline-flex items-center gap-2">
                <i class="fas fa-search"></i> Kham pha khoa hoc
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include('./stuInclude/footer.php'); ?>
