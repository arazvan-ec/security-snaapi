<?php

declare(strict_types=1);

namespace App\Previum\Model;

use Symfony\Component\Security\Core\User\UserInterface;

class PreviumUser implements UserInterface
{
    private const ROLE_PREVIUM = 'ROLE_PREVIUM';

    public function __construct(
        private readonly string $identifier,
        private readonly string $userType,
    ) {
    }

    /** @return string[] */
    public function getRoles(): array
    {
        return [self::ROLE_PREVIUM];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function userType(): string
    {
        return $this->userType;
    }
}
