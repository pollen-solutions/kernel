<?php

declare(strict_types=1);

namespace Pollen\Kernel;

use Pollen\Http\RequestInterface;
use Pollen\Http\ResponseInterface;

interface KernelInterface
{
    /**
     * Get application instance.
     *
     * @return ApplicationInterface
     */
    public function getApp(): ApplicationInterface;

    /**
     * Handles HTTP Request.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(RequestInterface $request): ResponseInterface;

    /**
     * Send HTTP request.
     *
     * @param ResponseInterface $response
     *
     * @return bool
     */
    public function send(ResponseInterface $response): bool;

    /**
     * Terminate HTTP request and response cycle.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function terminate(RequestInterface $request, ResponseInterface $response): void;
}
