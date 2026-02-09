# Task Breakdown: previum-editorial-preview

> Each task follows TDD (Red-Green-Refactor) methodology.
> Tasks are ordered by dependency — each can start only when its dependencies are COMPLETED.

---

## Task Dependency Graph

```
BE-001 (Dependencies + Config)
  │
  ├── BE-002 (Exceptions)
  │     │
  │     ├── BE-003 (PreviumUser)
  │     │     │
  │     │     ├── BE-004 (JwtDecoder)
  │     │     │     │
  │     │     │     └── BE-005 (TokenValidator)
  │     │     │           │
  │     │     │           └── BE-006 (Authenticator)
  │     │     │                 │
  │     │     │                 └── BE-007 (Firewall Config)
  │     │
  │     └── BE-008 (PreviumEditorialOrchestrator)
  │           │
  │           └── BE-009 (PreviumEditorialController + Route)
  │
  └── BE-010 (Integration Test + Final Verification)
```

---

## BE-001: Add Dependencies and Base Configuration

**Role**: Backend Engineer
**Methodology**: Configuration (no TDD needed)
**Dependencies**: None
**Spec**: Foundation for all subsequent tasks

**What to Do**:
1. Add `symfony/security-bundle` and `firebase/php-jwt` to `composer.json`
2. Add environment variables to `.env.dist`:
   ```bash
   ###> previum-jwt ###
   PREVIUM_AUTH_HOST=http://auth-microservice:8080
   PREVIUM_AUTH_ENDPOINT=/api/validate-token
   PREVIUM_JWT_SECRET=your-jwt-secret
   PREVIUM_AUTH_TIMEOUT=3
   ###< previum-jwt ###
   ```
3. Add `previum_auth` HTTP client to `config/packages/httplug.yaml`
4. Create `config/packages/previum.yaml` for service bindings

**Acceptance Criteria**:
- [ ] `composer.json` includes `symfony/security-bundle` and `firebase/php-jwt`
- [ ] `.env.dist` has `PREVIUM_*` variables
- [ ] `httplug.yaml` has `previum_auth` client
- [ ] `previum.yaml` service config exists

**Reference**: Existing `config/packages/httplug.yaml` (client definition pattern)

---

## BE-002: Create Custom Exceptions

**Role**: Backend Engineer
**Methodology**: TDD (Red-Green-Refactor)
**Dependencies**: BE-001
**Spec**: SPEC-F06 (Error Responses)

**SOLID Requirements**:
- **SRP**: Each exception represents exactly one error case
- **Pattern**: Custom Exceptions (existing project pattern)

**Tests to Write FIRST**:
- [ ] `test_invalid_token_exception_returns_401_code()`
- [ ] `test_invalid_token_exception_has_correct_message()`
- [ ] `test_insufficient_permissions_exception_returns_403_code()`
- [ ] `test_insufficient_permissions_exception_has_correct_message()`

**Files to Create**:
- `src/Previum/Exception/InvalidTokenException.php`
- `src/Previum/Exception/InsufficientPermissionsException.php`
- `tests/Unit/Previum/Exception/InvalidTokenExceptionTest.php`
- `tests/Unit/Previum/Exception/InsufficientPermissionsExceptionTest.php`

**Acceptance Criteria**:
- [ ] `InvalidTokenException` returns code 401
- [ ] `InsufficientPermissionsException` returns code 403
- [ ] Both follow existing `EditorialNotPublishedYetException` pattern
- [ ] Tests pass

**Reference**: `src/Exception/EditorialNotPublishedYetException.php` (existing pattern)

---

## BE-003: Create PreviumUser Model

**Role**: Backend Engineer
**Methodology**: TDD (Red-Green-Refactor)
**Dependencies**: BE-001
**Spec**: SPEC-F03 (User type verification)

**SOLID Requirements**:
- **SRP**: Value object — only holds user identity data, no logic
- **LSP**: Fully implements `Symfony\Component\Security\Core\User\UserInterface`
- **Pattern**: Value Object

