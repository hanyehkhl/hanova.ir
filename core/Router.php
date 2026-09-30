<?php

class Router
{
    private $basePath;
    private $routes;

    public function __construct()
    {
        $this->basePath = dirname(__DIR__);
        $this->routes = [
            '/' => '/pages/home.html',
            '/home' => '/pages/home.html',
            '/resume' => '/pages/home.html',
            '/upload' => '/pages/dropbox.html',
            '/chat' => '/pages/login.html',
            '/login' => '/pages/login.html',
            '/articles' => '/pages/articles.html',
            '/blog' => '/pages/articles.html',
            '/admin' => '/pages/dashboard.html',
            '/workspace' => '/pages/workspace.html',
        ];
    }

    public function handleRequest()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/');
        $uri = $uri === '' ? '/' : $uri;

        if ($uri === '/dropbox') {
            header('Location: /upload', true, 301);
            return;
        }

        if (isset($this->routes[$uri])) {
            $this->renderPage($this->routes[$uri]);
            return;
        }

        if (strpos($uri, '/api/') === 0) {
            $filePath = $this->basePath . $uri;

            if (is_file($filePath)) {
                require $filePath;
                return;
            }

            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'مسیر پیدا نشد'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (strpos($uri, '/pages/') === 0 || strpos($uri, '/assets/') === 0) {
            return false;
        }

        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo '404 - Page not found';
    }

    private function renderPage($relativePath)
    {
        $filePath = $this->basePath . $relativePath;

        if (!is_file($filePath)) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo '404 - Page not found';
            return;
        }

        header('Content-Type: text/html; charset=utf-8');
        readfile($filePath);
    }
}
