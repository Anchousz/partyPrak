(function() {
    'use strict';

    // 1. ОБНОВЛЕННАЯ БАЗА ДАННЫХ ЛОКАЦИЙ (на основе ваших данных)
    const regionData = {
        'krasilovo': {
            name: 'Турбаза "Озеро Красилово"',
            region: 'Алтайский край',
            address: 'Косихинский р-н, с. Озеро-Красилово, ул. Пушкина д.1',
            price: 2000 // Базовая цена за беседку
        },
        'chaika': {
            name: 'Парк-отель "Чайка"',
            region: 'Красноярский край',
            address: 'Ачинский р-н, с. Ключи, Пионерская долина, 5',
            price: 2000 // Базовая цена за беседку
        }
    };

    // 2. ЗАГРУЗКА ДАННЫХ ПРИ ВХОДЕ НА СТРАНИЦУ
    function loadBookingData() {
        // Получаем данные, которые пользователь выбрал на главной странице
        const locationData = JSON.parse(sessionStorage.getItem('selectedLocation') || '{}');
        const savedPrice = sessionStorage.getItem('totalZonePrice');

        const regionSelect = document.getElementById('booking-region');
        const addressField = document.getElementById('booking-address');
        const dateField = document.getElementById('booking-date');
        const guestsField = document.getElementById('booking-guests');
        const basePriceField = document.getElementById('booking-base-price');

        // Если есть ID выбранной локации (krasilovo или chaika)
        if (locationData.id && regionData[locationData.id]) {
            const info = regionData[locationData.id];
            
            if (regionSelect) regionSelect.value = info.region;
            if (addressField) addressField.value = `${info.name}, ${info.address}`;
            if (basePriceField) basePriceField.value = savedPrice || info.price;
        }

        if (dateField) dateField.value = locationData.date || '';
        if (guestsField) guestsField.value = locationData.guests || 10;

        calculateTotal();
    }

    // 3. ЛОГИКА РАСЧЕТА ИТОГОВОЙ СТОИМОСТИ
    function calculateTotal() {
        const basePriceField = document.getElementById('booking-base-price');
        const guestsField = document.getElementById('booking-guests');
        const totalPriceElement = document.getElementById('totalPrice');
        
        if (!totalPriceElement) return;

        let basePrice = basePriceField ? (parseInt(basePriceField.value) || 0) : 0;
        const guests = guestsField ? (parseInt(guestsField.value) || 0) : 1;

        let servicesTotal = 0;

        document.querySelectorAll('input[name="service"]:checked').forEach(cb => {
            const price = parseInt(cb.dataset.price || 0);
            if (cb.value === 'catering') {
                servicesTotal += price * guests; 
            } else if (cb.value === 'host') {
                servicesTotal += price * 2; // Допустим, заказ ведущего минимум на 2 часа
            } else {
                servicesTotal += price; 
            }
        });

        const finalSum = basePrice + servicesTotal;
        totalPriceElement.textContent = finalSum.toLocaleString('ru-RU');
    }

    // 4. ИНИЦИАЛИЗАЦИЯ
    document.addEventListener('DOMContentLoaded', () => {
        const bookingForm = document.getElementById('bookingFormDetailed');
        
        if (bookingForm) {
            loadBookingData();

            // Логика ручной смены региона в выпадающем списке
            const regionSelect = document.getElementById('booking-region');
            const addressField = document.getElementById('booking-address');

            if (regionSelect) {
                regionSelect.addEventListener('change', function() {
                    // Если пользователь меняет регион вручную
                    if (this.value === 'Алтайский край') {
                        addressField.value = 'Турбаза "Озеро Красилово", Косихинский р-н';
                    } else if (this.value === 'Красноярский край') {
                        addressField.value = 'Парк-отель "Чайка", Ачинский р-н';
                    } else {
                        addressField.value = '';
                    }
                    calculateTotal();
                });
            }

            // Слушатели на чекбоксы услуг
            const services = document.querySelectorAll('input[name="service"]');
            services.forEach(cb => {
                cb.addEventListener('change', calculateTotal);
            });

            // Слушатель на изменение кол-ва гостей
            const guestsField = document.getElementById('booking-guests');
            if (guestsField) {
                guestsField.addEventListener('input', calculateTotal);
            }

            // Отправка формы
            bookingForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const email = document.getElementById('booking-email').value;
                if (!email) {
                    alert('Пожалуйста, введите Email');
                    return;
                }
                alert(`Бронирование оформлено! Итоговая сумма: ${document.getElementById('totalPrice').textContent} ₽. Информация отправлена на ${email}`);
                sessionStorage.removeItem('selectedLocation');
                window.location.href = 'index.html';
            });
        }
    });
})();