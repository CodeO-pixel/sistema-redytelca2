<?php
// portal_cliente.php
// SPA del portal de cliente. Rewrite completo para 096a (2026-11-01).
// 096e (2026-11-01): página `portal-servicios` (read-only).
// 096b (2026-11-01): página `portal-facturas` (read-only, formatDual).
// 096c (2026-11-01): página `portal-pagos` (list + create + upload).
// 096d (2026-11-02): página `portal-tickets` (list + create + detail + reply).
// 096f (2026-11-02): `FacturaPdfView.js` para imprimir factura (window.print).
// 086d (2026-11-02): campana de notificaciones del cliente.
//
// FIXES 2026-11-02 (auditoría del portal):
//   - A1: nueva página `#portal-notificaciones` (buzón completo). El
//     dropdown de la campana es preview de las últimas 20; el botón
//     "Ver todas las notificaciones" navega a esta página. Patrón
//     Gmail/Slack/GitHub/Salesforce/Zendesk/Splynx/Sonar/Inter/Digitel/
//     Cantv: dropdown = preview, página = inbox completo.
//   - A4: regla CSS `html[data-portal-session="1"] #portal-app
//     { display: flex !important; }`. Sin esto, con sesión existente
//     `#portal-app` seguía con `display:none` inline hasta que
//     `restoreSession()` terminaba (100–500 ms), dejando un flash
//     blanco al recargar. Patrón render-blocking (Keycloakify).
//   - A6: `<script src="js/models/ConfiguracionEmpresaModel.js">`
//     antes de `main.js`. Sin esto, `main.js` (`await
//     ConfiguracionEmpresaModel.loadPublica()`) lanzaba TypeError,
//     el branding quedaba en defaults, y el PDF de factura salía
//     sin logo (usaba URL relativa en popup `about:blank`).
//
// Contrato de IDs que el JS busca (verificado contra
// NotificacionController.js y NotificacionView.js):
//   Dropdown topbar:
//     #portal-notif-wrap, #portal-notif-bell, #portal-notif-bell-badge,
//     #portal-notif-dropdown, #portal-notif-dropdown-list,
//     #portal-notif-mark-all, #portal-notif-view-all.
//   Página #portal-notificaciones:
//     #portal-notif-search, #portal-notif-estado,
//     #portal-notificaciones-list (tbody),
//     #portal-notificaciones-counter, #portal-notificaciones-pagination.
//   Modal de detalle:
//     #portal-notif-detail-modal, #portal-notif-detail-title,
//     #portal-notif-detail-content.
//
// Sesión separada del admin: fork de Session.js con clave
// `redytelca_portal_session`. Un mismo navegador puede tener admin y
// portal abiertos a la vez.
//
// Branding pre-login render-blocking: branding.css.php (accent color) +
// branding-logo.php?slot=login (logo) en el <head>. Sin FOUC.
//
// Gate: el backend (Auth::requireClientAuth) rechaza con 403 cualquier
// api_portal_* si must_change_password=1, excepto auth_state y
// cambiar_password. El frontend muestra un modal bloqueante hasta que
// el cliente cambie la contraseña.

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Portal de Cliente - REDYTELCA</title>
    <link rel="icon" type="image/png" href="Logos/logo_transparent.png">
    <meta name="robots" content="noindex, nofollow">

    <link rel="stylesheet" href="portal.css">
    <link rel="stylesheet" href="branding.css.php">

    <style>
        html.portal-login-visible,
        html.portal-login-visible body { overflow: hidden; }
        html[data-portal-session="1"] #portal-login-overlay { display: none !important; }
        /* A4 (2026-11-02): con sesión existente, mostrar #portal-app de
           inmediato. Sin esto, `#portal-app` seguía con `display:none`
           inline hasta que `restoreSession()` terminaba (100–500 ms),
           dejando un flash blanco al recargar. Patrón render-blocking
           (Keycloakify, no-ui-flash). */
        html[data-portal-session="1"] #portal-app { display: flex !important; }
    </style>

    <script>
    (function () {
        try {
            var raw = localStorage.getItem('redytelca_portal_session');
            if (!raw) {
                document.documentElement.classList.add('portal-login-visible');
                return;
            }
            var s = JSON.parse(raw);
            if (s && s.token) {
                document.documentElement.setAttribute('data-portal-session', '1');
            } else {
                document.documentElement.classList.add('portal-login-visible');
            }
        } catch (e) {
            document.documentElement.classList.add('portal-login-visible');
        }
    })();
    </script>
