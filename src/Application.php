<?php

declare(strict_types=1);

namespace Pollen\Kernel;

use Exception;
use Nette\Schema\Expect;
use Pollen\Asset\AssetManager;
use Pollen\Asset\AssetManagerInterface;
use Pollen\Config\Configurator;
use Pollen\Config\ConfiguratorInterface;
use Pollen\Console\Console;
use Pollen\Console\ConsoleInterface;
use Pollen\Container\BootableServiceProviderInterface;
use Pollen\Container\Container;
use Pollen\Container\ServiceProviderInterface;
use Pollen\Cookie\CookieJar;
use Pollen\Cookie\CookieJarInterface;
use Pollen\Database\DatabaseManager;
use Pollen\Database\DatabaseManagerInterface;
use Pollen\Debug\DebugManager;
use Pollen\Debug\DebugManagerInterface;
use Pollen\Encryption\Encrypter;
use Pollen\Encryption\EncrypterInterface;
use Pollen\Event\EventDispatcher;
use Pollen\Event\EventDispatcherInterface;
use Pollen\Faker\Faker;
use Pollen\Faker\FakerInterface;
use Pollen\Field\FieldManager;
use Pollen\Field\FieldManagerInterface;
use Pollen\Filesystem\StorageManager;
use Pollen\Filesystem\StorageManagerInterface;
use Pollen\Form\FormManager;
use Pollen\Form\FormManagerInterface;
use Pollen\Http\Request;
use Pollen\Http\RequestInterface;
use Pollen\Log\LogManager;
use Pollen\Log\LogManagerInterface;
use Pollen\Mail\MailManager;
use Pollen\Mail\MailManagerInterface;
use Pollen\Metabox\MetaboxManager;
use Pollen\Metabox\MetaboxManagerInterface;
use Pollen\Partial\PartialManager;
use Pollen\Partial\PartialManagerInterface;
use Pollen\Proxy\ProxyManager;
use Pollen\Routing\Router;
use Pollen\Routing\RouterInterface;
use Pollen\Session\SessionManager;
use Pollen\Session\SessionManagerInterface;
use Pollen\Support\Concerns\BuildableTrait;
use Pollen\Support\Env;
use Pollen\Support\Exception\ManagerRuntimeException;
use Pollen\Support\Filesystem as fs;
use Pollen\Support\ProxyResolver;
use Pollen\Validation\Validator;
use Pollen\Validation\ValidatorInterface;
use Pollen\View\ViewManager;
use Pollen\View\ViewManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

/**
 * @property-read AssetManagerInterface asset
 * @property-read ConfiguratorInterface config
 * @property-read ConsoleInterface console
 * @property-read CookieJarInterface cookie
 * @property-read EncrypterInterface crypt
 * @property-read DatabaseManagerInterface database
 * @property-read DatabaseManagerInterface db
 * @property-read DebugManagerInterface debug
 * @property-read EventDispatcherInterface event
 * @property-read FakerInterface faker
 * @property-read FieldManagerInterface field
 * @property-read FormManagerInterface form
 * @property-read KernelInterface kernel
 * @property-read LogManagerInterface log
 * @property-read MailManagerInterface mail
 * @property-read MetaboxManagerInterface metabox
 * @property-read PartialManagerInterface partial
 * @property-read RequestInterface request
 * @property-read RouterInterface router
 * @property-read ServerRequestInterface psr_request
 * @property-read SessionManagerInterface session
 * @property-read StorageManagerInterface storage
 * @property-read ValidatorInterface validator
 */
class Application extends Container implements ApplicationInterface
{
    use BuildableTrait;

    /**
     * Application main instance.
     * @var static|null
     */
    private static ?ApplicationInterface $instance = null;

    /**
     * Current application version number.
     * @var string
     */
    protected const VERSION = '1.0.0';

    /**
     * Application base path.
     * @var string
     */
    protected string $basePath;

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

        parent::__construct();
        
        Env::enableGlobal($_ENV['USE_GLOBAL_ENV'] ?? false);
        
        $this->preBuild();
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

            $this->envLoad();

            $this->publicDir = Env::get('APP_PUBLIC_DIR', 'public');

            Env::setMergeVars([
                'app' => [
                    'base_dir'   => $this->getBasePath(),
                    'public_dir' => $this->getPublicPath(),
                ],
            ]);

            $this->preBuildKernel();

