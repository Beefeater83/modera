/**
 * mobile-nav.js — поведение мобильной навигации (< 900 px)
 *
 * Синхронизирует состояние авторизации из десктопного DOM в sidebar.
 * Не дублирует ID, не конфликтует с auth.js.
 */

(() => {
    'use strict';

    const hamburgerBtn  = document.getElementById('hamburger-btn');
    const sidebar       = document.getElementById('mobile-sidebar');
    const overlay       = document.getElementById('mobile-overlay');
    const closeSidebarBtn = document.getElementById('sidebar-close-btn');

    // Элементы sidebar (авторизация)
    const mobProtectedNav  = document.getElementById('mob-protected-nav');
    const mobAuthDivider   = document.getElementById('mob-auth-divider');
    const mobCurrentUser   = document.getElementById('mob-current-user');
    const mobPasskeyBtn    = document.getElementById('mob-passkey-menu-btn');
    const mobAuthBtn       = document.getElementById('mob-authenticate-btn');
    const mobLogoutBtn     = document.getElementById('mob-logout-btn');

    // Ссылки на десктопные аналоги (управляются auth.js)
    const desktopProtectedNav = document.getElementById('protected-nav');
    const desktopCurrentUser  = document.getElementById('current-user');
    const desktopPasskeyBtn   = document.getElementById('passkey-menu-btn');
    const desktopAuthBtn      = document.getElementById('authenticateBtn');
    const desktopLogoutBtn    = document.getElementById('logout-btn');

    if (!hamburgerBtn || !sidebar || !overlay) {
        return;
    }

    // ── Sidebar open / close ─────────────────────────────────────────────

    let isOpen = false;
    let lastFocusedElement = null;

    /** Вычислить ширину скроллбара один раз (чтобы контент не прыгал). */
    function getScrollbarWidth() {
        return window.innerWidth - document.documentElement.clientWidth;
    }

    function openSidebar() {
        if (isOpen) return;
        isOpen = true;

        lastFocusedElement = document.activeElement;

        // Компенсация скроллбара
        document.documentElement.style.setProperty(
            '--scrollbar-width',
            `${getScrollbarWidth()}px`
        );
        document.body.classList.add('sidebar-open');

        sidebar.classList.add('is-open');
        overlay.classList.add('is-open');

        hamburgerBtn.setAttribute('aria-expanded', 'true');
        hamburgerBtn.setAttribute('aria-label', 'Close menu');
        sidebar.setAttribute('aria-hidden', 'false');
        overlay.setAttribute('aria-hidden', 'false');

        // Синхронизировать состояние auth при каждом открытии
        syncAuthState();

        // Перевести фокус на кнопку закрытия
        closeSidebarBtn?.focus();
    }

    function closeSidebar() {
        if (!isOpen) return;
        isOpen = false;

        document.body.classList.remove('sidebar-open');
        document.documentElement.style.setProperty('--scrollbar-width', '0px');

        sidebar.classList.remove('is-open');
        overlay.classList.remove('is-open');

        hamburgerBtn.setAttribute('aria-expanded', 'false');
        hamburgerBtn.setAttribute('aria-label', 'Open menu');
        sidebar.setAttribute('aria-hidden', 'true');
        overlay.setAttribute('aria-hidden', 'true');

        // Вернуть фокус
        if (lastFocusedElement && document.contains(lastFocusedElement)) {
            lastFocusedElement.focus();
        } else {
            hamburgerBtn.focus();
        }
        lastFocusedElement = null;
    }

    // ── Синхронизация состояния авторизации ─────────────────────────────

    /**
     * Копирует visible/hidden состояние из десктопных элементов (управляемых
     * auth.js) в мобильные аналоги.  Вызывается при открытии sidebar и при
     * MutationObserver-обновлениях из auth.js.
     */
    function syncAuthState() {
        // protected-nav (Contracts / Invoices)
        if (desktopProtectedNav && mobProtectedNav) {
            const authenticated = !desktopProtectedNav.hidden;
            mobProtectedNav.hidden = !authenticated;
            if (mobAuthDivider) mobAuthDivider.hidden = !authenticated;
        }

        // current-user
        if (desktopCurrentUser && mobCurrentUser) {
            mobCurrentUser.hidden = desktopCurrentUser.hidden;
            mobCurrentUser.textContent = desktopCurrentUser.textContent;
        }

        // passkey button
        if (desktopPasskeyBtn && mobPasskeyBtn) {
            mobPasskeyBtn.hidden = desktopPasskeyBtn.hidden;
        }

        // authenticate button
        if (desktopAuthBtn && mobAuthBtn) {
            mobAuthBtn.hidden = desktopAuthBtn.hidden;
        }

        // logout button
        if (desktopLogoutBtn && mobLogoutBtn) {
            mobLogoutBtn.hidden = desktopLogoutBtn.hidden;
        }
    }

    // ── Наблюдатель за изменениями auth.js ──────────────────────────────

    /**
     * auth.js меняет атрибут hidden у десктопных элементов.
     * MutationObserver позволяет реагировать на эти изменения и
     * синхронизировать их в sidebar без вмешательства в auth.js.
     */
    const observeTargets = [
        desktopProtectedNav,
        desktopCurrentUser,
        desktopPasskeyBtn,
        desktopAuthBtn,
        desktopLogoutBtn,
    ].filter(Boolean);

    const observer = new MutationObserver(syncAuthState);

    for (const target of observeTargets) {
        observer.observe(target, {
            attributes: true,
            attributeFilter: ['hidden'],
            characterData: true,
            subtree: true,
        });
    }

    // Также наблюдаем за textContent current-user
    if (desktopCurrentUser) {
        observer.observe(desktopCurrentUser, {
            characterData: true,
            subtree: true,
            childList: true,
        });
    }

    // ── Действия sidebar-кнопок ──────────────────────────────────────────

    /** Открыть Passkey-меню и закрыть sidebar. */
    mobPasskeyBtn?.addEventListener('click', () => {
        closeSidebar();
        // Делегируем десктопной кнопке, чтобы не дублировать логику auth.js
        desktopPasskeyBtn?.click();
    });

    /** Открыть auth-modal и закрыть sidebar. */
    mobAuthBtn?.addEventListener('click', () => {
        closeSidebar();
        desktopAuthBtn?.click();
    });

    /** Выход и закрытие sidebar. */
    mobLogoutBtn?.addEventListener('click', () => {
        closeSidebar();
        desktopLogoutBtn?.click();
    });

    // ── Закрытие меню при клике по ссылке ───────────────────────────────
    sidebar.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('click', () => closeSidebar());
    });

    // Клик по лого внутри sidebar тоже закрывает меню
    sidebar.querySelector('.sidebar-brand')?.addEventListener('click', () => {
        closeSidebar();
    });

    // ── Обработчики открытия/закрытия ───────────────────────────────────

    hamburgerBtn.addEventListener('click', () => {
        if (isOpen) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    closeSidebarBtn?.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);

    // Escape закрывает sidebar
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen) {
            closeSidebar();
        }
    });

    // ── Предотвращение случайного открытия при горизонтальном скролле ────

    let touchStartX = 0;
    let touchStartY = 0;

    document.addEventListener('touchstart', (event) => {
        touchStartX = event.touches[0].clientX;
        touchStartY = event.touches[0].clientY;
    }, { passive: true });

    document.addEventListener('touchmove', (event) => {
        if (!isOpen) return;

        const dx = Math.abs(event.touches[0].clientX - touchStartX);
        const dy = Math.abs(event.touches[0].clientY - touchStartY);

        // Если горизонтальный свайп больше вертикального — это не прокрутка
        // страницы, разрешаем событие, но sidebar уже заблокирован телом
        if (dx > dy && dx > 10) {
            // При свайпе влево от открытого sidebar — закрываем
            if (event.touches[0].clientX - touchStartX < -40) {
                closeSidebar();
            }
        }
    }, { passive: true });

    // Первичная синхронизация после загрузки DOM
    syncAuthState();
})();
