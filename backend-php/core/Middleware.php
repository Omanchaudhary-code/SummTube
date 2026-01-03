<?php

namespace Core;

/**
 * Base Middleware Class
 * All middleware should extend this
 */
abstract class Middleware
{
    /**
     * Handle the request
     * 
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return mixed
     */
    abstract public function handle(Request $request, Response $response, callable $next);
}