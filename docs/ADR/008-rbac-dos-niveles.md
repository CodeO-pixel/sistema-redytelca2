# ADR 008 — RBAC dos niveles

- **Estado**: aceptado
- **Fecha**: Fase 7 (2026-10-10). Cerrado en Bloque 17 (2026-10-11).
- **Contexto**: ampliación del modelo RBAC a dos niveles.

## Contexto

El modelo RBAC anterior (ADR 004) era **de un solo nivel**:
`rol_modulo_permiso` era la única tabla de autorización. La visibilidad
de una página se derivaba de tener al menos un permiso sobre esa página.
Esto es conforme a **ANSI/INCITS 359** (RBAC Core).

Sin embargo, hay escenarios donde esta derivación no es ideal:

- Un rol que debe **ver** una página pero no puede **operar** sobre
  ella (por ejemplo, un rol de auditoría que ve Facturas pero no puede
  crear/editar/eliminar).
- Un rol que debe ver una página **aunque no tenga permisos todavía**
  (por ejemplo, durante la configuración inicial).

En sistemas enterprise (AWS VAMS, Salesforce, ServiceNow, Splynx), el
patrón común es **separar visibilidad de operación** en dos tablas.

El docente de la tesis confirmó explícitamente que se esperaba el
modelo de dos niveles.

## Decisión

Reintroducir la tabla `rol_modulo_pagina` como fuente de **visibilidad
de página** independiente de `rol_modulo_permiso` (que sigue siendo la
fuente de **operaciones**).

Estructura implementada:

```sql
CREATE TABLE rol_modulo_pagina (
  id_rol_modulo_pagina INT AUTO_INCREMENT PRIMARY KEY,
  id_rol_modulo INT NOT NULL,
  id_modulo INT NOT NULL,
  id_pagina INT NOT NULL,
  UNIQUE (id_rol_modulo, id_pagina),
  FOREIGN KEY (id_rol_modulo) REFERENCES rol_modulo(id_rol_modulo) ON DELETE CASCADE,
  FOREIGN KEY (id_pagina) REFERENCES paginas(id_pagina) ON DELETE CASCADE
);
```

`requirePageAccess()` consulta `rol_modulo_pagina` (nivel 1).
`requireOperation()` consulta `rol_modulo_permiso` (nivel 2).

**Decisión de diseño:** los permisos se insertan aunque la visibilidad
esté desmarcada. Desmarcar visibilidad deja los permisos inertes:
`requirePageAccess` rechaza antes de llegar a `requireOperation`.
Re-marcar visibilidad los reactiva sin reconfigurarlos.

## Referencias

- `bd_redytelca.sql` — tabla `rol_modulo_pagina`.
- `conexion.php` — `requirePageAccess()`, `requireOperation()`.
- `docs/ROADMAP.md` — Bloque 17.
- ANSI/INCITS 359 — Role-Based Access Control.
- `docs/ADR/004-rbac-migracion-001a.md` — modelo de un solo nivel.