</head>
<body>

<!-- ================================================================
     LOGIN OVERLAY
     ================================================================ -->
<div id="portal-login-overlay" class="portal-login-overlay" style="display:flex;">
    <div class="portal-login-card">
        <div class="portal-login-brand">
            <img id="portal-login-logo" src="branding-logo.php?slot=login" alt="REDYTELCA" class="portal-login-logo">
        </div>
        <h3 class="portal-login-title">Portal de Cliente</h3>
        <p class="portal-login-subtitle">Accede a tus servicios, facturas y tickets.</p>

        <form id="portal-login-form" class="portal-login-form" autocomplete="on">
            <div class="portal-field">
                <label for="portal-username">Usuario</label>
                <input id="portal-username" type="text" autocomplete="username" placeholder="Tu usuario" required>
            </div>
            <div class="portal-field">
                <label for="portal-password">Contraseña</label>
                <input id="portal-password" type="password" autocomplete="current-password" placeholder="Tu contraseña" required>
            </div>
            <div id="portal-login-feedback" class="portal-login-feedback" role="alert"></div>
            <button id="portal-login-button" type="submit" class="portal-btn-primary">Ingresar</button>
        </form>

        <p class="portal-login-help">
            ¿Olvidaste tu contraseña? Contacta al administrador del servicio.
        </p>
    </div>
</div>

<!-- ================================================================
     APP (sidebar + main)
     ================================================================ -->
