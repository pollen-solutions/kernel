<?php

declare(strict_types=1);

namespace Pollen\Kernel\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface HttpKernelInterface
{
    /**
     * Handles HTTP Request.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface;

    /**
     * Send HTTP request.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function send(ResponseInterface $response): ResponseInterface;

    /**
     * Terminate HTTP request and response cycle.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function terminate(ServerRequestInterface $request, ResponseInterface $response): void;
}
