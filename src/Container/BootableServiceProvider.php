<?php

declare(strict_types=1);

namespace Pollen\Kernel\Container;

use Pollen\Container\BootableServiceProviderInterface;

class BootableServiceProvider extends ServiceProvider implements BootableServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function boot(): void {}
}