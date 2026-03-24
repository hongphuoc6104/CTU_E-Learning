<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../csrf.php');

define('TITLE', 'Phiên live');
define('PAGE', 'livesessions');
include('./adminInclude/header.php');
include('../dbConnection.php');

if(!isset($_SESSION['is_admin_login'])){
    echo "<script>location.href='../index.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_replay'])) {
    if(!csrf_verify($_POST['csrf_token'] ?? null)) {
        admin_set_flash('error', 'Phiên gửi biểu mẫu đã hết hạn.');
        echo "<script>location.href='liveSessions.php';</script>";
        exit;
    }

    $liveSessionId = filter_input(INPUT_POST, 'live_session_id', FILTER_VALIDATE_INT);
    $recordingUrl = trim((string) ($_POST['recording_url'] ?? ''));
    $recordingProvider = trim((string) ($_POST['recording_provider'] ?? ''));
    if ($recordingProvider === '') {
        $recordingProvider = 'External Replay';
    }

    if (!$liveSessionId) {
        admin_set_flash('error', 'Không xác định được phiên live cần xử lý.');
        echo "<script>location.href='liveSessions.php';</script>";
        exit;
    }

    if ($recordingUrl === '' || !admin_is_valid_url($recordingUrl)) {
        admin_set_flash('error', 'Replay URL không hợp lệ.');
        echo "<script>location.href='liveSessions.php';</script>";
        exit;
    }

    $conn->begin_transaction();
    try {
        $sessionStmt = $conn->prepare(
            'SELECT ls.live_session_id, ls.course_id, ls.section_id, ls.session_title, ls.session_status '
            . 'FROM live_session ls '
            . 'INNER JOIN course c ON c.course_id = ls.course_id '
            . 'WHERE ls.live_session_id = ? AND ls.is_deleted = 0 AND c.is_deleted = 0 LIMIT 1 FOR UPDATE'
        );
        if (!$sessionStmt) {
            throw new RuntimeException('Không thể tải phiên live để cập nhật replay.');
        }

        $sessionStmt->bind_param('i', $liveSessionId);
        $sessionStmt->execute();
        $sessionResult = $sessionStmt->get_result();
        $liveRow = $sessionResult ? $sessionResult->fetch_assoc() : null;
        $sessionStmt->close();

        if (!$liveRow) {
            throw new RuntimeException('Phiên live không tồn tại hoặc đã bị xóa.');
        }

        $courseId = (int) ($liveRow['course_id'] ?? 0);
        $sectionId = (int) ($liveRow['section_id'] ?? 0);
        $replayId = 0;

        $existingReplayStmt = $conn->prepare('SELECT replay_id FROM replay_asset WHERE live_session_id = ? LIMIT 1');
        if (!$existingReplayStmt) {
            throw new RuntimeException('Không thể kiểm tra replay hiện tại.');
        }

        $existingReplayStmt->bind_param('i', $liveSessionId);
        $existingReplayStmt->execute();
        $existingReplayResult = $existingReplayStmt->get_result();
        $existingReplay = $existingReplayResult ? $existingReplayResult->fetch_assoc() : null;
        $existingReplayStmt->close();

        if ($existingReplay) {
            $replayId = (int) ($existingReplay['replay_id'] ?? 0);
            $updateReplayStmt = $conn->prepare(
                'UPDATE replay_asset '
                . 'SET recording_url = ?, recording_provider = ?, available_at = NOW(), is_deleted = 0 '
                . 'WHERE replay_id = ?'
            );
            if (!$updateReplayStmt) {
                throw new RuntimeException('Không thể cập nhật replay hiện có.');
            }
            $updateReplayStmt->bind_param('ssi', $recordingUrl, $recordingProvider, $replayId);
            if (!$updateReplayStmt->execute()) {
                $updateReplayStmt->close();
                throw new RuntimeException('Không thể lưu replay URL.');
            }
            $updateReplayStmt->close();
        } else {
            $insertReplayStmt = $conn->prepare(
                'INSERT INTO replay_asset (live_session_id, recording_url, recording_provider, available_at, is_deleted) '
                . 'VALUES (?, ?, ?, NOW(), 0)'
            );
            if (!$insertReplayStmt) {
                throw new RuntimeException('Không thể tạo replay mới.');
            }
            $insertReplayStmt->bind_param('iss', $liveSessionId, $recordingUrl, $recordingProvider);
            if (!$insertReplayStmt->execute()) {
                $insertReplayStmt->close();
                throw new RuntimeException('Không thể tạo replay mới.');
            }
            $replayId = (int) $insertReplayStmt->insert_id;
            $insertReplayStmt->close();
        }

        $updateLiveStmt = $conn->prepare('UPDATE live_session SET session_status = ? WHERE live_session_id = ? LIMIT 1');
        if (!$updateLiveStmt) {
            throw new RuntimeException('Không thể cập nhật trạng thái live session.');
        }
        $replayStatus = 'replay_available';
        $updateLiveStmt->bind_param('si', $replayStatus, $liveSessionId);
        if (!$updateLiveStmt->execute()) {
            $updateLiveStmt->close();
            throw new RuntimeException('Không thể chuyển session sang replay_available.');
        }
        $updateLiveStmt->close();

        if ($sectionId > 0 && $replayId > 0) {
            $existingReplayItemStmt = $conn->prepare(
                'SELECT item_id FROM learning_item WHERE course_id = ? AND replay_id = ? AND is_deleted = 0 LIMIT 1'
            );
            if (!$existingReplayItemStmt) {
                throw new RuntimeException('Không thể kiểm tra learning item replay.');
            }
            $existingReplayItemStmt->bind_param('ii', $courseId, $replayId);
            $existingReplayItemStmt->execute();
            $existingReplayItemStmt->store_result();
            $hasReplayItem = $existingReplayItemStmt->num_rows > 0;
            $existingReplayItemStmt->close();

            if (!$hasReplayItem) {
                $positionStmt = $conn->prepare('SELECT COALESCE(MAX(item_position), 0) + 1 AS next_position FROM learning_item WHERE course_id = ? AND section_id = ? AND is_deleted = 0');
                if (!$positionStmt) {
                    throw new RuntimeException('Không thể xác định vị trí learning item replay.');
                }
                $positionStmt->bind_param('ii', $courseId, $sectionId);
                $positionStmt->execute();
                $positionResult = $positionStmt->get_result();
                $positionRow = $positionResult ? $positionResult->fetch_assoc() : null;
                $positionStmt->close();
                $itemPosition = (int) ($positionRow['next_position'] ?? 1);
                if ($itemPosition <= 0) {
                    $itemPosition = 1;
                }

                $itemTitle = 'Replay - ' . (string) ($liveRow['session_title'] ?? 'Live session');
                $contentStatus = 'published';
                $insertItemStmt = $conn->prepare(
                    'INSERT INTO learning_item '
                    . '(course_id, section_id, item_title, item_type, item_position, is_preview, is_required, content_status, replay_id, is_deleted) '
                    . "VALUES (?, ?, ?, 'replay', ?, 0, 1, ?, ?, 0)"
                );
                if (!$insertItemStmt) {
                    throw new RuntimeException('Không thể tạo learning item replay.');
                }
                $insertItemStmt->bind_param('iisisi', $courseId, $sectionId, $itemTitle, $itemPosition, $contentStatus, $replayId);
                if (!$insertItemStmt->execute()) {
                    $insertItemStmt->close();
                    throw new RuntimeException('Không thể tạo learning item replay.');
                }
                $insertItemStmt->close();
            }
        }

        $conn->commit();
        admin_set_flash('success', 'Đã cập nhật replay cho phiên live.');
    } catch (Throwable $exception) {
        $conn->rollback();
        admin_set_flash('error', $exception->getMessage() !== '' ? $exception->getMessage() : 'Không thể cập nhật replay.');
    }

    echo "<script>location.href='liveSessions.php';</script>";
    exit;
}

