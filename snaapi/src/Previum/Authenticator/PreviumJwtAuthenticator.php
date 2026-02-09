<?php

declare(strict_types=1);

namespace App\Previum\Authenticator;

use App\Previum\Exception\InsufficientPermissionsException;
use App\Previum\Exception\InvalidTokenException;
use App\Previum\Model\PreviumUser;
use App\Previum\Token\TokenValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class PreviumJwtAuthenticator extends AbstractAuthenticator
{
    private const PREVIUM_USER_TYPE = 'previum';

    public function __construct(
        private readonly TokenValidatorInterface $tokenValidator,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return str_starts_with($request->getPathInfo(), '/previum');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');

        if (null === $authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            throw new InvalidTokenException();
        }

        $token = substr($authHeader, 7);

        if ('' === $token) {
            throw new InvalidTokenException();
        }

        $payload = $this->tokenValidator->validate($token);

        $userType = $payload['user_type'] ?? null;

        if (self::PREVIUM_USER_TYPE !== $userType) {
            throw new InsufficientPermissionsException();
        }

        $identifier = $payload['sub'] ?? $payload['user_id'] ?? 'previum-user';

        return new SelfValidatingPassport(
            new UserBadge(
                (string) $identifier,
                fn () => new PreviumUser((string) $identifier, (string) $userType),
            ),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $statusCode = $exception instanceof InsufficientPermissionsException
            ? Response::HTTP_FORBIDDEN
            : Response::HTTP_UNAUTHORIZED;

        return new JsonResponse(
            ['errors' => [$exception->getMessageKey()]],
            $statusCode,
        );
    }
}
