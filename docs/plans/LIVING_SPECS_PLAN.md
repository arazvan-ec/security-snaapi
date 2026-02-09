# Plan de Mejora: Sistema de Living Specs

> Transformar `/workflows:discover` de detección de patrones a extracción de especificaciones vivas.

**Fecha**: 2026-02-03
**Estado**: Planificación
**Impacto**: Alto

---

## Resumen Ejecutivo

### Problema Actual

El comando `/workflows:discover` actualmente detecta **patrones** (cómo está estructurado el código) pero NO extrae **especificaciones** (qué hace realmente el código):

| Actualmente Detecta | Lo Que Falta |
|---------------------|--------------|
| "Usa arquitectura DDD" | "Entidad User tiene: id, email, name, role" |
| "Tiene patrón Repository" | "UserRepository define: findById, findByEmail, save" |
| "API REST detectada" | "POST /users espera: {email, password, name}" |
| "Auth JWT detectado" | "Token expira en 1h, requiere Bearer" |

### Solución Propuesta

Crear un **Sistema de Living Specs** que:

1. **Extrae specs del código existente** - No solo patrones, sino especificaciones reales
2. **Mantiene specs actualizadas** - Auto-actualiza cuando se completan features
3. **Fuerza pensamiento de integración** - Cada feature se piensa como extensión de la arquitectura existente

---

## Arquitectura Propuesta

### Nueva Estructura de Archivos

```
.ai/
├── project/
│   ├── specs/                          # NUEVO: Especificaciones Vivas
│   │   ├── entities/                   # Specs de entidades extraídas
│   │   │   ├── user.yaml               # Entidad User: campos, reglas, relaciones
│   │   │   ├── order.yaml              # Entidad Order
│   │   │   └── _index.yaml             # Índice de todas las entidades
│   │   │
│   │   ├── api-contracts/              # Contratos API extraídos
│   │   │   ├── users-api.yaml          # /api/users endpoints
│   │   │   ├── orders-api.yaml         # /api/orders endpoints
│   │   │   └── _index.yaml             # Índice de todos los endpoints
│   │   │
│   │   ├── business-rules/             # Reglas de negocio extraídas
│   │   │   ├── validation-rules.yaml   # Formato email, fuerza password
│   │   │   ├── authorization-rules.yaml# Quién puede hacer qué
│   │   │   └── _index.yaml             # Índice de reglas
│   │   │
│   │   ├── architectural-constraints/  # Restricciones arquitectónicas
│   │   │   ├── layer-dependencies.yaml # Qué capa importa qué
│   │   │   ├── naming-conventions.yaml # Convenciones enforceadas
│   │   │   └── _index.yaml             # Índice de restricciones
│   │   │
│   │   └── spec-manifest.yaml          # Manifiesto maestro con timestamps
│   │
│   ├── intelligence/                   # EXISTENTE (mejorado)
│   │   └── project-profile.md          # Ahora enlaza a specs
│   │
│   └── config.yaml                     # EXISTENTE (con rutas a specs)
```

### Ejemplo de Spec de Entidad

```yaml
# .ai/project/specs/entities/user.yaml
entity: User
source_file: src/domain/entities/User.ts
last_extracted: 2026-02-03T10:30:00Z
confidence: 95%

fields:
  - name: id
    type: UUID
    constraints: [primary_key, auto_generated]

  - name: email
    type: string
    constraints: [unique, not_null]
    validation: email_format

  - name: password
    type: string
    constraints: [not_null, min_length:8]
    storage: hashed_bcrypt

  - name: role
    type: enum
    values: [admin, user, guest]
    default: user

  - name: createdAt
    type: datetime
    constraints: [auto_generated]

relationships:
  - type: has_many
    target: Order
    foreign_key: user_id

  - type: has_one
    target: Profile
    foreign_key: user_id

business_rules:
  - "Email must be unique across all users"
  - "Password must be hashed before storage"
  - "Role defaults to 'user' on creation"

extracted_from:
  - src/domain/entities/User.ts
  - src/domain/value-objects/Email.ts
  - src/infrastructure/repositories/UserRepository.ts
```

### Ejemplo de Contrato API Extraído

```yaml
# .ai/project/specs/api-contracts/users-api.yaml
api: Users
base_path: /api/users
version: v1
last_extracted: 2026-02-03T10:30:00Z

endpoints:
  - method: POST
    path: /
    name: createUser
    description: "Create a new user account"
    request:
      content_type: application/json
      body:
        email: { type: string, required: true, validation: email }
        password: { type: string, required: true, min_length: 8 }
        name: { type: string, required: true }
    response:
      201:
        body: { id: UUID, email: string, name: string, createdAt: datetime }
      400:
        body: { error: string, details: array }
      409:
        body: { error: "Email already exists" }
    auth: none

  - method: GET
    path: /:id
    name: getUserById
    description: "Get user by ID"
    request:
      params:
        id: { type: UUID, required: true }
    response:
      200:
        body: { id: UUID, email: string, name: string, role: enum }
      404:
        body: { error: "User not found" }
    auth: Bearer JWT
    permissions: [self, admin]

extracted_from:
  - src/presentation/controllers/UserController.ts
  - src/presentation/routes/users.ts
```

