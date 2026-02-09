# Solutions: previum-editorial-preview

> Phase 3 — HOW to implement each spec (technical design with SOLID compliance)

---

## SOLID Baseline

**Current code analysis** (affected areas):
- `EditorialOrchestrator`: Good SRP (orchestration only), good DIP (interfaces injected). Score ~20/25.
- `OrchestratorChainHandler`: Excellent OCP (new orchestrators added via compiler pass without modification). Score ~23/25.
- `EditorialController`: Excellent SRP (thin, delegates to orchestrator). Score ~24/25.
- **Security layer**: N/A — does not exist yet (greenfield).

**Target SOLID Score**: >= 22/25

---

## Solution for SPEC-F01 + SPEC-F02 + SPEC-F03: JWT Authentication

**Approach**: Symfony Security custom authenticator with delegated HTTP validation.

### SOLID Compliance

| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | Authenticator only extracts/orchestrates. Token validation in dedicated service. JWT decoding in separate service. | Extract Class |
| **O** - OCP | New user types can be added by creating new authenticators without modifying existing ones. Token validation strategy can be swapped. | Strategy (implicit) |
| **L** - LSP | `PreviumUser` implements `UserInterface` fully — substitutable in any Symfony Security context | Interface Compliance |
| **I** - ISP | `TokenValidatorInterface` has single method `validate()`. `JwtDecoderInterface` has single method `decode()`. No fat interfaces. | Interface Segregation |
| **D** - DIP | Authenticator depends on `TokenValidatorInterface` and `JwtDecoderInterface` abstractions, not concrete HTTP client. | Dependency Injection |

### Class Design

```
src/Previum/
├── Authenticator/
│   └── PreviumJwtAuthenticator.php     # Symfony AuthenticatorInterface
├── Model/
│   └── PreviumUser.php                 # Symfony UserInterface (value object, no persistence)
├── Token/
│   ├── TokenValidatorInterface.php     # Abstraction: validate JWT against external service (DIP)
│   ├── HttpTokenValidator.php          # Implementation: HTTP call to auth microservice
│   ├── JwtDecoderInterface.php         # Abstraction: decode JWT payload (DIP)
│   └── FirebaseJwtDecoder.php          # Implementation: firebase/php-jwt decoder
└── Exception/
    ├── InvalidTokenException.php       # 401 — invalid/expired/missing token
    └── InsufficientPermissionsException.php  # 403 — valid token, wrong user_type
```

### Why This Structure

1. **`PreviumJwtAuthenticator`** (SRP): Only orchestrates the auth flow — extracts header, calls validator, checks result. Does NOT do HTTP calls or JWT parsing directly.

2. **`TokenValidatorInterface` / `HttpTokenValidator`** (DIP + SRP): Encapsulates the HTTP call to the auth microservice. Can be replaced with a mock in tests or swapped for a different validation strategy (e.g., local validation) without touching the authenticator.

3. **`JwtDecoderInterface` / `FirebaseJwtDecoder`** (DIP + SRP): Isolates JWT decoding. The authenticator and validator don't depend on `firebase/php-jwt` directly — they depend on the abstraction.

4. **`PreviumUser`** (SRP): Pure value object holding user identity. No business logic. Implements Symfony's `UserInterface`.

**Expected SOLID Score**: 23/25

---

## Solution for SPEC-F04: Serve Unpublished Editorial Content

**Approach**: New orchestrator in the Chain of Responsibility, following existing pattern.

### SOLID Compliance

| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | New `PreviumEditorialOrchestrator` is responsible only for previum editorial aggregation | Chain of Responsibility |
| **O** - OCP | Added to the chain via `app.orchestrators` tag + compiler pass — `OrchestratorChainHandler` is NOT modified | Compiler Pass + Service Tag |
| **L** - LSP | Implements `EditorialOrchestratorInterface` — fully substitutable in the chain | Interface Compliance |
| **D** - DIP | Depends on same injected client interfaces as `EditorialOrchestrator` | Constructor Injection |

### Class Design

