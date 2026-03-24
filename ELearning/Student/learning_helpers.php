<?php

if (!function_exists('learning_get_student_profile')) {
    function learning_get_student_profile($conn, $stuEmail)
    {
        $stmt = $conn->prepare(
            'SELECT stu_id, stu_name, stu_img FROM student WHERE stu_email = ? AND is_deleted = 0 LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $stuEmail);
        $stmt->execute();
        $result = $stmt->get_result();
        $profile = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$profile) {
            return null;
        }

        return [
            'stu_id' => (int) ($profile['stu_id'] ?? 0),
            'stu_name' => (string) ($profile['stu_name'] ?? ''),
            'stu_img' => (string) ($profile['stu_img'] ?? ''),
        ];
    }
}

if (!function_exists('learning_has_course_access')) {
    function learning_has_course_access($conn, $studentId, $stuEmail, $courseId)
    {
        $enrollStmt = $conn->prepare(
            "SELECT 1 FROM enrollment WHERE student_id = ? AND course_id = ? AND enrollment_status = 'active' LIMIT 1"
        );
        if (!$enrollStmt) {
            return false;
        }

        $enrollStmt->bind_param('ii', $studentId, $courseId);
        $enrollStmt->execute();
        $enrollStmt->store_result();
        $isEnrolled = $enrollStmt->num_rows > 0;
        $enrollStmt->close();

        return $isEnrolled;
    }
}

if (!function_exists('learning_is_youtube_url')) {
    function learning_is_youtube_url($url)
    {
        if (!is_string($url) || $url === '') {
            return false;
        }

        return str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be');
    }
}

if (!function_exists('learning_get_youtube_embed_url')) {
    function learning_get_youtube_embed_url($url)
    {
        if (!is_string($url) || $url === '') {
            return null;
        }

        $id = '';
        if (str_contains($url, 'youtube.com/embed/')) {
            $parts = explode('youtube.com/embed/', $url, 2);
            if (isset($parts[1])) {
                $id = explode('?', $parts[1])[0];
            }
        } elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_\-]+)/', $url, $match)) {
            $id = $match[1];
        } elseif (preg_match('/[?&]v=([a-zA-Z0-9_\-]+)/', $url, $match)) {
            $id = $match[1];
        } elseif (preg_match('/youtube\.com\/shorts\/([a-zA-Z0-9_\-]+)/', $url, $match)) {
            $id = $match[1];
        }

        if ($id === '') {
            return null;
        }

        return 'https://www.youtube.com/embed/' . $id . '?enablejsapi=1&rel=0';
    }
}

if (!function_exists('learning_is_item_completed')) {
    function learning_is_item_completed($itemType, $progressStatus)
    {
        if ($itemType === 'quiz') {
            return $progressStatus === 'passed';
        }

        if ($itemType === 'live_session') {
            return $progressStatus === 'completed';
        }

        return in_array($progressStatus, ['completed', 'passed'], true);
    }
}

