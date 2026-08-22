/* ==========================================================================
   booking.js — живой пересчёт итога на странице оформления.

   Итоговая сумма, которая попадает в базу, считается на сервере из цен в БД.
   Здесь только предпросмотр: подправить число в браузере и «удешевить» заказ
   не получится.
   ========================================================================== */

(function (window, document) {
    'use strict';

    var PVD = window.PVD;
    if (!PVD || !PVD.data) return;

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('bookingForm');
        if (!form) {
            /* Экран успешного оформления — просто салют. */
            if (document.querySelector('[data-confetti]') && PVD.confetti) PVD.confetti(140);
            return;
        }

        var guestsField = document.getElementById('bookingGuests');
        var dateField   = document.getElementById('bookingDate');
        var totalEl     = document.getElementById('summaryTotal');
        var list        = document.getElementById('summaryList');
        var note        = document.getElementById('guestsNote');

        var zonesData = PVD.data.zones || [];
        /* Зоны выбраны на прошлом шаге и приходят скрытыми полями. */
        var chosenZoneSlugs = Array.prototype.map.call(
            form.querySelectorAll('input[name="zones[]"]'),
            function (input) { return input.value; }
        );
        var chosenZones = zonesData.filter(function (z) {
            return chosenZoneSlugs.indexOf(z.id) !== -1;
        });

        var locationLabel = list ? list.querySelector('.summary__label').textContent : '';

        function guestCount() {
            var value = parseInt(guestsField && guestsField.value, 10);
            return isNaN(value) || value < 1 ? 1 : value;
        }

        function serviceCost(price, unit, hours, guests) {
            if (unit === 'guest') return price * guests;
            if (unit === 'hour')  return price * Math.max(hours, 1);
            return price;
        }

        function update() {
            var guests = guestCount();
            var total = 0;
            var rows = [];

            rows.push(
                '<li><span class="summary__label">' + PVD.escapeHtml(locationLabel) + '</span>' +
                '<span class="summary__value">' +
                PVD.escapeHtml(dateField && dateField.value ? PVD.formatDate(dateField.value) : 'дата не выбрана') +
                '</span></li>'
            );

            chosenZones.forEach(function (zone) {
                var cost = zone.perPerson ? zone.price * guests : zone.price;
                total += cost;
                rows.push(
                    '<li><span class="summary__label">' + PVD.escapeHtml(zone.name) + '</span>' +
                    '<span class="summary__value">' + PVD.money(cost) + '</span></li>'
                );
            });

            Array.prototype.forEach.call(
                form.querySelectorAll('input[name="services[]"]:checked'),
                function (input) {
                    var price = parseInt(input.getAttribute('data-price'), 10) || 0;
                    var hours = parseInt(input.getAttribute('data-hours'), 10) || 1;
                    var cost  = serviceCost(price, input.getAttribute('data-unit'), hours, guests);
                    var name  = input.closest('.check-card').querySelector('.check-card__name').textContent;
                    total += cost;
                    rows.push(
                        '<li><span class="summary__label">' + PVD.escapeHtml(name) + '</span>' +
                        '<span class="summary__value">' + PVD.money(cost) + '</span></li>'
                    );
                }
            );

            if (list)    list.innerHTML = rows.join('');
            if (totalEl) PVD.setAnimatedValue(totalEl, PVD.money(total));

            if (note) {
                var capacity = chosenZones.reduce(function (sum, z) { return sum + z.capacity; }, 0);
                note.hidden = capacity >= guests;
            }
        }

        form.addEventListener('change', update);
        form.addEventListener('input', function (event) {
            if (event.target === guestsField) update();
        });

        update();
    });
})(window, document);