```
src/Orchestrator/Chain/
└── PreviumEditorialOrchestrator.php    # Implements EditorialOrchestratorInterface
                                         # canOrchestrate() returns 'previum'
                                         # execute() = EditorialOrchestrator logic WITHOUT isVisible() check
```

### Key Design Decision: Composition vs. Duplication

**Option A**: Extend `EditorialOrchestrator` and override the visibility check.
- Risk: LSP violation if parent changes. Tight coupling.

**Option B**: Compose with `EditorialOrchestrator` and wrap the call.
- Risk: `EditorialOrchestrator::execute()` throws `EditorialNotPublishedYetException` before we can intercept.

**Option C** (SELECTED): Create independent orchestrator that reuses the same dependencies but skips the `isVisible()` check.
- Pros: Full control, no coupling to parent behavior, follows existing pattern.
- Cons: Some code similarity with `EditorialOrchestrator`.
- Mitigation: Extract shared logic into a trait or shared service in a future refactor if needed.

**Expected SOLID Score**: 22/25

---

## Solution for SPEC-F05: Existing Endpoint Unaffected

**Approach**: Symfony Security firewall isolation.

```yaml
# config/packages/previum_firewall.yaml
security:
    firewalls:
        previum:
            pattern: ^/previum
            stateless: true
            custom_authenticators:
                - App\Previum\Authenticator\PreviumJwtAuthenticator
        main:
            pattern: ^/
            security: false
```

**SOLID Compliance**: OCP — new firewall EXTENDS security config without modifying existing routes.

**Expected SOLID Score**: 25/25 (config only)

---

## Solution for SPEC-F06: Error Responses

**Approach**: Leverage existing `ExceptionSubscriber` pattern + custom exceptions with proper HTTP codes.

### Exception Hierarchy

| Exception | HTTP Code | When Thrown |
|-----------|-----------|-------------|
| `InvalidTokenException` | 401 | Missing, malformed, or invalid JWT; auth service failure |
| `InsufficientPermissionsException` | 403 | Valid JWT but `user_type !== 'previum'` |
| `EditorialNotPublishedYetException` (existing) | 404 | Editorial not found (reused for non-existent IDs) |

The `PreviumJwtAuthenticator::onAuthenticationFailure()` returns `401`/`403` JSON responses directly, following Symfony Security's pattern. This bypasses the `ExceptionSubscriber` for auth failures (which is correct — auth happens before the controller).

**Expected SOLID Score**: 24/25

---

## SOLID Score Summary

| Component | S | O | L | I | D | Total |
|-----------|---|---|---|---|---|-------|
| PreviumJwtAuthenticator | 5 | 4 | 5 | 5 | 5 | **24/25** |
| TokenValidatorInterface + HttpTokenValidator | 5 | 5 | 5 | 5 | 5 | **25/25** |
| JwtDecoderInterface + FirebaseJwtDecoder | 5 | 5 | 5 | 5 | 5 | **25/25** |
| PreviumUser | 5 | 4 | 5 | 4 | 4 | **22/25** |
| PreviumEditorialOrchestrator | 5 | 5 | 5 | 4 | 5 | **24/25** |
| PreviumEditorialController | 5 | 5 | 5 | 5 | 5 | **25/25** |
| **Overall Weighted Average** | | | | | | **24/25** |

**Result**: >= 22/25 — **APPROVED for implementation**

---

## Pattern Selection Summary

| Need | Pattern | SOLID Principles |
|------|---------|-----------------|
| Auth flow orchestration | **Symfony Custom Authenticator** | SRP, DIP |
| Token validation abstraction | **Strategy (Interface + Impl)** | DIP, OCP, ISP |
| JWT decoding abstraction | **Strategy (Interface + Impl)** | DIP, OCP, ISP |
| New orchestrator in chain | **Chain of Responsibility** (existing) | OCP, SRP, LSP |
| Route isolation | **Firewall pattern** (Symfony Security) | OCP |
| Error handling | **Custom Exceptions** (existing pattern) | SRP |

---

**Generated by**: workflows:plan (Phase 3 - Solutions)
**Date**: 2026-02-09
