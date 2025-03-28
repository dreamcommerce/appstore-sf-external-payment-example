<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AppStoreUserProvider implements UserProviderInterface
{
    public function refreshUser(UserInterface $user): UserInterface
    {
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return AppStoreUser::class === $class;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        /** You can replace this with your own logic to load user by identifier (database for example) */
        return new AppStoreUser($identifier);
    }
}