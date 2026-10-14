<?php
session_start();
require_once 'config/database.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Регистрация — АИС-расписание.РФ</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --ap-bg: #f5f5f7;
    --ap-card: #ffffff;
    --ap-dark: #1d1d1f;
    --ap-gray: #86868b;
    --ap-border: #e5e5ea;
    --ap-accent: #0071e3;
    --ap-accent-hover: #0077ed;
    --ap-green: #30d158;
    --ap-purple: #bf5af2;
    --ap-glow-1: rgba(0,113,227,0.08);
    --ap-glow-2: rgba(191,90,242,0.08);
}

html, body {
    margin: 0;
    padding: 0;
    min-height: 100vh;
    background: var(--ap-bg);
    color: var(--ap-dark);
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", Roboto, sans-serif;
}

/* Фон */
.register-page {
    min-height: 100vh;
    padding: 40px 20px;
    position: relative;
    overflow: hidden;
    background: linear-gradient(180deg, #fff 0%, #f5f5f7 100%);
}
.register-page::before {
    content: '';
    position: absolute;
    inset: -50%;
    background:
        radial-gradient(circle at 20% 30%, var(--ap-glow-1) 0%, transparent 40%),
        radial-gradient(circle at 80% 70%, var(--ap-glow-2) 0%, transparent 40%);
    animation: auroraMove 20s ease-in-out infinite;
    pointer-events: none;
    z-index: 0;
}
@keyframes auroraMove {
    0%,100% { transform: translate(0,0) scale(1); }
    33%     { transform: translate(5%,-5%) scale(1.1); }
    66%     { transform: translate(-5%,5%) scale(0.95); }
}
.register-page::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(0,113,227,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,113,227,0.03) 1px, transparent 1px);
    background-size: 60px 60px;
    mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%);
    pointer-events: none;
    z-index: 0;
}

.register-container {
    max-width: 560px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
    animation: fadeUpIn 0.7s cubic-bezier(0.16,1,0.3,1);
}
@keyframes fadeUpIn {
    from { opacity: 0; transform: translateY(30px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Логотип */
.register-logo {
    width: 88px;
    height: 88px;
    margin: 0 auto 24px;
    animation: logoFloat 5s ease-in-out infinite;
    filter: drop-shadow(0 20px 40px rgba(0,113,227,0.25));
}
.register-logo img { width: 100%; height: 100%; object-fit: contain; }
@keyframes logoFloat {
    0%, 100% { transform: translateY(0) scale(1); }
    50%      { transform: translateY(-10px) scale(1.02); }
}

.register-title {
    font-size: 1.9rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    text-align: center;
    margin: 0 0 8px;
    color: var(--ap-dark);
}
.register-subtitle {
    text-align: center;
    color: var(--ap-gray);
    font-size: 0.95rem;
    margin: 0 0 32px;
}

/* Карточка формы */
.register-card {
    background: var(--ap-card);
    border-radius: 24px;
    padding: 32px;
    box-shadow:
        0 4px 12px rgba(0,0,0,0.04),
        0 30px 80px rgba(0,0,0,0.1);
    border: 1px solid var(--ap-border);
}

/* Шаги */
.step-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 28px;
}
.step-dot {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--ap-border);
    color: var(--ap-gray);
    font-size: 0.85rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}
.step-dot.active {
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    color: #fff;
    box-shadow: 0 8px 20px rgba(0,113,227,0.35);
    transform: scale(1.1);
}
.step-dot.done {
    background: var(--ap-green);
    color: #fff;
}
.step-line {
    width: 40px;
    height: 2px;
    background: var(--ap-border);
    border-radius: 2px;
}
.step-line.done { background: var(--ap-green); }

/* Поля */
.form-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--ap-dark);
    margin-bottom: 6px;
}
.form-control, .form-select {
    padding: 14px 18px;
    border-radius: 12px;
    border: 1.5px solid var(--ap-border);
    background: #fafafc;
    font-size: 1rem;
    font-family: inherit;
    transition: all 0.2s;
}
.form-control:focus, .form-select:focus {
    border-color: var(--ap-accent);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(0,113,227,0.12);
    outline: none;
}
.form-text {
    font-size: 0.78rem;
    color: var(--ap-gray);
    margin-top: 6px;
}

