// ============================================================================
// REDYTELCA - app.js (Reescritura completa - Vanilla JS)
// ============================================================================

// ----------------------------------------------------------------------------
// 1. ESTADO GLOBAL Y CACHÉS
// ----------------------------------------------------------------------------
window.clientesCache = [];
window.usuariosCache = [];
window.nodosCache = [];
window.oltsCache = [];
window.napsCache = [];
window.serviciosCache = [];
window.facturasCache = [];
window.pagosCache = [];
window.metodosPagoCache = [];
window.planesCache = [];
window.contratosCache = [];
window.equiposCache = [];
window.ticketsCache = [];
window.tareasCache = [];
window.rolesCache = [];

window.permissionContext = {
    authenticated: false,
    user: '',
    roles: [],
    roleName: '',
    token: '',
    modules: [],
    must_change_password: false
};

window.currentClienteSearch = '';
window.currentUsersFilter = 'Todos';
window.currentTicketsFilter = 'Todos';
window.mapFilterState = { router: '', emisor: '', ubicacion: '' };

window.selectedIds = {};
window.pageSize = 8;

window.leafletMapInitialized = false;
window.leafletMapInstance = null;
window.leafletBaseLayers = null;
window.leafletActiveBaseLayer = null;
window.leafletMarkers = [];
window.leafletMarkersById = new Map();
window.leafletSearchMarker = null;
window.dashboardCharts = {};

const MARACAIBO_FALLBACK_CENTER = { lat: 10.6427, lon: -71.6125 };

// ----------------------------------------------------------------------------
// 2. UTILIDADES CORE
// ----------------------------------------------------------------------------
function escapeHtml(unsafe) {
    return String(unsafe ?? '')
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function normalizeForSearch(str) {
    return String(str ?? '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
}

/**
 * Bug 4 — helper de filtrado por búsqueda.
 * Devuelve los items cuyo contenido coincida (case-insensitive, substring)
 * con el valor del input indicado. Si el input está vacío o no existe,
 * devuelve el array tal cual.
 *
 * @param {Array} items        Caché a filtrar
 * @param {Array|Function} fieldsOrFn  Lista de campos a concatenar, o función que devuelve el string a buscar
 * @param {string} searchId    id del <input> en el DOM
 */
function filterBySearch(items, fieldsOrFn, searchId) {
    const input = document.getElementById(searchId);
    const search = normalizeForSearch((input?.value || '').trim());
    if (!search) return Array.isArray(items) ? items : [];

    return (Array.isArray(items) ? items : []).filter(item => {
        let haystack;
        if (typeof fieldsOrFn === 'function') {
            haystack = fieldsOrFn(item);
        } else {
            haystack = (fieldsOrFn || []).map(field => item?.[field] ?? '').join(' ');
        }
        return normalizeForSearch(haystack).includes(search);
    });
}

function getStoredSession() {
    try {
        return JSON.parse(localStorage.getItem('redytelca_session') || 'null');
    } catch (e) {
        return null;
    }
}

function persistSessionContext(context) {
    if (!context || !context.token) {
        localStorage.removeItem('redytelca_session');
    } else {
        localStorage.setItem('redytelca_session', JSON.stringify(context));
    }
}

function clearSessionContext() {
    // F08.b: limpiar cachés en memoria para que un cambio de rol no
    // muestre datos del rol anterior. Deben quedar como arrays vacíos.
    window.clientesCache = [];
    window.usuariosCache = [];
    window.nodosCache = [];
    window.oltsCache = [];
    window.napsCache = [];
    window.serviciosCache = [];
    window.facturasCache = [];
    window.pagosCache = [];
    window.metodosPagoCache = [];
    window.planesCache = [];
    window.contratosCache = [];
    window.equiposCache = [];
    window.ticketsCache = [];
    window.tareasCache = [];
    window.rolesCache = [];
    window.selectedIds = {};

    window.permissionContext = {
        authenticated: false, user: '', roles: [], roleName: '', token: '', modules: [], must_change_password: false
    };
    localStorage.removeItem('redytelca_session');
    const overlay = document.getElementById('login-overlay');
    if (overlay) overlay.style.display = 'flex';
    const label = document.getElementById('profile-name-label');
    if (label) label.textContent = 'Usuario';
    renderMenu();
}

function buildSessionFetchHeaders(extraHeaders = {}) {
    const stored = getStoredSession();
    const headers = { ...extraHeaders };
    if (stored && stored.token) {
        headers['X-Session-Token'] = stored.token;
    }
    return headers;
}

function isAdminRole() {
    return Array.isArray(window.permissionContext.roles) && window.permissionContext.roles.includes(1);
}

function userCan(moduleName, permiso) {
    const mod = (window.permissionContext.modules || []).find(m => {
        let url = String(m.url || '').toLowerCase();
        if (url === 'permisos') url = 'modulos';
        return url === moduleName;
    });
    if (!mod || !Array.isArray(mod.permisos)) return false;
    return mod.permisos.includes(permiso);
}

function getStatusClass(status) {
    const s = String(status || '').toLowerCase();
    if (s.includes('complet') || s.includes('cerr') || s === 'activo' || s === 'pagada' || s === 'validado' || s === 'vigente' || s === 'operativo') return 'status-ok';
    if (s.includes('curso') || s.includes('proceso') || s === 'pendiente' || s === 'suspendido' || s === 'parcial' || s === 'vencido' || s === 'mantenimiento' || s === 'stock') return 'status-warning';
    return 'status-alert';
}

function toMysqlDatetime(val) {
    if (!val) return '';
    const norm = val.replace('T', ' ');
    return norm.length === 16 ? norm + ':00' : norm;
}

function toDatetimeLocalValue(val) {
    if (!val) return '';
    return String(val).replace(' ', 'T').slice(0, 16);
}

function normalizarCoordenada(val) {
    const txt = String(val ?? '').trim();
    if (!txt) return '';
    const norm = txt.replace(',', '.').trim();
    if (!/^-?\d{1,3}(\.\d+)?$/.test(norm)) return null;
    return norm;
}

// ----------------------------------------------------------------------------
// 3. AUTENTICACIÓN Y SESIÓN
// ----------------------------------------------------------------------------
async function login() {
    const btn = document.getElementById('login-button');
    if (btn && btn.disabled) return;

    const user = document.getElementById('user').value.trim();
    const pass = document.getElementById('pass').value.trim();

    if (!user || !pass) {
        alert('Por favor ingresa usuario y contraseña.');
        return;
    }

    if (btn) btn.disabled = true;

    try {
        const res = await fetch('login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: user, password: pass })
        });

        const data = await res.json();
        if (data.status === 'success') {
            window.permissionContext = {
                authenticated: true,
                user: data.usuario,
                roles: data.roles || [],
                roleName: data.rol_nombre || '',
                token: data.token || '',
                modules: data.modulos_permitidos || [],
                must_change_password: data.must_change_password === true
            };
            persistSessionContext(window.permissionContext);
            document.getElementById('login-overlay').style.display = 'none';
            document.getElementById('user').value = '';
            document.getElementById('pass').value = '';
            
            const label = document.getElementById('profile-name-label');
            if (label) label.textContent = data.usuario || 'Usuario';
            
            await initApp();
        } else {
            alert(data.message || 'Error al iniciar sesión');
        }
    } catch (e) {
        alert('Error de conexión con el servidor.');
        console.error(e);
    } finally {
        if (btn) btn.disabled = false;
    }
}

async function logout() {
    try {
        await fetch('logout.php', { method: 'POST', headers: buildSessionFetchHeaders() });
    } catch (e) {
        console.warn('Error en logout:', e);
    }
    clearSessionContext();
}

async function restoreSession(autoNavigate = true) {
    const stored = getStoredSession();
    if (!stored || !stored.token) {
        clearSessionContext();
        return false;
    }

    try {
        const res = await fetch('auth_state.php', { headers: { 'X-Session-Token': stored.token } });
        const data = await res.json();

        if (data.status === 'success' && data.authenticated) {
            window.permissionContext = {
                authenticated: true,
                user: data.user,
                roles: data.roles || [],
                roleName: data.roleName || '',
                token: data.token || '',
                modules: data.modules || [],
                must_change_password: data.must_change_password === true
            };
            persistSessionContext(window.permissionContext);
            
            const label = document.getElementById('profile-name-label');
            if (label) label.textContent = data.user || 'Usuario';
            
            renderMenu(autoNavigate);
            await cargarConfiguracionEmpresaCompleta();
            checkMustChangePassword();
            return true;
        } else {
            clearSessionContext();
            return false;
        }
    } catch (e) {
        clearSessionContext();
        return false;
    }
}

function checkMustChangePassword() {
    if (window.permissionContext.must_change_password) {
        const modal = document.getElementById('password-modal');
        const closeBtn = document.getElementById('password-modal-close');
        if (modal) modal.style.display = 'flex';
        if (closeBtn) closeBtn.style.display = 'none';
        const userInput = document.getElementById('password-user');
        if (userInput) userInput.value = window.permissionContext.user || '';
        const feedback = document.getElementById('password-feedback');
        if (feedback) {
            feedback.style.color = 'orange';
            feedback.textContent = 'Debes cambiar tu contraseña temporal antes de continuar.';
        }
    }
}

// ----------------------------------------------------------------------------
// 4. NAVEGACIÓN Y UI CORE
// ----------------------------------------------------------------------------
function toggleSidebar() {
    document.querySelector('.sidebar')?.classList.toggle('collapsed');
    document.querySelector('.main-content')?.classList.toggle('expanded');
    if (document.getElementById('mapa')?.classList.contains('active')) {
        setTimeout(() => window.leafletMapInstance?.invalidateSize(), 250);
    }
}

function toggleProfileMenu() {
    document.getElementById('profile-menu')?.classList.toggle('show');
}

function selectProfileMenuPage(pageId) {
    if (pageId === 'password') {
        const modal = document.getElementById('password-modal');
        const closeBtn = document.getElementById('password-modal-close');
        if (modal) modal.style.display = 'flex';
        if (closeBtn) closeBtn.style.display = 'block';
        const userInput = document.getElementById('password-user');
        if (userInput) userInput.value = window.permissionContext.user || '';
        toggleProfileMenu();
        return;
    }
    showPage(pageId);
    toggleProfileMenu();
}

function renderMenu(autoNavigate = true) {
    const container = document.getElementById('sidebar-nav');
    if (!container) return;

    const sectionMap = {
        'dash': 'Análisis', 'reportes': 'Análisis',
        'clientes': 'Operación', 'servicios': 'Operación', 'mapa': 'Operación', 'tareas': 'Operación', 'tickets': 'Operación',
        'nodos': 'Infraestructura', 'olts': 'Infraestructura', 'naps': 'Infraestructura', 'equipos': 'Infraestructura',
        'facturas': 'Finanzas', 'pagos': 'Finanzas', 'planes': 'Finanzas', 'contratos': 'Finanzas',
        'roles': 'Configuración', 'modulos': 'Configuración', 'empresa': 'Configuración', 'usuarios': 'Configuración'
    };

    const items = [];
    const seen = new Set();

    (window.permissionContext.modules || []).forEach(m => {
        let pageId = String(m.url || m.vista || '').toLowerCase();
        if (pageId === 'permisos') pageId = 'modulos';

        if (pageId && !seen.has(pageId) && pageId !== 'password' && pageId !== 'notificaciones') {
            items.push({
                page: pageId,
                label: m.vista || m.url,
                module: sectionMap[pageId] || 'Operación'
            });
            seen.add(pageId);
        }
    });

    // Fallback: si el backend no devolvió ningún módulo, añadir los mínimos.
    if (items.length === 0) {
        items.push({ page: 'dash', label: 'Dashboard', module: 'Análisis' });
        items.push({ page: 'mapa', label: 'Mapa', module: 'Operación' });
        items.push({ page: 'clientes', label: 'Clientes', module: 'Operación' });
        seen.add('dash'); seen.add('mapa'); seen.add('clientes');
    }

    const groups = { 'Análisis': [], 'Operación': [], 'Infraestructura': [], 'Finanzas': [], 'Configuración': [] };
    items.forEach(item => {
        if (groups[item.module]) groups[item.module].push(item);
    });

    const activePageBefore = document.querySelector('.page.active')?.id;

    container.innerHTML = Object.keys(groups).filter(k => groups[k].length > 0).map(groupName => `
        <div class="nav-group collapsible expanded">
            <div class="nav-group-title" onclick="toggleNavGroup(this)">
                ${escapeHtml(groupName)} <span class="nav-group-arrow">▾</span>
            </div>
            ${groups[groupName].map(item => `
                <div class="nav-item" data-page="${escapeHtml(item.page)}" onclick="showPage('${escapeHtml(item.page)}', this)">
                    ${escapeHtml(item.label)}
                </div>
            `).join('')}
        </div>
    `).join('');

    const profileMenu = document.getElementById('profile-menu');
    if (profileMenu) {
        profileMenu.innerHTML = `
            <button type="button" onclick="selectProfileMenuPage('perfil')">Mi perfil</button>
            <button type="button" onclick="selectProfileMenuPage('password')">Cambiar contraseña</button>
            <button type="button" onclick="logout()">Cerrar sesión</button>
        `;
    }

    if (autoNavigate) {
        const first = container.querySelector('.nav-item');
        if (first) showPage(first.dataset.page, first);
    } else if (activePageBefore) {
        const activeNav = container.querySelector(`.nav-item[data-page="${activePageBefore}"]`);
        if (activeNav) activeNav.classList.add('active');
    }
}

function toggleNavGroup(el) {
    const group = el.closest('.nav-group');
    if (!group) return;
    const exp = group.classList.toggle('expanded');
    const arrow = group.querySelector('.nav-group-arrow');
    if (arrow) arrow.textContent = exp ? '▾' : '▸';
}

async function showPage(pageId, element = null) {
    if (window.permissionContext.must_change_password) {
        checkMustChangePassword();
        return;
    }

    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

    const pageEl = document.getElementById(pageId);
    if (pageEl) pageEl.classList.add('active');

    const navEl = element || document.querySelector(`.nav-item[data-page="${pageId}"]`);
    if (navEl) {
        navEl.classList.add('active');
        const group = navEl.closest('.nav-group');
        if (group) {
            group.classList.add('expanded');
            const arrow = group.querySelector('.nav-group-arrow');
            if (arrow) arrow.textContent = '▾';
        }
    }

    const titles = {
        dash: { t: 'Dashboard', e: 'Análisis' }, clientes: { t: 'Clientes', e: 'Gestión' },
        servicios: { t: 'Servicios', e: 'Operación' },
        tareas: { t: 'Control de tareas', e: 'Control' }, tickets: { t: 'Tickets', e: 'Soporte' },
        mapa: { t: 'Mapa técnico', e: 'Infraestructura' }, roles: { t: 'Roles', e: 'Configuración' },
        modulos: { t: 'Módulos y permisos', e: 'Configuración' }, empresa: { t: 'Empresa', e: 'Configuración' },
        perfil: { t: 'Mi perfil', e: 'Configuración' }, usuarios: { t: 'Usuarios', e: 'Configuración' },
        reportes: { t: 'Reportes', e: 'Analítica' }, nodos: { t: 'Nodos', e: 'Infraestructura' },
        olts: { t: 'OLTs', e: 'Infraestructura' }, naps: { t: 'NAPs', e: 'Infraestructura' },
        equipos: { t: 'Equipos', e: 'Infraestructura' }, facturas: { t: 'Facturación', e: 'Finanzas' },
        pagos: { t: 'Pagos', e: 'Finanzas' }, planes: { t: 'Planes', e: 'Finanzas' },
        contratos: { t: 'Contratos', e: 'Finanzas' }
    };

    const info = titles[pageId] || { t: 'Panel', e: 'Operación' };
    const titleEl = document.getElementById('page-title');
    const eyebrowEl = document.getElementById('page-eyebrow');
    if (titleEl) titleEl.textContent = info.t;
    if (eyebrowEl) eyebrowEl.textContent = info.e;

    initToolbar(pageId);

    if (pageId === 'dash') updateDashboardData();
    if (pageId === 'reportes') renderReportes();
    if (pageId === 'servicios') renderServiciosPage(1);
    if (pageId === 'perfil') await renderPerfil();
    if (pageId === 'empresa') await cargarConfiguracionEmpresaCompleta();
    if (pageId === 'modulos') {
        const sel = document.getElementById('modulos-role-select');
        if (sel && sel.value) loadRolePermissions('modulos');
    }
    if (pageId === 'mapa') {
        limpiarFiltrosMapa();
        renderMapItems();
        setTimeout(() => window.leafletMapInstance?.invalidateSize(), 100);
    }

    if (window.innerWidth <= 768) {
        document.querySelector('.sidebar')?.classList.add('collapsed');
        document.querySelector('.main-content')?.classList.add('expanded');
    }
}

// ----------------------------------------------------------------------------
// 5. TOOLBAR Y SELECCIÓN
// ----------------------------------------------------------------------------
function initToolbar(moduleName) {
    const toolbar = document.querySelector(`[data-toolbar="${moduleName}"]`);
    if (!toolbar) return;
    toolbar.querySelectorAll('.toolbar-btn[data-module]').forEach(btn => {
        const action = btn.dataset.action || '';
        if (['edit', 'delete', 'context', 'validar', 'rechazar', 'cerrar', 'reset'].includes(action)) {
            btn.disabled = true;
        }
    });
    window.selectedIds[moduleName] = null;
    document.querySelectorAll(`[data-module-row="${moduleName}"]`).forEach(r => r.classList.remove('selected-row'));
}

function selectRow(moduleName, id, rowElement) {
    const prevId = window.selectedIds[moduleName];
    if (prevId) {
        const prevRow = document.querySelector(`[data-module-row="${moduleName}"][data-id="${prevId}"]`);
        if (prevRow) prevRow.classList.remove('selected-row');
    }
    window.selectedIds[moduleName] = id;
    if (rowElement) rowElement.classList.add('selected-row');
    updateToolbarButtons(moduleName);
}

function getSelectedId(moduleName) {
    return window.selectedIds[moduleName] || null;
}

function getSelectedItem(moduleName) {
    const id = getSelectedId(moduleName);
    if (!id) return null;
    const cacheMap = {
        'clientes': window.clientesCache, 'servicios': window.serviciosCache,
        'tareas': window.tareasCache, 'tickets': window.ticketsCache,
        'nodos': window.nodosCache, 'olts': window.oltsCache, 'naps': window.napsCache, 'equipos': window.equiposCache,
        'facturas': window.facturasCache, 'pagos': window.pagosCache, 'planes': window.planesCache,
        'contratos': window.contratosCache, 'roles': window.rolesCache, 'usuarios': window.usuariosCache
    };
    const idFieldMap = {
        'clientes': 'id_cliente', 'servicios': 'id_servicio',
        'tareas': 'id_tarea', 'tickets': 'id_ticket',
        'nodos': 'id_nodo', 'olts': 'id_olt', 'naps': 'id_nap', 'equipos': 'id_equipo',
        'facturas': 'id_factura', 'pagos': 'id_pago', 'planes': 'id_plan',
        'contratos': 'id_contrato', 'roles': 'id_rol', 'usuarios': 'id_usuario'
    };
    const cache = cacheMap[moduleName] || [];
    const field = idFieldMap[moduleName] || 'id';
    return cache.find(item => String(item[field]) === String(id)) || null;
}

function updateToolbarButtons(moduleName) {
    const hasSel = !!getSelectedId(moduleName);
    const item = getSelectedItem(moduleName);

    document.querySelectorAll(`.toolbar-btn[data-module="${moduleName}"]`).forEach(btn => {
        const action = btn.dataset.action || '';

        if (action === 'new') {
            btn.disabled = !userCan(moduleName, 'crear');
        } else if (['edit', 'reset'].includes(action)) {
            btn.disabled = !hasSel || !userCan(moduleName, 'editar');
        } else if (action === 'delete') {
            btn.disabled = !hasSel || !userCan(moduleName, 'eliminar');
        } else if (action === 'context') {
            btn.disabled = !hasSel || !userCan(moduleName, 'leer');
        } else if (action === 'validar' || action === 'rechazar') {
            // SoD: solo Admin puede validar/rechazar pagos, independiente
            // de la matriz de permisos. Ver AUDITORIA_2026 (B05).
            if (!hasSel || !item) btn.disabled = true;
            else btn.disabled = !(String(item.estado).toLowerCase() === 'pendiente' && isAdminRole());
        } else if (action === 'cerrar') {
            if (!hasSel || !item) btn.disabled = true;
            else btn.disabled = String(item.estado).toLowerCase() === 'cerrado';
        }
    });
}

function renderPagination(containerId, moduleName, page, totalItems, renderFnName) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const totalPages = Math.max(1, Math.ceil(totalItems / window.pageSize));
    const safePage = Math.min(Math.max(1, page), totalPages);
    
    container.innerHTML = `
        <div class="pagination-controls">
            <button type="button" onclick="${renderFnName}(${Math.max(1, safePage - 1)})" ${safePage <= 1 ? 'disabled' : ''}>Anterior</button>
            <span>Página ${safePage} / ${totalPages}</span>
            <button type="button" onclick="${renderFnName}(${Math.min(totalPages, safePage + 1)})" ${safePage >= totalPages ? 'disabled' : ''}>Siguiente</button>
        </div>
    `;
}

