# SOLID Architecture Documentation

This document describes the SOLID principles implementation in the SNAAPI codebase.

## Overview

The refactoring follows all five SOLID principles using industry-standard design patterns:

| Principle | Implementation | Design Patterns Used |
|-----------|---------------|---------------------|
| **S**ingle Responsibility | Formatters, Enrichers, Factories | Strategy, Factory Method |
| **O**pen/Closed | Field Extractors, Specifications | Strategy, Chain of Responsibility |
| **L**iskov Substitution | Editorial Field Extractors | Strategy, Polymorphism |
| **I**nterface Segregation | Context Interfaces | Interface Segregation |
| **D**ependency Inversion | All services via DI | Dependency Injection |

## Directory Structure

```
src/
├── Application/
│   ├── Contract/
│   │   └── Context/                    # ISP: Segregated Interfaces
│   │       ├── ContextInterface.php
│   │       ├── HasEditorialInterface.php
│   │       ├── HasSectionInterface.php
│   │       ├── HasMultimediaInterface.php
│   │       ├── HasTagsInterface.php
│   │       ├── HasJournalistsInterface.php
│   │       ├── HasCommentsInterface.php
│   │       └── HasMembershipInterface.php
│   │
│   ├── Service/
│   │   └── Formatter/                  # SRP: Specialized Formatters
│   │       ├── DateFormatterInterface.php
│   │       ├── DateFormatter.php
│   │       ├── TypeMapperInterface.php
│   │       ├── TypeMapper.php
│   │       ├── UrlFormatterInterface.php
│   │       └── UrlFormatter.php
│   │
│   ├── Strategy/
│   │   └── Editorial/                  # OCP + LSP: Strategy Pattern
│   │       ├── EditorialFieldExtractorInterface.php
│   │       ├── DefaultEditorialFieldExtractor.php
│   │       ├── BlogEditorialFieldExtractor.php
│   │       └── EditorialFieldExtractorChain.php
│   │
│   ├── Specification/                  # SRP: Business Rules
│   │   ├── SpecificationInterface.php
│   │   ├── AbstractSpecification.php
│   │   ├── AndSpecification.php
│   │   ├── OrSpecification.php
│   │   ├── NotSpecification.php
│   │   └── Editorial/
│   │       ├── EditorialIsPublishedSpecification.php
│   │       ├── EditorialIsNotDeletedSpecification.php
│   │       ├── EditorialIsIndexableSpecification.php
│   │       ├── EditorialIsCommentableSpecification.php
│   │       └── EditorialIsAvailableSpecification.php
│   │
│   ├── Result/                         # Error Handling Pattern
│   │   ├── Result.php
│   │   ├── Error.php
│   │   └── ResultCollection.php
│   │
│   ├── Pipeline/
│   │   ├── Context/
│   │   │   └── EditorialPipelineContext.php  # ISP-compliant context
│   │   └── SolidEnrichmentPipeline.php
│   │
│   ├── Factory/
│   │   └── Response/
│   │       └── SolidEditorialResponseFactory.php
│   │
│   └── Handler/
│       └── SolidGetEditorialHandler.php
│
└── Domain/
    └── ValueObject/                    # Immutable Value Objects
        ├── EditorialId.php
        ├── EditorialType.php
        ├── PublicationDate.php
        ├── Url.php
        └── WordCount.php
```

## SOLID Principles in Detail

### 1. Single Responsibility Principle (SRP)

Each class has exactly one reason to change:

**Formatters** - Handle specific formatting concerns:
```php
// DateFormatter only formats dates
class DateFormatter implements DateFormatterInterface
{
    public function format(\DateTimeInterface $date): string;
    public function formatNullable(?\DateTimeInterface $date): ?string;
}

// TypeMapper only maps editorial types
class TypeMapper implements TypeMapperInterface
{
    public function map(int $typeId): EditorialType;
    public function getName(int $typeId): string;
}
```

**Specifications** - Encapsulate single business rules:
```php
// Each specification handles one rule
class EditorialIsPublishedSpecification extends AbstractSpecification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $candidate instanceof NewsBase && $candidate->isVisible();
    }
}
```

### 2. Open/Closed Principle (OCP)

Classes are open for extension but closed for modification:

**Strategy Pattern for Field Extractors**:
```php
// Add new editorial types by creating new extractors
// No need to modify existing code

class NewsEditorialFieldExtractor implements EditorialFieldExtractorInterface
{
    public function supports(NewsBase $editorial): bool
    {
        return $editorial instanceof EditorialNews;
    }
    // ... implement extraction methods
}
```

**Configuration-based extension**:
```yaml
# Just tag the new service - no code changes needed
services:
    App\Application\Strategy\Editorial\NewsEditorialFieldExtractor:
        tags: ['app.editorial_field_extractor']
```

### 3. Liskov Substitution Principle (LSP)

Subtypes are substitutable for their base types:

