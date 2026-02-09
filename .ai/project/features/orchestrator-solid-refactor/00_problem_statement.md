# Problem Statement: orchestrator-solid-refactor

## Request Analysis

**Original Request**: Refactorizar los orchestrators cumpliendo SOLID, teniendo en cuenta todas las caracteristicas del proyecto para elegir la decision correcta
**Request Type**: refactor
**Affected Areas**: Orchestrator layer, Pipeline architecture, Gateway layer, Response factories
**Confidence Level**: 95% (shaped brief + spike provide full context)

---

## What We're Building

Migracion completa de los orchestrators monoliticos legacy (`EditorialOrchestrator`, `PreviumEditorialOrchestrator`) a la arquitectura Pipeline+Enricher que ya existe parcialmente en el proyecto. El objetivo es conectar el codigo SOLID existente (pipeline, enrichers, gateways, factories, specifications, strategies) al flujo de peticiones HTTP, eliminando los orchestrators de 500+ lineas y su 98% de codigo duplicado.

## Why It's Needed

1. **Deuda tecnica critica**: 1039 lineas de codigo duplicado entre dos orchestrators
2. **Riesgo de regresion**: Cualquier cambio en la logica de enriquecimiento requiere modificar 2 clases identicas
3. **Codigo muerto**: La arquitectura Pipeline+Enricher existe desde hace tiempo pero no esta conectada. Inversiones anteriores no estan dando retorno.
4. **Testeabilidad**: EditorialOrchestrator tiene tests con 18 mocks y reflection hacks. PreviumEditorialOrchestrator tiene 0 tests en produccion.
5. **SRP violado**: Cada orchestrator tiene 17 responsabilidades y 20 dependencias en constructor.

## Who Benefits

- **Desarrolladores**: Pueden modificar un enricher individual sin tocar clases de 500+ lineas
- **QA**: Cada enricher se testea independientemente con mocks simples
- **Operaciones**: Graceful degradation (si un enricher falla, el resto continua)
- **Producto**: Nuevos content types (como Previum) se crean con una strategy + config, no copiando 500 lineas

## Constraints

### Technical
- Symfony 6.4 / PHP 8.1+
- Must maintain `EditorialOrchestratorInterface` contract (chain handler depends on it)
- Must produce identical JSON output to legacy (no API breaking changes)
- Gateways already wrap legacy HTTP clients (no need to reimplement transport)
- `EnrichmentPipeline`, `EnricherInterface`, `EditorialContext`, 7 enrichers, 6 gateway interfaces, 9 response factories already exist

### Business
- Backward compatibility obligatoria (los controllers no cambian)
- La API publica no debe cambiar (format JSON identico)
- Previum JWT authentication (feature reciente) debe seguir funcionando

### Architecture
- Hexagonal architecture (Port/Adapter) ya establecida en gateways
- Chain of Responsibility pattern para orchestrators (OrchestratorChainHandler)
- Pipeline pattern para enrichers (EnrichmentPipeline con prioridades)
- Strategy pattern para field extractors (EditorialFieldExtractorChain)
- Specification pattern para business rules (EditorialIsPublishedSpecification, etc.)

## Success Criteria

1. `EditorialOrchestrator.php` (536 lines) y `PreviumEditorialOrchestrator.php` (503 lines) eliminados
2. JSON output identico al legacy para todos los tipos de editorial
3. SOLID score >= 22/25 en el nuevo codigo
4. Cada enricher testeable individualmente (no reflection, no 18-mock setUp)
5. 0% duplicacion entre editorial y previum content types
6. Pipeline conectado al chain handler via adapter
7. Todos los tests existentes siguen pasando
