<?php
/**
 * Вкладка «Студенты».
 */
?>

<div class="adm-card">
    <div class="adm-card-header">
        <h3 class="adm-card-title">
            <i class="bi bi-mortarboard-fill"></i>
            Студенты
        </h3>
        <div style="display:flex; gap:8px; align-items:center;">
            <span class="adm-card-badge" id="studentsTotalBadge">— записей</span>
        </div>
    </div>

    <div class="adm-filters adm-filters--students">
        <div>
            <div class="adm-filter-label"><i class="bi bi-search"></i> Поиск</div>
            <div class="adm-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" class="adm-input" id="studentsSearch"
                       placeholder="ФИО или зачётка..." autocomplete="off">
            </div>
        </div>
        <div>
            <div class="adm-filter-label"><i class="bi bi-people-fill"></i> Группа</div>
            <select class="adm-select" id="studentsGroupFilter">
                <option value="">Все группы</option>
            </select>
        </div>
        <div>
            <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="StudentsTab.resetFilters()" style="height:42px;">
                <i class="bi bi-x-circle"></i> Сбросить
            </button>
        </div>
    </div>

    <div class="adm-bulk-bar" id="studentsBulkBar">
        <div class="adm-bulk-count">
            <i class="bi bi-check2-circle"></i>
            Выбрано: <span id="studentsBulkCount">0</span>
        </div>
        <div class="adm-bulk-actions">
            <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="StudentsTab.openBulkMove()">
                <i class="bi bi-arrow-left-right"></i> Перевести в группу
            </button>
            <button class="adm-btn adm-btn--secondary adm-btn--sm" onclick="StudentsTab.clearSelection()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <div id="studentsTableWrap">
        <div class="adm-skeleton">
            <div class="adm-skeleton-row"></div>
            <div class="adm-skeleton-row"></div>
            <div class="adm-skeleton-row"></div>
        </div>
    </div>

    <div id="studentsPagination"></div>
</div>

