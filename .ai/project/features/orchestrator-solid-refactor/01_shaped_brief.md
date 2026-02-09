# Shaped Brief: orchestrator-solid-refactor

## Frame

### Problem

Los orchestrators de SNAAPI (`EditorialOrchestrator`, `PreviumEditorialOrchestrator`) son clases monoliticas de 500+ lineas con 20 dependencias en constructor, 17 responsabilidades mezcladas (fetch, transform, async promise management, multimedia routing, membership links...) y un 98% de codigo duplicado entre ellos. Cada cambio en la logica de enriquecimiento de editoriales requiere modificar estas clases masivas, con alto riesgo de regresion. El `PreviumEditorialOrchestrator` no tiene tests (0% coverage en produccion).

Existe ya una nueva arquitectura SOLID parcialmente construida (`EnrichmentPipeline` + `EnricherInterface` + Gateway interfaces + Response Factories + Strategy/Specification patterns) pero NO esta conectada a los controllers ni al chain. Es codigo muerto esperando integracion.

### Outcome

Los orchestrators legacy se reemplazan por el pipeline de enrichers. Cada responsabilidad vive en su propio enricher testeable. La duplicacion Editorial/Previum se elimina via una estrategia de visibilidad inyectable. La nueva arquitectura se conecta al chain handler existente, manteniendo backward compatibility con los controllers. Los tests cubren cada enricher individualmente.

---

## Requirements

| # | Requirement | Status |
|---|-------------|--------|
| R0 | Eliminar la duplicacion entre EditorialOrchestrator y PreviumEditorialOrchestrator (98% codigo identico) | Core goal |
| R1 | Cada responsabilidad del orchestrator actual debe vivir en un servicio independiente con single responsibility | Must-have |
| R2 | Mantener backward compatibility: los controllers existentes (EditorialController, PreviumEditorialController) deben seguir funcionando sin cambios | Must-have |
| R3 | Integrar la nueva arquitectura Pipeline+Enrichers (ya existente pero desconectada) con el chain handler | Must-have |
| R4 | La diferencia Previum (sin check de isVisible) debe resolverse via mecanismo extensible, no via copia de codigo | Must-have |
| R5 | Los enrichers deben ser testeables individualmente con mocks de gateways | Must-have |
| R6 | Depender de abstracciones (interfaces Gateway) en lugar de clientes HTTP concretos | Must-have |
| R7 | El pipeline debe soportar graceful degradation (si un enricher falla, continuar con los demas) | Must-have |
| R8 | Los multimedia orchestrators (Photo, Widget, EmbedVideo) pueden permanecer como estan inicialmente | Nice-to-have |
| R9 | Genericizar los chain handlers (OrchestratorChainHandler y MultimediaOrchestratorHandler comparten estructura identica) | Nice-to-have |
| R10 | Eliminar uso de traits (UrlGeneratorTrait, MultimediaTrait) y reemplazar por servicios inyectables | Nice-to-have |
| R11 | Eliminar el uso de arrays asociativos no tipados ($resolveData) y usar DTOs tipados (EditorialContext ya existe) | Must-have |
| R12 | El response final debe ser consistente con el formato actual de la API (no breaking changes en JSON output) | Must-have |

---

## Shape A: Pipeline Migration con Visibility Strategy

Migrar los orchestrators legacy a adaptadores delgados que delegan al `EnrichmentPipeline` existente, usando un VisibilityStrategy inyectable para eliminar la duplicacion Editorial/Previum.

| # | Part | Description | Flag |
|---|------|-------------|------|
| A1 | Pipeline-backed Orchestrator Adapter | Nuevo `PipelineEditorialOrchestrator` que implementa `EditorialOrchestratorInterface` y delega al `EnrichmentPipeline`. Recibe Request, extrae ID, crea `EditorialContext`, ejecuta pipeline, transforma context a array response. Reemplaza tanto `EditorialOrchestrator` como `PreviumEditorialOrchestrator`. | |
| A2 | Visibility Strategy (OCP) | `VisibilityStrategyInterface` con dos implementaciones: `PublishedOnlyStrategy` (lanza exception si !isVisible, para editorial) y `AllowAllStrategy` (no check, para previum). Inyectada en el adapter via config YAML diferenciada por firewall/content-type. | |
| A3 | Enricher completion | Completar los enrichers existentes (EditorialEnricher, SectionEnricher, etc.) verificando que cubren TODAS las responsabilidades del execute() actual: inserted news, recommended editorials, body photos, membership links, standfirst, async multimedia, opening multimedia, meta image, comment count, journalist aliases. | |
| A4 | Gateway adapter layer | Los enrichers ya dependen de interfaces Gateway (`EditorialGatewayInterface`, etc.). Las implementaciones HTTP gateway (`EditorialHttpGateway`, etc.) ya existen. Verificar que los gateways HTTP wrappean los clientes legacy existentes (`QueryEditorialClient`, `QuerySectionClient`, etc.) para no reimplementar HTTP calls. | |
| A5 | Response Factory integration | Los `ResponseFactory` existentes (`EditorialResponseFactory`, `BodyResponseFactory`, etc.) transforman el `EditorialContext` enriquecido al formato JSON array compatible con la API actual. Verificar paridad de output con el orchestrator legacy. | |
| A6 | Chain handler wiring | Configurar dos servicios del adapter: uno con `PublishedOnlyStrategy` registrado como `canOrchestrate()='editorial'` y otro con `AllowAllStrategy` registrado como `canOrchestrate()='previum'`. Ambos usan el mismo pipeline. Se eliminan los orchestrators legacy. | |
| A7 | Legacy orchestrator deprecation + removal | Marcar EditorialOrchestrator y PreviumEditorialOrchestrator como deprecated, luego eliminar tras verificacion completa. Conservar EditorialOrchestratorLegacy.php (ya tiene trigger_deprecation). | |

