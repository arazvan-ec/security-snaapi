<?php

declare(strict_types=1);

namespace App\Application\Factory\Response;

use App\Application\DTO\Response\MultimediaResponse;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaEmbedVideo;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaWidget;

final readonly class MultimediaResponseFactory
{
    public function create(Multimedia $multimedia): MultimediaResponse
    {
        return match (true) {
            $multimedia instanceof MultimediaPhoto => $this->createPhotoResponse($multimedia),
            $multimedia instanceof MultimediaEmbedVideo => $this->createVideoResponse($multimedia),
            $multimedia instanceof MultimediaWidget => $this->createWidgetResponse($multimedia),
            default => $this->createGenericResponse($multimedia),
        };
    }

    private function createPhotoResponse(MultimediaPhoto $photo): MultimediaResponse
    {
        return new MultimediaResponse(
            type: 'photo',
            id: $photo->id(),
            url: $photo->url(),
            caption: $photo->caption(),
            credit: $photo->credit(),
        );
    }

    private function createVideoResponse(MultimediaEmbedVideo $video): MultimediaResponse
    {
        return new MultimediaResponse(
            type: 'embed_video',
            id: $video->id(),
            url: $video->url(),
            caption: $video->caption(),
        );
    }

    private function createWidgetResponse(MultimediaWidget $widget): MultimediaResponse
    {
        return new MultimediaResponse(
            type: 'widget',
            id: $widget->id(),
            metadata: [
                'widgetType' => $widget->type(),
            ],
        );
    }

    private function createGenericResponse(Multimedia $multimedia): MultimediaResponse
    {
        return new MultimediaResponse(
            type: 'unknown',
            id: $multimedia->id(),
        );
    }
}
