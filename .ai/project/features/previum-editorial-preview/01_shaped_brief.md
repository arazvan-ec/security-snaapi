# Shaped Brief: previum-editorial-preview

## Frame

### Problem
Los editores y usuarios premium no tienen forma de previsualizar editoriales antes de su publicación. Actualmente, el endpoint `GET /editorials/{id}` devuelve 404 para editoriales no publicados (`isVisible() === false`), y no existe ninguna capa de autenticación en SNAAPI que permita restringir acceso a contenido sensible.

### Outcome
Un endpoint securizado donde usuarios autenticados con tipo "previum" pueden ver editoriales independientemente de su estado de publicación. La autenticación se delega a un microservicio externo via HTTP, y solo usuarios con `user_type=previum` en el JWT de respuesta tienen acceso.

---

## Requirements

| # | Requirement | Status |
|---|-------------|--------|
| R0 | Servir editoriales no publicados a usuarios autorizados | Core goal |
| R1 | Extraer JWT del header `Authorization: Bearer` de la petición | Must-have |
| R2 | Validar el JWT enviándolo via HTTP POST a un microservicio externo de autenticación | Must-have |
| R3 | Decodificar el JWT que devuelve el microservicio y verificar `user_type === 'previum'` | Must-have |
| R4 | Devolver `401 Unauthorized` cuando no hay token, token inválido, o el microservicio falla | Must-have |
| R5 | Devolver `403 Forbidden` cuando el JWT es válido pero `user_type !== 'previum'` | Must-have |
| R6 | El endpoint existente `GET /editorials/{id}` no se modifica en absoluto | Must-have |
| R7 | El formato de respuesta del nuevo endpoint es idéntico al de `/editorials/{id}` | Must-have |
| R8 | La respuesta del nuevo endpoint NO tiene cache público (contenido privado por usuario) | Must-have |
| R9 | Integración con Symfony Security (firewall stateless) | Must-have |
| R10 | Timeout agresivo (3s) para la llamada HTTP al microservicio de auth | Nice-to-have |

---

## Shape A: Symfony Security Authenticator + Nuevo Orquestador

| # | Part | Description | Flag |
|---|------|-------------|------|
| A1 | Symfony Security Firewall | Firewall stateless `^/previum` con `custom_authenticator`, firewall `main` con `security: false` para rutas existentes | |
| A2 | PreviumJwtAuthenticator | Implementa `AuthenticatorInterface`: extrae Bearer, llama a A3, verifica `user_type`, retorna Passport | |
| A3 | HttpTokenValidator | Servicio que envía POST al microservicio de auth con el JWT del cliente. Usa HTTPlug/Guzzle7 (patrón existente). Devuelve payload decodificado | |
| A4 | JwtDecoder | Decodifica el JWT de respuesta del microservicio usando `firebase/php-jwt`. Interfaz + implementación (DIP) | |
| A5 | PreviumEditorialOrchestrator | Nuevo orquestador en la Chain of Responsibility (`canOrchestrate() = 'previum'`). Misma lógica que `EditorialOrchestrator` sin el check `isVisible()` | |
| A6 | PreviumEditorialController | Thin controller que delega a `OrchestratorChain::handler('previum', $request)` | |
| A7 | Ruta y configuración | Ruta `GET /previum/{id}`, configuración httplug para nuevo cliente HTTP, variables de entorno | |

---

## Fit Check: R x A

| Req | Description | A |
|-----|-------------|---|
| R0 | Servir editoriales no publicados | :white_check_mark: A5 (sin check isVisible) + A6 |
| R1 | Extraer JWT del header | :white_check_mark: A2 (supports + authenticate) |
| R2 | Validar JWT via HTTP POST a microservicio | :white_check_mark: A3 (HttpTokenValidator) |
| R3 | Decodificar JWT respuesta y verificar user_type | :white_check_mark: A4 (decode) + A2 (check user_type) |
| R4 | 401 cuando token ausente/inválido/servicio falla | :white_check_mark: A2 (onAuthenticationFailure) + A3 (excepciones) |
| R5 | 403 cuando user_type incorrecto | :white_check_mark: A2 (InsufficientPermissionsException) |
| R6 | Endpoint existente sin cambios | :white_check_mark: A1 (firewall main: security false) |
| R7 | Mismo formato de respuesta | :white_check_mark: A5 (misma lógica de agregación) |
| R8 | Sin cache público | :white_check_mark: A6 (no s-maxage en respuesta) |
| R9 | Integración Symfony Security | :white_check_mark: A1 + A2 (firewall + authenticator nativo) |
| R10 | Timeout 3s para HTTP auth | :white_check_mark: A7 (config httplug con timeout: 3) |

**Result**: All green. No flagged parts. Shape A is ready.

---

## Fit Check: A x R (rotated)

| Part | Description | R0 | R1 | R2 | R3 | R4 | R5 | R6 | R7 | R8 | R9 | R10 |
|------|-------------|----|----|----|----|----|----|----|----|----|----|-----|
| A1 | Security Firewall | | | | | | | :white_check_mark: | | | :white_check_mark: | |
| A2 | JwtAuthenticator | | :white_check_mark: | | :white_check_mark: | :white_check_mark: | :white_check_mark: | | | | :white_check_mark: | |
| A3 | HttpTokenValidator | | | :white_check_mark: | | :white_check_mark: | | | | | | |
| A4 | JwtDecoder | | | | :white_check_mark: | :white_check_mark: | | | | | | |
| A5 | PreviumOrchestrator | :white_check_mark: | | | | | | | :white_check_mark: | | | |
| A6 | Controller | :white_check_mark: | | | | | | | | :white_check_mark: | | |
| A7 | Route + Config | | | | | | | | | | | :white_check_mark: |

---

## Decisions

| # | Decision | Status | Resolution |
|---|----------|--------|------------|
| D1 | Composición vs herencia para PreviumEditorialOrchestrator | Resolved | Orquestador independiente (no hereda de EditorialOrchestrator) — evita LSP issues y acoplamiento |
| D2 | lexik/jwt-auth-bundle vs firebase/php-jwt | Resolved | firebase/php-jwt — solo necesitamos decodificar, no la suite completa de auth |
| D3 | Módulo src/Previum/ vs src/Security/ | Resolved | src/Previum/ — evita trigger del hook de seguridad en paths, mantiene cohesión de dominio |
| D4 | JWT validation: local vs delegada | Resolved | Delegada a microservicio externo via HTTP (requisito del usuario) |

## Next Steps

- [x] Shape complete (all R covered, no flags)
- [ ] Proceed to breadboarding (Phase 6)
- [ ] Proceed to slicing (Phase 7)
- [ ] Handoff to `/workflows:plan`

---

**Generated by**: workflows:shape (Phases 1-5)
**Date**: 2026-02-09
