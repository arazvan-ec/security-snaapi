# Architectural Impact: orchestrator-solid-refactor

## Summary
- Layers affected: Application, Domain (Port), Infrastructure (Gateway), Orchestrator
- Modules touched: 4 existing, 1 new (Strategy/Visibility)
- Change scope: ~12 files to create, ~4 files to modify, ~3 files to delete

---

## Layer Analysis

| Layer | Impact Level | Changes Required |
|-------|--------------|------------------|
| **Domain (Port)** | LOW | 1 new gateway interface (CommentGatewayInterface), 1 extended (MultimediaGatewayInterface) |
| **Application (Pipeline)** | MEDIUM | 4 new enrichers, 1 new response factory |
| **Application (Strategy)** | LOW | 1 new interface + 2 implementations (Visibility) |
| **Infrastructure (Gateway)** | LOW | 1 new HTTP gateway (CommentHttpGateway), 1 extended (MultimediaHttpGateway) |
| **Orchestrator** | HIGH | 1 new adapter, 2 legacy files deleted, config rewired |
| **Presentation (Controller)** | NONE | Zero changes to controllers or routes |
| **Config** | MEDIUM | orchestrators.yaml rewired, pipeline.yaml + solid.yaml extended |

---

## Affected Layers Diagram

```
+-------------------------------------------------------------------+
| PRESENTATION (UNCHANGED)                                           |
|   EditorialController.php         -- NO CHANGES                    |
|   PreviumEditorialController.php  -- NO CHANGES                    |
+-------------------------------------------------------------------+
                              |
                              v
+-------------------------------------------------------------------+
| ORCHESTRATOR                                                       |
|   [UNCHANGED] OrchestratorChainHandler.php                         |
|   [NEW] PipelineEditorialOrchestrator.php  (adapter, ~60 lines)   |
|   [DELETE] EditorialOrchestrator.php       (536 lines)             |
|   [DELETE] PreviumEditorialOrchestrator.php (503 lines)            |
+-------------------------------------------------------------------+
                              |
                              v
+-------------------------------------------------------------------+
| APPLICATION - Strategy                                             |
|   [NEW] VisibilityStrategyInterface.php                            |
|   [NEW] PublishedOnlyStrategy.php                                  |
|   [NEW] AllowAllStrategy.php                                       |
+-------------------------------------------------------------------+
                              |
                              v
+-------------------------------------------------------------------+
| APPLICATION - Pipeline                                             |
|   [UNCHANGED] EnrichmentPipeline.php                               |
|   [UNCHANGED] EditorialContext.php                                  |
|   [UNCHANGED] EditorialEnricher.php (priority 100)                 |
|   [UNCHANGED] SectionEnricher.php (priority 90)                    |
|   [UNCHANGED] MultimediaEnricher.php (priority 80)                 |
|   [NEW] OpeningMultimediaEnricher.php (priority 75)                |
|   [UNCHANGED] TagsEnricher.php (priority 70)                       |
|   [UNCHANGED] JournalistsEnricher.php (priority 60)                |
|   [UNCHANGED] MembershipEnricher.php (priority 50)                 |
|   [MODIFIED] CommentsEnricher.php (priority 40) - DIP fix          |
|   [NEW] BodyPhotosEnricher.php (priority 35)                       |
|   [NEW] InsertedNewsEnricher.php (priority 30)                     |
|   [NEW] RecommendedEditorialsEnricher.php (priority 25)            |
+-------------------------------------------------------------------+
                              |
                              v
+-------------------------------------------------------------------+
| APPLICATION - Factory                                              |
|   [UNCHANGED] EditorialResponseFactory.php                         |
|   [UNCHANGED] BodyResponseFactory.php                              |
|   [UNCHANGED] SectionResponseFactory.php                           |
|   [UNCHANGED] TagResponseFactory.php                               |
|   [UNCHANGED] SignatureResponseFactory.php                         |
|   [UNCHANGED] MultimediaResponseFactory.php                        |
|   [NEW] RecommendedEditorialResponseFactory.php                    |
+-------------------------------------------------------------------+
                              |
                              v
+-------------------------------------------------------------------+
| DOMAIN - Port                                                      |
|   [UNCHANGED] EditorialGatewayInterface.php                        |
|   [UNCHANGED] SectionGatewayInterface.php                          |
|   [EXTENDED] MultimediaGatewayInterface.php  (add opening method)  |
|   [UNCHANGED] TagGatewayInterface.php                              |
|   [UNCHANGED] JournalistGatewayInterface.php                       |
|   [UNCHANGED] MembershipGatewayInterface.php                       |
|   [NEW] CommentGatewayInterface.php                                |
+-------------------------------------------------------------------+
                              |
                              v
+-------------------------------------------------------------------+
| INFRASTRUCTURE - Gateway                                           |
|   [UNCHANGED] EditorialHttpGateway.php                             |
|   [UNCHANGED] SectionHttpGateway.php                               |
|   [EXTENDED] MultimediaHttpGateway.php  (add opening impl)        |
|   [UNCHANGED] TagHttpGateway.php                                   |
|   [UNCHANGED] JournalistHttpGateway.php                            |
|   [UNCHANGED] MembershipHttpGateway.php                            |
|   [NEW] CommentHttpGateway.php                                     |
+-------------------------------------------------------------------+
```

