# Spike: A3 - Enricher Coverage Gap Analysis

## Context
Shape A3 requires verifying that existing enrichers cover all 17 responsibilities from the legacy EditorialOrchestrator::execute(). This spike investigates which enrichers are missing and what gateway/factory support is needed.

## Questions

| # | Question | Answer |
|---|----------|--------|
| Q1 | Do the 7 existing enrichers cover all 17 responsibilities? | No. 7 fully covered, 3 partial, 4 missing, 3 N/A |
| Q2 | What enrichers are missing? | InsertedNewsEnricher, RecommendedEditorialsEnricher, BodyPhotosEnricher, OpeningMultimediaEnricher |
| Q3 | Are gateway interfaces complete? | No. Missing: CommentGatewayInterface, LegacyEditorialGateway, opening multimedia method |
| Q4 | Are response factories complete? | No. Missing: RecommendedEditorialResponseFactory, opening multimedia transformation |
| Q5 | Is the legacy fallback (sourceEditorial) still needed? | Yes but can be handled inside EditorialHttpGateway transparently |
| Q6 | Is async parallelism lost? | Yes. All enrichers are sync. Gateway methods have *Async() variants unused. Acceptable trade-off for now (sequential is simpler, correctness over perf). |

## Findings

### 7 Fully Covered
| Responsibility | Enricher |
|---|---|
| Fetch editorial | EditorialEnricher (priority 100) |
| Check visibility | EditorialEnricher (line 43-45) |
| Fetch section | SectionEnricher (priority 90) |
| Fetch tags | TagsEnricher (priority 70) |
| Resolve journalists | JournalistsEnricher (priority 60) |
| Resolve membership links | MembershipEnricher (priority 50) |
| Fetch comment count | CommentsEnricher (priority 40) - DIP violation: uses concrete QueryLegacyClient |

### 4 Missing Enrichers (must be created)

1. **OpeningMultimediaEnricher** (priority 75)
   - Fetch `$editorial->opening()->multimediaId()` via multimedia gateway
   - Route through MultimediaOrchestratorHandler (or new equivalent)
   - Populate `context->setMultimediaOpening()`

2. **BodyPhotosEnricher** (priority 35)
   - Extract photo IDs from BodyTagPicture + BodyTagMembershipCard in editorial body
   - Fetch each via `MultimediaGatewayInterface::findPhotoById()`
   - Populate `context->setBodyPhotos()`

3. **InsertedNewsEnricher** (priority 30)
   - Iterate BodyTagInsertedNews from editorial body
   - For each: fetch sub-editorial, check visibility, fetch section, resolve signatures, fetch multimedia
   - Most complex enricher - mini-orchestrator

4. **RecommendedEditorialsEnricher** (priority 25)
   - Iterate editorial->recommendedEditorials()->editorialIds()
   - Same sub-orchestration pattern as InsertedNewsEnricher
   - Needs RecommendedEditorialsResponseFactory

### 3 Gateway Gaps
- `CommentGatewayInterface` - CommentsEnricher uses concrete QueryLegacyClient
- Legacy editorial fallback - Can be absorbed into EditorialHttpGateway::findById()
- Opening multimedia - Needs `findOpeningMultimediaById()` on MultimediaGatewayInterface

### 2 Response Factory Gaps
- `RecommendedEditorialResponseFactory` - Currently raw array passthrough
- Opening multimedia transformation - No equivalent of MediaDataTransformerHandler

### Architecture Notes
- EditorialPipelineContext (new DTO) extends EditorialContext with additional type-safe fields
- SolidEnrichmentPipeline has backward-compat adapter to legacy EditorialContext
- Specifications exist but unused (EditorialIsPublishedSpecification, etc.)
- All gateways define *Async() methods but no enricher uses them

## Conclusion

Shape A3 is viable but requires creating 4 new enrichers + filling 3 gateway gaps + 2 factory gaps. The most complex work is InsertedNewsEnricher and RecommendedEditorialsEnricher due to their sub-orchestration patterns. The pipeline adapter (A1) can be built first with existing enrichers, then gaps filled incrementally per slice.
