<?php
/**
 * Вкладка «Пользователи».
 */
?>

<div class="adm-card">
    <div class="adm-card-header">
        <h3 class="adm-card-title">
            <i class="bi bi-people-fill"></i>
            Пользователи системы
        </h3>
        <div style="display:flex; gap:8px; align-items:center;">
            <span class="adm-card-badge" id="usersTotalBadge">— записей</span>
            <button class="adm-btn adm-btn--primary adm-btn--sm" onclick="UsersTab.openCreate()">
                <i class="bi bi-person-plus-fill"></i> Добавить
            </button>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="adm-filters adm-filters--users">
        <div>
            <div class="adm-filter-label"><i class="bi bi-search"></i> Поиск</div>
            <div class="adm-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" class="adm-input" id="usersSearch"
                       placeholder="ФИО, логин или email..." autocomplete="off">
            </div>
        </div>
        <div>
            <div class="adm-filter-label"><i class="bi bi-shield-check"></i> Роль</div>
            <select class="adm-select" id="usersRoleFilter">
                <option value="">Все роли</option>
                <option value="teacher">Преподаватель</option>
                <option value="head_of_department">Зав. кафедрой</option>
                <option value="administration">Администрация</option>
                <option value="admin">Администратор</option>
            </select>
        </div>
        <div>
            <div class="adm-filter-label"><i class="bi bi-circle-fill"></i> Статус</div>
            <select class="adm-select" id="usersStatusFilter">
                <option value="">Все</option>
                <option value="active">Активные</option>
                <option value="blocked">Заблокированные</option>
            </select>
        </div>
        <div>
            <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="UsersTab.resetFilters()" style="height:42px;">
                <i class="bi bi-x-circle"></i> Сбросить
            </button>
        </div>
    </div>

    <!-- Массовые действия -->
    <div class="adm-bulk-bar" id="usersBulkBar">
        <div class="adm-bulk-count">
            <i class="bi bi-check2-circle"></i>
            Выбрано: <span id="usersBulkCount">0</span>
        </div>
        <div class="adm-bulk-actions">
            <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="UsersTab.bulkAction('unblock')">
                <i class="bi bi-unlock-fill"></i> Разблокировать
            </button>
            <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="UsersTab.bulkAction('block')">
                <i class="bi bi-lock-fill"></i> Заблокировать
            </button>
            <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="UsersTab.openBulkRole()">
                <i class="bi bi-shield-check"></i> Сменить роль
            </button>
            <button class="adm-btn adm-btn--danger adm-btn--sm" onclick="UsersTab.bulkAction('delete')">
                <i class="bi bi-trash"></i> Удалить
            </button>
            <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="UsersTab.clearSelection()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <!-- Таблица -->
    <div id="usersTableWrap">
        <div class="adm-skeleton">
            <div class="adm-skeleton-row"></div>
            <div class="adm-skeleton-row"></div>
            <div class="adm-skeleton-row"></div>
            <div class="adm-skeleton-row"></div>
            <div class="adm-skeleton-row"></div>
        </div>
    </div>

    <!-- Пагинация -->
    <div id="usersPagination"></div>
</div>