            $this->preBuilt = true;
        }
    }

    /**
     * Kernel pre-initialization.
     *
     * @return void
     */
    protected function preBuildKernel(): void
    {
        if (!$this->has(KernelInterface::class)) {
            $this->share(KernelInterface::class, new Kernel($this));
        }
    }

    /**
     * @inheritDoc
     */
    public function build(): ApplicationInterface
    {
        if (!$this->isBuilt()) {
            $this->buildConfig();
            $this->buildContainer();
            $this->buildProxies();
            $this->buildServices();
            $this->buildSession();

            $this->setBuilt();
        }

        return $this;
    }

    /**
     * Configuration initialization.
     *
     * @return void
     */
    protected function buildConfig(): void
    {
        $this->share(ConfiguratorInterface::class, $configurator = new Configurator());

        $configurator->addSchema('app_url', Expect::string());

        /** @todo Depuis le framework */
        $configurator->set(
            array_merge(
                [
                    'app_url'  => Env::get('APP_URL'),
                    'timezone' => Env::get('APP_TIMEZONE'),
                ],
                $this->configParams
            )
        );

        mb_internal_encoding($configurator->get('charset', 'UTF-8'));
    }

    /**
     * Initialization of dependencies injection container.
     *
     * @return void
     */
    protected function buildContainer(): void
    {
        $this->enableAutoWiring(true);
        $this->share(ApplicationInterface::class, $this);

        $this->registerAliases();

        foreach ($this->getServiceProviders() as $definition) {
            if (is_string($definition)) {
                try {
                    $serviceProvider = new $definition($this);
                } catch (Exception $e) {
                    throw new RuntimeException(
                        'ServiceProvider [%s] instanciation return exception :%s',
                        $definition,
                        $e->getMessage()
                    );
                }
            } elseif (is_object($definition)) {
                $serviceProvider = $definition;
            } else {
                throw new RuntimeException(
                    'ServiceProvider [%s] type not supported',
                    $definition
                );
            }

            if (!$serviceProvider instanceof ServiceProviderInterface) {
                throw new RuntimeException(
                    'ServiceProvider [%s] must be an instance of %s',
                    $definition,
                    ServiceProviderInterface::class
                );
            }

            $serviceProvider->setContainer($this);
            if ($serviceProvider instanceof BootableServiceProviderInterface) {
                $this->bootableProviders[] = $serviceProvider;
            }
            $this->addServiceProvider($serviceProvider);
        }
    }

    /**
     * Initialization of proxy accessors.
     *
     * @return void
     */
    protected function buildProxies(): void
    {
        ProxyResolver::setContainer($this);

        if (class_exists(ProxyManager::class)) {
            $manager = new ProxyManager([], $this);
            foreach ($this->config->get('proxy', []) as $alias => $proxy) {
                $manager->addProxy($alias, $proxy);
            }

            $manager->enable(ProxyManager::ROOT_NAMESPACE_ANY);
        }
    }

    /**
     * Initialization of services provided by application.
     *
     * @return void
     */
    protected function buildServices(): void
    {
        foreach ($this->bootableProviders as $bootableProvider) {
            $bootableProvider->boot();
        }
    }

    /**
     * Initialization of session.
     *
     * @return void
     */
    protected function buildSession(): void
    {
        try {
            $this->session->start();

            $this->request->setSession($this->session->processor());
        } catch (Throwable $e) {
            unset($e);
        }
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
    public function registerAliases(): void
    {
        foreach (
            [
                ApplicationInterface::class     => [
                    'app',
                    'container',
                    Container::class,
                    ContainerInterface::class,
                ],
                AssetManagerInterface::class    => [
                    'asset',
                    AssetManager::class,
                ],
                ConfiguratorInterface::class    => [
                    'config',
                    Configurator::class,
                ],
                ConsoleInterface::class         => [
                    'console',
                    Console::class,
                ],
                CookieJarInterface::class       => [
                    'cookie',
                    CookieJar::class,
                ],
                DatabaseManagerInterface::class => [
                    'database',
                    'db',
                    DatabaseManager::class,
                ],
                DebugManagerInterface::class    => [
                    'debug',
                    DebugManager::class,
                ],
                EncrypterInterface::class       => [
                    'crypt',
                    Encrypter::class,
                ],
                EventDispatcherInterface::class => [
                    'event',
                    EventDispatcher::class,
                ],
                FakerInterface::class           => [
                    'faker',
                    Faker::class,
                ],
                FieldManagerInterface::class    => [
                    'field',
                    FieldManager::class,
                ],
                FormManagerInterface::class     => [
                    'form',
                    FormManager::class,
                ],
                KernelInterface::class          => [
                    'kernel',
                    Kernel::class,
                ],
                LogManagerInterface::class      => [
                    'log',
                    LogManager::class,
                ],
                MailManagerInterface::class     => [
                    'mail',
                    MailManager::class,
                ],
                MetaboxManagerInterface::class  => [
                    'metabox',
                    MetaboxManager::class,
                ],
                PartialManagerInterface::class  => [
                    'partial',
                    PartialManager::class,
                ],
                RequestInterface::class         => [
                    'request',
                    Request::class,
                ],
                RouterInterface::class          => [
                    'router',
                    Router::class,
                ],
                ServerRequestInterface::class   => [
                    'psr_request',
                ],
                SessionManagerInterface::class  => [
                    'session',
                    SessionManager::class,
                ],
                StorageManagerInterface::class  => [
                    'storage',
                    StorageManager::class,
                ],
                ValidatorInterface::class       => [
                    'validator',
                    Validator::class,
                ],
                ViewManagerInterface::class     => [
                    'view',
                    ViewManager::class,
                ],
            ] as $key => $aliases
        ) {
            foreach ($aliases as $alias) {
                $this->aliases[$alias] = $key;
            }
        }
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
    public function setConfigParams(array $configParams): ApplicationInterface
    {
        $this->configParams = $configParams;

        return $this;
    }
}
