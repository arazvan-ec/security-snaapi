# Feature: previum-editorial-preview

## Overview

Nuevo endpoint securizado `GET /previum/{editorialId}` que permite a usuarios con suscripción "previum" acceder a editoriales no publicados, validando JWT contra un microservicio externo de autenticación.

## Documents

| Document | Phase | Purpose |
|----------|-------|---------|
| [00_problem_statement.md](./00_problem_statement.md) | Phase 1 | Problem understanding and success criteria |
| [12_specs.md](./12_specs.md) | Phase 2 | Functional specs (WHAT) — 6 specs defined |
| [13_integration_analysis.md](./13_integration_analysis.md) | Phase 2 | Integration with existing architecture |
| [15_solutions.md](./15_solutions.md) | Phase 3 | Technical solutions with SOLID (HOW) |
| [16_architectural_impact.md](./16_architectural_impact.md) | Phase 3 | Layers, modules, and risk assessment |
| [30_tasks.md](./30_tasks.md) | Tasks | 10 tasks ordered by dependency |
| [50_state.md](./50_state.md) | State | Current progress tracking |

## Key Metrics

| Metric | Value |
|--------|-------|
| **SOLID Score** | 24/25 (Approved) |
| **Files to Create** | 14 |
| **Files to Modify** | 5 |
| **Estimated LOC** | ~750 |
| **Trust Level** | HIGH |
| **Backward Compatible** | YES |
| **Breaking Changes** | NONE |

## Architecture

```
GET /previum/{id}
    → [Symfony Security Firewall: previum]
        → PreviumJwtAuthenticator
            → HttpTokenValidator (HTTP POST to auth microservice)
            → FirebaseJwtDecoder (decode response JWT)
            → Check user_type === 'previum'
        → PreviumEditorialController
            → OrchestratorChain::handler('previum')
                → PreviumEditorialOrchestrator
                    → (same data aggregation as EditorialOrchestrator, without isVisible() check)
```

## Next Step

Run `/workflows:work previum-editorial-preview --role=backend` to start implementation with task BE-001.

---

**Created**: 2026-02-09
**Status**: Planning COMPLETED — Ready for Implementation
