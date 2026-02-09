# Functional Specs: orchestrator-solid-refactor

> Phase 2: QUE debe hacer el sistema (requisitos funcionales, no tecnicos)

---

### SPEC-F01: Pipeline como motor de enriquecimiento editorial

**Description**: El sistema debe resolver peticiones de contenido editorial ejecutando una cadena de pasos de enriquecimiento ordenados por prioridad, donde cada paso aporta un tipo de dato (editorial base, seccion, multimedia, tags, periodistas, membership, comentarios, fotos del body, noticias insertadas, editoriales recomendados, multimedia de apertura).

**Acceptance Criteria**:
- [ ] Una peticion `GET /editorials/{id}` retorna un JSON con el editorial completo enriquecido
- [ ] El enriquecimiento se ejecuta en orden de prioridad (editorial primero, datos secundarios despues)
- [ ] Si un paso de enriquecimiento falla, los demas continuan (degradacion graceful)
- [ ] El JSON de respuesta es identico al producido por el sistema actual

**Verification**: Test de paridad: comparar output JSON del pipeline vs output del orchestrator legacy para el mismo editorial ID.

---

### SPEC-F02: Diferenciacion de visibilidad por tipo de contenido

**Description**: El sistema debe aplicar reglas de visibilidad distintas segun el tipo de contenido solicitado. El tipo "editorial" solo permite editoriales publicados (isVisible=true). El tipo "previum" permite cualquier editorial, incluyendo no publicados.

**Acceptance Criteria**:
- [ ] `GET /editorials/{id}` con editorial no publicado retorna HTTP 404
- [ ] `GET /previum/{id}` con editorial no publicado retorna HTTP 200 con el contenido
- [ ] `GET /editorials/{id}` con editorial publicado retorna HTTP 200
- [ ] `GET /previum/{id}` con editorial publicado retorna HTTP 200
- [ ] Nuevos tipos de visibilidad pueden anadirse sin modificar codigo existente

**Verification**: Tests con editoriales publicados y no publicados para ambas rutas.

---

### SPEC-F03: Backward compatibility total de la API

**Description**: Los endpoints existentes (`/editorials/{id}`, `/previum/{id}`) deben mantener exactamente el mismo contrato JSON. Los controllers no se modifican. Las rutas no cambian.

**Acceptance Criteria**:
- [ ] `EditorialController::getEditorialById()` sigue llamando a `orchestratorChain->handler('editorial', request)` sin cambios
- [ ] `PreviumEditorialController::getEditorialById()` sigue llamando a `orchestratorChain->handler('previum', request)` sin cambios
- [ ] Formato JSON de respuesta identico campo por campo
- [ ] HTTP status codes identicos (200, 404)
- [ ] Headers de respuesta identicos

**Verification**: Snapshot test comparando JSON actual vs JSON post-refactor.

---

### SPEC-F04: Independencia de enrichers

**Description**: Cada paso de enriquecimiento debe ser un servicio independiente que puede anadirse, eliminarse o modificarse sin afectar a los demas. Cada enricher recibe un contexto compartido y lo enriquece con su contribucion.

**Acceptance Criteria**:
- [ ] Anadir un nuevo enricher solo requiere crear una clase + tag en config
- [ ] Eliminar un enricher no causa errores en los demas
- [ ] Cada enricher puede testearse unitariamente con un mock de su gateway
- [ ] El contexto compartido tiene getters/setters tipados para cada tipo de dato

**Verification**: Test de cada enricher individualmente. Test del pipeline sin uno de los enrichers (verify graceful degradation).

---

### SPEC-F05: Datos de noticias insertadas

**Description**: El sistema debe enriquecer las noticias insertadas dentro del body de un editorial, obteniendo para cada una: datos del editorial, seccion, firmas de periodistas y multimedia.

**Acceptance Criteria**:
- [ ] Las noticias insertadas (BodyTagInsertedNews) se identifican en el body del editorial
- [ ] Para cada noticia insertada se obtienen: editorial base, seccion, periodistas, multimedia
- [ ] Las noticias no publicadas se omiten silenciosamente
- [ ] Errores al obtener una noticia insertada no afectan a las demas

**Verification**: Test con editorial que contiene 3+ noticias insertadas, incluyendo una no publicada.

---

### SPEC-F06: Datos de editoriales recomendados

**Description**: El sistema debe enriquecer los editoriales recomendados asociados a un editorial, obteniendo para cada uno: datos del editorial, seccion, firmas y multimedia.

**Acceptance Criteria**:
- [ ] Los IDs de editoriales recomendados se extraen del editorial principal
- [ ] Para cada editorial recomendado se obtienen: editorial base, seccion, periodistas, multimedia
- [ ] Los recomendados no publicados se omiten
- [ ] Errores al obtener un recomendado no afectan a los demas

**Verification**: Test con editorial que tiene 3+ recomendados.

---

### SPEC-F07: Multimedia de apertura

**Description**: El sistema debe obtener y transformar el multimedia de apertura (opening) del editorial, que puede ser foto, widget o embed video, y procesarlo segun su tipo.

**Acceptance Criteria**:
- [ ] El multimedia de apertura se identifica por `editorial->opening()->multimediaId()`
- [ ] El tipo de multimedia (photo, widget, embed_video) determina el procesamiento
- [ ] El resultado se incluye en la respuesta JSON bajo el campo multimedia/opening

**Verification**: Tests con editoriales cuyo opening es foto, widget y video.

---

### SPEC-F08: Fotos del body

**Description**: El sistema debe extraer y obtener todas las fotos referenciadas en los body tags (BodyTagPicture y BodyTagMembershipCard) del editorial.

**Acceptance Criteria**:
- [ ] Se identifican fotos en BodyTagPicture y BodyTagMembershipCard
- [ ] Cada foto se obtiene por su ID via el servicio de multimedia
- [ ] Fotos no encontradas se omiten sin error
- [ ] Las fotos se incluyen en el contexto para su uso en la transformacion del body

**Verification**: Test con editorial que tiene 5+ fotos en el body.

---

### SPEC-F09: Abstraccion de acceso a datos

**Description**: Todos los accesos a servicios externos deben hacerse a traves de interfaces de gateway, no de clientes HTTP concretos.

**Acceptance Criteria**:
- [ ] Ningun enricher importa o depende de clases concretas de clientes HTTP
- [ ] Cada tipo de dato externo tiene su propia interface de gateway
- [ ] Las implementaciones HTTP de gateways wrappean los clientes legacy existentes
- [ ] Los gateways son mockeables para testing

**Verification**: Analisis estatico de imports en enrichers. Tests unitarios con mocks de gateways.

---

### SPEC-F10: Eliminacion de codigo legacy

**Description**: Una vez verificada la paridad completa, los orchestrators legacy se eliminan del codebase.

**Acceptance Criteria**:
- [ ] `EditorialOrchestrator.php` eliminado (536 lineas)
- [ ] `PreviumEditorialOrchestrator.php` eliminado (503 lineas)
- [ ] `EditorialOrchestratorTest.php` eliminado (1700 lineas, reemplazado por tests de enrichers)
- [ ] No quedan referencias a las clases eliminadas
- [ ] `EditorialOrchestratorLegacy.php` (trigger_deprecation) se conserva como documentacion

**Verification**: `grep -r 'EditorialOrchestrator' src/` no retorna resultados (excluyendo Legacy).