if (!function_exists('learning_upsert_progress')) {
    function learning_upsert_progress($conn, $studentId, $courseId, $itemId, $targetStatus, $markCompleted = false)
    {
        $allowedStatuses = ['not_started', 'in_progress', 'completed', 'passed'];
        if (!in_array($targetStatus, $allowedStatuses, true)) {
            return false;
        }

        $existingStmt = $conn->prepare(
            'SELECT progress_id, progress_status FROM learning_progress WHERE student_id = ? AND course_id = ? AND item_id = ? LIMIT 1'
        );
        if (!$existingStmt) {
            return false;
        }

        $existingStmt->bind_param('iii', $studentId, $courseId, $itemId);
        $existingStmt->execute();
        $existingResult = $existingStmt->get_result();
        $existingRow = $existingResult ? $existingResult->fetch_assoc() : null;
        $existingStmt->close();

        $finalStatus = $targetStatus;
        if ($existingRow) {
            $currentStatus = (string) $existingRow['progress_status'];
            if ($currentStatus === 'passed' && $targetStatus !== 'passed') {
                $finalStatus = 'passed';
            } elseif ($currentStatus === 'completed' && in_array($targetStatus, ['not_started', 'in_progress'], true)) {
                $finalStatus = 'completed';
            } elseif ($currentStatus === 'in_progress' && $targetStatus === 'not_started') {
                $finalStatus = 'in_progress';
            }
        }

        $shouldSetCompletedAt = in_array($finalStatus, ['completed', 'passed'], true)
            && ($markCompleted || in_array($targetStatus, ['completed', 'passed'], true));

        if ($existingRow) {
            $progressId = (int) $existingRow['progress_id'];
            if ($shouldSetCompletedAt) {
                $updateStmt = $conn->prepare(
                    'UPDATE learning_progress SET progress_status = ?, last_accessed_at = NOW(), completed_at = COALESCE(completed_at, NOW()) WHERE progress_id = ?'
                );
            } else {
                $updateStmt = $conn->prepare(
                    'UPDATE learning_progress SET progress_status = ?, last_accessed_at = NOW() WHERE progress_id = ?'
                );
            }

            if (!$updateStmt) {
                return false;
            }

            $updateStmt->bind_param('si', $finalStatus, $progressId);
            $ok = $updateStmt->execute();
            $updateStmt->close();

            return $ok;
        }

        if ($shouldSetCompletedAt) {
            $insertStmt = $conn->prepare(
                'INSERT INTO learning_progress (student_id, course_id, item_id, progress_status, last_accessed_at, completed_at) VALUES (?, ?, ?, ?, NOW(), NOW())'
            );
        } else {
            $insertStmt = $conn->prepare(
                'INSERT INTO learning_progress (student_id, course_id, item_id, progress_status, last_accessed_at) VALUES (?, ?, ?, ?, NOW())'
            );
        }

        if (!$insertStmt) {
            return false;
        }

        $insertStmt->bind_param('iiis', $studentId, $courseId, $itemId, $finalStatus);
        $ok = $insertStmt->execute();
        $insertStmt->close();

        return $ok;
    }
}

if (!function_exists('learning_get_item_type_label')) {
    function learning_get_item_type_label($itemType)
    {
        $labels = [
            'video' => 'Video',
            'article' => 'Bai viet',
            'document' => 'Tai lieu',
            'quiz' => 'Quiz',
            'live_session' => 'Live',
            'replay' => 'Replay',
        ];

        return $labels[$itemType] ?? 'Khong xac dinh';
    }
}

if (!function_exists('learning_get_progress_summary_from_items')) {
    function learning_get_progress_summary_from_items($items)
    {
        $requiredTotal = 0;
        $requiredCompleted = 0;
        $completedTotal = 0;
        $nextItem = null;

        foreach ($items as $item) {
            $itemType = (string) ($item['item_type'] ?? '');
            $progressStatus = (string) ($item['progress_status'] ?? 'not_started');
            $isRequired = (int) ($item['is_required'] ?? 0) === 1;
            $isCompleted = learning_is_item_completed($itemType, $progressStatus);

            if ($isCompleted) {
                $completedTotal++;
            }

            if ($isRequired) {
                $requiredTotal++;
                if ($isCompleted) {
                    $requiredCompleted++;
                } elseif ($nextItem === null) {
                    $nextItem = $item;
                }
            }
        }

        if ($nextItem === null) {
            foreach ($items as $item) {
                $itemType = (string) ($item['item_type'] ?? '');
                $progressStatus = (string) ($item['progress_status'] ?? 'not_started');
                if (!learning_is_item_completed($itemType, $progressStatus)) {
                    $nextItem = $item;
                    break;
                }
            }
        }

        $progressPercent = $requiredTotal > 0
            ? round(($requiredCompleted / $requiredTotal) * 100, 2)
            : 0.00;

        return [
            'required_total' => $requiredTotal,
            'required_completed' => $requiredCompleted,
            'completed_total' => $completedTotal,
            'total_items' => count($items),
            'progress_percent' => $progressPercent,
            'next_item' => $nextItem,
        ];
    }
}