**Tests to Write FIRST**:
- [ ] `test_previum_user_returns_correct_identifier()`
- [ ] `test_previum_user_returns_correct_user_type()`
- [ ] `test_previum_user_returns_roles_with_previum_role()`
- [ ] `test_previum_user_implements_user_interface()`

**Files to Create**:
- `src/Previum/Model/PreviumUser.php`
- `tests/Unit/Previum/Model/PreviumUserTest.php`

**Acceptance Criteria**:
- [ ] Implements `UserInterface`
- [ ] Constructor accepts `identifier` and `userType`
- [ ] `getRoles()` returns `['ROLE_PREVIUM']`
- [ ] All properties are readonly
- [ ] Tests pass

---

## BE-004: Create JwtDecoder Interface and Implementation

**Role**: Backend Engineer
**Methodology**: TDD (Red-Green-Refactor)
**Dependencies**: BE-001
**Spec**: SPEC-F03 (Decode response JWT)

**SOLID Requirements**:
- **DIP**: Authenticator depends on `JwtDecoderInterface`, not `firebase/php-jwt`
- **ISP**: Interface has single method: `decode(string $jwt): array`
- **SRP**: Only decodes JWT — no validation, no HTTP calls
- **Pattern**: Strategy (Interface + Implementation)

**Tests to Write FIRST**:
- [ ] `test_decode_valid_jwt_returns_payload_array()`
- [ ] `test_decode_invalid_jwt_throws_invalid_token_exception()`
- [ ] `test_decode_expired_jwt_throws_invalid_token_exception()`
- [ ] `test_decode_jwt_with_wrong_secret_throws_invalid_token_exception()`

**Files to Create**:
- `src/Previum/Token/JwtDecoderInterface.php`
- `src/Previum/Token/FirebaseJwtDecoder.php`
- `tests/Unit/Previum/Token/FirebaseJwtDecoderTest.php`

**Acceptance Criteria**:
- [ ] `JwtDecoderInterface` defines `decode(string $jwt): array`
- [ ] `FirebaseJwtDecoder` uses `firebase/php-jwt` for decoding
- [ ] Throws `InvalidTokenException` on any decode failure
- [ ] Tests pass with real JWT encoding/decoding

**Reference**: `firebase/php-jwt` library documentation

---

## BE-005: Create TokenValidator Interface and Implementation

**Role**: Backend Engineer
**Methodology**: TDD (Red-Green-Refactor)
**Dependencies**: BE-002, BE-004
**Spec**: SPEC-F02 (Validate JWT via external microservice)

**SOLID Requirements**:
- **DIP**: Authenticator depends on `TokenValidatorInterface`, not concrete HTTP client
- **ISP**: Interface has single method: `validate(string $token): array`
- **SRP**: Only sends HTTP request and returns decoded response — no auth logic
- **Pattern**: Strategy (Interface + Implementation) + Adapter (wraps HTTP client)

**Tests to Write FIRST**:
- [ ] `test_validate_with_valid_token_returns_decoded_payload()`
- [ ] `test_validate_with_invalid_token_throws_invalid_token_exception()`
- [ ] `test_validate_when_auth_service_returns_500_throws_invalid_token_exception()`
- [ ] `test_validate_when_auth_service_unreachable_throws_invalid_token_exception()`
- [ ] `test_validate_when_response_jwt_is_malformed_throws_invalid_token_exception()`

**Files to Create**:
- `src/Previum/Token/TokenValidatorInterface.php`
- `src/Previum/Token/HttpTokenValidator.php`
- `tests/Unit/Previum/Token/HttpTokenValidatorTest.php`

**Acceptance Criteria**:
- [ ] `TokenValidatorInterface` defines `validate(string $token): array`
- [ ] `HttpTokenValidator` sends POST to auth microservice with the client JWT
- [ ] Returns decoded payload from response JWT using `JwtDecoderInterface`
- [ ] Throws `InvalidTokenException` on HTTP error, timeout, or malformed response
- [ ] Uses `previum_auth` HTTP client from httplug
- [ ] Tests pass with mocked HTTP client

