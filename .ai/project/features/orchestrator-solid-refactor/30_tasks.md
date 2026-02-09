# Task Breakdown: orchestrator-solid-refactor

> Derived from vertical slices (03_slices.md). Each task includes SOLID requirements.
> Methodology: TDD (Red-Green-Refactor) for all tasks.

---

## Dependency Graph

```
V1-001 ──> V1-002 ──> V1-003 ──> V1-004
                                    |
                          V2-001 ──>|──> V3-001 ──> V3-002 ──> V5-001 ──> V5-002
                          V2-002 ──>|                              ^
                                    |                              |
                          V4-001 ──> V4-002 ─────────────────────-─┘
```

- V1-001 to V1-004: Sequential (each builds on previous)
- V2-001, V2-002: Parallel (after V1-004)
- V4-001, V4-002: Parallel with V2 (after V1-004)
- V3-001, V3-002: After V2 complete
- V5-001, V5-002: After V3 and V4 complete

---

## V1: Pipeline Adapter + Visibility Strategy

### Task V1-001: Create VisibilityStrategyInterface + Implementations

**Specs**: SPEC-F02
**Methodology**: TDD

**SOLID Requirements**:
- **SRP**: Each strategy = one visibility rule
- **OCP**: New visibility types = new class, no modifications
- **LSP**: Both strategies substitutable via interface
- **DIP**: Adapter depends on interface, not implementations

**Files to Create**:
- `src/Application/Strategy/Visibility/VisibilityStrategyInterface.php`
- `src/Application/Strategy/Visibility/PublishedOnlyStrategy.php`
- `src/Application/Strategy/Visibility/AllowAllStrategy.php`

**Tests to Write FIRST**:
- [ ] `test_published_only_strategy_throws_for_invisible_editorial()`
- [ ] `test_published_only_strategy_allows_visible_editorial()`
- [ ] `test_allow_all_strategy_allows_invisible_editorial()`
- [ ] `test_allow_all_strategy_allows_visible_editorial()`

**Test File**: `tests/Unit/Application/Strategy/Visibility/PublishedOnlyStrategyTest.php`, `AllowAllStrategyTest.php`

**Acceptance Criteria**:
- [ ] Interface in `src/Application/Strategy/Visibility/`
- [ ] Two implementations with full test coverage
- [ ] No dependency on concrete classes

**Reference**: `src/Application/Strategy/Editorial/EditorialFieldExtractorInterface.php` (existing strategy pattern)

---

### Task V1-002: Create PipelineEditorialOrchestrator

**Specs**: SPEC-F01, SPEC-F03
**Methodology**: TDD

**SOLID Requirements**:
- **SRP**: Only coordinates: extract ID -> pipeline -> visibility check -> response factory
- **DIP**: Depends on EnrichmentPipeline, VisibilityStrategyInterface, EditorialResponseFactory
- **LSP**: Implements EditorialOrchestratorInterface identically

**Files to Create**:
- `src/Orchestrator/Chain/PipelineEditorialOrchestrator.php`

**Constructor**:
```php
public function __construct(
    private readonly EnrichmentPipeline $pipeline,
    private readonly VisibilityStrategyInterface $visibilityStrategy,
    private readonly EditorialResponseFactory $responseFactory,
    private readonly string $contentType,
)
```

**Tests to Write FIRST**:
- [ ] `test_execute_creates_context_and_runs_pipeline()`
- [ ] `test_execute_applies_visibility_strategy_after_enrichment()`
- [ ] `test_execute_returns_response_factory_output()`
- [ ] `test_execute_throws_for_not_published_with_published_only_strategy()`
- [ ] `test_execute_allows_unpublished_with_allow_all_strategy()`
- [ ] `test_can_orchestrate_returns_content_type()`

**Test File**: `tests/Unit/Orchestrator/Chain/PipelineEditorialOrchestratorTest.php`

**Acceptance Criteria**:
- [ ] Implements `EditorialOrchestratorInterface`
- [ ] `canOrchestrate()` returns configurable content type
- [ ] execute() delegates to pipeline then response factory
- [ ] ~60 lines max
- [ ] SOLID score >= 24/25

---

### Task V1-003: Wire PipelineEditorialOrchestrator in Config

**Specs**: SPEC-F03 (backward compat)

**Files to Modify**:
- `config/packages/orchestrators.yaml` - Register two instances:
  - `app.orchestrator.editorial`: PipelineEditorialOrchestrator with PublishedOnlyStrategy, contentType='editorial'
  - `app.orchestrator.previum`: PipelineEditorialOrchestrator with AllowAllStrategy, contentType='previum'