/* Кнопки */
.btn-primary-gradient {
    width: 100%;
    padding: 15px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    color: #fff;
    font-size: 1rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.16,1,0.3,1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 8px 20px rgba(0,113,227,0.25);
}
.btn-primary-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(0,113,227,0.4);
}
.btn-primary-gradient:active { transform: translateY(0); }
.btn-primary-gradient:disabled { opacity: 0.7; cursor: wait; }

.btn-outline {
    padding: 12px 24px;
    border: 1.5px solid var(--ap-border);
    border-radius: 12px;
    background: #fff;
    color: var(--ap-dark);
    font-weight: 500;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-outline:hover {
    border-color: var(--ap-accent);
    color: var(--ap-accent);
    background: rgba(0,113,227,0.04);
}

/* Alert */
.alert {
    border-radius: 12px;
    border: none;
    padding: 12px 16px;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: shakeIn 0.4s ease;
}
@keyframes shakeIn {
    0% { transform: translateX(-8px); }
    25% { transform: translateX(8px); }
    50% { transform: translateX(-4px); }
    75% { transform: translateX(4px); }
    100% { transform: translateX(0); }
}

/* Шаг 1 — поиск */
#step1 .input-group {
    display: flex;
    gap: 8px;
}
#step1 .input-group input {
    flex: 1;
    padding: 14px 18px;
    border-radius: 12px;
    border: 1.5px solid var(--ap-border);
    background: #fafafc;
    font-size: 1rem;
    font-family: inherit;
    transition: all 0.2s;
}
#step1 .input-group input:focus {
    border-color: var(--ap-accent);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(0,113,227,0.12);
    outline: none;
}
#step1 .input-group button {
    padding: 14px 24px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%);
    color: #fff;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.25s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}
#step1 .input-group button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,113,227,0.35);
}

/* Список преподавателей */
#teachersList {
    max-height: 320px;
    overflow-y: auto;
    border-radius: 12px;
    border: 1px solid var(--ap-border);
    margin-top: 12px;
}
#teachersList::-webkit-scrollbar { width: 6px; }
#teachersList::-webkit-scrollbar-thumb { background: var(--ap-border); border-radius: 3px; }

.teacher-item {
    padding: 14px 18px;
    border-bottom: 1px solid var(--ap-border);
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}
.teacher-item:last-child { border-bottom: none; }
.teacher-item:hover {
    background: rgba(0,113,227,0.06);
    padding-left: 22px;
}
.teacher-item-info { flex: 1; min-width: 0; }
.teacher-item-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--ap-dark);
    margin-bottom: 2px;
}
.teacher-item-meta {
    font-size: 0.78rem;
    color: var(--ap-gray);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.teacher-item-arrow {
    color: var(--ap-gray);
    flex-shrink: 0;
    transition: transform 0.2s;
}
.teacher-item:hover .teacher-item-arrow {
    color: var(--ap-accent);
    transform: translateX(4px);
}

/* Выбранный преподаватель */
.selected-teacher {
    background: linear-gradient(135deg, rgba(0,113,227,0.08) 0%, rgba(191,90,242,0.08) 100%);
    border: 1px solid rgba(0,113,227,0.2);
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
}
.selected-teacher i {
    color: var(--ap-accent);
    font-size: 1.4rem;
}
.selected-teacher-info { flex: 1; }
.selected-teacher-name {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 2px;
}
.selected-teacher-dept {
    font-size: 0.78rem;
    color: var(--ap-gray);
}

/* Разделитель */
.divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 28px 0 20px;
    color: var(--ap-gray);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.divider::before,
.divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--ap-border);
}

