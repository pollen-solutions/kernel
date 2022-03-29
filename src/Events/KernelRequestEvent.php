<?php

declare(strict_types=1);

namespace Pollen\Kernel\Events;

use League\Event\HasEventName;
use Psr\Http\Message\ServerRequestInterface;

class KernelRequestEvent implements HasEventName
{
    protected ServerRequestInterface $request;

    /**
     * @param ServerRequestInterface $request
     */
    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    public function eventName(): string
    {
        return 'kernel.request';
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return void
     */
    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }
}