- `config/services/solid.yaml` or `config/services/pipeline.yaml` - Register strategy services

**Files to Modify (remove legacy)**:
- `config/packages/orchestrators.yaml` - Remove/comment `EditorialOrchestrator` and `PreviumEditorialOrchestrator` from `app.orchestrators` resource

**Acceptance Criteria**:
- [ ] Chain handler resolves 'editorial' to PipelineEditorialOrchestrator(PublishedOnly)
- [ ] Chain handler resolves 'previum' to PipelineEditorialOrchestrator(AllowAll)
- [ ] Legacy orchestrators no longer registered in chain
- [ ] Controllers work without changes

---

### Task V1-004: Verify V1 Integration + JSON Parity

**Specs**: SPEC-F03 (backward compat), SPEC-F01

**Tests to Write**:
- [ ] `test_editorial_endpoint_returns_200_for_published()` (integration)
- [ ] `test_editorial_endpoint_returns_404_for_unpublished()` (integration)
- [ ] `test_previum_endpoint_returns_200_for_unpublished()` (integration)
- [ ] `test_json_output_matches_legacy_for_basic_editorial()` (parity snapshot)

**Note**: At this point, some JSON fields may be incomplete (inserted news, recommended editorials, body photos, opening multimedia). V2 and V3 will fill those gaps. The parity test should compare only the fields that the existing enrichers cover.

**Acceptance Criteria**:
- [ ] Both endpoints respond correctly
- [ ] Core fields match legacy output (editorial base, section, tags, journalists, membership, comments)
- [ ] Fields pending completion identified and documented

---

## V2: Missing Enrichers - Opening Multimedia + Body Photos

### Task V2-001: Create OpeningMultimediaEnricher

**Specs**: SPEC-F07, SPEC-F04
**Methodology**: TDD

**SOLID Requirements**:
- **SRP**: Only fetches and processes opening multimedia
- **DIP**: Depends on MultimediaGatewayInterface (will need extension) and MultimediaOrchestratorHandler

**Files to Create**:
- `src/Application/Pipeline/Enricher/OpeningMultimediaEnricher.php`

**Files to Modify**:
- `src/Domain/Port/Gateway/MultimediaGatewayInterface.php` - Add `findOpeningMultimediaById(string $id): ?Multimedia`
- `src/Infrastructure/Gateway/Http/MultimediaHttpGateway.php` - Implement using QueryMultimediaOpeningClient

**Tests to Write FIRST**:
- [ ] `test_enrich_populates_multimedia_opening_for_editorial_with_opening()`
- [ ] `test_enrich_skips_when_no_opening_multimedia_id()`
- [ ] `test_enrich_handles_gateway_error_gracefully()`
- [ ] `test_supports_returns_true_when_editorial_exists_in_context()`
- [ ] `test_priority_is_75()`

**Test File**: `tests/Unit/Application/Pipeline/Enricher/OpeningMultimediaEnricherTest.php`

**Acceptance Criteria**:
- [ ] Priority 75 (after MultimediaEnricher at 80)
- [ ] Populates `context->setMultimediaOpening()`
- [ ] Delegates to MultimediaOrchestratorHandler for type-specific processing
- [ ] Handles missing/failed gracefully

---

### Task V2-002: Create BodyPhotosEnricher

**Specs**: SPEC-F08, SPEC-F04
**Methodology**: TDD

**SOLID Requirements**:
- **SRP**: Only extracts and fetches body tag photos
- **DIP**: Depends on MultimediaGatewayInterface

**Files to Create**:
- `src/Application/Pipeline/Enricher/BodyPhotosEnricher.php`

**Tests to Write FIRST**:
- [ ] `test_enrich_extracts_photo_ids_from_body_tag_pictures()`
- [ ] `test_enrich_extracts_photo_ids_from_body_tag_membership_cards()`
- [ ] `test_enrich_fetches_each_photo_via_gateway()`
- [ ] `test_enrich_handles_missing_photo_gracefully()`
- [ ] `test_enrich_skips_when_no_body()`
- [ ] `test_priority_is_35()`

**Test File**: `tests/Unit/Application/Pipeline/Enricher/BodyPhotosEnricherTest.php`

**Acceptance Criteria**:
- [ ] Priority 35
- [ ] Iterates BodyTagPicture and BodyTagMembershipCard
- [ ] Populates `context->setBodyPhotos()`
- [ ] Each photo fetched via `MultimediaGatewayInterface::findPhotoById()`

---

## V3: Missing Enrichers - Inserted News + Recommended Editorials

### Task V3-001: Create InsertedNewsEnricher