---

## Shape B: Abstract Base Class extraction (descartada)

| # | Part | Description | Flag |
|---|------|-------------|------|
| B1 | AbstractEditorialOrchestrator | Extraer clase base abstracta con todo el codigo comun, dejando isVisible() como metodo abstracto/hook. | |
| B2 | ConcreteEditorial extends Abstract | Solo override del visibility check. | |
| B3 | ConcretePrevium extends Abstract | Override que retorna true siempre. | |

**Descartada porque**: No resuelve SRP (la clase base seguiria con 17 responsabilidades, 500+ lineas, 20 deps). Solo resuelve R0 (duplicacion) pero NO R1, R6, R11. Ademas, la nueva arquitectura Pipeline ya existe y es superior. Seria "poner un parche" en vez de completar la migracion que ya empezo.

---

## Shape C: Decorator Pattern sobre orchestrator actual (descartada)

| # | Part | Description | Flag |
|---|------|-------------|------|
| C1 | VisibilityDecorator | Decorator que wrappea EditorialOrchestrator, ejecuta su logica, y decide si lanzar exception basado en visibility. | |

**Descartada porque**: El check de isVisible() ocurre DENTRO del execute() en la linea 110, antes del resto de la logica de fetch. Un decorator post-ejecucion no puede interceptarlo a tiempo (el editorial ya fue fetched). Habria que re-fetch o modificar el interior igualmente. Y sigue sin resolver SRP.

---

## Fit Check: R x A

| Req | Description | A |
|-----|-------------|---|
| R0 | Eliminar duplicacion Editorial/Previum | :white_check_mark: A1+A2 - Un solo adapter con strategy inyectable elimina los dos orchestrators de 500+ lineas |
| R1 | Single responsibility por servicio | :white_check_mark: A3 - Cada enricher = 1 responsabilidad, cada gateway = 1 fetch, cada factory = 1 transform |
| R2 | Backward compat controllers | :white_check_mark: A1+A6 - Los controllers llaman `orchestratorChain->handler('editorial')` que ahora resuelve al adapter que delega al pipeline |
| R3 | Integrar Pipeline+Enrichers existente | :white_check_mark: A1+A3 - El adapter ES el puente entre el chain y el pipeline |
| R4 | Previum via mecanismo extensible | :white_check_mark: A2 - VisibilityStrategy con OCP: nuevos tipos solo necesitan nueva strategy impl |
| R5 | Enrichers testeables con mocks | :white_check_mark: A3+A4 - Enrichers dependen de interfaces Gateway, mockeables trivialmente |
| R6 | Depender de abstracciones | :white_check_mark: A4 - Gateways como interfaces, implementations en infra |
| R7 | Graceful degradation | :white_check_mark: A1 - EnrichmentPipeline ya implementa try/catch por enricher con logging |
| R8 | Multimedia orch sin cambios | :white_check_mark: No impactados, siguen como sub-chain del MultimediaEnricher |
| R9 | Genericizar chain handlers | :white_check_mark: A6 - Puede hacerse como mejora posterior sin afectar la migracion |
| R10 | Eliminar traits | :white_check_mark: A4+A5 - Los gateways y factories reemplazan la logica de traits |
| R11 | DTOs tipados vs arrays | :white_check_mark: A1+A5 - EditorialContext (ya existe) reemplaza $resolveData |
| R12 | Paridad JSON output | :white_check_mark: A5 - Response factories verificadas contra output legacy |

**Result**: All green. Shape A selected.

---

## Decisions

| # | Decision | Status | Resolution |
|---|----------|--------|------------|
| D1 | Base class vs Pipeline migration | Resolved | Pipeline migration (Shape A). Base class (Shape B) no resuelve SRP. Pipeline ya existe. |
| D2 | Decorator vs Strategy para visibility | Resolved | Strategy inyectable (A2). Decorator no puede interceptar check interno. |
| D3 | Reescribir gateways o wrappear legacy clients | Resolved | Los HttpGateway ya existen y wrappean los clientes legacy. Solo verificar cobertura. |
| D4 | Big bang vs incremental migration | Resolved | Incremental por slices verticales. V1 conecta pipeline sin eliminar legacy. V2+ migra content types. |
| D5 | Enrichers existentes completos o hay gaps | Resolved | 7/17 covered, 4 enrichers missing (InsertedNews, RecommendedEditorials, BodyPhotos, OpeningMultimedia). See spike-a3. |
| D6 | Response factories producen JSON identico | Resolved | 2 factories missing (RecommendedEditorialResponseFactory, opening multimedia). Body/Section/Tag/Signature factories complete. |
| D7 | Async parallelism vs sync simplicity | Resolved | Accept sync-first approach. Gateways have *Async() methods for future optimization. Correctness over perf. |
| D8 | Legacy fallback (sourceEditorial) handling | Resolved | Absorb into EditorialHttpGateway::findById() transparently. No separate enricher needed. |

---

## Next Steps

- [x] Frame the problem
- [x] Capture requirements
- [x] Draft Shape A, B, C
- [x] Run fit check
- [x] Spike D5: verificar gap entre enrichers y execute() responsibilities (see spike-a3-enricher-gaps.md)
- [x] Spike D6: verificar paridad de response factories con output legacy (resolved in spike-a3)
- [x] Proceed to breadboard (Phase 6) -> 02_breadboard.md
- [x] Proceed to slicing (Phase 7) -> 03_slices.md
