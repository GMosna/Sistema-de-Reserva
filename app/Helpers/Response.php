<?php

/**
 * Global response helpers — thin wrappers around headers/output.
 * Autoloaded via helpers.php; do not namespace these functions.
 */

if (!function_exists('redirect')) {
    function redirect(string $path, ?string $message = null): never
    {
        if ($message !== null) {
            flash('success', $message);
        }
        $url = str_starts_with($path, 'http') ? $path : APP_BASE . $path;
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('back')) {
    /**
     * Redirect to the previous URL (HTTP_REFERER).
     *
     * @param array|string|null $errors Flash as 'error' before redirecting.
     */
    function back(array|string|null $errors = null): never
    {
        if ($errors !== null) {
            $msg = is_array($errors) ? implode(' ', array_values($errors)) : $errors;
            flash('error', $msg);
        }
        $url = $_SERVER['HTTP_REFERER'] ?? APP_BASE . '/';
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('json_resp')) {
    function json_resp(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = [], string $layout = 'app'): never
    {
        extract($data, EXTR_SKIP);
        $file = VIEWS_PATH . '/' . str_replace('.', '/', $template) . '.php';
        if (!file_exists($file)) {
            throw new \RuntimeException("View not found: {$file}");
        }
        ob_start();
        require $file;
        $content = ob_get_clean();
        $layoutFile = VIEWS_PATH . '/layouts/' . $layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
        exit;
    }
}
