// seat.js
(function() {
    'use strict';

    const mockZones = [
        { id: 1, name: 'Шатер', price: 5000, capacity: 50, booked: false },
        { id: 2, name: 'Летний домик', price: 3000, capacity: 10, booked: true },
        { id: 3, name: 'Беседка', price: 2000, capacity: 15, booked: false },
        { id: 4, name: 'Игровая комната', price: 300, capacity: 10, booked: false, perPerson: true },
        { id: 5, name: 'Спортивная площадка', price: 200, capacity: 15, booked: false, perPerson: true }
    ];

    let selectedZones = [];

    function loadLocationInfo() {
        const locationData = JSON.parse(sessionStorage.getItem('selectedLocation') || '{}');
        const infoDiv = document.getElementById('locationInfo');
        if (infoDiv) {
            infoDiv.innerHTML = `
                <h2>${locationData.name || 'Не выбрано'}</h2>
                <p>${locationData.address || ''}</p>
                <p>Дата: ${locationData.date || 'не указана'}</p>
                <p>Количество гостей: ${locationData.guests || 0}</p>
            `;
        }
        return locationData;
    }

    function renderZones() {
        const container = document.getElementById('zonesContainer');
        if (!container) return;

        container.innerHTML = mockZones.map(zone => `
            <div class="zone-card ${zone.booked ? 'booked' : 'available'}" 
                 data-id="${zone.id}" 
                 data-price="${zone.price}" 
                 data-per-person="${zone.perPerson || false}">
                <h4>${zone.name}</h4>
                <p class="capacity">Вместимость: до ${zone.capacity} чел.</p>
                <p class="price">${zone.price} ${zone.perPerson ? '₽/чел' : '₽/сутки'}</p>
                ${zone.booked ? '<span class="badge">Занято</span>' : ''}
            </div>
        `).join('');

        document.querySelectorAll('.zone-card.available').forEach(card => {
            card.addEventListener('click', function() {
                this.classList.toggle('selected');
                const zoneId = parseInt(this.dataset.id);
                const price = parseInt(this.dataset.price);
                const perPerson = this.dataset.perPerson === 'true';

                if (this.classList.contains('selected')) {
                    selectedZones.push({ id: zoneId, price, perPerson });
                } else {
                    selectedZones = selectedZones.filter(z => z.id !== zoneId);
                }
                updateSummary();
            });
        });
    }

    function updateSummary() {
        const locationData = JSON.parse(sessionStorage.getItem('selectedLocation') || '{}');
        const guests = locationData.guests || 0;
        
        let total = selectedZones.reduce((sum, zone) => {
            return sum + (zone.perPerson ? zone.price * guests : zone.price);
        }, 0);

        const countSpan = document.getElementById('selectedZonesCount');
        const priceSpan = document.getElementById('totalZonePrice');
        if (countSpan) countSpan.textContent = selectedZones.length;
        if (priceSpan) priceSpan.textContent = total;

        sessionStorage.setItem('selectedZones', JSON.stringify(selectedZones));
        sessionStorage.setItem('totalZonePrice', total);
    }

    function setupBookingNavigation() {
        const btn = document.getElementById('goToBookingBtn');
        if (btn) {
            btn.addEventListener('click', () => {
                if (selectedZones.length === 0) {
                    alert('Пожалуйста, выберите хотя бы одну зону.');
                    return;
                }
                window.location.href = 'booking.html';
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('zonesContainer')) {
            loadLocationInfo();
            renderZones();
            setupBookingNavigation();
        }
    });
})();