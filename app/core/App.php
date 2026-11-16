<?php
/**
 * Application Router & Dispatcher
 */

namespace App\Core;

class App {
    protected string $controller = 'HomeController';
    protected string $method = 'index';
    protected array $params = [];

    public function __construct() {
        $url = $this->parseUrl();

        // 1. Controller check & sanitization
        if (!empty($url[0])) {
            $sanitized = preg_replace('/[^a-zA-Z0-9]/', '', $url[0]);
            if ($sanitized === '') {
                $this->render404("Format nama controller tidak valid.");
                return;
            }
            $controllerName = ucfirst($sanitized) . 'Controller';
            $controllerFile = APP_ROOT . '/controllers/' . $controllerName . '.php';

            if (file_exists($controllerFile)) {
                $this->controller = $controllerName;
                unset($url[0]);
            } else {
                // Try singular form (remove trailing 's')
                $singular = rtrim($sanitized, 's');
                $controllerNameSingular = ucfirst($singular) . 'Controller';
                $controllerFileSingular = APP_ROOT . '/controllers/' . $controllerNameSingular . '.php';
                if (file_exists($controllerFileSingular)) {
                    $this->controller = $controllerNameSingular;
                    unset($url[0]);
                } else {
                    $this->render404("Controller [$controllerName] tidak ditemukan.");
                    return;
                }
            }
        }

        $controllerClass = "App\\Controllers\\" . $this->controller;
        if (!class_exists($controllerClass)) {
            $this->render404("Class [$controllerClass] tidak dapat dimuat.");
            return;
        }

        $controllerInstance = new $controllerClass();

        // 2. Method check & Reflection Security Validation
        if (isset($url[1])) {
            $methodName = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$url[1]);

            // Prohibit private, protected, and magic methods starting with '_'
            if ($methodName === '' || str_starts_with($methodName, '_')) {
                $this->render404("Akses ke method [{$url[1]}] tidak diizinkan.");
                return;
            }

            try {
                $reflection = new \ReflectionClass($controllerInstance);
                if ($reflection->hasMethod($methodName)) {
                    $methodRef = $reflection->getMethod($methodName);
                    // Ensure the method is public, non-static, and not a constructor/destructor
                    if ($methodRef->isPublic() && !$methodRef->isStatic() && !$methodRef->isConstructor() && !$methodRef->isDestructor()) {
                        $this->method = $methodName;
                        unset($url[1]);
                    } else {
                        $this->render404("Method [$methodName] tidak dapat diakses.");
                        return;
                    }
                } else {
                    $this->render404("Method [$methodName] tidak ditemukan pada controller [{$this->controller}].");
                    return;
                }
            } catch (\ReflectionException $e) {
                $this->render404("Gagal memvalidasi method pada controller [{$this->controller}].");
                return;
            }
        }

        // 3. Parameters
        $this->params = $url ? array_values($url) : [];

        // 4. Call action
        call_user_func_array([$controllerInstance, $this->method], $this->params);
    }

    /**
     * Parse requested URL from query string or request URI
     */
    protected function parseUrl(): array {
        if (isset($_GET['url']) && trim($_GET['url']) !== '') {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Strip query string
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        $uri = rawurldecode($uri);

        // Strip subfolder prefix if any
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = str_replace('\\', '/', $scriptDir);
        if ($scriptDir !== '/' && $scriptDir !== '' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }

        $uri = trim($uri, '/');
        return $uri !== '' ? explode('/', filter_var($uri, FILTER_SANITIZE_URL)) : [];
    }

    /**
     * Render 404 Not Found Page
     */
    protected function render404(string $message = ''): void {
        http_response_code(404);
        $errorPath = APP_ROOT . '/views/errors/404.php';
        $headerPath = APP_ROOT . '/views/layouts/header.php';
        $navbarPath = APP_ROOT . '/views/layouts/navbar.php';
        $footerPath = APP_ROOT . '/views/layouts/footer.php';

        $title = "404 - Halaman Tidak Ditemukan";
        $data = ['title' => $title, 'message' => $message];
        extract($data);

        if (file_exists($headerPath)) require_once $headerPath;
        if (file_exists($navbarPath)) require_once $navbarPath;
        if (file_exists($errorPath)) {
            require_once $errorPath;
        } else {
            echo '<div class="container py-5 text-center"><h1>404</h1><p>Halaman tidak ditemukan.</p></div>';
        }
        if (file_exists($footerPath)) require_once $footerPath;
        exit;
    }
}
