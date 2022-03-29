<?php

declare(strict_types=1);

namespace Pollen\Kernel;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Pollen\Asset\AssetManagerInterface;
use Pollen\Config\ConfiguratorInterface;
use Pollen\Console\ConsoleInterface;
use Pollen\Container\BootableServiceProviderInterface;
use Pollen\Container\Container;
use Pollen\Container\ServiceProviderInterface;
use Pollen\Debug\DebugManagerInterface;
use Pollen\Event\EventDispatcherInterface;
use Pollen\Faker\FakerInterface;
use Pollen\Http\RequestInterface;
use Pollen\Kernel\Events\BootedEvent;
use Pollen\Kernel\Events\BootEvent;
use Pollen\Kernel\Events\ConfigLoadedEvent;
use Pollen\Kernel\Events\ConfigLoadEvent;
use Pollen\Kernel\Events\KernelRequestEvent;
use Pollen\Kernel\Events\LocaleUpdateEvent;
use Pollen\Kernel\Http\HttpKernel;
use Pollen\Kernel\Http\HttpKernelInterface;
use Pollen\Log\LogManagerInterface;
use Pollen\Routing\RouterInterface;
use Pollen\Routing\UrlMatcher;
use Pollen\Support\Concerns\BuildableTrait;
use Pollen\Support\Env;
use Pollen\Support\Exception\ManagerRuntimeException;
use Pollen\Support\Filesystem as fs;
use Pollen\Support\ParamsBag;
use Pollen\Validation\ValidatorInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use SplFileInfo;

/**
 * @property-read AssetManagerInterface asset
 * @property-read ConfiguratorInterface config
 * @property-read ConsoleInterface console
 * @property-read DebugManagerInterface debug
 * @property-read EventDispatcherInterface|PsrEventDispatcherInterface event
 * @property-read FakerInterface faker
 * @property-read LogManagerInterface log
 * @property-read RequestInterface request
 * @property-read RouterInterface router
 * @property-read ServerRequestInterface psr_request
 * @property-read ValidatorInterface validator
 */
class Application implements ApplicationInterface
{
    use BuildableTrait;

    /**
     * Current application version number.
     * @var string
     */
    protected const VERSION = '1.0.0';

    /**
     * Application main instance.
     * @var static|null
     */
    private static ?ApplicationInterface $instance = null;

    /**
     * Application base path.
     * @var string
     */
    protected string $basePath;

    /**
     * Dependency injection container.
     * @var ContainerInterface|null
     */
    protected ?ContainerInterface $container = null;

    protected ?ParamsBag $config = null;

    /**
     * List of configuration parameters.
     * @var array
     */
    protected array $configParams = [];

    /**
     * Application subdirectory of public path from base path.
     * @var string
     */
    protected string $publicDir = 'public';

    /**
     * Application public path.
     * @var string|null
     */
    protected ?string $publicPath = null;

    /**
     * Application pre-initialisation indicator.
     * @var bool
     */
    protected bool $preBuilt = false;

    /**
     * Application start microtime value.
     * @var float|null
     */
    protected ?float $startTime = null;

    /**
     * List of services providers served by application.
     * @var ServiceProviderInterface[]
     */
    protected array $serviceProviders = [];

    /**
     * List of bootable service providers.
     * @var BootableServiceProviderInterface[]|array
     */
    protected array $bootableProviders = [];

    /**
     * Application is running in console indicator.
     * @var bool|null
     */
    protected ?bool $isRunningInConsole = null;

