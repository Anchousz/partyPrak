/* ==========================================================================
   catalog.js — общее поведение страниц каталога: галерея площадки.
   ========================================================================== */

(function (window, document) {
    'use strict';

    var PVD = window.PVD;
    if (!PVD || !PVD.data) return;

    /** Находит локацию по слагу среди переданных сервером. */
    function locationBySlug(slug) {
        var list = PVD.data.locations || [];
        for (var i = 0; i < list.length; i++) {
            if (list[i].id === slug) return list[i];
        }
        return null;
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-gallery]');
        if (!trigger) return;
        event.preventDefault();

        var loc = locationBySlug(trigger.getAttribute('data-gallery'));
        if (!loc || !loc.gallery || !loc.gallery.length) return;

        PVD.openLightbox(loc.gallery.map(function (src) {
            return { src: src, alt: loc.name };
        }), 0);
    });
})(window, document);
