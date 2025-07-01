<?php

namespace App\Factory;

use DreamCommerce\Component\ShopAppstore\Model\Application;

interface ApplicationFactoryInterface
{
    /**
     * Create a new Application instance with app credentials
     */
    public function createApplication(): Application;
}
