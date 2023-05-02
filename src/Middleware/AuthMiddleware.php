<?php

namespace Com\Pulunomoe\PototGym\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class AuthMiddleware
{
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (empty($_SESSION['user'])) {
            return (new Response())
                ->withHeader('Location', '/logout')
                ->withStatus(302);
        }

        return $handler->handle($request);
    }
}