/* Карточка студентам */
.student-link {
    display: block;
    background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 50%, #bf5af2 100%);
    background-size: 200% 200%;
    animation: gradientShift 8s ease-in-out infinite;
    border-radius: 20px;
    padding: 24px;
    text-decoration: none;
    color: #fff;
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
    box-shadow: 0 10px 30px rgba(0,113,227,0.3);
}
@keyframes gradientShift {
    0%,100% { background-position: 0% 50%; }
    50%     { background-position: 100% 50%; }
}
.student-link:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 50px rgba(0,113,227,0.45);
    color: #fff;
}
.student-link::before {
    content: '';
    position: absolute;
    top: -50%; right: -20%;
    width: 240px; height: 240px;
    background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 65%);
    border-radius: 50%;
    pointer-events: none;
}
.student-link-inner {
    display: flex;
    align-items: center;
    gap: 18px;
    position: relative;
    z-index: 1;
}
.student-link-icon {
    width: 56px; height: 56px;
    border-radius: 16px;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.7rem;
    flex-shrink: 0;
}
.student-link-content { flex: 1; min-width: 0; }
.student-link-title {
    font-size: 1.15rem;
    font-weight: 700;
    letter-spacing: -0.01em;
    margin: 0 0 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.student-link-title .badge-new {
    background: rgba(255,255,255,0.25);
    border: 1px solid rgba(255,255,255,0.4);
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.student-link-desc {
    font-size: 0.88rem;
    opacity: 0.9;
    margin: 0;
    line-height: 1.4;
}
.student-link-arrow {
    font-size: 1.5rem;
    opacity: 0.8;
    flex-shrink: 0;
    transition: transform 0.3s;
}
.student-link:hover .student-link-arrow {
    transform: translateX(6px);
    opacity: 1;
}

/* Ссылки снизу */
.login-links {
    text-align: center;
    margin-top: 24px;
    font-size: 0.88rem;
    color: var(--ap-gray);
}
.login-links a {
    color: var(--ap-accent);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.15s;
}
.login-links a:hover {
    color: var(--ap-accent-hover);
    text-decoration: underline;
}
.login-links .dot {
    margin: 0 10px;
    opacity: 0.5;
}

/* Футер */
.register-footer {
    text-align: center;
    margin-top: 32px;
    font-size: 0.8rem;
    color: var(--ap-gray);
}
.register-footer a {
    color: var(--ap-gray);
    text-decoration: none;
}
.register-footer a:hover { color: var(--ap-accent); }
.register-footer .dot {
    margin: 0 8px;
    opacity: 0.5;
}

/* Спиннер */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 0.15em;
}

/* Мобильный */
@media (max-width: 576px) {
    .register-card { padding: 24px 20px; }
    .register-title { font-size: 1.5rem; }
    #step1 .input-group { flex-direction: column; }
    #step1 .input-group button { width: 100%; justify-content: center; }
}
</style>
</head>
<body>

<div class="register-page">
    <div class="register-container">

        <!-- Логотип -->
        <div class="register-logo">
            <img src="assets/img/logo.svg" alt="АИС-расписание.РФ">
        </div>

        <h1 class="register-title">Регистрация</h1>
        <p class="register-subtitle">АИС-расписание.РФ — для преподавателей КузГТУ</p>

        <!-- Индикатор шагов -->
        <div class="step-indicator">
            <div class="step-dot active" id="stepDot1">1</div>
            <div class="step-line" id="stepLine1"></div>
            <div class="step-dot" id="stepDot2">2</div>
        </div>

        <!-- Карточка -->
        <div class="register-card">

            <div id="alertBox"></div>

            <!-- ШАГ 1: поиск по фамилии -->
            <div id="step1">
                <h2 style="font-size:1.15rem; font-weight:600; margin:0 0 6px; letter-spacing:-0.01em;">
                    Найдём вас в базе КузГТУ
                </h2>
                <p style="font-size:0.88rem; color:var(--ap-gray); margin:0 0 20px;">
                    Введите вашу фамилию — мы поищем вас среди преподавателей портала.
                </p>

                <div class="input-group">
                    <input type="text" id="lastName" placeholder="Например: Иванов" autocomplete="off" autofocus>
                    <button type="button" id="searchBtn">
                        <i class="bi bi-search"></i> Найти
                    </button>
                </div>

                <div class="form-text">
                    <i class="bi bi-info-circle"></i>
                    Данные берутся с официального портала <strong>portal.kuzstu.ru</strong>
                </div>

                <div id="teachersList" class="d-none"></div>
            </div>

            <!-- ШАГ 2: форма регистрации -->
            <form id="registerForm" class="d-none" autocomplete="off">
                <input type="hidden" name="teacher_id"   id="teacherId">
                <input type="hidden" name="full_name"    id="fullName">
                <input type="hidden" name="last_name"    id="lastNameHidden">
                <input type="hidden" name="first_name"   id="firstNameHidden">
                <input type="hidden" name="middle_name"  id="middleNameHidden">
                <input type="hidden" name="post"         id="post">

                <!-- Выбранный преподаватель -->
                <div class="selected-teacher" id="selectedTeacher"></div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Подразделение / кафедра</label>
                        <input type="text" name="department" id="department" class="form-control"
                               placeholder="Например: Кафедра ИТ">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Логин <span style="color:var(--ap-red);">*</span></label>
                        <input type="text" name="login" id="login" class="form-control"
                               required minlength="4" pattern="[a-zA-Z0-9_.\-]+"
                               placeholder="ivanov">
                        <div class="form-text">Латиница, цифры, _ . -</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email <span style="color:var(--ap-red);">*</span></label>
                        <input type="email" name="email" id="email" class="form-control"
                               required placeholder="ivanov@kuzstu.ru">
                        <div class="form-text">Нужен для восстановления пароля</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Пароль <span style="color:var(--ap-red);">*</span></label>
                        <input type="password" name="password" id="password" class="form-control"
                               required minlength="6" placeholder="Минимум 6 символов">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Повтор пароля <span style="color:var(--ap-red);">*</span></label>
                        <input type="password" name="password_confirm" id="passwordConfirm"
                               class="form-control" required minlength="6" placeholder="Повторите пароль">
                    </div>
                </div>

                <div style="display:flex; gap:12px; margin-top:24px;">
                    <button type="submit" class="btn-primary-gradient" id="submitBtn">
                        <i class="bi bi-check-circle"></i>
                        Зарегистрироваться
                    </button>
                    <button type="button" class="btn-outline" id="backBtn" title="Выбрать другого преподавателя">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                </div>
            </form>

            <!-- Разделитель -->
            <div class="divider">Уже студент?</div>

            <!-- Карточка студентам -->
            <a href="student.php" class="student-link">
                <div class="student-link-inner">
                    <div class="student-link-icon">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div class="student-link-content">
                        <div class="student-link-title">
                            Проверить посещаемость
                            <span class="badge-new">Без входа</span>
                        </div>
                        <p class="student-link-desc">
                            Введите номер зачётки — узнайте свою статистику по всем предметам.
                        </p>
                    </div>
                    <i class="bi bi-arrow-right student-link-arrow"></i>
                </div>
            </a>

            <!-- Ссылки снизу -->
            <div class="login-links">
                <a href="login.php">
                    <i class="bi bi-box-arrow-in-right"></i> Уже есть аккаунт?
                </a>
            </div>

        </div>

        <!-- Футер -->
        <div class="register-footer">
            <a href="presentation.php">
                <i class="bi bi-stars"></i> О системе
            </a>
            <span class="dot">·</span>
            <a href="https://portal.kuzstu.ru" target="_blank" rel="noopener">
                <i class="bi bi-globe"></i> portal.kuzstu.ru
            </a>
        </div>

    </div>
</div>

<script>
/* ============================================================
   Индикатор шагов
   ============================================================ */
function goToStep(step) {
    const dot1 = document.getElementById('stepDot1');
    const dot2 = document.getElementById('stepDot2');
    const line = document.getElementById('stepLine1');

    if (step === 1) {
        dot1.classList.add('active');
        dot1.classList.remove('done');
        dot2.classList.remove('active');
        line.classList.remove('done');
    } else {
        dot1.classList.remove('active');
        dot1.classList.add('done');
        dot1.innerHTML = '✓';
        dot2.classList.add('active');
        line.classList.add('done');
    }
}

/* Alert */
function showAlert(message, type = 'danger') {
    const box = document.getElementById('alertBox');
    const icons = {
        danger: 'exclamation-triangle-fill',
        success: 'check-circle-fill',
        warning: 'info-circle-fill',
    };
    box.innerHTML = `
        <div class="alert alert-${type} mb-3">
            <i class="bi bi-${icons[type] || 'info-circle-fill'} fs-5"></i>
            <div>${message}</div>
        </div>`;
}

/* Поиск преподавателей */
const lastNameInput = document.getElementById('lastName');
const searchBtn     = document.getElementById('searchBtn');
const teachersList  = document.getElementById('teachersList');
const step1         = document.getElementById('step1');
const registerForm  = document.getElementById('registerForm');
const backBtn       = document.getElementById('backBtn');

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
    document.getElementById('alertBox').innerHTML = '';

    try {
        const res = await fetch(`api/search_teacher.php?last_name=${encodeURIComponent(lastName)}`);
        const data = await res.json();

        if (data.error) {
            showAlert(data.error + (data.hint ? '<br><small style="opacity:.8">' + data.hint + '</small>' : ''));
            return;
        }

        if (!Array.isArray(data) || data.length === 0) {
            showAlert('Преподаватели с такой фамилией не найдены', 'warning');
            return;
        }

        teachersList.innerHTML = '';
        data.forEach(t => {
            const item = document.createElement('div');
            item.className = 'teacher-item';
            item.innerHTML = `
                <div class="teacher-item-info">
                    <div class="teacher-item-name">${escapeHtml(t.full_name)}</div>
                    ${t.department ? `<div class="teacher-item-meta">${escapeHtml(t.department)}</div>` : ''}
                </div>
                <i class="bi bi-arrow-right teacher-item-arrow"></i>`;
            item.addEventListener('click', () => selectTeacher(t));
            teachersList.appendChild(item);
        });
        teachersList.classList.remove('d-none');

    } catch (e) {
        showAlert('Ошибка запроса: ' + e.message);
    } finally {
        searchBtn.disabled = false;
        searchBtn.innerHTML = '<i class="bi bi-search"></i> Найти';
    }
}

