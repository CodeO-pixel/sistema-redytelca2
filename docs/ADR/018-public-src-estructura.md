# ADR 018 -- Estructura de despliegue con public/ como DocumentRoot y src/ para el codigo

- **Estado**: aceptado
- **Fecha**: 2026-10-19
- **Contexto**: el codigo fuente vivia dentro del webroot. Cualquier URL a core/Config.php, controllers/*.php, etc. ejecutaba los archivos por HTTP. CWE-200, CWE-540.
- **Cierra**: spec 139e. Deuda 139e-webroot de MEMORY.md.

## Decision

Estructura de despliegue:

- public/ = DocumentRoot. Thin files + frontend + assets. Unico servido por HTTP.
- src/ = codigo. core/, controllers/, services/, repositories/, models/, validators/, tests/. No servido.
- Raiz = BD, docs, config. No servido.

## Alternativas descartadas

- .htaccess raiz: fragil. Depende de Apache, se pierde, no protege core/Config.php (solo bloquea extensiones). CWE-540 no se cierra.
- Vhost por app: complica dev local (vhost + hosts file en cada maquina). php -S -t public resuelve igual.
- Solo .htaccess + DocumentRoot = raiz: no protege el codigo.

## Consecuencias

### Positivas

- core/Config.php por URL devuelve 404 (verificado 6.10).
- Coherente con Laravel, Symfony, CodeIgniter, manual oficial de PHP.
- BD y docs fuera del webroot.

### Negativas

- URLs cambian a http://localhost:8000/login.php.
- Dev local: php -S localhost:8000 -t public public/router.php (con router script, no solo -t public).
- router.php necesito fix return false; en el fallback para servir thin files con php -S. Bug latente expuesto por el servidor embebido. Con Apache no se usa el router para archivos existentes (RewriteCond !-f).

### Neutras

- .htaccess raiz se conserva con Require all denied a .sql/.md/.log/.bak. Defensa en profundidad real.

## Verificacion

E2E con php -S localhost:8000 -t public public/router.php:

- Login 200 + token
- api_empresa.php 200 + config completa
- api_empresa_publica.php 200 + 3 campos
- branding.css.php 200 + CSS
- portal_cliente.php 200 + HTML
- / 200 + index.html
- api_facturas.php 200 + facturas
- api_nodos.php 200 + nodos
- /src/core/Config.php 404
- /src/.htaccess 404
- /bd_redytelca.sql 404
- BD: c=7, s=8, f=9, p=5, ct=7, pl=5 (sin dano)

## Referencias

- docs/ROADMAP.md - spec 139e.
- MEMORY.md - deuda 139e-webroot.
- Manual oficial de PHP.
- Laravel, Symfony, CodeIgniter.
- CWE-200, CWE-540.
