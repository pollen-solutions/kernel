<?php

use Illuminate\Database\Query\Builder as QueryBuilder;
use Pollen\Asset\AssetManagerInterface;
use Pollen\Config\ConfiguratorInterface;
use Pollen\Database\DatabaseManagerInterface;
use Pollen\Event\EventDispatcherInterface;
use Pollen\Kernel\Application;
use Pollen\Kernel\ApplicationInterface;
use Pollen\Http\RequestInterface;
use Pollen\Log\LogManagerInterface;
use Pollen\Routing\RouterInterface;
use Pollen\Support\Env;
use Pollen\Validation\ValidatorInterface;
use Pollen\View\ViewManagerInterface;
use Pollen\View\ViewInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

if (!function_exists('app')) {
    /**
     * App instance|Service provides by the dependency injection container.
     *
     * @param string|null $serviceAlias
     *
     * @return ApplicationInterface|mixed
     */
    function app(?string $serviceAlias = null)
    {
        $app = Application::getInstance();

        if ($serviceAlias === null) {
            return $app;
        }

        try {
            return $app->get($serviceAlias);
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface $e) {
            return null;
        }
    }
}

if (!function_exists('asset')) {
    /**
     * Asset manager instance.
     *
     * @return AssetManagerInterface
     */
    function asset(): AssetManagerInterface
    {
        return app(AssetManagerInterface::class);
    }
}

if (!function_exists('config')) {
    /**
     * Configurator instance|Set config|Get config.
     * {@internal
     * - null $key to get Configurator instance.
     * - array $key to set config attributes.
     * - string $key to get config attribute value.
     * }
     *
     * @param null|array|string $key
     * @param mixed $default
     *
     * @return ConfiguratorInterface|mixed
     */
    function config($key = null, $default = null)
    {
        /* @var ConfiguratorInterface $config */
        $config = app(ConfiguratorInterface::class);

        if (is_null($key)) {
            return $config;
        }
        if (is_array($key)) {
            $config->set($key);

            return $config;
        }
        return $config->get($key, $default);
    }
}

if (!function_exists('database')) {
    /**
     * Database manager instance|Get the query builder associated with a specific table.
     *
     * @param string|null $table
     *
     * @return DatabaseManagerInterface|QueryBuilder|null
     */
    function database(?string $table = null)
    {
        /* @var DatabaseManagerInterface $dbManager */
        $dbManager = app(DatabaseManagerInterface::class);

        if ($table === null) {
            return $dbManager;
        }
        return $dbManager::table($table);
    }
}

if (!function_exists('env')) {
    /**
     * Get global environment variable value.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return bool|string|null
     */
    function env(string $key, $default = null)
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('event')) {
    /**
     * Event dispatcher instance.
     *
     * @return EventDispatcherInterface
     */
    function event(): EventDispatcherInterface
    {
        return app(EventDispatcherInterface::class);
    }
}

if (!function_exists('logger')) {
    /**
     * Logger instance|Set a new log message.
     *
     * @param string|null $message
     * @param array $context
     *
     * @return LogManagerInterface|void
     */
    function logger(?string $message = null, array $context = []): ?LogManagerInterface
    {
        /* @var LogManagerInterface $manager */
        $manager = app(LogManagerInterface::class);

        if ($message === null) {
            return $manager;
        }
        $manager->debug($message, $context);
    }
}

if (!function_exists('request')) {
    /**
     * HTTP Request instance.
     *
     * @return RequestInterface
     */
    function request(): RequestInterface
    {
        return app(RequestInterface::class);
    }
}

if (!function_exists('route')) {
    /**
     * Get route url for a registered route.
     *
     * @param string $name
     * @param array $parameters
     * @param boolean $absolute
     *
     * @return string|null
     */
    function route(string $name, array $parameters = [], bool $absolute = true): ?string
    {
        /* @var RouterInterface $router */
        $router = app(RouterInterface::class);

        return $router->getNamedRouteUrl($name, $parameters, $absolute);
    }
}

if (!function_exists('validator')) {
    /**
     * Validator instance.
     *
     * @return ValidatorInterface
     */
    function validator(): ValidatorInterface
    {
        return app(ValidatorInterface::class);
    }
}

if (!function_exists('view')) {
    /**
     * View instance|Template render
     *
     * @param string|null $name
     * @param array $datas
     *
     * @return ViewManagerInterface|ViewInterface|string
     */
    function view(?string $name = null, array $datas = [])
    {
        /** @var ViewManagerInterface $view */
        $view = app(ViewManagerInterface::class);

        if ($name === null) {
            return $view;
        }
        return $view->render($name, $datas);
    }
}

