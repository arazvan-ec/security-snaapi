# Índice de Navegación

> Mapa completo del repositorio para encontrar rápidamente lo que necesitas.

---

## Primera Vez? Empieza Aquí

```bash
# Opción recomendada: Onboarding interactivo
/workflows:onboarding

# O si prefieres leer documentación
# Ver → QUICKSTART.md (5 minutos)
```

---

## Por Dónde Empezar

| Tu Situación | Lee Esto | Comando Alternativo |
|--------------|----------|---------------------|
| **Primera vez con el plugin** | [WELCOME.md](./WELCOME.md) | `/workflows:onboarding` |
| **Nuevo aquí** | [QUICKSTART.md](./QUICKSTART.md) | `/workflows:help` |
| **Quiero un ejemplo** | [TUTORIAL.md](./TUTORIAL.md) | - |
| **No entiendo un término** | [GLOSSARY.md](./GLOSSARY.md) | `/workflows:help concepts` |
| **No sé qué comando usar** | - | `/workflows:route` |
| **Quiero todo el detalle** | [README.md](./README.md) | - |
| **Soy un agente Claude** | [CLAUDE.md](./CLAUDE.md) | - |

---

## Estructura del Repositorio

```
workflow/
├── 📄 WELCOME.md             # Mensaje de bienvenida (nuevo!)
├── 📄 QUICKSTART.md          # Guía de 5 minutos
├── 📄 TUTORIAL.md            # Ejemplo práctico completo
├── 📄 GLOSSARY.md            # Definiciones de términos
├── 📄 INDEX.md               # Este archivo
├── 📄 README.md              # Documentación completa
├── 📄 CLAUDE.md              # Guía para agentes Claude
│
├── .ai/project/
│   └── specs/                # Living Specs del proyecto
│       ├── manifest.yaml     # Índice de todas las specs
│       └── {domain}/         # Specs organizadas por dominio
│
└── plugins/
    └── multi-agent-workflow/
        ├── 📁 agents/        # Agentes especializados
        ├── 📁 commands/      # Comandos ejecutables (incluye onboarding y help)
        ├── 📁 skills/        # Utilidades reutilizables
        └── 📁 core/          # Core del framework
```

---

## Guías y Documentación

| Archivo | Descripción | Audiencia |
|---------|-------------|-----------|
| [WELCOME.md](./WELCOME.md) | Mensaje de bienvenida post-instalación | Nuevos usuarios |
| [QUICKSTART.md](./QUICKSTART.md) | Empezar en 5 minutos | Nuevos usuarios |
| [TUTORIAL.md](./TUTORIAL.md) | Ejemplo paso a paso | Todos |
| [GLOSSARY.md](./GLOSSARY.md) | Diccionario de términos | Todos |
| [README.md](./README.md) | Documentación completa | Referencia |
| [CLAUDE.md](./CLAUDE.md) | Guía técnica para IA | Agentes Claude |

> **Tip**: Si es tu primera vez, usa `/workflows:onboarding` para una experiencia guiada.

---

## Reglas del Proyecto

| Archivo | Propósito | Cuándo Consultarlo |
|---------|-----------|-------------------|
| [framework_rules.md](./plugins/multi-agent-workflow/core/rules/framework_rules.md) | Reglas operacionales core | Siempre (always-loaded) |
| [testing-rules.md](./plugins/multi-agent-workflow/core/rules/testing-rules.md) | TDD, coverage, Ralph Wiggum | Al editar tests (scoped) |
| [security-rules.md](./plugins/multi-agent-workflow/core/rules/security-rules.md) | Trust model, seguridad | Al tocar auth/security/payment (scoped) |
| [git-rules.md](./plugins/multi-agent-workflow/core/rules/git-rules.md) | Branching, commits, conflictos | Durante operaciones git (scoped) |

---

## Agentes Disponibles

### Roles Principales

| Agente | Archivo | Función |
|--------|---------|---------|
| **Planner** | [planner.md](./plugins/multi-agent-workflow/agents/roles/planner.md) | Planifica features y arquitectura |
| **Backend** | [backend.md](./plugins/multi-agent-workflow/agents/roles/backend.md) | Implementa APIs y lógica servidor |
| **Frontend** | [frontend.md](./plugins/multi-agent-workflow/agents/roles/frontend.md) | Implementa UI React/TypeScript |
| **QA** | [qa.md](./plugins/multi-agent-workflow/agents/roles/qa.md) | Testing y validación |

### Agentes de Review

| Agente | Archivo | Especialidad |
|--------|---------|--------------|
| **Security** | [security-review.md](./plugins/multi-agent-workflow/agents/review/security-review.md) | OWASP, vulnerabilidades |
| **Performance** | [performance-review.md](./plugins/multi-agent-workflow/agents/review/performance-review.md) | Optimización, N+1 |
| **DDD Compliance** | [ddd-compliance.md](./plugins/multi-agent-workflow/agents/review/ddd-compliance.md) | Arquitectura correcta |
| **Code Review** | [code-review-ts.md](./plugins/multi-agent-workflow/agents/review/code-review-ts.md) | Estándares TypeScript |

