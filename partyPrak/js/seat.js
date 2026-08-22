/* ==========================================================================
   seat.js — живой пересчёт стоимости зон.

   Страница работает и без этого скрипта: зоны — обычные чекбоксы внутри
   формы, а окончательную сумму всё равно считает PHP. Здесь только
   мгновенная обратная связь, пока пользователь выбирает.
   ========================================================================== */

(function (window, document) {
    'use strict';

    var PVD = window.PVD;
    if (!PVD) return;

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('zonesForm');
        if (!form) return;

        var guests   = (PVD.data && PVD.data.guests) || 1;
        var list     = document.getElementById('zoneSummaryList');
        var totalEl  = document.getElementById('zoneTotal');
        var submit   = document.getElementById('goToBooking');
        var note     = document.getElementById('capacityNote');
        var checkboxes = form.querySelectorAll('.zone__input');

        function update() {
            var total = 0;
            var capacity = 0;
            var rows = [];

            Array.prototype.forEach.call(checkboxes, function (input) {
                if (!input.checked) return;

                var price     = parseInt(input.getAttribute('data-price'), 10) || 0;
                var perPerson = input.getAttribute('data-per-person') === '1';
                var cost      = perPerson ? price * Math.max(guests, 1) : price;
                var name      = input.closest('.zone').querySelector('.zone__name').textContent;

                total    += cost;
                capacity += parseInt(input.getAttribute('data-capacity'), 10) || 0;

                rows.push(
                    '<li><span class="summary__label">' + PVD.escapeHtml(name) + '</span>' +
                    '<span class="summary__value">' + PVD.money(cost) + '</span></li>'
                );
            });

            if (list) {
                list.innerHTML = rows.length
                    ? rows.join('')
                    : '<li class="summary__empty">Зоны пока не выбраны</li>';
            }
            if (totalEl) PVD.setAnimatedValue(totalEl, PVD.money(total));
            if (submit)  submit.disabled = rows.length === 0;

            /* Предупреждаем, если выбранных мест меньше, чем гостей. */
            if (note) {
                if (rows.length && capacity < guests) {
                    note.hidden = false;
                    note.textContent = 'Выбранные зоны вмещают ' + capacity + ' ' +
                        PVD.plural(capacity, ['гостя', 'гостей', 'гостей']) +
                        ', а в заявке ' + guests + '. Добавьте ещё одну зону.';
                } else {
                    note.hidden = true;
                }
            }
        }

        form.addEventListener('change', function (event) {
            if (event.target.classList.contains('zone__input')) update();
        });

        update();
    });
})(window, document);
