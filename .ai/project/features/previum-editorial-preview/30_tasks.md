# Task Breakdown: previum-editorial-preview

> Tasks organized by vertical slices (from 03_slices.md)
> Each task follows TDD. Each slice is demoable end-to-end.

---

## Slice V1: JWT Authentication Pipeline

### V1-001: Dependencies and Configuration

**Role**: Backend | **TDD**: No (config)
**Breadboard**: S1 (Env Config), A7

1. Add `symfony/security-bundle` and `firebase/php-jwt` to `composer.json`
2. Add `PREVIUM_*` env vars to `.env.dist` and `.env.test`
3. Add `previum_auth` HTTP client to `config/packages/httplug.yaml`
4. Create `config/packages/previum.yaml` (service bindings)

**Done when**: composer.json updated, env vars defined, httplug client configured.

---

### V1-002: Custom Exceptions

**Role**: Backend | **TDD**: Red-Green-Refactor
**Breadboard**: N9, N10
**Pattern**: Custom Exceptions (existing `EditorialNotPublishedYetException` pattern)

**Tests FIRST**:
- `test_invalid_token_exception_returns_401()`
- `test_insufficient_permissions_returns_403()`

**Files**: `src/Previum/Exception/InvalidTokenException.php`, `InsufficientPermissionsException.php` + tests
**Reference**: `src/Exception/EditorialNotPublishedYetException.php`

---

### V1-003: PreviumUser Model

**Role**: Backend | **TDD**: Red-Green-Refactor
**Breadboard**: S2
**SOLID**: SRP (value object), LSP (UserInterface)

**Tests FIRST**:
- `test_returns_correct_identifier()`
- `test_returns_roles_with_previum()`
- `test_implements_user_interface()`

**Files**: `src/Previum/Model/PreviumUser.php` + test

---

### V1-004: JwtDecoder Interface + Implementation

**Role**: Backend | **TDD**: Red-Green-Refactor
**Breadboard**: N6
**SOLID**: DIP (interface), ISP (single method), SRP (decode only)

**Tests FIRST**:
- `test_decode_valid_jwt_returns_payload()`
- `test_decode_invalid_jwt_throws_exception()`
- `test_decode_expired_jwt_throws_exception()`

**Files**: `src/Previum/Token/JwtDecoderInterface.php`, `FirebaseJwtDecoder.php` + test

---

### V1-005: TokenValidator Interface + Implementation

**Role**: Backend | **TDD**: Red-Green-Refactor
**Breadboard**: N4, N5
**SOLID**: DIP (interface), SRP (HTTP call only)

**Tests FIRST**:
- `test_validate_valid_token_returns_payload()`
- `test_validate_when_auth_returns_500_throws()`
- `test_validate_when_auth_unreachable_throws()`
- `test_validate_when_response_malformed_throws()`

**Files**: `src/Previum/Token/TokenValidatorInterface.php`, `HttpTokenValidator.php` + test

---

### V1-006: PreviumJwtAuthenticator

**Role**: Backend | **TDD**: Red-Green-Refactor
**Breadboard**: N1, N2, N3, N7
**SOLID**: SRP (orchestrates auth), DIP (depends on interfaces)

**Tests FIRST**:
- `test_supports_previum_routes_only()`
- `test_authenticate_valid_previum_token()`
- `test_authenticate_no_header_throws()`
- `test_authenticate_wrong_user_type_throws_403()`
- `test_on_failure_returns_json_401()`
- `test_on_success_returns_null()`

**Files**: `src/Previum/Authenticator/PreviumJwtAuthenticator.php` + test

---

### V1-007: Firewall Configuration

**Role**: Backend | **TDD**: No (config)
**Breadboard**: N1

Create `config/packages/previum_firewall.yaml`:
- `previum` firewall: `^/previum`, stateless, custom authenticator
- `main` firewall: `^/`, `security: false`

**Done when**: Firewall isolates previum routes, existing routes unaffected.

---

### V1 Demo Checkpoint

```
curl -X GET /previum/123                              → 401
curl -X GET /previum/123 -H "Authorization: Bearer X" → 401 (invalid)
curl -X GET /previum/123 -H "Authorization: Bearer VALID_WRONG_TYPE" → 403
curl -X GET /previum/123 -H "Authorization: Bearer VALID_PREVIUM"    → passes auth ✓
```

---

## Slice V2: Previum Editorial Endpoint

### V2-001: PreviumEditorialOrchestrator

**Role**: Backend | **TDD**: Red-Green-Refactor
**Breadboard**: N11, N12
**SOLID**: SRP, OCP (compiler pass), LSP (EditorialOrchestratorInterface)

**Tests FIRST**:
- `test_can_orchestrate_returns_previum()`
- `test_execute_serves_published_editorial()`
- `test_execute_serves_unpublished_editorial()`
- `test_execute_nonexistent_throws()`

**Files**: `src/Orchestrator/Chain/PreviumEditorialOrchestrator.php` + test
**Reference**: `src/Orchestrator/Chain/EditorialOrchestrator.php`

---

### V2-002: PreviumEditorialController + Route

**Role**: Backend | **TDD**: Red-Green-Refactor
**Breadboard**: N8, S3

**Tests FIRST**:
- `test_delegates_to_orchestrator_chain()`
- `test_returns_json_response()`

**Files**: `src/Controller/V1/PreviumEditorialController.php` + test
**Modified**: `config/routes/v1.yaml` (add route)
**Reference**: `src/Controller/V1/EditorialController.php`

---

### V2 Demo Checkpoint

```
curl -X GET /previum/456 -H "Authorization: Bearer VALID_PREVIUM"
→ 200 { editorial data for unpublished editorial 456 }
```

---

## Slice V3: Firewall Isolation Verification

### V3-001: Integration Verification

**Role**: QA

1. Run `make tests` — all pass
2. Run `make test_stan` — PHPStan L9 passes
3. Run `make test_cs` — code style passes
4. Run `make test_infection` — MSI >= 86%
5. Verify no existing test file was modified
6. Verify `GET /editorials/{id}` works without auth

### V3 Demo Checkpoint

```
curl -X GET /editorials/789 → 200 (no auth needed, existing behavior)
curl -X GET /previum/789    → 401 (auth required)
make tests                  → all green
```

---

## Summary

| Task | Slice | Description | Deps |
|------|-------|-------------|------|
| V1-001 | V1 | Dependencies + Config | None |
| V1-002 | V1 | Exceptions | V1-001 |
| V1-003 | V1 | PreviumUser | V1-001 |
| V1-004 | V1 | JwtDecoder | V1-001 |
| V1-005 | V1 | TokenValidator | V1-002, V1-004 |
| V1-006 | V1 | Authenticator | V1-002, V1-003, V1-005 |
| V1-007 | V1 | Firewall Config | V1-006 |
| V2-001 | V2 | PreviumOrchestrator | V1-001 |
| V2-002 | V2 | Controller + Route | V1-007, V2-001 |
| V3-001 | V3 | Integration Verification | ALL |

---

**Generated by**: workflows:plan (Tasks, structured by vertical slices from 03_slices.md)
**Date**: 2026-02-09