function escapeHtml(s) {
    return String(s || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function selectTeacher(t) {
    document.getElementById('teacherId').value       = t.id ?? '';
    document.getElementById('fullName').value        = t.full_name ?? '';
    document.getElementById('lastNameHidden').value  = t.last_name ?? '';
    document.getElementById('firstNameHidden').value = t.first_name ?? '';
    document.getElementById('middleNameHidden').value= t.middle_name ?? '';
    document.getElementById('post').value            = t.post ?? '';

    if (t.department) document.getElementById('department').value = t.department;
    if (t.email)      document.getElementById('email').value      = t.email;

    document.getElementById('selectedTeacher').innerHTML = `
        <i class="bi bi-person-check-fill"></i>
        <div class="selected-teacher-info">
            <div class="selected-teacher-name">${escapeHtml(t.full_name)}</div>
            ${t.department ? `<div class="selected-teacher-dept">${escapeHtml(t.department)}</div>` : ''}
        </div>`;

    step1.classList.add('d-none');
    registerForm.classList.remove('d-none');
    goToStep(2);
}

/* Отправка формы */
registerForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const pwd  = document.getElementById('password').value;
    const pwd2 = document.getElementById('passwordConfirm').value;

    if (pwd !== pwd2) {
        showAlert('Пароли не совпадают');
        return;
    }

    const payload = Object.fromEntries(new FormData(registerForm).entries());

    const submitBtn = document.getElementById('submitBtn');
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

/* Назад */
backBtn.addEventListener('click', () => {
    registerForm.classList.add('d-none');
    step1.classList.remove('d-none');
    teachersList.classList.add('d-none');
    document.getElementById('alertBox').innerHTML = '';
    goToStep(1);
});

/* Слушатели */
searchBtn.addEventListener('click', searchTeachers);
lastNameInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); searchTeachers(); }
});
</script>

</body>
</html>