<div id="portal-app" class="portal-app" style="display:none;">

    <aside class="portal-sidebar" id="portal-sidebar">
        <div class="portal-brand">
            <img id="portal-sidebar-logo" src="branding-logo.php?slot=sidebar" alt="REDYTELCA" class="portal-brand-logo">
        </div>
        <nav id="portal-sidebar-nav" class="portal-sidebar-nav"></nav>
    </aside>

    <main class="portal-main" id="portal-main">
        <header class="portal-topbar">
            <div class="portal-topbar-left">
                <button type="button" class="portal-sidebar-toggle" id="portal-sidebar-toggle" aria-label="Alternar menú">☰</button>
                <div>
                    <p id="portal-page-eyebrow" class="portal-eyebrow">Portal</p>
                    <h2 id="portal-page-title">Inicio</h2>
                </div>
            </div>
            <div class="portal-topbar-right">

                <!-- 086d: campana de notificaciones del cliente -->
                <div class="portal-notif-wrap" id="portal-notif-wrap">
                    <button class="portal-notif-bell" id="portal-notif-bell" type="button"
                            aria-label="Notificaciones" aria-haspopup="true" aria-expanded="false">
                        🔔
                        <span class="portal-notif-bell-badge" id="portal-notif-bell-badge"
                              role="status" aria-live="polite" style="display:none;"></span>
                    </button>
                    <div class="portal-notif-dropdown" id="portal-notif-dropdown"
                         style="display:none;" role="dialog" aria-label="Notificaciones">
                        <div class="portal-notif-dropdown-header">
                            <span>Notificaciones</span>
                            <button type="button" class="portal-notif-link" id="portal-notif-mark-all">
                                Marcar todas como leídas
                            </button>
                        </div>
                        <div class="portal-notif-dropdown-list" id="portal-notif-dropdown-list"
                             role="log" aria-live="polite"></div>
                        <div class="portal-notif-dropdown-footer">
                            <button type="button" class="portal-notif-link" id="portal-notif-view-all">
                                Ver todas las notificaciones
                            </button>
                        </div>
                    </div>
                </div>

                <button type="button" class="portal-profile-button" id="portal-profile-button">
                    <span id="portal-profile-name">Cliente</span>
                    <span class="portal-profile-arrow">▾</span>
                </button>
                <div id="portal-profile-menu" class="portal-profile-menu"></div>
            </div>
        </header>

        <div id="portal-pages" class="portal-pages">

            <!-- ====================================================
                 PÁGINA: Mis servicios (096e, read-only)
                 ==================================================== -->
            <div id="portal-servicios" class="portal-page">
                <div class="portal-card">
                    <div class="portal-toolbar">
                        <input id="portal-servicios-search" type="text" class="portal-search"
                               placeholder="Buscar servicio (alias, plan, NAP, dirección)...">
                    </div>

                    <div class="portal-table-wrap">
                        <table class="portal-table">
                            <thead>
                                <tr>
                                    <th>Alias</th>
                                    <th>Plan</th>
                                    <th>Precio</th>
                                    <th>Estado</th>
                                    <th>Red</th>
                                    <th>Dirección</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="portal-servicios-list"></tbody>
                        </table>
                    </div>

                    <div id="portal-servicios-counter" class="portal-text-muted"></div>
                    <div id="portal-servicios-pagination" class="portal-pagination"></div>
                </div>
            </div>

            <!-- ====================================================
                 PÁGINA: Mis facturas (096b, read-only, formatDual)
                 ==================================================== -->
            <div id="portal-facturas" class="portal-page">
                <div class="portal-card">
                    <div class="portal-toolbar" data-toolbar="portal-facturas">
                        <select id="portal-facturas-estado" class="portal-select">
                            <option value="">Todas</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="pagada">Pagada</option>
                            <option value="parcial">Parcial</option>
                            <option value="vencida">Vencida</option>
                            <option value="anulada">Anulada</option>
                        </select>
                        <input id="portal-facturas-periodo" type="text" class="portal-search"
                               placeholder="Periodo (AAAA-MM)" maxlength="7">
                        <button type="button" class="portal-btn-primary" data-action="apply-filters">Aplicar</button>
                        <button type="button" class="portal-btn-secondary" data-action="clear-filters">Limpiar</button>
                    </div>

                    <div class="portal-table-wrap">
                        <table class="portal-table">
                            <thead>
                                <tr>
                                    <th>Periodo</th>
                                    <th>Servicio</th>
                                    <th>Monto</th>
                                    <th>Pagado</th>
                                    <th>Saldo</th>
                                    <th>Vencimiento</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="portal-facturas-list"></tbody>
                        </table>
                    </div>

                    <div id="portal-facturas-counter" class="portal-text-muted"></div>
                    <div id="portal-facturas-pagination" class="portal-pagination"></div>
                </div>
            </div>

            <!-- ====================================================
                 PÁGINA: Mis pagos (096c, list + create + upload)
                 ==================================================== -->
            <div id="portal-pagos" class="portal-page">
                <div class="portal-card">
                    <div class="portal-toolbar" data-toolbar="portal-pagos">
                        <select id="portal-pagos-estado" class="portal-select">
                            <option value="">Todos</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="validado">Validado</option>
                            <option value="rechazado">Rechazado</option>
                        </select>
                        <button type="button" class="portal-btn-secondary" data-action="clear-filters">Limpiar</button>
                        <button type="button" class="portal-btn-primary" data-action="new-pago" style="margin-left:auto;">Registrar pago</button>
                    </div>

                    <div class="portal-table-wrap">
                        <table class="portal-table">
                            <thead>
                                <tr>
                                    <th>Factura</th>
                                    <th>Servicio</th>
                                    <th>Monto</th>
                                    <th>Fecha</th>
                                    <th>Método</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="portal-pagos-list"></tbody>
                        </table>
                    </div>

                    <div id="portal-pagos-counter" class="portal-text-muted"></div>
                    <div id="portal-pagos-pagination" class="portal-pagination"></div>
                </div>
            </div>

            <!-- ====================================================
                 PÁGINA: Mis tickets (096d, list + create + detail + reply)
                 ==================================================== -->
            <div id="portal-tickets" class="portal-page">
                <div class="portal-card">
                    <div class="portal-toolbar" data-toolbar="portal-tickets">
                        <select id="portal-tickets-estado" class="portal-select">
                            <option value="">Todos</option>
                            <option value="Abierto">Abierto</option>
                            <option value="En proceso">En proceso</option>
                            <option value="Cerrado">Cerrado</option>
                        </select>
                        <select id="portal-tickets-prioridad" class="portal-select">
                            <option value="">Todas las prioridades</option>
                            <option value="Alta">Alta</option>
                            <option value="Media">Media</option>
                            <option value="Baja">Baja</option>
                        </select>
                        <button type="button" class="portal-btn-primary" data-action="new-ticket" style="margin-left:auto;">Nuevo ticket</button>
                    </div>

                    <div class="portal-table-wrap">
                        <table class="portal-table">
                            <thead>
                                <tr>
                                    <th>Asunto</th>
                                    <th>Categoría</th>
                                    <th>Prioridad</th>
                                    <th>Estado</th>
                                    <th>Creado</th>
                                    <th>SLA vence</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="portal-tickets-list"></tbody>
                        </table>
                    </div>

                    <div id="portal-tickets-counter" class="portal-text-muted"></div>
                    <div id="portal-tickets-pagination" class="portal-pagination"></div>
                </div>
            </div>

            <!-- ====================================================
                 PÁGINA: Notificaciones (A1, buzón completo)
                 Dropdown = preview de 20. Página = inbox completo.
                 Patrón Gmail/Slack/Salesforce/Zendesk/Splynx/Sonar.
                 ==================================================== -->
            <div id="portal-notificaciones" class="portal-page">
                <div class="portal-card">
                    <div class="portal-toolbar" data-toolbar="portal-notificaciones">
                        <input id="portal-notif-search" type="text" class="portal-search"
                               placeholder="Buscar por título, mensaje o tipo...">
                        <select id="portal-notif-estado" class="portal-select">
                            <option value="">Todas</option>
                            <option value="no leido">No leídas</option>
                            <option value="leido">Leídas</option>
                        </select>
                        <button type="button" class="portal-btn-secondary" data-action="clear-filters">Limpiar</button>
                        <button type="button" class="portal-btn-primary" data-action="mark-all" style="margin-left:auto;">Marcar todas como leídas</button>
                    </div>

                    <div class="portal-table-wrap">
                        <table class="portal-table portal-notif-table">
                            <thead>
                                <tr>
                                    <th style="width: 110px;">Estado</th>
                                    <th style="width: 25%;">Título</th>
                                    <th>Mensaje</th>
                                    <th style="width: 160px;">Tipo</th>
                                    <th style="width: 150px;">Fecha</th>
                                    <th style="width: 110px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="portal-notificaciones-list"></tbody>
                        </table>
                    </div>

                    <div id="portal-notificaciones-counter" class="portal-text-muted"></div>
                    <div id="portal-notificaciones-pagination" class="portal-pagination"></div>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- ================================================================
     MODAL: Detalle del servicio (096e)
     ================================================================ -->
