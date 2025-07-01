<?php

namespace App\Factory;

use DreamCommerce\Component\ShopAppstore\Model\Application;

class ApplicationFactory implements ApplicationFactoryInterface
{
    private string $appClientId;
    private string $appSecret;
    private string $appStoreSecret;

    public function __construct(
        string $appClientId,
        string $appSecret,
        string $appStoreSecret
    ) {
        $this->appClientId = $appClientId;
        $this->appSecret = $appSecret;
        $this->appStoreSecret = $appStoreSecret;
    }

    /**
     * Create a new Application instance with app credentials
     */
    public function createApplication(): Application
    {
        return new Application(
            $this->appClientId,
            $this->appSecret,
            $this->appStoreSecret
        );
    }
}
