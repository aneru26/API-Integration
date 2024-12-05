<?php
namespace Middleware;

class AuthMiddleware implements MiddlewareInterface {
    public function handle($request, $next) {
        session_start();
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }
        return $next($request);
    }
}
