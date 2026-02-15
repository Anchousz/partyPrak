// form.js
(function() {
    'use strict';

    window.formInit = function(modalCloseCallback) {
        const form = document.querySelector('.booking-form');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.textContent;

            btn.textContent = 'Отправка...';
            btn.disabled = true;

            setTimeout(() => {
                alert('🎉 Волшебство началось! Мы скоро свяжемся с вами.');
                
                if (typeof modalCloseCallback === 'function') {
                    modalCloseCallback();
                }

                form.reset();
                btn.textContent = originalText;
                btn.disabled = false;
            }, 1500);
        });
    };
})();