// ----------------------------------------------------------------------------
// 6. DASHBOARD Y REPORTES
// ----------------------------------------------------------------------------
function updateDashboardData() {
    const d = new Date();
    const dateEl = document.getElementById('dashboard-date');
    if (dateEl) dateEl.textContent = d.toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' });

    const setTxt = (id, txt) => { const el = document.getElementById(id); if (el) el.textContent = txt; };
    const setHtml = (id, html) => { const el = document.getElementById(id); if (el) el.innerHTML = html; };

    setTxt('preview-clientes-count', window.clientesCache.length);
    
    const activos = window.serviciosCache.filter(s => String(s.estado_comercial).toLowerCase() === 'activo').length;
    setTxt('preview-servicios-count', `${activos}/${window.serviciosCache.length}`);
    
    const srvBreakdown = window.serviciosCache.reduce((acc, s) => {
        const e = String(s.estado_comercial || 'pendiente').toLowerCase();
        acc[e] = (acc[e] || 0) + 1; return acc;
    }, {});
    setHtml('preview-servicios-breakdown', Object.entries(srvBreakdown).map(([k,v]) => `<div><span>${escapeHtml(k)}</span> <strong>${v}</strong></div>`).join(''));

    setTxt('preview-contratos-count', window.contratosCache.length);
    const vigentes = window.contratosCache.filter(c => String(c.estado).toLowerCase() === 'vigente').length;
    setHtml('preview-contratos-breakdown', `<div><span>Vigentes</span> <strong>${vigentes}</strong></div><div><span>Otros</span> <strong>${window.contratosCache.length - vigentes}</strong></div>`);

    const tAbiertos = window.ticketsCache.filter(t => String(t.estado).toLowerCase() !== 'cerrado').length;
    setTxt('preview-tickets-count', tAbiertos);

    const tarPend = window.tareasCache.filter(t => String(t.estado).toLowerCase() !== 'completada').length;
    setTxt('preview-tareas-count', tarPend);

    setTxt('preview-olts-count', window.oltsCache.length);
    const oltsConNaps = window.oltsCache.filter(o => Number(o.total_naps || 0) > 0).length;
    setHtml('preview-olts-breakdown', `<div><span>Con NAPs</span> <strong>${window.oltsCache.length - oltsConNaps}</strong></div><div><span>Sin NAPs</span> <strong>${window.oltsCache.length - oltsConNaps}</strong></div>`);

    setTxt('preview-naps-count', window.napsCache.length);
    const totalClNap = window.napsCache.reduce((acc, n) => acc + Number(n.total_clientes || 0), 0);
    const totalPuertos = window.napsCache.reduce((acc, n) => acc + Number(n.cantidad_puertos_max || 0), 0);
    const ocup = totalPuertos > 0 ? Math.round((totalClNap / totalPuertos) * 100) : 0;
    setHtml('preview-naps-breakdown', `<div><span>Clientes</span> <strong>${totalClNap}</strong></div><div><span>Ocupación</span> <strong>${ocup}%</strong></div>`);

    const fVencidas = window.facturasCache.filter(f => String(f.estado).toLowerCase() === 'vencida').length;
    setTxt('preview-facturas-vencidas-count', fVencidas);
    const fPill = document.getElementById('preview-facturas-vencidas-pill');
    if (fPill) {
        fPill.className = fVencidas > 0 ? 'stat-pill negative' : 'stat-pill positive';
        fPill.textContent = fVencidas > 0 ? 'Requiere seguimiento' : 'Sin incidencias';
    }

    const pPend = window.pagosCache.filter(p => String(p.estado).toLowerCase() === 'pendiente').length;
    setTxt('preview-pagos-pendientes-count', pPend);

    // Charts
    if (typeof Chart !== 'undefined') {
        const accentRgb = getComputedStyle(document.documentElement).getPropertyValue('--accent-rgb').trim() || '16, 74, 168';
        
        // Income
        const periods = [...new Set([...window.facturasCache.map(f=>f.periodo), ...window.pagosCache.map(p=>p.factura_periodo)])].filter(Boolean).sort().slice(-6);
        const incomeData = periods.map(per => window.pagosCache.filter(p => p.estado === 'validado' && p.factura_periodo === per).reduce((sum, p) => sum + Number(p.monto), 0));
        renderChart('dashboard-income-chart', 'line', periods, [{ label: 'Ingresos', data: incomeData, borderColor: 'var(--accent)', backgroundColor: `rgba(${accentRgb}, 0.18)`, fill: true }]);

        // Billing
        const fCounts = window.facturasCache.reduce((acc, f) => { acc[f.estado] = (acc[f.estado] || 0) + 1; return acc; }, {});
        renderChart('dashboard-billing-chart', 'doughnut', Object.keys(fCounts), [{ data: Object.values(fCounts), backgroundColor: Object.keys(fCounts).map(k => k==='pagada'?'var(--success)':k==='vencida'?'var(--danger)':'var(--warning)') }]);

        // Services
        const sCounts = window.serviciosCache.reduce((acc, s) => { acc[s.estado_comercial] = (acc[s.estado_comercial] || 0) + 1; return acc; }, {});
        renderChart('dashboard-services-chart', 'doughnut', Object.keys(sCounts), [{ data: Object.values(sCounts), backgroundColor: Object.keys(sCounts).map(k => k==='activo'?'var(--success)':k==='retirado'?'var(--danger)':'var(--warning)') }]);

        // OLTs
        const oLabels = window.oltsCache.slice(0,8).map(o => o.codigo || 'OLT');
        const oData = window.oltsCache.slice(0,8).map(o => Number(o.total_naps || 0));
        renderChart('dashboard-olts-chart', 'bar', oLabels, [{ label: 'NAPs', data: oData, backgroundColor: 'var(--accent)' }]);

        // NAPs
        const nLabels = window.napsCache.slice(0,8).map(n => n.codigo || 'NAP');
        const nDataCl = window.napsCache.slice(0,8).map(n => Number(n.total_clientes || 0));
        const nDataPt = window.napsCache.slice(0,8).map(n => Number(n.cantidad_puertos_max || 0));
        renderChart('dashboard-naps-chart', 'bar', nLabels, [{ label: 'Clientes', data: nDataCl, backgroundColor: 'var(--accent)' }, { label: 'Puertos', data: nDataPt, backgroundColor: 'var(--warning)' }]);

        // Tickets
        const tkCounts = window.ticketsCache.reduce((acc, t) => { const k = `${t.prioridad}:${t.estado}`; acc[k] = (acc[k] || 0) + 1; return acc; }, {});
        renderChart('dashboard-tickets-chart', 'bar', Object.keys(tkCounts), [{ label: 'Tickets', data: Object.values(tkCounts), backgroundColor: Object.keys(tkCounts).map(k => k.startsWith('Alta')?'var(--danger)':k.startsWith('Media')?'var(--warning)':'var(--success)') }]);
    }
}

function renderChart(canvasId, type, labels, datasets) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    if (window.dashboardCharts[canvasId]) window.dashboardCharts[canvasId].destroy();
    
    const parent = canvas.parentElement;
    parent.querySelectorAll('.dashboard-chart-empty').forEach(e => e.remove());

    if (!labels.length || datasets.every(d => d.data.every(v => !v))) {
        const p = document.createElement('p');
        p.className = 'stat-label dashboard-chart-empty';
        p.style.marginTop = '8px';
        p.textContent = 'Sin datos suficientes';
        parent.appendChild(p);
        return;
    }

    window.dashboardCharts[canvasId] = new Chart(canvas.getContext('2d'), {
        type, data: { labels, datasets },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: type === 'doughnut' } } }
    });
}

function renderReportes() {
    const setTxt = (id, txt) => { const el = document.getElementById(id); if (el) el.textContent = txt; };
    setTxt('report-total-clientes', window.clientesCache.length);
    setTxt('report-tareas-pendientes', window.tareasCache.filter(t => String(t.estado).toLowerCase() !== 'completada').length);
    setTxt('report-tareas-completadas', window.tareasCache.filter(t => String(t.estado).toLowerCase() === 'completada').length);
    setTxt('report-tickets-abiertos', window.ticketsCache.filter(t => String(t.estado).toLowerCase() !== 'cerrado').length);

    const prioEl = document.getElementById('report-tickets-prioridad');
    if (prioEl) {
        const counts = { Alta: 0, Media: 0, Baja: 0 };
        window.ticketsCache.forEach(t => { if (counts[t.prioridad] !== undefined) counts[t.prioridad]++; });
        prioEl.innerHTML = Object.entries(counts).map(([k,v]) => `<div><span>Prioridad ${escapeHtml(k)}</span><strong>${v}</strong></div>`).join('');
    }
}

// ----------------------------------------------------------------------------
// 7. MAPA (LEAFLET)
// ----------------------------------------------------------------------------
function initLeafletMap() {
    if (window.leafletMapInitialized || typeof L === 'undefined') return window.leafletMapInstance;
    const container = document.getElementById('map-embed');
    if (!container) return null;

    const map = L.map('map-embed', { zoomControl: true, scrollWheelZoom: true });
    const normal = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 });
    const sat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19 });

    window.leafletBaseLayers = { Mapa: normal, Satelital: sat };
    window.leafletActiveBaseLayer = normal;
    normal.addTo(map);
    L.control.layers(window.leafletBaseLayers, null, { position: 'topright' }).addTo(map);
    map.setView([MARACAIBO_FALLBACK_CENTER.lat, MARACAIBO_FALLBACK_CENTER.lon], 13);
    
    window.leafletMapInstance = map;
    window.leafletMapInitialized = true;
    return map;
}

