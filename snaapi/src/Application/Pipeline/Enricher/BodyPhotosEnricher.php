<?php

declare(strict_types=1);

namespace App\Application\Pipeline\Enricher;

use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\EnricherInterface;
use App\Domain\Port\Gateway\MultimediaGatewayInterface;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\BodyTagPicture;
use Psr\Log\LoggerInterface;

/**
 * Enriches the context with photos referenced in the editorial body.
 *
 * Scans BodyTagPicture and BodyTagMembershipCard elements to fetch
 * their associated photo resources from the multimedia gateway.
 */
final readonly class BodyPhotosEnricher implements EnricherInterface
{
    public function __construct(
        private MultimediaGatewayInterface $gateway,
        private LoggerInterface $logger,
    ) {
    }

    public function priority(): int
    {
        return 35;
    }

    public function supports(EditorialContext $context): bool
    {
        return null !== $context->editorial();
    }

    public function enrich(EditorialContext $context): void
    {
        $editorial = $context->editorial();

        if (null === $editorial) {
            return;
        }

        $body = $editorial->body();

        if (null === $body) {
            return;
        }

        $photos = [];

        /** @var BodyTagPicture[] $pictures */
        $pictures = $body->bodyElementsOf(BodyTagPicture::class);

        foreach ($pictures as $picture) {
            $photoId = $picture->id()->id();
            $this->fetchPhoto($photoId, $photos);
        }

        /** @var BodyTagMembershipCard[] $membershipCards */
        $membershipCards = $body->bodyElementsOf(BodyTagMembershipCard::class);

        foreach ($membershipCards as $membershipCard) {
            $photoId = $membershipCard->bodyTagPictureMembership()->id()->id();
            $this->fetchPhoto($photoId, $photos);
        }

        if ([] !== $photos) {
            $context->setBodyPhotos($photos);
        }
    }

    /**
     * @param array<string, mixed> $photos
     */
    private function fetchPhoto(string $photoId, array &$photos): void
    {
        try {
            $photo = $this->gateway->findPhotoById($photoId);

            if (null !== $photo) {
                $photos[$photoId] = $photo;
            }
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to fetch body photo', [
                'photoId' => $photoId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