### Agentes de Research

| Agente | Archivo | Especialidad |
|--------|---------|--------------|
| **Codebase Analyzer** | [codebase-analyzer.md](./plugins/multi-agent-workflow/agents/research/codebase-analyzer.md) | Analiza estructura existente |
| **Git Historian** | [git-historian.md](./plugins/multi-agent-workflow/agents/research/git-historian.md) | Historial y decisiones |
| **Dependency Auditor** | [dependency-auditor.md](./plugins/multi-agent-workflow/agents/research/dependency-auditor.md) | Vulnerabilidades deps |

### Agentes de Workflow

| Agente | Archivo | Especialidad |
|--------|---------|--------------|
| **Bug Reproducer** | [bug-reproducer.md](./plugins/multi-agent-workflow/agents/workflow/bug-reproducer.md) | Reproducir y documentar bugs |
| **Spec Analyzer** | [spec-analyzer.md](./plugins/multi-agent-workflow/agents/workflow/spec-analyzer.md) | Validar vs especificaciones |
| **Style Enforcer** | [style-enforcer.md](./plugins/multi-agent-workflow/agents/workflow/style-enforcer.md) | Estándares de código |

### Agentes de Design

| Agente | Archivo | Especialidad |
|--------|---------|--------------|
| **API Designer** | [api-designer.md](./plugins/multi-agent-workflow/agents/design/api-designer.md) | Contratos RESTful |
| **UI Verifier** | [ui-verifier.md](./plugins/multi-agent-workflow/agents/design/ui-verifier.md) | Validación UI/UX |

---

## Workflows (Comandos)

### Comandos de Setup y Onboarding

| Comando | Descripción | Cuándo Usarlo |
|---------|-------------|---------------|
| `/workflows:onboarding` | Experiencia guiada para nuevos usuarios | Primera vez usando el plugin |
| `/workflows:discover` | Análisis profundo del proyecto | Después de instalar, para que el plugin conozca tu proyecto |
| `/workflows:specs` | Gestión de Living Specs | Extraer, sincronizar o validar especificaciones |
| `/workflows:help` | Ayuda rápida y navegación | Cuando necesites orientación |
| `/workflows:route` | Router inteligente | No sabes qué comando usar |

### Comandos de Shaping (Pre-Planificación)

| Comando | Descripción | Cuándo Usarlo |
|---------|-------------|---------------|
| `/workflows:shape` | Explorar problema y solución antes de planificar | Features complejas o con scope difuso |

### Comandos Principales

| Comando | Descripción | Cuándo Usarlo |
|---------|-------------|---------------|
| `/workflows:plan` | Planificar feature | Inicio de feature (o después de shape) |
| `/workflows:work` | Implementar código | Después de planificar |
| `/workflows:review` | Review de calidad | Antes de merge |
| `/workflows:compound` | Capturar learnings | Después de approval |
| `/workflows:role` | Trabajar como rol | Desarrollo específico |
| `/workflows:sync` | Sincronizar estado | Entre sesiones |
| `/workflows:status` | Ver estado actual | Cualquier momento |

---

## Skills (Utilidades)

| Skill | Ubicación | Función |
|-------|-----------|---------|
| **consultant** | `skills/core/consultant/` | Consulta y análisis |
| **checkpoint** | `skills/core/checkpoint/` | Guardar/restaurar estado |
| **git-sync** | `skills/core/git-sync/` | Sincronización git |
| **test-runner** | `skills/quality/test-runner/` | Ejecutar tests |
| **coverage-checker** | `skills/quality/coverage-checker/` | Validar cobertura |
| **lint-fixer** | `skills/quality/lint-fixer/` | Arreglar estilo |
| **worktree-manager** | `skills/workflow/worktree-manager/` | Gestión worktrees |
| **commit-formatter** | `skills/workflow/commit-formatter/` | Formato commits |
| **changelog-generator** | `skills/compound/changelog-generator/` | Generar changelogs |
| **layer-validator** | `skills/compound/layer-validator/` | Validar capas DDD |
| **shaper** | `skills/shaper/` | Shaping: separar problema de solución |
| **breadboarder** | `skills/breadboarder/` | Breadboarding: affordances y slicing vertical |

---

## Metodologías Documentadas

| Metodología | Sección en README | Origen |
|-------------|-------------------|--------|
| Compound Engineering | §1 | Evolución Vibe Coding 2025 |
| Ralph Wiggum Pattern | §2 | Geoffrey Huntley |
| Agent Harnesses | §3 | Tendencia 2026 |
| Agent-Native Architecture | §4 | Diseño para agentes |
| Model Context Protocol | §5 | Anthropic |
| Multi-Agent Coordination | §6 | Orquestación |
| TDD + DDD | §7 | Best practices |
| Context Window Management | §8 | Optimización |
| Spec-Driven Development | §9 | Contratos primero |
| The 70% Problem | §10 | Addy Osmani |
| Shape Up (Shaping) | §11 | Ryan Singer |