<!-- Модалка: редактирование -->
<div class="adm-modal-backdrop" id="userEditModal">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h5><i class="bi bi-pencil-square"></i> Редактировать пользователя</h5>
            <button class="adm-modal-close" onclick="Adm.closeModal('userEditModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="userEditForm" onsubmit="UsersTab.submitEdit(event)">
            <div class="adm-modal-body">
                <input type="hidden" id="editUserId">
                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-person-fill"></i> ФИО <span class="req">*</span></label>
                    <input type="text" name="full_name" id="editUserFullName" class="adm-input" required>
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-envelope-fill"></i> Email</label>
                    <input type="email" name="email" id="editUserEmail" class="adm-input">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-shield-fill-check"></i> Роль</label>
                    <select name="role" id="editUserRole" class="adm-select">
                        <option value="teacher">Преподаватель</option>
                        <option value="head_of_department">Зав. кафедрой</option>
                        <option value="administration">Администрация</option>
                        <option value="admin">Администратор</option>
                    </select>
                </div>
                <div class="adm-form-hint">
                    <i class="bi bi-info-circle"></i>
                    Логин: <code id="editUserLogin">—</code>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn--secondary" onclick="Adm.closeModal('userEditModal')">
                    Отмена
                </button>
                <button type="submit" class="adm-btn adm-btn--primary">
                    <i class="bi bi-check-circle-fill"></i> Сохранить
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Модалка: создание -->
<div class="adm-modal-backdrop" id="userCreateModal">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h5><i class="bi bi-person-plus-fill"></i> Новый пользователь</h5>
            <button class="adm-modal-close" onclick="Adm.closeModal('userCreateModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="userCreateForm" onsubmit="UsersTab.submitCreate(event)">
            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-person-fill"></i> ФИО <span class="req">*</span></label>
                    <input type="text" name="full_name" class="adm-input" required
                           placeholder="Иванов Иван Иванович">
                </div>
                <div class="adm-form-row">
                    <div class="adm-form-group">
                        <label class="adm-form-label"><i class="bi bi-key-fill"></i> Логин <span class="req">*</span></label>
                        <input type="text" name="login" class="adm-input" required
                               pattern="[a-zA-Z0-9_.\-]{4,}" placeholder="ivanov">
                        <div class="adm-form-hint">Латиница, цифры, _ . - (мин. 4)</div>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-form-label"><i class="bi bi-shield-fill-check"></i> Роль <span class="req">*</span></label>
                        <select name="role" class="adm-select" required>
                            <option value="teacher">Преподаватель</option>
                            <option value="head_of_department">Зав. кафедрой</option>
                            <option value="administration">Администрация</option>
                            <option value="admin">Администратор</option>
                        </select>
                    </div>
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-envelope-fill"></i> Email</label>
                    <input type="email" name="email" class="adm-input" placeholder="ivanov@kuzstu.ru">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-lock-fill"></i> Пароль <span class="req">*</span></label>
                    <input type="text" name="password" class="adm-input" required minlength="6">
                    <div class="adm-form-hint">
                        <i class="bi bi-lightbulb"></i>
                        Сгенерирован случайный. Скопируйте и передайте пользователю.
                    </div>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn--secondary" onclick="Adm.closeModal('userCreateModal')">
                    Отмена
                </button>
                <button type="submit" class="adm-btn adm-btn--primary">
                    <i class="bi bi-check-circle-fill"></i> Создать
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Модалка: массовая смена роли -->
<div class="adm-modal-backdrop" id="bulkRoleModal">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h5><i class="bi bi-shield-check"></i> Сменить роль</h5>
            <button class="adm-modal-close" onclick="Adm.closeModal('bulkRoleModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="adm-modal-body">
            <div class="adm-form-group">
                <label class="adm-form-label"><i class="bi bi-shield-fill-check"></i> Новая роль</label>
                <select class="adm-select" id="bulkRoleValue">
                    <option value="teacher">Преподаватель</option>
                    <option value="head_of_department">Зав. кафедрой</option>
                    <option value="administration">Администрация</option>
                    <option value="admin">Администратор</option>
                </select>
            </div>
            <div class="adm-form-hint">
                <i class="bi bi-exclamation-triangle" style="color: var(--adm-orange);"></i>
                Роль будет установлена для всех выбранных пользователей.
            </div>
        </div>
        <div class="adm-modal-footer">
            <button type="button" class="adm-btn adm-btn--secondary" onclick="Adm.closeModal('bulkRoleModal')">
                Отмена
            </button>
            <button type="button" class="adm-btn adm-btn--primary" onclick="UsersTab.applyBulkRole()">
                <i class="bi bi-check-circle-fill"></i> Применить
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    const state = {
        page: 1,
        perPage: 25,
        sort: 'name',
        dir: 'asc',
        search: '',
        role: '',
        status: '',
        selected: new Set(),
        loaded: false,
    };

    async function load(force = false) {
        if (state.loaded && !force) return;
        state.loaded = true;

        const wrap = document.getElementById('usersTableWrap');
        wrap.innerHTML = Adm.skeleton(6);

        try {
            const res = await Adm.apiCall('users_list', {
                page: state.page,
                per_page: state.perPage,
                sort: state.sort,
                dir: state.dir,
                search: state.search,
                role: state.role,
                status: state.status,
            });

            document.getElementById('usersTotalBadge').textContent = res.total + ' записей';
            document.getElementById('tabCountUsers').textContent = res.total;

            renderTable(res.rows);
            Adm.renderPagination(
                document.getElementById('usersPagination'),
                res.page, res.pages,
                p => { state.page = p; load(true); window.scrollTo({top:0, behavior:'smooth'}); }
            );
        } catch (e) {
            wrap.innerHTML = Adm.emptyState('bi-exclamation-triangle', 'Ошибка', e.message);
        }
    }

    function renderTable(rows) {
        const wrap = document.getElementById('usersTableWrap');
        if (!rows.length) {
            wrap.innerHTML = Adm.emptyState('bi-people', 'Не найдено', 'Попробуйте изменить фильтры');
            return;
        }

        const sortIcon = (field) => state.sort === field
            ? `<i class="bi bi-arrow-${state.dir === 'asc' ? 'up' : 'down'}"></i>`
            : '';

        const html = `
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th style="width:44px;">
                                <input type="checkbox" class="adm-checkbox" id="selectAllUsers"
                                       onchange="UsersTab.toggleAll(this)">
                            </th>
                            <th><a href="#" onclick="UsersTab.sort('name');return false;" class="${state.sort==='name'?'active':''}">
                                Пользователь ${sortIcon('name')}
                            </a></th>
                            <th><a href="#" onclick="UsersTab.sort('login');return false;" class="${state.sort==='login'?'active':''}">
                                Логин ${sortIcon('login')}
                            </a></th>
                            <th>Кафедра</th>
                            <th class="center">
                                <a href="#" onclick="UsersTab.sort('role');return false;" class="${state.sort==='role'?'active':''}">
                                    Роль ${sortIcon('role')}
                                </a>
                            </th>
                            <th class="center">Статус</th>
                            <th class="center">
                                <a href="#" onclick="UsersTab.sort('last_login');return false;" class="${state.sort==='last_login'?'active':''}">
                                    Последний вход ${sortIcon('last_login')}
                                </a>
                            </th>
                            <th class="center" style="width:170px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.map(u => {
                            const initials = (u.full_name || 'U').split(' ').slice(0,2)
                                .map(w => w.charAt(0)).join('').toUpperCase();
                            const isBlocked = !u.is_active;
                            const isSelected = state.selected.has(u.id);
                            const rowCls = [isBlocked ? 'adm-row-blocked' : '', isSelected ? 'adm-row-selected' : '']
                                .filter(Boolean).join(' ');

                            return `
                                <tr class="${rowCls}" data-id="${u.id}">
                                    <td class="num">
                                        <input type="checkbox" class="adm-checkbox" data-user-id="${u.id}"
                                               ${isSelected ? 'checked' : ''}
                                               onchange="UsersTab.toggle(${u.id}, this)">
                                    </td>
                                    <td>
                                        <div class="adm-user-cell">
                                            <div class="adm-user-avatar ${isBlocked ? 'blocked' : ''}">
                                                ${Adm.escapeHtml(initials)}
                                            </div>
                                            <div class="adm-user-info">
                                                <div class="adm-user-name">${Adm.escapeHtml(u.full_name)}</div>
                                                <div class="adm-user-login">${Adm.escapeHtml(u.email || u.login)}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <code style="background:rgba(0,113,227,0.08);color:var(--adm-accent);padding:2px 8px;border-radius:6px;font-size:0.75rem;font-family:'SF Mono',monospace;">
                                            ${Adm.escapeHtml(u.login)}
                                        </code>
                                    </td>
                                    <td style="font-size:0.82rem;color:var(--adm-text-soft);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        ${Adm.escapeHtml(u.department_name || u.department || '—')}
                                    </td>
                                    <td class="num">${Adm.roleBadge(u.role)}</td>
                                    <td class="num">
                                        <span class="adm-status adm-status--${isBlocked ? 'blocked' : 'active'}">
                                            <i class="bi bi-${isBlocked ? 'lock-fill' : 'check-circle-fill'}"></i>
                                            ${isBlocked ? 'Заблокирован' : 'Активен'}
                                        </span>
                                    </td>
                                    <td class="num" style="font-size:0.78rem;color:var(--adm-text-soft);">
                                        ${u.last_login ? Adm.fmtDate(u.last_login, 'date') : '—'}
                                    </td>
                                    <td class="num">
                                        <div class="adm-row-actions">
                                            <button class="adm-icon-btn adm-icon-btn--primary"
                                                    onclick="UsersTab.openEdit(${u.id})" title="Редактировать">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="adm-icon-btn adm-icon-btn--warning"
                                                    onclick="UsersTab.toggleActive(${u.id}, ${u.is_active ? 1 : 0})"
                                                    title="${u.is_active ? 'Заблокировать' : 'Разблокировать'}">
                                                <i class="bi bi-${u.is_active ? 'lock' : 'unlock'}"></i>
                                            </button>
                                            <button class="adm-icon-btn adm-icon-btn--success"
                                                    onclick="UsersTab.resetPassword(${u.id}, '${Adm.escapeHtml(u.full_name).replace(/'/g, "\\'")}')"
                                                    title="Сбросить пароль">
                                                <i class="bi bi-key"></i>
                                            </button>
                                            <button class="adm-icon-btn adm-icon-btn--danger"
                                                    onclick="UsersTab.delete(${u.id}, '${Adm.escapeHtml(u.full_name).replace(/'/g, "\\'")}')"
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

        wrap.innerHTML = html;
        updateBulkBar();
    }

    function updateBulkBar() {
        const count = state.selected.size;
        document.getElementById('usersBulkCount').textContent = count;
        document.getElementById('usersBulkBar').classList.toggle('show', count > 0);
    }

    /* -------- Публичные методы -------- */

    const UsersTab = {
        sort(field) {
            if (state.sort === field) state.dir = state.dir === 'asc' ? 'desc' : 'asc';
            else { state.sort = field; state.dir = 'asc'; }
            state.page = 1;
            load(true);
        },
        resetFilters() {
            state.search = '';
            state.role = '';
            state.status = '';
            state.page = 1;
            document.getElementById('usersSearch').value = '';
            document.getElementById('usersRoleFilter').value = '';
            document.getElementById('usersStatusFilter').value = '';
            load(true);
        },
        toggle(id, cb) {
            if (cb.checked) state.selected.add(id);
            else state.selected.delete(id);
            cb.closest('tr').classList.toggle('adm-row-selected', cb.checked);
            updateBulkBar();
        },
        toggleAll(master) {
            document.querySelectorAll('.adm-checkbox[data-user-id]').forEach(cb => {
                const id = parseInt(cb.dataset.userId, 10);
                cb.checked = master.checked;
                cb.closest('tr').classList.toggle('adm-row-selected', master.checked);
                if (master.checked) state.selected.add(id);
                else state.selected.delete(id);
            });
            updateBulkBar();
        },
        clearSelection() {
            state.selected.clear();
            document.querySelectorAll('.adm-checkbox[data-user-id]').forEach(cb => {
                cb.checked = false;
                cb.closest('tr')?.classList.remove('adm-row-selected');
            });
            const master = document.getElementById('selectAllUsers');
            if (master) master.checked = false;
            updateBulkBar();
        },

        async openEdit(id) {
            try {
                const res = await Adm.apiCall('user_get', { id });
                const u = res.user;
                document.getElementById('editUserId').value = u.id;
                document.getElementById('editUserFullName').value = u.full_name || '';
                document.getElementById('editUserEmail').value = u.email || '';
                document.getElementById('editUserRole').value = u.role || 'teacher';
                document.getElementById('editUserLogin').textContent = u.login || '—';
                Adm.openModal('userEditModal');
            } catch (e) {
                Adm.showToast(e.message, true);
            }
        },

        async submitEdit(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            try {
                await Adm.apiCall('user_update', {
                    id:        document.getElementById('editUserId').value,
                    full_name: document.getElementById('editUserFullName').value,
                    email:     document.getElementById('editUserEmail').value,
                    role:      document.getElementById('editUserRole').value,
                });
                Adm.showToast('Пользователь обновлён');
                Adm.closeModal('userEditModal');
                load(true);
            } catch (err) {
                Adm.showToast(err.message, true);
            } finally {
                btn.disabled = false;
            }
        },

        openCreate() {
            const form = document.getElementById('userCreateForm');
            form.reset();
            form.querySelector('input[name="password"]').value = Adm.genPassword(8);
            Adm.openModal('userCreateModal');
        },

        async submitCreate(e) {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            try {
                const fd = new FormData(form);
                const data = Object.fromEntries(fd.entries());
                await Adm.apiCall('user_create', data);
                Adm.showToast('Пользователь создан');
                Adm.closeModal('userCreateModal');
                load(true);
            } catch (err) {
                Adm.showToast(err.message, true);
            } finally {
                btn.disabled = false;
            }
        },

        toggleActive(id, currentActive) {
            const action = currentActive ? 'Заблокировать' : 'Разблокировать';
            Adm.confirmDialog(`${action} этого пользователя?`, async () => {
                try {
                    await Adm.apiCall('user_toggle_active', { id });
                    Adm.showToast(currentActive ? 'Заблокирован' : 'Разблокирован');
                    load(true);
                } catch (e) { Adm.showToast(e.message, true); }
            }, { danger: false });
        },

        resetPassword(id, name) {
            const newPwd = Adm.genPassword(8);
            Adm.confirmDialog(
                `Сбросить пароль для "${name}"?\n\nНовый пароль: ${newPwd}\n\nСкопируйте его и передайте пользователю.`,
                async () => {
                    try {
                        await Adm.apiCall('user_reset_password', { id, password: newPwd });
                        Adm.showToast('Пароль сброшен');
                        setTimeout(() => {
                            prompt(`Новый пароль для ${name}:\n\nСкопируйте и передайте пользователю:`, newPwd);
                        }, 400);
                    } catch (e) { Adm.showToast(e.message, true); }
                },
                { danger: false }
            );
        },

        delete(id, name) {
            Adm.confirmDialog(
                `Удалить пользователя "${name}"?\n\nЕсли у него есть занятия — удаление невозможно.`,
                async () => {
                    try {
                        await Adm.apiCall('user_delete', { id });
                        Adm.showToast('Пользователь удалён');
                        load(true);
                    } catch (e) { Adm.showToast(e.message, true); }
                }
            );
        },

        bulkAction(action) {
            const ids = Array.from(state.selected);
            if (!ids.length) return;

            const labels = {
                block:   `Заблокировать ${ids.length} пользователей?`,
                unblock: `Разблокировать ${ids.length} пользователей?`,
                delete:  `Удалить ${ids.length} пользователей?\n\nПользователи с занятиями будут пропущены.`,
            };

            Adm.confirmDialog(labels[action] || 'Подтвердить?', async () => {
                try {
                    await Adm.apiCall('users_bulk', {
                        ids: ids,
                        bulk_action: action,
                    });
                    Adm.showToast(`Готово: обработано ${ids.length}`);
                    UsersTab.clearSelection();
                    load(true);
                } catch (e) { Adm.showToast(e.message, true); }
            }, { danger: action === 'delete' });
        },

        openBulkRole() {
            if (!state.selected.size) return;
            Adm.openModal('bulkRoleModal');
        },

        async applyBulkRole() {
            const value = document.getElementById('bulkRoleValue').value;
            const ids = Array.from(state.selected);
            if (!ids.length) return;
            Adm.closeModal('bulkRoleModal');

            try {
                await Adm.apiCall('users_bulk', {
                    ids: ids,
                    bulk_action: 'role',
                    value: value,
                });
                Adm.showToast(`Роль изменена у ${ids.length}`);
                UsersTab.clearSelection();
                load(true);
            } catch (e) { Adm.showToast(e.message, true); }
        },
    };

    window.UsersTab = UsersTab;

    /* -------- Debounced поиск -------- */
    const searchInput = document.getElementById('usersSearch');
    searchInput.addEventListener('input', Adm.debounce(e => {
        state.search = e.target.value.trim();
        state.page = 1;
        load(true);
    }, 300));

    document.getElementById('usersRoleFilter').addEventListener('change', e => {
        state.role = e.target.value;
        state.page = 1;
        load(true);
    });
    document.getElementById('usersStatusFilter').addEventListener('change', e => {
        state.status = e.target.value;
        state.page = 1;
        load(true);
    });

    /* -------- Регистрация вкладки -------- */
    Adm.registerTab('users', {
        onInit: () => load(true),
    });
})();
</script>