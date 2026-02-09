# Feature: previum-editorial-preview

## Overview

Endpoint securizado `GET /previum/{editorialId}` con autenticación JWT validada contra microservicio externo. Usuarios con `user_type=previum` acceden a editoriales no publicados.

## Documents

| Document | Phase | Purpose |
|----------|-------|---------|
| [01_shaped_brief.md](./01_shaped_brief.md) | Shape | Frame, R0-R10, Shape A, Fit Check |
| [02_breadboard.md](./02_breadboard.md) | Shape | Places, Affordances, Wiring, Mermaid |
| [03_slices.md](./03_slices.md) | Shape | 3 vertical slices (V1: Auth, V2: Endpoint, V3: Verify) |
| [00_problem_statement.md](./00_problem_statement.md) | Plan Phase 1 | Problem + success criteria |
| [12_specs.md](./12_specs.md) | Plan Phase 2 | 6 functional specs |
| [13_integration_analysis.md](./13_integration_analysis.md) | Plan Phase 2 | Integration (CLEAR, 0 conflicts) |
| [15_solutions.md](./15_solutions.md) | Plan Phase 3 | SOLID solutions (24/25) |
| [16_architectural_impact.md](./16_architectural_impact.md) | Plan Phase 3 | Layers, risk |
| [30_tasks.md](./30_tasks.md) | Plan | 10 tasks by vertical slice |
| [50_state.md](./50_state.md) | State | Progress tracking |

## Architecture

```
GET /previum/{id}
    → Firewall (^/previum, stateless)
        → PreviumJwtAuthenticator
            → HttpTokenValidator → Auth Microservice (HTTP POST)
            → FirebaseJwtDecoder → decode response JWT
            → Check user_type === 'previum'
        → PreviumEditorialController
            → OrchestratorChain('previum')
                → PreviumEditorialOrchestrator (no isVisible check)
```

## Next Step

`/workflows:work previum-editorial-preview --role=backend` → Start with V1-001

---

**Status**: Shape + Plan COMPLETED — Ready for Implementation
