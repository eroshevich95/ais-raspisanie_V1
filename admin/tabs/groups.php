<?php
/**
 * Вкладка «Группы».
 */
?>

<div class="adm-card">
    <div class="adm-card-header">
        <h3 class="adm-card-title">
            <i class="bi bi-people-fill"></i>
            Группы
        </h3>
        <div style="display:flex; gap:8px; align-items:center;">
            <span class="adm-card-badge" id="groupsTotalBadge">— записей</span>
            <button class="adm-btn adm-btn--primary adm-btn--sm" onclick="GroupsTab.openCreate()">
                <i class="bi bi-plus-circle-fill"></i> Добавить
            </button>
        </div>
    </div>

    <div class="adm-filters adm-filters--groups">
        <div>
            <div class="adm-filter-label"><i class="bi bi-search"></i> Поиск</div>
            <div class="adm-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" class="adm-input" id="groupsSearch"
                       placeholder="Название или институт..." autocomplete="off">
            </div>
        </div>
        <div>
            <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="GroupsTab.resetFilters()" style="height:42px;">
                <i class="bi bi-x-circle"></i> Сбросить
            </button>
        </div>
    </div>

    <div id="groupsTableWrap">
        <div class="adm-skeleton">
            <div class="adm-skeleton-row"></div>
            <div class="adm-skeleton-row"></div>
            <div class="adm-skeleton-row"></div>
        </div>
    </div>

    <div id="groupsPagination"></div>
</div>

