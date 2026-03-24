<?php

require_once(__DIR__ . '/../../session_bootstrap.php');
require_once(__DIR__ . '/../../csrf.php');
require_once(__DIR__ . '/../../dbConnection.php');

secure_session_start();

if (!function_exists('instructor_is_logged_in')) {
    function instructor_is_logged_in(): bool
    {
        return isset($_SESSION['is_instructor_login'], $_SESSION['instructor_id'])
            && $_SESSION['is_instructor_login'] === true
            && (int) $_SESSION['instructor_id'] > 0;
    }
}

if (!function_exists('instructor_current_id')) {
    function instructor_current_id(): int
    {
        return (int) ($_SESSION['instructor_id'] ?? 0);
    }
}

if (!function_exists('instructor_current_email')) {
    function instructor_current_email(): string
    {
        return (string) ($_SESSION['instructorLogEmail'] ?? '');
    }
}

if (!function_exists('instructor_force_logout')) {
    function instructor_force_logout(): void
    {
        unset(
            $_SESSION['is_instructor_login'],
            $_SESSION['instructorLogEmail'],
            $_SESSION['instructor_id'],
            $_SESSION['instructor_flash']
        );
    }
}

if (!function_exists('instructor_require_login')) {
    function instructor_require_login(): void
    {
        if (!instructor_is_logged_in()) {
            header('Location: instructorLogin.php');
            exit;
        }
    }
}

if (!function_exists('instructor_set_flash')) {
    function instructor_set_flash(string $type, string $text): void
    {
        $_SESSION['instructor_flash'] = [
            'type' => $type,
            'text' => $text,
        ];
    }
}

if (!function_exists('instructor_get_flash')) {
    function instructor_get_flash(): ?array
    {
        if (!isset($_SESSION['instructor_flash']) || !is_array($_SESSION['instructor_flash'])) {
            return null;
        }

        $flash = $_SESSION['instructor_flash'];
        unset($_SESSION['instructor_flash']);

        $type = isset($flash['type']) ? (string) $flash['type'] : 'info';
        $text = isset($flash['text']) ? (string) $flash['text'] : '';

        if ($text === '') {
            return null;
        }

        return [
            'type' => $type,
            'text' => $text,
        ];
    }
}

if (!function_exists('instructor_current_profile')) {
    function instructor_current_profile(mysqli $conn): ?array
    {
        static $loaded = false;
        static $profile = null;

        if ($loaded) {
            return $profile;
        }

        $loaded = true;
        $instructorId = instructor_current_id();
        if ($instructorId <= 0) {
            return null;
        }

        $stmt = $conn->prepare('SELECT ins_id, ins_name, ins_email, ins_img, ins_status, is_deleted FROM instructor WHERE ins_id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $instructorId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            instructor_force_logout();
            return null;
        }

        if ((int) ($row['is_deleted'] ?? 0) === 1 || (string) ($row['ins_status'] ?? '') !== 'active') {
            instructor_force_logout();
            return null;
        }

        $profile = $row;
        return $profile;
    }
}

if (!function_exists('instructor_find_owned_course')) {
    function instructor_find_owned_course(mysqli $conn, int $courseId, int $instructorId): ?array
    {
        $stmt = $conn->prepare('SELECT * FROM course WHERE course_id = ? AND instructor_id = ? AND is_deleted = 0 LIMIT 1');
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('ii', $courseId, $instructorId);
        $stmt->execute();
        $result = $stmt->get_result();
        $course = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $course ?: null;
    }
}

if (!function_exists('instructor_next_section_position')) {
    function instructor_next_section_position(mysqli $conn, int $courseId): int
    {
        $stmt = $conn->prepare('SELECT COALESCE(MAX(section_position), 0) + 1 AS next_position FROM course_section WHERE course_id = ? AND is_deleted = 0');
        if (!$stmt) {
            return 1;
        }

        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        $next = (int) ($row['next_position'] ?? 1);
        return $next > 0 ? $next : 1;
    }
}

