# Guia: Patrones de Diseno para Cumplir SOLID con Rigor

> Guia practica basada en el analisis real del codebase SNAAPI.
> Cada patron incluye el principio SOLID que refuerza, ejemplos reales del proyecto y anti-patrones a evitar.

---

## Tabla de Referencia Rapida

| Principio SOLID | Patron Principal | Patron Complementario | Donde ya lo usamos |
|-----------------|------------------|-----------------------|---------------------|
| **S** - Single Responsibility | Pipeline, Strategy | Value Object, Specification | `EnricherInterface`, `EditorialFieldExtractorInterface` |
| **O** - Open/Closed | Strategy, Decorator, Pipeline | Tagged Iterator (Symfony) | `SolidEnrichmentPipeline`, `CachedGatewayDecorator` |
| **L** - Liskov Substitution | Template Method, Strategy | Value Object con Factory Methods | `EnricherInterface` implementaciones, `EditorialType` |
| **I** - Interface Segregation | Role Interface, Gateway | Composition of Interfaces | `HasEditorialInterface`, `HasSectionInterface` |
| **D** - Dependency Inversion | Port/Adapter, Decorator | Factory, Result Pattern | `EditorialGatewayInterface`, `SolidGetEditorialHandler` |

---

## S - Single Responsibility Principle

> "Una clase debe tener una, y solo una, razon para cambiar."

### Patron 1: Pipeline Pattern

El patron Pipeline descompone un proceso complejo en pasos atomicos, cada uno con una unica responsabilidad.

**Ejemplo real** - `SolidEnrichmentPipeline` orquesta enrichers independientes:

```php
// src/Application/Pipeline/SolidEnrichmentPipeline.php
// SRP: Solo orquesta la ejecucion de enrichers. No sabe QUE enriquecen.
final class SolidEnrichmentPipeline
{
    public function __construct(
        #[TaggedIterator('app.enricher')]
        iterable $enrichers,
        private readonly LoggerInterface $logger,
    ) {}

    public function process(EditorialPipelineContext $context): Result
    {
        foreach ($this->sortedEnrichers as $enricher) {
            if (!$this->supportsContext($enricher, $context)) {
                continue;
            }
            $result = $this->executeEnricher($enricher, $context);
            // ...
        }
        return Result::success($context);
    }
}
```

Cada enricher tiene una UNICA responsabilidad:

```php
// src/Application/Pipeline/Enricher/SectionEnricher.php
// SRP: Solo busca la seccion y la pone en el contexto
final readonly class SectionEnricher implements EnricherInterface
{
    public function __construct(
        private SectionGatewayInterface $gateway, // 1 dependencia
    ) {}

    public function enrich(EditorialContext $context): void
    {
        $section = $this->gateway->findById($editorial->sectionId());
        if (null !== $section) {
            $context->setSection($section);
        }
    }
}
```

**Criterio de cumplimiento**: Si necesitas describir lo que hace una clase con "Y" (ej: "busca la editorial Y la seccion Y el multimedia"), esta violando SRP. Cada "Y" debe ser una clase separada.

### Patron 2: Specification Pattern

Encapsula reglas de negocio individuales en clases propias.

```yaml
# config/services/solid.yaml - Cada regla es una clase
App\Application\Specification\Editorial\EditorialIsPublishedSpecification: ~
App\Application\Specification\Editorial\EditorialIsNotDeletedSpecification: ~
App\Application\Specification\Editorial\EditorialIsIndexableSpecification: ~
App\Application\Specification\Editorial\EditorialIsCommentableSpecification: ~
```

**Cuando aplicar Pipeline + Specification:**
- Un proceso tiene 3+ pasos secuenciales -> Pipeline
- Una validacion tiene 3+ reglas de negocio -> Specification
- Una clase tiene 5+ dependencias inyectadas -> es senal de multiples responsabilidades

### Anti-patron: God Class

