<?php

declare(strict_types=1);

namespace Pollen\Kernel\Http;

use Laminas\Diactoros\Response;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Pollen\Kernel\Events\KernelRequestEvent;
use Pollen\Kernel\Events\KernelResponseEvent;
use Pollen\Kernel\Events\KernelTerminateEvent;
use Pollen\Support\Exception\ManagerRuntimeException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class HttpKernel implements HttpKernelInterface
{
    private static ?HttpKernelInterface $instance = null;

    protected EventDispatcherInterface $eventDispatcher;

    protected RequestHandlerInterface $requestHandler;

    protected EmitterInterface $responseHandler;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param RequestHandlerInterface $requestHandler
     * @param EmitterInterface|null $responseHandler
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        RequestHandlerInterface $requestHandler,
        ?EmitterInterface $responseHandler = null
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->requestHandler = $requestHandler;
        $this->responseHandler = $responseHandler ?? new SapiEmitter();

        if (!self::$instance instanceof static) {
            self::$instance = $this;
        }
    }

    /**
     * Get kernel main instance.
     *
     * @return static
     */
    public static function getInstance(): HttpKernelInterface
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }
        throw new ManagerRuntimeException(sprintf('Unavailable [%s] instance', __CLASS__));
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var KernelRequestEvent $event */
        $event = $this->eventDispatcher->dispatch(new KernelRequestEvent($request));

        $request = $event->getRequest();

        return $this->requestHandler->handle($request);
    }

    /**
     * @inheritDoc
     */
    public function send(ResponseInterface $response): ResponseInterface
    {
        $this->eventDispatcher->dispatch(new KernelResponseEvent($response));

        $this->responseHandler->emit($response);

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function terminate(ServerRequestInterface $request, ResponseInterface $response): void
    {
        $this->eventDispatcher->dispatch(new KernelTerminateEvent($request, $response));

        exit;
    }
}
