# REDYTELCA

Sistema de gestión para ISPs (Proveedores de Servicios de Internet). Proyecto de tesis.

Backend PHP 8.2 + PDO + MariaDB. Frontend vanilla JS sin build tools. Uso interno (staff del ISP) y portal de clientes.

## Stack

- PHP 8.2 (XAMPP)
- MariaDB 10.4
- HTML, CSS, JavaScript puro (sin npm, sin bundler)

## Arranque local

```bash
# Desde la raíz del proyecto
php -S localhost:8000

# Importar la base de datos
mysql -u root bd_redytelca < bd_redytelca.sql
```

Abrir `http://localhost:8000` en el navegador.

## Estructura

- `api_*.php` — endpoints JSON del backend.
- `conexion.php` — middleware: conexión PDO + autenticación staff/cliente.
- `index.html` + `app.js` + `styles.css` — frontend.
- `bd_redytelca.sql` — fuente de verdad del esquema.
- `specs/` — specs activas (SDD).
- `tests/` — harness de tests. Ejecutar: `php tests/run.php`.
- `docs/` — documentación. Ver `docs/README.md`.
- `AGENTS.md` — reglas operativas para agentes IA.
- `MEMORY.md` — estado del proyecto entre sesiones.

## Estado actual del proyecto

Ver `MEMORY.md`. Resumen: sistema funcional en fase de correcciones post-auditoría.

## Documentación

Toda la documentación vive en `docs/`. Empezar por:

1. `docs/README.md` — índice.
2. `docs/AUDITORIA_2026.md` — hallazgos detectados.
3. `docs/ROADMAP.md` — plan de correcciones.

## Cómo trabajar en este proyecto

Ver `docs/METODOLOGIA_DESARROLLO_IA.md`. Regla principal: nada se implementa sin spec aprobada.