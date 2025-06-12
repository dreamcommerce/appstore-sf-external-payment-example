<?php

namespace App\Service\OAuth\Factory;

use DreamCommerce\Component\ShopAppstore\Model\Application;

interface ApplicationFactoryInterface
{
    /**
     * Create a new Application instance with app credentials
     */
    public function createApplication(): Application;
}
