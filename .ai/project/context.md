# Project Context

> Updated: 2026-02-09
> Integrated from GitLab: ec-awesomemakers1/microservices/services/snaapi

## Project Overview

| Field | Value |
|-------|-------|
| **Name** | SNAAPI |
| **Type** | API Gateway |
| **Description** | API Gateway that aggregates content from multiple microservices for mobile apps |
| **Framework** | Symfony 6.4 / PHP 8.1+ |
| **Architecture** | Hexagonal + CQRS (Query side) |
| **Source** | `./snaapi/` |

## Tech Stack

### Backend
- **Language**: PHP 8.1+ (strict types, readonly, enums, match expressions)
- **Framework**: Symfony 6.4
- **HTTP Client**: HTTPlug + Guzzle7 (async, promise-based)
- **Messaging**: RabbitMQ via Symfony Messenger (AMQP)
- **Search**: Elasticsearch 8.0+
- **Image Processing**: Thumbor (via 99designs/phumbor)
- **API Docs**: NelmioApiDocBundle (OpenAPI/Swagger)
- **Testing**: PHPUnit 10, PHPStan level 9, Infection (mutation testing), PHP-CS-Fixer

### External Microservice Clients
| Client | Domain |
|--------|--------|
| `ec/editorial-client` | Editorial content |
| `ec/multimedia-client` | Photos, videos, widgets |
| `ec/section-client` | Section hierarchy |
| `ec/journalist-client` | Author information |
| `ec/tag-client` | Tags/categories |
| `ec/membership-client` | Subscription/membership |
| `ec/widget-client` | Widget rendering |

### Infrastructure
- **Docker**: Multi-stage build (local, ci, k8s, prod)
- **CI/CD**: GitLab CI with 15 stages
- **Monitoring**: NewRelic, Blackfire profiling
- **Message Queue**: RabbitMQ (AMQP)

## Directory Structure

```
snaapi/
├── src/
│   ├── Application/              # Use cases & data transformers
│   │   └── DataTransformer/      # 30+ transformers (body elements, media)
│   ├── Controller/V1/            # HTTP entry points
│   │   └── EditorialController   # GET /editorials/{id}
│   ├── Orchestrator/             # Chain of Responsibility
│   │   ├── OrchestratorChainHandler
│   │   ├── Chain/Editorial       # Editorial orchestration (async)
│   │   └── Chain/Multimedia/     # Photo, video, widget handlers
│   ├── Infrastructure/           # External services
│   │   ├── Service/              # Thumbor, PictureShots
│   │   ├── Enum/                 # EditorialTypes, Sites, ClossingMode
│   │   └── Trait/                # Shared URL & multimedia utilities
│   ├── DependencyInjection/      # 7 compiler passes
│   │   └── Compiler/             # Dynamic service registration via tags
│   ├── Ec/Snaapi/Infrastructure/ # Bounded context clients
│   ├── EventHandler/             # Async message handlers
│   ├── EventSubscriber/          # Event subscribers
│   ├── Exception/                # Custom exceptions
│   └── Kernel.php                # Symfony kernel
├── tests/                        # 63 test files (mirrors src/)
├── config/                       # 30 YAML config files
│   ├── packages/                 # Service & domain configs
│   └── routes/                   # API routing (v1.yaml)
├── docker-compose.yml
├── Dockerfile                    # 10 build stages
├── Makefile                      # Dev commands
├── composer.json
└── .gitlab-ci.yml                # 15-stage pipeline
```

## Request Flow

```
Controller → OrchestratorChainHandler → EditorialOrchestrator → External Clients → DataTransformers → Response
```

## Key Architectural Patterns

### Chain of Responsibility (Orchestrator)
- `OrchestratorChainHandler` routes requests by content type
- `MultimediaOrchestratorHandler` routes by media type
- Registered dynamically via compiler passes + service tags

### Strategy Pattern (DataTransformers)
- `BodyElementDataTransformerHandler` dispatches to type-specific transformers
- Tagged with `app.data_transformer`, auto-registered via compiler pass

### Compiler Passes (7 total)
1. `EditorialOrchestratorCompiler` - orchestrators (`app.orchestrators`)
2. `BodyDataTransformerCompiler` - body transformers (`app.data_transformer`)
3. `MediaDataTransformerCompiler` - media transformers (`app.media_data_transformer`)
4. `MultimediaOrchestratorCompiler` - multimedia handlers (`app.multimedia.orchestrators`)
5. `WidgetDataTransformerCompiler` - widget transformers
6. `WidgetLegacyCreatorHandlerCompiler` - legacy widget support
7. `MultimediaFactoryCompiler` - multimedia factory

## Quality Gates

| Check | Command | Threshold |
|-------|---------|-----------|
| Unit Tests | `make test_unit` | All pass |
| Static Analysis | `make test_stan` | PHPStan Level 9 |
| Code Style | `make test_cs` | PSR-12 + Symfony |
| YAML Lint | `make test_yaml` | Valid YAML |
| Container Lint | `make test_container` | Valid DI |
| Mutation Testing | `make test_infection` | MSI 86%, Covered MSI 87% |
| All Tests | `make tests` | All above pass |

## Available Workflows

| Workflow | Description | Use Case |
|----------|-------------|----------|
| `task-breakdown` | Detailed planning (10 documents) | Complex features requiring specs |
| `default` | Plan → Backend → Frontend → QA | Medium complexity features |
| `implementation-only` | Implementation without planning | After task-breakdown completes |

## Commands Quick Reference

```bash
# Run all tests
make tests

# Individual test targets
make test_unit          # PHPUnit
make test_cs            # PHP-CS-Fixer
make test_stan          # PHPStan level 9
make test_infection     # Mutation testing

# Docker
make up                 # Start containers
make down               # Stop containers
make cli                # Shell into PHP container

# Workflow commands (via plugin)
/workflows:plan <feature>       # Plan a feature
/workflows:work <feature>       # Implement
/workflows:review <feature>     # Multi-agent review
/workflows:compound <feature>   # Capture learnings
/workflows:status               # View progress
```

---

**Source Repository**: GitLab `ec-awesomemakers1/microservices/services/snaapi`
**Workflow Plugin**: `multi-agent-workflow` v2.7.0
