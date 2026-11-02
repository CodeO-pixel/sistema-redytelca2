// js/portal/main.js
// Entry point del portal de cliente. Se ejecuta en DOMContentLoaded.
// - Setea `window.__appContext = 'portal'` para que `ApiClient` use
//   `PortalSession` en vez de `Session` (admin).
// - Attach listeners del login + password modal.
// - Registra los módulos del portal.
// - Restaura la sesión si existe token válido.
//
// FIXES 2026-11-02 (auditoría del portal):
//   - A7: `onClear` limpia los 5 Models de datos (Servicio, Factura,
//     Pago, MetodoPago, Ticket) además de Notificacion. Sin esto,
//     después de logout → login de otro cliente, los Models quedaban
//     con datos de la sesión anterior y un fallo de red en el
//     `load()` de la nueva sesión los dejaba visibles (riesgo
//     BOLA/IDOR). Fundamento: "When a user logs out, the frontend
//     must clear all client-side storage — localStorage,
//     sessionStorage, IndexedDB, cookies, and in-memory state."
//     Patrón Auth0, MSAL, TanStack Query.
//   - A5: `onClear` llama `PortalAuthView.showLogin()`. Sin esto,
//     un 401 (token expirado o revocado desde el admin) borraba la
//     sesión del localStorage pero no redirigía al login: el
//     usuario quedaba en la página de Tickets con la sesión muerta
//     y sin feedback. Fundamento: "On 401, clear local state...
//     so your auth context routes back to login" (Sinequa). No se
//     usa `PortalAuthController.logout()` porque haría un POST
//     `api_portal_logout.php` → 401 → `session.clear()` → recursión.
//   - A6: `main.js` carga `ConfiguracionEmpresaModel.loadPublica()`
//     antes de `restoreSession`. El PDF de factura
//     (`FacturaPdfView.js`) lee `ConfiguracionEmpresaModel.cache.publico`
//     para el logo y el nombre. Sin esto, el PDF usaba los
//     defaults hardcodeados ('REDYTELCA', '#104aa8') y el logo
//     relativo no resolvía en el popup `about:blank`.
//   - A3: `load(1)` → `load()` en los 3 `Router.register` que
//     paginan server-side (Facturas, Pagos, Tickets). Con `load(1)`
//     la paginación se reseteaba a página 1 en cada navegación.
//   - A1: `NotificacionController.load()` (sin argumento) para la
//     página `portal-notificaciones`.
(function () {
    'use strict';

    // CRÍTICO: setear el contexto ANTES de cualquier fetch. Sin esto,
    // ApiClient usa window.Session (admin) y el portal queda sin token
    // en cada request → 401 "No autenticado". Bug encontrado en 096a
    // (2026-11-01).
    window.__appContext = 'portal';

    window.__redytelcaPortal = { version: 'fase5-fixes' };

    document.addEventListener('DOMContentLoaded', async function () {
        // 0. P3 — Callback de limpieza de sesión del portal.
        //    Se dispara cuando `PortalSession.clear()` se ejecuta por
        //    cualquier vía: logout explícito, restore fallido o 401 de
        //    `ApiClient`. El patrón es estándar en SPAs: al perder la
        //    sesión se detiene todo poll/fetch de fondo y se limpia el
        //    estado en memoria.
        if (window.PortalSession) {
            window.PortalSession.onClear(function () {
                // Notificaciones (existente desde 086d).
                if (window.PortalNotificacionController) window.PortalNotificacionController.stopPolling();
                if (window.PortalNotificacionModel) window.PortalNotificacionModel.reset();
                if (window.PortalNotificacionView) {
                    window.PortalNotificacionView.renderBadge(0);
                    window.PortalNotificacionView.toggleDropdown(false);
                }
                // A7 — Limpiar cachés de datos de la sesión anterior.
                if (window.PortalServicioModel) window.PortalServicioModel.cache = [];
                if (window.PortalFacturaModel) {
                    window.PortalFacturaModel.cache = [];
                    window.PortalFacturaModel.cacheLineas = {};
                }
                if (window.PortalPagoModel) window.PortalPagoModel.cache = [];
                if (window.PortalMetodoPagoModel) window.PortalMetodoPagoModel.cache = [];
                if (window.PortalTicketModel) {
                    window.PortalTicketModel.cache = [];
                    window.PortalTicketModel.categoriasCache = [];
                }
                // A5 — Redirigir al login. Sin esto, un 401 deja al
                // usuario en la página actual con la sesión muerta.
                if (window.PortalAuthView) window.PortalAuthView.showLogin();
            });
        }

        // 1. Listeners del login + password modal + profile menu.
        if (window.PortalAuthView) window.PortalAuthView._attachListeners();

        // 2. Registrar módulos del portal.
        //    A3: load() sin argumento para que cada controller use su
        //    `window.__portalXxxCurrentPage` restaurado de sessionStorage.
        if (window.PortalServicioController) {
            window.PortalRouter.register('portal-servicios', {
                onShow: function () { window.PortalServicioController.load(); }
            });
        }
        if (window.PortalFacturaController) {
            window.PortalRouter.register('portal-facturas', {
                onShow: function () { window.PortalFacturaController.load(); }
            });
        }
        if (window.PortalPagoController) {
            window.PortalRouter.register('portal-pagos', {
                onShow: function () { window.PortalPagoController.load(); }
            });
        }
        if (window.PortalTicketController) {
            window.PortalRouter.register('portal-tickets', {
                onShow: function () { window.PortalTicketController.load(); }
            });
        }
        if (window.PortalNotificacionController) {
            window.PortalRouter.register('portal-notificaciones', {
                onShow: function () { window.PortalNotificacionController.load(); }
            });
        }

        // 3. Cargar branding público para el PDF de factura (A6).
        //    `api_empresa_publica.php` es público, no requiere token.
        //    Se llama antes de `restoreSession` para que el logo esté
        //    disponible en `ConfiguracionEmpresaModel.cache.publico`
        //    cuando el usuario abra una factura.
        if (window.ConfiguracionEmpresaModel) {
            try {
                await window.ConfiguracionEmpresaModel.loadPublica();
            } catch (e) {
                console.warn('[Portal] loadPublica error', e);
            }
        }

        // 4. Restaurar sesión si existe token.
        var restored = false;
        try {
            restored = await window.PortalAuthController.restoreSession();
        } catch (e) {
            console.warn('[Portal] restoreSession error', e);
        }

        if (!restored) {
            if (window.PortalAuthView) window.PortalAuthView.showLogin();
        } else {
            // P1 — No arrancar polling si el cliente debe cambiar la
            // contraseña. El backend rechaza con 403 hasta que la cambie.
            var session = window.PortalSession ? window.PortalSession.get() : null;
            var mustChange = session && session.must_change_password;
            if (!mustChange && window.PortalNotificacionController) {
                window.PortalNotificacionController.startPolling();
            }
        }

        // 5. Smoke test en consola.
        setTimeout(function () {
            var session = window.PortalSession ? window.PortalSession.get() : null;
            console.info('[REDYTELCA Portal] Session state:', {
                authenticated: window.PortalSession ? window.PortalSession.isAuthenticated() : false,
                cliente: session ? session.cliente : null,
                must_change_password: session ? session.must_change_password : null,
                appContext: window.__appContext,
                registeredPages: window.PortalRouter ? window.PortalRouter.listRegistered() : []
            });
        }, 400);
    });

    // 086d: cuando el login es exitoso, arrancar polling.
    window.__onPortalLoginSuccess = function () {
        if (window.PortalNotificacionController) {
            window.PortalNotificacionController.startPolling();
        }
    };

    window.__portalCaches = function () {
        return {
            session: window.PortalSession ? window.PortalSession.get() : null,
            registeredPages: window.PortalRouter ? window.PortalRouter.listRegistered() : [],
            servicios: window.PortalServicioModel ? window.PortalServicioModel.cache : [],
            facturas: window.PortalFacturaModel ? window.PortalFacturaModel.cache : [],
            pagos: window.PortalPagoModel ? window.PortalPagoModel.cache : [],
            metodosPago: window.PortalMetodoPagoModel ? window.PortalMetodoPagoModel.cache : [],
            tickets: window.PortalTicketModel ? window.PortalTicketModel.cache : [],
            categoriasTicket: window.PortalTicketModel ? window.PortalTicketModel.categoriasCache : [],
            notificaciones: window.PortalNotificacionModel ? window.PortalNotificacionModel.cache : [],
            notificacionesNoLeidas: window.PortalNotificacionModel ? window.PortalNotificacionModel.lastCount : 0,
            notificacionesPage: window.__portalNotifCurrentPage || 1,
            empresaPublica: window.ConfiguracionEmpresaModel ? window.ConfiguracionEmpresaModel.cache.publico : null
        };
    };
})();