$statusFilter = trim((string) ($_GET['status'] ?? 'all'));
$validStatusFilter = ['all', 'scheduled', 'live', 'ended', 'replay_available', 'cancelled'];
if (!in_array($statusFilter, $validStatusFilter, true)) {
    $statusFilter = 'all';
}

$whereSql = 'WHERE ls.is_deleted = 0 AND c.is_deleted = 0 ';
if ($statusFilter !== 'all') {
    $whereSql .= "AND ls.session_status = '" . $conn->real_escape_string($statusFilter) . "' ";
}

$sessions = $conn->query(
    'SELECT ls.live_session_id, ls.course_id, ls.section_id, ls.session_title, ls.session_description, ls.start_at, ls.end_at, ls.join_url, ls.platform_name, ls.session_status, '
    . 'c.course_name, cs.section_title, i.ins_name, '
    . 'ra.replay_id, ra.recording_url, ra.recording_provider, ra.available_at '
    . 'FROM live_session ls '
    . 'INNER JOIN course c ON c.course_id = ls.course_id '
    . 'LEFT JOIN course_section cs ON cs.section_id = ls.section_id '
    . 'LEFT JOIN instructor i ON i.ins_id = ls.instructor_id '
    . 'LEFT JOIN replay_asset ra ON ra.live_session_id = ls.live_session_id AND ra.is_deleted = 0 '
    . $whereSql
    . 'ORDER BY ls.start_at DESC, ls.live_session_id DESC'
);
?>

