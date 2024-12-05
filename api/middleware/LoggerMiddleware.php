<?php
namespace Middleware;

class LoggerMiddleware implements MiddlewareInterface {
    public function handle($request, $next) {
        $log = date('Y-m-d H:i:s') . " - " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . "\n";
        file_put_contents(__DIR__ . '/../logs/api.log', $log, FILE_APPEND);
        return $next($request);
    }
}
