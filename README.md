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
php -S localhost:8000 -t public public/router.php

# Importar la base de datos
mysql -u root bd_redytelca < bd_redytelca.sql
```

Abrir `http://localhost:8000` en el navegador.

El router script (`public/router.php`) es necesario para que `/cliente` y `/admin` funcionen con `php -S`.

## Estructura

- `public/` — DocumentRoot. Solo esto se sirve por HTTP.
  - `api_*.php` — endpoints JSON del backend (thin files).
  - `index.html` + `app.js` + `styles.css` — frontend.
  - `portal_cliente.php` — portal de pago (stub, se implementa en Fase 5).
  - `router.php` — routing para `php -S` y Apache (fallback).
  - `Logos/`, `uploads/` — assets.
- `src/` — código fuente. No se sirve por HTTP.
  - `core/` — kernel MVC (Bootstrap, Config, Database, Response, Validator, Auth, Exceptions, bases).
  - `controllers/`, `services/`, `repositories/`, `models/`, `validators/` — capas MVC.
  - `tests/` — harness de tests. Ejecutar: `php src/tests/run.php`.
- `bd_redytelca.sql` — fuente de verdad del esquema.
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

Ver `docs/METODOLOGIA_DESARROLLO_IA.md`. Regla principal: cada bloque
de trabajo tiene un plan aprobado antes de tocar código. La IA auditora
redacta prompts, el humano los lleva a AI Studio, la IA auditora verifica
el resultado. Los planes de bloque viven fuera del repositorio, junto a
los documentos de continuidad.