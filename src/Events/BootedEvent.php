<?php

declare(strict_types=1);

namespace Pollen\Kernel\Events;

use League\Event\HasEventName;
use Pollen\Kernel\ApplicationInterface;

class BootedEvent implements HasEventName
{
    protected ApplicationInterface $app;

    /**
     * @param ApplicationInterface $app
     */
    public function __construct(ApplicationInterface $app)
    {
        $this->app = $app;
    }

    public function eventName(): string
    {
        return 'app.booted';
    }

    /**
     * @return ApplicationInterface
     */
    public function getApp(): ApplicationInterface
    {
        return $this->app;
    }
}