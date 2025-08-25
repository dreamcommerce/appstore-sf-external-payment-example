<?php

namespace App\Repository;

use App\Entity\ShopAppInstallation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ShopAppInstallationRepository extends ServiceEntityRepository implements ShopAppInstallationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopAppInstallation::class);
    }

    public function save(ShopAppInstallation $shopAppInstallation, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($shopAppInstallation);
        if ($flush) {
            $em->flush();
        }
    }

    public function remove(ShopAppInstallation $shopAppInstallation, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->remove($shopAppInstallation);
        if ($flush) {
            $em->flush();
        }
    }
}