```php
// VIOLACION SRP: EditorialOrchestrator.php - 536 lineas, 19 dependencias
// Busca editorial, seccion, multimedia, tags, journalists, comments,
// membership, inserted news, recommended editorials, photos...
// TODAS son razones diferentes para cambiar
public function __construct(
    private readonly QueryEditorialClient $queryEditorialClient,
    private readonly QuerySectionClient $querySectionClient,
    private readonly QueryMultimediaClient $queryMultimediaClient,
    // ... 16 dependencias mas
) {}
```

**Regla empirica**: Si tu constructor tiene mas de 3-4 dependencias, probablemente viola SRP.

---

## O - Open/Closed Principle

> "Abierto para extension, cerrado para modificacion."

### Patron 3: Strategy Pattern con Tagged Iterators

El Strategy Pattern permite anadir nuevos comportamientos sin modificar codigo existente. Combinado con Symfony Tagged Iterators, es el patron mas poderoso para OCP.

```php
// src/Application/Strategy/Editorial/EditorialFieldExtractorInterface.php
// OCP: Define el contrato. Nuevos extractors se anaden sin tocar este archivo.
interface EditorialFieldExtractorInterface
{
    public function supports(NewsBase $editorial): bool;
    public function priority(): int;
    public function extractEndOn(NewsBase $editorial): ?\DateTimeInterface;
    public function extractIsBrand(NewsBase $editorial): bool;
    // ...
}
```

```yaml
# config/services/solid.yaml
# OCP: Solo anades una nueva clase PHP. Cero modificacion en codigo existente.
_instanceof:
    App\Application\Strategy\Editorial\EditorialFieldExtractorInterface:
        tags: ['app.editorial_field_extractor']

# Se registra automaticamente - solo crea el archivo .php
App\Application\Strategy\Editorial\DefaultEditorialFieldExtractor: ~
App\Application\Strategy\Editorial\BlogEditorialFieldExtractor: ~
# Para anadir OpinionEditorialFieldExtractor: crea la clase y anade una linea aqui
```

**Como extender sin modificar:**

1. Crea una nueva clase que implemente la interfaz
2. Symfony la auto-registra via `_instanceof` + tags
3. El `EditorialFieldExtractorChain` la recoge automaticamente
4. **Cero lineas de codigo existente modificadas**

### Patron 4: Decorator Pattern

Extiende el comportamiento de un objeto sin modificar su clase.

```php
// src/Infrastructure/Gateway/Decorator/CachedGatewayDecorator.php
// OCP: Anade caching SIN modificar el gateway original
final class CachedGatewayDecorator implements EditorialGatewayInterface
{
    public function __construct(
        private readonly EditorialGatewayInterface $inner, // Envuelve la interfaz
        private readonly CacheItemPoolInterface $cache,
    ) {}

    public function findById(string $id): ?NewsBase
    {
        $cacheItem = $this->cache->getItem($this->getCacheKey($id));
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }
        $editorial = $this->inner->findById($id); // Delega al original
        // ... guarda en cache
        return $editorial;
    }
}
```

Los decorators son **apilables** (stackable):

```
Request -> CircuitBreaker -> Cache -> HttpGateway -> Microservicio
```

Cada capa anade un concern sin que las demas lo sepan.

### Patron 5: Pipeline con Enrichers (OCP via Composicion)

```php
// src/Application/Pipeline/EnricherInterface.php
// OCP: Nuevo enricher = nueva clase. El pipeline no cambia.
interface EnricherInterface
{
    public function priority(): int;
    public function supports(EditorialContext $context): bool;
    public function enrich(EditorialContext $context): void;
}
```

**Cuando aplicar:**
- Necesitas anadir un nuevo tipo de editorial -> Nuevo `FieldExtractor` (Strategy)
- Necesitas anadir caching/logging/circuit-breaker -> Nuevo `Decorator`
- Necesitas anadir una nueva fuente de datos -> Nuevo `Enricher` (Pipeline)

---

## L - Liskov Substitution Principle

> "Los subtipos deben ser sustituibles por sus tipos base."

### Patron 6: Value Objects con Named Constructors

Los Value Objects inmutables garantizan LSP porque no pueden ser corrompidos despues de la creacion.

