<?php
/**
 * Base Controller
 */

namespace App\Core;

abstract class Controller {
    /**
     * Render a view file with data inside reusable layout components
     */
    protected function view(string $view, array $data = [], bool $withLayout = true): void {
        // Extract variables for view access
        extract($data);

        $viewPath = APP_ROOT . '/views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            die("View [{$view}] tidak ditemukan di {$viewPath}");
        }

        if ($withLayout) {
            $headerPath     = APP_ROOT . '/views/layouts/header.php';
            $sidebarPath    = APP_ROOT . '/views/layouts/sidebar.php';
            $navbarPath     = APP_ROOT . '/views/layouts/navbar.php';
            $breadcrumbPath = APP_ROOT . '/views/layouts/breadcrumb.php';
            $flashPath      = APP_ROOT . '/views/layouts/flash.php';
            $footerPath     = APP_ROOT . '/views/layouts/footer.php';

            if (file_exists($headerPath)) require $headerPath;
            if (file_exists($sidebarPath)) require $sidebarPath;

            echo '<div class="app-main-wrapper ' . (Auth::check() ? 'has-sidebar' : '') . '">';
            
            if (file_exists($navbarPath)) require $navbarPath;
            if (file_exists($breadcrumbPath)) require $breadcrumbPath;
            if (file_exists($flashPath)) require $flashPath;

            echo '<main class="content-container">';
            require $viewPath;
            echo '</main>';

            if (file_exists($footerPath)) require $footerPath;
            echo '</div>'; // close .app-main-wrapper
        } else {
            require $viewPath;
        }
    }

    /**
     * Send JSON Response
     */
    protected function json(mixed $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Redirect to internal path or URL
     */
    protected function redirect(string $path): void {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            header("Location: $path");
        } else {
            header("Location: " . base_url($path));
        }
        exit;
    }

    /**
     * Check if request method is POST
     */
    protected function isPost(): bool {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    /**
     * Check if request method is GET
     */
    protected function isGet(): bool {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET';
    }

    /**
     * Get POST parameter or all POST data
     */
    protected function post(?string $key = null, mixed $default = null): mixed {
        if ($key === null) {
            return Security::sanitize($_POST);
        }
        return isset($_POST[$key]) ? Security::sanitize($_POST[$key]) : $default;
    }

    /**
     * Get GET parameter or all GET data
     */
    protected function get(?string $key = null, mixed $default = null): mixed {
        if ($key === null) {
            return Security::sanitize($_GET);
        }
        return isset($_GET[$key]) ? Security::sanitize($_GET[$key]) : $default;
    }

    /**
     * Verify CSRF Token from POST request
     */
    protected function validateCsrf(): bool {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        return Security::validateCsrfToken($token);
    }
}
