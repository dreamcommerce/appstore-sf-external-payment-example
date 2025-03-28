<?php

declare(strict_types=1);

namespace App\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class IframeAuthentication extends AbstractAuthenticator
{
    public function __construct(
        private readonly HashValidator $authenticationKeyService,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $receivedParams = [];
        // Check if the request is a POST request for events callbacks
        if ($request->request->get('shop')) {
            $receivedParams = $request->request->all();
        }

        // Check if the request is a GET request for the iframe
        if ($request->query->get('shop')) {
            $receivedParams = [
                'place' => $request->query->get('place'),
                'shop' => $request->query->get('shop'),
                'timestamp' => $request->query->get('timestamp'),
                'hash' => $request->query->get('hash')
            ];
        }

        $this->guardAgainstInvalidParams($receivedParams);
        $isHashCorrect = $this->authenticationKeyService->isValid(requestHashParams: $receivedParams);

        if (!$isHashCorrect) {
            throw new AuthenticationException('Invalid hash comparison');
        }

        return new SelfValidatingPassport(new UserBadge(userIdentifier: $receivedParams['shop']));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response($exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }

    private function guardAgainstInvalidParams(array $receivedParams): void
    {
        if (!isset($receivedParams['hash'], $receivedParams['shop'])) {
            throw new AuthenticationException('Missing authentication credentials.');
        }

        if (!$receivedParams['hash'] || !$receivedParams['shop']) {
            throw new AuthenticationException('Missing authentication credentials.');
        }
    }
}
