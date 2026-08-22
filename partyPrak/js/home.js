/* ==========================================================================
   home.js — интерактив главной страницы.

   Каталог, акции и цены рисует PHP. Скрипту остаются модальные окна,
   галерея, подписка и уведомление о принятой заявке.
   ========================================================================== */

(function (window, document) {
    'use strict';

    var PVD = window.PVD;
    if (!PVD || !PVD.data) return;

    var esc = PVD.escapeHtml;

    function locationBySlug(slug) {
        var list = PVD.data.locations || [];
        for (var i = 0; i < list.length; i++) {
            if (list[i].id === slug) return list[i];
        }
        return null;
    }

    function promoById(id) {
        var list = PVD.data.promos || [];
        for (var i = 0; i < list.length; i++) {
            if (list[i].id === id) return list[i];
        }
        return null;
    }

    /* --- Галерея площадки ------------------------------------------------- */
    function initGallery() {
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-gallery]');
            if (!trigger) return;
            event.preventDefault();
            event.stopPropagation();

            var loc = locationBySlug(trigger.getAttribute('data-gallery'));
            if (!loc || !loc.gallery || !loc.gallery.length) return;

            PVD.openLightbox(loc.gallery.map(function (src) {
                return { src: src, alt: loc.name };
            }), 0);
        });
    }

    /* --- Окно с условиями акции -------------------------------------------- */
    function initPromoModal() {
        var modal   = PVD.Modal('promoModal');
        var body    = document.getElementById('promoModalBody');
        var titleEl = document.getElementById('promoModalTitle');
        if (!modal || !body || !titleEl) return;

        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-promo]');
            if (!trigger) return;

            var promo = promoById(trigger.getAttribute('data-promo'));
            if (!promo) return;

            titleEl.textContent = promo.title;
            body.innerHTML =
                '<p class="cluster" style="margin-bottom: var(--space-4);">' +
                    '<span class="badge badge--solid">' + esc(promo.discount) + '</span>' +
                    '<span class="badge badge--mint">' + PVD.icon('clock') + 'Действует в этом сезоне</span>' +
                '</p>' +
                '<p>' + esc(promo.summary) + '</p>' +
                '<ul>' + (promo.details || []).map(function (item) {
                    return '<li>' + esc(item) + '</li>';
                }).join('') + '</ul>' +
                '<p class="muted"><small>' + esc(promo.fineprint) + '</small></p>';

            var action = document.getElementById('promoModalAction');
            if (action) action.setAttribute('data-book-location', promo.locationId || '');

            modal.open();
        });
    }

    /* --- Быстрая заявка ---------------------------------------------------- */
    function initQuickBooking() {
        var modal  = PVD.Modal('bookingModal');
        var select = document.getElementById('quickLocation');
        if (!modal) return;

        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-book-location]');
            if (!trigger) return;
            event.preventDefault();

            var slug = trigger.getAttribute('data-book-location');
            if (select && slug) select.value = slug;

            /* Если открыто окно акции — сначала закрываем его,
               иначе два слоя наложатся друг на друга. */
            var promoModal = document.getElementById('promoModal');
            if (promoModal && promoModal.__pvdModal && promoModal.__pvdModal.isOpen()) {
                promoModal.__pvdModal.close();
                window.setTimeout(modal.open, 200);
            } else {
                modal.open();
            }
        });
    }

    /* --- Подписка ----------------------------------------------------------- */
    function initNewsletter() {
        var form = document.getElementById('newsletterForm');
        if (!form) return;

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!form.reportValidity()) return;
            var email = form.querySelector('input[type="email"]').value;
            form.reset();
            PVD.toast({
                type: 'success',
                title: 'Вы подписаны',
                text: 'Письма об акциях будут приходить на ' + email + '.'
            });
        });
    }

    /* --- Карточки шагов ------------------------------------------------------ */
    function initSteps() {
        var container = document.getElementById('stepsGrid');
        if (!container) return;

        container.addEventListener('click', function (event) {
            var step = event.target.closest('.step');
            if (!step) return;
            var target = step.getAttribute('data-step-target');
            var section = target && document.querySelector(target);
            if (section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    /* --- Итог отправки быстрой заявки ---------------------------------------
       PHP редиректит на index.php?quick=ok — показываем салют и уведомление,
       после чего убираем параметр, чтобы он не всплыл при перезагрузке. */
    var QUICK_ERROR_TEXT = {
        fields:   'Проверьте имя, телефон и дату праздника.',
        csrf:     'Форма устарела — откройте окно заявки заново и попробуйте ещё раз.',
        throttle: 'Вы только что отправляли заявку — подождите немного и повторите.'
    };

    function initQuickResult() {
        var params = new URLSearchParams(window.location.search);
        var quick = params.get('quick');
        if (!quick) return;

        if (quick === 'ok') {
            PVD.confetti();
            PVD.toast({
                type: 'success',
                title: 'Заявка принята!',
                text: 'Менеджер перезвонит в течение 15 минут и подтвердит дату.'
            });
        } else {
            var reason = params.get('reason');
            PVD.toast({
                type: 'error',
                title: 'Заявка не отправлена',
                text: QUICK_ERROR_TEXT[reason] || QUICK_ERROR_TEXT.fields
            });
        }

        params.delete('quick');
        params.delete('reason');
        var rest = params.toString();
        window.history.replaceState({}, '', window.location.pathname + (rest ? '?' + rest : ''));
    }

    document.addEventListener('DOMContentLoaded', function () {
        initGallery();
        initPromoModal();
        initQuickBooking();
        initNewsletter();
        initSteps();
        initQuickResult();
    });
})(window, document);