```php
// src/Domain/ValueObject/EditorialType.php
// LSP: Cualquier EditorialType se puede usar donde se espera otro.
// Los Named Constructors garantizan estados validos.
final readonly class EditorialType implements \JsonSerializable
{
    private function __construct(
        private int $id,
        private string $name,
    ) {}

    // Named Constructors - siempre producen objetos validos
    public static function fromId(int $id): self { /* ... */ }
    public static function news(): self { return self::fromId(1); }
    public static function blog(): self { return self::fromId(2); }

    // Comportamiento consistente para TODOS los tipos
    public function equals(self $other): bool { return $this->id === $other->id; }
    public function jsonSerialize(): array { return ['id' => (string)$this->id, 'name' => $this->name]; }
}
```

**Reglas para LSP con Value Objects:**
- Constructor privado + Named Constructors = estados invalidos imposibles
- `final readonly` = nadie puede heredar y romper invariantes
- Metodos de comparacion (`equals`) garantizan comportamiento consistente

### Patron 7: Contratos con `supports()` Guard

En vez de forzar subtipos que no encajan, usa `supports()` para dejar que cada implementacion decida si aplica.

```php
// BUENA practica LSP: SectionEnricher verifica ANTES de ejecutar
final readonly class SectionEnricher implements EnricherInterface
{
    public function supports(EditorialContext $context): bool
    {
        return null !== $context->editorial(); // Autodeclaracion de capacidad
    }

    public function enrich(EditorialContext $context): void
    {
        // Aqui ya sabemos que editorial existe
    }
}
```

### Anti-patron: Casteo de Tipos

```php
// VIOLACION LSP: MultimediaPhotoOrchestrator.php
// Dice que acepta Multimedia, pero REQUIERE MultimediaPhoto
public function execute(Multimedia $multimedia): array
{
    // PELIGRO: asume que $multimedia es MultimediaPhoto
    $photo = $this->queryMultimediaClient->findPhotoById(
        $multimedia->resourceId()->id() // Falla si no es MultimediaPhoto
    );
}
```

**Correccion via interfaces especificas:**

```php
// Opcion A: Interfaces segregadas por tipo
interface MultimediaPhotoOrchestratorInterface {
    public function execute(MultimediaPhoto $multimedia): array;
}

// Opcion B: Guard clause explicito
public function execute(Multimedia $multimedia): array {
    if (!$multimedia instanceof MultimediaPhoto) {
        throw new \InvalidArgumentException('Expected MultimediaPhoto');
    }
}
```

---

## I - Interface Segregation Principle

> "Los clientes no deben ser forzados a depender de interfaces que no usan."

### Patron 8: Role Interfaces (Interfaces de Rol)

Divide una interfaz grande en interfaces pequenas basadas en roles.

```php
// src/Application/Contract/Context/
// ISP: Cada interfaz tiene UN rol minimo

interface HasEditorialInterface {
    public function editorial(): ?NewsBase;
    public function hasEditorial(): bool;
}

interface HasSectionInterface {
    public function section(): ?Section;
    public function hasSection(): bool;
}

interface HasMultimediaInterface {
    public function multimedia(): array;
    public function multimediaOpening(): ?Multimedia;
    public function hasMultimedia(): bool;
    public function hasMultimediaOpening(): bool;
}

// Composicion: Los clientes que necesitan TODO implementan la union
interface ContextInterface extends
    HasEditorialInterface,
    HasSectionInterface,
    HasMultimediaInterface,
    HasTagsInterface,
    HasJournalistsInterface,
    HasCommentsInterface,
    HasMembershipInterface
{}
```

**Beneficio concreto**: Un enricher que solo necesita la editorial depende solo de `HasEditorialInterface`, no de toda la interfaz `ContextInterface`. Si `HasMultimediaInterface` cambia, el enricher NO se ve afectado.

### Patron 9: Gateway Interfaces Minimas

