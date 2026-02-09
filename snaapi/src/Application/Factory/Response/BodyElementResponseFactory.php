<?php

declare(strict_types=1);

namespace App\Application\Factory\Response;

use App\Application\DTO\Response\BodyElementResponse;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagExplanatorySummary;
use Ec\Editorial\Domain\Model\Body\BodyTagHtml;
use Ec\Editorial\Domain\Model\Body\BodyTagInsertedNews;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\BodyTagPicture;
use Ec\Editorial\Domain\Model\Body\BodyTagPictureMembership;
use Ec\Editorial\Domain\Model\Body\BodyTagSummary;
use Ec\Editorial\Domain\Model\Body\BodyTagVideo;
use Ec\Editorial\Domain\Model\Body\BodyTagVideoYoutube;
use Ec\Editorial\Domain\Model\Body\GenericList;
use Ec\Editorial\Domain\Model\Body\Link;
use Ec\Editorial\Domain\Model\Body\NumberedList;
use Ec\Editorial\Domain\Model\Body\Paragraph;
use Ec\Editorial\Domain\Model\Body\SubHead;
use Ec\Editorial\Domain\Model\Body\UnorderedList;

/**
 * Factory for creating BodyElementResponse DTOs from domain body elements.
 */
final class BodyElementResponseFactory
{
    /**
     * @param array<string, mixed> $resolveData Additional data for element transformation
     */
    public function create(BodyElement $element, array $resolveData = []): BodyElementResponse
    {
        return match (true) {
            $element instanceof Paragraph => $this->createParagraph($element),
            $element instanceof SubHead => $this->createSubHead($element),
            $element instanceof BodyTagPicture => $this->createPicture($element, $resolveData),
            $element instanceof BodyTagPictureMembership => $this->createPictureMembership($element, $resolveData),
            $element instanceof BodyTagVideoYoutube => $this->createVideoYoutube($element),
            $element instanceof BodyTagVideo => $this->createVideo($element),
            $element instanceof BodyTagHtml => $this->createHtml($element),
            $element instanceof BodyTagSummary => $this->createSummary($element),
            $element instanceof BodyTagExplanatorySummary => $this->createExplanatorySummary($element),
            $element instanceof BodyTagInsertedNews => $this->createInsertedNews($element, $resolveData),
            $element instanceof BodyTagMembershipCard => $this->createMembershipCard($element, $resolveData),
            $element instanceof Link => $this->createLink($element),
            $element instanceof UnorderedList => $this->createUnorderedList($element),
            $element instanceof NumberedList => $this->createNumberedList($element),
            $element instanceof GenericList => $this->createGenericList($element),
            default => $this->createGeneric($element),
        };
    }

    private function createParagraph(Paragraph $element): BodyElementResponse
    {
        return new BodyElementResponse(
            type: 'paragraph',
            content: $element->content(),
        );
    }

    private function createSubHead(SubHead $element): BodyElementResponse
    {
        return new BodyElementResponse(
            type: 'subhead',
            content: $element->content(),
            level: $element->level(),
        );
    }

    /**
     * @param array<string, mixed> $resolveData
     */
    private function createPicture(BodyTagPicture $element, array $resolveData): BodyElementResponse
    {
        $photoId = $element->id()->id();
        $photoData = $resolveData['photoFromBodyTags'][$photoId] ?? null;

        return new BodyElementResponse(
            type: 'picture',
            imageUrl: $photoData['url'] ?? null,
            caption: $element->caption(),
            credit: $photoData['credit'] ?? null,
            extra: [
                'id' => $photoId,
            ],
        );
    }

    /**
     * @param array<string, mixed> $resolveData
     */
    private function createPictureMembership(BodyTagPictureMembership $element, array $resolveData): BodyElementResponse
    {
        $photoId = $element->id()->id();
        $photoData = $resolveData['photoFromBodyTags'][$photoId] ?? null;

        return new BodyElementResponse(
            type: 'picture_membership',
            imageUrl: $photoData['url'] ?? null,
            caption: $element->caption(),
            credit: $photoData['credit'] ?? null,
        );
    }

    private function createVideoYoutube(BodyTagVideoYoutube $element): BodyElementResponse
    {
        return new BodyElementResponse(
            type: 'video_youtube',
            videoId: $element->videoId(),
        );
    }

    private function createVideo(BodyTagVideo $element): BodyElementResponse
    {
        return new BodyElementResponse(
            type: 'video',
            videoUrl: $element->videoUrl(),
        );
    }

    private function createHtml(BodyTagHtml $element): BodyElementResponse
    {
        return new BodyElementResponse(
            type: 'html',
            html: $element->content(),
        );
    }

    private function createSummary(BodyTagSummary $element): BodyElementResponse
    {
        return new BodyElementResponse(
            type: 'summary',
            content: $element->content(),
        );
    }

    private function createExplanatorySummary(BodyTagExplanatorySummary $element): BodyElementResponse
    {
        return new BodyElementResponse(
            type: 'explanatory_summary',
            content: $element->content(),
            extra: [
                'title' => $element->title(),
            ],
        );
    }

    /**
     * @param array<string, mixed> $resolveData
     */
    private function createInsertedNews(BodyTagInsertedNews $element, array $resolveData): BodyElementResponse
    {
        $editorialId = $element->editorialId()->id();
        $newsData = $resolveData['insertedNews'][$editorialId] ?? null;

        return new BodyElementResponse(
            type: 'inserted_news',
            extra: [
                'editorialId' => $editorialId,
                'editorial' => $newsData,
            ],
        );
    }

    /**
     * @param array<string, mixed> $resolveData
     */
    private function createMembershipCard(BodyTagMembershipCard $element, array $resolveData): BodyElementResponse
    {
        $membershipLinks = $resolveData['membershipLinkCombine'] ?? [];

        return new BodyElementResponse(
            type: 'membership_card',
            extra: [
                'title' => $element->title(),
                'membershipLinks' => $membershipLinks,
            ],
        );
    }

    private function createLink(Link $element): BodyElementResponse
    {
        return new BodyElementResponse(
            type: 'link',
            content: $element->text(),
            extra: [
                'url' => $element->url(),
            ],
        );
    }

    private function createUnorderedList(UnorderedList $element): BodyElementResponse
    {
        return new BodyElementResponse(
            type: 'unordered_list',
            items: $element->items(),
        );
    }

    private function createNumberedList(NumberedList $element): BodyElementResponse
    {
        return new BodyElementResponse(
            type: 'numbered_list',
            items: $element->items(),
        );
    }

    private function createGenericList(GenericList $element): BodyElementResponse
    {
        return new BodyElementResponse(
            type: 'generic_list',
            items: $element->items(),
        );
    }

    private function createGeneric(BodyElement $element): BodyElementResponse
    {
        return new BodyElementResponse(
            type: 'unknown',
            extra: [
                'class' => $element::class,
            ],
        );
    }
}