---

## Existing Modules Touched

| Module | Files Touched | Risk Level | Notes |
|--------|---------------|------------|-------|
| `src/Orchestrator/Chain/` | 3 (1 new, 2 deleted) | MEDIUM | Core routing. Adapter must match interface exactly. |
| `src/Application/Pipeline/Enricher/` | 5 (4 new, 1 modified) | LOW | Additive changes. Existing enrichers untouched. |
| `src/Application/Strategy/Visibility/` | 3 (all new) | LOW | New module, no existing code affected. |
| `src/Application/Factory/Response/` | 1 (new) | LOW | Additive. |
| `src/Domain/Port/Gateway/` | 2 (1 new, 1 extended) | LOW | Interface changes are additive. |
| `src/Infrastructure/Gateway/Http/` | 2 (1 new, 1 extended) | LOW | Implementation additions. |
| `config/packages/` | 1 (modified) | MEDIUM | Service wiring change. |
| `config/services/` | 2 (modified) | LOW | Additive service registrations. |
| `tests/` | ~10 (new) | N/A | All new test files. |

---

## Change Scope

```
+==================================================================+
|                    CHANGE SCOPE SUMMARY                           |
+==================================================================+
|  Files to CREATE:     ~15                                         |
|    - Source files:      8 (adapter, strategies, enrichers,        |
|                           gateways, factory)                      |
|    - Test files:       ~7 (1 per new component)                   |
|  Files to MODIFY:      ~5                                         |
|    - CommentsEnricher (DIP fix)                                   |
|    - MultimediaGatewayInterface (add method)                      |
|    - MultimediaHttpGateway (add implementation)                   |
|    - orchestrators.yaml (rewire)                                  |
|    - pipeline.yaml / solid.yaml (register new services)           |
|  Files to DELETE:       3                                         |
|    - EditorialOrchestrator.php (536 lines)                        |
|    - PreviumEditorialOrchestrator.php (503 lines)                 |
|    - EditorialOrchestratorTest.php (1700 lines)                   |
|  ------------------------------------------------------------------
|  Total files affected:  ~23                                       |
|                                                                    |
|  Estimated LOC added:    ~800 (source) + ~600 (tests) = ~1400    |
|  Estimated LOC deleted:  ~2739 (legacy orchestrators + test)      |
|  Net LOC change:         -1339 lines                              |
|  ------------------------------------------------------------------
|  Complexity: MEDIUM-HIGH                                           |
|  (V3 InsertedNews/Recommended enrichers are most complex)         |
+==================================================================+
```

---

## Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| JSON output parity breaks | MEDIUM | HIGH | Snapshot test comparing legacy vs pipeline output for multiple editorial types |
| InsertedNews enricher complexity | MEDIUM | MEDIUM | Follow exact same loop logic as legacy execute(). Test with editorials that have inserted news. |
| Opening multimedia routing differs | LOW | MEDIUM | Keep MultimediaOrchestratorHandler as-is. OpeningMultimediaEnricher delegates to it. |
| Legacy fallback (sourceEditorial) missed | LOW | HIGH | Verify EditorialHttpGateway handles this transparently. Test with editorial that has null sourceEditorial. |
| Chain handler duplicate key conflict | LOW | HIGH | When both legacy and pipeline are registered (during transition), use different content type keys. Remove legacy before activating pipeline for 'editorial'. |
| Performance regression (sync vs async) | LOW | LOW | Async optimization deferred. All gateways have *Async() methods ready for future use. |