---

## Flujos Mejorados

### 1. `/workflows:discover` Mejorado

```
FLUJO ACTUAL:
detect stack → detect architecture → detect patterns → generate profile

FLUJO PROPUESTO:
detect stack → detect architecture → EXTRACT SPECS → detect patterns → generate profile
                                          │
                                          ├── Extract entities
                                          ├── Extract API contracts
                                          ├── Extract business rules
                                          └── Extract architectural constraints
```

**Nuevo output:**

```markdown
## Especificaciones Extraídas

### Entidades (5 encontradas)
| Entidad | Campos | Relaciones | Archivo Fuente |
|---------|--------|------------|----------------|
| User | 5 | 2 | src/domain/entities/User.ts |
| Order | 8 | 3 | src/domain/entities/Order.ts |
| Product | 6 | 1 | src/domain/entities/Product.ts |
| ...

### API Contracts (12 endpoints)
| Método | Path | Auth | Extraído de |
|--------|------|------|-------------|
| POST | /api/users | none | UserController.ts |
| GET | /api/users/:id | JWT | UserController.ts |
| ...

### Reglas de Negocio (8 encontradas)
- Email debe ser único en el sistema
- Password mínimo 8 caracteres
- Solo admin puede crear otros admins
- ...

### Restricciones Arquitectónicas
- Domain layer no importa de Infrastructure
- Todos los repositorios implementan interfaz de Domain
- Controllers solo llaman a Application services
- ...
```

### 2. `/workflows:plan` con Pensamiento Arquitectónico

```
FLUJO ACTUAL:
understand request → define specs → design solution → breakdown tasks

FLUJO PROPUESTO:
LOAD PROJECT SPECS → understand request → INTEGRATION ANALYSIS → define specs → design solution → breakdown tasks
        │                                        │
        │                                        ├── ¿Qué entidades existentes se afectan?
        │                                        ├── ¿Qué endpoints se modifican/agregan?
        │                                        ├── ¿Qué reglas de negocio cambian?
        │                                        └── ¿Qué capas arquitectónicas se tocan?
        │
        └── Carga specs/entities/, specs/api-contracts/, etc.
```

**Nuevo output en planning:**

```markdown
## Análisis de Integración Arquitectónica

### Contexto del Proyecto
Tu proyecto tiene:
- 5 entidades: User, Order, Product, Category, Review
- 12 endpoints API
- 8 reglas de negocio documentadas

### Impacto de esta Feature

#### Entidades
| Entidad | Acción | Cambios |
|---------|--------|---------|
| User | EXTENDER | +campo: `preferences` |
| NEW: UserPreference | CREAR | 4 campos, relación con User |

#### API
| Endpoint | Acción | Cambios |
|----------|--------|---------|
| GET /api/users/:id | MODIFICAR | +incluir preferences en response |
| NEW: PATCH /api/users/:id/preferences | CREAR | Nuevo endpoint |

#### Reglas de Negocio
| Regla | Acción |
|-------|--------|
| NEW: "Preferences son opcionales" | AGREGAR |
| NEW: "Solo el usuario puede modificar sus preferences" | AGREGAR |

#### Capas Afectadas
- Domain: Nueva entidad UserPreference, modificar User
- Application: Nuevo use case UpdateUserPreferences
- Infrastructure: Nuevo repository, migración DB
- Presentation: Modificar UserController, nueva ruta

### Estimación de Scope
- Archivos a modificar: 8
- Archivos nuevos: 4
- Migraciones DB: 1
- Tests nuevos estimados: 12
```

### 3. `/workflows:compound` con Actualización de Specs

```
FLUJO ACTUAL:
analyze commits → extract patterns → update rules → log learnings

FLUJO PROPUESTO:
analyze commits → extract patterns → UPDATE PROJECT SPECS → update rules → log learnings
                                            │
                                            ├── Diff specs antes/después
                                            ├── Merge nuevas entidades
                                            ├── Merge nuevos endpoints
                                            ├── Merge nuevas reglas
                                            └── Update spec-manifest.yaml
```

**Nuevo output en compound:**

```markdown
## Actualización de Especificaciones

### Specs Actualizadas
| Tipo | Archivo | Cambio |
|------|---------|--------|
| Entity | specs/entities/user.yaml | +campo: preferences |
| Entity | specs/entities/user-preference.yaml | NUEVO |
| API | specs/api-contracts/users-api.yaml | +endpoint: PATCH preferences |
| Rule | specs/business-rules/user-rules.yaml | +2 reglas |

### Spec Manifest
```yaml
last_update: 2026-02-03T15:30:00Z
updated_by: feature/user-preferences
changes:
  - entity: User (extended)
  - entity: UserPreference (new)
  - endpoint: PATCH /users/:id/preferences (new)
  - rules: +2 new rules
```
```

---

## Plan de Implementación

### Fase 1: Fundamentos (Días 1-5)

