<?php

declare(strict_types=1);

namespace Pollen\Kernel\Events;

use League\Event\HasEventName;

class LocaleUpdateEvent implements HasEventName
{
    public string $locale;

    /**
     * @param string $locale
     */
    public function __construct(string $locale)
    {
        $this->locale = $locale;
    }

    public function eventName(): string
    {
        return 'locale.update';
    }
}