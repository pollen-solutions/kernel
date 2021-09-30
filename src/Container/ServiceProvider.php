<?php

declare(strict_types=1);

namespace Pollen\Kernel\Container;

use Pollen\Container\ServiceProvider as BaseServiceProvider;
use Pollen\Kernel\ApplicationInterface;

class ServiceProvider extends BaseServiceProvider
{
    protected ?ApplicationInterface $app = null;

    /**
     * @param ApplicationInterface $app
     */
    public function __construct(ApplicationInterface $app)
    {
        $this->app = $app;
    }
}