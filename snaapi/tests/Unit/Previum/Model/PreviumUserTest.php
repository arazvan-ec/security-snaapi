<?php

declare(strict_types=1);

namespace App\Tests\Unit\Previum\Model;

use App\Previum\Model\PreviumUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

#[CoversClass(PreviumUser::class)]
class PreviumUserTest extends TestCase
{
    private const IDENTIFIER = 'user-123';
    private const USER_TYPE = 'previum';

    private PreviumUser $user;

    protected function setUp(): void
    {
        $this->user = new PreviumUser(self::IDENTIFIER, self::USER_TYPE);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->user);
    }

    #[Test]
    public function getRolesShouldReturnRolePrevium(): void
    {
        $this->assertSame(['ROLE_PREVIUM'], $this->user->getRoles());
    }

    #[Test]
    public function getUserIdentifierShouldReturnTheIdentifier(): void
    {
        $this->assertSame(self::IDENTIFIER, $this->user->getUserIdentifier());
    }

    #[Test]
    public function userTypeShouldReturnTheUserType(): void
    {
        $this->assertSame(self::USER_TYPE, $this->user->userType());
    }

    #[Test]
    public function eraseCredentialsShouldNotThrow(): void
    {
        $this->user->eraseCredentials();

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function shouldImplementUserInterface(): void
    {
        $this->assertInstanceOf(UserInterface::class, $this->user);
    }
}
