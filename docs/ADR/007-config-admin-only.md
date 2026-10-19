# ADR 007 — Config Admin-only (Grupo E, Diseño B.3)

- **Estado**: aceptado
- **Fecha**: Fase 7 (2026-10-10)
- **Contexto**: separación de funciones sobre el módulo Configuración.

## Contexto

El módulo Configuración (`id_modulo = 4`) agrupa las páginas:
`usuarios`, `permisos`, `roles`, `password`, `empresa`. Estas páginas
gestionan:

- La identidad de los usuarios del staff.
- La matriz de permisos del sistema.
- La configuración del negocio (nombre, logo, colores).

En el seed original de `rol_modulo_permiso`, el rol Operador (rol 2)
tenía permisos sobre esas páginas. En la práctica esto significaba que
un operador podía, por ejemplo:

- Crear usuarios con cualquier rol.
- Modificar la matriz de permisos.
- Otorgarse permisos que antes no tenía.

Esto viola dos principios:

1. **NIST SP 800-53 AC-5** — Separación de Funciones (SoD).
2. **NIST SP 800-53 AC-6** — Mínimo Privilegio.

## Decisión

**Diseño B.3**: el módulo Configuración está reservado al rol
Administrador (rol 1). La restricción se aplica en **dos capas**:

1. **Backend** (`login.php` y `auth_state.php`): si el usuario no tiene
   el rol 1, se filtran los módulos con `id_modulo === 4` del array
   devuelto al frontend.
2. **Frontend** (`app.js → loadRolePermissions()`): al editar un rol
   que no es Administrador, los checkboxes del módulo Configuración se
   deshabilitan con tooltip y aviso ámbar.

## Alternativas descartadas

### B.1 — Solo UI (deshabilitar checkboxes sin filtro backend)

- **Ventaja**: cambio mínimo.
- **Razón de descarte**: la UI no es una capa de seguridad. Un atacante
  con la sesión de un operador podría llamar directamente a
  `permisos_guardar.php` y otorgarse permisos. Defensa en profundidad
  exige que el backend también bloquee.

### B.2 — Solo backend (filtrar módulos sin tocar la UI)

- **Ventaja**: cambio mínimo en un solo archivo.
- **Razón de descarte**: la UI quedaría inconsistente. Un admin editando
  un rol no-Admin vería checkboxes que parecen editables pero que no se
  guardarían. Mala UX.

### B.4 — Reasignar permisos en `rol_modulo_permiso` (quitar seed del Operador)

- **Ventaja**: no requiere cambios de código.
- **Razón de descarte**: no impide que un admin otorgue esos permisos a
  un operador en el futuro. Es una restricción de datos, no de diseño.
  La spec 099 necesita separar visibilidad de operación para que esta
  decisión sea estructural.

## Consecuencias

### Positivas

- Separación de funciones real: solo el Admin gestiona identidad y
  permisos.
- Defensa en profundidad: UI + backend.
- Coherente con la práctica de la industria (Red Hat, IBM, ABP.io,
  Camunda reservan la administración al rol admin).
- Sin cambios de BD: solo filtro en backend y deshabilitado en UI.

### Negativas

- La restricción está **hardcodeada** como `id_modulo === 4` en tres
  archivos. Si se añade un módulo nuevo que también deba ser
  admin-only, hay que tocar los tres puntos.
- El filtro de módulos en `login.php` y `auth_state.php` debe
  mantenerse en sincronía. Documentado en ambas skills.
- Se solapa parcialmente con la spec 099 (RBAC dos niveles), que la
  formaliza.

### Neutras

- El rol Administrador sigue viendo y editando todo.
- El resto del seed de `rol_modulo_permiso` no se modificó.

## Referencias

- `login.php` — filtro de módulos.
- `auth_state.php` — filtro de módulos.
- `app.js → loadRolePermissions()` — deshabilitado de checkboxes.
- `docs/ROADMAP.md` — Bloque activo "Grupo E".
- `MEMORY.md` — cierre del Grupo E.
- NIST SP 800-53 AC-5, AC-6.
- `docs/ADR/008-rbac-dos-niveles.md` — formalización posterior.