// location-select.js
(function() {
    'use strict';

    window.locationSelectInit = function(modalController) {
        const locationSelect = document.getElementById('location-select');
        // Если select нет на странице (например, не главная), просто выходим
        if (!locationSelect) return;

        document.addEventListener('click', (e) => {
            const card = e.target.closest('.js-select-location');
            if (!card) return;

            e.preventDefault();

            // Сброс выделения
            document.querySelectorAll('.js-select-location').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');

            const locationName = card.dataset.location;
            if (!locationName) return;

            // Попытка найти опцию в селекте
            const options = Array.from(locationSelect.options);
            const optionToSelect = options.find(opt => opt.text === locationName);
            
            if (optionToSelect) {
                locationSelect.value = optionToSelect.value;
            } else {
                const fallback = options.find(opt => 
                    locationName.toLowerCase().includes(opt.text.toLowerCase()) ||
                    opt.text.toLowerCase().includes(locationName.toLowerCase())
                );
                if (fallback) locationSelect.value = fallback.value;
            }

            // Закрываем модалку локаций, открываем бронирование
            if (modalController && modalController.location && typeof modalController.location.close === 'function') {
                modalController.location.close();
            }

            setTimeout(() => {
                if (modalController && modalController.booking && typeof modalController.booking.open === 'function') {
                    modalController.booking.open();
                }
            }, 350);
        });
    };
})();