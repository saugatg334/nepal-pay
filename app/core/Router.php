<?php
/**
 * NepalPay Router
 * Simple routing system
 */

class Router {
    private $routes = [];
    private $basePath = '';
    
    public function __construct($routes, $basePath = '') {
        $this->routes = $routes;
        $this->basePath = $basePath;
    }
    
    /**
     * Dispatch the request to appropriate controller/method
     */
    public function dispatch() {
        $uri = $this->getUri();
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Try exact match first
        $key = "{$method} {$uri}";
        
        if (isset($this->routes[$key])) {
            return $this->execute($this->routes[$key]);
        }
        
        // Try route patterns
        foreach ($this->routes as $route => $handler) {
            $pattern = $this->convertToRegex($route);
            if (preg_match($pattern, "{$method} {$uri}", $matches)) {
                array_shift($matches);
                return $this->execute($handler, $matches);
            }
        }
        
        // 404 Not Found
        $this->notFound();
    }
    
    /**
     * Get clean URI
     */
    private function getUri() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = str_replace($this->basePath, '', $uri);
        return trim($uri, '/');
    }
    
    /**
     * Convert route to regex
     */
    private function convertToRegex($route) {
        $route = preg_replace('/\//', '\\/', $route);
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<$1>[a-z-]+)', $route);
        $route = preg_replace('/\{([a-z]+)\}/', '([^/]+)', $route);
        return "/^{$route}$/";
    }
    
    /**
     * Execute controller/method
     */
    private function execute($handler, $params = []) {
        list($controllerName, $method) = $handler;
        
        // Load required files
        $this->loadController($controllerName);
        
        $controller = new $controllerName();
        
        if (!method_exists($controller, $method)) {
            $this->notFound();
        }
        
        return call_user_func_array([$controller, $method], $params);
    }
    
    /**
     * Load controller
     */
    private function loadController($name) {
        $file = __DIR__ . '/../app/controllers/' . $name . '.php';
        
        if (!file_exists($file)) {
            throw new Exception("Controller not found: {$name}");
        }
        
        require_once $file;
    }
    
    /**
     * Handle 404
     */
    private function notFound() {
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
        exit;
    }
}

/**
 * Simple View Renderer
 */
class View {
    static function render($view, $data = []) {
        extract($data);
        
        $viewFile = __DIR__ . '/../app/views/' . $view . '.php';
        
        if (!file_exists($viewFile)) {
            throw new Exception("View not found: {$view}");
        }
        
        require $viewFile;
    }
    
    static function layout($layout, $data = []) {
        extract($data);
        
        $layoutFile = __DIR__ . '/../app/views/layouts/' . $layout . '.php';
        
        if (!file_exists($layoutFile)) {
            throw new Exception("Layout not found: {$layout}");
        }
        
        require $layoutFile;
    }
}