    /**
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->basePath = fs::normalizePath($basePath);

        if (!self::$instance instanceof static) {
            self::$instance = $this;
        }

        Env::enableGlobal((bool)($_ENV['USE_GLOBAL_ENV'] ?? false));
        
        $this->preBuild();
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->getContainer()->{$name}(...$arguments);
    }

    /**
     * Get main application instance.
     *
     * @return static
     */
    public static function getInstance(): ApplicationInterface
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }

        throw new ManagerRuntimeException(sprintf('Unavailable [%s] instance', __CLASS__));
    }

    /**
     * Pre-initialization.
     *
     * @return void
     */
    protected function preBuild(): void
    {
        if ($this->preBuilt === false) {
            $this->startTime = defined('START_TIME') ? START_TIME : microtime(true);

            $this->preBuildEnv();
            $this->preBuildConfig();
            $this->preBuildContainer();
            $this->preBuildBootableServices();
            $this->preBuildEvents();

            $this->preBuilt = true;
        }
    }

    /**
     * @return void
     */
    protected function preBuildEnv(): void
    {
        $this->envLoad();

        error_reporting(E_ALL);
        ini_set('display_errors',  $this->inDebug() ? 'on' : 'off');

        $this->publicDir = Env::get('APP_PUBLIC_DIR', 'public');

        Env::setMergeVars([
            'app' => [
                'base_dir'   => $this->getBasePath(),
                'public_dir' => $this->getPublicPath(),
            ],
        ]);
    }

    /**
     * @return void
     */
    protected function preBuildConfig(): void
    {
        if (!$this->config instanceof ParamsBag) {
            $this->config = new ParamsBag();
        }

        $configPath = $this->getBasePath('config');
        foreach (glob($configPath . '/*.php') as $filename) {
            $finfo = new SplFileInfo($filename);
            $key = $finfo->getBasename('.' . $finfo->getExtension());

            $app = $this;
            $this->config[$key] = include $finfo->getRealPath();
        }
    }

    /**
     * @return void
     */
    protected function preBuildContainer(): void
    {
        $container = $this->getContainer();
        $container->add(ApplicationInterface::class, $this);
        $container->add(ContainerInterface::class, $container);
        $container->add(HttpKernelInterface::class, function () {
            return new HttpKernel(
                $this->get(EventDispatcherInterface::class),
                $this->get(RequestHandlerInterface::class),
                $this->get(EmitterInterface::class)
            );
        });

        if (($config = $this->config['container'] ?? null) && is_callable($config)) {
            $config($container, $this);
        }
    }

    /**
     * @return void
     */
    protected function preBuildBootableServices(): void
    {
        $this->getContainer()->bootProviders();
    }

    /**
     * @return void
     */
    protected function preBuildEvents(): void
    {
        /** @var EventDispatcherInterface $eventDispatcher */
        if ($eventDispatcher = $this->resolve(EventDispatcherInterface::class)) {
            $eventDispatcher->subscribeTo('kernel.request', function (KernelRequestEvent $event) {
                $this->build();

                /**  Routes */
                if (
                    ($requestHandler = $this->get(RequestHandlerInterface::class)) &&
                    $requestHandler instanceof RouterInterface
                ) {
                    $matcher = new UrlMatcher($requestHandler);
                    $request = $matcher->handle($event->getRequest());

                    if ($route = $request->getAttribute('_route')) {
                        $requestHandler->setCurrentRoute($route);
                        $event->setRequest($request);
                        $this->getContainer()->extend(ServerRequestInterface::class)->setConcrete($request);
                    }
                }
            });

            $eventDispatcher->subscribeTo('locale.update', function (LocaleUpdateEvent $event) {
                /** @var FakerInterface $faker */
                if ($event->locale && ($faker = $this->resolve(FakerInterface::class))) {
                    $faker->setLocale($event->locale);
                }
            });

            if ($locale = $this->config->get('app.locale')) {
                $eventDispatcher->dispatch(new LocaleUpdateEvent($locale));
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function build(): ApplicationInterface
    {
        if (!$this->isBuilt()) {
            /** @var EventDispatcherInterface $eventDispatcher */
            $eventDispatcher = $this->resolve(EventDispatcherInterface::class);

            if ($eventDispatcher) {
                $eventDispatcher->dispatch(new BootEvent($this));

                $eventDispatcher->dispatch(new ConfigLoadEvent($this->config, $this));

                $eventDispatcher->dispatch(new ConfigLoadedEvent($this->config, $this));
            }

            if (method_exists($this, 'boot')) {
                $this->boot();
            }

            if ($eventDispatcher) {
                $eventDispatcher->dispatch(new BootedEvent($this));
            }

            $this->setBuilt();
        }

        return $this;
    }

    /**
     * @return Container
     */
    private function getContainer(): Container
    {
        if ($this->container === null) {
            $this->container = new Container();
        }
        return $this->container;
    }

    /**
     * @inheritDoc
     */
    public function boot(): void { }

    /**
     * Load global environment variables.
     *
     * @return void
     */
    protected function envLoad(): void
    {
        Env::load($this->getBasePath());
    }

    /**
     * @inheritDoc
     */
    public function get(string $id, bool $new = false)
    {
        $container = $this->getContainer();

        if ($new === true) {
            return $container->getNew($id);
        }
        return $this->getContainer()->get($id);
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return $this->getContainer()->has($id);
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $id)
    {
        if ($this->getContainer()->has($id)) {
            try {
                return $this->getContainer()->get($id);
            } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
                unset($e);
            }
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getBasePath(?string $path = null): string
    {
        return $path ? fs::normalizePath($this->basePath . fs::DS . $path) : $this->basePath;
    }

    /**
     * @inheritDoc
     */
    public function getPublicPath(?string $path = null): string
    {
        if ($this->publicPath === null) {
            $this->publicPath = fs::normalizePath($this->basePath . fs::DS . $this->publicDir);
        }

        return $path ? fs::normalizePath($this->publicPath . fs::DS . $path) : $this->publicPath;
    }

    /**
     * @inheritDoc
     */
    public function getServiceProviders(): array
    {
        return $this->serviceProviders;
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): string
    {
        return static::VERSION;
    }

    /**
     * @inheritDoc
     */
    public function runningInConsole(): bool
    {
        if ($this->isRunningInConsole === null) {
            $this->isRunningInConsole = Env::get(
                    'APP_RUNNING_IN_CONSOLE'
                ) ?? (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
        }

        return $this->isRunningInConsole;
    }

    /**
     * @inheritDoc
     */
    public function inDebug(): bool
    {
        return Env::get('APP_DEBUG', false) || (Env::get('APP_DEBUG') === null && Env::inDev());
    }

    /**
     * @inheritDoc
     */
    public function getLocale(): ?string
    {
        return $this->config->get('app.locale');
    }

    /**
     * @inheritDoc
     */
    public function setLocale(string $locale): void
    {
        $this->config->set('app.locale', $locale);

        /** @var EventDispatcherInterface $eventDispatcher */
        if ($eventDispatcher = $this->resolve(EventDispatcherInterface::class)) {
            $eventDispatcher->dispatch(new LocaleUpdateEvent($locale));
        }
    }

    /**
     * @inheritDoc
     */
    public function isLocale(string $locale): bool
    {
        return $this->getLocale() === $locale;
    }

//    /**
//     * Configuration initialization.
//     *
//     * @return void
//     */
//    protected function buildConfig(): void
//    {
//        $this->share(ConfiguratorInterface::class, $configurator = new Configurator());
//
//        $configurator->addSchema('app_url', Expect::string());
//
//        /** @todo Depuis le framework */
//        $configurator->set(
//            array_merge(
//                [
//                    'app_url'  => Env::get('APP_URL'),
//                    'timezone' => Env::get('APP_TIMEZONE'),
//                ],
//                $this->configParams
//            )
//        );
//
//        mb_internal_encoding($configurator->get('charset', 'UTF-8'));
//    }

//    /**
//     * Initialization of dependencies injection container.
//     *
//     * @return void
//     */
//    protected function buildContainer(): void
//    {
//        $this->enableAutoWiring(true);
//        $this->share(ApplicationInterface::class, $this);
//
//        $this->registerAliases();
//
//        foreach ($this->getServiceProviders() as $definition) {
//            if (is_string($definition)) {
//                try {
//                    $serviceProvider = new $definition($this);
//                } catch (Exception $e) {
//                    throw new RuntimeException(
//                        'ServiceProvider [%s] instanciation return exception :%s',
//                        $definition,
//                        $e->getMessage()
//                    );
//                }
//            } elseif (is_object($definition)) {
//                $serviceProvider = $definition;
//            } else {
//                throw new RuntimeException(
//                    'ServiceProvider [%s] type not supported',
//                    $definition
//                );
//            }
//
//            if (!$serviceProvider instanceof ServiceProviderInterface) {
//                throw new RuntimeException(
//                    'ServiceProvider [%s] must be an instance of %s',
//                    $definition,
//                    ServiceProviderInterface::class
//                );
//            }
//
//            $serviceProvider->setContainer($this);
//            if ($serviceProvider instanceof BootableServiceProviderInterface) {
//                $this->bootableProviders[] = $serviceProvider;
//            }
//            $this->addServiceProvider($serviceProvider);
//        }
//    }

//    /**
//     * Initialization of proxy accessors.
//     *
//     * @return void
//     */
//    protected function buildProxies(): void
//    {
//        ProxyResolver::setContainer($this);
//
//        if (class_exists(ProxyManager::class)) {
//            $manager = new ProxyManager([], $this);
//            foreach ($this->config->get('proxy', []) as $alias => $proxy) {
//                $manager->addProxy($alias, $proxy);
//            }
//
//            $manager->enable(ProxyManager::ROOT_NAMESPACE_ANY);
//        }
//    }

//    /**
//     * Initialization of session.
//     *
//     * @return void
//     */
//    protected function buildSession(): void
//    {
//        try {
//            $this->session->start();
//
//            $this->request->setSession($this->session->processor());
//        } catch (Throwable $e) {
//            unset($e);
//        }
//    }
}