<div id="portal-servicio-detail-modal" class="portal-modal-overlay" style="display:none;">
    <div class="portal-modal-card">
        <div class="portal-modal-header">
            <h3 id="portal-servicio-detail-title">Detalle del servicio</h3>
            <button type="button" class="portal-modal-close" data-action="close-servicio-detail" aria-label="Cerrar">×</button>
        </div>
        <div id="portal-servicio-detail-content" class="portal-detail-content"></div>
    </div>
</div>

<!-- ================================================================
     MODAL: Detalle de la factura (096b)
     ================================================================ -->
<div id="portal-factura-detail-modal" class="portal-modal-overlay" style="display:none;">
    <div class="portal-modal-card modal-lg">
        <div class="portal-modal-header">
            <h3 id="portal-factura-detail-title">Detalle de la factura</h3>
            <button type="button" class="portal-modal-close" data-action="close-factura-detail" aria-label="Cerrar">×</button>
        </div>
        <div id="portal-factura-detail-content" class="portal-detail-content"></div>
    </div>
</div>

<!-- ================================================================
     MODAL: Formulario de pago (096c)
     ================================================================ -->
<div id="portal-pago-form-modal" class="portal-modal-overlay" style="display:none;">
    <div class="portal-modal-card modal-md">
        <div class="portal-modal-header">
            <h3 id="portal-pago-form-title">Registrar pago</h3>
            <button type="button" class="portal-modal-close" data-action="cancel-pago" aria-label="Cerrar">×</button>
        </div>

        <form id="portal-pago-form" class="portal-modal-form" enctype="multipart/form-data">
            <input id="portal-pago-id" type="hidden">

            <div class="portal-field">
                <label for="portal-pago-servicio">Servicio <span class="portal-required">*</span></label>
                <select id="portal-pago-servicio" required></select>
            </div>

            <div class="portal-field">
                <label for="portal-pago-factura">Factura</label>
                <select id="portal-pago-factura">
                    <option value="">Sin factura asociada (pago a cuenta)</option>
                </select>
                <small class="portal-field-help">Si no seleccionas factura, el pago queda como crédito a favor.</small>
            </div>

            <div class="portal-form-grid-2">
                <div class="portal-field">
                    <label for="portal-pago-monto">Monto USD <span class="portal-required">*</span></label>
                    <input id="portal-pago-monto" type="number" step="0.01" min="0.01" required>
                </div>
                <div class="portal-field">
                    <label for="portal-pago-moneda">Moneda del pago <span class="portal-required">*</span></label>
                    <select id="portal-pago-moneda">
                        <option value="USD" selected>USD (dólares)</option>
                        <option value="VES">VES (bolívares al cambio BCV)</option>
                    </select>
                </div>
            </div>

            <div class="portal-field" id="portal-pago-tasa-wrap" style="display:none;">
                <label for="portal-pago-tasa">Tasa BCV (Bs/USD) <span class="portal-required">*</span></label>
                <input id="portal-pago-tasa" type="number" step="0.0001" min="0.0001" placeholder="Ej. 36.5000">
                <small class="portal-field-help">Tasa del día del pago según BCV.</small>
            </div>

            <div class="portal-form-grid-2">
                <div class="portal-field">
                    <label for="portal-pago-fecha">Fecha del pago <span class="portal-required">*</span></label>
                    <input id="portal-pago-fecha" type="datetime-local" required>
                </div>
                <div class="portal-field">
                    <label for="portal-pago-metodo">Método de pago <span class="portal-required">*</span></label>
                    <select id="portal-pago-metodo" required></select>
                </div>
            </div>

            <div class="portal-field" id="portal-pago-referencia-wrap" style="display:none;">
                <label for="portal-pago-referencia">Referencia bancaria <span class="portal-required">*</span></label>
                <input id="portal-pago-referencia" type="text" maxlength="255" placeholder="Número de referencia / comprobante">
            </div>

            <div class="portal-field">
                <label for="portal-pago-comprobante">Comprobante <span class="portal-required">*</span></label>
                <input id="portal-pago-comprobante" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                <small class="portal-field-help">JPG, PNG, WEBP o PDF. Máximo 2 MB.</small>
                <div id="portal-pago-comprobante-preview" class="portal-comprobante-preview" style="display:none;"></div>
            </div>

            <div id="portal-pago-feedback" class="portal-modal-feedback" role="alert"></div>

            <div class="portal-modal-actions">
                <button type="button" class="portal-btn-secondary" data-action="cancel-pago">Cancelar</button>
                <button type="submit" class="portal-btn-primary" id="portal-pago-submit">Registrar pago</button>
            </div>
        </form>
    </div>
