# ADR 002 — Sin frameworks en backend ni frontend

- **Estado**: aceptado
- **Fecha**: Fase 0 (2026)
- **Contexto**: elección del stack tecnológico del proyecto de tesis.

## Contexto

REDYTELCA se desarrolla como proyecto de tesis de grado. Debe:
- Ser defendible ante un jurado académico.
- Ser desplegable en hosting económico (XAMPP local, cPanel compartido).
- No depender de instalaciones complejas para evaluarse.
- Servir como demostración de dominio de fundamentos.

## Decisión

Usar **PHP puro + PDO** en backend y **HTML/CSS/JavaScript vanilla** en frontend, sin frameworks, sin build tools, sin npm, sin Composer en runtime.

## Alternativas descartadas

### Laravel / Symfony (backend)
- **Ventaja**: estructura, ORM, validación, ecosistema.
- **Razón de descarte**: introduce cientos de archivos generados, requiere `composer install`, `artisan`, `.env` elaborado. Un jurado que quiera probar el sistema necesita correr `composer install` y configurar la app. Para tesis, la transparencia del código vale más que la productividad del framework.

### React / Vue / Svelte (frontend)
- **Ventaja**: componentización, reactividad, herramientas de desarrollo.
- **Razón de descarte**: requieren `npm`, `node`, bundlers (Webpack/Vite). El frontend no podría abrirse con doble clic en `index.html`. El jurado no podría inspeccionar la app sin un entorno Node.

### jQuery (frontend)
- **Ventaja**: simplifica DOM y AJAX.
- **Razón de descarte**: en 2026, `fetch` nativo y `querySelector` cubren el 95% de los casos. Añadir jQuery por costumbre no es justificable.

### Alpine.js / HTMX
- **Ventaja**: minimalismo, baja curva de aprendizaje.
- **Razón de descarte**: son dependencias. La regla de "cero dependencias" es más simple de defender ante un jurado que "una dependencia pequeña, pero dependencia al fin".

## Consecuencias

### Positivas
- Despliegue: copiar archivos + crear BD. Nada más.
- Cero `node_modules`, cero `vendor/`, cero build step.
- Cualquiera con XAMPP y un navegador puede correrlo.
- Todo el código es visible, no hay magia de framework.
- Se ajusta al alcance de tesis: honestidad técnica sobre herramientas.

### Negativas
- Sin validación automática de formularios, sin ORM, sin sistema de vistas.
- `app.js` ha crecido a ~2500 líneas sin estructura (hallazgo AUD-2026-F10).
- Ruteo manual en cada endpoint (`$method === 'GET'`, `'POST'`, etc.).
- Sin migraciones automatizadas; el esquema se versiona como dump SQL.

### Neutras
- El proyecto tiene reglas estrictas de consistencia interna porque no hay framework que imponga patrones. Esa responsabilidad recae en `docs/constitution.md`.

## Referencias

- `docs/constitution.md` — principio 1 (alcance de tesis).
- `AGENTS.md` — stack declarado.
- Hallazgo AUD-2026-F10 (app.js monolítico, spec 052-modularizar-app-js).