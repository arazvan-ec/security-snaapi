<?php

declare(strict_types=1);

namespace App\Tests\Unit\Previum\Authenticator;

use App\Previum\Authenticator\PreviumJwtAuthenticator;
use App\Previum\Exception\InsufficientPermissionsException;
use App\Previum\Exception\InvalidTokenException;
use App\Previum\Model\PreviumUser;
use App\Previum\Token\TokenValidatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

#[CoversClass(PreviumJwtAuthenticator::class)]
class PreviumJwtAuthenticatorTest extends TestCase
{
    private TokenValidatorInterface&MockObject $tokenValidator;
    private PreviumJwtAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->tokenValidator = $this->createMock(TokenValidatorInterface::class);
        $this->authenticator = new PreviumJwtAuthenticator($this->tokenValidator);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->tokenValidator, $this->authenticator);
    }

    #[Test]
    public function supportsShouldReturnTrueForPreviumPath(): void
    {
        $request = Request::create('/previum/editorials/123');

        $this->assertTrue($this->authenticator->supports($request));
    }

    #[Test]
    public function supportsShouldReturnTrueForPreviumRootPath(): void
    {
        $request = Request::create('/previum');

        $this->assertTrue($this->authenticator->supports($request));
    }

    #[Test]
    public function supportsShouldReturnFalseForNonPreviumPath(): void
    {
        $request = Request::create('/editorials/123');

        $this->assertFalse($this->authenticator->supports($request));
    }

    #[Test]
    public function supportsShouldReturnFalseForRootPath(): void
    {
        $request = Request::create('/');

        $this->assertFalse($this->authenticator->supports($request));
    }

    #[Test]
    public function authenticateShouldThrowInvalidTokenExceptionWhenNoAuthorizationHeader(): void
    {
        $this->expectException(InvalidTokenException::class);

        $request = Request::create('/previum/editorials/123');

        $this->authenticator->authenticate($request);
    }

    #[Test]
    public function authenticateShouldThrowInvalidTokenExceptionWhenAuthorizationIsNotBearer(): void
    {
        $this->expectException(InvalidTokenException::class);

        $request = Request::create('/previum/editorials/123');
        $request->headers->set('Authorization', 'Basic dXNlcjpwYXNz');

        $this->authenticator->authenticate($request);
    }

    #[Test]
    public function authenticateShouldThrowInvalidTokenExceptionWhenTokenIsEmpty(): void
    {
        $this->expectException(InvalidTokenException::class);

        $request = Request::create('/previum/editorials/123');
        $request->headers->set('Authorization', 'Bearer ');

        $this->authenticator->authenticate($request);
    }

    #[Test]
    public function authenticateShouldThrowInsufficientPermissionsExceptionWhenUserTypeIsNotPrevium(): void
    {
        $this->expectException(InsufficientPermissionsException::class);

        $this->tokenValidator
            ->expects($this->once())
            ->method('validate')
            ->with('valid-token')
            ->willReturn(['sub' => 'user-123', 'user_type' => 'other']);

        $request = Request::create('/previum/editorials/123');
        $request->headers->set('Authorization', 'Bearer valid-token');

        $this->authenticator->authenticate($request);
    }

    #[Test]
    public function authenticateShouldThrowInsufficientPermissionsExceptionWhenUserTypeMissing(): void
    {
        $this->expectException(InsufficientPermissionsException::class);

        $this->tokenValidator
            ->expects($this->once())
            ->method('validate')
            ->with('valid-token')
            ->willReturn(['sub' => 'user-123']);

        $request = Request::create('/previum/editorials/123');
        $request->headers->set('Authorization', 'Bearer valid-token');

        $this->authenticator->authenticate($request);
    }

    #[Test]
    public function authenticateShouldReturnSelfValidatingPassportWithPreviumUserForValidToken(): void
    {
        $payload = ['sub' => 'user-123', 'user_type' => 'previum'];

        $this->tokenValidator
            ->expects($this->once())
            ->method('validate')
            ->with('valid-token')
            ->willReturn($payload);

        $request = Request::create('/previum/editorials/123');
        $request->headers->set('Authorization', 'Bearer valid-token');

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);

        $user = $passport->getUser();
        $this->assertInstanceOf(PreviumUser::class, $user);
        $this->assertSame('user-123', $user->getUserIdentifier());
        /** @var PreviumUser $user */
        $this->assertSame('previum', $user->userType());
    }

    #[Test]
    public function onAuthenticationSuccessShouldReturnNull(): void
    {
        $request = Request::create('/previum/editorials/123');
        $token = $this->createMock(TokenInterface::class);

        $result = $this->authenticator->onAuthenticationSuccess($request, $token, 'previum');

        $this->assertNull($result);
    }

    #[Test]
    public function onAuthenticationFailureShouldReturn401ForInvalidTokenException(): void
    {
        $request = Request::create('/previum/editorials/123');
        $exception = new InvalidTokenException();

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(401, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);
        $decoded = json_decode($content, true);
        $this->assertSame(['errors' => ['Unauthorized']], $decoded);
    }

    #[Test]
    public function onAuthenticationFailureShouldReturn403ForInsufficientPermissionsException(): void
    {
        $request = Request::create('/previum/editorials/123');
        $exception = new InsufficientPermissionsException();

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(403, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);
        $decoded = json_decode($content, true);
        $this->assertSame(['errors' => ['Forbidden: insufficient permissions']], $decoded);
    }
}
