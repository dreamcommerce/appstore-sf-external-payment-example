<?php

namespace App\Service\Event;

readonly class AppStoreLifecycleEvent
{
    public function __construct(
        public AppStoreLifecycleAction $action,
        public string $applicationCode,
        public int $version,
        public ?string $authCode,
        public string $shopId,
        public string $shopUrl,
        public AppStoreLifecycleTrial $trial,
        public string $hash
    )
    {
    }
}