// assets/js/register.js
const alertBox       = document.getElementById('alertBox');
const lastNameInput  = document.getElementById('lastName');
const searchBtn      = document.getElementById('searchBtn');
const teachersList   = document.getElementById('teachersList');
const step1          = document.getElementById('step1');
const registerForm   = document.getElementById('registerForm');
const backBtn        = document.getElementById('backBtn');

function showAlert(message, type = 'danger') {
    alertBox.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>`;
}

async function searchTeachers() {
    const lastName = lastNameInput.value.trim();
    if (lastName.length < 2) {
        showAlert('Введите минимум 2 символа фамилии');
        return;
    }

    searchBtn.disabled = true;
    searchBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Поиск...';
    teachersList.classList.add('d-none');
    teachersList.innerHTML = '';

    try {
        const res  = await fetch(`api/search_teacher.php?last_name=${encodeURIComponent(lastName)}`);
        const data = await res.json();

        if (data.error) {
            showAlert(data.error + (data.hint ? '<br><small>' + data.hint + '</small>' : ''));
            return;
        }

        if (!Array.isArray(data) || data.length === 0) {
            showAlert('Преподаватели с такой фамилией не найдены', 'warning');
            return;
        }

        teachersList.innerHTML = '';
        data.forEach(t => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action';
            btn.innerHTML = `
                <div class="d-flex justify-content-between">
                    <div>
                        <strong>${t.full_name}</strong>
                        ${t.post ? `<div class="small text-muted">${t.post}</div>` : ''}
                    </div>
                    <div class="text-end small text-muted">
                        ${t.department ? `<div>${t.department}</div>` : ''}
                        ${t.email ? `<div>${t.email}</div>` : ''}
                    </div>
                </div>`;
            btn.addEventListener('click', () => selectTeacher(t));
            teachersList.appendChild(btn);
        });
        teachersList.classList.remove('d-none');

    } catch (e) {
        showAlert('Ошибка запроса: ' + e.message);
    } finally {
        searchBtn.disabled = false;
        searchBtn.innerHTML = '<i class="bi bi-search"></i> Найти';
    }
}

function selectTeacher(t) {
    document.getElementById('teacherId').value      = t.id ?? '';
    document.getElementById('fullName').value       = t.full_name ?? '';
    document.getElementById('lastNameHidden').value = t.last_name ?? '';
    document.getElementById('firstNameHidden').value= t.first_name ?? '';
    document.getElementById('middleNameHidden').value= t.middle_name ?? '';
    document.getElementById('department').value     = t.department ?? '';
    document.getElementById('post').value           = t.post ?? '';

    if (t.email) document.getElementById('email').value = t.email;

    document.getElementById('selectedTeacher').innerHTML = `
        <i class="bi bi-person-check"></i> Выбран: <strong>${t.full_name}</strong>
        ${t.department ? `<br><small>${t.department}</small>` : ''}`;

    step1.classList.add('d-none');
    registerForm.classList.remove('d-none');
}

registerForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const pwd  = document.getElementById('password').value;
    const pwd2 = document.getElementById('passwordConfirm').value;

    if (pwd !== pwd2) {
        showAlert('Пароли не совпадают');
        return;
    }

    const payload = Object.fromEntries(new FormData(registerForm).entries());

    const submitBtn = registerForm.querySelector('button[type=submit]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Отправка...';

    try {
        const res = await fetch('api/register.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            showAlert('Регистрация успешна! Перенаправление...', 'success');
            setTimeout(() => window.location.href = data.redirect, 800);
        } else {
            showAlert(data.error || 'Неизвестная ошибка');
        }
    } catch (e) {
        showAlert('Ошибка отправки: ' + e.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Зарегистрироваться';
    }
});

backBtn.addEventListener('click', () => {
    registerForm.classList.add('d-none');
    step1.classList.remove('d-none');
    teachersList.classList.add('d-none');
    alertBox.innerHTML = '';
});

searchBtn.addEventListener('click', searchTeachers);
lastNameInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); searchTeachers(); }
});