# Architectural Impact: previum-editorial-preview

## Summary

- Layers affected: Infrastructure (HIGH), Presentation (MEDIUM), Orchestrator (MEDIUM)
- Modules touched: 0 existing modified, 1 new module (`src/Previum/`)
- Change scope: 14 files to create, 5 files to modify

---

## Layer Analysis

| Layer | Impact Level | Changes Required |
|-------|--------------|------------------|
| **Previum (NEW)** | HIGH | New module: authenticator, token validator, JWT decoder, user model, exceptions |
| **Orchestrator** | MEDIUM | 1 new orchestrator (`PreviumEditorialOrchestrator`) |
| **Presentation/API** | MEDIUM | 1 new controller, 1 route addition |
| **Configuration** | MEDIUM | 3 new config files, 2 modified config files |
| **Domain** | NONE | No domain changes |
| **Application/DataTransformer** | NONE | Reuses existing transformers |
| **Existing Controllers** | NONE | No changes |

---

## Affected Layers Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│ PRESENTATION                                                     │
│   [UNCHANGED] EditorialController.php                            │
│   [NEW] PreviumEditorialController.php                           │
│   [MODIFIED] config/routes/v1.yaml (add previum route)           │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ PREVIUM MODULE (NEW)                                             │
│   [NEW] Authenticator/PreviumJwtAuthenticator.php                │
│   [NEW] Token/TokenValidatorInterface.php                        │
│   [NEW] Token/HttpTokenValidator.php                             │
│   [NEW] Token/JwtDecoderInterface.php                            │
│   [NEW] Token/FirebaseJwtDecoder.php                             │
│   [NEW] Model/PreviumUser.php                                    │
│   [NEW] Exception/InvalidTokenException.php                      │
│   [NEW] Exception/InsufficientPermissionsException.php           │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ ORCHESTRATOR                                                     │
│   [UNCHANGED] OrchestratorChainHandler.php                       │
│   [UNCHANGED] EditorialOrchestrator.php                          │
│   [NEW] PreviumEditorialOrchestrator.php                         │
│   (auto-registered via app.orchestrators tag)                    │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ INFRASTRUCTURE (existing, unchanged)                             │
│   [UNCHANGED] All existing clients, services, enums, traits     │
│   [REUSED] QueryEditorialClient, QuerySectionClient, etc.       │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ CONFIGURATION                                                    │
│   [MODIFIED] composer.json (add security-bundle, firebase/jwt)   │
│   [MODIFIED] .env.dist (add PREVIUM_* variables)                 │
│   [MODIFIED] config/packages/httplug.yaml (add previum_auth)     │
│   [NEW] config/packages/previum_firewall.yaml                    │
│   [NEW] config/packages/previum.yaml                             │
│   [MODIFIED] config/routes/v1.yaml (add route)                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## Existing Modules Touched

| Module | Files Touched | Risk Level | Notes |
|--------|---------------|------------|-------|
| `config/routes/v1.yaml` | 1 | LOW | Add 3 lines for new route |
| `config/packages/httplug.yaml` | 1 | LOW | Add new HTTP client definition |
| `composer.json` | 1 | LOW | Add 2 dependencies |
| `.env.dist` | 1 | LOW | Add 4 environment variables |
| `config/packages/` | 0 modified, 2 new | LOW | New config files only |

**No existing PHP source files are modified.**

---

## Change Scope

```
╔══════════════════════════════════════════════════════════════════╗
║                    CHANGE SCOPE SUMMARY                          ║
╠══════════════════════════════════════════════════════════════════╣
║  Files to CREATE:     14                                         ║
║    - PHP source:       8  (authenticator, validator, decoder,    ║
║                            user, exceptions, orchestrator,       ║
║                            controller)                           ║
║    - PHP tests:        4  (authenticator, validator, decoder,    ║
║                            orchestrator)                         ║
║    - Config:           2  (previum_firewall.yaml, previum.yaml)  ║
║                                                                  ║
║  Files to MODIFY:      5                                         ║
║    - composer.json     (add 2 dependencies)                      ║
║    - .env.dist         (add 4 env vars)                          ║
║    - httplug.yaml      (add 1 client definition)                 ║
║    - routes/v1.yaml    (add 1 route)                             ║
║    - services.yaml     (exclude Previum/ from autowire if needed)║
║  ────────────────────────────────────────────────                ║
║  Total files affected: 19                                        ║
║                                                                  ║
║  Estimated LOC added:   ~600                                     ║
║  Estimated LOC modified: ~25                                     ║
║  ────────────────────────────────────────────────                ║
║  Complexity: MEDIUM-HIGH                                         ║
║  Trust Level: HIGH (first auth feature)                          ║
╚══════════════════════════════════════════════════════════════════╝
```

---

## Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Symfony Security bundle conflicts with existing exception handling | LOW | MEDIUM | Isolate with dedicated firewall; main firewall has `security: false` |
| HTTP timeout to auth microservice causes slow responses | MEDIUM | MEDIUM | Configure aggressive timeout (3s); retry plugin handles transient failures |
| `firebase/php-jwt` version conflict with other dependencies | LOW | LOW | Check compatibility before `composer require` |
| PreviumEditorialOrchestrator diverges from EditorialOrchestrator over time | MEDIUM | LOW | Document relationship; consider extraction of shared trait in future |
| PHPStan level 9 strictness with new Symfony Security types | MEDIUM | LOW | Use proper PHPDoc annotations; security interfaces are well-typed |
| Auth microservice JWT format changes | LOW | HIGH | Define clear contract; JwtDecoderInterface allows swap |

---

**Generated by**: workflows:plan (Phase 3 - Architectural Impact)
**Date**: 2026-02-09
