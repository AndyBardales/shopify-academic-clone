<?php
namespace ShopCMS\Core;

class Router {
    private static array $routes = [];

    public static function get(string $path, $handler): void {
        self::$routes['GET'][$path] = $handler;
    }

    public static function post(string $path, $handler): void {
        self::$routes['POST'][$path] = $handler;
    }

    public static function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        
        // Determinar la ruta solicitada
        $route = $_GET['route'] ?? '';
        $route = '/' . trim($route, '/');
        if ($route === '') $route = '/';

        // Buscar coincidencia exacta
        if (isset(self::$routes[$method][$route])) {
            Security::enforcePostCsrf($route);
            self::callHandler(self::$routes[$method][$route]);
            return;
        }

        // Buscar coincidencia con parámetros (ej. /product/{slug})
        foreach (self::$routes[$method] ?? [] as $pattern => $handler) {
            $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $pattern);
            $regex = "#^" . $regex . "$#";

            if (preg_match($regex, $route, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                Security::enforcePostCsrf($route);
                self::callHandler($handler, $params);
                return;
            }
        }

        // Ruta no encontrada 404
        http_response_code(404);
        echo "<h1 style='font-family:sans-serif; text-align:center; margin-top:50px;'>404 - Página no encontrada</h1>";
    }

    private static function callHandler($handler, array $params = []): void {
        $positionalArgs = array_values($params);
        if (is_callable($handler)) {
            call_user_func_array($handler, $positionalArgs);
        } elseif (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();
            call_user_func_array([$controller, $method], $positionalArgs);
        }
    }
}