</div>

<!-- ================================================================
     MODAL: Detalle del pago (096c)
     ================================================================ -->
<div id="portal-pago-detail-modal" class="portal-modal-overlay" style="display:none;">
    <div class="portal-modal-card modal-md">
        <div class="portal-modal-header">
            <h3 id="portal-pago-detail-title">Detalle del pago</h3>
            <button type="button" class="portal-modal-close" data-action="close-pago-detail" aria-label="Cerrar">×</button>
        </div>
        <div id="portal-pago-detail-content" class="portal-detail-content"></div>
    </div>
</div>

<!-- ================================================================
     MODAL: Detalle del ticket (096d) — hilo de respuestas
     ================================================================ -->
<div id="portal-ticket-detail-modal" class="portal-modal-overlay" style="display:none;">
    <div class="portal-modal-card modal-lg">
        <div class="portal-modal-header">
            <h3 id="portal-ticket-detail-title">Detalle del ticket</h3>
            <button type="button" class="portal-modal-close" data-action="close-ticket-detail" aria-label="Cerrar">×</button>
        </div>
        <div id="portal-ticket-detail-content" class="portal-detail-content"></div>
    </div>
</div>

<!-- ================================================================
     MODAL: Nuevo ticket (096d)
     ================================================================ -->
<div id="portal-ticket-form-modal" class="portal-modal-overlay" style="display:none;">
    <div class="portal-modal-card modal-md">
        <div class="portal-modal-header">
            <h3 id="portal-ticket-form-title">Nuevo ticket</h3>
            <button type="button" class="portal-modal-close" data-action="close-ticket-form" aria-label="Cerrar">×</button>
        </div>

        <form id="portal-ticket-form" class="portal-modal-form">
            <div class="portal-field">
                <label for="portal-ticket-asunto">Asunto <span class="portal-required">*</span></label>
                <input id="portal-ticket-asunto" type="text" maxlength="150" required>
            </div>

            <div class="portal-form-grid-2">
                <div class="portal-field">
                    <label for="portal-ticket-prioridad">Prioridad</label>
                    <select id="portal-ticket-prioridad">
                        <option value="Media" selected>Media</option>
                        <option value="Alta">Alta</option>
                        <option value="Baja">Baja</option>
                    </select>
                </div>
                <div class="portal-field">
                    <label for="portal-ticket-categoria">Categoría</label>
                    <select id="portal-ticket-categoria">
                        <option value="">Sin categoría</option>
                    </select>
                </div>
            </div>

            <div class="portal-field">
                <label for="portal-ticket-servicio">Servicio asociado</label>
                <select id="portal-ticket-servicio">
                    <option value="">Sin servicio asociado</option>
                </select>
            </div>

            <div class="portal-field">
                <label for="portal-ticket-descripcion">Descripción</label>
                <textarea id="portal-ticket-descripcion" rows="5" placeholder="Describe el problema con el mayor detalle posible..."></textarea>
            </div>

            <div id="portal-ticket-form-feedback" class="portal-modal-feedback" role="alert"></div>

            <div class="portal-modal-actions">
                <button type="button" class="portal-btn-secondary" data-action="close-ticket-form">Cancelar</button>
                <button type="submit" class="portal-btn-primary" id="portal-ticket-form-submit">Crear ticket</button>
            </div>
        </form>
    </div>