```php
// src/Domain/Port/Gateway/
// ISP: Cada gateway expone SOLO lo que su dominio necesita

interface EditorialGatewayInterface {  // 2 metodos
    public function findById(string $id): ?NewsBase;
    public function findByIdAsync(string $id): PromiseInterface;
}

interface SectionGatewayInterface {     // 2 metodos
    public function findById(string $sectionId): ?Section;
    public function findByIdAsync(string $sectionId): PromiseInterface;
}

interface TagGatewayInterface {         // 3 metodos
    public function findByEditorialId(string $editorialId): array;
    public function findByEditorialIdAsync(string $editorialId): PromiseInterface;
    public function findById(string $tagId): ?Tag;
}
```

**Regla**: Si un metodo de tu interfaz tiene implementaciones que retornan `null`, lanzan `NotImplementedException`, o simplemente no hacen nada, la interfaz es demasiado grande.

---

## D - Dependency Inversion Principle

> "Depende de abstracciones, no de concreciones."

### Patron 10: Port/Adapter (Hexagonal Architecture)

Es el patron fundamental para DIP. Define "puertos" (interfaces) en el dominio y "adaptadores" (implementaciones) en infraestructura.

```
Domain/Port/Gateway/                    <- ABSTRACCIONES (Puertos)
    EditorialGatewayInterface.php
    SectionGatewayInterface.php

Infrastructure/Gateway/Http/            <- IMPLEMENTACIONES (Adaptadores)
    HttpEditorialGateway.php
    HttpSectionGateway.php

Infrastructure/Gateway/Decorator/       <- DECORADORES (tambien Adaptadores)
    CachedGatewayDecorator.php
    CircuitBreakerDecorator.php
```

**Flujo de dependencias:**

```
Application Layer -> Domain Port Interface <- Infrastructure Adapter
     (usa)            (define)                  (implementa)
```

Las flechas de dependencia apuntan hacia las abstracciones, NUNCA hacia las implementaciones.

### Patron 11: Handler con Inyeccion de Abstracciones

```php
// src/Application/Handler/SolidGetEditorialHandler.php
// DIP: Depende de pipeline y factory, NO de clientes HTTP concretos
final readonly class SolidGetEditorialHandler
{
    public function __construct(
        private SolidEnrichmentPipeline $pipeline,       // Abstraccion
        private SolidEditorialResponseFactory $factory,   // Abstraccion
    ) {}

    public function handle(string $editorialId): Result
    {
        $context = EditorialPipelineContext::forEditorial($editorialId);
        return $this->pipeline
            ->process($context)
            ->flatMap(fn (ContextInterface $ctx) => $this->factory->createResult($ctx));
    }
}
```

```yaml
# config/services/solid.yaml - El contenedor resuelve las implementaciones
App\Application\Factory\Response\SolidEditorialResponseFactory:
    arguments:
        $dateFormatter: '@App\Application\Service\Formatter\DateFormatterInterface'
        $typeMapper: '@App\Application\Service\Formatter\TypeMapperInterface'
        $fieldExtractor: '@App\Application\Strategy\Editorial\EditorialFieldExtractorInterface'
```

### Patron 12: Result Pattern (Monada de Error)

Invierte la dependencia en el manejo de errores: el Handler NO decide como manejar errores. Retorna un `Result` y el llamador decide.

```php
// El handler retorna Result, NO lanza excepciones
public function handle(string $editorialId): Result
{
    return $this->pipeline->process($context)
        ->flatMap(fn ($ctx) => $this->factory->createResult($ctx));
}

// El LLAMADOR decide como manejar el error
$result->fold(
    fn (EditorialResponse $response) => $response,         // Exito
    fn (Error $error) => throw new \RuntimeException(...)   // Error
);
```

---

## Matriz de Decision: Que Patron Usar Segun el Problema

| Problema | Patron Recomendado | Principio SOLID |
|----------|--------------------|-----------------|
| Clase con muchas responsabilidades | **Pipeline** (descomponer en pasos) | SRP |
| Multiples reglas de validacion | **Specification** (una clase por regla) | SRP |
| Necesito anadir comportamiento sin modificar | **Decorator** (envolver con nueva capa) | OCP |
| Necesito anadir nuevas variantes | **Strategy** + Tagged Iterator | OCP |
| Subtipos rompen el contrato del padre | **Value Object** + Named Constructors | LSP |
| Interfaz con metodos que no todos usan | **Role Interfaces** (segregar por rol) | ISP |
| Clase depende de implementaciones concretas | **Port/Adapter** (extraer interfaz) | DIP |
| Necesito manejar errores sin acoplar | **Result Pattern** | DIP |
| Cross-cutting concerns (cache, logs, retry) | **Decorator** apilable | OCP + SRP |
| Proceso con pasos opcionales/condicionales | **Pipeline** + `supports()` | OCP + SRP |