**Specs**: SPEC-F05, SPEC-F04
**Methodology**: TDD

**SOLID Requirements**:
- **SRP**: Only enriches inserted news data
- **DIP**: Depends on EditorialGatewayInterface, SectionGatewayInterface, JournalistGatewayInterface, MultimediaGatewayInterface

**Files to Create**:
- `src/Application/Pipeline/Enricher/InsertedNewsEnricher.php`

**Internal Logic** (mirror legacy execute() lines 125-161):
```
for each BodyTagInsertedNews in editorial.body():
    fetch sub-editorial via EditorialGateway
    if sub-editorial is null or not visible -> skip
    fetch section via SectionGateway
    resolve signatures via JournalistGateway
    fetch multimedia via MultimediaGateway
    add to insertedNews array
context->setInsertedNews(array)
```

**Tests to Write FIRST**:
- [ ] `test_enrich_fetches_all_inserted_news_editorials()`
- [ ] `test_enrich_skips_non_visible_inserted_news()`
- [ ] `test_enrich_skips_null_inserted_news()`
- [ ] `test_enrich_continues_on_single_news_error()`
- [ ] `test_enrich_includes_section_for_each_news()`
- [ ] `test_enrich_includes_signatures_for_each_news()`
- [ ] `test_enrich_includes_multimedia_for_each_news()`
- [ ] `test_priority_is_30()`

**Test File**: `tests/Unit/Application/Pipeline/Enricher/InsertedNewsEnricherTest.php`

**Acceptance Criteria**:
- [ ] Priority 30 (needs editorial from context)
- [ ] Mirrors exact data structure of legacy `$resolveData['insertedNews']`
- [ ] Each sub-editorial fully enriched (section, signatures, multimedia)
- [ ] Non-visible sub-editorials silently skipped
- [ ] Errors per sub-editorial caught and logged (continue with next)

---

### Task V3-002: Create RecommendedEditorialsEnricher + ResponseFactory

**Specs**: SPEC-F06, SPEC-F04
**Methodology**: TDD

**SOLID Requirements**:
- **SRP**: Only enriches recommended editorial data
- **DIP**: Same gateways as InsertedNewsEnricher

**Files to Create**:
- `src/Application/Pipeline/Enricher/RecommendedEditorialsEnricher.php`
- `src/Application/Factory/Response/RecommendedEditorialResponseFactory.php`

**Tests to Write FIRST**:
- [ ] `test_enrich_fetches_all_recommended_editorials()`
- [ ] `test_enrich_skips_non_visible_recommended()`
- [ ] `test_enrich_includes_section_and_signatures()`
- [ ] `test_enrich_handles_errors_per_recommended()`
- [ ] `test_priority_is_25()`
- [ ] `test_response_factory_produces_legacy_compatible_format()`

**Test Files**:
- `tests/Unit/Application/Pipeline/Enricher/RecommendedEditorialsEnricherTest.php`
- `tests/Unit/Application/Factory/Response/RecommendedEditorialResponseFactoryTest.php`

**Acceptance Criteria**:
- [ ] Priority 25
- [ ] Mirrors exact data structure of legacy `$resolveData['recommendedEditorials']`
- [ ] ResponseFactory produces same format as legacy `RecommendedEditorialsDataTransformer`
- [ ] Full JSON parity for recommended editorials section

---

## V4: Gateway Cleanup + CommentGateway

### Task V4-001: Create CommentGatewayInterface + Implementation

**Specs**: SPEC-F09
**Methodology**: TDD

**SOLID Requirements**:
- **SRP**: Gateway only wraps comment data access
- **DIP**: CommentsEnricher depends on interface, not concrete client

**Files to Create**:
- `src/Domain/Port/Gateway/CommentGatewayInterface.php`
- `src/Infrastructure/Gateway/Http/CommentHttpGateway.php`

**Files to Modify**:
- `src/Application/Pipeline/Enricher/CommentsEnricher.php` - Replace `QueryLegacyClient` with `CommentGatewayInterface`
- `config/services/pipeline.yaml` - Register `CommentGatewayInterface` alias to `CommentHttpGateway`

**Tests to Write FIRST**:
- [ ] `test_comment_http_gateway_returns_count_from_legacy_client()`
- [ ] `test_comment_http_gateway_handles_error_returning_zero()`
- [ ] `test_comments_enricher_uses_gateway_interface()`

**Test Files**:
- `tests/Unit/Infrastructure/Gateway/Http/CommentHttpGatewayTest.php`