if (!function_exists('learning_fetch_course_outline')) {
    function learning_fetch_course_outline($conn, $courseId, $studentId)
    {
        $stmt = $conn->prepare(
            'SELECT '
            . 'cs.section_id, cs.section_title, cs.section_position, '
            . 'li.item_id, li.item_title, li.item_type, li.item_position, li.is_required, '
            . 'li.video_url, li.article_content, li.document_url, li.quiz_id, li.live_session_id, li.replay_id, '
            . "COALESCE(lp.progress_status, 'not_started') AS progress_status, "
            . 'q.quiz_title, q.quiz_description, q.pass_score, q.max_attempts, '
            . 'ls.session_title, ls.session_description, ls.start_at, ls.end_at, ls.join_url, ls.platform_name, ls.session_status, '
            . 'ra.recording_url, ra.recording_provider, ra.available_at, ra.live_session_id AS replay_live_session_id '
            . 'FROM course_section cs '
            . 'LEFT JOIN learning_item li '
            . "ON li.section_id = cs.section_id AND li.is_deleted = 0 AND li.content_status = 'published' "
            . 'LEFT JOIN learning_progress lp '
            . 'ON lp.item_id = li.item_id AND lp.student_id = ? AND lp.course_id = ? '
            . "LEFT JOIN quiz q ON q.quiz_id = li.quiz_id AND q.is_deleted = 0 AND q.content_status = 'published' "
            . 'LEFT JOIN live_session ls ON ls.live_session_id = li.live_session_id AND ls.is_deleted = 0 '
            . 'LEFT JOIN replay_asset ra ON ra.replay_id = li.replay_id AND ra.is_deleted = 0 '
            . 'WHERE cs.course_id = ? AND cs.is_deleted = 0 '
            . 'ORDER BY cs.section_position ASC, li.item_position ASC, li.item_id ASC'
        );
        if (!$stmt) {
            return [
                'sections' => [],
                'items' => [],
                'items_by_id' => [],
            ];
        }

        $stmt->bind_param('iii', $studentId, $courseId, $courseId);
        $stmt->execute();
        $result = $stmt->get_result();

        $sections = [];
        $items = [];
        $itemsById = [];

        while ($row = $result->fetch_assoc()) {
            $sectionId = (int) ($row['section_id'] ?? 0);
            if (!isset($sections[$sectionId])) {
                $sections[$sectionId] = [
                    'section_id' => $sectionId,
                    'section_title' => (string) ($row['section_title'] ?? ''),
                    'section_position' => (int) ($row['section_position'] ?? 0),
                    'items' => [],
                ];
            }

            if (!isset($row['item_id']) || $row['item_id'] === null) {
                continue;
            }

            $itemId = (int) $row['item_id'];
            $item = [
                'item_id' => $itemId,
                'course_id' => $courseId,
                'section_id' => $sectionId,
                'section_title' => (string) ($row['section_title'] ?? ''),
                'item_title' => (string) ($row['item_title'] ?? ''),
                'item_type' => (string) ($row['item_type'] ?? ''),
                'item_position' => (int) ($row['item_position'] ?? 0),
                'is_required' => (int) ($row['is_required'] ?? 0),
                'video_url' => (string) ($row['video_url'] ?? ''),
                'article_content' => (string) ($row['article_content'] ?? ''),
                'document_url' => (string) ($row['document_url'] ?? ''),
                'quiz_id' => isset($row['quiz_id']) ? (int) $row['quiz_id'] : 0,
                'live_session_id' => isset($row['live_session_id']) ? (int) $row['live_session_id'] : 0,
                'replay_id' => isset($row['replay_id']) ? (int) $row['replay_id'] : 0,
                'progress_status' => (string) ($row['progress_status'] ?? 'not_started'),
                'quiz_title' => (string) ($row['quiz_title'] ?? ''),
                'quiz_description' => (string) ($row['quiz_description'] ?? ''),
                'pass_score' => (float) ($row['pass_score'] ?? 0),
                'max_attempts' => (int) ($row['max_attempts'] ?? 0),
                'session_title' => (string) ($row['session_title'] ?? ''),
                'session_description' => (string) ($row['session_description'] ?? ''),
                'start_at' => (string) ($row['start_at'] ?? ''),
                'end_at' => (string) ($row['end_at'] ?? ''),
                'join_url' => (string) ($row['join_url'] ?? ''),
                'platform_name' => (string) ($row['platform_name'] ?? ''),
                'session_status' => (string) ($row['session_status'] ?? ''),
                'recording_url' => (string) ($row['recording_url'] ?? ''),
                'recording_provider' => (string) ($row['recording_provider'] ?? ''),
                'available_at' => (string) ($row['available_at'] ?? ''),
                'replay_live_session_id' => isset($row['replay_live_session_id']) ? (int) $row['replay_live_session_id'] : 0,
            ];

            $sections[$sectionId]['items'][] = $item;
            $items[] = $item;
            $itemsById[$itemId] = $item;
        }

        $stmt->close();

        return [
            'sections' => array_values($sections),
            'items' => $items,
            'items_by_id' => $itemsById,
        ];
    }
}

