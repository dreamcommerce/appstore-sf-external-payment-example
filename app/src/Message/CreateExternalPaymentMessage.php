<?php

namespace App\Message;

use App\Service\Event\AppStoreLifecycleEvent;

class CreateExternalPaymentMessage
{
    private AppStoreLifecycleEvent $event;

    public function __construct(AppStoreLifecycleEvent $event)
    {
        $this->event = $event;
    }

    public function getEvent(): AppStoreLifecycleEvent
    {
        return $this->event;
    }
}

