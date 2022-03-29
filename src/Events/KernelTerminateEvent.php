<?php

declare(strict_types=1);

namespace Pollen\Kernel\Events;

use League\Event\HasEventName;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class KernelTerminateEvent implements HasEventName
{
    protected ServerRequestInterface $request;

    protected ResponseInterface $response;

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    public function __construct(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function eventName(): string
    {
        return 'kernel.terminate';
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}