<div class="mb-6 flex flex-wrap gap-2">
  <?php
    $tabs = [
      'all' => 'Tất cả',
      'scheduled' => 'Sắp diễn ra',
      'live' => 'Đang live',
      'ended' => 'Đã kết thúc',
      'replay_available' => 'Có replay',
      'cancelled' => 'Đã hủy',
    ];
    foreach ($tabs as $tabKey => $tabLabel):
      $isActive = $statusFilter === $tabKey;
  ?>
    <a href="liveSessions.php?status=<?php echo urlencode($tabKey); ?>" class="inline-flex items-center rounded-xl border px-3 py-2 text-xs font-bold <?php echo $isActive ? 'border-primary bg-primary text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'; ?>">
      <?php echo htmlspecialchars($tabLabel, ENT_QUOTES, 'UTF-8'); ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="space-y-4">
  <?php if($sessions && $sessions->num_rows > 0): ?>
    <?php while($live = $sessions->fetch_assoc()): ?>
      <?php
        $resolvedStatus = admin_resolve_live_status($live);
        $statusMeta = admin_live_status_meta($resolvedStatus);
        $recordingUrl = trim((string) ($live['recording_url'] ?? ''));
        $joinUrl = trim((string) ($live['join_url'] ?? ''));
        $isJoinValid = admin_is_valid_url($joinUrl);
      ?>
      <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h3 class="m-0 text-base font-black text-slate-800"><?php echo htmlspecialchars((string) ($live['session_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
            <p class="m-0 mt-1 text-xs text-slate-500"><?php echo htmlspecialchars((string) ($live['course_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?><?php echo !empty($live['section_title']) ? ' · ' . htmlspecialchars((string) $live['section_title'], ENT_QUOTES, 'UTF-8') : ''; ?></p>
            <p class="m-0 mt-1 text-xs text-slate-500">Instructor: <?php echo htmlspecialchars((string) ($live['ins_name'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
          <span class="inline-flex rounded-lg px-2.5 py-1 text-[11px] font-bold <?php echo htmlspecialchars((string) $statusMeta['class'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars((string) $statusMeta['label'], ENT_QUOTES, 'UTF-8'); ?>
          </span>
        </div>

        <div class="mt-3 grid gap-2 text-xs text-slate-600 sm:grid-cols-2">
          <p class="m-0"><span class="font-bold">Thời gian:</span> <?php echo htmlspecialchars((string) ($live['start_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars((string) ($live['end_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
          <p class="m-0"><span class="font-bold">Nền tảng:</span> <?php echo htmlspecialchars((string) ($live['platform_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
          <p class="m-0 sm:col-span-2">
            <span class="font-bold">Join link:</span>
            <?php if($isJoinValid): ?>
              <a href="<?php echo htmlspecialchars($joinUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline">
                <?php echo htmlspecialchars($joinUrl, ENT_QUOTES, 'UTF-8'); ?>
              </a>
            <?php else: ?>
              <span class="text-red-600">Link không hợp lệ, cần can thiệp.</span>
            <?php endif; ?>
          </p>
        </div>

        <?php if (!empty($live['session_description'])): ?>
          <p class="m-0 mt-3 rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-600"><?php echo htmlspecialchars((string) $live['session_description'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
          <div class="mb-2 flex items-center justify-between gap-2">
            <p class="m-0 text-xs font-extrabold uppercase tracking-wide text-slate-500">Replay can thiệp bởi admin</p>
            <?php if ($recordingUrl !== ''): ?>
              <a href="<?php echo htmlspecialchars($recordingUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-primary hover:underline">Mở replay</a>
            <?php endif; ?>
          </div>

          <form method="POST" class="grid gap-2 md:grid-cols-[1fr_180px_auto] md:items-end">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="live_session_id" value="<?php echo (int) ($live['live_session_id'] ?? 0); ?>">

            <div>
              <label class="mb-1 block text-xs font-bold text-slate-600">Recording URL</label>
              <input type="url" name="recording_url" value="<?php echo htmlspecialchars($recordingUrl, ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="https://www.youtube.com/watch?v=..." required>
            </div>

            <div>
              <label class="mb-1 block text-xs font-bold text-slate-600">Provider</label>
              <input type="text" name="recording_provider" value="<?php echo htmlspecialchars((string) ($live['recording_provider'] ?? 'YouTube'), ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10" placeholder="YouTube">
            </div>

            <button type="submit" name="update_replay" class="inline-flex items-center justify-center gap-2 rounded-xl border-0 bg-emerald-600 px-4 py-2 text-xs font-extrabold text-white transition hover:bg-emerald-700">
              <i class="fas fa-save"></i>
              <span>Lưu replay</span>
            </button>
          </form>
        </div>
      </article>
    <?php endwhile; ?>
  <?php else: ?>
    <article class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm text-slate-400">
      Không có phiên live nào phù hợp bộ lọc hiện tại.
    </article>
  <?php endif; ?>
</div>

<?php include('./adminInclude/footer.php'); ?>
