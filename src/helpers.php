<?php

use Illuminate\Database\Query\Builder as QueryBuilder;
use Pollen\Asset\AssetManagerInterface;
use Pollen\Config\ConfiguratorInterface;
use Pollen\Database\DatabaseManagerInterface;
use Pollen\Event\EventDispatcherInterface;
use Pollen\Field\FieldDriverInterface;
use Pollen\Field\FieldManagerInterface;
use Pollen\Filesystem\FilesystemInterface;
use Pollen\Filesystem\StorageManagerInterface;
use Pollen\Kernel\ApplicationInterface;
use Pollen\Kernel\Kernel;
use Pollen\Partial\PartialDriverInterface;
use Pollen\Partial\PartialManagerInterface;
use Pollen\Http\RequestInterface;
use Pollen\Form\FormManagerInterface;
use Pollen\Form\FormInterface;
use Pollen\Log\LogManagerInterface;
use Pollen\Routing\RouterInterface;
use Pollen\Support\Env;
use Pollen\Validation\ValidatorInterface;
use Pollen\View\ViewManagerInterface;
use Pollen\View\ViewInterface;

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
        $app = Kernel::getInstance()->getApp();

        if ($serviceAlias === null) {
            return $app;
        }

        return $app->get($serviceAlias);
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
     * @return mixed
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

if (!function_exists('field')) {
    /**
     * Field Manager instance|Instance of a registered field.
     *
     * @param string|null $alias
     * @param mixed $idOrParams
     * @param array $params
     *
     * @return FieldManagerInterface|FieldDriverInterface|null
     */
    function field(?string $alias = null, $idOrParams = null, array $params = [])
    {
        /* @var FieldManagerInterface $manager */
        $manager = app(FieldManagerInterface::class);

        if (is_null($alias)) {
            return $manager;
        }
        return $manager->get($alias, $idOrParams, $params);
    }
}

if (!function_exists('form')) {
    /**
     * Form Manager instance|Instance of a registered form.
     *
     * @param string|null $name
     *
     * @return FormManagerInterface|FormInterface|null
     */
    function form(?string $name = null)
    {
        /* @var FormManagerInterface $manager */
        $manager = app(FormManagerInterface::class);

        if ($name === null) {
            return $manager;
        }
        return $manager->get($name);
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

if (!function_exists('partial')) {
    /**
     * Partial Manager instance|Instance of a registered partial.
     *
     * @param string|null $alias
     * @param mixed $idOrParams
     * @param array $params
     *
     * @return PartialManagerInterface|PartialDriverInterface|null
     */
    function partial(?string $alias = null, $idOrParams = null, array $params = [])
    {
        /* @var PartialManagerInterface $manager */
        $manager = app(PartialManagerInterface::class);

        if (is_null($alias)) {
            return $manager;
        }
        return $manager->get($alias, $idOrParams, $params);
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

if (!function_exists('storage')) {
    /**
     * Storage Manager instance|Get instance of Filesystem corresponding to a registered disk (aka mounting point).
     *
     * @param string|null $name
     *
     * @return StorageManagerInterface|FilesystemInterface
     */
    function storage(?string $name = null)
    {
        /* @var StorageManagerInterface $manager */
        $manager = app(StorageManagerInterface::class);

        if ($name === null) {
            return $manager;
        }
        return $manager->disk($name);
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

