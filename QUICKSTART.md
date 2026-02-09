# Quick Start - AI Workflow en 5 Minutos

> Empieza a desarrollar con IA en minutos, no horas.

## Prerequisitos

- Claude Code CLI instalado
- Git configurado
- Un proyecto existente o nuevo

### Verificación Rápida

Antes de empezar, verifica que tu entorno esté listo:

```bash
# Verificar Git
git --version

# Verificar configuración de Git
git config user.name && git config user.email

# Verificar que estás en un proyecto Git
git status
```

Si algo falla:

| Problema | Solución |
|----------|----------|
| Git no instalado | `brew install git` (macOS) o `apt install git` (Linux) |
| Git no configurado | `git config --global user.name "Tu Nombre"` |
| No es repo Git | `git init` |

> **Tip**: También puedes ejecutar `/workflows:onboarding --verify` para verificación automática.

---

## Paso 1: Instalar el Plugin (30 segundos)

```bash
# Añadir el workflow como plugin
/plugin marketplace add https://github.com/arazvan-ec/workflow
/plugin install multi-agent-workflow
```

---

## Paso 2: Que el Plugin Conozca tu Proyecto (1 minuto)

```bash
# Análisis profundo del proyecto
/workflows:discover --full
```

Esto analiza automáticamente:
- Stack tecnológico (frameworks, lenguajes, bases de datos)
- Arquitectura (DDD, MVC, Clean, etc.)
- Convenciones de código
- Patrones existentes
- Archivos de referencia para usar como templates
- **Living Specs**: Extrae especificaciones del código existente

**Resultado**: Genera un perfil completo en `.ai/project/intelligence/project-profile.md` y specs en `.ai/project/specs/`

> **Nota**: Las Living Specs se extraen automáticamente durante discover. Usa `/workflows:specs` para gestionar specs manualmente o detectar drift.

> **Tip**: Ejecuta `/workflows:discover --refresh` después de cambios importantes (nuevas librerías, refactors, etc.)

---

## Paso 3: Tu Primera Feature (2 minutos)

### Planificar

```bash
# Ejemplo: Crear sistema de autenticación
/workflows:plan user-authentication
```

Esto genera automáticamente:
- Análisis de requisitos
- Arquitectura
- Contratos de API
- Tareas para backend y frontend

### Implementar

```bash
# Backend primero
/workflows:work user-authentication --mode=roles --role=backend

# Frontend después (o en paralelo en otra terminal)
/workflows:work user-authentication --mode=roles --role=frontend
```

### Revisar

```bash
# QA automático
/workflows:review user-authentication
```

---

## Paso 4: Entender el Flujo (1 minuto)

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   PLAN      │───▶│   WORK      │───▶│   REVIEW    │───▶│  COMPOUND   │
│  (80%)      │    │  (15%)      │    │   (4%)      │    │   (1%)      │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
   Planner          Backend/Frontend      QA              Learnings
```

**Regla de oro**: 80% planificación, 20% ejecución.

---

## Comandos Esenciales

| Comando | Qué hace | Cuándo usarlo |
|---------|----------|---------------|
| `/workflows:discover` | Analiza tu proyecto | Después de instalar el plugin |
| `/workflows:plan` | Planifica una feature | Siempre primero |
| `/workflows:work` | Implementa código | Después de planificar |
| `/workflows:review` | Revisa calidad | Antes de merge |
| `/workflows:status` | Ver progreso | Cuando quieras |
| `/workflows:sync` | Sincronizar estado | Entre sesiones |
| `/workflows:skill-dev` | Crear/editar/probar skills | Desarrollo de skills |

---

## Conceptos Clave (30 segundos)

| Concepto | Significado |
|----------|-------------|
| **Compound Engineering** | Cada tarea hace las siguientes más fáciles |
| **Context Engineering** | Curar qué ve el modelo en cada momento |
| **context: fork** | Agentes aislados que no contaminan tu contexto |
| **Scoped Rules** | Reglas que se cargan solo al editar archivos relevantes |
| **50_state.md** | Archivo con el estado actual (fuente de verdad) |
| **Ralph Wiggum Loop** | Auto-corrección automática (máx 10 intentos) |
| **TDD** | Tests primero, código después |

> Ver [GLOSSARY.md](./GLOSSARY.md) para más términos.

---

## Siguiente Paso

1. **Tutorial completo**: [TUTORIAL.md](./TUTORIAL.md) - Ejemplo práctico paso a paso
2. **Documentación completa**: [README.md](./README.md) - Todo el detalle
3. **Índice de navegación**: [INDEX.md](./INDEX.md) - Mapa del repositorio

---

## Troubleshooting Rápido

### "No sé por dónde empezar"
```bash
/workflows:plan mi-feature
```
El planner te guiará.

### "El contexto se volvió muy largo"
```bash
# Checkpoint y nueva sesión
/workflows:sync
# Empezar nueva sesión de Claude
```

### "No entiendo un concepto"
Consulta [GLOSSARY.md](./GLOSSARY.md)

---

## Comandos de Ayuda y Setup

| Comando | Cuándo usarlo |
|---------|---------------|
| `/workflows:onboarding` | Primera vez usando el plugin |
| `/workflows:discover` | Para que el plugin conozca tu proyecto |
| `/workflows:help` | Referencia rápida de comandos |
| `/workflows:route` | No sabes qué comando usar |

---

**Tiempo total**: ~5 minutos para tu primera feature planificada.

> **Nueva aquí?** Ejecuta `/workflows:onboarding` para una experiencia guiada paso a paso.
