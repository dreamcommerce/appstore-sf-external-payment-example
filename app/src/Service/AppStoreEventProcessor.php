<?php

namespace App\Service;

use App\Service\Event\AppStoreLifecycleAction;
use App\Service\Event\AppStoreLifecycleEvent;

class AppStoreEventProcessor
{
    public function handleEvent(AppStoreLifecycleEvent $event): void
    {
        if ($event->action === AppStoreLifecycleAction::INSTALL) {
            /**
             * It a unique key which is generated for each application
             *      and is used to obtain the refresh token (required to make shop API requests).
             * You should store it for each installation.
             */
            $authCode = $event->authCode;
        }

        if ($event->action === AppStoreLifecycleAction::UNINSTALL) {
            // Remove the application data from the database.
        }
    }
}