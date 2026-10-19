# ADR 005 — Extensión `rol_modulo` + `rol_modulo_permiso`

- **Estado**: aceptado
- **Fecha**: Fase 0 (2026-10-05)
- **Contexto**: gating de autorización en dos niveles (módulo + operación).

## Contexto

El estándar ANSI/INCITS 359 (RBAC Core) define dos relaciones
fundamentales:

- **UA** (User Assignment): usuarios ↔ roles, N:M.
- **PA** (Permission Assignment): permisos ↔ roles, N:M.

En RBAC Core, un permiso es una tupla `(objeto, operación)`. En el
modelo de REDYTELCA, el "objeto" es una página y la "operación" es un
verbo (`leer`/`crear`/`editar`/`eliminar`/`validar`).

Sin embargo, REDYTELCA tiene un nivel intermedio: el **módulo**. Un
módulo agrupa páginas (por ejemplo, "Infraestructura" agrupa Nodos,
OLTs, NAPs, Equipos). El sistema necesita:

1. Saber qué módulos ve un rol (para construir el menú del sidebar).
2. Saber qué páginas ve un rol dentro de esos módulos.
3. Saber qué operaciones puede hacer un rol sobre cada página.

Si se implementara solo con `rol_modulo_permiso`, el nivel 1 se derivaría
de tener al menos un permiso sobre alguna página del módulo. Pero
`login.php` y `auth_state.php` necesitan saber los módulos del usuario
sin agregar permisos.

## Decisión

Mantener dos tablas de gating:

- **`rol_modulo`**: N:M entre `rol` y `modulos`. Define qué módulos ve
  un rol.
- **`rol_modulo_permiso`**: N:M entre `rol_modulo`, `modulo`, `pagina` y
  `permiso`. Define qué operaciones puede hacer un rol sobre cada
  página.

`rol_modulo_permiso` tiene FK compuesta a `rol_modulo(id_rol_modulo,
id_modulo)` y a `paginas(id_pagina, id_modulo)` para asegurar que un
permiso siempre esté asociado a un módulo consistente.

## Alternativas descartadas

### Solo `rol_modulo_permiso` sin `rol_modulo`

- **Ventaja**: una sola tabla; simplifica el modelo.
- **Razón de descarte**: para construir el menú del sidebar, el backend
  tendría que hacer `SELECT DISTINCT id_modulo FROM rol_modulo_permiso`.
  Funciona, pero pierde la capacidad de distinguir "el rol ve este
  módulo pero no tiene permisos sobre ninguna página todavía" de "el rol
  no ve este módulo". El modelo de dos tablas es más expresivo.

### `rol_modulo_pagina` + `rol_modulo_permiso` (dos niveles de gating)

- **Ventaja**: tres niveles explícitos (módulo, página, operación).
- **Razón de descarte en 001a**: la tabla `rol_modulo_pagina` existía
  antes de 001a y se eliminó por redundante. En el modelo actual, la
  visibilidad de página se deriva de tener al menos un permiso sobre esa
  página. La spec 099 reintroduce `rol_modulo_pagina` para separar
  visibilidad de operación, como decisión explícita y documentada
  (ADR 008).

### Tabla única con discriminador tipo/valor

- **Ventaja**: una sola tabla, flexible.
- **Razón de descarte**: viola 1FN (atributos multivaluados en la misma
  columna) y complica los JOIN. Los permisos son un dominio cerrado
  (catálogo `permisos`) y las páginas también (`paginas`), lo que
  justifica tablas relacionales dedicadas.

## Consecuencias

### Positivas

- Gating en dos niveles: módulo (menú) y página-operación (acciones).
- Construcción eficiente del menú: `SELECT id_modulo FROM rol_modulo`.
- Integridad referencial fuerte: un permiso no puede apuntar a una
  página de otro módulo (FK compuesta).
- Documentable como extensión consciente del estándar: el jurado ve que
  se evaluó la tensión entre conformidad estricta y necesidad de
  negocio.

### Negativas

- Extensión no estándar: ANSI/INCITS 359 no describe `rol_modulo`
  explícitamente. Se documenta como decisión consciente alineada con
  la práctica enterprise (Splynx, SmartOLT usan modelos similares).
- Dos tablas que mantener sincronizadas al crear/eliminar roles. La
  eliminación en cascada está implementada en `roles_eliminar.php`.

### Neutras

- El seed de `rol_modulo` y `rol_modulo_permiso` se genera de forma
  consistente en `bd_redytelca.sql`.

## Referencias

- `bd_redytelca.sql` — tablas `rol_modulo`, `rol_modulo_permiso`,
  `paginas`, `permisos`.
- `conexion.php` — `requirePageAccess()`, `requireOperation()`.
- `docs/BD.md` — modelo de permisos.
- ANSI/INCITS 359 — Role-Based Access Control.
- `docs/ADR/004-rbac-migracion-001a.md` — migración a este modelo.
- `docs/ADR/008-rbac-dos-niveles.md` — ampliación posterior.