**Before (violation)**:
```php
// BAD: Using method_exists breaks substitutability
private function getEndOn(NewsBase $editorial): ?string
{
    if (!method_exists($editorial, 'endOn')) {
        return null;
    }
    return $editorial->endOn();
}
```

**After (compliant)**:
```php
// GOOD: Proper polymorphism via Strategy pattern
class EditorialFieldExtractorChain implements EditorialFieldExtractorInterface
{
    public function extractEndOn(NewsBase $editorial): ?\DateTimeInterface
    {
        return $this->findExtractor($editorial)->extractEndOn($editorial);
    }
}
```

### 4. Interface Segregation Principle (ISP)

Clients depend only on interfaces they use:

**Segregated Interfaces**:
```php
// Clients that only need editorial data
interface HasEditorialInterface
{
    public function editorial(): ?NewsBase;
    public function hasEditorial(): bool;
}

// Clients that only need tags
interface HasTagsInterface
{
    public function tags(): array;
    public function hasTags(): bool;
}

// Full interface for pipeline (combines all)
interface ContextInterface extends
    HasEditorialInterface,
    HasSectionInterface,
    HasMultimediaInterface,
    HasTagsInterface,
    HasJournalistsInterface,
    HasCommentsInterface,
    HasMembershipInterface
{
    // ...
}
```

**Usage**:
```php
// This service only depends on what it needs
class TagProcessor
{
    public function process(HasTagsInterface $context): void
    {
        foreach ($context->tags() as $tag) {
            // ...
        }
    }
}
```

### 5. Dependency Inversion Principle (DIP)

High-level modules depend on abstractions:

**Before**:
```php
// BAD: Depends on concrete implementations
class EditorialResponseFactory
{
    public function __construct(
        private DateFormatter $dateFormatter,  // Concrete!
    ) {}
}
```

**After**:
```php
// GOOD: Depends on abstractions
class SolidEditorialResponseFactory
{
    public function __construct(
        private DateFormatterInterface $dateFormatter,     // Interface!
        private TypeMapperInterface $typeMapper,           // Interface!
        private EditorialFieldExtractorInterface $extractor, // Interface!
    ) {}
}
```

**DI Configuration**:
```yaml
services:
    App\Application\Service\Formatter\DateFormatterInterface:
        alias: App\Application\Service\Formatter\DateFormatter

    App\Application\Factory\Response\SolidEditorialResponseFactory:
        arguments:
            $dateFormatter: '@App\Application\Service\Formatter\DateFormatterInterface'
```

## Design Patterns Used

### 1. Strategy Pattern
Used for editorial field extraction, allowing different algorithms for different editorial types.

### 2. Chain of Responsibility
`EditorialFieldExtractorChain` iterates through extractors to find the appropriate one.

### 3. Specification Pattern
Encapsulates business rules as composable, reusable specifications.

### 4. Factory Method
`SolidEditorialResponseFactory` creates response objects with all dependencies injected.

### 5. Result/Either Pattern
Explicit error handling without exceptions:
```php
$result = $handler->handle($editorialId);

// Functional style
$response = $result->fold(
    fn ($editorial) => new JsonResponse($editorial),
    fn ($error) => new JsonResponse(['error' => $error->message()], 404)
);

// Or imperative style
if ($result->isSuccess()) {
    return new JsonResponse($result->getValue());
} else {
    return new JsonResponse(['error' => $result->getError()->message()], 404);
}
```

### 6. Value Object Pattern
Immutable, self-validating domain objects:
```php
$editorialId = EditorialId::fromString('123');  // Validates on creation
$type = EditorialType::fromId(1);               // Encapsulates type logic
$date = PublicationDate::fromDateTime($dt);     // Immutable date
```

## Migration Guide

### Using the New SOLID Components

1. **Replace legacy handler**:
```php
// Before
$handler = new GetEditorialHandler($pipeline, $factory);
$response = $handler($editorialId);

// After
$handler = new SolidGetEditorialHandler($solidPipeline, $solidFactory);
$result = $handler->handle($editorialId);

// With explicit error handling
if ($result->isFailure()) {
    return handleError($result->getError());
}
return $result->getValue();
```

2. **Use specifications for validation**:
```php
$isAvailable = new EditorialIsAvailableSpecification();

if (!$isAvailable->isSatisfiedBy($editorial)) {
    throw new EditorialNotAvailableException();
}
```

3. **Depend on segregated interfaces**:
```php
// If you only need editorial data, depend on HasEditorialInterface
public function process(HasEditorialInterface $context): void
{
    $editorial = $context->editorial();
    // ...
}
```

## Benefits

1. **Testability**: Each component can be tested in isolation
2. **Maintainability**: Changes are localized to specific classes
3. **Extensibility**: New types/behaviors added without modifying existing code
4. **Readability**: Code is self-documenting through clear interfaces
5. **Reliability**: Type-safe, no runtime reflection needed
