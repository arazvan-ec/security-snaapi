# Solutions: orchestrator-solid-refactor

> Phase 3: COMO implementar cada spec (diseno tecnico con SOLID como constraint)

---

## SOLID Baseline

### Current State (Legacy Orchestrators)

| Principle | Score (0-5) | Violations |
|-----------|-------------|------------|
| **S** - SRP | 1/5 | 17 responsabilidades en una clase: fetch, transform, async, routing, visibility... |
| **O** - OCP | 2/5 | Chain pattern permite anadir orchestrators, pero no extender logica interna |
| **L** - LSP | 3/5 | PreviumEditorialOrchestrator subtly violates (different visibility behavior, undocumented) |
| **I** - ISP | 3/5 | EditorialOrchestratorInterface es pequena (2 metodos), pero acepta Request (HTTP coupling) |
| **D** - DIP | 2/5 | Depende de QueryLegacyClient, MultimediaOrchestratorHandler, JournalistFactory (concretas) |
| **TOTAL** | **11/25** | Grade F - Rejected |

### Target State (Pipeline Architecture)

| Principle | Target Score | How |
|-----------|-------------|-----|
| **S** - SRP | 5/5 | Cada enricher = 1 responsabilidad, cada gateway = 1 fetch, cada factory = 1 transform |
| **O** - OCP | 5/5 | Nuevos enrichers via tag, nuevas strategies via interface, pipeline extensible |
| **L** - LSP | 5/5 | Todas las implementations son substituibles por sus interfaces |
| **I** - ISP | 4/5 | Interfaces Gateway segregadas. EditorialOrchestratorInterface conserva Request param (-1) |
| **D** - DIP | 5/5 | Todo depende de abstracciones: GatewayInterface, EnricherInterface, VisibilityStrategyInterface |
| **TOTAL** | **24/25** | Grade A - SOLID Compliant |

---

## Solution for SPEC-F01 + SPEC-F03: Pipeline Adapter (Shape A1)

**Approach**: Crear `PipelineEditorialOrchestrator` que implementa `EditorialOrchestratorInterface` y delega al `EnrichmentPipeline` existente. Es un adaptador thin (~60 lineas) que:
1. Extrae ID del Request
2. Crea EditorialContext(id)
3. Ejecuta pipeline.process(context)
4. Aplica visibility strategy
5. Delega a EditorialResponseFactory para producir JSON

**SOLID Compliance**:

| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | Adapter solo coordina: extract -> pipeline -> check -> transform. No logica de negocio. | Adapter |
| **O** - OCP | Nuevo content type = nueva strategy + config. No modifica adapter. | Strategy + DI |
| **L** - LSP | Implementa EditorialOrchestratorInterface identicamente al legacy. Chain handler no nota diferencia. | Interface |
| **I** - ISP | Acepta Request (interface legacy), pero internamente solo usa getId(). | Adapter |
| **D** - DIP | Depende de EnrichmentPipeline, VisibilityStrategyInterface, EditorialResponseFactory (abstracciones). | DI |

**Files to Create**:
```
src/Orchestrator/Chain/PipelineEditorialOrchestrator.php
```

**Expected SOLID Score**: 24/25

---

## Solution for SPEC-F02: Visibility Strategy (Shape A2)

**Approach**: Strategy pattern con `VisibilityStrategyInterface` y dos implementaciones.

**SOLID Compliance**:

| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | Cada strategy tiene una unica regla de visibilidad | Strategy |
| **O** - OCP | Nuevas reglas = nueva implementation, sin modificar existentes | Strategy |
| **L** - LSP | Ambas implementations son substituibles en el adapter | Strategy |
| **D** - DIP | Adapter depende de VisibilityStrategyInterface, no de implementations | DI |

**Files to Create**:
```
src/Application/Strategy/Visibility/VisibilityStrategyInterface.php
src/Application/Strategy/Visibility/PublishedOnlyStrategy.php
src/Application/Strategy/Visibility/AllowAllStrategy.php
```

