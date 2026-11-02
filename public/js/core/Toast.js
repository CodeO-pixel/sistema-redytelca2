// js/core/Toast.js
(function () {
    'use strict';

    let cssInjected = false;
    let container = null;
    const MAX_TOASTS = 5;

    function injectCss() {
        if (cssInjected) return;
        const style = document.createElement('style');
        style.textContent = `
            .core-toast-container {
                position: fixed; top: 20px; right: 20px; z-index: 9999;
                display: flex; flex-direction: column; gap: 10px;
                pointer-events: none;
            }
            .core-toast {
                background: white; border-radius: 10px; padding: 12px 16px;
                box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);
                display: flex; align-items: center; gap: 12px;
                pointer-events: auto; cursor: pointer;
                border-left: 4px solid var(--accent, #104aa8);
                min-width: 250px; max-width: 350px;
                animation: core-toast-in 0.3s ease forwards;
                transition: opacity 0.3s ease, transform 0.3s ease;
            }
            .core-toast.hiding { opacity: 0; transform: translateX(100%); }
            .core-toast-icon {
                width: 24px; height: 24px; border-radius: 50%;
                display: flex; align-items: center; justify-content: center;
                color: white; font-weight: bold; font-size: 14px; flex-shrink: 0;
            }
            .core-toast-msg { color: #0f172a; font-size: 0.9rem; line-height: 1.4; word-break: break-word; }
            .core-toast-info { border-left-color: var(--accent, #104aa8); }
            .core-toast-info .core-toast-icon { background: var(--accent, #104aa8); }
            .core-toast-success { border-left-color: var(--success, #16a34a); }
            .core-toast-success .core-toast-icon { background: var(--success, #16a34a); }
            .core-toast-warning { border-left-color: var(--warning, #f59e0b); }
            .core-toast-warning .core-toast-icon { background: var(--warning, #f59e0b); }
            .core-toast-error { border-left-color: var(--danger, #dc2626); }
            .core-toast-error .core-toast-icon { background: var(--danger, #dc2626); }
            @keyframes core-toast-in {
                from { opacity: 0; transform: translateX(100%); }
                to { opacity: 1; transform: translateX(0); }
            }
        `;
        document.head.appendChild(style);
        cssInjected = true;
    }

    function getContainer() {
        if (!container) {
            injectCss();
            container = document.createElement('div');
            container.className = 'core-toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    function showToast(message, type = 'info', duration = 4000) {
        const cont = getContainer();

        if (cont.children.length >= MAX_TOASTS) {
            const oldest = cont.firstElementChild;
            if (oldest) {
                oldest.remove();
            }
        }

        const toast = document.createElement('div');
        toast.className = `core-toast core-toast-${type}`;

        const icon = document.createElement('div');
        icon.className = 'core-toast-icon';
        if (type === 'info') icon.textContent = 'i';
        else if (type === 'success') icon.textContent = '✓';
        else if (type === 'warning') icon.textContent = '!';
        else if (type === 'error') icon.textContent = '×';

        const msg = document.createElement('div');
        msg.className = 'core-toast-msg';
        msg.textContent = message;

        toast.appendChild(icon);
        toast.appendChild(msg);

        let timeoutId = null;

        const close = () => {
            if (timeoutId) clearTimeout(timeoutId);
            toast.classList.add('hiding');
            setTimeout(() => {
                if (toast.parentElement) toast.remove();
            }, 300);
        };

        toast.onclick = close;

        cont.appendChild(toast);

        if (duration > 0) {
            timeoutId = setTimeout(close, duration);
        }
    }

    window.Toast = {
        show: showToast,
        info: function (message, duration) { showToast(message, 'info', duration); },
        success: function (message, duration) { showToast(message, 'success', duration); },
        warning: function (message, duration) { showToast(message, 'warning', duration); },
        error: function (message, duration) { showToast(message, 'error', duration); }
    };
})();