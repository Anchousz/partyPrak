(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {

        // 1. ФИКС БИТЫХ КАРТИНОК
        const fixBrokenImages = () => {
            const images = document.querySelectorAll('img');
            images.forEach(img => {
                img.addEventListener('error', function() {
                    if (!this.dataset.tried) {
                        this.dataset.tried = "true";
                        const src = this.src;
                        if (src.includes('.jpg')) {
                            this.src = src.replace('.jpg', '.png');
                        } else if (src.includes('.png')) {
                            this.src = src.replace('.png', '.jpg');
                        } else {
                            this.src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";
                            this.style.backgroundColor = "#f0f0f0";
                        }
                    }
                }, { once: true });
            });
        };
        fixBrokenImages();

        // 2. SCROLL HEADER
        const header = document.querySelector('.header');
        if (header) {
            window.addEventListener('scroll', () => {
                header.classList.toggle('scrolled', window.scrollY > 50);
            });
        }

        // 3. REVEAL ANIMATION
        const revealElements = () => {
            const reveals = document.querySelectorAll('.reveal');
            reveals.forEach(el => {
                const elementTop = el.getBoundingClientRect().top;
                if (elementTop < window.innerHeight - 100) {
                    el.classList.add('active');
                }
            });
        };
        window.addEventListener('scroll', revealElements);
        revealElements();

        // 4. MOBILE MENU
        const navToggle = document.querySelector('.nav__toggle');
        const navList = document.querySelector('.nav__list');
        if (navToggle && navList) {
            navToggle.addEventListener('click', () => {
                navList.classList.toggle('active');
            });
        }

        // 5. MODALS INIT (Booking & Locations)
        let bookingModalCtrl = null;
        let locationModalCtrl = null;
        if (typeof window.setupModal === 'function') {
            if (document.getElementById('bookingModal')) {
                bookingModalCtrl = window.setupModal('bookingModal', '.js-open-modal', '.js-close-modal');
            }
            if (document.getElementById('locationModal')) {
                locationModalCtrl = window.setupModal('locationModal', '.js-open-location-modal', '.js-close-location-modal');
            }
        }

        // Контроллер для доступа к модалкам из других функций
        const modalController = {
            booking: bookingModalCtrl,
            location: locationModalCtrl
        };

        // 11. ЛОГИКА ПОП-АПОВ ДЛЯ АКЦИЙ
        const promoModal = document.getElementById('promoModal');
        const promoContent = document.getElementById('promoModalContent');

        const promoData = [
            {
                title: 'Скидка до 60%',
                subtitle: 'Локация «Черная жемчужина»',
                image: 'images/promotions/promotion1.jpg',
                body: '<p>Заказывайте место в локации «Черная жемчужина». Вас ждет настоящая пиратская шхуна!</p><ul><li><strong>25-30 чел:</strong> Скидка 40%</li><li><strong>31-50 чел:</strong> Скидка 55%</li></ul>'
            },
            {
                title: 'Скидка до 51%',
                subtitle: 'Локация «Поляна сказок»',
                image: 'images/promotions/promotion2.jpg',
                body: '<p>Уютное место на берегу озера. Скидка действует при заказе услуг аниматоров.</p><ul><li>Частная территория</li><li>Обработано от насекомых</li></ul>'
            },
            {
                title: 'Скидка до 25%',
                subtitle: 'Праздник "Под ключ"',
                image: 'images/promotions/promotion3.jpg',
                body: '<p>Экономьте при комплексном заказе услуг:</p><ul><li><strong>Скидка 25%</strong> — группы от 25 чел.</li><li><strong>Скидка 10%</strong> — группы от 15 чел.</li></ul>'
            }
        ];

        const closePromo = () => {
            if (promoModal) {
                promoModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        };

        document.querySelectorAll('.promo-card__btn').forEach((btn, index) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const data = promoData[index];
                if (data && promoModal && promoContent) {
                    promoContent.innerHTML = `
                        <img src="${data.image}" style="width:100%; height:180px; object-fit:cover; border-radius:15px; margin-bottom:15px;">
                        <h3 class="modal__title">${data.title}</h3>
                        <p style="font-weight:bold; color:var(--color-primary);">${data.subtitle}</p>
                        <div style="text-align:left; margin-top:15px;">${data.body}</div>
                        <button class="btn btn--primary btn--full js-close-promo-modal" style="margin-top:20px;">Понятно</button>
                    `;
                    promoModal.classList.add('active');
                    document.body.style.overflow = 'hidden';

                    const closeBtn = promoContent.querySelector('.js-close-promo-modal');
                    if (closeBtn) closeBtn.onclick = closePromo;
                }
            });
        });

        document.querySelectorAll('.js-close-promo-modal').forEach(el => {
            el.addEventListener('click', closePromo);
        });

        // 9. ПОИСК
        const searchForm = document.getElementById('searchForm');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                window.location.href = 'search.html?' + new URLSearchParams(new FormData(searchForm)).toString();
            });
        }

        // =======================================================
        // 12. НОВАЯ ЛОГИКА: АВТОМАТИЧЕСКИЙ ВЫБОР ЛОКАЦИИ И БРОНЬ
        // =======================================================
        
        // Функция выбора локации в форме и открытия модалки
        const selectLocationAndOpenModal = (locationValue) => {
            const selectElement = document.getElementById('location-select');
            
            if (selectElement) {
                // Пытаемся найти опцию по value
                let optionExists = false;
                for (let i = 0; i < selectElement.options.length; i++) {
                    if (selectElement.options[i].value === locationValue) {
                        selectElement.selectedIndex = i;
                        optionExists = true;
                        break;
                    }
                }
                
                // Если по value не нашли, пробуем по тексту (как резерв)
                if (!optionExists) {
                    for (let i = 0; i < selectElement.options.length; i++) {
                        if (selectElement.options[i].text.includes(locationValue)) {
                            selectElement.selectedIndex = i;
                            break;
                        }
                    }
                }
            }

            // Открываем модалку бронирования, если она инициализирована
            if (modalController.booking && typeof modalController.booking.open === 'function') {
                modalController.booking.open();
            } else {
                // Фолбэк, если контроллер еще не готов или простая реализация
                const modal = document.getElementById('bookingModal');
                if (modal) {
                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            }
        };

        // Обработчик клика по кнопкам "Забронировать" в карточках локаций
        const bookingTriggers = document.querySelectorAll('.js-trigger-booking');
        bookingTriggers.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const location = btn.dataset.location; // Получаем krasilovo, chaika и т.д.
                if (location) {
                    selectLocationAndOpenModal(location);
                }
            });
        });

        // Обработчик выбора в модальном окне локаций (шаг 3)
        // Если там кликают на карточку, мы закрываем окно локаций и открываем бронь
        const locationModalItems = document.querySelectorAll('.js-select-location');
        locationModalItems.forEach(item => {
            item.addEventListener('click', function() {
                const location = this.dataset.location;
                
                // 1. Закрываем модалку локаций
                if (modalController.location) {
                    modalController.location.close();
                }

                // 2. Ждем анимацию закрытия (немного) и открываем бронь с выбранной локацией
                setTimeout(() => {
                    if (location) {
                        selectLocationAndOpenModal(location);
                    }
                }, 300);
            });
        });

        // Проверка URL параметров при загрузке страницы
        // (Например: index.html?location=krasilovo)
        const urlParams = new URLSearchParams(window.location.search);
        const locationParam = urlParams.get('location');
        if (locationParam) {
            // Небольшая задержка, чтобы сайт успел прогрузиться визуально
            setTimeout(() => {
                selectLocationAndOpenModal(locationParam);
            }, 500);
        }

(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', () => {
        // Фикс картинок
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            img.addEventListener('error', function() {
                this.src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";
            }, { once: true });
        });

        // Скролл шапки
        const header = document.querySelector('.header');
        if (header) {
            window.addEventListener('scroll', () => {
                header.classList.toggle('scrolled', window.scrollY > 50);
            });
        }
    });
})();

    });
})();