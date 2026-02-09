<?php

declare(strict_types=1);

namespace App\Controller\V1;

use App\Orchestrator\OrchestratorChain;
use Ec\MicroserviceBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PreviumEditorialController extends AbstractController
{
    public function __construct(
        private readonly OrchestratorChain $orchestratorChain,
        private readonly int $sMaxAge = 0,
    ) {
        parent::__construct($this->sMaxAge, 'v1.0.0');
    }

    public function getEditorialById(Request $request, string $id): JsonResponse
    {
        $request->attributes->set('id', $id);

        return new JsonResponse($this->orchestratorChain->handler('previum', $request));
    }
}
