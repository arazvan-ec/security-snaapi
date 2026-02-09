<?php

declare(strict_types=1);

namespace App\Application\Pipeline\Enricher;

use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\EnricherInterface;
use App\Domain\Port\Gateway\MembershipGatewayInterface;
use App\Infrastructure\Enum\SitesEnum;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\MembershipCardButton;
use Psr\Http\Message\UriFactoryInterface;

/**
 * Enriches the context with membership link data.
 */
final readonly class MembershipEnricher implements EnricherInterface
{
    public function __construct(
        private MembershipGatewayInterface $gateway,
        private UriFactoryInterface $uriFactory,
    ) {
    }

    public function priority(): int
    {
        return 50;
    }

    public function supports(EditorialContext $context): bool
    {
        $editorial = $context->editorial();

        if (null === $editorial) {
            return false;
        }

        $membershipCards = $editorial->body()->bodyElementsOf(BodyTagMembershipCard::class);

        return [] !== $membershipCards;
    }

    public function enrich(EditorialContext $context): void
    {
        $editorial = $context->editorial();
        $section = $context->section();

        if (null === $editorial || null === $section) {
            return;
        }

        $links = $this->extractMembershipLinks($editorial->body());

        if ([] === $links) {
            return;
        }

        $uris = array_map(
            fn (string $link) => $this->uriFactory->createUri($link),
            $links
        );

        $siteName = SitesEnum::getEncodenameById($section->siteId());
        $resolvedLinks = $this->gateway->getMembershipUrls(
            $editorial->id()->id(),
            $uris,
            $siteName
        );

        if ([] !== $resolvedLinks) {
            $context->setMembershipLinks(array_combine($links, $resolvedLinks));
        }
    }

    /**
     * @return string[]
     */
    private function extractMembershipLinks(\Ec\Editorial\Domain\Model\Body\Body $body): array
    {
        $links = [];

        /** @var BodyTagMembershipCard[] $membershipCards */
        $membershipCards = $body->bodyElementsOf(BodyTagMembershipCard::class);

        foreach ($membershipCards as $card) {
            /** @var MembershipCardButton $button */
            foreach ($card->buttons()->buttons() as $button) {
                $links[] = $button->urlMembership();
                $links[] = $button->url();
            }
        }

        return array_filter($links);
    }
}
