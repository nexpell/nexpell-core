<?php

class Router
{
    private $routes = [];

    public function get($pattern, $callback)
    {
        $this->addRoute('GET', $pattern, $callback);
    }
    
    public function post($pattern, $callback)
    {
        $this->addRoute('POST', $pattern, $callback);
    }

    private function addRoute($method, $pattern, $callback)
    {
        $pattern = trim($pattern, '/');
        $pattern = preg_replace('#:([\w]+)#', '([^/]+)', $pattern);
        $pattern = "#^$pattern$#";
        $this->routes[] = ['method' => $method, 'pattern' => $pattern, 'callback' => $callback];
    }

    public function dispatch($uri, $method)
    {
        $uri = parse_url($uri, PHP_URL_PATH); // Query entfernen

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], trim($uri, '/'), $matches)) {
                call_user_func_array($route['callback'], array_slice($matches, 1));
                return;
            }
        }

        // Keine Route gefunden
        http_response_code(404);
        include_once __DIR__ . '/../../includes/themes/404.php'; 
        exit;
    }
}

