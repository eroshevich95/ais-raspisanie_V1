<?php
/**
 * Вкладка «Сообщения» — управление уведомлениями на страницах портала.
 */
require_once __DIR__ . '/../../includes/messages.php';
$sections = getSectionsList();
$types    = getMessagesTypeMeta();
?>

<div class="adm-card">
    <div class="adm-card-header">
        <h3 class="adm-card-title">
            <i class="bi bi-chat-square-text-fill"></i>
            Сообщения на страницах
        </h3>
        <div style="display:flex; gap:8px; align-items:center;">
            <span class="adm-card-badge" id="messagesTotalBadge">— записей</span>
            <button class="adm-btn adm-btn--primary adm-btn--sm" onclick="MessagesTab.openCreate()">
                <i class="bi bi-plus-circle-fill"></i> Добавить
            </button>
        </div>
    </div>

    <div class="adm-filters" style="grid-template-columns: 1.5fr 1.5fr 1fr auto;">
        <div>
            <div class="adm-filter-label"><i class="bi bi-search"></i> Поиск</div>
            <div class="adm-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" class="adm-input" id="messagesSearch"
                       placeholder="Заголовок или текст..." autocomplete="off">
            </div>
        </div>
        <div>
            <div class="adm-filter-label"><i class="bi bi-folder-fill"></i> Раздел</div>
            <select class="adm-select" id="messagesSectionFilter">
                <option value="">Все разделы</option>
                <?php foreach ($sections as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <div class="adm-filter-label"><i class="bi bi-circle-fill"></i> Статус</div>
            <select class="adm-select" id="messagesStatusFilter">
                <option value="">Все</option>
                <option value="active">Активные</option>
                <option value="disabled">Скрытые</option>
            </select>
        </div>
        <div>
            <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="MessagesTab.resetFilters()" style="height:42px;">
                <i class="bi bi-x-circle"></i> Сбросить
            </button>
        </div>
    </div>

    <div id="messagesTableWrap">
        <div class="adm-skeleton">
            <div class="adm-skeleton-row"></div>
            <div class="adm-skeleton-row"></div>
            <div class="adm-skeleton-row"></div>
        </div>
    </div>

    <div id="messagesPagination"></div>
</div>

<!-- Модалка: создание/редактирование -->
<div class="adm-modal-backdrop" id="messageEditModal">
    <div class="adm-modal adm-modal--wide">
        <div class="adm-modal-header">
            <h5><i class="bi bi-chat-square-text-fill"></i> <span id="messageModalTitle">Новое сообщение</span></h5>
            <button class="adm-modal-close" onclick="Adm.closeModal('messageEditModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="messageForm" onsubmit="MessagesTab.submit(event)">
            <div class="adm-modal-body">
                <input type="hidden" id="msgId" name="id">

                <div class="adm-form-row">
                    <div class="adm-form-group">
                        <label class="adm-form-label"><i class="bi bi-folder-fill"></i> Раздел <span class="req">*</span></label>
                        <select name="section" id="msgSection" class="adm-select" required>
                            <?php foreach ($sections as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="adm-form-hint">Куда показывать: конкретная страница или везде.</div>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-form-label"><i class="bi bi-palette-fill"></i> Тип <span class="req">*</span></label>
                        <select name="type" id="msgType" class="adm-select" required>
                            <?php foreach ($types as $k => $v): ?>
                                <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($v[0]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-type-h1"></i> Заголовок</label>
                    <input type="text" name="title" id="msgTitle" class="adm-input" maxlength="255"
                           placeholder="Например: Раздел работает в тестовом режиме">
                </div>

                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-text-paragraph"></i> Текст <span class="req">*</span></label>
                    <textarea name="text" id="msgText" class="adm-input" rows="5" required
                              placeholder="Содержимое сообщения..." style="resize: vertical; min-height: 100px;"></textarea>
                </div>

                <div class="adm-form-row">
                    <div class="adm-form-group">
                        <label class="adm-form-label"><i class="bi bi-sort-numeric-up"></i> Порядок</label>
                        <input type="number" name="sort_order" id="msgSort" class="adm-input" value="0">
                        <div class="adm-form-hint">Меньше = выше в списке.</div>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-form-label"><i class="bi bi-toggles"></i> Настройки</label>
                        <div style="display:flex;flex-direction:column;gap:8px;padding-top:4px;">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.88rem;">
                                <input type="checkbox" name="is_active" id="msgActive" class="adm-checkbox" checked>
                                Активно (видно на страницах)
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.88rem;">
                                <input type="checkbox" name="dismissible" id="msgDismissible" class="adm-checkbox" checked>
                                Можно скрыть (крестик)
                            </label>
                        </div>
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-eye-fill"></i> Предпросмотр</label>
                    <div id="msgPreview" style="border-radius:14px;padding:14px 18px;font-size:0.9rem;line-height:1.5;border:1px solid rgba(0,113,227,0.2);background:rgba(0,113,227,0.08);color:#084298;">
                        Так будет выглядеть сообщение на странице.
                    </div>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn--secondary" onclick="Adm.closeModal('messageEditModal')">
                    Отмена
                </button>
                <button type="submit" class="adm-btn adm-btn--primary">
                    <i class="bi bi-check-circle-fill"></i> Сохранить
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    'use strict';

    const SECTIONS = <?= json_encode($sections, JSON_UNESCAPED_UNICODE) ?>;
    const TYPES = <?= json_encode([
        'info'    => ['Информация', 'bi-info-circle-fill',       '#0071e3', 'rgba(0,113,227,0.08)',  'rgba(0,113,227,0.25)'],
        'success' => ['Успех',      'bi-check-circle-fill',      '#198754', 'rgba(48,209,88,0.08)',  'rgba(48,209,88,0.25)'],
        'warning' => ['Внимание',   'bi-exclamation-triangle-fill','#b25c00','rgba(255,159,10,0.08)', 'rgba(255,159,10,0.3)'],
        'danger'  => ['Ошибка',     'bi-x-octagon-fill',         '#842029', 'rgba(255,69,58,0.08)',  'rgba(255,69,58,0.25)'],
    ], JSON_UNESCAPED_UNICODE) ?>;

    const state = {
        page: 1,
        perPage: 30,
        search: '',
        section: '',
        status: '',
        loaded: false,
    };

    async function load(force = false) {
        if (state.loaded && !force) return;
        state.loaded = true;

        const wrap = document.getElementById('messagesTableWrap');
        wrap.innerHTML = Adm.skeleton(4);

        try {
            const res = await Adm.apiCall('messages_list', {
                page: state.page,
                per_page: state.perPage,
                search: state.search,
                section: state.section,
                status: state.status,
            });

            document.getElementById('messagesTotalBadge').textContent = res.total + ' записей';
            document.getElementById('tabCountMessages').textContent = res.total;

            renderTable(res.rows);
            Adm.renderPagination(
                document.getElementById('messagesPagination'),
                res.page, res.pages,
                p => { state.page = p; load(true); window.scrollTo({top:0, behavior:'smooth'}); }
            );
        } catch (e) {
            wrap.innerHTML = Adm.emptyState('bi-exclamation-triangle', 'Ошибка', e.message);
        }
    }

    function renderTable(rows) {
        const wrap = document.getElementById('messagesTableWrap');
        if (!rows.length) {
            wrap.innerHTML = Adm.emptyState('bi-chat-square-text', 'Пока нет сообщений', 'Создайте первое сообщение');
            return;
        }

        wrap.innerHTML = `
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th style="width:60px;" class="center">ID</th>
                            <th style="width:180px;">Раздел</th>
                            <th style="width:120px;" class="center">Тип</th>
                            <th>Заголовок / Текст</th>
                            <th style="width:80px;" class="center">Порядок</th>
                            <th style="width:110px;" class="center">Статус</th>
                            <th style="width:120px;" class="center">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.map(m => {
                            const t = TYPES[m.type] || TYPES.info;
                            const sectionLabel = SECTIONS[m.section] || m.section;
                            const isActive = parseInt(m.is_active, 10) === 1;

                            return `
                                <tr data-id="${m.id}">
                                    <td class="num" style="color:var(--adm-text-soft);font-family:'SF Mono',monospace;font-size:0.78rem;">
                                        #${m.id}
                                    </td>
                                    <td style="font-size:0.82rem;">${Adm.escapeHtml(sectionLabel)}</td>
                                    <td class="num">
                                        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;background:${t[3]};color:${t[2]};font-size:0.7rem;font-weight:700;">
                                            <i class="bi ${t[1]}"></i> ${t[0]}
                                        </span>
                                    </td>
                                    <td>
                                        ${m.title ? `<div style="font-weight:600;font-size:0.88rem;color:var(--adm-text);margin-bottom:3px;">${Adm.escapeHtml(m.title)}</div>` : ''}
                                        <div style="font-size:0.8rem;color:var(--adm-text-soft);line-height:1.4;max-height:3em;overflow:hidden;">
                                            ${Adm.escapeHtml(m.text)}
                                        </div>
                                    </td>
                                    <td class="num">${m.sort_order}</td>
                                    <td class="num">
                                        <span class="adm-status adm-status--${isActive ? 'active' : 'blocked'}">
                                            <i class="bi bi-${isActive ? 'eye-fill' : 'eye-slash-fill'}"></i>
                                            ${isActive ? 'Активно' : 'Скрыто'}
                                        </span>
                                    </td>
                                    <td class="num">
                                        <div class="adm-row-actions">
                                            <button class="adm-icon-btn adm-icon-btn--warning"
                                                    onclick="MessagesTab.toggle(${m.id}, ${isActive ? 1 : 0})"
                                                    title="${isActive ? 'Скрыть' : 'Показать'}">
                                                <i class="bi bi-${isActive ? 'eye-slash' : 'eye'}"></i>
                                            </button>
                                            <button class="adm-icon-btn adm-icon-btn--primary"
                                                    onclick="MessagesTab.openEdit(${m.id})" title="Редактировать">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="adm-icon-btn adm-icon-btn--danger"
                                                    onclick="MessagesTab.delete(${m.id}, '${Adm.escapeHtml((m.title || m.text).substring(0, 40)).replace(/'/g, "\\'")}')"
                                                    title="Удалить">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    /* Предпросмотр */
    function updatePreview() {
        const type = document.getElementById('msgType').value;
        const title = document.getElementById('msgTitle').value.trim();
        const text = document.getElementById('msgText').value.trim();
        const t = TYPES[type] || TYPES.info;

        const p = document.getElementById('msgPreview');
        p.style.background = t[3];
        p.style.borderColor = t[4];
        p.style.color = t[2];

        p.innerHTML = `
            <div style="display:flex;align-items:flex-start;gap:12px;">
                <i class="bi ${t[1]}" style="font-size:1.15rem;flex-shrink:0;margin-top:1px;"></i>
                <div>
                    ${title ? `<div style="font-weight:700;margin-bottom:4px;">${Adm.escapeHtml(title)}</div>` : ''}
                    <div>${Adm.escapeHtml(text || 'Так будет выглядеть сообщение на странице.').replace(/\n/g, '<br>')}</div>
                </div>
            </div>
        `;
    }

    ['msgType','msgTitle','msgText'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', updatePreview);
        if (el) el.addEventListener('change', updatePreview);
    });

    const MessagesTab = {
        resetFilters() {
            state.search = '';
            state.section = '';
            state.status = '';
            state.page = 1;
            document.getElementById('messagesSearch').value = '';
            document.getElementById('messagesSectionFilter').value = '';
            document.getElementById('messagesStatusFilter').value = '';
            load(true);
        },

        openCreate() {
            document.getElementById('messageModalTitle').textContent = 'Новое сообщение';
            document.getElementById('msgId').value = '';
            document.getElementById('messageForm').reset();
            document.getElementById('msgActive').checked = true;
            document.getElementById('msgDismissible').checked = true;
            document.getElementById('msgSort').value = 0;
            updatePreview();
            Adm.openModal('messageEditModal');
        },

        async openEdit(id) {
            try {
                const res = await Adm.apiCall('message_get', { id });
                const m = res.message;
                document.getElementById('messageModalTitle').textContent = 'Редактировать сообщение';
                document.getElementById('msgId').value = m.id;
                document.getElementById('msgSection').value = m.section;
                document.getElementById('msgType').value = m.type;
                document.getElementById('msgTitle').value = m.title || '';
                document.getElementById('msgText').value = m.text || '';
                document.getElementById('msgSort').value = m.sort_order || 0;
                document.getElementById('msgActive').checked = parseInt(m.is_active, 10) === 1;
                document.getElementById('msgDismissible').checked = parseInt(m.dismissible, 10) === 1;
                updatePreview();
                Adm.openModal('messageEditModal');
            } catch (e) { Adm.showToast(e.message, true); }
        },

        async submit(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;

            const fd = new FormData(e.target);
            const data = Object.fromEntries(fd.entries());
            data.is_active   = document.getElementById('msgActive').checked ? 1 : 0;
            data.dismissible = document.getElementById('msgDismissible').checked ? 1 : 0;

            const action = data.id ? 'message_update' : 'message_create';

            try {
                await Adm.apiCall(action, data);
                Adm.showToast(data.id ? 'Сообщение обновлено' : 'Сообщение создано');
                Adm.closeModal('messageEditModal');
                load(true);
            } catch (err) { Adm.showToast(err.message, true); }
            finally { btn.disabled = false; }
        },

        async toggle(id, currentActive) {
            try {
                await Adm.apiCall('message_toggle', { id, active: currentActive ? 0 : 1 });
                Adm.showToast(currentActive ? 'Сообщение скрыто' : 'Сообщение включено');
                load(true);
            } catch (e) { Adm.showToast(e.message, true); }
        },

        delete(id, preview) {
            Adm.confirmDialog(
                `Удалить сообщение?\n\n${preview}...`,
                async () => {
                    try {
                        await Adm.apiCall('message_delete', { id });
                        Adm.showToast('Сообщение удалено');
                        load(true);
                    } catch (e) { Adm.showToast(e.message, true); }
                }
            );
        },
    };

    window.MessagesTab = MessagesTab;

    document.getElementById('messagesSearch').addEventListener('input', Adm.debounce(e => {
        state.search = e.target.value.trim();
        state.page = 1;
        load(true);
    }, 300));

    document.getElementById('messagesSectionFilter').addEventListener('change', e => {
        state.section = e.target.value;
        state.page = 1;
        load(true);
    });
    document.getElementById('messagesStatusFilter').addEventListener('change', e => {
        state.status = e.target.value;
        state.page = 1;
        load(true);
    });

    Adm.registerTab('messages', {
        onInit: () => load(true),
    });
})();
</script>