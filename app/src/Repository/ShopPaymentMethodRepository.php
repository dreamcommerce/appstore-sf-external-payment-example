<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ShopAppInstallation;
use App\Entity\ShopPaymentMethod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ShopPaymentMethodRepository extends ServiceEntityRepository implements ShopPaymentMethodRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopPaymentMethod::class);
    }

    public function save(ShopPaymentMethod $shopPaymentMethod, bool $flush = true): void
    {
        $this->getEntityManager()->persist($shopPaymentMethod);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ShopPaymentMethod $shopPaymentMethod, bool $flush = true): void
    {
        $shopPaymentMethod->setRemovedAt(new \DateTimeImmutable());
        $this->getEntityManager()->persist($shopPaymentMethod);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findActiveOneByShopAndPaymentMethodId(ShopAppInstallation $shop, string $paymentMethodId): ?ShopPaymentMethod
    {
        return $this->findOneBy([
            'shop' => $shop,
            'paymentMethodId' => $paymentMethodId,
            'removedAt' => null
        ]);
    }
}
