<?php

if (!function_exists('app_upload_prepare_directory')) {
    function app_upload_prepare_directory(string $directoryPath, string $label = 'thư mục tải lên'): array
    {
        if ($directoryPath === '') {
            return [
                'ok' => false,
                'message' => 'Đường dẫn lưu tệp không hợp lệ.',
            ];
        }

        if (!is_dir($directoryPath)) {
            if (!@mkdir($directoryPath, 0777, true) && !is_dir($directoryPath)) {
                return [
                    'ok' => false,
                    'message' => 'Không thể tạo ' . $label . '. Vui lòng kiểm tra quyền ghi của thư mục cha.',
                ];
            }
        }

        @chmod($directoryPath, 0777);
        clearstatcache(true, $directoryPath);

        if (!is_writable($directoryPath)) {
            return [
                'ok' => false,
                'message' => 'Thư mục ' . $label . ' chưa có quyền ghi. Trên XAMPP/Windows, hãy đảm bảo Apache có thể ghi vào thư mục này.',
            ];
        }

        return ['ok' => true];
    }
}

if (!function_exists('app_upload_build_filename')) {
    function app_upload_build_filename(string $originalName, string $prefix = 'file'): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $safePrefix = preg_replace('/[^A-Za-z0-9_-]+/', '_', $prefix);
        if ($safePrefix === null || $safePrefix === '') {
            $safePrefix = 'file';
        }

        $filename = $safePrefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(6));
        if ($extension !== '') {
            $filename .= '.' . $extension;
        }

        return $filename;
    }
}

if (!function_exists('app_upload_store_file')) {
    function app_upload_store_file(
        string $tmpPath,
        string $originalName,
        string $targetDirectory,
        string $dbPrefix,
        string $label,
        string $prefix = 'file'
    ): array {
        $directoryCheck = app_upload_prepare_directory($targetDirectory, $label);
        if (!($directoryCheck['ok'] ?? false)) {
            return $directoryCheck;
        }

        $filename = app_upload_build_filename($originalName, $prefix);
        $diskPath = rtrim($targetDirectory, DIRECTORY_SEPARATOR . '/\\') . DIRECTORY_SEPARATOR . $filename;
        $dbPath = rtrim($dbPrefix, '/\\') . '/' . $filename;

        if (!@move_uploaded_file($tmpPath, $diskPath)) {
            return [
                'ok' => false,
                'message' => 'Không thể lưu tệp vào ' . $label . '. Vui lòng kiểm tra quyền ghi của thư mục.',
            ];
        }

        @chmod($diskPath, 0666);

        return [
            'ok' => true,
            'disk_path' => $diskPath,
            'db_path' => $dbPath,
            'filename' => $filename,
        ];
    }
}