if (!function_exists('learning_sync_enrollment_progress')) {
    function learning_sync_enrollment_progress($conn, $studentId, $courseId, $progressPercent)
    {
        $normalized = max(0, min(100, (float) $progressPercent));

        $stmt = $conn->prepare(
            'UPDATE enrollment '
            . 'SET progress_percent = ?, '
            . 'completed_at = CASE '
            . 'WHEN ? >= 100 AND completed_at IS NULL THEN NOW() '
            . 'WHEN ? < 100 THEN NULL '
            . 'ELSE completed_at END '
            . "WHERE student_id = ? AND course_id = ? AND enrollment_status = 'active'"
        );
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ddiii', $normalized, $normalized, $normalized, $studentId, $courseId);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }
}

if (!function_exists('learning_recalculate_course_progress')) {
    function learning_recalculate_course_progress($conn, $studentId, $courseId)
    {
        $stmt = $conn->prepare(
            'SELECT '
            . 'li.item_id, li.item_type, li.is_required, '
            . "COALESCE(lp.progress_status, 'not_started') AS progress_status "
            . 'FROM learning_item li '
            . 'INNER JOIN course_section cs ON cs.section_id = li.section_id AND cs.is_deleted = 0 '
            . 'LEFT JOIN learning_progress lp ON lp.item_id = li.item_id AND lp.student_id = ? AND lp.course_id = ? '
            . "WHERE li.course_id = ? AND li.is_deleted = 0 AND li.content_status = 'published' "
            . 'ORDER BY cs.section_position ASC, li.item_position ASC, li.item_id ASC'
        );
        if (!$stmt) {
            return [
                'required_total' => 0,
                'required_completed' => 0,
                'completed_total' => 0,
                'total_items' => 0,
                'progress_percent' => 0,
                'next_item' => null,
            ];
        }

        $stmt->bind_param('iii', $studentId, $courseId, $courseId);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'item_id' => (int) ($row['item_id'] ?? 0),
                'item_type' => (string) ($row['item_type'] ?? ''),
                'is_required' => (int) ($row['is_required'] ?? 0),
                'progress_status' => (string) ($row['progress_status'] ?? 'not_started'),
            ];
        }

        $stmt->close();

        $summary = learning_get_progress_summary_from_items($items);
        learning_sync_enrollment_progress($conn, $studentId, $courseId, $summary['progress_percent']);

        return $summary;
    }
}
