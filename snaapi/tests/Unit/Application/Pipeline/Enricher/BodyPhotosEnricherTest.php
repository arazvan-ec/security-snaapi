<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Pipeline\Enricher;

use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\Enricher\BodyPhotosEnricher;
use App\Domain\Port\Gateway\MultimediaGatewayInterface;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\BodyTagPicture;
use Ec\Editorial\Domain\Model\Body\BodyTagPictureMembership;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(BodyPhotosEnricher::class)]
final class BodyPhotosEnricherTest extends TestCase
{
    private MultimediaGatewayInterface&MockObject $gateway;
    private LoggerInterface&MockObject $logger;
    private BodyPhotosEnricher $enricher;

    protected function setUp(): void
    {
        $this->gateway = $this->createMock(MultimediaGatewayInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->enricher = new BodyPhotosEnricher(
            $this->gateway,
            $this->logger,
        );
    }

    #[Test]
    public function it_has_priority_35(): void
    {
        self::assertSame(35, $this->enricher->priority());
    }

    #[Test]
    public function it_supports_context_with_editorial(): void
    {
        $context = new EditorialContext('123');
        $context->setEditorial($this->createMock(NewsBase::class));

        self::assertTrue($this->enricher->supports($context));
    }

    #[Test]
    public function it_does_not_support_context_without_editorial(): void
    {
        $context = new EditorialContext('123');

        self::assertFalse($this->enricher->supports($context));
    }

    #[Test]
    public function it_enriches_context_with_photos_from_body_tag_pictures(): void
    {
        $pictureId = $this->createMockId('photo-1');
        $picture = $this->createMock(BodyTagPicture::class);
        $picture->method('id')->willReturn($pictureId);

        $body = $this->createMock(Body::class);
        $body->method('bodyElementsOf')->willReturnMap([
            [BodyTagPicture::class, [$picture]],
            [BodyTagMembershipCard::class, []],
        ]);

        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('body')->willReturn($body);

        $photo = $this->createMock(MultimediaPhoto::class);

        $this->gateway->expects(self::once())
            ->method('findPhotoById')
            ->with('photo-1')
            ->willReturn($photo);

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->enricher->enrich($context);

        self::assertSame(['photo-1' => $photo], $context->bodyPhotos());
    }

    #[Test]
    public function it_enriches_context_with_photos_from_membership_cards(): void
    {
        $membershipPictureId = $this->createMockId('membership-photo-1');
        $pictureMembership = $this->createMock(BodyTagPictureMembership::class);
        $pictureMembership->method('id')->willReturn($membershipPictureId);

        $membershipCard = $this->createMock(BodyTagMembershipCard::class);
        $membershipCard->method('bodyTagPictureMembership')->willReturn($pictureMembership);

        $body = $this->createMock(Body::class);
        $body->method('bodyElementsOf')->willReturnMap([
            [BodyTagPicture::class, []],
            [BodyTagMembershipCard::class, [$membershipCard]],
        ]);

        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('body')->willReturn($body);

        $photo = $this->createMock(MultimediaPhoto::class);

        $this->gateway->expects(self::once())
            ->method('findPhotoById')
            ->with('membership-photo-1')
            ->willReturn($photo);

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->enricher->enrich($context);

        self::assertSame(['membership-photo-1' => $photo], $context->bodyPhotos());
    }

    #[Test]
    public function it_enriches_context_with_photos_from_both_types(): void
    {
        $pictureId = $this->createMockId('photo-1');
        $picture = $this->createMock(BodyTagPicture::class);
        $picture->method('id')->willReturn($pictureId);

        $membershipPictureId = $this->createMockId('membership-photo-1');
        $pictureMembership = $this->createMock(BodyTagPictureMembership::class);
        $pictureMembership->method('id')->willReturn($membershipPictureId);

        $membershipCard = $this->createMock(BodyTagMembershipCard::class);
        $membershipCard->method('bodyTagPictureMembership')->willReturn($pictureMembership);

        $body = $this->createMock(Body::class);
        $body->method('bodyElementsOf')->willReturnMap([
            [BodyTagPicture::class, [$picture]],
            [BodyTagMembershipCard::class, [$membershipCard]],
        ]);

        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('body')->willReturn($body);

        $photo1 = $this->createMock(MultimediaPhoto::class);
        $photo2 = $this->createMock(MultimediaPhoto::class);

        $this->gateway->expects(self::exactly(2))
            ->method('findPhotoById')
            ->willReturnMap([
                ['photo-1', $photo1],
                ['membership-photo-1', $photo2],
            ]);

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->enricher->enrich($context);

        self::assertSame([
            'photo-1' => $photo1,
            'membership-photo-1' => $photo2,
        ], $context->bodyPhotos());
    }

    #[Test]
    public function it_returns_early_when_editorial_is_null(): void
    {
        $context = new EditorialContext('123');

        $this->gateway->expects(self::never())->method('findPhotoById');

        $this->enricher->enrich($context);

        self::assertSame([], $context->bodyPhotos());
    }

    #[Test]
    public function it_returns_early_when_body_is_null(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('body')->willReturn(null);

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->gateway->expects(self::never())->method('findPhotoById');

        $this->enricher->enrich($context);

        self::assertSame([], $context->bodyPhotos());
    }

    #[Test]
    public function it_does_not_set_body_photos_when_no_photos_found(): void
    {
        $body = $this->createMock(Body::class);
        $body->method('bodyElementsOf')->willReturnMap([
            [BodyTagPicture::class, []],
            [BodyTagMembershipCard::class, []],
        ]);

        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('body')->willReturn($body);

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->gateway->expects(self::never())->method('findPhotoById');

        $this->enricher->enrich($context);

        self::assertSame([], $context->bodyPhotos());
    }

    #[Test]
    public function it_logs_error_and_skips_when_photo_fetch_fails(): void
    {
        $pictureId1 = $this->createMockId('photo-1');
        $picture1 = $this->createMock(BodyTagPicture::class);
        $picture1->method('id')->willReturn($pictureId1);

        $pictureId2 = $this->createMockId('photo-2');
        $picture2 = $this->createMock(BodyTagPicture::class);
        $picture2->method('id')->willReturn($pictureId2);

        $body = $this->createMock(Body::class);
        $body->method('bodyElementsOf')->willReturnMap([
            [BodyTagPicture::class, [$picture1, $picture2]],
            [BodyTagMembershipCard::class, []],
        ]);

        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('body')->willReturn($body);

        $photo2 = $this->createMock(MultimediaPhoto::class);

        $this->gateway->expects(self::exactly(2))
            ->method('findPhotoById')
            ->willReturnCallback(function (string $id) use ($photo2) {
                if ('photo-1' === $id) {
                    throw new \RuntimeException('Connection timeout');
                }

                return $photo2;
            });

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Failed to fetch body photo', self::callback(
                fn (array $logContext) => 'photo-1' === $logContext['photoId']
                    && 'Connection timeout' === $logContext['error']
            ));

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->enricher->enrich($context);

        self::assertSame(['photo-2' => $photo2], $context->bodyPhotos());
    }

    #[Test]
    public function it_skips_photos_returned_as_null_from_gateway(): void
    {
        $pictureId = $this->createMockId('photo-1');
        $picture = $this->createMock(BodyTagPicture::class);
        $picture->method('id')->willReturn($pictureId);

        $body = $this->createMock(Body::class);
        $body->method('bodyElementsOf')->willReturnMap([
            [BodyTagPicture::class, [$picture]],
            [BodyTagMembershipCard::class, []],
        ]);

        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('body')->willReturn($body);

        $this->gateway->expects(self::once())
            ->method('findPhotoById')
            ->with('photo-1')
            ->willReturn(null);

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->enricher->enrich($context);

        self::assertSame([], $context->bodyPhotos());
    }

    /**
     * Creates a value object stub with an id() method returning the given string.
     *
     * Used to simulate the value object chain: $bodyTag->id()->id().
     */
    private function createMockId(string $id): object
    {
        return new class($id) {
            public function __construct(private readonly string $value)
            {
            }

            public function id(): string
            {
                return $this->value;
            }
        };
    }
}