**Reference**: `config/packages/httplug.yaml` (existing HTTP client pattern)

---

## BE-006: Create PreviumJwtAuthenticator

**Role**: Backend Engineer
**Methodology**: TDD (Red-Green-Refactor)
**Dependencies**: BE-002, BE-003, BE-005
**Spec**: SPEC-F01, SPEC-F02, SPEC-F03 (Full auth flow)

**SOLID Requirements**:
- **SRP**: Orchestrates auth flow only — delegates validation and decoding
- **DIP**: Depends on `TokenValidatorInterface` abstraction
- **Pattern**: Symfony Custom Authenticator

**Tests to Write FIRST**:
- [ ] `test_supports_only_previum_routes()`
- [ ] `test_authenticate_with_valid_previum_token_returns_passport()`
- [ ] `test_authenticate_without_authorization_header_throws_exception()`
- [ ] `test_authenticate_with_malformed_bearer_throws_exception()`
- [ ] `test_authenticate_with_invalid_token_throws_exception()`
- [ ] `test_authenticate_with_non_previum_user_type_throws_insufficient_permissions()`
- [ ] `test_on_authentication_failure_returns_json_401()`
- [ ] `test_on_authentication_failure_for_403_returns_json_403()`
- [ ] `test_on_authentication_success_returns_null()`

**Files to Create**:
- `src/Previum/Authenticator/PreviumJwtAuthenticator.php`
- `tests/Unit/Previum/Authenticator/PreviumJwtAuthenticatorTest.php`

**Acceptance Criteria**:
- [ ] Implements `AuthenticatorInterface`
- [ ] `supports()`: returns true for requests to `/previum/*`
- [ ] `authenticate()`: extracts Bearer token, calls `TokenValidatorInterface::validate()`, checks `user_type`, returns `SelfValidatingPassport`
- [ ] `onAuthenticationFailure()`: returns JSON response with correct status code
- [ ] `onAuthenticationSuccess()`: returns null (continue to controller)
- [ ] Tests pass with mocked dependencies

**Reference**: Symfony Security documentation for custom authenticators

---

## BE-007: Create Symfony Security Firewall Configuration

**Role**: Backend Engineer
**Methodology**: Configuration
**Dependencies**: BE-006
**Spec**: SPEC-F05 (Existing endpoint unaffected)

**What to Do**:
1. Create `config/packages/previum_firewall.yaml` with:
   - `previum` firewall: `pattern: ^/previum`, `stateless: true`, authenticator reference
   - `main` firewall: `pattern: ^/`, `security: false`
2. Ensure firewall order is correct (previum before main)

**Acceptance Criteria**:
- [ ] `previum` firewall matches `/previum/*` routes
- [ ] `previum` firewall is stateless
- [ ] `main` firewall has `security: false`
- [ ] Firewall order: previum first, main second
- [ ] Existing `/editorials/{id}` is NOT affected by any firewall

---

## BE-008: Create PreviumEditorialOrchestrator

**Role**: Backend Engineer
**Methodology**: TDD (Red-Green-Refactor)
**Dependencies**: BE-001
**Spec**: SPEC-F04 (Serve unpublished editorial content)

**SOLID Requirements**:
- **SRP**: Only orchestrates previum editorial data aggregation
- **OCP**: Registered via `app.orchestrators` tag — `OrchestratorChainHandler` is not modified
- **LSP**: Implements `EditorialOrchestratorInterface` fully
- **Pattern**: Chain of Responsibility (existing)

**Tests to Write FIRST**:
- [ ] `test_can_orchestrate_returns_previum()`
- [ ] `test_execute_returns_editorial_data_for_published_editorial()`
- [ ] `test_execute_returns_editorial_data_for_unpublished_editorial()`
- [ ] `test_execute_with_nonexistent_editorial_throws_exception()`
- [ ] `test_execute_with_legacy_editorial_delegates_to_legacy_client()`

