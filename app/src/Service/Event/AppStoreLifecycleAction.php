<?php

namespace App\Service\Event;

enum AppStoreLifecycleAction: string
{
    case INSTALL = 'install';
    case UNINSTALL = 'uninstall';
}