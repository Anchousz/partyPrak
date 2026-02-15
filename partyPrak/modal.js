// modal.js
(function() {
    'use strict';

    window.setupModal = function(modalId, openTriggerClass, closeTriggerClass) {
        const modal = document.getElementById(modalId);
        if (!modal) return null;

        const open = () => {
            modal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        };
        const close = () => {
            modal.classList.remove('is-open');
            document.body.style.overflow = '';
        };
        const toggle = () => {
            modal.classList.contains('is-open') ? close() : open();
        };

        // Открытие
        document.addEventListener('click', (e) => {
            if (e.target.closest(openTriggerClass)) {
                e.preventDefault();
                open();
            }
        });

        // Закрытие по триггеру
        document.addEventListener('click', (e) => {
            if (e.target.closest(closeTriggerClass)) {
                e.preventDefault();
                close();
            }
        });

        // ESC
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                close();
            }
        });

        return { open, close, toggle };
    };
})();

document.addEventListener('DOMContentLoaded', () => {
    const promoModal = document.getElementById('promoModal');
    const promoContent = document.getElementById('promoModalContent');
    const closeBtns = document.querySelectorAll('.js-close-promo-modal');

    // Данные акций
    const promoData = {
        1: {
            title: 'Скидка до 60%',
            subtitle: 'За заказ зоны (от 25 человек) в локации «Черная жемчужина»',
            image: 'images/promotions/promotion1.jpg',
            body: `
                <p>Устали от городского шума, хотите провести праздник за городом в живописной локации? Заказывайте место в локации «Черная жемчужина». Здесь вас и ваших детей ждет настоящая пиратская шхуна с настоящими пиратами!</p>
                <ul>
                    <li><strong>Заказ от 25 до 30 человек:</strong> Скидка 40% на аренду в будний день</li>
                    <li><strong>Заказ от 31 до 50 человек:</strong> Скидка 55% на аренду в будний день</li>
                </ul>
                <p class="promo-note">* Купон действует в будние дни при наличии свободных мест</p>
            `
        },
        2: {
            title: 'Скидка до 51%',
            subtitle: 'Уютное место «Поляна сказок»',
            image: 'images/promotions/promotion2.jpg',
            body: `
                <p>Локация «Поляна сказок» расположена в живописной загородной местности на берегу чистого озера. Есть места для организации праздника как на открытом воздухе, так и под навесом.</p>
                <p><strong>Скидка 51%</strong> на аренду зоны при условии заказа услуг аниматоров.</p>
                <p>Преимущества:</p>
                <ul>
                    <li>Безопасность (частная охраняемая территория);</li>
                    <li>Мало людей (площадь — 200 кв. м);</li>
                    <li>Защита от насекомых (территория обработана от клещей);</li>
                    <li>Обустроенный тематический лагерь.</li>
                </ul>
                <p class="promo-note">Обязательна предварительная запись по телефону.</p>
            `
        },
        3: {
            title: 'Скидка до 25%',
            subtitle: 'Закажи праздник "Под ключ"',
            image: 'images/promotions/promotion3.jpg',
            body: `
                <p>При выборе локации для 15 человек и больше, а также при заказе всех дополнительных услуг можно очень неплохо сэкономить!</p>
                <ul>
                    <li><strong>Скидка 25%</strong> — для групп от 25 человек.</li>
                    <li><strong>Скидка 10%</strong> — для групп от 15 человек.</li>
                </ul>
                <p class="promo-note">Обязательна предварительная запись по телефону.</p>
            `
        }
    };

    // Открытие модалки
    document.querySelectorAll('.promo-card__btn').forEach((btn, index) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const data = promoData[index + 1]; // Берем данные по индексу кнопки
            if (data) {
                promoContent.innerHTML = `
                    <img src="${data.image}" class="promo-modal-img" alt="">
                    <h3 class="modal__title">${data.title}</h3>
                    <p class="promo-modal-subtitle"><strong>${data.subtitle}</strong></p>
                    <div class="promo-modal-body">${data.body}</div>
                    <button class="btn btn--primary btn--full js-open-modal" style="margin-top:20px">Забронировать по акции</button>
                `;
                promoModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    });

    // Закрытие
    closeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            promoModal.classList.remove('active');
            document.body.style.overflow = '';
        });
    });
});