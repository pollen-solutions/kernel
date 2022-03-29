<?php

declare(strict_types=1);

namespace Pollen\Kernel;

use Pollen\Asset\AssetManagerInterface;
use Pollen\Config\ConfiguratorInterface;
use Pollen\Container\ServiceProviderInterface;
use Pollen\Database\DatabaseManagerInterface;
use Pollen\Debug\DebugManagerInterface;
use Pollen\Event\EventDispatcherInterface;
use Pollen\Faker\FakerInterface;
use Pollen\Http\RequestInterface;
use Pollen\Log\LogManagerInterface;
use Pollen\Routing\RouterInterface;
use Pollen\Support\Concerns\BuildableTraitInterface;
use Pollen\Validation\ValidatorInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;

/**
 * @property-read AssetManagerInterface asset
 * @property-read ConfiguratorInterface config
 * @property-read DatabaseManagerInterface database
 * @property-read DatabaseManagerInterface db
 * @property-read DebugManagerInterface debug
 * @property-read EventDispatcherInterface event
 * @property-read FakerInterface faker
 * @property-read LogManagerInterface log
 * @property-read RequestInterface request
 * @property-read RouterInterface router
 * @property-read ServerRequestInterface psr_request
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
     * Get service provides by dependency injection container.
     *
     * @param string $id
     * @param bool $new
     *
     * @return mixed
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function get(string $id, bool $new = false);

    /**
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id): bool;

    /**
     * Try to resolve a service provides by dependency injection container.
     *
     * @param string $id
     *
     * @return mixed|null
     */
    public function resolve(string $id);

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
     * Check if the application is running in the console.
     *
     * @return bool
     */
    public function runningInConsole(): bool;

    /**
     * Check if in debug mode.
     *
     * @return bool
     */
    public function inDebug(): bool;

    /**
     * Get the current application locale.
     *
     * @return string
     */
    public function getLocale(): ?string;

    /**
     * Set the current application locale.
     *
     * @param  string $locale
     *
     * @return void
     */
    public function setLocale(string $locale): void;

    /**
     * Determine if the application locale is the given locale.
     *
     * @param  string $locale
     *
     * @return bool
     */
    public function isLocale(string $locale): bool;
}