if (!function_exists('instructor_next_item_position')) {
    function instructor_next_item_position(mysqli $conn, int $courseId, int $sectionId): int
    {
        $stmt = $conn->prepare('SELECT COALESCE(MAX(item_position), 0) + 1 AS next_position FROM learning_item WHERE course_id = ? AND section_id = ? AND is_deleted = 0');
        if (!$stmt) {
            return 1;
        }

        $stmt->bind_param('ii', $courseId, $sectionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        $next = (int) ($row['next_position'] ?? 1);
        return $next > 0 ? $next : 1;
    }
}

if (!function_exists('instructor_is_valid_url')) {
    function instructor_is_valid_url(string $url): bool
    {
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }
}

if (!function_exists('instructor_course_has_meaningful_content')) {
    function instructor_course_has_meaningful_content(mysqli $conn, int $courseId): array
    {
        $sectionCount = 0;
        $meaningfulItemCount = 0;

        $sectionStmt = $conn->prepare('SELECT COUNT(*) AS c FROM course_section WHERE course_id = ? AND is_deleted = 0');
        if ($sectionStmt) {
            $sectionStmt->bind_param('i', $courseId);
            $sectionStmt->execute();
            $sectionResult = $sectionStmt->get_result();
            $sectionRow = $sectionResult ? $sectionResult->fetch_assoc() : null;
            $sectionCount = (int) ($sectionRow['c'] ?? 0);
            $sectionStmt->close();
        }

        $itemSql =
            'SELECT COUNT(*) AS c '
            . 'FROM learning_item li '
            . 'INNER JOIN course_section cs ON cs.section_id = li.section_id '
            . 'WHERE li.course_id = ? AND li.is_deleted = 0 AND cs.is_deleted = 0 '
            . 'AND ( '
            . "(li.item_type = 'video' AND TRIM(COALESCE(li.video_url, '')) <> '') OR "
            . "(li.item_type = 'article' AND TRIM(COALESCE(li.article_content, '')) <> '') OR "
            . "(li.item_type = 'document' AND TRIM(COALESCE(li.document_url, '')) <> '') OR "
            . "(li.item_type = 'quiz' AND li.quiz_id IS NOT NULL) OR "
            . "(li.item_type = 'live_session' AND li.live_session_id IS NOT NULL) OR "
            . "(li.item_type = 'replay' AND li.replay_id IS NOT NULL AND EXISTS ("
            . 'SELECT 1 FROM replay_asset ra WHERE ra.replay_id = li.replay_id AND ra.is_deleted = 0 AND TRIM(COALESCE(ra.recording_url, \'\')) <> \'\''
            . ')) '
            . ')';

        $itemStmt = $conn->prepare($itemSql);
        if ($itemStmt) {
            $itemStmt->bind_param('i', $courseId);
            $itemStmt->execute();
            $itemResult = $itemStmt->get_result();
            $itemRow = $itemResult ? $itemResult->fetch_assoc() : null;
            $meaningfulItemCount = (int) ($itemRow['c'] ?? 0);
            $itemStmt->close();
        }

        return [
            'section_count' => $sectionCount,
            'meaningful_item_count' => $meaningfulItemCount,
        ];
    }
}

if (!function_exists('instructor_refresh_live_session_statuses')) {
    function instructor_refresh_live_session_statuses(mysqli $conn, int $instructorId): void
    {
        $stmt = $conn->prepare(
            'SELECT ls.live_session_id, ls.start_at, ls.end_at, ls.session_status, '
            . 'ra.recording_url, ra.is_deleted AS replay_deleted '
            . 'FROM live_session ls '
            . 'LEFT JOIN replay_asset ra ON ra.live_session_id = ls.live_session_id '
            . 'WHERE ls.instructor_id = ? AND ls.is_deleted = 0 AND ls.session_status <> \'cancelled\''
        );
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('i', $instructorId);
        $stmt->execute();
        $result = $stmt->get_result();

        $updateStmt = $conn->prepare('UPDATE live_session SET session_status = ? WHERE live_session_id = ?');
        $now = time();

        if ($result && $updateStmt) {
            while ($row = $result->fetch_assoc()) {
                $currentStatus = (string) ($row['session_status'] ?? 'scheduled');
                $startAt = strtotime((string) ($row['start_at'] ?? ''));
                $endAt = strtotime((string) ($row['end_at'] ?? ''));
                $hasReplay = (int) ($row['replay_deleted'] ?? 0) === 0
                    && trim((string) ($row['recording_url'] ?? '')) !== '';

                $targetStatus = $currentStatus;
                if ($hasReplay) {
                    $targetStatus = 'replay_available';
                } elseif ($startAt !== false && $endAt !== false && $endAt > $startAt) {
                    if ($now < $startAt) {
                        $targetStatus = 'scheduled';
                    } elseif ($now <= $endAt) {
                        $targetStatus = 'live';
                    } else {
                        $targetStatus = 'ended';
                    }
                }

                if ($targetStatus !== $currentStatus) {
                    $liveSessionId = (int) $row['live_session_id'];
                    $updateStmt->bind_param('si', $targetStatus, $liveSessionId);
                    $updateStmt->execute();
                }
            }
        }

        if ($updateStmt) {
            $updateStmt->close();
        }
        $stmt->close();
    }
}

if (!function_exists('instructor_resolve_live_status')) {
    function instructor_resolve_live_status(array $sessionRow): string
    {
        $storedStatus = (string) ($sessionRow['session_status'] ?? 'scheduled');
        if ($storedStatus === 'cancelled') {
            return 'cancelled';
        }

        $hasReplay = trim((string) ($sessionRow['recording_url'] ?? '')) !== '';
        if ($hasReplay) {
            return 'replay_available';
        }

        $startAt = strtotime((string) ($sessionRow['start_at'] ?? ''));
        $endAt = strtotime((string) ($sessionRow['end_at'] ?? ''));
        if ($startAt === false || $endAt === false || $endAt <= $startAt) {
            return $storedStatus;
        }

        $now = time();
        if ($now < $startAt) {
            return 'scheduled';
        }
        if ($now <= $endAt) {
            return 'live';
        }
        return 'ended';
    }
}

if (!function_exists('instructor_course_status_meta')) {
    function instructor_course_status_meta(string $status): array
    {
        $map = [
            'draft' => ['label' => 'Bản nháp', 'class' => 'bg-slate-100 text-slate-700'],
            'pending_review' => ['label' => 'Chờ duyệt', 'class' => 'bg-amber-100 text-amber-800'],
            'published' => ['label' => 'Đã xuất bản', 'class' => 'bg-emerald-100 text-emerald-700'],
            'archived' => ['label' => 'Lưu trữ', 'class' => 'bg-zinc-200 text-zinc-700'],
        ];

        return $map[$status] ?? ['label' => $status, 'class' => 'bg-slate-100 text-slate-700'];
    }
}

if (!function_exists('instructor_live_status_meta')) {
    function instructor_live_status_meta(string $status): array
    {
        $map = [
            'scheduled' => ['label' => 'Sắp diễn ra', 'class' => 'bg-blue-100 text-blue-700'],
            'live' => ['label' => 'Đang live', 'class' => 'bg-emerald-100 text-emerald-700'],
            'ended' => ['label' => 'Đã kết thúc', 'class' => 'bg-slate-100 text-slate-700'],
            'replay_available' => ['label' => 'Có replay', 'class' => 'bg-violet-100 text-violet-700'],
            'cancelled' => ['label' => 'Đã huỷ', 'class' => 'bg-rose-100 text-rose-700'],
        ];

        return $map[$status] ?? ['label' => $status, 'class' => 'bg-slate-100 text-slate-700'];
    }
}