</div>

<!-- ================================================================
     MODAL: Detalle de notificación (086d)
     ================================================================ -->
<div id="portal-notif-detail-modal" class="portal-modal-overlay" style="display:none;">
    <div class="portal-modal-card modal-md">
        <div class="portal-modal-header">
            <h3 id="portal-notif-detail-title">Notificación</h3>
            <button type="button" class="portal-modal-close" data-action="close-notif-detail" aria-label="Cerrar">×</button>
        </div>
        <div id="portal-notif-detail-content" class="portal-detail-content"></div>
    </div>
</div>

<!-- ================================================================
     PASSWORD MODAL (bloqueante si must_change_password=1)
     ================================================================ -->
<div id="portal-password-modal" class="portal-modal-overlay" style="display:none;">
    <div class="portal-modal-card">
        <div class="portal-modal-header">
            <h3 id="portal-password-title">Cambiar contraseña</h3>
            <button type="button" class="portal-modal-close" id="portal-password-close" aria-label="Cerrar">×</button>
        </div>

        <div id="portal-password-forced-msg" class="portal-password-forced-msg" style="display:none;">
            Debes cambiar tu contraseña temporal antes de continuar.
        </div>

        <form id="portal-password-form" class="portal-modal-form">
            <div class="portal-field">
                <label for="portal-password-user">Usuario</label>
                <input id="portal-password-user" type="text" readonly>
            </div>
            <div class="portal-field">
                <label for="portal-password-current">Contraseña actual</label>
                <input id="portal-password-current" type="password" autocomplete="current-password" required>
            </div>
            <div class="portal-field">
                <label for="portal-password-new">Nueva contraseña</label>
                <input id="portal-password-new" type="password" autocomplete="new-password" minlength="8" required>
                <small class="portal-field-help">Mínimo 8 caracteres. Debe ser distinta a la actual.</small>
            </div>
            <div class="portal-field">
                <label for="portal-password-confirm">Confirmar nueva contraseña</label>
                <input id="portal-password-confirm" type="password" autocomplete="new-password" minlength="8" required>
            </div>
            <div id="portal-password-feedback" class="portal-modal-feedback" role="alert"></div>
            <div class="portal-modal-actions">
                <button type="button" class="portal-btn-secondary" id="portal-password-cancel">Cancelar</button>
                <button type="submit" class="portal-btn-primary" id="portal-password-submit">Actualizar contraseña</button>
            </div>
        </form>
    </div>
