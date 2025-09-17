<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\ShopAppInstallation;
use App\Repository\ShopAppInstallationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShopAppInstallationRepositoryTest extends TestCase
{
    private ShopAppInstallationRepository $repository;
    private MockObject $registry;
    private MockObject $entityManager;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->repository = new class($this->registry) extends ShopAppInstallationRepository {
            public function findOneBy(array $criteria, array $orderBy = null): ?object
            {
                // Mock implementation for testing
                if (isset($criteria['shopUrl']) && $criteria['shopUrl'] === 'test-shop.myshopify.com') {
                    return new ShopAppInstallation();
                }
                return null;
            }
        };
    }

    public function testFindOneByShopUrl(): void
    {
        // Create a mock ShopAppInstallation
        $shopInstallation = $this->createMock(ShopAppInstallation::class, 
            ['getShopUrl'], 
            ['test-shop', 'test-shop.myshopify.com', 1, 'test-auth-code']
        );
        
        // Set up the repository to return our mock
        $this->repository = new class($this->registry, $shopInstallation) extends ShopAppInstallationRepository {
            private $mockShopInstallation;
            
            public function __construct($registry, $mockShopInstallation)
            {
                parent::__construct($registry);
                $this->mockShopInstallation = $mockShopInstallation;
            }
            
            public function findOneBy(array $criteria, array $orderBy = null): ?object
            {
                if (isset($criteria['shopUrl']) && $criteria['shopUrl'] === 'test-shop.myshopify.com') {
                    return $this->mockShopInstallation;
                }
                return null;
            }
        };
        
        // Test when shop is found
        $result = $this->repository->findOneByShopUrl('test-shop.myshopify.com');
        $this->assertInstanceOf(ShopAppInstallation::class, $result);
        
        // Test when shop is not found
        $result = $this->repository->findOneByShopUrl('nonexistent-shop.myshopify.com');
        $this->assertNull($result);
    }
    
    public function testFindOneByShopLicense(): void
    {
        // Create a mock ShopAppInstallation
        $shopInstallation = $this->createMock(ShopAppInstallation::class, 
            ['getShop'], 
            ['test-shop', 'test-shop.myshopify.com', 1, 'test-auth-code']
        );
        
        // Set up the repository to return our mock
        $this->repository = new class($this->registry, $shopInstallation) extends ShopAppInstallationRepository {
            private $mockShopInstallation;
            
            public function __construct($registry, $mockShopInstallation)
            {
                parent::__construct($registry);
                $this->mockShopInstallation = $mockShopInstallation;
            }
            
            public function findOneBy(array $criteria, array $orderBy = null): ?object
            {
                if (isset($criteria['shop']) && $criteria['shop'] === 'test-license') {
                    return $this->mockShopInstallation;
                }
                return null;
            }
        };
        
        // Test when shop is found by license
        $result = $this->repository->findOneByShopLicense('test-license');
        $this->assertInstanceOf(ShopAppInstallation::class, $result);
        
        // Test when shop is not found by license
        $result = $this->repository->findOneByShopLicense('nonexistent-license');
        $this->assertNull($result);
    }
}
