<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class AppStoreUser implements UserInterface
{
    public function __construct(private readonly string $shopId)
    {
    }

    public function getRoles(): array
    {
        return [];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->shopId;
    }
}