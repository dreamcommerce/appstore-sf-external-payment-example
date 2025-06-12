<?php

namespace App\Repository;

use App\Entity\ShopInstalled;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ShopInstalledRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopInstalled::class);
    }

    public function save(ShopInstalled $shopInstalled, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($shopInstalled);
        if ($flush) {
            $em->flush();
        }
    }
}