**Acceptance Criteria**:
- [ ] `CommentGatewayInterface` in `Domain/Port/Gateway/`
- [ ] `CommentHttpGateway` wraps existing `QueryLegacyClient`
- [ ] `CommentsEnricher` no longer imports any concrete client
- [ ] Zero runtime behavior change

---

### Task V4-002: Verify All Enrichers Use Gateway Abstractions

**Specs**: SPEC-F09

**Verification**:
- [ ] `grep -r 'QueryLegacyClient\|QueryEditorialClient\|QuerySectionClient\|QueryMultimediaClient\|QueryTagClient\|QueryJournalistClient\|QueryMembershipClient' src/Application/Pipeline/Enricher/` returns ZERO results
- [ ] Every enricher constructor only accepts `*GatewayInterface` or `*Interface` types
- [ ] All gateway interfaces are in `src/Domain/Port/Gateway/`
- [ ] All gateway implementations are in `src/Infrastructure/Gateway/Http/`

**Acceptance Criteria**:
- [ ] Zero DIP violations in enricher layer
- [ ] Port/Adapter hexagonal architecture fully enforced

---

## V5: Legacy Removal + Test Parity

### Task V5-001: Full JSON Parity Test

**Specs**: SPEC-F03, SPEC-F10

**Tests to Write**:
- [ ] `test_pipeline_output_matches_legacy_for_simple_editorial()`
- [ ] `test_pipeline_output_matches_legacy_for_editorial_with_inserted_news()`
- [ ] `test_pipeline_output_matches_legacy_for_editorial_with_recommended()`
- [ ] `test_pipeline_output_matches_legacy_for_editorial_with_all_multimedia_types()`
- [ ] `test_pipeline_output_matches_legacy_for_unpublished_editorial_via_previum()`

**Test File**: `tests/Integration/PipelineParityTest.php`

**Acceptance Criteria**:
- [ ] JSON output field-by-field identical for all editorial types
- [ ] HTTP status codes identical
- [ ] Error scenarios identical (404 for unpublished)

---

### Task V5-002: Delete Legacy Orchestrators

**Specs**: SPEC-F10

**Files to Delete**:
- `src/Orchestrator/Chain/EditorialOrchestrator.php` (536 lines)
- `src/Orchestrator/Chain/PreviumEditorialOrchestrator.php` (503 lines)
- `tests/Orchestrator/Chain/EditorialOrchestratorTest.php` (1700 lines)

**Files to Preserve**:
- `src/Orchestrator/Chain/EditorialOrchestratorLegacy.php` (deprecation notice)

**Files to Verify Clean**:
- `config/packages/orchestrators.yaml` - No references to deleted classes
- `config/services.yaml` - No references to deleted classes

**Post-Deletion Verification**:
- [ ] `php -l` syntax check on all remaining files
- [ ] Full PHPUnit suite passes
- [ ] `grep -r 'EditorialOrchestrator' src/ --include='*.php'` only matches Legacy and Pipeline files
- [ ] `grep -r 'PreviumEditorialOrchestrator' src/ --include='*.php'` returns zero results

**Acceptance Criteria**:
- [ ] 2739 lines deleted (536 + 503 + 1700)
- [ ] Zero references to deleted classes
- [ ] All tests pass
- [ ] SOLID score 24/25 achieved

---

## Task Summary

| Task | Slice | Files Create | Files Modify | Files Delete | Complexity | Dependencies |
|------|-------|-------------|-------------|-------------|------------|--------------|
| V1-001 | V1 | 3 src + 2 test | 0 | 0 | Low | None |
| V1-002 | V1 | 1 src + 1 test | 0 | 0 | Medium | V1-001 |
| V1-003 | V1 | 0 | 2 config | 0 | Low | V1-002 |
| V1-004 | V1 | 1 test | 0 | 0 | Medium | V1-003 |
| V2-001 | V2 | 1 src + 1 test | 2 (gateway) | 0 | Medium | V1-004 |
| V2-002 | V2 | 1 src + 1 test | 0 | 0 | Medium | V1-004 |
| V3-001 | V3 | 1 src + 1 test | 0 | 0 | High | V2-001, V2-002 |
| V3-002 | V3 | 2 src + 2 test | 0 | 0 | High | V2-001, V2-002 |
| V4-001 | V4 | 2 src + 1 test | 2 (enricher, config) | 0 | Low | V1-004 |
| V4-002 | V4 | 0 | 0 | 0 | Low | V4-001 |
| V5-001 | V5 | 1 test | 0 | 0 | Medium | V3-002, V4-002 |
| V5-002 | V5 | 0 | 1 config | 3 | Low | V5-001 |
| **TOTAL** | | **~12 src + ~10 test** | **~7** | **3** | | |