**Class Design**:
```php
interface VisibilityStrategyInterface
{
    /** @throws EditorialNotPublishedYetException */
    public function checkVisibility(NewsBase $editorial): void;
}

final class PublishedOnlyStrategy implements VisibilityStrategyInterface
{
    public function checkVisibility(NewsBase $editorial): void
    {
        if (!$editorial->isVisible()) {
            throw new EditorialNotPublishedYetException();
        }
    }
}

final class AllowAllStrategy implements VisibilityStrategyInterface
{
    public function checkVisibility(NewsBase $editorial): void
    {
        // No visibility check - all editorials are accessible
    }
}
```

**Expected SOLID Score**: 25/25

---

## Solution for SPEC-F04 + SPEC-F07 + SPEC-F08: Missing Enrichers (Shape A3)

**Approach**: Crear 4 enrichers que siguen el mismo patron que los 7 existentes.

### OpeningMultimediaEnricher (SPEC-F07)

| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | Solo obtiene opening multimedia | Enricher |
| **D** - DIP | Depende de MultimediaGatewayInterface | Port/Adapter |

**Files**: `src/Application/Pipeline/Enricher/OpeningMultimediaEnricher.php`

### BodyPhotosEnricher (SPEC-F08)

| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | Solo extrae y obtiene fotos del body | Enricher |
| **D** - DIP | Depende de MultimediaGatewayInterface | Port/Adapter |

**Files**: `src/Application/Pipeline/Enricher/BodyPhotosEnricher.php`

### InsertedNewsEnricher (SPEC-F05)

| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | Solo enriquece noticias insertadas | Enricher |
| **D** - DIP | Depende de EditorialGateway, SectionGateway, JournalistGateway, MultimediaGateway | Port/Adapter |

**Files**: `src/Application/Pipeline/Enricher/InsertedNewsEnricher.php`

**Note**: Este enricher es el mas complejo (mini-orchestrador). Internamente usa multiples gateways para cada sub-editorial. Esto es aceptable porque su RESPONSABILIDAD es una sola: enriquecer las noticias insertadas. Que necesite varios gateways es parte de esa responsabilidad, no una violacion de SRP.

### RecommendedEditorialsEnricher (SPEC-F06)

| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | Solo enriquece editoriales recomendados | Enricher |
| **D** - DIP | Mismas gateway interfaces que InsertedNewsEnricher | Port/Adapter |

**Files**: `src/Application/Pipeline/Enricher/RecommendedEditorialsEnricher.php`

**Note**: Patron identico a InsertedNewsEnricher. Podria extraerse un `SubEditorialEnrichmentHelper` para compartir logica, pero esto se deja como mejora futura (YAGNI hasta que haya un tercer caso).

**Expected SOLID Score**: 24/25 (todos los enrichers siguen el mismo patron probado)

---

## Solution for SPEC-F09: Gateway Cleanup (Shape A4)

**Approach**: Crear `CommentGatewayInterface` + `CommentHttpGateway`, actualizar CommentsEnricher.

**SOLID Compliance**:

| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | Gateway solo obtiene datos de comentarios | Port/Adapter |
| **D** - DIP | CommentsEnricher depende de interface, no de QueryLegacyClient | Port/Adapter |

**Files to Create**:
```
src/Domain/Port/Gateway/CommentGatewayInterface.php
src/Infrastructure/Gateway/Http/CommentHttpGateway.php
```

**Files to Modify**:
```
src/Application/Pipeline/Enricher/CommentsEnricher.php  (change constructor injection)
config/services/pipeline.yaml  (add CommentGatewayInterface alias)
```

**Gateway Extension**:
```
src/Domain/Port/Gateway/MultimediaGatewayInterface.php  (add findOpeningMultimediaById)
src/Infrastructure/Gateway/Http/MultimediaHttpGateway.php  (implement using QueryMultimediaOpeningClient)
```

**Expected SOLID Score**: 25/25

---

## Solution for SPEC-F10: Legacy Removal (Shape A7)

