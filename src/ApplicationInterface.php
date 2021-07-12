<?php

declare(strict_types=1);

namespace Pollen\Kernel;

use Pollen\Asset\AssetManagerInterface;
use Pollen\Config\ConfiguratorInterface;
use Pollen\Container\ContainerInterface;
use Pollen\Container\ServiceProviderInterface;
use Pollen\Cookie\CookieJarInterface;
use Pollen\Database\DatabaseManagerInterface;
use Pollen\Debug\DebugManagerInterface;
use Pollen\Encryption\EncrypterInterface;
use Pollen\Event\EventDispatcherInterface;
use Pollen\Faker\FakerInterface;
use Pollen\Field\FieldManagerInterface;
use Pollen\Filesystem\StorageManagerInterface;
use Pollen\Form\FormManagerInterface;
use Pollen\Http\RequestInterface;
use Pollen\Log\LogManagerInterface;
use Pollen\Mail\MailManagerInterface;
use Pollen\Metabox\MetaboxManagerInterface;
use Pollen\Partial\PartialManagerInterface;
use Pollen\Routing\RouterInterface;
use Pollen\Session\SessionManagerInterface;
use Pollen\Support\Concerns\BuildableTraitInterface;
use Pollen\Validation\ValidatorInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @property-read AssetManagerInterface asset
 * @property-read ConfiguratorInterface config
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
interface ApplicationInterface extends BuildableTraitInterface, ContainerInterface
{
    /**
     * Booting.
     *
     * @return void
     */
    public function boot(): void;

    /**
     * Initialization.
     *
     * @return ApplicationInterface
     */
    public function build(): ApplicationInterface;

    /**
     * Get the path of application. Optionally a subdirectory or file included.
     *
     * @param string|null $path
     *
     * @return string
     */
    public function getBasePath(?string $path = null): string;

    /**
     * Get the public path of application. Optionally a subdirectory or file included.
     *
     * @param string|null $path
     *
     * @return string
     */
    public function getPublicPath(?string $path = null): string;

    /**
     * Get list of service providers served by application.
     *
     * @return ServiceProviderInterface[]|array
     */
    public function getServiceProviders(): array;

    /**
     * Get version number of application.
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Register aliases of services provided by application.
     *
     * @return void
     */
    public function registerAliases(): void;

    /**
     * Check if the application is running in the console.
     *
     * @return bool
     */
    public function runningInConsole(): bool;

    /**
     * Set configuration parameters.
     *
     * @param array $configParams
     *
     * @return static
     */
    public function setConfigParams(array $configParams): ApplicationInterface;
}