</div>

<!-- ================================================================
     SCRIPTS
     ================================================================ -->
<script src="js/core/ApiClient.js"></script>
<script src="js/core/Toast.js"></script>
<script src="js/core/Modal.js"></script>
<script src="js/core/Pagination.js"></script>
<script src="js/models/ConfiguracionEmpresaModel.js"></script>
<script src="js/portal/core/Session.js"></script>
<script src="js/portal/core/Router.js"></script>
<script src="js/portal/controllers/AuthController.js"></script>
<script src="js/portal/views/AuthView.js"></script>

<!-- 096e — Portal servicios (read-only) -->
<script src="js/portal/models/ServicioModel.js"></script>
<script src="js/portal/views/ServicioView.js"></script>
<script src="js/portal/controllers/ServicioController.js"></script>

<!-- 096b — Portal facturas (read-only, formatDual) -->
<script src="js/portal/models/FacturaModel.js"></script>
<script src="js/portal/views/FacturaView.js"></script>
<script src="js/portal/controllers/FacturaController.js"></script>

<!-- 096f — Portal PDF de factura (window.print, sin dependencias) -->
<script src="js/portal/views/FacturaPdfView.js"></script>

<!-- 096c — Portal pagos (list + create + upload) -->
<script src="js/portal/models/MetodoPagoModel.js"></script>
<script src="js/portal/models/PagoModel.js"></script>
<script src="js/portal/views/PagoView.js"></script>
<script src="js/portal/controllers/PagoController.js"></script>

<!-- 096d — Portal tickets (list + create + detail + reply) -->
<script src="js/portal/models/TicketModel.js"></script>
<script src="js/portal/views/TicketView.js"></script>
<script src="js/portal/controllers/TicketController.js"></script>

<!-- 086d + A1 — Portal notificaciones del cliente (campana + buzón) -->
<script src="js/portal/models/NotificacionModel.js"></script>
<script src="js/portal/views/NotificacionView.js"></script>
<script src="js/portal/controllers/NotificacionController.js"></script>

<script src="js/portal/main.js"></script>

</body>
</html>