function getMapItems() {
    const items = [];
    window.nodosCache.forEach(n => {
        if (Number.isFinite(Number(n.latitud)) && Number.isFinite(Number(n.longitud))) {
            items.push({ id: `nodo-${n.id_nodo}`, type: 'Nodo', icon: '📍', title: n.nombre, subtitle: n.ubicacion, meta: `Lat ${n.latitud}`, lat: Number(n.latitud), lon: Number(n.longitud), className: 'nodo', routerValue: n.nombre, emisorValue: n.ubicacion, ubicacionValue: n.ubicacion });
        }
    });
    window.oltsCache.forEach(o => {
        if (Number.isFinite(Number(o.latitud)) && Number.isFinite(Number(o.longitud))) {
            items.push({ id: `olt-${o.id_olt}`, type: 'Router', icon: '🖧', title: o.codigo || o.marca_modelo, subtitle: o.nombre_nodo, meta: o.marca_modelo, lat: Number(o.latitud), lon: Number(o.longitud), className: 'router', routerValue: o.codigo || o.marca_modelo, emisorValue: o.nombre_nodo, ubicacionValue: o.nombre_nodo });
        }
    });
    window.napsCache.forEach(n => {
        if (Number.isFinite(Number(n.latitud)) && Number.isFinite(Number(n.longitud))) {
            items.push({ id: `nap-${n.id_nap}`, type: 'Emisor', icon: '🔌', title: n.codigo, subtitle: n.olt_codigo || n.olt_nombre, meta: n.ubicacion_fisica, lat: Number(n.latitud), lon: Number(n.longitud), className: 'emisor', routerValue: n.olt_codigo || n.olt_nombre, emisorValue: n.codigo, ubicacionValue: n.ubicacion_fisica });
        }
    });
    window.clientesCache.forEach(c => {
        if (Number.isFinite(Number(c.latitud)) && Number.isFinite(Number(c.longitud))) {
            items.push({ id: `cliente-${c.id_cliente}`, type: 'Cliente', icon: '👤', title: `${c.nombres} ${c.apellidos}`.trim(), subtitle: c.nap_codigo || c.olt_codigo, meta: c.direccion, lat: Number(c.latitud), lon: Number(c.longitud), className: 'client', routerValue: c.olt_codigo, emisorValue: c.nap_codigo, ubicacionValue: c.direccion || c.nodo_nombre });
        }
    });
    return items;
}

function renderMapItems() {
    const map = initLeafletMap();
    const list = document.getElementById('map-item-list');
    if (!map || !list) return;

    const routers = new Set(window.oltsCache.map(o => o.codigo || o.marca_modelo).filter(Boolean));
    const emisores = new Set(window.napsCache.map(n => n.codigo).filter(Boolean));
    const ubicaciones = new Set([...window.nodosCache.map(n=>n.ubicacion), ...window.napsCache.map(n=>n.ubicacion_fisica), ...window.clientesCache.map(c=>c.direccion)].filter(Boolean));

    const fillSel = (id, set, def) => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = `<option value="">${escapeHtml(def)}</option>` + Array.from(set).sort().map(v => `<option value="${escapeHtml(v)}"${v===window.mapFilterState[id.replace('map-filter-','')]?' selected':''}>${escapeHtml(v)}</option>`).join('');
    };
    fillSel('map-filter-router', routers, 'Router (todos)');
    fillSel('map-filter-emisor', emisores, 'Emisor (todos)');
    fillSel('map-filter-ubicacion', ubicaciones, 'Ubicación (todas)');

    const { router, emisor, ubicacion } = window.mapFilterState;
    const items = getMapItems().filter(i => {
        if (i.type === 'Nodo') return !ubicacion || i.ubicacionValue === ubicacion;
        return (!router || i.routerValue === router) && (!emisor || i.emisorValue === emisor) && (!ubicacion || i.ubicacionValue === ubicacion);
    });

    list.innerHTML = items.length ? items.map(i => `
        <div class="map-item map-item-${i.className}" data-map-item-id="${escapeHtml(i.id)}">
            <div class="map-item-icon">${escapeHtml(i.icon)}</div>
            <div class="map-item-type">${escapeHtml(i.type)}</div>
            <div class="map-item-title">${escapeHtml(i.title)}</div>
            <div class="map-item-subtitle">${escapeHtml(i.subtitle)}</div>
            <div class="map-item-meta">${escapeHtml(i.meta)}</div>
        </div>
    `).join('') : '<div class="map-item-empty">No hay datos visibles.</div>';

    window.leafletMarkers.forEach(m => m.remove());
    window.leafletMarkers = [];
    window.leafletMarkersById.clear();

    const bounds = [];
    items.forEach(i => {
        if (i.lat === 0 && i.lon === 0) return;
        const m = L.marker([i.lat, i.lon], {
            icon: L.divIcon({ html: `<div style="font-size:22px;line-height:1;transform:translate(-50%,-100%);">${i.icon}</div>`, className: 'leaflet-map-marker', iconSize: [28,28], iconAnchor: [14,28] })
        }).bindPopup(`<div><strong>${escapeHtml(i.title)}</strong><br><span>${escapeHtml(i.subtitle)}</span></div>`).addTo(map);
        window.leafletMarkers.push(m);
        window.leafletMarkersById.set(i.id, m);
        bounds.push([i.lat, i.lon]);
    });

    if (bounds.length) map.fitBounds(L.latLngBounds(bounds), { padding: [40,40], maxZoom: 16 });
    else map.setView([MARACAIBO_FALLBACK_CENTER.lat, MARACAIBO_FALLBACK_CENTER.lon], 13);

    list.querySelectorAll('.map-item').forEach(card => {
        card.addEventListener('click', () => {
            const m = window.leafletMarkersById.get(card.dataset.mapItemId);
            if (m) { map.setView(m.getLatLng(), 15); m.openPopup(); }
        });
    });
}

function onMapFilterChange() {
    window.mapFilterState = {
        router: document.getElementById('map-filter-router')?.value || '',
        emisor: document.getElementById('map-filter-emisor')?.value || '',
        ubicacion: document.getElementById('map-filter-ubicacion')?.value || ''
    };
    const badge = document.getElementById('map-filters-active');
    if (badge) badge.style.display = (window.mapFilterState.router || window.mapFilterState.emisor || window.mapFilterState.ubicacion) ? 'inline-flex' : 'none';
    renderMapItems();
}