---

## Flujo de Desarrollo Recomendado

### Para Nuevos Usuarios

```
Día 1: Onboarding
┌─────────────────────────────────────────────────────────────────┐
│  /workflows:onboarding  →  QUICKSTART.md  →  Primera feature   │
│        (5 min)               (5 min)           (10 min)         │
└─────────────────────────────────────────────────────────────────┘

Día 2-3: Aprendizaje Profundo
┌─────────────────────────────────────────────────────────────────┐
│  TUTORIAL.md    →    GLOSSARY.md    →    Feature real          │
│   (30-45 min)      (referencia)         (tu proyecto)          │
└─────────────────────────────────────────────────────────────────┘

Semana 1+: Dominio
┌─────────────────────────────────────────────────────────────────┐
│  README.md   →   Personalizar agentes   →   Contribuir mejoras │
│  (referencia)      (según necesidad)        (opcional)         │
└─────────────────────────────────────────────────────────────────┘
```

### Resumen de Progresión

```
1. /workflows:onboarding → Experiencia guiada (5 min)
2. QUICKSTART.md         → Instalación básica (5 min)
3. TUTORIAL.md           → Ejemplo práctico (30-45 min)
4. GLOSSARY.md           → Consultar términos confusos
5. README.md             → Profundizar en metodologías
6. global_rules.md       → Entender reglas del proyecto
7. Agentes               → Personalizar según necesidad
```

---

## FAQ de Navegación

**¿Dónde encuentro las reglas de arquitectura DDD?**
→ [ddd_rules.md](./plugins/multi-agent-workflow/rules/ddd_rules.md)

**¿Cómo personalizo los agentes?**
→ Edita los archivos `.md` en `plugins/multi-agent-workflow/agents/`

**¿Dónde está el estado de mi feature?**
→ `.ai/project/features/{feature-name}/50_state.md`

**¿Cómo agrego una nueva regla global?**
→ Edita [global_rules.md](./plugins/multi-agent-workflow/rules/global_rules.md)

**¿Qué archivo lee Claude primero?**
→ [CLAUDE.md](./CLAUDE.md)

---

## Documentación de Referencia (On-Demand)

| Archivo | Descripción | Cuándo Se Carga |
|---------|-------------|-----------------|
| [ROUTING_REFERENCE.md](./plugins/multi-agent-workflow/core/docs/ROUTING_REFERENCE.md) | Templates de preguntas, decision matrix | Routing complejo |
| [CONTEXT_ENGINEERING.md](./plugins/multi-agent-workflow/core/docs/CONTEXT_ENGINEERING.md) | Context isolation, fork model, Queen Agent | Referencia técnica |
| [KARPATHY_PRINCIPLES.md](./plugins/multi-agent-workflow/core/docs/KARPATHY_PRINCIPLES.md) | Principios de coding con IA | Referencia de principios |
| [SESSION_CONTINUITY.md](./plugins/multi-agent-workflow/core/docs/SESSION_CONTINUITY.md) | Snapshots, metrics, context management | Sesiones largas |

---

## Actualizaciones Recientes

| Fecha | Cambio | Archivo Afectado |
|-------|--------|------------------|
| 2026-02 | v2.5.0: Scoped rules, slim CLAUDE.md, ROUTING_REFERENCE.md | core/rules/, core/docs/, CLAUDE.md |
| 2026-02 | Sistema Living Specs | GLOSSARY.md, commands/workflows/specs.md |
| 2026-02 | Nuevo comando /workflows:discover | commands/workflows/discover.md |
| 2026-02 | Template de project profile | core/templates/project-profile-template.md |
| 2026-02 | Skill consultant mejorado | skills/consultant/SKILL.md |
| 2026-02 | Sistema de conocimiento documentado | GLOSSARY.md |
| 2026-02 | Nuevo comando /workflows:onboarding | commands/workflows/onboarding.md |
| 2026-02 | Nuevo comando /workflows:help | commands/workflows/help.md |
| 2026-02 | Mensaje de bienvenida | WELCOME.md |
| 2026-02 | v2.8.0: Shaping skills (Shape Up, Singer) | skills/shaper/, skills/breadboarder/, commands/workflows/shape.md |
| 2026-02 | Mejoras de onboarding | QUICKSTART.md, INDEX.md |
| 2026-01 | Evolution Governance | global_rules.md |
| 2026-01 | Beyond Vibe Coding | README.md |
| 2026-01 | Spec-Driven Development | README.md |
| 2026-01 | Mejoras de usabilidad | QUICKSTART, TUTORIAL, GLOSSARY, INDEX |
