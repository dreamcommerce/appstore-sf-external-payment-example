<?php

namespace App\Controller;

use App\Security\HashValidator;
use App\Service\AppStoreEventProcessor;
use App\Service\Event\AppStoreLifecycleAction;
use App\Service\Event\AppStoreLifecycleEvent;
use App\Service\Event\AppStoreLifecycleTrial;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
readonly class AppStoreEventLifecycleController
{
    public function __construct(
        private AppStoreEventProcessor $appstoreEventProcessor,
    ) {
    }

    /** The path is defined in the app configuration in the devshop or admin panel. */
    #[Route('/app-store/event', methods: ['POST'])]
    public function handeAppStoreEvent(Request $request): JsonResponse
    {
        $this->appstoreEventProcessor->handleEvent(
            new AppStoreLifecycleEvent(
                action: AppStoreLifecycleAction::from($request->request->get('action')),
                applicationCode: $request->request->get('application_code'),
                version: (int) $request->request->get('application_version'),
                authCode: $request->request->get('auth_code'),
                shopId: $request->request->get('shop'),
                shopUrl: $request->request->get('shop_url'),
                trial: AppStoreLifecycleTrial::from((int) $request->request->get('trial')),
                hash: $request->request->get('hash'),
            )
        );

        return new JsonResponse();
    }
}