function limpiarFiltrosMapa() {
    window.mapFilterState = { router: '', emisor: '', ubicacion: '' };
    ['map-filter-router', 'map-filter-emisor', 'map-filter-ubicacion'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
    onMapFilterChange();
}

// ----------------------------------------------------------------------------
// 8. SELECTORES COMPARTIDOS
// ----------------------------------------------------------------------------
function populateAssignmentSelectors() {
    const buildCl = () => '<option value="">Sin cliente</option>' + window.clientesCache.map(c => `<option value="${c.id_cliente}">${escapeHtml(`${c.nombres} ${c.apellidos}`.trim() || c.cedula)}</option>`).join('');
    const buildUs = () => '<option value="">Sin asignar</option>' + window.usuariosCache.map(u => `<option value="${u.id_usuario}">${escapeHtml(u.username)}</option>`).join('');
    
    ['task-client', 'ticket-client'].forEach(id => {
        const el = document.getElementById(id);
        if (el) { const v = el.value; el.innerHTML = buildCl(); if (v) el.value = v; }
    });
    ['task-user', 'ticket-user'].forEach(id => {
        const el = document.getElementById(id);
        if (el) { const v = el.value; el.innerHTML = buildUs(); if (v) el.value = v; }
    });
}

function populateInfraSelectors() {
    const buildNd = () => '<option value="">Selecciona un nodo</option>' + window.nodosCache.map(n => `<option value="${n.id_nodo}">${escapeHtml(n.nombre)}</option>`).join('');
    const buildOl = () => '<option value="">Selecciona una OLT</option>' + window.oltsCache.map(o => `<option value="${o.id_olt}">${escapeHtml(o.codigo || o.marca_modelo)}</option>`).join('');
    
    const elNd = document.getElementById('olt-nodo');
    if (elNd) { const v = elNd.value; elNd.innerHTML = buildNd(); if (v) elNd.value = v; }
    
    const elOl = document.getElementById('nap-olt');
    if (elOl) { const v = elOl.value; elOl.innerHTML = buildOl(); if (v) elOl.value = v; }
}

function populateEquipoSelectores() {
    const elNap = document.getElementById('equipo-nap');
    if (elNap) {
        const v = elNap.value;
        elNap.innerHTML = '<option value="">Selecciona una NAP</option>' + window.napsCache.map(n => `<option value="${n.id_nap}">${escapeHtml(n.codigo)}</option>`).join('');
        if (v) elNap.value = v;
    }
    const elSrv = document.getElementById('equipo-servicio');
    if (elSrv) {
        const v = elSrv.value;
        const activos = window.serviciosCache.filter(s => String(s.estado_comercial).toLowerCase() === 'activo');
        elSrv.innerHTML = '<option value="">Sin asignar</option>' + activos.map(s => `<option value="${s.id_servicio}">${escapeHtml(s.alias || `${s.nombres} ${s.apellidos}`.trim())}</option>`).join('');
        if (v) elSrv.value = v;
    }
}

function populateFinanzasSelectors() {
    const buildSrv = () => '<option value="">Selecciona un servicio</option>' + window.serviciosCache.filter(s => s.estado_comercial !== 'retirado').map(s => `<option value="${s.id_servicio}" data-cliente="${s.id_cliente}">${escapeHtml(`${s.nombres} ${s.apellidos}`.trim() + (s.alias ? ` - ${s.alias}` : ''))}</option>`).join('');
    ['factura-servicio', 'pago-servicio', 'contrato-servicio'].forEach(id => {
        const el = document.getElementById(id);
        if (el) { const v = el.value; el.innerHTML = buildSrv(); if (v) el.value = v; }
    });
    const pSrv = document.getElementById('pago-servicio');
    if (pSrv) pSrv.onchange = populatePagoFacturaOptions;
}

function populatePagoMetodos() {
    const el = document.getElementById('pago-metodo');
    if (!el) return;
    const current = el.value;
    el.innerHTML = '<option value="">Método de pago</option>'
        + window.metodosPagoCache.map(m =>
            `<option value="${m.id_metodo}">${escapeHtml(m.nombre)}</option>`
        ).join('');
    if (current) el.value = current;
}

function populateServicioSelectores() {
    const elCliente = document.getElementById('servicio-cliente');
    if (elCliente) {
        const v = elCliente.value;
        elCliente.innerHTML = '<option value="">Selecciona un cliente</option>' + window.clientesCache.map(c => `<option value="${c.id_cliente}">${escapeHtml(`${c.nombres} ${c.apellidos}`.trim() || c.cedula)}</option>`).join('');
        if (v) elCliente.value = v;
    }
    const elNap = document.getElementById('servicio-nap');
    if (elNap) {
        const v = elNap.value;
        elNap.innerHTML = '<option value="">Sin NAP asignada</option>' + window.napsCache.map(n => `<option value="${n.id_nap}">${escapeHtml(n.codigo + (n.olt_codigo ? ` (${n.olt_codigo})` : ''))}</option>`).join('');
        if (v) elNap.value = v;
    }
    const elPlan = document.getElementById('servicio-plan');
    if (elPlan) {
        const v = elPlan.value;
        const planes = window.planesCache.length ? window.planesCache : [{id_plan:1, nombre:'Plan Básico'}];
        elPlan.innerHTML = planes.map(p => `<option value="${p.id_plan}">${escapeHtml(p.nombre)}</option>`).join('');
        if (v) elPlan.value = v;
    }
}

// ----------------------------------------------------------------------------
// 9. MÓDULOS CRUD
// ----------------------------------------------------------------------------

// --- CLIENTES ---
async function loadClientes(filter = '', page = 1) {
    window.currentClienteSearch = filter;
    try {
        const res = await fetch(`clientes_listar.php?filter=${encodeURIComponent(filter)}&page=${page}&per_page=10`, { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success') {
            window.clientesCache = data.clientes || [];
            populateAssignmentSelectors();
            updateDashboardData();
            
            const tbody = document.getElementById('clientes-list');
            if (tbody) {
                tbody.innerHTML = window.clientesCache.length ? window.clientesCache.map(c => `
                    <tr data-module-row="clientes" data-id="${escapeHtml(c.id_cliente)}" data-client-id="${c.id_cliente}" onclick="selectRow('clientes', '${escapeHtml(c.id_cliente)}', this)">
                        <td><strong>${escapeHtml(`${c.nombres} ${c.apellidos}`.trim())}</strong></td>
                        <td>${escapeHtml(c.cedula)}</td>
                        <td>${escapeHtml(c.num_telefono)}</td>
                        <td>${escapeHtml(c.correo)}</td>
                        <td>${escapeHtml(c.direccion || 'No registrada')}</td>
                        <td><span class="status status-ok">${Number(c.total_servicios || 0)} servicio(s)</span></td>
                    </tr>
                `).join('') : `<tr><td colspan="6">No hay clientes.</td></tr>`;
            }
            
            const pag = document.getElementById('clientes-pagination');
            if (pag) {
                const tp = Number(data.pages || 1);
                const cp = Number(data.page || 1);
                pag.innerHTML = `
                    <div class="pagination-controls">
                        <button onclick="loadClientes(window.currentClienteSearch, ${Math.max(1, cp - 1)})" ${cp <= 1 ? 'disabled' : ''}>Anterior</button>
                        <span>Página ${cp} / ${tp}</span>
                        <button onclick="loadClientes(window.currentClienteSearch, ${Math.min(tp, cp + 1)})" ${cp >= tp ? 'disabled' : ''}>Siguiente</button>
                    </div>
                `;
            }
        }
    } catch (e) { console.error(e); }
}

function toggleClientForm(forceClose = false, reset = true) {
    const m = document.getElementById('client-modal');
    if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
    if (!forceClose && reset) {
        ['client-id', 'client-nombres', 'client-apellidos', 'client-cedula', 'client-telefono', 'client-correo'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
    }
}

async function guardarCliente() {
    const id = document.getElementById('client-id').value;
    const payload = {
        nombres: document.getElementById('client-nombres').value.trim(),
        apellidos: document.getElementById('client-apellidos').value.trim(),
        cedula: document.getElementById('client-cedula').value.trim(),
        telefono: document.getElementById('client-telefono').value.trim(),
        correo: document.getElementById('client-correo').value.trim()
    };
    if (!payload.nombres || !payload.cedula) { alert('Faltan datos'); return; }
    if (id) payload.id_cliente = id;

    try {
        const res = await fetch(id ? 'cliente_actualizar.php' : 'registrar_cliente.php', {
            method: 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') {
            toggleClientForm(true);
            await loadClientes(window.currentClienteSearch, 1);
        }
    } catch (e) { console.error(e); }
}

function editarCliente(id) {
    const c = window.clientesCache.find(x => String(x.id_cliente) === String(id));
    if (!c) return;
    document.getElementById('client-id').value = c.id_cliente;
    document.getElementById('client-nombres').value = c.nombres;
    document.getElementById('client-apellidos').value = c.apellidos;
    document.getElementById('client-cedula').value = c.cedula;
    document.getElementById('client-telefono').value = c.num_telefono;
    document.getElementById('client-correo').value = c.correo;
    toggleClientForm(false, false);
}

async function eliminarCliente(id) {
    if (!confirm('¿Eliminar cliente y todo su historial?')) return;
    try {
        const res = await fetch('cliente_eliminar.php', {
            method: 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ id_cliente: id })
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') {
            await loadClientes(window.currentClienteSearch, 1);
            await loadServicios();
        }
    } catch (e) { console.error(e); }
}

function renderClientProfile(c) {
    const m = document.getElementById('client-detail-modal');
    const cont = document.getElementById('client-detail-content');
    if (!m || !cont || !c) return;
    
    cont.innerHTML = `
        <div class="detail-grid">
            <div class="detail-card"><span class="detail-label">Nombre</span><div class="detail-value">${escapeHtml(`${c.nombres} ${c.apellidos}`.trim())}</div></div>
            <div class="detail-card"><span class="detail-label">Cédula</span><div class="detail-value">${escapeHtml(c.cedula)}</div></div>
            <div class="detail-card"><span class="detail-label">Teléfono</span><div class="detail-value">${escapeHtml(c.num_telefono)}</div></div>
            <div class="detail-card"><span class="detail-label">Correo</span><div class="detail-value">${escapeHtml(c.correo)}</div></div>
        </div>
        <div style="margin-top:20px;">
            <div class="card-title-row">
                <h3>Servicios del cliente</h3>
                <button type="button" class="table-action" onclick="abrirNuevoServicio(${c.id_cliente})">+ Añadir servicio</button>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Servicio</th><th>Plan</th><th>NAP</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody id="client-servicios-list"></tbody>
                </table>
            </div>
            <div id="client-servicios-pagination" class="pagination-row"></div>
        </div>
    `;
    renderClientServiciosPage(c.id_cliente, 1);
    m.style.display = 'flex';
}

function closeClientDetailModal() {
    const m = document.getElementById('client-detail-modal');
    if (m) m.style.display = 'none';
}

function renderClientServiciosPage(idCliente, page) {
    const srvs = window.serviciosCache.filter(s => String(s.id_cliente) === String(idCliente));
    const tbody = document.getElementById('client-servicios-list');
    if (!tbody) return;
    
    const start = (page - 1) * 6;
    const vis = srvs.slice(start, start + 6);
    
    tbody.innerHTML = vis.length ? vis.map(s => `
        <tr>
            <td><strong>${escapeHtml(s.alias || 'Servicio')}</strong><br><small>${escapeHtml(s.direccion_texto)}</small></td>
            <td>${escapeHtml(s.plan_nombre)}</td>
            <td>${escapeHtml(s.nap_codigo || s.olt_codigo)}</td>
            <td><span class="status ${getStatusClass(s.estado_comercial)}">${escapeHtml(s.estado_comercial)}</span></td>
            <td>
                <button onclick="event.stopPropagation(); editarServicio(${s.id_servicio})" style="width:auto;padding:4px 8px;">Editar</button>
                <button onclick="event.stopPropagation(); eliminarServicio(${s.id_servicio})" style="width:auto;padding:4px 8px;background:var(--danger);">Eliminar</button>
            </td>
        </tr>
    `).join('') : '<tr><td colspan="5">Sin servicios.</td></tr>';
    
    const pag = document.getElementById('client-servicios-pagination');
    if (pag) {
        const tp = Math.max(1, Math.ceil(srvs.length / 6));
        pag.innerHTML = `<div class="pagination-controls">
            <button onclick="renderClientServiciosPage(${idCliente}, ${Math.max(1, page-1)})" ${page<=1?'disabled':''}>Ant</button>
            <span>${page}/${tp}</span>
            <button onclick="renderClientServiciosPage(${idCliente}, ${Math.min(tp, page+1)})" ${page>=tp?'disabled':''}>Sig</button>
        </div>`;
    }
}

// --- SERVICIOS ---
async function loadServicios() {
    try {
        const res = await fetch('api_servicios.php', { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success') {
            window.serviciosCache = data.servicios || [];
            populateFinanzasSelectors();
        }
    } catch (e) { console.error(e); }
}

function renderServiciosPage(page) {
    const tbody = document.getElementById('servicios-list');
    if (!tbody) return;

    const filtered = filterBySearch(
        window.serviciosCache,
        ['nombres', 'apellidos', 'alias', 'plan_nombre', 'nap_codigo', 'estado_comercial', 'direccion_texto'],
        'servicios-search'
    );

    const start = (page - 1) * window.pageSize;
    const vis = filtered.slice(start, start + window.pageSize);

    tbody.innerHTML = vis.length ? vis.map(s => `
        <tr data-module-row="servicios" data-id="${escapeHtml(s.id_servicio)}" onclick="selectRow('servicios', '${escapeHtml(s.id_servicio)}', this)">
            <td>${escapeHtml(s.nombres ? `${s.nombres} ${s.apellidos}`.trim() : 'Sin cliente')}</td>
            <td>${escapeHtml(s.alias || '—')}</td>
            <td>${escapeHtml(s.plan_nombre || 'Sin plan')}</td>
            <td>${escapeHtml(s.nap_codigo || '—')}</td>
            <td><span class="status ${getStatusClass(s.estado_comercial)}">${escapeHtml(s.estado_comercial)}</span></td>
            <td>${escapeHtml(s.direccion_texto || '—')}</td>
        </tr>
    `).join('') : '<tr><td colspan="6">No hay servicios.</td></tr>';
    renderPagination('servicios-pagination', 'servicios', page, filtered.length, 'renderServiciosPage');
}

function toggleServicioForm(forceClose = false, reset = true) {
    const m = document.getElementById('servicio-modal');
    if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
    if (!forceClose && reset) {
        ['servicio-id', 'servicio-cliente-id', 'servicio-alias', 'servicio-direccion', 'servicio-latitud', 'servicio-longitud', 'servicio-nap'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        const elCliente = document.getElementById('servicio-cliente');
        if (elCliente) { elCliente.value = ''; elCliente.disabled = false; }
        const elPlan = document.getElementById('servicio-plan'); if (elPlan) elPlan.value = '1';
        const elEst = document.getElementById('servicio-estado'); if (elEst) elEst.value = 'pendiente';
    }
}

function abrirNuevoServicio(idCliente) {
    toggleServicioForm(false, true);
    populateServicioSelectores();
    document.getElementById('servicio-cliente-id').value = idCliente;
    const selCliente = document.getElementById('servicio-cliente');
    if (selCliente) { selCliente.value = idCliente; selCliente.disabled = true; }
}

async function guardarServicio() {
    const id = document.getElementById('servicio-id').value;
    const idCliente = document.getElementById('servicio-cliente').value;
    const payload = {
        id_cliente: idCliente,
        alias: document.getElementById('servicio-alias').value.trim(),
        direccion_texto: document.getElementById('servicio-direccion').value.trim(),
        latitud: normalizarCoordenada(document.getElementById('servicio-latitud').value),
        longitud: normalizarCoordenada(document.getElementById('servicio-longitud').value),
        id_naps: document.getElementById('servicio-nap').value,
        id_plan: document.getElementById('servicio-plan').value,
        estado_comercial: document.getElementById('servicio-estado').value
    };
    if (!idCliente) { alert('Cliente obligatorio'); return; }
    if (!payload.direccion_texto) { alert('Dirección obligatoria'); return; }
    if (id) payload.id_servicio = id;

    try {
        const res = await fetch('api_servicios.php', {
            method: id ? 'PUT' : 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') {
            toggleServicioForm(true);
            await loadServicios();
            await loadClientes(window.currentClienteSearch, 1);
            populateFinanzasSelectors();
            if (document.getElementById('servicios')?.classList.contains('active')) {
                renderServiciosPage(1);
            }
            const c = window.clientesCache.find(x => String(x.id_cliente) === String(idCliente));
            if (c) renderClientProfile(c);
        }
    } catch (e) { console.error(e); }
}

function editarServicio(id) {
    const s = window.serviciosCache.find(x => String(x.id_servicio) === String(id));
    if (!s) return;
    toggleServicioForm(false, false);
    populateServicioSelectores();
    document.getElementById('servicio-id').value = s.id_servicio;
    document.getElementById('servicio-cliente-id').value = s.id_cliente;
    const selCliente = document.getElementById('servicio-cliente');
    if (selCliente) { selCliente.value = s.id_cliente; selCliente.disabled = true; }
    document.getElementById('servicio-alias').value = s.alias || '';
    document.getElementById('servicio-direccion').value = s.direccion_texto || '';
    document.getElementById('servicio-latitud').value = s.latitud_instalacion || '';
    document.getElementById('servicio-longitud').value = s.longitud_instalacion || '';
    document.getElementById('servicio-nap').value = s.id_naps || '';
    document.getElementById('servicio-plan').value = s.id_plan || '1';
    document.getElementById('servicio-estado').value = s.estado_comercial || 'pendiente';
}

async function eliminarServicio(id) {
    if (!confirm('¿Eliminar servicio?')) return;
    const s = window.serviciosCache.find(x => String(x.id_servicio) === String(id));
    try {
        const res = await fetch(`api_servicios.php?id=${id}`, { method: 'DELETE', headers: buildSessionFetchHeaders() });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') {
            await loadServicios();
            await loadClientes(window.currentClienteSearch, 1);
            populateFinanzasSelectors();
            if (document.getElementById('servicios')?.classList.contains('active')) {
                renderServiciosPage(1);
            }
            if (s) {
                const c = window.clientesCache.find(x => String(x.id_cliente) === String(s.id_cliente));
                if (c) renderClientProfile(c);
            }
        }
    } catch (e) { console.error(e); }
}

// --- TAREAS ---
async function loadTareas() {
    try {
        const res = await fetch('api_tareas.php', { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success') { window.tareasCache = data.tareas || []; renderTareasPage(1); }
    } catch (e) { console.error(e); }
}

function renderTareasPage(page) {
    const tbody = document.getElementById('task-list');
    if (!tbody) return;
    const start = (page - 1) * window.pageSize;
    const vis = window.tareasCache.slice(start, start + window.pageSize);
    
    tbody.innerHTML = vis.length ? vis.map(t => `
        <tr data-module-row="tareas" data-id="${escapeHtml(t.id_tarea)}" onclick="selectRow('tareas', '${escapeHtml(t.id_tarea)}', this)">
            <td><strong>${escapeHtml(t.titulo)}</strong><br><small>${escapeHtml(t.descripcion)}</small></td>
            <td>${escapeHtml(t.nombres ? `${t.nombres} ${t.apellidos}`.trim() : 'Sin asignar')}</td>
            <td>${escapeHtml(t.creador || 'Sin asignar')}</td>
            <td>${escapeHtml(t.prioridad)}</td>
            <td><span class="status ${getStatusClass(t.estado)}">${escapeHtml(t.estado)}</span></td>
        </tr>
    `).join('') : '<tr><td colspan="5">No hay tareas.</td></tr>';
    renderPagination('tasks-pagination', 'tareas', page, window.tareasCache.length, 'renderTareasPage');
}

function toggleTaskForm(forceClose = false, reset = true) {
    const m = document.getElementById('task-modal');
    if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
    if (!forceClose && reset) {
        ['task-id', 'task-title', 'task-description', 'task-client', 'task-user'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        const elSt = document.getElementById('task-status'); if (elSt) elSt.value = 'Pendiente';
        const elPr = document.getElementById('task-priority'); if (elPr) elPr.value = 'Media';
    }
}

async function guardarTarea() {
    const id = document.getElementById('task-id').value;
    const payload = {
        titulo: document.getElementById('task-title').value.trim(),
        descripcion: document.getElementById('task-description').value.trim(),
        estado: document.getElementById('task-status').value,
        prioridad: document.getElementById('task-priority').value,
        id_cliente: document.getElementById('task-client').value,
        id_usuario_creador: document.getElementById('task-user').value
    };
    if (!payload.titulo) { alert('Título obligatorio'); return; }
    if (id) {
        payload.id_tarea = id;
        const tareaActual = window.tareasCache.find(t => String(t.id_tarea) === String(id));
        if (tareaActual) {
            payload.id_ticket = tareaActual.id_ticket || '';
            payload.id_servicio = tareaActual.id_servicio || '';
        }
    } else {
        payload.id_ticket = '';
        payload.id_servicio = '';
    }

    try {
        const res = await fetch('api_tareas.php', {
            method: id ? 'PUT' : 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') { toggleTaskForm(true); await loadTareas(); }
    } catch (e) { console.error(e); }
}

function editarTarea(id) {
    const t = window.tareasCache.find(x => String(x.id_tarea) === String(id));
    if (!t) return;
    document.getElementById('task-id').value = t.id_tarea;
    document.getElementById('task-title').value = t.titulo;
    document.getElementById('task-description').value = t.descripcion;
    document.getElementById('task-status').value = t.estado;
    document.getElementById('task-priority').value = t.prioridad;
    document.getElementById('task-client').value = t.id_cliente || '';
    document.getElementById('task-user').value = t.id_usuario_creador || '';
    toggleTaskForm(false, false);
}

async function eliminarTarea(id) {
    if (!confirm('¿Eliminar tarea?')) return;
    try {
        const res = await fetch(`api_tareas.php?id=${id}`, { method: 'DELETE', headers: buildSessionFetchHeaders() });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') await loadTareas();
    } catch (e) { console.error(e); }
}

// --- TICKETS ---
async function loadTickets() {
    try {
        const res = await fetch('api_tickets.php', { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success') { window.ticketsCache = data.tickets || []; renderTicketsPage(1); }
    } catch (e) { console.error(e); }
}

function setTicketsFilter(filter, el) {
    document.querySelectorAll('#ticket-filters .filter-pill').forEach(b => b.classList.remove('active'));
    if (el) el.classList.add('active');
    window.currentTicketsFilter = filter;
    renderTicketsPage(1);
}

function renderTicketsPage(page) {
    const tbody = document.getElementById('ticket-list');
    if (!tbody) return;
    
    const f = window.currentTicketsFilter;
    const filtered = window.ticketsCache.filter(t => f === 'Todos' || String(t.estado).toLowerCase() === f.toLowerCase());
    
    const start = (page - 1) * window.pageSize;
    const vis = filtered.slice(start, start + window.pageSize);
    
    tbody.innerHTML = vis.length ? vis.map(t => `
        <tr data-module-row="tickets" data-id="${escapeHtml(t.id_ticket)}" onclick="selectRow('tickets', '${escapeHtml(t.id_ticket)}', this)">
            <td><strong>${escapeHtml(t.asunto)}</strong><br><small>${escapeHtml(t.descripcion)}</small></td>
            <td>${escapeHtml(t.nombres ? `${t.nombres} ${t.apellidos}`.trim() : 'Sin asignar')}</td>
            <td>${escapeHtml(t.creador || 'Sin asignar')}</td>
            <td>${escapeHtml(t.prioridad)}</td>
            <td><span class="status ${getStatusClass(t.estado)}">${escapeHtml(t.estado)}</span></td>
        </tr>
    `).join('') : '<tr><td colspan="5">No hay tickets.</td></tr>';
    renderPagination('tickets-pagination', 'tickets', page, filtered.length, 'renderTicketsPage');
}

function toggleTicketForm(forceClose = false, reset = true) {
    const m = document.getElementById('ticket-modal');
    if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
    if (!forceClose && reset) {
        ['ticket-id', 'ticket-subject', 'ticket-description', 'ticket-client', 'ticket-user'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        const elSt = document.getElementById('ticket-status'); if (elSt) elSt.value = 'Abierto';
        const elPr = document.getElementById('ticket-priority'); if (elPr) elPr.value = 'Media';
    }
}

async function guardarTicket() {
    const id = document.getElementById('ticket-id').value;
    const payload = {
        asunto: document.getElementById('ticket-subject').value.trim(),
        descripcion: document.getElementById('ticket-description').value.trim(),
        estado: document.getElementById('ticket-status').value,
        prioridad: document.getElementById('ticket-priority').value,
        id_cliente: document.getElementById('ticket-client').value,
        id_usuario_creador: document.getElementById('ticket-user').value,
        origen: 'backoffice'
    };
    if (!payload.asunto) { alert('Asunto obligatorio'); return; }
    if (id) {
        payload.id_ticket = id;
        const ticketActual = window.ticketsCache.find(t => String(t.id_ticket) === String(id));
        if (ticketActual && ticketActual.origen) {
            payload.origen = ticketActual.origen;
        }
    }

    try {
        const res = await fetch('api_tickets.php', {
            method: id ? 'PUT' : 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') { toggleTicketForm(true); await loadTickets(); }
    } catch (e) { console.error(e); }
}

function editarTicket(id) {
    const t = window.ticketsCache.find(x => String(x.id_ticket) === String(id));
    if (!t) return;
    document.getElementById('ticket-id').value = t.id_ticket;
    document.getElementById('ticket-subject').value = t.asunto;
    document.getElementById('ticket-description').value = t.descripcion;
    document.getElementById('ticket-status').value = t.estado;
    document.getElementById('ticket-priority').value = t.prioridad;
    document.getElementById('ticket-client').value = t.id_cliente || '';
    document.getElementById('ticket-user').value = t.id_usuario_creador || '';
    toggleTicketForm(false, false);
}

async function eliminarTicket(id) {
    if (!confirm('¿Eliminar ticket?')) return;
    try {
        const res = await fetch(`api_tickets.php?id=${id}`, { method: 'DELETE', headers: buildSessionFetchHeaders() });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') await loadTickets();
    } catch (e) { console.error(e); }
}

function toggleCloseTicketModal(forceClose = false, reset = true) {
    const m = document.getElementById('close-ticket-modal');
    if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
    if (!forceClose && reset) {
        document.getElementById('close-ticket-id').value = '';
        document.getElementById('close-ticket-details').value = '';
    }
}

function abrirCierreTicket(id) {
    const t = window.ticketsCache.find(x => String(x.id_ticket) === String(id));
    if (!t) return;
    document.getElementById('close-ticket-id').value = t.id_ticket;
    document.getElementById('close-ticket-details').value = '';
    toggleCloseTicketModal(false, false);
}

async function confirmarCierreTicket() {
    const id = document.getElementById('close-ticket-id').value;
    const details = document.getElementById('close-ticket-details').value.trim();
    const t = window.ticketsCache.find(x => String(x.id_ticket) === String(id));
    if (!t) return;

    const desc = [t.descripcion, details ? `Cierre: ${details}` : ''].filter(Boolean).join('\n\n');
    try {
        const res = await fetch('api_tickets.php', {
            method: 'PUT', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ id_ticket: id, asunto: t.asunto, descripcion: desc, estado: 'Cerrado', prioridad: t.prioridad, id_cliente: t.id_cliente, id_usuario_creador: t.id_usuario_creador })
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') { toggleCloseTicketModal(true); await loadTickets(); }
    } catch (e) { console.error(e); }
}

function exportClosedTicketsPdf() {
    const closed = window.ticketsCache.filter(t => String(t.estado).toLowerCase() === 'cerrado');
    if (!closed.length) { alert('No hay tickets cerrados.'); return; }
    
    const rows = closed.map(t => `<tr><td>${escapeHtml(t.asunto)}</td><td>${escapeHtml(t.nombres ? `${t.nombres} ${t.apellidos}` : '')}</td><td>${escapeHtml(t.creador)}</td><td>${escapeHtml(t.descripcion)}</td></tr>`).join('');
    const win = window.open('', '_blank');
    if (!win) { alert('Pop-up bloqueado'); return; }
    win.document.write(`<html><head><title>Tickets Cerrados</title><style>table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ccc;padding:8px;}</style></head><body><h2>Tickets Cerrados</h2><table><tr><th>Asunto</th><th>Cliente</th><th>Creador</th><th>Detalle</th></tr>${rows}</table></body></html>`);
    win.document.close();
    setTimeout(() => win.print(), 250);
}

// --- NODOS ---
async function loadNodos() {
    try {
        const res = await fetch('api_nodos.php', { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success') { window.nodosCache = data.nodos || []; renderNodosPage(1); populateInfraSelectors(); }
    } catch (e) { console.error(e); }
}

function renderNodosPage(page) {
    const tbody = document.getElementById('nodos-list');
    if (!tbody) return;

    // Bug 4: filtrado por búsqueda antes de paginar.
    const filtered = filterBySearch(window.nodosCache, ['nombre', 'ubicacion'], 'nodos-search');

    const start = (page - 1) * window.pageSize;
    const vis = filtered.slice(start, start + window.pageSize);
    
    tbody.innerHTML = vis.length ? vis.map(n => `
        <tr data-module-row="nodos" data-id="${escapeHtml(n.id_nodo)}" onclick="selectRow('nodos', '${escapeHtml(n.id_nodo)}', this)">
            <td><strong>${escapeHtml(n.nombre)}</strong></td>
            <td>${escapeHtml(n.ubicacion)}</td>
            <td>${escapeHtml(n.latitud)}, ${escapeHtml(n.longitud)}</td>
            <td><span class="status ${getStatusClass(n.estado)}">${escapeHtml(n.estado)}</span></td>
            <td>${escapeHtml(n.total_olts || 0)}</td>
        </tr>
    `).join('') : '<tr><td colspan="5">No hay nodos.</td></tr>';
    renderPagination('nodos-pagination', 'nodos', page, filtered.length, 'renderNodosPage');
}

function toggleNodoForm(forceClose = false, reset = true) {
    const m = document.getElementById('nodo-modal');
    if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
    if (!forceClose && reset) {
        ['nodo-id', 'nodo-nombre', 'nodo-ubicacion', 'nodo-latitud', 'nodo-longitud'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        const elEst = document.getElementById('nodo-estado'); if (elEst) elEst.value = 'activo';
    }
}

async function guardarNodo() {
    const id = document.getElementById('nodo-id').value;
    const payload = {
        nombre: document.getElementById('nodo-nombre').value.trim(),
        ubicacion: document.getElementById('nodo-ubicacion').value.trim(),
        latitud: normalizarCoordenada(document.getElementById('nodo-latitud').value),
        longitud: normalizarCoordenada(document.getElementById('nodo-longitud').value),
        estado: document.getElementById('nodo-estado').value
    };
    if (!payload.nombre || !payload.latitud || !payload.longitud) { alert('Faltan datos'); return; }
    if (id) payload.id_nodo = id;

    try {
        const res = await fetch('api_nodos.php', {
            method: id ? 'PUT' : 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') { toggleNodoForm(true); await loadNodos(); }
    } catch (e) { console.error(e); }
}

function editarNodo(id) {
    const n = window.nodosCache.find(x => String(x.id_nodo) === String(id));
    if (!n) return;
    document.getElementById('nodo-id').value = n.id_nodo;
    document.getElementById('nodo-nombre').value = n.nombre;
    document.getElementById('nodo-ubicacion').value = n.ubicacion;
    document.getElementById('nodo-latitud').value = n.latitud;
    document.getElementById('nodo-longitud').value = n.longitud;
    document.getElementById('nodo-estado').value = n.estado;
    toggleNodoForm(false, false);
}

async function eliminarNodo(id) {
    if (!confirm('¿Eliminar nodo?')) return;
    try {
        const res = await fetch(`api_nodos.php?id=${id}`, { method: 'DELETE', headers: buildSessionFetchHeaders() });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') await loadNodos();
    } catch (e) { console.error(e); }
}

// --- OLTs ---
async function loadOlts() {
    try {
        const res = await fetch('api_olts.php', { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success') { window.oltsCache = data.olts || []; renderOltsPage(1); populateInfraSelectors(); }
    } catch (e) { console.error(e); }
}

function renderOltsPage(page) {
    const tbody = document.getElementById('olts-list');
    if (!tbody) return;

    // Bug 4: filtrado por búsqueda antes de paginar.
    const filtered = filterBySearch(window.oltsCache, ['codigo', 'marca_modelo', 'ip_gestion', 'nombre_nodo'], 'olts-search');

    const start = (page - 1) * window.pageSize;
    const vis = filtered.slice(start, start + window.pageSize);
    
    tbody.innerHTML = vis.length ? vis.map(o => `
        <tr data-module-row="olts" data-id="${escapeHtml(o.id_olt)}" onclick="selectRow('olts', '${escapeHtml(o.id_olt)}', this)">
            <td><strong>${escapeHtml(o.codigo)}</strong></td>
            <td>${escapeHtml(o.marca_modelo)}</td>
            <td>${escapeHtml(o.puertos_pon)}</td>
            <td>${escapeHtml(o.ip_gestion)}</td>
            <td>${escapeHtml(o.nombre_nodo)}</td>
            <td>${escapeHtml(o.total_naps || 0)}</td>
        </tr>
    `).join('') : '<tr><td colspan="6">No hay OLTs.</td></tr>';
    renderPagination('olts-pagination', 'olts', page, filtered.length, 'renderOltsPage');
}

function toggleOltForm(forceClose = false, reset = true) {
    const m = document.getElementById('olt-modal');
    if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
    if (!forceClose && reset) {
        ['olt-id', 'olt-codigo', 'olt-marca', 'olt-puertos', 'olt-ip', 'olt-nodo'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
    }
}

async function guardarOlt() {
    const id = document.getElementById('olt-id').value;
    const payload = {
        codigo: document.getElementById('olt-codigo').value.trim(),
        marca_modelo: document.getElementById('olt-marca').value.trim(),
        puertos_pon: document.getElementById('olt-puertos').value,
        ip_gestion: document.getElementById('olt-ip').value.trim(),
        id_nodos: document.getElementById('olt-nodo').value
    };
    if (!payload.marca_modelo || !payload.id_nodos) { alert('Faltan datos'); return; }
    if (id) payload.id_olt = id;

    try {
        const res = await fetch('api_olts.php', {
            method: id ? 'PUT' : 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') { toggleOltForm(true); await loadOlts(); }
    } catch (e) { console.error(e); }
}

function editarOlt(id) {
    const o = window.oltsCache.find(x => String(x.id_olt) === String(id));
    if (!o) return;
    document.getElementById('olt-id').value = o.id_olt;
    document.getElementById('olt-codigo').value = o.codigo;
    document.getElementById('olt-marca').value = o.marca_modelo;
    document.getElementById('olt-puertos').value = o.puertos_pon;
    document.getElementById('olt-ip').value = o.ip_gestion;
    document.getElementById('olt-nodo').value = o.id_nodos;
    toggleOltForm(false, false);
}

async function eliminarOlt(id) {
    if (!confirm('¿Eliminar OLT?')) return;
    try {
        const res = await fetch(`api_olts.php?id=${id}`, { method: 'DELETE', headers: buildSessionFetchHeaders() });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') await loadOlts();
    } catch (e) { console.error(e); }
}

// --- NAPs ---
async function loadNaps() {
    try {
        const res = await fetch('api_naps.php', { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success') { window.napsCache = data.naps || []; renderNapsPage(1); populateEquipoSelectores(); populateServicioSelectores(); }
    } catch (e) { console.error(e); }
}

function renderNapsPage(page) {
    const tbody = document.getElementById('naps-list');
    if (!tbody) return;

    // Bug 4: filtrado por búsqueda antes de paginar.
    const filtered = filterBySearch(window.napsCache, ['codigo', 'ubicacion_fisica', 'olt_codigo', 'olt_nombre', 'nodo_nombre'], 'naps-search');

    const start = (page - 1) * window.pageSize;
    const vis = filtered.slice(start, start + window.pageSize);
    
    tbody.innerHTML = vis.length ? vis.map(n => `
        <tr data-module-row="naps" data-id="${escapeHtml(n.id_nap)}" onclick="selectRow('naps', '${escapeHtml(n.id_nap)}', this)">
            <td><strong>${escapeHtml(n.codigo)}</strong></td>
            <td>${escapeHtml(n.cantidad_puertos_max)}</td>
            <td>${escapeHtml(n.ubicacion_fisica)}</td>
            <td>${escapeHtml(n.olt_codigo || n.olt_nombre)}</td>
            <td>${escapeHtml(n.nodo_nombre)}</td>
            <td><button onclick="event.stopPropagation(); verClientesNap(${n.id_nap})" style="width:auto;padding:4px 8px;">${escapeHtml(n.total_clientes || 0)} clientes</button></td>
        </tr>
    `).join('') : '<tr><td colspan="6">No hay NAPs.</td></tr>';
    renderPagination('naps-pagination', 'naps', page, filtered.length, 'renderNapsPage');
}

function verClientesNap(id) {
    const n = window.napsCache.find(x => String(x.id_nap) === String(id));
    if (!n) return;
    if (!Number(n.total_clientes)) { alert('Sin clientes conectados.'); return; }
    alert(`Clientes en ${n.codigo}:\n\n${n.clientes_conectados || 'Sin detalle'}`);
}

function toggleNapForm(forceClose = false, reset = true) {
    const m = document.getElementById('nap-modal');
    if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
    if (!forceClose && reset) {
        ['nap-id', 'nap-codigo', 'nap-puertos', 'nap-ubicacion', 'nap-latitud', 'nap-longitud', 'nap-olt'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
    }
}

async function guardarNap() {
    const id = document.getElementById('nap-id').value;
    const payload = {
        codigo: document.getElementById('nap-codigo').value.trim(),
        cantidad_puertos_max: document.getElementById('nap-puertos').value,
        ubicacion_fisica: document.getElementById('nap-ubicacion').value.trim(),
        latitud: normalizarCoordenada(document.getElementById('nap-latitud').value),
        longitud: normalizarCoordenada(document.getElementById('nap-longitud').value),
        id_olts: document.getElementById('nap-olt').value
    };
    if (!payload.codigo || !payload.latitud || !payload.longitud || !payload.id_olts) { alert('Faltan datos'); return; }
    if (id) payload.id_nap = id;

    try {
        const res = await fetch('api_naps.php', {
            method: id ? 'PUT' : 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') { toggleNapForm(true); await loadNaps(); }
    } catch (e) { console.error(e); }
}

function editarNap(id) {
    const n = window.napsCache.find(x => String(x.id_nap) === String(id));
    if (!n) return;
    document.getElementById('nap-id').value = n.id_nap;
    document.getElementById('nap-codigo').value = n.codigo;
    document.getElementById('nap-puertos').value = n.cantidad_puertos_max;
    document.getElementById('nap-ubicacion').value = n.ubicacion_fisica;
    document.getElementById('nap-latitud').value = n.latitud;
    document.getElementById('nap-longitud').value = n.longitud;
    document.getElementById('nap-olt').value = n.id_olts;
    toggleNapForm(false, false);
}

async function eliminarNap(id) {
    if (!confirm('¿Eliminar NAP?')) return;
    try {
        const res = await fetch(`api_naps.php?id=${id}`, { method: 'DELETE', headers: buildSessionFetchHeaders() });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') await loadNaps();
    } catch (e) { console.error(e); }
}

function closeNapDetailModal() {
    const m = document.getElementById('nap-detail-modal');
    if (m) m.style.display = 'none';
}

// --- EQUIPOS ---
async function loadEquipos() {
    try {
        const res = await fetch('api_equipos.php', { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success') {
            window.equiposCache = data.equipos || [];
            renderEquiposPage(1);
            populateEquipoSelectores();
        }
    } catch (e) { console.error(e); }
}

function renderEquiposPage(page) {
    const tbody = document.getElementById('equipos-list');
    if (!tbody) return;

    // Bug 4: filtrado por búsqueda antes de paginar.
    const filtered = filterBySearch(
        window.equiposCache,
        ['tipo', 'marca', 'modelo', 'direccion_mac', 'nap_codigo', 'estado_fisico', 'propiedad', 'nombres', 'apellidos'],
        'equipos-search'
    );

    const start = (page - 1) * window.pageSize;
    const vis = filtered.slice(start, start + window.pageSize);
    
    tbody.innerHTML = vis.length ? vis.map(e => `
        <tr data-module-row="equipos" data-id="${escapeHtml(e.id_equipo)}" onclick="selectRow('equipos', '${escapeHtml(e.id_equipo)}', this)">
            <td><strong>${escapeHtml(e.tipo)}</strong></td>
            <td>${escapeHtml([e.marca, e.modelo].filter(Boolean).join(' / '))}</td>
            <td>${escapeHtml(e.direccion_mac)}</td>
            <td>${escapeHtml(e.nap_codigo)}</td>
            <td>${escapeHtml(e.num_puerto_nap)}</td>
            <td><span class="status ${getStatusClass(e.estado_fisico)}">${escapeHtml(e.estado_fisico)}</span></td>
            <td>${escapeHtml(e.propiedad)}</td>
            <td>${escapeHtml(e.id_servicio ? `${e.nombres} ${e.apellidos}`.trim() : 'Sin asignar')}</td>
        </tr>
    `).join('') : '<tr><td colspan="8">No hay equipos.</td></tr>';
    renderPagination('equipos-pagination', 'equipos', page, filtered.length, 'renderEquiposPage');
}

function toggleEquipoForm(forceClose = false, reset = true) {
    const m = document.getElementById('equipo-modal');
    if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
    if (!forceClose && reset) {
        ['equipo-id', 'equipo-marca', 'equipo-modelo', 'equipo-mac', 'equipo-puerto', 'equipo-nap', 'equipo-servicio'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        const elT = document.getElementById('equipo-tipo'); if (elT) elT.value = 'ONU';
        const elE = document.getElementById('equipo-estado'); if (elE) elE.value = 'stock';
        const elP = document.getElementById('equipo-propiedad'); if (elP) elP.value = 'empresa';
        populateEquipoSelectores();
    }
}

async function guardarEquipo() {
    const id = document.getElementById('equipo-id').value;
    const payload = {
        tipo: document.getElementById('equipo-tipo').value,
        marca: document.getElementById('equipo-marca').value.trim(),
        modelo: document.getElementById('equipo-modelo').value.trim(),
        direccion_mac: document.getElementById('equipo-mac').value.trim().toUpperCase(),
        num_puerto_nap: document.getElementById('equipo-puerto').value,
        estado_fisico: document.getElementById('equipo-estado').value,
        propiedad: document.getElementById('equipo-propiedad').value,
        id_naps: document.getElementById('equipo-nap').value,
        id_servicio: document.getElementById('equipo-servicio').value || ''
    };
    if (!payload.tipo || !payload.direccion_mac || !payload.num_puerto_nap || !payload.id_naps) { alert('Faltan datos'); return; }
    if (id) payload.id_equipo = id;

    try {
        const res = await fetch('api_equipos.php', {
            method: id ? 'PUT' : 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') { toggleEquipoForm(true); await loadEquipos(); }
    } catch (e) { console.error(e); }
}

function editarEquipo(id) {
    const e = window.equiposCache.find(x => String(x.id_equipo) === String(id));
    if (!e) return;
    document.getElementById('equipo-id').value = e.id_equipo;
    document.getElementById('equipo-tipo').value = e.tipo;
    document.getElementById('equipo-marca').value = e.marca;
    document.getElementById('equipo-modelo').value = e.modelo;
    document.getElementById('equipo-mac').value = e.direccion_mac;
    document.getElementById('equipo-puerto').value = e.num_puerto_nap;
    document.getElementById('equipo-estado').value = e.estado_fisico;
    document.getElementById('equipo-propiedad').value = e.propiedad;
    document.getElementById('equipo-nap').value = e.id_naps;
    document.getElementById('equipo-servicio').value = e.id_servicio || '';
    toggleEquipoForm(false, false);
}

async function eliminarEquipo(id) {
    if (!confirm('¿Eliminar equipo?')) return;
    try {
        const res = await fetch(`api_equipos.php?id=${id}`, { method: 'DELETE', headers: buildSessionFetchHeaders() });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') await loadEquipos();
    } catch (e) { console.error(e); }
}

// --- FACTURAS ---
async function loadFacturas() {
    try {
        await fetch('api_facturas_vencer.php', {
            method: 'POST',
            headers: buildSessionFetchHeaders()
        });
        const res = await fetch('api_facturas.php', { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success') { window.facturasCache = data.facturas || []; renderFacturasPage(1); }
    } catch (e) { console.error(e); }
}

function renderFacturasPage(page) {
    const tbody = document.getElementById('facturas-list');
    if (!tbody) return;

    // Bug 4: filtrado por búsqueda antes de paginar.
    const filtered = filterBySearch(window.facturasCache, ['nombres', 'apellidos', 'periodo', 'monto', 'estado'], 'facturas-search');

    const start = (page - 1) * window.pageSize;
    const vis = filtered.slice(start, start + window.pageSize);
    
    tbody.innerHTML = vis.length ? vis.map(f => {
        const montoDisplay = (f.moneda === 'VES' && f.tasa_cambio)
            ? `Bs. ${(Number(f.monto) * Number(f.tasa_cambio)).toFixed(2)}<br><small style="color:#64748b;">(USD ${Number(f.monto).toFixed(2)})</small>`
            : `$${Number(f.monto).toFixed(2)}`;
        return `
        <tr data-module-row="facturas" data-id="${escapeHtml(f.id_factura)}" onclick="selectRow('facturas', '${escapeHtml(f.id_factura)}', this)">
            <td>${escapeHtml(f.nombres ? `${f.nombres} ${f.apellidos}`.trim() : 'Sin cliente')}</td>
            <td>${escapeHtml(f.periodo)}</td>
            <td>${montoDisplay}</td>
            <td>$${Number(f.total_pagado || 0).toFixed(2)}</td>
            <td>${escapeHtml(f.fecha_vencimiento)}</td>
            <td><span class="status ${getStatusClass(f.estado)}">${escapeHtml(f.estado)}</span></td>
        </tr>
        `;
    }).join('') : '<tr><td colspan="6">No hay facturas.</td></tr>';
    renderPagination('facturas-pagination', 'facturas', page, filtered.length, 'renderFacturasPage');
}

function toggleFacturaForm(forceClose = false, reset = true) {
    const m = document.getElementById('factura-modal');
    if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
    if (!forceClose && reset) {
        ['factura-id', 'factura-servicio', 'factura-periodo', 'factura-monto', 'factura-fecha-emision', 'factura-fecha-vencimiento', 'factura-moneda', 'factura-tasa-cambio'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        const elEst = document.getElementById('factura-estado'); if (elEst) elEst.value = 'pendiente';
        const eq = document.getElementById('factura-equivalente-bs');
        if (eq) eq.textContent = '';
    }
}

function updateFacturaEquivalenteBs() {
    const montoInput = document.getElementById('factura-monto');
    const monedaInput = document.getElementById('factura-moneda');
    const tasaInput = document.getElementById('factura-tasa-cambio');
    const target = document.getElementById('factura-equivalente-bs');
    if (!target) return;

    const monto = montoInput ? Number(montoInput.value) : 0;
    const moneda = monedaInput ? monedaInput.value : 'USD';
    const tasa = tasaInput ? Number(tasaInput.value) : 0;

    if (moneda === 'VES' && monto > 0 && tasa > 0) {
        target.textContent = `Bs. ${(monto * tasa).toFixed(2)}`;
    } else {
        target.textContent = '';
    }
}

async function guardarFactura() {
    const id = document.getElementById('factura-id').value;
    const payload = {
        id_servicio: document.getElementById('factura-servicio').value,
        periodo: document.getElementById('factura-periodo').value.trim(),
        monto: document.getElementById('factura-monto').value,
        moneda: document.getElementById('factura-moneda')?.value || 'USD',
        tasa_cambio: document.getElementById('factura-tasa-cambio')?.value || '',
        fecha_emision: document.getElementById('factura-fecha-emision').value,
        fecha_vencimiento: document.getElementById('factura-fecha-vencimiento').value,
        estado: document.getElementById('factura-estado').value
    };
    if (!payload.id_servicio || !payload.periodo || !payload.monto || !payload.fecha_emision || !payload.fecha_vencimiento) { alert('Faltan datos'); return; }
    if (id) payload.id_factura = id;

    try {
        const res = await fetch('api_facturas.php', {
            method: id ? 'PUT' : 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') { toggleFacturaForm(true); await loadFacturas(); }
    } catch (e) { console.error(e); }
}

function editarFactura(id) {
    const f = window.facturasCache.find(x => String(x.id_factura) === String(id));
    if (!f) return;
    document.getElementById('factura-id').value = f.id_factura;
    document.getElementById('factura-servicio').value = f.id_servicio;
    document.getElementById('factura-periodo').value = f.periodo;
    document.getElementById('factura-monto').value = f.monto;
    document.getElementById('factura-moneda').value = f.moneda || 'USD';
    document.getElementById('factura-tasa-cambio').value = f.tasa_cambio || '';
    document.getElementById('factura-fecha-emision').value = f.fecha_emision;
    document.getElementById('factura-fecha-vencimiento').value = f.fecha_vencimiento;
    document.getElementById('factura-estado').value = f.estado;
    toggleFacturaForm(false, false);
    updateFacturaEquivalenteBs();
}

async function eliminarFactura(id) {
    if (!confirm('¿Eliminar factura?')) return;
    try {
        const res = await fetch(`api_facturas.php?id=${id}`, { method: 'DELETE', headers: buildSessionFetchHeaders() });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') await loadFacturas();
    } catch (e) { console.error(e); }
}

// --- PAGOS ---
async function loadPagos() {
    try {
        const res = await fetch('api_pagos.php', { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success') { window.pagosCache = data.pagos || []; renderPagosPage(1); }
    } catch (e) { console.error(e); }
}

async function loadMetodosPago() {
    try {
        const res = await fetch('api_metodos_pago.php', { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success') {
            window.metodosPagoCache = data.metodos || [];
            populatePagoMetodos();
        }
    } catch (e) { console.error(e); }
}

function renderPagosPage(page) {
    const tbody = document.getElementById('pagos-list');
    if (!tbody) return;

    // Bug 4: filtrado por búsqueda antes de paginar.
    const filtered = filterBySearch(window.pagosCache, ['nombres', 'apellidos', 'factura_periodo', 'metodo_nombre', 'monto', 'estado'], 'pagos-search');

    const start = (page - 1) * window.pageSize;
    const vis = filtered.slice(start, start + window.pageSize);
    
    tbody.innerHTML = vis.length ? vis.map(p => {
        const montoDisplay = (p.moneda_pago === 'VES' && p.tasa_cambio)
            ? `Bs. ${(Number(p.monto) * Number(p.tasa_cambio)).toFixed(2)}<br><small style="color:#64748b;">(USD ${Number(p.monto).toFixed(2)})</small>`
            : `$${Number(p.monto).toFixed(2)}`;
        return `
        <tr data-module-row="pagos" data-id="${escapeHtml(p.id_pago)}" onclick="selectRow('pagos', '${escapeHtml(p.id_pago)}', this)">
            <td>${escapeHtml(p.nombres ? `${p.nombres} ${p.apellidos}`.trim() : 'Sin cliente')}</td>
            <td>${escapeHtml(p.factura_periodo || 'A cuenta')}</td>
            <td>${montoDisplay}</td>
            <td>${escapeHtml(p.fecha_pago)}</td>
            <td>${escapeHtml(p.metodo_nombre || '-')}</td>
            <td><span class="status ${getStatusClass(p.estado)}">${escapeHtml(p.estado)}</span></td>
        </tr>
        `;
    }).join('') : '<tr><td colspan="6">No hay pagos.</td></tr>';
    renderPagination('pagos-pagination', 'pagos', page, filtered.length, 'renderPagosPage');
}

function populatePagoFacturaOptions() {
    const srv = document.getElementById('pago-servicio');
    const fac = document.getElementById('pago-factura');
    if (!srv || !fac) return;
    const idSrv = srv.value;
    const prev = fac.value;
    const pagoId = document.getElementById('pago-id')?.value;
    const pagoActual = pagoId ? window.pagosCache.find(p => String(p.id_pago) === String(pagoId)) : null;
    const facturaActualId = pagoActual && pagoActual.id_factura ? String(pagoActual.id_factura) : null;

    const facturas = window.facturasCache.filter(f =>
        String(f.id_servicio) === String(idSrv) &&
        (String(f.id_factura) === facturaActualId || (f.estado !== 'pagada' && f.estado !== 'anulada'))
    );
    fac.innerHTML = '<option value="">Sin factura asociada (a cuenta)</option>' + facturas.map(f => `<option value="${f.id_factura}">${escapeHtml(f.periodo)} - $${Number(f.monto).toFixed(2)} (${escapeHtml(f.estado)})</option>`).join('');
    if (prev) fac.value = prev;
}

function togglePagoForm(forceClose = false, reset = true) {
    const m = document.getElementById('pago-modal');
    if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
    if (!forceClose && reset) {
        ['pago-id', 'pago-servicio', 'pago-monto', 'pago-fecha', 'pago-metodo', 'pago-referencia', 'pago-moneda', 'pago-tasa-cambio'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        populatePagoFacturaOptions();
    }
}

async function guardarPago() {
    const id = document.getElementById('pago-id').value;
    const srv = document.getElementById('pago-servicio');
    const opt = srv ? srv.options[srv.selectedIndex] : null;
    const idCliente = opt ? opt.getAttribute('data-cliente') : '';

    const payload = {
        id_servicio: srv ? srv.value : '',
        id_cliente: idCliente,
        id_factura: document.getElementById('pago-factura').value,
        monto: document.getElementById('pago-monto').value,
        fecha_pago: toMysqlDatetime(document.getElementById('pago-fecha').value),
        moneda_pago: document.getElementById('pago-moneda')?.value || 'USD',
        tasa_cambio: document.getElementById('pago-tasa-cambio')?.value || '',
        id_metodo: document.getElementById('pago-metodo').value,
        referencia_bancaria: document.getElementById('pago-referencia').value.trim(),
        origen: 'backoffice'
    };
    if (!payload.id_servicio || !payload.id_cliente || !payload.monto || !payload.fecha_pago) { alert('Faltan datos'); return; }
    if (id) payload.id_pago = id;

    try {
        const res = await fetch('api_pagos.php', {
            method: id ? 'PUT' : 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') { togglePagoForm(true); await loadFacturas(); await loadPagos(); }
    } catch (e) { console.error(e); }
}

function editarPago(id) {
    const p = window.pagosCache.find(x => String(x.id_pago) === String(id));
    if (!p) return;
    document.getElementById('pago-id').value = p.id_pago;
    const srv = document.getElementById('pago-servicio');
    if (srv) { srv.value = p.id_servicio; populatePagoFacturaOptions(); }
    document.getElementById('pago-factura').value = p.id_factura || '';
    document.getElementById('pago-monto').value = p.monto;
    document.getElementById('pago-fecha').value = toDatetimeLocalValue(p.fecha_pago);
    document.getElementById('pago-metodo').value = p.id_metodo || '';
    document.getElementById('pago-referencia').value = p.referencia_bancaria || '';
    document.getElementById('pago-moneda').value = p.moneda_pago || 'USD';
    document.getElementById('pago-tasa-cambio').value = p.tasa_cambio || '';
    togglePagoForm(false, false);
}

async function actualizarEstadoPago(id, estado) {
    if (!confirm(`¿Deseas ${estado === 'validado' ? 'validar' : 'rechazar'} este pago?`)) return;
    try {
        const res = await fetch('api_pagos.php', {
            method: 'PUT', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ id_pago: id, estado })
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') { await loadFacturas(); await loadPagos(); }
    } catch (e) { console.error(e); }
}

async function eliminarPago(id) {
    if (!confirm('¿Eliminar pago?')) return;
    try {
        const res = await fetch(`api_pagos.php?id=${id}`, { method: 'DELETE', headers: buildSessionFetchHeaders() });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') { await loadFacturas(); await loadPagos(); }
    } catch (e) { console.error(e); }
}

// --- PLANES ---
async function loadPlanes() {
    try {
        const res = await fetch('api_planes.php', { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success') { window.planesCache = data.planes || []; renderPlanesPage(1); }
    } catch (e) { console.error(e); }
}

function renderPlanesPage(page) {
    const tbody = document.getElementById('planes-list');
    if (!tbody) return;

    // Bug 4: filtrado por búsqueda antes de paginar.
    const filtered = filterBySearch(window.planesCache, ['nombre', 'velocidad_bajada_mbps', 'velocidad_subida_mbps', 'tecnologia', 'moneda'], 'planes-search');

    const start = (page - 1) * window.pageSize;
    const vis = filtered.slice(start, start + window.pageSize);
    
    tbody.innerHTML = vis.length ? vis.map(p => {
        const velDisplay = (p.velocidad_bajada_mbps || p.velocidad_subida_mbps)
            ? `${p.velocidad_bajada_mbps || '?'}/${p.velocidad_subida_mbps || '?'} Mbps`
            : '—';
        return `
        <tr data-module-row="planes" data-id="${escapeHtml(p.id_plan)}" onclick="selectRow('planes', '${escapeHtml(p.id_plan)}', this)">
            <td><strong>${escapeHtml(p.nombre)}</strong></td>
            <td>${velDisplay}</td>
            <td>$${Number(p.precio_mensual).toFixed(2)}</td>
            <td>${escapeHtml(p.moneda)}</td>
            <td>${escapeHtml(p.tecnologia || 'FTTH')}</td>
            <td>${escapeHtml(p.total_servicios || 0)}</td>
        </tr>
        `;
    }).join('') : '<tr><td colspan="6">No hay planes.</td></tr>';
    renderPagination('planes-pagination', 'planes', page, filtered.length, 'renderPlanesPage');
}

function togglePlanForm(forceClose = false, reset = true) {
    const m = document.getElementById('plan-modal');
    if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
    if (!forceClose && reset) {
        ['plan-id', 'plan-nombre', 'plan-velocidad-bajada', 'plan-velocidad-subida', 'plan-precio'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        const elMon = document.getElementById('plan-moneda'); if (elMon) elMon.value = 'USD';
        const elTec = document.getElementById('plan-tecnologia'); if (elTec) elTec.value = 'FTTH';
    }
}

async function guardarPlan() {
    const id = document.getElementById('plan-id').value;
    const payload = {
        nombre: document.getElementById('plan-nombre').value.trim(),
        velocidad_bajada_mbps: document.getElementById('plan-velocidad-bajada').value,
        velocidad_subida_mbps: document.getElementById('plan-velocidad-subida').value,
        tecnologia: document.getElementById('plan-tecnologia').value,
        precio_mensual: document.getElementById('plan-precio').value,
        moneda: document.getElementById('plan-moneda').value.trim() || 'USD'
    };
    if (!payload.nombre || !payload.velocidad_bajada_mbps || !payload.precio_mensual) { alert('Faltan datos'); return; }
    if (id) payload.id_plan = id;

    try {
        const res = await fetch('api_planes.php', {
            method: id ? 'PUT' : 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') { togglePlanForm(true); await loadPlanes(); }
    } catch (e) { console.error(e); }
}

function editarPlan(id) {
    const p = window.planesCache.find(x => String(x.id_plan) === String(id));
    if (!p) return;
    document.getElementById('plan-id').value = p.id_plan;
    document.getElementById('plan-nombre').value = p.nombre;
    document.getElementById('plan-velocidad-bajada').value = p.velocidad_bajada_mbps || '';
    document.getElementById('plan-velocidad-subida').value = p.velocidad_subida_mbps || '';
    document.getElementById('plan-tecnologia').value = p.tecnologia || 'FTTH';
    document.getElementById('plan-precio').value = p.precio_mensual;
    document.getElementById('plan-moneda').value = p.moneda;
    togglePlanForm(false, false);
}

async function eliminarPlan(id) {
    if (!confirm('¿Eliminar plan?')) return;
    try {
        const res = await fetch(`api_planes.php?id=${id}`, { method: 'DELETE', headers: buildSessionFetchHeaders() });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') await loadPlanes();
    } catch (e) { console.error(e); }
}

// --- CONTRATOS ---
async function loadContratos() {
    try {
        await fetch('api_contratos_vencer.php', {
            method: 'POST',
            headers: buildSessionFetchHeaders()
        });
        const res = await fetch('api_contratos.php', { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success') { window.contratosCache = data.contratos || []; renderContratosPage(1); }
    } catch (e) { console.error(e); }
}

function renderContratosPage(page) {
    const tbody = document.getElementById('contratos-list');
    if (!tbody) return;

    // Bug 4: filtrado por búsqueda antes de paginar.
    const filtered = filterBySearch(window.contratosCache, ['nombres', 'apellidos', 'servicio_alias', 'direccion_texto', 'tipo_contrato', 'estado'], 'contratos-search');

    const start = (page - 1) * window.pageSize;
    const vis = filtered.slice(start, start + window.pageSize);
    
    tbody.innerHTML = vis.length ? vis.map(c => `
        <tr data-module-row="contratos" data-id="${escapeHtml(c.id_contrato)}" onclick="selectRow('contratos', '${escapeHtml(c.id_contrato)}', this)">
            <td>${escapeHtml(c.nombres ? `${c.nombres} ${c.apellidos}`.trim() : 'Sin cliente')}</td>
            <td>${escapeHtml(c.servicio_alias || c.direccion_texto || 'Servicio')}</td>
            <td>${escapeHtml(c.fecha_inicio)}</td>
            <td>${escapeHtml(c.fecha_fin || 'Indefinido')}</td>
            <td>${escapeHtml(c.tipo_contrato)}</td>
            <td><span class="status ${getStatusClass(c.estado)}">${escapeHtml(c.estado)}</span></td>
        </tr>
    `).join('') : '<tr><td colspan="6">No hay contratos.</td></tr>';
    renderPagination('contratos-pagination', 'contratos', page, filtered.length, 'renderContratosPage');
}

function refreshContratoDraft() {
    const draft = document.getElementById('contrato-draft-preview');
    if (!draft) return;
    const idSrv = document.getElementById('contrato-servicio')?.value;
    const srv = window.serviciosCache.find(s => String(s.id_servicio) === String(idSrv));
    const clName = srv ? `${srv.nombres} ${srv.apellidos}`.trim() || srv.cedula : 'cliente';
    const dir = srv?.direccion_texto || 'sin dirección';
    const alias = srv?.alias ? ` (${srv.alias})` : '';
    const fIni = document.getElementById('contrato-fecha-inicio')?.value || 'fecha por definir';
    const fFin = document.getElementById('contrato-fecha-fin')?.value || 'sin fecha de fin';
    const tipo = document.getElementById('contrato-tipo')?.value || 'indefinido';
    const obs = document.getElementById('contrato-observaciones')?.value.trim() || 'Sin observaciones.';
    const tipoLbl = tipo === 'plazo_fijo' ? 'a plazo fijo' : tipo === 'promocional' ? 'promocional' : 'indefinido';
    
    draft.value = `Borrador de contrato de prestación de servicios.\n\nREDYTELCA acuerda con ${clName} la prestación del servicio${alias} en ${dir}. El presente acuerdo inicia el ${fIni} y, en caso de aplicarse, concluye el ${fFin}. La modalidad contractual corresponde a un contrato ${tipoLbl}. Observaciones: ${obs}`;
}

function exportContratoPdf() {
    const draft = document.getElementById('contrato-draft-preview');
    if (!draft || !draft.value.trim()) { alert('No hay contenido.'); return; }
    const win = window.open('', '_blank');
    if (!win) { alert('Pop-up bloqueado'); return; }
    win.document.write(`<html><head><title>Borrador</title><style>body{font-family:sans-serif;padding:20px;white-space:pre-wrap;}</style></head><body><h2>Borrador de contrato</h2>${escapeHtml(draft.value).replace(/\n/g, '<br>')}</body></html>`);
    win.document.close();
    setTimeout(() => win.print(), 250);
}

function toggleContratoForm(forceClose = false, reset = true) {
    const m = document.getElementById('contrato-modal');
    if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
    if (!forceClose && reset) {
        ['contrato-id', 'contrato-servicio', 'contrato-fecha-inicio', 'contrato-fecha-fin', 'contrato-observaciones'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        const elT = document.getElementById('contrato-tipo'); if (elT) elT.value = 'indefinido';
        const elE = document.getElementById('contrato-estado'); if (elE) elE.value = 'vigente';
        refreshContratoDraft();
    }
}

async function guardarContrato() {
    const id = document.getElementById('contrato-id').value;
    const payload = {
        id_servicio: document.getElementById('contrato-servicio').value,
        fecha_inicio: document.getElementById('contrato-fecha-inicio').value,
        fecha_fin: document.getElementById('contrato-fecha-fin').value,
        tipo_contrato: document.getElementById('contrato-tipo').value,
        estado: document.getElementById('contrato-estado').value,
        observaciones: document.getElementById('contrato-observaciones').value.trim()
    };
    if (!payload.id_servicio || !payload.fecha_inicio) { alert('Faltan datos'); return; }
    if (payload.tipo_contrato !== 'indefinido' && !payload.fecha_fin) { alert('Requiere fecha fin'); return; }
    if (id) payload.id_contrato = id;

    try {
        const res = await fetch('api_contratos.php', {
            method: id ? 'PUT' : 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') { toggleContratoForm(true); await loadContratos(); await loadClientes(window.currentClienteSearch, 1); }
    } catch (e) { console.error(e); }
}

function editarContrato(id) {
    const c = window.contratosCache.find(x => String(x.id_contrato) === String(id));
    if (!c) return;
    document.getElementById('contrato-id').value = c.id_contrato;
    document.getElementById('contrato-servicio').value = c.id_servicio;
    document.getElementById('contrato-fecha-inicio').value = c.fecha_inicio;
    document.getElementById('contrato-fecha-fin').value = c.fecha_fin || '';
    document.getElementById('contrato-tipo').value = c.tipo_contrato;
    document.getElementById('contrato-estado').value = c.estado;
    document.getElementById('contrato-observaciones').value = c.observaciones || '';
    toggleContratoForm(false, false);
    refreshContratoDraft();
}

async function eliminarContrato(id) {
    if (!confirm('¿Eliminar contrato?')) return;
    try {
        const res = await fetch(`api_contratos.php?id=${id}`, { method: 'DELETE', headers: buildSessionFetchHeaders() });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') await loadContratos();
    } catch (e) { console.error(e); }
}

// --- ROLES Y PERMISOS ---
async function loadRoles() {
    try {
        const res = await fetch('roles_listar.php', { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success') {
            window.rolesCache = data.roles || [];
            const opts = window.rolesCache.map(r => `<option value="${r.id_rol}">${escapeHtml(r.nombre_rol)}</option>`).join('');
            ['role-select', 'new-user-role', 'config-role-select', 'modulos-role-select'].forEach(id => {
                const el = document.getElementById(id); if (el) el.innerHTML = opts;
            });
            
            const rolesCheckboxes = document.getElementById('edit-user-roles-checkboxes');
            if (rolesCheckboxes) {
                if (window.rolesCache.length > 0) {
                    rolesCheckboxes.innerHTML = window.rolesCache.map(r => `
                        <label style="display:inline-flex; align-items:center; gap:8px; font-size:0.95rem; color:var(--text); cursor:pointer;">
                            <input type="checkbox" value="${r.id_rol}">
                            ${escapeHtml(r.nombre_rol)}
                        </label>
                    `).join('');
                } else {
                    rolesCheckboxes.innerHTML = '<small style="color:#64748b;">No hay roles cargados.</small>';
                }
            }

            renderRoleList(window.rolesCache);
            if (window.rolesCache.length) {
                loadRolePermissions('modulos');
            }
        }
    } catch (e) { console.error(e); }
}

function renderRoleList(roles) {
    const tbody = document.getElementById('roles-list');
    if (!tbody) return;

    // Bug 4: filtrado por búsqueda antes de renderizar.
    const filtered = filterBySearch(roles, ['nombre_rol'], 'roles-search');

    tbody.innerHTML = filtered.length ? filtered.map(r => `
        <tr data-module-row="roles" data-id="${escapeHtml(r.id_rol)}" onclick="selectRow('roles', '${escapeHtml(r.id_rol)}', this)">
            <td><strong>${escapeHtml(r.id_rol)}</strong></td>
            <td>${escapeHtml(r.nombre_rol)}</td>
        </tr>
    `).join('') : '<tr><td colspan="2">No hay roles.</td></tr>';
}

function toggleRoleModal(forceClose = false, reset = true) {
    const m = document.getElementById('role-modal');
    if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
    if (!forceClose && reset) {
        const el = document.getElementById('config-new-role-name'); if (el) el.value = '';
        const fb = document.getElementById('config-role-feedback'); if (fb) fb.textContent = '';
    }
}

async function crearRol() {
    const nombre = document.getElementById('config-new-role-name')?.value.trim();
    if (!nombre) { alert('Nombre obligatorio'); return; }
    try {
        const res = await fetch('roles_listar.php', {
            method: 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ nombre_rol: nombre })
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') { toggleRoleModal(true); await loadRoles(); }
    } catch (e) { console.error(e); }
}

function abrirEditarRol(id) {
    const r = window.rolesCache.find(x => String(x.id_rol) === String(id));
    if (!r) return;
    if (Number(r.id_rol) === 1) { alert('El rol Administrador no se puede editar.'); return; }
    document.getElementById('edit-role-id').value = r.id_rol;
    document.getElementById('edit-role-name').value = r.nombre_rol;
    document.getElementById('edit-role-modal').style.display = 'flex';
}

async function guardarRolEditado() {
    const id = document.getElementById('edit-role-id').value;
    const nombre = document.getElementById('edit-role-name').value.trim();
    if (!nombre) { alert('Nombre obligatorio'); return; }
    try {
        const res = await fetch('roles_editar.php', {
            method: 'PUT', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ id_rol: id, nombre_rol: nombre })
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') { document.getElementById('edit-role-modal').style.display = 'none'; await loadRoles(); }
    } catch (e) { console.error(e); }
}

async function eliminarRol(id) {
    if (Number(id) === 1) { alert('El rol Administrador no se puede eliminar.'); return; }
    if (!confirm('¿Eliminar rol?')) return;
    try {
        const res = await fetch('roles_eliminar.php', {
            method: 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ id_rol: id })
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') await loadRoles();
    } catch (e) { console.error(e); }
}

function preseleccionarRolEnModulos(id) {
    const sel = document.getElementById('modulos-role-select');
    if (sel) { sel.value = id; loadRolePermissions('modulos'); }
}

function onVisibleToggle(idPagina) {
    const block = document.querySelector(`[data-pagina-block="${idPagina}"]`);
    if (!block) return;
    const visibleChk = block.querySelector('.permiso-visible');
    const visible = visibleChk.checked;
    const ops = block.querySelectorAll('.permiso-operacion');
    ops.forEach(op => {
        op.disabled = visibleChk.disabled ? true : !visible;
    });
    const wrap = block.querySelector('.permisos-operacion');
    if (wrap) wrap.style.opacity = visible ? '1' : '0.55';
}

async function loadRolePermissions(target = 'modulos') {
    const selId = target === 'modulos' ? 'modulos-role-select' : 'role-select';
    const contId = target === 'modulos' ? 'modulos-permissions-list' : 'permissions-list';
    const sel = document.getElementById(selId);
    const cont = document.getElementById(contId);
    if (!sel || !cont) return;

    try {
        const res = await fetch(`permisos_listar.php?id_rol=${sel.value}`, { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success') {
            const esAdmin = String(sel.value) === '1';

            cont.innerHTML = `<div class="permissions-matrix">` + data.modulos.map(m => {
                const isConfig = m.id_modulo === 4;
                const disableSection = !esAdmin && isConfig;
                const disabledAttr = disableSection ? 'disabled' : '';
                const titleAttr = disableSection ? 'title="Solo un Administrador puede otorgar permisos sobre el módulo Configuración."' : '';
                const labelStyle = disableSection ? 'opacity: 0.55; cursor: not-allowed;' : '';
                const warningMsg = disableSection ? `<small style="display:block; margin-top:8px; color:#b45309; font-size:0.8rem;">Solo Administradores pueden modificar esta sección.</small>` : '';

                return `
                <div class="permission-module-card">
                    <h4 class="permission-module-title">${escapeHtml(m.nombre_modulo)}</h4>
                    ${warningMsg}
                    <div style="display:flex; flex-direction:column; gap:12px; ${disableSection ? 'margin-top:12px;' : ''}">
                        ${m.vistas.map(v => `
                            <div class="permission-item" data-pagina-block="${v.id_pagina}" style="flex-direction:column; align-items:flex-start; gap:6px;">
                                <span class="permission-label">${escapeHtml(v.nombre_pagina)}</span>
                                <div>
                                    <label style="display:inline-flex; align-items:center; gap:6px; font-size:0.85rem; font-weight:600; color:#0f172a; padding:4px 8px; background:#eef4ff; border-radius:6px; ${labelStyle}" ${titleAttr}>
                                        <input type="checkbox" class="permiso-visible"
                                               data-pagina="${v.id_pagina}"
                                               ${v.visible ? 'checked' : ''}
                                               onchange="onVisibleToggle(${v.id_pagina})"
                                               ${disabledAttr}>
                                        Ver
                                    </label>
                                </div>
                                <div class="permisos-operacion" style="display:flex; flex-wrap:wrap; gap:10px;">
                                    ${v.permisos.map(p => `
                                        <label style="display:inline-flex; align-items:center; gap:6px; font-size:0.85rem; color:#475569; ${labelStyle}" ${titleAttr}>
                                            <input type="checkbox" class="permiso-operacion" data-pagina="${v.id_pagina}" data-permiso="${p.id_permiso}" value="${p.id_permiso}" ${p.asignado ? 'checked' : ''} ${disabledAttr}>
                                            ${escapeHtml(p.nombre)}
                                        </label>
                                    `).join('')}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>`;
            }).join('') + `</div>`;

            cont.querySelectorAll('.permiso-visible').forEach(chk => {
                if (!chk.disabled) onVisibleToggle(chk.dataset.pagina);
            });
        }
    } catch (e) { console.error(e); }
}

async function guardarPermisos(target = 'modulos') {
    const selId = target === 'modulos' ? 'modulos-role-select' : 'role-select';
    const contId = target === 'modulos' ? 'modulos-permissions-list' : 'permissions-list';
    const fbId = target === 'modulos' ? 'modulos-permissions-feedback' : 'permissions-feedback';
    
    const idRol = document.getElementById(selId)?.value;
    const cont = document.getElementById(contId);
    const fb = document.getElementById(fbId);

    if (!idRol || !cont) return;

    const paginas = [];
    const blocks = cont.querySelectorAll('[data-pagina-block]');
    
    blocks.forEach(block => {
        const idPagina = Number(block.dataset.paginaBlock);
        const visibleChk = block.querySelector('.permiso-visible');
        const visible = visibleChk ? visibleChk.checked : false;
        
        const permisos = [];
        const opChks = block.querySelectorAll('.permiso-operacion');
        opChks.forEach(op => {
            if (op.checked) {
                permisos.push(Number(op.dataset.permiso));
            }
        });
        
        if (visible || permisos.length > 0) {
            paginas.push({
                id_pagina: idPagina,
                visible: visible,
                permisos: permisos
            });
        }
    });

    try {
        const res = await fetch('permisos_guardar.php', {
            method: 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ id_rol: idRol, paginas })
        });
        const data = await res.json();
        if (fb) { 
            fb.style.color = data.status === 'success' ? 'green' : 'red'; 
            fb.textContent = data.message; 
        }
        if (data.status === 'success') {
            await restoreSession(false);
            // RBAC-04: refrescar la matriz visible con los permisos recién guardados.
            // Sin esto, la UI sigue mostrando el estado previo del formulario hasta F5.
            await loadRolePermissions(target);
        }
    } catch (e) { console.error(e); }
}

// --- USUARIOS ---
async function loadUsers() {
    try {
        const res = await fetch('usuarios_listar.php', { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success') { window.usuariosCache = data.usuarios || []; renderUsersPage(1); populateAssignmentSelectors(); }
    } catch (e) { console.error(e); }
}

function renderUsersPage(page) {
    const tbody = document.querySelector('#users-table tbody');
    if (!tbody) return;

    // Filtro por estado (pastillas) — se conserva del diseño anterior.
    const f = window.currentUsersFilter || 'Todos';
    const porEstado = window.usuariosCache.filter(u => f === 'Todos' || String(u.estado || '') === f);

    // Bug 4: filtrado por búsqueda antes de paginar.
    const filtered = filterBySearch(porEstado, ['username', 'email', 'roles_nombres', 'estado'], 'users-search');

    const start = (page - 1) * window.pageSize;
    const vis = filtered.slice(start, start + window.pageSize);
    
    tbody.innerHTML = vis.length ? vis.map(u => `
        <tr data-module-row="usuarios" data-id="${escapeHtml(u.id_usuario)}" onclick="selectRow('usuarios', '${escapeHtml(u.id_usuario)}', this)">
            <td><strong>${escapeHtml(u.username)}</strong></td>
            <td>${escapeHtml(u.email)}</td>
            <td>${escapeHtml(u.roles_nombres || 'Sin rol')}</td>
            <td><span class="status ${u.email_verified ? 'status-ok' : 'status-warning'}">${u.email_verified ? 'Verificado' : 'Pendiente'}</span></td>
            <td>${u.must_change_password ? 'Sí' : 'No'}</td>
            <td>${escapeHtml(u.ultima_conexion || 'Nunca')}</td>
        </tr>
    `).join('') : '<tr><td colspan="6">No hay usuarios.</td></tr>';
    renderPagination('users-pagination', 'usuarios', page, filtered.length, 'renderUsersPage');
}

function toggleUserModal(forceClose = false, reset = true) {
    const m = document.getElementById('user-modal');
    if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
    if (!forceClose && reset) {
        ['new-user-username', 'new-user-email'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        const fb = document.getElementById('user-feedback'); if (fb) fb.textContent = '';
    }
}

async function crearUsuario() {
    const payload = {
        username: document.getElementById('new-user-username').value.trim(),
        email: document.getElementById('new-user-email').value.trim(),
        roles: [document.getElementById('new-user-role').value]
    };
    if (!payload.username || !payload.email) { alert('Faltan datos'); return; }

    try {
        const res = await fetch('usuarios_crear.php', {
            method: 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        const fb = document.getElementById('user-feedback');
        if (fb) { fb.style.color = data.status === 'success' ? 'green' : 'red'; fb.textContent = data.message; }
        if (data.status === 'success') { setTimeout(() => toggleUserModal(true), 1500); await loadUsers(); }
    } catch (e) { console.error(e); }
}

function abrirEditarUsuario(id) {
    const u = window.usuariosCache.find(x => String(x.id_usuario) === String(id));
    if (!u) return;
    document.getElementById('edit-user-id').value = u.id_usuario;
    document.getElementById('edit-user-username').value = u.username;
    document.getElementById('edit-user-email').value = u.email;

    const rolesDelUsuario = Array.isArray(u.roles_ids) ? u.roles_ids.map(String) : [];
    document.querySelectorAll('#edit-user-roles-checkboxes input[type=checkbox]').forEach(cb => {
        cb.checked = rolesDelUsuario.includes(String(cb.value));
    });

    document.getElementById('edit-user-modal').style.display = 'flex';
}

async function guardarUsuarioEditado() {
    const id = document.getElementById('edit-user-id').value;
    const rolesSeleccionados = Array.from(
        document.querySelectorAll('#edit-user-roles-checkboxes input[type=checkbox]:checked')
    ).map(cb => cb.value);

    const payload = {
        id_usuario: id,
        username: document.getElementById('edit-user-username').value.trim(),
        email: document.getElementById('edit-user-email').value.trim(),
        roles: rolesSeleccionados
    };

    if (!payload.username || !payload.email) { alert('Faltan datos'); return; }
    if (payload.roles.length === 0) { alert('Selecciona al menos un rol.'); return; }

    try {
        const res = await fetch('usuarios_editar.php', {
            method: 'PUT', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') {
            document.getElementById('edit-user-modal').style.display = 'none';
            await loadUsers();
        }
    } catch (e) { console.error(e); }
}

async function eliminarUsuario(id) {
    if (!confirm('¿Eliminar usuario?')) return;
    try {
        const res = await fetch('usuarios_eliminar.php', {
            method: 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ id_usuario: id })
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') await loadUsers();
    } catch (e) { console.error(e); }
}

function abrirResetPassword(id, username) {
    document.getElementById('reset-user-id').value = id;
    document.getElementById('reset-username').value = username;
    document.getElementById('reset-new-password').value = '';
    document.getElementById('reset-password-feedback').textContent = '';
    document.getElementById('reset-password-modal').style.display = 'flex';
}

function toggleResetPasswordModal(forceClose = false) {
    const m = document.getElementById('reset-password-modal');
    if (m) m.style.display = forceClose ? 'none' : (m.style.display === 'flex' ? 'none' : 'flex');
}

async function guardarResetPassword() {
    const id = document.getElementById('reset-user-id').value;
    const user = document.getElementById('reset-username').value;
    const pass = document.getElementById('reset-new-password').value;
    const fb = document.getElementById('reset-password-feedback');
    if (!pass) { fb.style.color = 'red'; fb.textContent = 'Ingresa la nueva contraseña'; return; }

    try {
        const res = await fetch('cambiar_password.php', {
            method: 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ username: user, user_id: id, password_nueva: pass })
        });
        const data = await res.json();
        fb.style.color = data.status === 'success' ? 'green' : 'red';
        fb.textContent = data.message;
        if (data.status === 'success') { setTimeout(() => toggleResetPasswordModal(true), 1500); await loadUsers(); }
    } catch (e) { console.error(e); }
}

function closeUserDetailModal() {
    const m = document.getElementById('user-detail-modal');
    if (m) m.style.display = 'none';
}

// --- EMPRESA ---
async function cargarConfiguracionEmpresaCompleta() {
    try {
        const res = await fetch('api_empresa.php', { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success' && data.config) {
            applyEmpresaConfig(data.config);
            const elN = document.getElementById('empresa-nombre'); if (elN) elN.value = data.config.nombre_empresa || '';
            const elC = document.getElementById('empresa-color'); if (elC) elC.value = data.config.color_primario || '#104aa8';
        }
    } catch (e) { console.error(e); }
}

/**
 * Carga solo el branding público (nombre, logo, color) antes del login.
 * Separa la configuración sensible de la pública (Hallazgo B09, spec 024).
 */
async function cargarConfiguracionEmpresaPublica() {
    try {
        const res = await fetch('api_empresa_publica.php', { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success' && data.config) {
            applyEmpresaConfig(data.config);
        }
    } catch (e) { console.warn(e); }
}

function applyEmpresaConfig(config) {
    const name = config.nombre_empresa || 'REDYTELCA';
    const color = config.color_primario || '#104aa8';
    const logo = config.logo_path || 'Logos/logo_transparent.png';

    const hex = color.replace('#', '');
    let r = 16, g = 74, b = 168;
    if (hex.length === 6) {
        r = parseInt(hex.substring(0, 2), 16);
        g = parseInt(hex.substring(2, 4), 16);
        b = parseInt(hex.substring(4, 6), 16);
    }
    document.documentElement.style.setProperty('--accent', color);
    document.documentElement.style.setProperty('--accent-rgb', `${r}, ${g}, ${b}`);

    ['sidebar-brand-logo', 'login-brand-logo', 'empresa-logo-preview'].forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.src = logo; el.alt = name; }
    });

        try {
        localStorage.setItem('redytelca_empresa_config', JSON.stringify(config));
    } catch (e) { /* quota excedida, ignorar */ }
}

async function guardarEmpresa() {
    const fd = new FormData();
    fd.append('nombre_empresa', document.getElementById('empresa-nombre').value.trim());
    fd.append('color_primario', document.getElementById('empresa-color').value);
    const file = document.getElementById('empresa-logo').files[0];
    if (file) fd.append('logo', file);

    try {
        const res = await fetch('api_empresa.php', { method: 'POST', headers: buildSessionFetchHeaders(), body: fd });
        const data = await res.json();
        const fb = document.getElementById('empresa-feedback');
        if (fb) { fb.style.color = data.status === 'success' ? 'green' : 'red'; fb.textContent = data.message; }
        if (data.status === 'success') await cargarConfiguracionEmpresaCompleta();
    } catch (e) { console.error(e); }
}

// --- PERFIL ---
async function renderPerfil() {
    try {
        const res = await fetch('api_perfil.php', { headers: buildSessionFetchHeaders() });
        const data = await res.json();
        if (data.status === 'success') {
            const u = data.usuario;
            const name = u.username || 'Usuario';
            document.getElementById('perfil-username').textContent = name;
            document.getElementById('perfil-email').textContent = u.email || '-';
            document.getElementById('perfil-rol').textContent = u.nombre_rol || '-';
            document.getElementById('perfil-avatar-iniciales').textContent = name.charAt(0).toUpperCase();
            document.getElementById('perfil-username-large').textContent = name;
            document.getElementById('perfil-rol-badge').textContent = u.nombre_rol || 'Sin rol';

            const tbody = document.getElementById('perfil-sesiones-body');
            if (tbody) {
                tbody.innerHTML = data.sesiones.length ? data.sesiones.map(s => `
                    <tr><td>${escapeHtml(s.id_sesion)}</td><td>${escapeHtml(s.creado_en)}</td><td>${escapeHtml(s.expira_en)}</td></tr>
                `).join('') : '<tr><td colspan="3">Sin sesiones.</td></tr>';
            }
        }
    } catch (e) { console.error(e); }
}

async function cambiarPassword() {
    const user = document.getElementById('password-user').value.trim();
    const curr = document.getElementById('password-current').value;
    const newP = document.getElementById('password-new').value;
    const conf = document.getElementById('password-confirm').value;
    const fb = document.getElementById('password-feedback');

    if (!user || !curr || !newP || !conf) { fb.style.color = 'red'; fb.textContent = 'Faltan datos'; return; }
    if (newP !== conf) { fb.style.color = 'red'; fb.textContent = 'No coinciden'; return; }

    try {
        const res = await fetch('cambiar_password.php', {
            method: 'POST', headers: buildSessionFetchHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ username: user, password_actual: curr, password_nueva: newP })
        });
        const data = await res.json();
        fb.style.color = data.status === 'success' ? 'green' : 'red';
        fb.textContent = data.message;
        if (data.status === 'success') {
            window.permissionContext.must_change_password = false;
            persistSessionContext(window.permissionContext);
            setTimeout(() => {
                document.getElementById('password-modal').style.display = 'none';
                ['password-current', 'password-new', 'password-confirm'].forEach(id => document.getElementById(id).value = '');
                fb.textContent = '';
            }, 1500);
        }
    } catch (e) { console.error(e); }
}

// ----------------------------------------------------------------------------
// 10. INICIALIZACIÓN Y EVENTOS
// ----------------------------------------------------------------------------
async function initApp() {
    renderMenu();
    await loadRoles();
    await loadUsers();
    await loadNaps();
    await loadServicios();
    await loadNodos();
    await loadOlts();
    await loadEquipos();
    await loadClientes('', 1);
    await loadPlanes();
    await loadFacturas();
    await loadPagos();
    await loadMetodosPago();
    await loadContratos();
    await loadTickets();
    await loadTareas();
    await cargarConfiguracionEmpresaCompleta();
    updateDashboardData();
}

document.addEventListener('DOMContentLoaded', async () => {
    document.querySelectorAll('.modal-overlay').forEach(m => {
        m.addEventListener('click', e => { if (e.target === m) m.style.display = 'none'; });
    });

    // Aplicar branding cacheado SÍNCRONAMENTE (evita flash del color default)
    const cachedBranding = localStorage.getItem('redytelca_empresa_config');
    if (cachedBranding) {
        try {
            applyEmpresaConfig(JSON.parse(cachedBranding));
        } catch (e) { /* cache corrupto, ignorar */ }
    }

  // Refrescar branding desde el servidor (por si cambió desde otra sesión)
  await cargarConfiguracionEmpresaPublica();
    const sessionActive = await restoreSession();
    if (sessionActive) {
        await initApp();
    }
});

document.addEventListener('click', (e) => {
    const menu = document.getElementById('profile-menu');
    const btn = document.querySelector('.profile-button');
    if (menu && btn && !btn.contains(e.target) && !menu.contains(e.target)) {
        menu.classList.remove('show');
    }
    
    const clientRow = e.target.closest('#clientes-list tr[data-client-id]');
    if (clientRow) {
        const id = Number(clientRow.dataset.clientId);
        const c = window.clientesCache.find(x => Number(x.id_cliente) === id);
        if (c) renderClientProfile(c);
        return;
    }
});