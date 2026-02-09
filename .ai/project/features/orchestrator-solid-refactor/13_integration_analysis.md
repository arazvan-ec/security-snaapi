# Integration Analysis: orchestrator-solid-refactor

## Summary
- Entities: 0 extended, 0 modified, 3 new (VisibilityStrategy, 4 new enrichers, 1 new gateway)
- Endpoints: 0 extended, 0 modified, 0 new (backward compatible, endpoints unchanged)
- Business Rules: 0 conflicts, 1 new (visibility strategy dispatching)
- Status: CLEAR - No conflicts detected

---

## Entities Impact

### EXTENDED (existing entities with new behavior)

| Entity | Change | Impact |
|--------|--------|--------|
| `MultimediaGatewayInterface` | Add `findOpeningMultimediaById()` method | LOW - additive change, no existing code affected |
| `EditorialResponseFactory` | Wire `RecommendedEditorialResponseFactory` for recommended editorials | LOW - internal composition change |

### MODIFIED (existing entities with changed behavior)

| Entity | Change | Impact |
|--------|--------|--------|
| `CommentsEnricher` | Replace `QueryLegacyClient` with `CommentGatewayInterface` | LOW - same behavior, different injection |
| `orchestrators.yaml` | Replace legacy orchestrator registrations with PipelineEditorialOrchestrator instances | MEDIUM - service wiring change |

### NEW (entities created by this feature)

| Entity | Purpose | Relationships |
|--------|---------|---------------|
| `VisibilityStrategyInterface` | Decouple visibility check from orchestrator | Used by PipelineEditorialOrchestrator |
| `PublishedOnlyStrategy` | Check isVisible for editorial content type | Implements VisibilityStrategyInterface |
| `AllowAllStrategy` | Skip visibility check for previum content type | Implements VisibilityStrategyInterface |
| `PipelineEditorialOrchestrator` | Adapter between chain and pipeline | Implements EditorialOrchestratorInterface, uses EnrichmentPipeline |
| `OpeningMultimediaEnricher` | Enrich opening multimedia | Implements EnricherInterface |
| `BodyPhotosEnricher` | Enrich body tag photos | Implements EnricherInterface |
| `InsertedNewsEnricher` | Enrich inserted news sub-editorials | Implements EnricherInterface |
| `RecommendedEditorialsEnricher` | Enrich recommended editorials | Implements EnricherInterface |
| `CommentGatewayInterface` | Abstract comment data access | Port interface |
| `CommentHttpGateway` | HTTP implementation for comments | Wraps QueryLegacyClient |
| `RecommendedEditorialResponseFactory` | Transform recommended editorial data | Used by EditorialResponseFactory |

---

## API Contracts Impact

### EXTENDED
None - all endpoints retain exact same contract.

### MODIFIED
None - no endpoint behavior changes.

### NEW
None - no new endpoints.

| Endpoint | Method | Before | After | Change |
|----------|--------|--------|-------|--------|
| `/editorials/{id}` | GET | EditorialOrchestrator via chain | PipelineEditorialOrchestrator(PublishedOnly) via chain | Internal routing only |
| `/previum/{id}` | GET | PreviumEditorialOrchestrator via chain | PipelineEditorialOrchestrator(AllowAll) via chain | Internal routing only |

---

## Business Rules Impact

### CONFLICTS
None detected.

### NEW

| Rule ID | Entity | Description |
|---------|--------|-------------|
| VIS-001 | Editorial | Visibility strategy dispatches based on content type: 'editorial' -> PublishedOnlyStrategy, 'previum' -> AllowAllStrategy |

### PRESERVED (existing rules maintained)

| Rule | Status |
|------|--------|
| Editorial not published -> 404 | Preserved via PublishedOnlyStrategy |
| Previum sees unpublished -> 200 | Preserved via AllowAllStrategy |
| JWT auth required for /previum | Unaffected (firewall layer, not orchestrator) |
| Graceful degradation on enricher failure | Already built into EnrichmentPipeline |

---

## Compatibility Assessment

- **Backward Compatible**: YES
- **Migration Required**: NO (service wiring only, no DB changes)
- **Breaking Changes**: None
- **Deprecation**: EditorialOrchestrator and PreviumEditorialOrchestrator deprecated then removed
