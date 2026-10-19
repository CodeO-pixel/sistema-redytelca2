# ADR 009 — Endpoint público separado para branding pre-login

- **Estado**: aceptado
- **Fecha**: 2026-10-12
- **Contexto**: cierre de spec 024 (proteger-api-empresa, hallazgo B09).

## Contexto

`api_empresa.php` GET devolvía la fila completa de `configuracion_empresa`
sin autenticación. Incluía no solo branding (`nombre_empresa`, `logo_path`,
`color_primario`), sino también configuración sensible del negocio:
`impuesto_porcentaje`, `dia_facturacion`, `dias_gracia_mora`,
`monto_recargo_mora`, `dias_corte_automatico`. Es el hallazgo canónico
**B09** de `AUDITORIA_2026.md`.

El frontend llama a este endpoint desde `DOMContentLoaded`, **antes** de
que el usuario se autentique, para pintar el branding de la pantalla de
login. Si se protegía el GET sin más, el endpoint respondía 401 en esa
llamada pre-login y la pantalla mostraba defaults hasta el próximo login.

El problema real no era "proteger o no proteger". Era **qué parte de la
configuración es branding público y qué parte es configuración sensible
del negocio**, y cómo servir la primera sin exponer la segunda.

## Decisión

Dividir el endpoint en dos:

1. **`api_empresa_publica.php`** (nuevo) — endpoint público, sin
   autenticación. Devuelve **solo** `nombre_empresa`, `logo_path`,
   `color_primario`. Consumido por el frontend antes del login.

2. **`api_empresa.php`** (modificado) — GET ahora protegido con
   `requirePageAccess($pdo, 'empresa')`. Devuelve la config completa.
   Consumido por el frontend después del login, y por el POST que ya
   estaba protegido.

El frontend separa las funciones correspondientes:

- `cargarConfiguracionEmpresaPublica()` → llamada en `DOMContentLoaded`,
  antes de `restoreSession()`.
- `cargarConfiguracionEmpresaCompleta()` → llamada en `restoreSession()`
  (después del login exitoso) y en `showPage('empresa')`.

## Alternativas descartadas

### Opción B — Proteger el GET entero y asumir fallback

- **Ventaja**: cambio mínimo. Un solo archivo backend, sin endpoint nuevo.
- **Razón de descarte**: la pantalla de login mostraría el branding por
  defecto hardcodeado en `index.html` (`#104aa8`, `logo_transparent.png`)
  en cualquier navegador sin caché. Si el admin cambia el color a rojo,
  un usuario en incógnito vería azul hasta autenticarse. La marca de la
  empresa queda secuestrada por la caché del navegador. UX degradada sin
  ganancia real de seguridad: el branding ya es público en la pestaña del
  navegador, en el HTML de la app, y en cualquier captura de pantalla
  compartida.

### Tercera opción — Dejar el GET abierto y solo añadir el branding sensible en el POST

- **Ventaja**: cero cambios al GET.
- **Razón de descarte**: no cierra B09. El hallazgo es exactamente que el
  GET expone config sensible sin auth. Mover campos a otro endpoint no
  cierra el hallazgo, lo reubica.

### Cuarta opción — Servir branding desde un archivo estático (`branding.json`) generado al guardar

- **Ventaja**: sin endpoint nuevo, caché HTTP trivial.
- **Razón de descarte**: añade un proceso de sincronización (el POST
  tendría que escribir el JSON además de la BD). Introduce una segunda
  fuente de verdad que puede desincronizarse. El patrón de proveedores
  de identidad (Okta, Entra, Auth0) es **endpoint**, no archivo generado.
  Además, un archivo estático en webroot es más difícil de versionar y
  auditar que un endpoint explícito.

## Consecuencias

### Positivas

- Cierra B09: la config sensible (`impuesto_porcentaje`,
  `dia_facturacion`, `dias_gracia_mora`, `monto_recargo_mora`,
  `dias_corte_automatico`) deja de ser accesible sin autenticación.
- La pantalla de login muestra el branding real de la empresa en
  cualquier navegador, incluso en incógnito y en primera visita, sin
  depender de caché.
- El frontend tiene dos funciones explícitas con propósitos distintos,
  en lugar de una sola que se usa en dos contextos con requisitos de
  auth diferentes.
- El guard SoD del módulo Configuración (implementado en
  `requirePageAccess`) se aplica al GET completo: un Operador no puede
  leer los impuestos ni los días de corte, aunque tenga la sesión activa.
- Patrón alineado con tres proveedores de identidad en producción (ver
  referencias).

### Negativas

- Un archivo más en la raíz: `api_empresa_publica.php`.
- La lista de campos públicos queda **hardcodeada** en la query de
  `api_empresa_publica.php`. Si mañana se quiere exponer un campo nuevo
  pre-login, hay que tocar ese archivo además del protegido.
- La separación público/protegido debe respetarse en futuros cambios:
  si alguien añade un campo sensible a `configuracion_empresa` y lo
  incluye "por error" en la query del público, el hallazgo vuelve.
- El `applyEmpresaConfig()` en frontend ahora se llama desde dos sitios
  (`cargarConfiguracionEmpresaPublica` y
  `cargarConfiguracionEmpresaCompleta`). Debe permanecer **idempotente**
  — lo es hoy, porque solo lee campos específicos y los vuelve a aplicar.

### Neutras

- El caché en `localStorage` (`redytelca_empresa_config`) sigue
  funcionando igual. Solo contiene los tres campos de branding, que ya
  eran públicos.
- El POST de `api_empresa.php` no cambia.
- El flujo de guardado de config desde el admin no cambia
  (`guardarEmpresa()` sigue llamando al POST protegido).

## Referencias

- `api_empresa_publica.php` — endpoint público nuevo.
- `api_empresa.php` — GET ahora con `requirePageAccess`.
- `app.js` — `cargarConfiguracionEmpresaPublica()` y
  `cargarConfiguracionEmpresaCompleta()`.
- `docs/AUDITORIA_2026.md` — hallazgo B09.
- `docs/ROADMAP.md` — spec 024.
- **Okta** — Multibrand architecture. `GET /api/public/clientid2brand/{clientId}`
  devuelve branding tokens antes de renderizar el Sign-In Widget.
  Endpoint público por diseño, separado del Management API protegido.
- **Microsoft Entra External ID** — Company Branding. La página de
  sign-in muestra neutral branding por defecto; la personalización
  completa vive detrás de Graph API (protegida).
- **Auth0** — Authentication API (pública, para front-ends y partes no
  confiables) vs Management API (protegida, para back-end y partes de
  confianza). Misma lógica de separación por contexto de consumo.