<?php

declare(strict_types=1);

namespace Pollen\Kernel\Events;

use League\Event\HasEventName;
use Pollen\Support\ParamsBag;
use Pollen\Kernel\ApplicationInterface;

class ConfigLoadEvent implements HasEventName
{
    protected ApplicationInterface $app;

    protected ParamsBag $config;

    /**
     * @param ParamsBag $config
     * @param ApplicationInterface $app
     */
    public function __construct(ParamsBag $config, ApplicationInterface $app)
    {
        $this->config = $config;
        $this->app = $app;
    }

    public function eventName(): string
    {
        return 'config.load';
    }

    /**
     * @return ApplicationInterface
     */
    public function getApp(): ApplicationInterface
    {
        return $this->app;
    }

    /**
     * @param string|null $key
     *
     * @return ParamsBag|mixed
     */
    public function getConfig(?string $key = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->config[$key] ?? null;
    }

    /**
     * @param ParamsBag $config
     *
     * @return void
     */
    public function setConfig(ParamsBag $config): void
    {
        $this->config = $config;
    }
}