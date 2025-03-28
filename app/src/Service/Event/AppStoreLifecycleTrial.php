<?php

namespace App\Service\Event;

enum AppStoreLifecycleTrial: int
{
    case ON = 1;
    case OFF = 0;
}