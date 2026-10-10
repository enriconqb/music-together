<?php
ini_set('display_errors', '0');
ini_set('html_errors', '0');

ob_start();

register_shutdown_function(static function (): void {
    $error = error_get_last();
    $buffer = ob_get_clean();
    $buffer = is_string($buffer) ? $buffer : '';
    $trimmed = ltrim($buffer);

    if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
        echo $buffer;
        return;
    }

    if (preg_match('/(\{.*\})\s*$/s', $buffer, $match)) {
        echo $match[1];
        return;
    }

    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }

    $message = 'Server mengembalikan respons yang bukan JSON.';
    if (is_array($error) && !empty($error['message'])) {
        $message = trim(preg_replace('/\s+/', ' ', strip_tags((string)$error['message'])) ?? '');
        if ($message === '') {
            $message = 'Kesalahan server.';
        }
    }

    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
});
