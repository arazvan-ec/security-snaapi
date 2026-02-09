<?php

declare(strict_types=1);

namespace App\Application\Factory\Response;

use App\Application\DTO\Response\BodyResponse;
use Ec\Editorial\Domain\Model\Body\Body;

final readonly class BodyResponseFactory
{
    public function __construct(
        private BodyElementResponseFactory $elementFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $resolveData
     */
    public function create(Body $body, array $resolveData = []): BodyResponse
    {
        $elements = [];

        foreach ($body->elements() as $element) {
            $elements[] = $this->elementFactory->create($element, $resolveData);
        }

        return new BodyResponse(
            type: $body->type(),
            elements: $elements,
        );
    }
}
