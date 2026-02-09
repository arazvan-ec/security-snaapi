<?php

declare(strict_types=1);

namespace App\Orchestrator\Chain;

/**
 * @deprecated since 2.0, use App\Application\Handler\GetEditorialHandler instead.
 *
 * This class is kept for backward compatibility during migration.
 * Will be removed in version 3.0.
 *
 * Migration guide:
 * - Replace OrchestratorChain->handler('editorial', $request)
 * - With GetEditorialHandler->__invoke($id)
 *
 * @see \App\Application\Handler\GetEditorialHandler
 */
trigger_deprecation(
    'app',
    '2.0',
    'The "%s" class is deprecated, use "%s" instead.',
    EditorialOrchestrator::class,
    \App\Application\Handler\GetEditorialHandler::class
);
