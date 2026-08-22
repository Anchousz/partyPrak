/* ==========================================================================
   ui.js — базовый слой интерфейса: иконки, уведомления, модальные окна,
   шапка, появление при скролле и мелкие утилиты.
   Всё живёт в пространстве имён PVD, глобальных функций не создаём.
   ========================================================================== */

(function (window, document) {
    'use strict';

    var PVD = window.PVD = window.PVD || {};

    /* --- Утилиты --------------------------------------------------------- */

    var rubles = new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        maximumFractionDigits: 0
    });

    /** Форматирует сумму как «12 500 ₽». */
    function money(value) {
        return rubles.format(Number(value) || 0);
    }

    /** Экранирует пользовательские и словарные строки перед вставкой в HTML. */
    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /** Склонение по числу: plural(3, ['гость', 'гостя', 'гостей']). */
    function plural(count, forms) {
        var n = Math.abs(count) % 100;
        var n1 = n % 10;
        if (n > 10 && n < 20) return forms[2];
        if (n1 > 1 && n1 < 5) return forms[1];
        if (n1 === 1) return forms[0];
        return forms[2];
    }

    /** Дата в человеческом виде: «12 июня 2025». */
    function formatDate(iso) {
        if (!iso) return '';
        var date = new Date(iso + 'T00:00:00');
        if (isNaN(date.getTime())) return iso;
        /* Убираем хвост «г.», который ru-локаль добавляет к году. */
        return date
            .toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' })
            .replace(/\s*г\.$/, '');
    }

    /* --- Иконки ---------------------------------------------------------- */
    /* Один набор путей, обёрнутых общим <svg>. Никаких внешних библиотек. */

    var ICON_PATHS = {
        calendar: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/>',
        pin: '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        sparkles: '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/><path d="M18 16l.8 2.2L21 19l-2.2.8L18 22l-.8-2.2L15 19l2.2-.8z"/>',
        checkCircle: '<circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 6-6"/>',
        alert: '<circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/>',
        info: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-5M12 8h.01"/>',
        close: '<path d="M18 6L6 18M6 6l12 12"/>',
        clipboard: '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>',
        inbox: '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5.1L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.9A2 2 0 0 0 16.7 4H7.3a2 2 0 0 0-1.8 1.1z"/>',
        camera: '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>',
        clock: '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>'
    };

    /**
     * Возвращает разметку иконки.
     * @param {string} name ключ из ICON_PATHS
     * @param {string} [className] дополнительный класс
     */
    function icon(name, className) {
        var path = ICON_PATHS[name];
        if (!path) return '';
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" ' +
            'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"' +
            (className ? ' class="' + className + '"' : '') + '>' + path + '</svg>';
    }

    /* --- Уведомления ----------------------------------------------------- */

    var toastRegion = null;

    function getToastRegion() {
        if (toastRegion && document.body.contains(toastRegion)) return toastRegion;
        toastRegion = document.querySelector('.toast-region');
        if (!toastRegion) {
            toastRegion = document.createElement('div');
            toastRegion.className = 'toast-region';
            toastRegion.setAttribute('role', 'status');
            toastRegion.setAttribute('aria-live', 'polite');
            document.body.appendChild(toastRegion);
        }
        return toastRegion;
    }

    var TOAST_ICONS = { success: 'checkCircle', error: 'alert', info: 'info' };

    /**
     * Показывает уведомление вместо alert().
     * @param {{type?: string, title: string, text?: string, duration?: number}} options
     */
    function toast(options) {
        var opts = options || {};
        var type = TOAST_ICONS[opts.type] ? opts.type : 'info';
        var region = getToastRegion();

        var el = document.createElement('div');
        el.className = 'toast toast--' + type;
        el.innerHTML =
            icon(TOAST_ICONS[type], 'toast__icon') +
            '<div class="toast__body">' +
                '<p class="toast__title">' + escapeHtml(opts.title) + '</p>' +
                (opts.text ? '<p class="toast__text">' + escapeHtml(opts.text) + '</p>' : '') +
            '</div>' +
            '<button type="button" class="toast__close" aria-label="Закрыть уведомление">' + icon('close') + '</button>';

        var timer = null;
        function dismiss() {
            if (timer) window.clearTimeout(timer);
            el.classList.add('is-leaving');
            el.addEventListener('animationend', function () { el.remove(); }, { once: true });
        }

        el.querySelector('.toast__close').addEventListener('click', dismiss);
        region.appendChild(el);
        timer = window.setTimeout(dismiss, opts.duration || 5000);

        return { dismiss: dismiss };
    }

    /* --- Блокировка прокрутки -------------------------------------------- */
    /* Считаем открытые слои, чтобы закрытие одного окна не разблокировало
       страницу, пока открыто другое. */

    var scrollLocks = 0;

    function lockScroll() {
        if (scrollLocks === 0) {
            var barWidth = window.innerWidth - document.documentElement.clientWidth;
            if (barWidth > 0) document.body.style.paddingRight = barWidth + 'px';
            document.body.classList.add('is-locked');
        }
        scrollLocks++;
    }

    function unlockScroll() {
        scrollLocks = Math.max(0, scrollLocks - 1);
        if (scrollLocks === 0) {
            document.body.classList.remove('is-locked');
            document.body.style.paddingRight = '';
        }
    }

    /* --- Модальные окна --------------------------------------------------- */

    var FOCUSABLE = [
        'a[href]', 'button:not([disabled])', 'input:not([disabled]):not([type="hidden"])',
        'select:not([disabled])', 'textarea:not([disabled])', '[tabindex]:not([tabindex="-1"])'
    ].join(',');

    /**
     * Доступное модальное окно: ловушка фокуса, Escape, возврат фокуса,
     * блокировка прокрутки. Ожидает разметку .modal > .modal__backdrop + .modal__panel.
     * @param {string|Element} target id элемента или сам элемент
     */
    function Modal(target) {
        var el = typeof target === 'string' ? document.getElementById(target) : target;
        if (!el) return null;
        if (el.__pvdModal) return el.__pvdModal;

        var panel = el.querySelector('.modal__panel');
        var lastFocused = null;
        var isOpen = false;

        el.setAttribute('aria-hidden', 'true');
        if (panel) {
            panel.setAttribute('role', 'dialog');
            panel.setAttribute('aria-modal', 'true');
            if (!panel.hasAttribute('tabindex')) panel.setAttribute('tabindex', '-1');
        }

        function onKeydown(event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                close();
                return;
            }
            if (event.key !== 'Tab' || !panel) return;

            var items = Array.prototype.filter.call(
                panel.querySelectorAll(FOCUSABLE),
                function (node) { return node.offsetParent !== null || node === document.activeElement; }
            );
            if (!items.length) {
                event.preventDefault();
                panel.focus();
                return;
            }
            var first = items[0];
            var last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }

        function open() {
            if (isOpen) return;
            isOpen = true;
            lastFocused = document.activeElement;
            el.classList.add('is-open');
            el.setAttribute('aria-hidden', 'false');
            lockScroll();
            document.addEventListener('keydown', onKeydown);

            /* Фокус переводим сразу: requestAnimationFrame не срабатывает
               в фоновой вкладке, и окно осталось бы без фокуса. */
            var target = panel && (panel.querySelector('[data-autofocus]') || panel.querySelector(FOCUSABLE));
            if (target || panel) (target || panel).focus();
            el.dispatchEvent(new CustomEvent('modal:open'));
        }

        function close() {
            if (!isOpen) return;
            isOpen = false;
            el.classList.remove('is-open');
            el.setAttribute('aria-hidden', 'true');
            unlockScroll();
            document.removeEventListener('keydown', onKeydown);
            if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
            el.dispatchEvent(new CustomEvent('modal:close'));
        }

        /* Закрытие по подложке и по любому [data-modal-close] внутри окна. */
        el.addEventListener('click', function (event) {
            if (event.target.closest('[data-modal-close]') || event.target.classList.contains('modal__backdrop')) {
                event.preventDefault();
                close();
            }
        });

        var api = {
            el: el,
            open: open,
            close: close,
            isOpen: function () { return isOpen; }
        };
        el.__pvdModal = api;
        return api;
    }

    /**
     * Связывает все [data-modal-open="id"] с соответствующими окнами.
     * Делегирование — работает и для разметки, добавленной позже.
     */
    function initModalTriggers() {
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-modal-open]');
            if (!trigger) return;
            var modal = Modal(trigger.getAttribute('data-modal-open'));
            if (!modal) return;
            event.preventDefault();
            modal.open();
        });
    }

    /* --- Шапка ------------------------------------------------------------ */

    function initHeader() {
        var header = document.querySelector('.header');
        if (!header) return;

        var toggle = header.querySelector('.nav__toggle');
        var nav = header.querySelector('.nav');

        /* Тень и фон появляются только после отрыва от верха страницы. */
        var ticking = false;
        function onScroll() {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(function () {
                header.classList.toggle('is-scrolled', window.scrollY > 8);
                ticking = false;
            });
        }
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        if (!toggle || !nav) return;

        function setOpen(open) {
            toggle.setAttribute('aria-expanded', String(open));
            nav.classList.toggle('is-open', open);
        }

        toggle.addEventListener('click', function () {
            setOpen(toggle.getAttribute('aria-expanded') !== 'true');
        });

        nav.addEventListener('click', function (event) {
            if (event.target.closest('a')) setOpen(false);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
                setOpen(false);
                toggle.focus();
            }
        });

        document.addEventListener('click', function (event) {
            if (toggle.getAttribute('aria-expanded') !== 'true') return;
            if (!header.contains(event.target)) setOpen(false);
        });
    }

    /* --- Появление при скролле -------------------------------------------- */

    function initReveal(root) {
        var items = (root || document).querySelectorAll('.reveal:not(.is-visible)');
        if (!items.length) return;

        if (!('IntersectionObserver' in window)) {
            Array.prototype.forEach.call(items, function (el) { el.classList.add('is-visible'); });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -10% 0px', threshold: 0.05 });

        Array.prototype.forEach.call(items, function (el, index) {
            /* Небольшая лесенка внутри одной сетки — эффект заметен, но не тормозит. */
            el.style.transitionDelay = Math.min(index % 4, 3) * 70 + 'ms';
            observer.observe(el);
        });
    }

    /* --- Подсветка активного пункта меню ---------------------------------- */

    function initActiveNav() {
        var sections = document.querySelectorAll('main section[id]');
        var links = document.querySelectorAll('.nav__link[href^="#"]');
        if (!sections.length || !links.length || !('IntersectionObserver' in window)) return;

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                Array.prototype.forEach.call(links, function (link) {
                    var isMatch = link.getAttribute('href') === '#' + entry.target.id;
                    if (isMatch) {
                        link.setAttribute('aria-current', 'page');
                    } else {
                        link.removeAttribute('aria-current');
                    }
                });
            });
        }, { rootMargin: '-45% 0px -50% 0px' });

        Array.prototype.forEach.call(sections, function (section) { observer.observe(section); });
    }

    /* --- Лайтбокс -------------------------------------------------------------
       Просмотр фотографий во весь экран: стрелки, Escape, свайп-независимая
       навигация с клавиатуры и возврат фокуса на миниатюру. */

    var lightbox = null;

    function buildLightbox() {
        var el = document.createElement('div');
        el.className = 'lightbox';
        el.setAttribute('aria-hidden', 'true');
        el.innerHTML =
            '<button type="button" class="lightbox__backdrop" tabindex="-1" aria-label="Закрыть просмотр"></button>' +
            '<div class="lightbox__panel" role="dialog" aria-modal="true" aria-label="Просмотр фотографии" tabindex="-1">' +
                '<img class="lightbox__img" src="" alt="">' +
                '<p class="lightbox__caption"></p>' +
                '<button type="button" class="lightbox__nav lightbox__nav--prev" aria-label="Предыдущее фото">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>' +
                '</button>' +
                '<button type="button" class="lightbox__nav lightbox__nav--next" aria-label="Следующее фото">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>' +
                '</button>' +
                '<button type="button" class="lightbox__close" aria-label="Закрыть просмотр">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>' +
                '</button>' +
            '</div>';
        document.body.appendChild(el);

        var state = { items: [], index: 0, lastFocused: null, open: false };
        var img = el.querySelector('.lightbox__img');
        var caption = el.querySelector('.lightbox__caption');
        var panel = el.querySelector('.lightbox__panel');
        var prev = el.querySelector('.lightbox__nav--prev');
        var next = el.querySelector('.lightbox__nav--next');

        function show(index) {
            if (!state.items.length) return;
            /* Зацикливаем: с последнего кадра переходим на первый. */
            state.index = (index + state.items.length) % state.items.length;
            var item = state.items[state.index];
            img.src = item.src;
            img.alt = item.alt || '';
            caption.textContent = (state.index + 1) + ' из ' + state.items.length +
                (item.alt ? ' · ' + item.alt : '');
            var many = state.items.length > 1;
            prev.hidden = !many;
            next.hidden = !many;
        }

        function onKeydown(event) {
            if (event.key === 'Escape') { event.preventDefault(); close(); }
            else if (event.key === 'ArrowRight') { event.preventDefault(); show(state.index + 1); }
            else if (event.key === 'ArrowLeft') { event.preventDefault(); show(state.index - 1); }
            else if (event.key === 'Tab') {
                /* Внутри лайтбокса фокус ходит только по его кнопкам. */
                var focusable = Array.prototype.filter.call(
                    panel.querySelectorAll('button:not([hidden])'),
                    function (b) { return b.offsetParent !== null; }
                );
                if (!focusable.length) { event.preventDefault(); panel.focus(); return; }
                var first = focusable[0];
                var last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
            }
        }

        function open(items, index) {
            state.items = items || [];
            if (!state.items.length) return;
            state.lastFocused = document.activeElement;
            state.open = true;
            show(index || 0);
            el.classList.add('is-open');
            el.setAttribute('aria-hidden', 'false');
            lockScroll();
            document.addEventListener('keydown', onKeydown);
            panel.focus();
        }

        function close() {
            if (!state.open) return;
            state.open = false;
            el.classList.remove('is-open');
            el.setAttribute('aria-hidden', 'true');
            unlockScroll();
            document.removeEventListener('keydown', onKeydown);
            /* Освобождаем память, но только после анимации закрытия. */
            window.setTimeout(function () { if (!state.open) img.src = ''; }, 300);
            if (state.lastFocused && typeof state.lastFocused.focus === 'function') state.lastFocused.focus();
        }

        prev.addEventListener('click', function () { show(state.index - 1); });
        next.addEventListener('click', function () { show(state.index + 1); });
        el.querySelector('.lightbox__close').addEventListener('click', close);
        el.querySelector('.lightbox__backdrop').addEventListener('click', close);

        return { open: open, close: close, isOpen: function () { return state.open; } };
    }

    /**
     * Открывает лайтбокс.
     * @param {Array<{src: string, alt?: string}>} items
     * @param {number} [index] с какого кадра начать
     */
    function openLightbox(items, index) {
        if (!lightbox) lightbox = buildLightbox();
        lightbox.open(items, index);
    }

    /**
     * Связывает контейнер с миниатюрами и лайтбокс.
     * Ожидает элементы [data-lightbox] с img внутри.
     */
    function initLightbox(root) {
        var container = root || document;
        container.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-lightbox]');
            if (!trigger) return;
            event.preventDefault();

            var group = trigger.closest('[data-lightbox-group]') || container;
            var triggers = Array.prototype.slice.call(group.querySelectorAll('[data-lightbox]'));
            var items = triggers.map(function (node) {
                var image = node.matches('img') ? node : node.querySelector('img');
                return {
                    src: node.getAttribute('data-lightbox') || (image && image.src),
                    alt: node.getAttribute('data-lightbox-alt') || (image && image.alt) || ''
                };
            }).filter(function (item) { return item.src; });

            openLightbox(items, triggers.indexOf(trigger));
        });
    }

    /* --- Конфетти ------------------------------------------------------------
       Короткий салют после успешного действия. Чисто декоративный слой:
       не мешает кликам и полностью отключается при prefers-reduced-motion. */

    var CONFETTI_COLORS = ['#ff3d7f', '#ffb627', '#12cdbe', '#7c3aed', '#ff6b5b', '#ffffff'];

    function confetti(count) {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        var pieces = count || 90;
        var layer = document.createElement('div');
        layer.className = 'confetti-layer';
        layer.setAttribute('aria-hidden', 'true');

        var maxLife = 0;
        for (var i = 0; i < pieces; i++) {
            var piece = document.createElement('i');
            var duration = 2.4 + Math.random() * 2.2;
            var delay = Math.random() * 0.7;
            maxLife = Math.max(maxLife, duration + delay);

            piece.className = 'confetti-piece';
            piece.style.left = Math.random() * 100 + 'vw';
            piece.style.background = CONFETTI_COLORS[i % CONFETTI_COLORS.length];
            piece.style.animationDuration = duration + 's';
            piece.style.animationDelay = delay + 's';
            /* Часть кусочков делаем круглыми, часть — вытянутыми. */
            if (i % 3 === 0) piece.style.borderRadius = '50%';
            if (i % 4 === 0) piece.style.height = '0.5rem';
            layer.appendChild(piece);
        }

        document.body.appendChild(layer);
        window.setTimeout(function () { layer.remove(); }, (maxLife + 0.4) * 1000);
    }

    /* --- Липкая панель заказа на мобильных ------------------------------------- */

    function initStickyCta() {
        var bar = document.querySelector('.sticky-cta');
        if (!bar) return;

        /* Панель появляется, когда первый экран прокручен, и прячется у подвала,
           чтобы не перекрывать контакты. */
        var footer = document.querySelector('.footer');
        var ticking = false;

        function update() {
            ticking = false;
            var scrolled = window.scrollY > window.innerHeight * 0.6;
            var atFooter = false;
            if (footer) {
                atFooter = footer.getBoundingClientRect().top < window.innerHeight - 60;
            }
            bar.classList.toggle('is-visible', scrolled && !atFooter);
        }

        window.addEventListener('scroll', function () {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(update);
        }, { passive: true });
        update();
    }

    /* --- Плавное появление изображений ----------------------------------------
       Картинка проявляется, когда реально загрузилась. Уже готовые из кэша
       помечаем сразу, чтобы они не мигали. */

    function initImageFade(root) {
        var images = (root || document).querySelectorAll('img:not([data-loaded])');
        Array.prototype.forEach.call(images, function (img) {
            if (img.complete && img.naturalWidth > 0) {
                img.setAttribute('data-loaded', '');
                return;
            }
            img.setAttribute('data-loading', '');
            img.addEventListener('load', function () {
                img.removeAttribute('data-loading');
                img.setAttribute('data-loaded', '');
            }, { once: true });
            img.addEventListener('error', function () {
                img.removeAttribute('data-loading');
            }, { once: true });
        });
    }

    /**
     * Обновляет число и коротко подсвечивает его, если значение изменилось.
     * @param {Element} el элемент со значением
     * @param {string} text новое значение
     */
    function setAnimatedValue(el, text) {
        if (!el || el.textContent === text) return;
        el.textContent = text;
        el.classList.remove('is-updated');
        /* Перезапускаем анимацию: без принудительного reflow повторное
           добавление класса не сбрасывает её. */
        void el.offsetWidth;
        el.classList.add('is-updated');
    }

    /* --- Год в подвале ----------------------------------------------------- */

    function initYear() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-year]'), function (el) {
            el.textContent = String(new Date().getFullYear());
        });
    }

    /* --- Общая инициализация ----------------------------------------------- */

    function init() {
        initHeader();
        initModalTriggers();
        initReveal();
        initActiveNav();
        initStickyCta();
        initLightbox();
        initImageFade();
        initYear();
    }

    PVD.money = money;
    PVD.escapeHtml = escapeHtml;
    PVD.plural = plural;
    PVD.formatDate = formatDate;
    PVD.icon = icon;
    PVD.toast = toast;
    PVD.confetti = confetti;
    PVD.openLightbox = openLightbox;
    PVD.Modal = Modal;
    PVD.setAnimatedValue = setAnimatedValue;
    PVD.init = init;

    document.addEventListener('DOMContentLoaded', init);
})(window, document);
