<?php

declare(strict_types=1);

namespace Pollen\Kernel;

use Pollen\Http\RequestInterface;
use Pollen\Http\ResponseInterface;
use Pollen\Routing\Router;
use Pollen\Support\Exception\ManagerRuntimeException;
use Pollen\Routing\RouterInterface;
use RuntimeException;
use Throwable;

class Kernel implements KernelInterface
{
    /**
     * Kernel main instance
     * @var KernelInterface|null
     */
    private static ?KernelInterface $instance = null;

    /**
     * Application main instance.
     * @var ApplicationInterface
     */
    protected ApplicationInterface $app;

    /**
     * Router main instance.
     * @var RouterInterface|null
     */
    protected ?RouterInterface $router = null;

    public function __construct(ApplicationInterface $app)
    {
        $this->app = $app;

        if (!self::$instance instanceof static) {
            self::$instance = $this;
        }
    }

    /**
     * Get kernel main instance.
     *
     * @return static
     */
    public static function getInstance(): KernelInterface
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }
        throw new ManagerRuntimeException(sprintf('Unavailable [%s] instance', __CLASS__));
    }

    /**
     * @inheritDoc
     */
    public function getApp(): ApplicationInterface
    {
        if (!$this->app instanceof ApplicationInterface) {
            throw new RuntimeException('Unable to retrieve Application instance');
        }
        return $this->app;
    }

    /**
     * @inheritDoc
     */
    public function handle(RequestInterface $request): ResponseInterface
    {
        if (!$this->app->isBuilt()) {
            $this->app->build();
        }

        if (!$this->app->has(RouterInterface::class)) {
            try {
                $this->app->share(RouterInterface::class, new Router([], $this->app));
            } catch (Throwable $e) {
            }
        }

        $this->router = $this->app->router;

        return $this->router->setHandleRequest($request)->handleRequest();
    }

    /**
     * @inheritDoc
     */
    public function send(ResponseInterface $response): bool
    {
        return $this->router->sendResponse($response);
    }

    /**
     * @inheritDoc
     */
    public function terminate(RequestInterface $request, ResponseInterface $response): void
    {
        $this->router->terminateEvent($request, $response);
    }
}