| Tarea | Descripción | Entregable |
|-------|-------------|------------|
| 1.1 | Crear JSON schemas para specs | `core/schemas/entity_spec.json`, `business_rule_spec.json`, `architectural_constraint.json` |
| 1.2 | Crear templates YAML | `core/templates/entity_spec.yaml`, etc. |
| 1.3 | Crear estructura de directorios | `.ai/project/specs/` scaffolding |
| 1.4 | Documentar formato de specs | `core/docs/LIVING_SPECS.md` |

### Fase 2: Extracción (Días 6-12)

| Tarea | Descripción | Entregable |
|-------|-------------|------------|
| 2.1 | Implementar extracción de entidades | Actualizar `discover.md` con Step 3 |
| 2.2 | Implementar extracción de API | Actualizar `discover.md` con Step 4 |
| 2.3 | Implementar extracción de reglas | Actualizar `discover.md` con Step 5 |
| 2.4 | Implementar extracción de constraints | Actualizar `discover.md` con Step 6 |
| 2.5 | Crear agente spec-extractor | `agents/workflow/spec-extractor.md` |

### Fase 3: Integración en Planning (Días 13-18)

| Tarea | Descripción | Entregable |
|-------|-------------|------------|
| 3.1 | Agregar carga de specs en plan | Actualizar `plan.md` con Step 0 |
| 3.2 | Implementar análisis de integración | Nuevo output en Phase 2 |
| 3.3 | Agregar visualización de impacto | Nuevo output en Phase 3 |
| 3.4 | Mejorar spec-analyzer | Comparar con project specs |

### Fase 4: Auto-Actualización (Días 19-24)

| Tarea | Descripción | Entregable |
|-------|-------------|------------|
| 4.1 | Agregar spec diff en compound | Actualizar `compound.md` |
| 4.2 | Implementar merge de specs | Crear `skills/spec-merger/SKILL.md` |
| 4.3 | Agregar spec-manifest tracking | Auto-update timestamps |
| 4.4 | Crear comando refresh | `/workflows:specs refresh` |

### Fase 5: Validación (Días 25-30)

| Tarea | Descripción | Entregable |
|-------|-------------|------------|
| 5.1 | Probar con proyecto real | Ejecutar en SNAAPI |
| 5.2 | Refinar extracción | Mejorar confidence scores |
| 5.3 | Documentar edge cases | Actualizar docs |
| 5.4 | Crear troubleshooting | FAQ de specs |

---

## Comandos Nuevos Propuestos

### `/workflows:specs`

```bash
# Ver todas las specs del proyecto
/workflows:specs

# Ver specs de un tipo específico
/workflows:specs entities
/workflows:specs api
/workflows:specs rules

# Refrescar specs (re-extraer del código)
/workflows:specs refresh

# Ver diff entre código y specs (detectar drift)
/workflows:specs drift

# Validar que código cumple con specs
/workflows:specs validate
```

### Flags para comandos existentes

```bash
# Discover con extracción de specs
/workflows:discover --full --extract-specs

# Plan mostrando impacto en specs
/workflows:plan feature-name --show-impact

# Compound con auto-update de specs
/workflows:compound feature-name --update-specs
```

---

## Métricas de Éxito

| Métrica | Objetivo | Cómo Medir |
|---------|----------|------------|
| Precisión de extracción | >85% | Comparar specs extraídas vs. manual |
| Cobertura de entidades | >90% | Entidades detectadas / entidades reales |
| Cobertura de API | >95% | Endpoints detectados / endpoints reales |
| Uso en planning | 100% | Plans que cargan specs existentes |
| Actualización automática | >80% | Features que actualizan specs en compound |

---

## Riesgos y Mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| Extracción imprecisa | Media | Alto | Permitir corrección manual, confidence scores |
| Spec drift (código cambia sin actualizar specs) | Alta | Medio | Pre-commit hooks, comando `specs drift` |
| Complejidad para usuarios | Media | Medio | Hacer specs opcionales, progressive disclosure |
| Performance en codebases grandes | Baja | Bajo | Extracción incremental, caching |

---

## Próximos Pasos Inmediatos

1. **Aprobar este plan** - Revisar y ajustar según feedback
2. **Comenzar Fase 1** - Crear schemas y estructura
3. **Prototipo de extracción** - Probar extracción de entidades en un caso simple
4. **Iterar** - Refinar basado en resultados del prototipo

---

## Apéndice: Diferencia Conceptual

### Antes (Pattern Detection)
```
"El proyecto usa DDD con estas carpetas: domain/, application/, infrastructure/"
```

### Después (Spec Extraction)
```
"El proyecto tiene estas especificaciones concretas:
- User entity: id (UUID), email (unique, validated), password (hashed), role (enum: admin|user)
- POST /api/users: expects {email, password, name}, returns {id, email, name, createdAt}
- Business rule: 'Email must be unique across all users'
- Constraint: 'Domain layer cannot import from Infrastructure'"
```

La diferencia es entre saber **cómo está organizado** vs. saber **qué existe realmente**.
