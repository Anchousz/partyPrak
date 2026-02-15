// lightbox.js
(function() {
    'use strict';

    window.lightboxInit = function() {
        const lightbox = document.getElementById('lightbox');
        if (!lightbox) return;

        const lightboxImg = lightbox.querySelector('.lightbox__img');
        const closeTriggers = lightbox.querySelectorAll('.js-close-lightbox');

        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('.js-lightbox-trigger');
            if (!trigger) return;
            e.preventDefault();

            if (lightboxImg) {
                lightboxImg.src = trigger.src;
                lightbox.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            }
        });

        const closeLightbox = () => {
            lightbox.classList.remove('is-open');
            document.body.style.overflow = '';
            if (lightboxImg) setTimeout(() => { lightboxImg.src = ''; }, 300);
        };

        closeTriggers.forEach(el => el.addEventListener('click', closeLightbox));

        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && lightbox.classList.contains('is-open')) {
                closeLightbox();
            }
        });
    };
})();