**Files to Create**:
- `src/Orchestrator/Chain/PreviumEditorialOrchestrator.php`
- `tests/Unit/Orchestrator/Chain/PreviumEditorialOrchestratorTest.php`

**Acceptance Criteria**:
- [ ] `canOrchestrate()` returns `'previum'`
- [ ] `execute()` fetches editorial data WITHOUT `isVisible()` check
- [ ] Auto-registered via `app.orchestrators` tag (no manual registration needed)
- [ ] Response format identical to `EditorialOrchestrator`
- [ ] Tests pass

**Reference**: `src/Orchestrator/Chain/EditorialOrchestrator.php` (existing pattern)

---

## BE-009: Create PreviumEditorialController and Route

**Role**: Backend Engineer
**Methodology**: TDD (Red-Green-Refactor)
**Dependencies**: BE-006, BE-007, BE-008
**Spec**: SPEC-F04 (endpoint), SPEC-F01 (auth integration)

**SOLID Requirements**:
- **SRP**: Thin controller — delegates to orchestrator chain
- **Pattern**: Existing controller pattern (same as `EditorialController`)

**Tests to Write FIRST**:
- [ ] `test_get_previum_editorial_delegates_to_orchestrator_chain()`
- [ ] `test_get_previum_editorial_returns_json_response()`

**Files to Create**:
- `src/Controller/V1/PreviumEditorialController.php`
- `tests/Unit/Controller/V1/PreviumEditorialControllerTest.php`

**Files to Modify**:
- `config/routes/v1.yaml` — add:
  ```yaml
  getPreviumEditorialById:
      path: /previum/{id}
      controller: App\Controller\V1\PreviumEditorialController::getPreviumEditorialById
      methods: GET
  ```

**Acceptance Criteria**:
- [ ] Controller extends `AbstractController` (from MicroserviceBundle)
- [ ] Delegates to `OrchestratorChain::handler('previum', $request)`
- [ ] Has OpenAPI attributes for documentation (including 401, 403 responses)
- [ ] Route `GET /previum/{id}` is registered
- [ ] Tests pass

**Reference**: `src/Controller/V1/EditorialController.php` (existing pattern)

---

## BE-010: Integration Test and Final Verification

**Role**: QA
**Methodology**: Verification
**Dependencies**: ALL previous tasks
**Spec**: ALL specs

**What to Verify**:
1. Run full test suite: `make tests`
2. Verify PHPStan level 9: `make test_stan`
3. Verify code style: `make test_cs`
4. Verify mutation testing MSI: `make test_infection`
5. Verify existing tests still pass without modification
6. Manual verification of auth flow (if possible)

**Acceptance Criteria**:
- [ ] `make tests` passes (all tests green)
- [ ] PHPStan level 9 passes
- [ ] PHP-CS-Fixer passes
- [ ] Mutation testing MSI >= 86%
- [ ] No existing test file was modified
- [ ] All new tests follow existing naming conventions

---

## Task Summary

| Task | Description | Dependencies | Est. LOC |
|------|-------------|-------------|----------|
| BE-001 | Dependencies + Config | None | ~40 |
| BE-002 | Custom Exceptions | BE-001 | ~50 |
| BE-003 | PreviumUser Model | BE-001 | ~60 |
| BE-004 | JwtDecoder Interface + Impl | BE-001 | ~80 |
| BE-005 | TokenValidator Interface + Impl | BE-002, BE-004 | ~100 |
| BE-006 | PreviumJwtAuthenticator | BE-002, BE-003, BE-005 | ~120 |
| BE-007 | Firewall Configuration | BE-006 | ~20 |
| BE-008 | PreviumEditorialOrchestrator | BE-001 | ~200 |
| BE-009 | Controller + Route | BE-006, BE-007, BE-008 | ~80 |
| BE-010 | Integration Test + Verification | ALL | ~0 (verify) |
| **TOTAL** | | | **~750 LOC** |

---

**Generated by**: workflows:plan (Task Breakdown)
**Date**: 2026-02-09
