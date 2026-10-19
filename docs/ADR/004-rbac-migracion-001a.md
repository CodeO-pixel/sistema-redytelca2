# ADR 004 — Migración al modelo RBAC Core N:M

- **Estado**: aceptado
- **Fecha**: Fase 0 (2026-10-05)
- **Contexto**: migración 001a del esquema de autorización.

## Contexto

En las fases tempranas del proyecto, el modelo de autorización se había
construido sobre tres decisiones que no correspondían al estándar RBAC:

1. **`usuarios.id_rol`** (columna escalar): cada usuario tenía un único
   rol. Esto impedía que un mismo usuario pudiera combinar roles (por
   ejemplo, un técnico que también fuera supervisor de un área). El
   modelo correcto exige N:M (relación Usuario-Rol como en
   ANSI/INCITS 359).

2. **`rol_modulo_pagina`** (tabla de visibilidad binaria): autorizaba
   *qué páginas ve* un rol, pero no distinguía *qué operaciones* puede
   hacer sobre cada una. Un rol con visibilidad sobre "Facturas" podía
   leer y escribir sin diferenciación. El estándar RBAC exige separar
   objeto (recurso) de operación (acción).

3. **`sesion_rol_activo`** (tabla): modelaba "rol activo por sesión",
   concepto que no existe en RBAC Core.

La tabla `usuario_rol` ya existía y estaba poblada (3 filas), y
`rol_modulo_permiso` existía con seed completo, pero ningún endpoint
las consultaba. El código leía `usuarios.id_rol` como escalar y
`rol_modulo_pagina` para gating de página.

## Decisión

Migrar el esquema al modelo RBAC Core N:M conforme a ANSI/INCITS 359:

- **Eliminar** la columna `usuarios.id_rol`.
- **Eliminar** la tabla `rol_modulo_pagina`.
- **Eliminar** la tabla `sesion_rol_activo`.
- **Consolidar** `usuario_rol` como única fuente de asignación
  usuario-rol (N:M).
- **Consolidar** `rol_modulo_permiso` como única fuente de autorización
  por operación, con estructura `(id_rol_modulo, id_modulo, id_pagina,
  id_permiso)`.

La migración se registró en `logs` (ids 151-152).

## Alternativas descartadas

### Mantener `usuarios.id_rol` como escalar único

- **Ventaja**: cero migración; el código existente sigue funcionando.
- **Razón de descarte**: incumple ANSI/INCITS 359. Impide multi-rol. En
  una defensa de tesis ante un jurado que invoca el estándar, esto es
  no conforme en Fase 1 (bloqueante).

### Mantener `rol_modulo_pagina` y añadir `rol_modulo_permiso` en paralelo

- **Ventaja**: transición sin romper el gating de página actual.
- **Razón de descarte**: duplica fuentes de verdad. Toda consulta
  tendría que decidir cuál usar. La migración limpia elimina la
  ambigüedad.

### Migrar directamente al modelo RBAC de dos niveles

- **Ventaja**: más granular; es lo que el docente pidió después.
- **Razón de descarte en 001a**: el estándar ANSI/INCITS 359 describe
  RBAC Core en un solo nivel. La conformidad formal con el estándar es
  el objetivo de la Fase 1. El modelo de dos niveles es una extensión
  documentada aparte (ADR 008, spec 099).

## Consecuencias

### Positivas

- Conformidad con ANSI/INCITS 359 en Fase 1 estructural.
- Un usuario puede tener varios roles.
- Autorización granular por operación (`leer`/`crear`/`editar`/
  `eliminar`/`validar`).
- Una única fuente de verdad para asignación (`usuario_rol`) y una
  única para autorización (`rol_modulo_permiso`).
- Habilita el middleware `requireOperation()`.

### Negativas

- La migración requirió cambios coordinados en backend (`conexion.php`,
  `login.php`, `auth_state.php`, `api_perfil.php`, `usuarios_*`,
  `roles_*`, `permisos_*`) y frontend (`app.js`, `index.html`).
- El modelo de un solo nivel es conforme al estándar pero no es la
  práctica más común en sistemas enterprise modernos. Se compensa con
  la spec 099.

### Neutras

- El seed de `rol_modulo_permiso` se mantuvo sin cambios.
- Los logs de auditoría registraron la migración (ids 151-152).

## Referencias

- `specs/001a-rbac-migracion-esquema/spec.md` — spec de la migración.
- `bd_redytelca.sql` — esquema resultante.
- `docs/BD.md` — documentación del esquema post-001a.
- `conexion.php` — `requirePageAccess()`, `requireOperation()`,
  `loadUserRoles()`, `loadUserRoleNames()`.
- ANSI/INCITS 359 — Role-Based Access Control.
- `docs/ADR/008-rbac-dos-niveles.md` — ampliación posterior.