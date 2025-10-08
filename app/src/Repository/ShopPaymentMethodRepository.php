<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ShopAppInstallation;
use App\Entity\ShopPaymentMethod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class ShopPaymentMethodRepository extends ServiceEntityRepository implements ShopPaymentMethodRepositoryInterface
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

    public function findActiveOneByShopAndPaymentMethodId(ShopAppInstallation $shop, int $paymentMethodId): ?ShopPaymentMethod
    {
        return $this->createQueryBuilder('spm')
            ->where('spm.shop = :shop')
            ->andWhere('spm.paymentMethodId = :paymentMethodId')
            ->andWhere('spm.removedAt IS NULL')
            ->setParameter('shop', $shop)
            ->setParameter('paymentMethodId', $paymentMethodId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
