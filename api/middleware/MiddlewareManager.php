<?php
namespace Middleware;

class MiddlewareManager {
    private $middlewares = [];
    
    public function addMiddleware(MiddlewareInterface $middleware) {
        $this->middlewares[] = $middleware;
    }

    public function handle($request, $callback) {
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            fn ($next, $middleware) => fn ($request) => $middleware->handle($request, $next),
            $callback
        );
        return $pipeline($request);
    }
}