<!-- Модалка: редактирование -->
<div class="adm-modal-backdrop" id="groupEditModal">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h5><i class="bi bi-pencil-square"></i> Редактировать группу</h5>
            <button class="adm-modal-close" onclick="Adm.closeModal('groupEditModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="groupEditForm" onsubmit="GroupsTab.submitEdit(event)">
            <div class="adm-modal-body">
                <input type="hidden" id="editGroupId">
                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-people-fill"></i> Название <span class="req">*</span></label>
                    <input type="text" name="name" id="editGroupName" class="adm-input" required>
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-building"></i> Институт</label>
                    <input type="text" name="institute" id="editGroupInstitute" class="adm-input"
                           placeholder="Например: ИИТМА">
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn--secondary" onclick="Adm.closeModal('groupEditModal')">
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
<div class="adm-modal-backdrop" id="groupCreateModal">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h5><i class="bi bi-plus-circle-fill"></i> Новая группа</h5>
            <button class="adm-modal-close" onclick="Adm.closeModal('groupCreateModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="groupCreateForm" onsubmit="GroupsTab.submitCreate(event)">
            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-people-fill"></i> Название <span class="req">*</span></label>
                    <input type="text" name="name" class="adm-input" required
                           placeholder="Например: ИСт-241">
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-building"></i> Институт</label>
                    <input type="text" name="institute" class="adm-input"
                           placeholder="Например: ИИТМА">
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn--secondary" onclick="Adm.closeModal('groupCreateModal')">
                    Отмена
                </button>
                <button type="submit" class="adm-btn adm-btn--primary">
                    <i class="bi bi-check-circle-fill"></i> Создать
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    'use strict';

    const state = {
        page: 1,
        perPage: 30,
        sort: 'name',
        dir: 'asc',
        search: '',
        loaded: false,
    };

    async function load(force = false) {
        if (state.loaded && !force) return;
        state.loaded = true;

        const wrap = document.getElementById('groupsTableWrap');
        wrap.innerHTML = Adm.skeleton(4);

        try {
            const res = await Adm.apiCall('groups_list', {
                page: state.page,
                per_page: state.perPage,
                sort: state.sort,
                dir: state.dir,
                search: state.search,
            });

            document.getElementById('groupsTotalBadge').textContent = res.total + ' записей';
            document.getElementById('tabCountGroups').textContent = res.total;

            renderTable(res.rows);
            Adm.renderPagination(
                document.getElementById('groupsPagination'),
                res.page, res.pages,
                p => { state.page = p; load(true); window.scrollTo({top:0, behavior:'smooth'}); }
            );
        } catch (e) {
            wrap.innerHTML = Adm.emptyState('bi-exclamation-triangle', 'Ошибка', e.message);
        }
    }

    function renderTable(rows) {
        const wrap = document.getElementById('groupsTableWrap');
        if (!rows.length) {
            wrap.innerHTML = Adm.emptyState('bi-people', 'Не найдено', 'Попробуйте изменить фильтры');
            return;
        }

        const sortIcon = (field) => state.sort === field
            ? `<i class="bi bi-arrow-${state.dir === 'asc' ? 'up' : 'down'}"></i>`
            : '';

        wrap.innerHTML = `
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th><a href="#" onclick="GroupsTab.sort('name');return false;" class="${state.sort==='name'?'active':''}">
                                Название ${sortIcon('name')}
                            </a></th>
                            <th><a href="#" onclick="GroupsTab.sort('institute');return false;" class="${state.sort==='institute'?'active':''}">
                                Институт ${sortIcon('institute')}
                            </a></th>
                            <th class="center" style="width:120px;">
                                <a href="#" onclick="GroupsTab.sort('students');return false;" class="${state.sort==='students'?'active':''}">
                                    Студентов ${sortIcon('students')}
                                </a>
                            </th>
                            <th class="center" style="width:100px;">
                                <a href="#" onclick="GroupsTab.sort('lessons');return false;" class="${state.sort==='lessons'?'active':''}">
                                    Занятий ${sortIcon('lessons')}
                                </a>
                            </th>
                            <th class="center" style="width:100px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.map(g => `
                            <tr data-id="${g.id}">
                                <td>
                                    <div class="adm-user-cell">
                                        <div class="adm-user-avatar purple" style="width:38px;height:38px;font-size:0.75rem;">
                                            ${Adm.escapeHtml((g.name || '?').substring(0, 3).toUpperCase())}
                                        </div>
                                        <div class="adm-user-info">
                                            <div class="adm-user-name">${Adm.escapeHtml(g.name)}</div>
                                            ${g.api_group_id ? `<div class="adm-user-login">API: ${Adm.escapeHtml(g.api_group_id)}</div>` : ''}
                                        </div>
                                    </div>
                                </td>
                                <td style="font-size:0.82rem;color:var(--adm-text-soft);">
                                    ${g.institute
                                        ? Adm.escapeHtml(g.institute)
                                        : '<span class="adm-status adm-status--type" style="font-size:0.68rem;">не указан</span>'}
                                </td>
                                <td class="num">
                                    <span class="adm-status adm-status--active" style="font-weight:700;">
                                        <i class="bi bi-mortarboard-fill"></i> ${g.students_cnt}
                                    </span>
                                </td>
                                <td class="num">
                                    <span class="adm-status adm-status--type" style="font-weight:700;">
                                        <i class="bi bi-calendar-week"></i> ${g.lessons_cnt}
                                    </span>
                                </td>
                                <td class="num">
                                    <div class="adm-row-actions">
                                        <a class="adm-icon-btn adm-icon-btn--primary"
                                           href="../group_view.php?id=${g.id}" target="_blank"
                                           title="Открыть группу">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                        <button class="adm-icon-btn adm-icon-btn--primary"
                                                onclick="GroupsTab.openEdit(${g.id})" title="Редактировать">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="adm-icon-btn adm-icon-btn--danger"
                                                onclick="GroupsTab.delete(${g.id}, '${Adm.escapeHtml(g.name).replace(/'/g, "\\'")}', ${g.students_cnt}, ${g.lessons_cnt})"
                                                title="Удалить">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    const GroupsTab = {
        sort(field) {
            if (state.sort === field) state.dir = state.dir === 'asc' ? 'desc' : 'asc';
            else { state.sort = field; state.dir = 'asc'; }
            state.page = 1;
            load(true);
        },
        resetFilters() {
            state.search = '';
            state.page = 1;
            document.getElementById('groupsSearch').value = '';
            load(true);
        },
        openCreate() {
            document.getElementById('groupCreateForm').reset();
            Adm.openModal('groupCreateModal');
        },
        async submitCreate(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            try {
                const fd = new FormData(e.target);
                await Adm.apiCall('group_create', Object.fromEntries(fd.entries()));
                Adm.showToast('Группа создана');
                Adm.closeModal('groupCreateModal');
                load(true);
            } catch (err) { Adm.showToast(err.message, true); }
            finally { btn.disabled = false; }
        },
        async openEdit(id) {
            try {
                const res = await Adm.apiCall('group_get', { id });
                const g = res.group;
                document.getElementById('editGroupId').value = g.id;
                document.getElementById('editGroupName').value = g.name || '';
                document.getElementById('editGroupInstitute').value = g.institute || '';
                Adm.openModal('groupEditModal');
            } catch (e) { Adm.showToast(e.message, true); }
        },
        async submitEdit(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            try {
                await Adm.apiCall('group_update', {
                    id:        document.getElementById('editGroupId').value,
                    name:      document.getElementById('editGroupName').value,
                    institute: document.getElementById('editGroupInstitute').value,
                });
                Adm.showToast('Группа обновлена');
                Adm.closeModal('groupEditModal');
                load(true);
            } catch (err) { Adm.showToast(err.message, true); }
            finally { btn.disabled = false; }
        },
        delete(id, name, studentsCnt, lessonsCnt) {
            if (lessonsCnt > 0) {
                Adm.showToast(`Нельзя удалить: у группы ${lessonsCnt} занятий`, true);
                return;
            }
            const warn = studentsCnt > 0
                ? `\n\nВнимание: будет удалено ${studentsCnt} студентов!`
                : '';
            Adm.confirmDialog(
                `Удалить группу "${name}"?${warn}`,
                async () => {
                    try {
                        await Adm.apiCall('group_delete', { id });
                        Adm.showToast('Группа удалена');
                        load(true);
                    } catch (e) { Adm.showToast(e.message, true); }
                }
            );
        },
    };

    window.GroupsTab = GroupsTab;

    document.getElementById('groupsSearch').addEventListener('input', Adm.debounce(e => {
        state.search = e.target.value.trim();
        state.page = 1;
        load(true);
    }, 300));

    Adm.registerTab('groups', {
        onInit: () => load(true),
    });
})();
</script>