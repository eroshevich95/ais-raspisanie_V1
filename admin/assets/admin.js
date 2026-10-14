/* ============================================================
   ADMIN PANEL JS
   ============================================================
   Глобальный объект window.Adm — все утилиты и API.
   Каждая вкладка (tabs/*.php) подключает свою логику
   через window.Adm.registerTab(name, { onShow, onInit }).
   ============================================================ */

(function() {
    'use strict';

    const CSRF = window.__ADM_CSRF__ || '';
    const API  = window.__ADM_API__  || 'api.php';

    /* ============================================================
       УТИЛИТЫ
       ============================================================ */

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        })[c]);
    }

    function fmtDate(str, opts) {
        if (!str) return '—';
        const d = new Date(String(str).replace(' ', 'T'));
        if (isNaN(d)) return escapeHtml(str);
        if (opts === 'date') {
            return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
        }
        if (opts === 'short') {
            return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' })
                + ' ' + d.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
        }
        if (opts === 'time') {
            return d.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
        }
        return d.toLocaleString('ru-RU', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }

    function fmtNumber(n) {
        return Number(n || 0).toLocaleString('ru-RU');
    }

    function genPassword(len = 8) {
        const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        const arr = new Uint8Array(len);
        crypto.getRandomValues(arr);
        return Array.from(arr).map(b => chars[b % chars.length]).join('');
    }

    function debounce(fn, ms = 300) {
        let t = null;
        return function(...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), ms);
        };
    }

    /* ============================================================
       TOAST
       ============================================================ */

    let toastTimer = null;
    function showToast(text, isError = false) {
        const t = document.getElementById('admToast');
        const txt = document.getElementById('admToastText');
        const icon = t.querySelector('i');

        txt.textContent = text;
        if (isError) {
            icon.className = 'bi bi-exclamation-triangle-fill';
            icon.style.color = 'var(--adm-red)';
        } else {
            icon.className = 'bi bi-check-circle-fill';
            icon.style.color = 'var(--adm-green)';
        }

        t.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.classList.remove('show'), 2400);
    }

    /* ============================================================
       МОДАЛКИ
       ============================================================ */

    function openModal(id) {
        const m = document.getElementById(id);
        if (!m) return;
        m.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(id) {
        const m = document.getElementById(id);
        if (!m) return;
        m.classList.remove('show');
        // Если других открытых модалок нет — вернуть скролл
        if (!document.querySelector('.adm-modal-backdrop.show')) {
            document.body.style.overflow = '';
        }
    }

    function confirmDialog(text, onConfirm, opts = {}) {
        const modal = document.getElementById('confirmModal');
        if (!modal) {
            if (window.confirm(text)) onConfirm();
            return;
        }
        document.getElementById('confirmText').textContent = text;
        const titleEl = document.getElementById('confirmTitle');
        if (titleEl) titleEl.textContent = opts.title || 'Подтверждение';

        const btn = document.getElementById('confirmOkBtn');
        const newBtn = btn.cloneNode(true);
        newBtn.classList.toggle('adm-btn--danger', opts.danger !== false);
        newBtn.classList.toggle('adm-btn--primary', opts.danger === false);
        btn.replaceWith(newBtn);

        newBtn.addEventListener('click', () => {
            closeModal('confirmModal');
            onConfirm();
        });
        openModal('confirmModal');
    }

    /* ============================================================
       API CALL
       ============================================================ */

    async function apiCall(action, data = {}, opts = {}) {
        const fd = new FormData();
        fd.append('action', action);
        fd.append('csrf', CSRF);
        for (const [k, v] of Object.entries(data)) {
            if (Array.isArray(v)) {
                v.forEach(item => fd.append(k + '[]', item));
            } else if (v !== null && v !== undefined) {
                fd.append(k, v);
            }
        }

        const res = await fetch(API, { method: 'POST', body: fd });

        if (opts.raw) return res;

        if (!res.ok) {
            let msg = 'HTTP ' + res.status;
            try {
                const j = await res.json();
                if (j && j.error) msg = j.error;
            } catch (e) {}
            throw new Error(msg);
        }

        const json = await res.json();
        if (!json.ok) throw new Error(json.error || 'Неизвестная ошибка');
        return json;
    }

    /* ============================================================
       АНИМАЦИЯ СЧЁТЧИКОВ
       ============================================================ */

    function animateCounters(root) {
        (root || document).querySelectorAll('[data-count]:not([data-count-done])').forEach(el => {
            el.setAttribute('data-count-done', '1');
            const raw = el.dataset.count;
            const target = parseInt(raw, 10);
            if (isNaN(target)) return;

            const hasPercent = el.dataset.countSuffix === '%'
                || /%$/.test(el.textContent || '');
            const start = performance.now();
            const dur = 1000;

            function tick(now) {
                const t = Math.min((now - start) / dur, 1);
                const eased = 1 - Math.pow(1 - t, 3);
                const cur = Math.round(eased * target);
                el.textContent = hasPercent ? cur + '%' : fmtNumber(cur);
                if (t < 1) requestAnimationFrame(tick);
                else el.textContent = hasPercent ? target + '%' : fmtNumber(target);
            }
            requestAnimationFrame(tick);
        });
    }

    function animateProgressBars(root) {
        (root || document).querySelectorAll('[data-width]:not([data-width-done])').forEach(el => {
            el.setAttribute('data-width-done', '1');
            setTimeout(() => { el.style.width = el.dataset.width + '%'; }, 200);
        });
    }

    /* ============================================================
       ПАГИНАЦИЯ (общий рендер)
       ============================================================ */

    function renderPagination(container, page, pages, onGo) {
        if (!container) return;
        if (pages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '<div class="adm-pagination">';

        html += `<button class="adm-page-btn" ${page === 1 ? 'disabled' : ''} data-go="${page - 1}">
            <i class="bi bi-chevron-left"></i>
        </button>`;

        const start = Math.max(1, page - 2);
        const end = Math.min(pages, page + 2);

        if (start > 1) {
            html += `<button class="adm-page-btn" data-go="1">1</button>`;
            if (start > 2) html += `<span style="padding:0 6px;color:var(--adm-text-soft);">…</span>`;
        }

        for (let i = start; i <= end; i++) {
            html += `<button class="adm-page-btn ${i === page ? 'active' : ''}" data-go="${i}">${i}</button>`;
        }

        if (end < pages) {
            if (end < pages - 1) html += `<span style="padding:0 6px;color:var(--adm-text-soft);">…</span>`;
            html += `<button class="adm-page-btn" data-go="${pages}">${pages}</button>`;
        }

        html += `<button class="adm-page-btn" ${page === pages ? 'disabled' : ''} data-go="${page + 1}">
            <i class="bi bi-chevron-right"></i>
        </button>`;

        html += '</div>';
        container.innerHTML = html;

        container.querySelectorAll('[data-go]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.disabled) return;
                onGo(parseInt(btn.dataset.go, 10));
            });
        });
    }

    /* ============================================================
       СКЕЛЕТОН
       ============================================================ */

    function skeleton(rows = 5) {
        return `<div class="adm-skeleton">
            ${Array(rows).fill('<div class="adm-skeleton-row"></div>').join('')}
        </div>`;
    }

    function emptyState(icon, title, text = '') {
        return `<div class="adm-empty">
            <i class="bi ${icon}"></i>
            <h4>${escapeHtml(title)}</h4>
            ${text ? `<p>${escapeHtml(text)}</p>` : ''}
        </div>`;
    }

    /* ============================================================
       БЕЙДЖИ
       ============================================================ */

    const ROLE_LABELS = {
        'teacher':            ['Преподаватель', 'teacher'],
        'head_of_department': ['Зав. кафедрой', 'head_of_department'],
        'administration':     ['Администрация', 'administration'],
        'admin':              ['Администратор', 'admin'],
    };

    function roleBadge(role) {
        const r = ROLE_LABELS[role] || ['—', 'teacher'];
        return `<span class="adm-role adm-role--${r[1]}">${escapeHtml(r[0])}</span>`;
    }

    const TYPE_LABELS = {
        'lecture':  ['Лекция',       'lecture'],
        'practice': ['Практика',     'practice'],
        'lab':      ['Лабораторная', 'lab'],
        'seminar':  ['Семинар',      'seminar'],
        'other':    ['Другое',       'other'],
    };

    function typeBadge(type) {
        const t = TYPE_LABELS[type] || TYPE_LABELS['other'];
        return `<span class="adm-status adm-status--${t[1]}">${escapeHtml(t[0])}</span>`;
    }

    /* ============================================================
       ВКЛАДКИ
       ============================================================ */

    const tabs = {};   // name -> { onInit, onShow, initialized }
    let activeTab = null;

    function registerTab(name, handlers) {
        tabs[name] = Object.assign({ onInit: null, onShow: null, initialized: false }, handlers || {});
    }

    async function showTab(name) {
        // Переключаем кнопки и панели
        document.querySelectorAll('.adm-tab[data-tab]').forEach(b => {
            b.classList.toggle('active', b.dataset.tab === name);
        });
        document.querySelectorAll('.adm-panel').forEach(p => {
            p.classList.toggle('active', p.dataset.panel === name);
        });

        activeTab = name;

        if (window.location.hash !== '#' + name) {
            history.replaceState(null, '', '#' + name);
        }

        const tab = tabs[name];
        if (!tab) return;

        try {
            if (!tab.initialized && tab.onInit) {
                await tab.onInit();
                tab.initialized = true;
            }
            if (tab.onShow) {
                await tab.onShow();
            }
        } catch (e) {
            console.error('Tab error:', name, e);
            showToast('Ошибка вкладки: ' + e.message, true);
        }
    }

    /* ============================================================
       ИНИЦИАЛИЗАЦИЯ
       ============================================================ */

    function initModalBackdrops() {
        document.querySelectorAll('.adm-modal-backdrop').forEach(m => {
            m.addEventListener('click', e => {
                if (e.target === m) closeModal(m.id);
            });
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.adm-modal-backdrop.show').forEach(m => closeModal(m.id));
            }
        });
    }

    function initTabs() {
        document.querySelectorAll('.adm-tab[data-tab]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.disabled) return;
                showTab(btn.dataset.tab);
            });
        });
    }

    function initHashRouter() {
        window.addEventListener('hashchange', () => {
            const hash = window.location.hash.replace('#', '') || 'overview';
            if (hash !== activeTab && tabs[hash]) showTab(hash);
        });
    }

    function initCountersOnLoad() {
        // Глобальные счётчики (на карточках Overview)
        // вызываем после загрузки overview
    }

    /* ============================================================
       ПУБЛИЧНЫЙ API
       ============================================================ */

    window.Adm = {
        CSRF, API,
        escapeHtml, fmtDate, fmtNumber, genPassword, debounce,
        showToast, openModal, closeModal, confirmDialog,
        apiCall,
        animateCounters, animateProgressBars,
        renderPagination, skeleton, emptyState,
        roleBadge, typeBadge,
        registerTab, showTab,
        getActiveTab: () => activeTab,
        isTabRegistered: (name) => !!tabs[name],
        _tabs: tabs,
    };

    /* ============================================================
       AUTO-INIT
       ============================================================ */

    document.addEventListener('DOMContentLoaded', () => {
        initModalBackdrops();
        initTabs();
        initHashRouter();

        // Определяем начальную вкладку
        const hash = window.location.hash.replace('#', '') || 'overview';
        const startTab = tabs[hash] ? hash : 'overview';
        showTab(startTab);
    });

})();