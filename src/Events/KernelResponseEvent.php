<?php

declare(strict_types=1);

namespace Pollen\Kernel\Events;

use League\Event\HasEventName;
use Psr\Http\Message\ResponseInterface;

class KernelResponseEvent implements HasEventName
{
    protected ResponseInterface $response;

    /**
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function eventName(): string
    {
        return 'kernel.response';
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}