---

## Checklist de Validacion SOLID

Usa este checklist antes de hacer merge de cualquier PR:

### SRP
- [ ] Cada clase tiene una unica razon para cambiar
- [ ] El constructor tiene 4 o menos dependencias
- [ ] El nombre de la clase describe UNA responsabilidad (sin "And", "Or", "Manager")
- [ ] Los metodos publicos son cohesivos (todos relacionados al mismo concepto)

### OCP
- [ ] Puedo anadir un nuevo tipo/variante creando una nueva clase, sin modificar las existentes
- [ ] Los puntos de extension usan interfaces + tagged iterators, no condicionales (`if/switch`)
- [ ] Los cross-cutting concerns usan decorators, no modificaciones al codigo original

### LSP
- [ ] Todas las implementaciones de una interfaz cumplen su contrato sin excepciones ocultas
- [ ] No hay casteos de tipo (`instanceof`) dentro de metodos que aceptan el tipo base
- [ ] Los Value Objects son inmutables y se crean via Named Constructors
- [ ] Los metodos `supports()` se usan para auto-declarar capacidades

### ISP
- [ ] Ninguna interfaz tiene metodos que alguna implementacion no usa
- [ ] Las interfaces estan segregadas por rol/responsabilidad
- [ ] Cada gateway tiene 2-4 metodos como maximo

### DIP
- [ ] El Application Layer depende de interfaces del Domain Layer
- [ ] El Infrastructure Layer implementa interfaces del Domain Layer
- [ ] Los constructores inyectan interfaces, no clases concretas
- [ ] La configuracion de servicios usa alias de interfaces

---

## Plan de Accion para el Codebase Actual

### Prioridad 1: Migrar EditorialOrchestrator al Pipeline

El `EditorialOrchestrator` (536 lineas, 19 dependencias) es la violacion mas critica. La arquitectura nueva con Pipeline + Enrichers ya demuestra como hacerlo bien. El plan:

1. Cada bloque del `execute()` se convierte en un `Enricher`
2. Cada cliente concreto se abstrae tras una `GatewayInterface`
3. El Pipeline orquesta todo via tagged iterators

### Prioridad 2: Resolver violacion LSP en MultimediaOrchestrator

Crear interfaces especificas por tipo de multimedia en vez de castear el tipo base.

### Prioridad 3: Activar Decoradores en Produccion

Los `CachedGatewayDecorator` y `CircuitBreakerDecorator` estan implementados pero comentados en configuracion. Activarlos anade resiliencia sin cambiar codigo.

---

## Referencia de Archivos Clave

| Archivo | Principio | Rol |
|---------|-----------|-----|
| `src/Application/Pipeline/EnricherInterface.php` | SRP, OCP | Contrato de paso del pipeline |
| `src/Application/Pipeline/SolidEnrichmentPipeline.php` | SRP, OCP, DIP | Orquestador del pipeline |
| `src/Application/Pipeline/Enricher/*.php` | SRP | Un enricher por concern |
| `src/Domain/Port/Gateway/*.php` | ISP, DIP | Puertos (abstracciones) |
| `src/Infrastructure/Gateway/Decorator/*.php` | OCP, DIP | Decoradores apilables |
| `src/Application/Contract/Context/Has*Interface.php` | ISP | Role interfaces segregadas |
| `src/Application/Strategy/Editorial/*.php` | OCP, LSP | Estrategias por tipo |
| `src/Domain/ValueObject/*.php` | LSP | Objetos inmutables |
| `src/Application/Handler/SolidGetEditorialHandler.php` | SRP, DIP | Handler limpio |
| `config/services/solid.yaml` | ALL | Configuracion DI SOLID |