**Approach**: Una vez V1-V4 completados y verificados, eliminar orchestrators legacy.

**Files to Delete**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php          (536 lines)
src/Orchestrator/Chain/PreviumEditorialOrchestrator.php   (503 lines)
tests/Orchestrator/Chain/EditorialOrchestratorTest.php    (1700 lines)
```

**Files to Modify**:
```
config/packages/orchestrators.yaml  (remove legacy registrations if not already done)
```

**Files to Preserve**:
```
src/Orchestrator/Chain/EditorialOrchestratorLegacy.php  (trigger_deprecation notice)
```

---

## Solution for Response Factory Gaps

**New Factory**: `RecommendedEditorialResponseFactory`
- Transforma datos de editorial recomendado al mismo formato que `RecommendedEditorialsDataTransformer` legacy
- Usado por `EditorialResponseFactory` para componer la respuesta final

**Opening Multimedia**: La transformacion de opening multimedia se integra en el flujo existente de `MultimediaResponseFactory` + `MediaDataTransformerHandler`. El `OpeningMultimediaEnricher` alimenta `context->setMultimediaOpening()` que luego `EditorialResponseFactory` consume.

---

## Chain Handler Wiring (Shape A6)

**Config approach**: Registrar dos instancias del mismo `PipelineEditorialOrchestrator` con diferentes strategies.

```yaml
# config/packages/orchestrators.yaml (updated)
services:
    # Editorial (published only)
    app.orchestrator.editorial:
        class: App\Orchestrator\Chain\PipelineEditorialOrchestrator
        arguments:
            $visibilityStrategy: '@App\Application\Strategy\Visibility\PublishedOnlyStrategy'
            $contentType: 'editorial'
        tags: ['app.orchestrators']

    # Previum (all editorials)
    app.orchestrator.previum:
        class: App\Orchestrator\Chain\PipelineEditorialOrchestrator
        arguments:
            $visibilityStrategy: '@App\Application\Strategy\Visibility\AllowAllStrategy'
            $contentType: 'previum'
        tags: ['app.orchestrators']
```

---

## SOLID Score Verification

| Component | S | O | L | I | D | Total |
|-----------|---|---|---|---|---|-------|
| PipelineEditorialOrchestrator | 5 | 5 | 5 | 4 | 5 | 24/25 |
| VisibilityStrategy (interface + 2 impls) | 5 | 5 | 5 | 5 | 5 | 25/25 |
| OpeningMultimediaEnricher | 5 | 5 | 5 | 5 | 5 | 25/25 |
| BodyPhotosEnricher | 5 | 5 | 5 | 5 | 5 | 25/25 |
| InsertedNewsEnricher | 5 | 5 | 5 | 5 | 5 | 25/25 |
| RecommendedEditorialsEnricher | 5 | 5 | 5 | 5 | 5 | 25/25 |
| CommentGateway (interface + impl) | 5 | 5 | 5 | 5 | 5 | 25/25 |
| RecommendedEditorialResponseFactory | 5 | 5 | 5 | 5 | 5 | 25/25 |
| **Average** | | | | | | **24.9/25** |

**Grade: A - SOLID Compliant** (up from F - 11/25)

---

## Pattern Summary

| Pattern | Where Used | SOLID Principles |
|---------|-----------|-----------------|
| **Adapter** | PipelineEditorialOrchestrator (chain <-> pipeline bridge) | SRP, DIP |
| **Strategy** | VisibilityStrategyInterface (editorial vs previum) | OCP, SRP, LSP |
| **Pipeline** | EnrichmentPipeline + EnricherInterface (ordered enrichment) | SRP, OCP |
| **Port/Adapter** | *GatewayInterface + *HttpGateway (hexagonal architecture) | DIP, OCP |
| **Factory** | *ResponseFactory (context -> JSON transformation) | SRP, DIP |
| **Chain of Responsibility** | OrchestratorChainHandler (content type routing) | OCP, SRP |
| **Specification** | EditorialIsPublishedSpecification (business rules) | SRP |