<!-- Модалка: редактирование -->
<div class="adm-modal-backdrop" id="studentEditModal">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h5><i class="bi bi-pencil-square"></i> Редактировать студента</h5>
            <button class="adm-modal-close" onclick="Adm.closeModal('studentEditModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="studentEditForm" onsubmit="StudentsTab.submitEdit(event)">
            <div class="adm-modal-body">
                <input type="hidden" id="editStudentId">
                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-person-fill"></i> ФИО <span class="req">*</span></label>
                    <input type="text" name="full_name" id="editStudentFullName" class="adm-input" required>
                </div>
                <div class="adm-form-row">
                    <div class="adm-form-group">
                        <label class="adm-form-label"><i class="bi bi-card-text"></i> Зачётка</label>
                        <input type="text" name="zachetka" id="editStudentZachetka" class="adm-input">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-form-label"><i class="bi bi-diagram-3"></i> Подгруппа</label>
                        <select name="subgroup" id="editStudentSubgroup" class="adm-select">
                            <option value="">— нет —</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                        </select>
                    </div>
                </div>
                <div class="adm-form-group">
                    <label class="adm-form-label"><i class="bi bi-people-fill"></i> Группа <span class="req">*</span></label>
                    <select name="group_id" id="editStudentGroupId" class="adm-select" required>
                        <option value="">— выберите —</option>
                    </select>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn--secondary" onclick="Adm.closeModal('studentEditModal')">
                    Отмена
                </button>
                <button type="submit" class="adm-btn adm-btn--primary">
                    <i class="bi bi-check-circle-fill"></i> Сохранить
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Модалка: массовый перевод -->
<div class="adm-modal-backdrop" id="studentsBulkMoveModal">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h5><i class="bi bi-arrow-left-right"></i> Перевести студентов</h5>
            <button class="adm-modal-close" onclick="Adm.closeModal('studentsBulkMoveModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="adm-modal-body">
            <div class="adm-form-group">
                <label class="adm-form-label"><i class="bi bi-people-fill"></i> В группу</label>
                <select class="adm-select" id="bulkMoveGroupId">
                    <option value="">— выберите группу —</option>
                </select>
            </div>
            <div class="adm-form-hint">
                <i class="bi bi-info-circle"></i>
                <span id="bulkMoveHint">Выбрано: 0 студентов</span>
            </div>
        </div>
        <div class="adm-modal-footer">
            <button type="button" class="adm-btn adm-btn--secondary" onclick="Adm.closeModal('studentsBulkMoveModal')">
                Отмена
            </button>
            <button type="button" class="adm-btn adm-btn--primary" onclick="StudentsTab.applyBulkMove()">
                <i class="bi bi-check-circle-fill"></i> Перевести
            </button>
        </div>
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
        groupId: 0,
        selected: new Set(),
        loaded: false,
        groupsLoaded: false,
    };

    let groupsCache = [];

    function fillGroupsSelects(groups) {
        groupsCache = groups;

        const filterSel = document.getElementById('studentsGroupFilter');
        filterSel.innerHTML = '<option value="">Все группы</option>'
            + groups.map(g => `<option value="${g.id}">${Adm.escapeHtml(g.name)}</option>`).join('');

        const editSel = document.getElementById('editStudentGroupId');
        editSel.innerHTML = '<option value="">— выберите —</option>'
            + groups.map(g => `<option value="${g.id}">${Adm.escapeHtml(g.name)}</option>`).join('');

        const bulkSel = document.getElementById('bulkMoveGroupId');
        bulkSel.innerHTML = '<option value="">— выберите группу —</option>'
            + groups.map(g => `<option value="${g.id}">${Adm.escapeHtml(g.name)}</option>`).join('');

        state.groupsLoaded = true;
    }

    async function load(force = false) {
        if (state.loaded && !force) return;
        state.loaded = true;

        const wrap = document.getElementById('studentsTableWrap');
        wrap.innerHTML = Adm.skeleton(6);

        try {
            const res = await Adm.apiCall('students_list', {
                page: state.page,
                per_page: state.perPage,
                sort: state.sort,
                dir: state.dir,
                search: state.search,
                group_id: state.groupId,
            });

            if (res.groups && !state.groupsLoaded) {
                fillGroupsSelects(res.groups);
            }

            document.getElementById('studentsTotalBadge').textContent = res.total + ' записей';
            document.getElementById('tabCountStudents').textContent = res.total;

            renderTable(res.rows);
            Adm.renderPagination(
                document.getElementById('studentsPagination'),
                res.page, res.pages,
                p => { state.page = p; load(true); window.scrollTo({top:0, behavior:'smooth'}); }
            );
        } catch (e) {
            wrap.innerHTML = Adm.emptyState('bi-exclamation-triangle', 'Ошибка', e.message);
        }
    }

    function renderTable(rows) {
        const wrap = document.getElementById('studentsTableWrap');
        if (!rows.length) {
            wrap.innerHTML = Adm.emptyState('bi-mortarboard', 'Не найдено', 'Попробуйте изменить фильтры');
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
                            <th style="width:44px;">
                                <input type="checkbox" class="adm-checkbox" id="selectAllStudents"
                                       onchange="StudentsTab.toggleAll(this)">
                            </th>
                            <th><a href="#" onclick="StudentsTab.sort('name');return false;" class="${state.sort==='name'?'active':''}">
                                Студент ${sortIcon('name')}
                            </a></th>
                            <th style="width:110px;">
                                <a href="#" onclick="StudentsTab.sort('zachetka');return false;" class="${state.sort==='zachetka'?'active':''}">
                                    Зачётка ${sortIcon('zachetka')}
                                </a>
                            </th>
                            <th style="width:140px;">
                                <a href="#" onclick="StudentsTab.sort('group');return false;" class="${state.sort==='group'?'active':''}">
                                    Группа ${sortIcon('group')}
                                </a>
                            </th>
                            <th class="center" style="width:80px;">Подгр.</th>
                            <th class="center" style="width:100px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.map(s => {
                            const initials = (s.full_name || 'S').split(' ').slice(0,2)
                                .map(w => w.charAt(0)).join('').toUpperCase();
                            const isSelected = state.selected.has(s.id);
                            const rowCls = isSelected ? 'adm-row-selected' : '';
                            const sg = s.subgroup || '';

                            return `
                                <tr class="${rowCls}" data-id="${s.id}">
                                    <td class="num">
                                        <input type="checkbox" class="adm-checkbox" data-student-id="${s.id}"
                                               ${isSelected ? 'checked' : ''}
                                               onchange="StudentsTab.toggle(${s.id}, this)">
                                    </td>
                                    <td>
                                        <div class="adm-user-cell">
                                            <div class="adm-user-avatar" style="width:38px;height:38px;font-size:0.75rem;">
                                                ${Adm.escapeHtml(initials)}
                                            </div>
                                            <div class="adm-user-info">
                                                <div class="adm-user-name">${Adm.escapeHtml(s.full_name)}</div>
                                                ${s.login ? `<div class="adm-user-login">${Adm.escapeHtml(s.login)}</div>` : ''}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        ${s.zachetka
                                            ? `<code style="background:rgba(0,113,227,0.08);color:var(--adm-accent);padding:2px 8px;border-radius:6px;font-size:0.75rem;font-family:'SF Mono',monospace;">${Adm.escapeHtml(s.zachetka)}</code>`
                                            : '<span style="color:var(--adm-text-soft);">—</span>'}
                                    </td>
                                    <td style="font-size:0.82rem;font-weight:600;color:var(--adm-text);">
                                        ${Adm.escapeHtml(s.group_name || '—')}
                                    </td>
                                    <td class="num">
                                        ${sg
                                            ? `<span class="adm-status adm-status--type" style="font-weight:700;">${Adm.escapeHtml(sg)}</span>`
                                            : '<span style="color:var(--adm-text-soft);">—</span>'}
                                    </td>
                                    <td class="num">
                                        <div class="adm-row-actions">
                                            <button class="adm-icon-btn adm-icon-btn--primary"
                                                    onclick="StudentsTab.openEdit(${s.id})" title="Редактировать">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="adm-icon-btn adm-icon-btn--danger"
                                                    onclick="StudentsTab.delete(${s.id}, '${Adm.escapeHtml(s.full_name).replace(/'/g, "\\'")}')"
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

        updateBulkBar();
    }

    function updateBulkBar() {
        const count = state.selected.size;
        document.getElementById('studentsBulkCount').textContent = count;
        document.getElementById('studentsBulkBar').classList.toggle('show', count > 0);
    }

    const StudentsTab = {
        sort(field) {
            if (state.sort === field) state.dir = state.dir === 'asc' ? 'desc' : 'asc';
            else { state.sort = field; state.dir = 'asc'; }
            state.page = 1;
            load(true);
        },
        resetFilters() {
            state.search = '';
            state.groupId = 0;
            state.page = 1;
            document.getElementById('studentsSearch').value = '';
            document.getElementById('studentsGroupFilter').value = '';
            load(true);
        },
        toggle(id, cb) {
            if (cb.checked) state.selected.add(id);
            else state.selected.delete(id);
            cb.closest('tr').classList.toggle('adm-row-selected', cb.checked);
            updateBulkBar();
        },
        toggleAll(master) {
            document.querySelectorAll('.adm-checkbox[data-student-id]').forEach(cb => {
                const id = parseInt(cb.dataset.studentId, 10);
                cb.checked = master.checked;
                cb.closest('tr').classList.toggle('adm-row-selected', master.checked);
                if (master.checked) state.selected.add(id);
                else state.selected.delete(id);
            });
            updateBulkBar();
        },
        clearSelection() {
            state.selected.clear();
            document.querySelectorAll('.adm-checkbox[data-student-id]').forEach(cb => {
                cb.checked = false;
                cb.closest('tr')?.classList.remove('adm-row-selected');
            });
            const master = document.getElementById('selectAllStudents');
            if (master) master.checked = false;
            updateBulkBar();
        },
        async openEdit(id) {
            try {
                const res = await Adm.apiCall('student_get', { id });
                const s = res.student;
                document.getElementById('editStudentId').value = s.id;
                document.getElementById('editStudentFullName').value = s.full_name || '';
                document.getElementById('editStudentZachetka').value = s.zachetka || '';
                document.getElementById('editStudentSubgroup').value = s.subgroup || '';
                document.getElementById('editStudentGroupId').value = s.group_id || '';
                Adm.openModal('studentEditModal');
            } catch (e) { Adm.showToast(e.message, true); }
        },
        async submitEdit(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            try {
                await Adm.apiCall('student_update', {
                    id:        document.getElementById('editStudentId').value,
                    full_name: document.getElementById('editStudentFullName').value,
                    zachetka:  document.getElementById('editStudentZachetka').value,
                    subgroup:  document.getElementById('editStudentSubgroup').value,
                    group_id:  document.getElementById('editStudentGroupId').value,
                });
                Adm.showToast('Студент обновлён');
                Adm.closeModal('studentEditModal');
                load(true);
            } catch (err) { Adm.showToast(err.message, true); }
            finally { btn.disabled = false; }
        },
        delete(id, name) {
            Adm.confirmDialog(
                `Удалить студента "${name}"?\n\nВсе отметки посещаемости и работы также будут удалены.`,
                async () => {
                    try {
                        await Adm.apiCall('student_delete', { id });
                        Adm.showToast('Студент удалён');
                        load(true);
                    } catch (e) { Adm.showToast(e.message, true); }
                }
            );
        },
        openBulkMove() {
            if (!state.selected.size) return;
            document.getElementById('bulkMoveHint').textContent =
                `Выбрано: ${state.selected.size} студентов`;
            Adm.openModal('studentsBulkMoveModal');
        },
        async applyBulkMove() {
            const groupId = document.getElementById('bulkMoveGroupId').value;
            if (!groupId) { Adm.showToast('Выберите группу', true); return; }

            const ids = Array.from(state.selected);
            Adm.closeModal('studentsBulkMoveModal');

            try {
                await Adm.apiCall('students_bulk_move', {
                    ids: ids,
                    group_id: groupId,
                });
                Adm.showToast(`Переведено: ${ids.length}`);
                StudentsTab.clearSelection();
                load(true);
            } catch (e) { Adm.showToast(e.message, true); }
        },
    };

    window.StudentsTab = StudentsTab;

    document.getElementById('studentsSearch').addEventListener('input', Adm.debounce(e => {
        state.search = e.target.value.trim();
        state.page = 1;
        load(true);
    }, 300));

    document.getElementById('studentsGroupFilter').addEventListener('change', e => {
        state.groupId = parseInt(e.target.value, 10) || 0;
        state.page = 1;
        load(true);
    });

    Adm.registerTab('students', {
        onInit: () => load(true),
    });
})();
</script>