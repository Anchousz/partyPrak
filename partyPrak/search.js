// search.js
(function() {
    'use strict';

    const mockLocations = [
        {
            id: 1,
            name: 'Озеро Красилово',
            address: 'Алтайский край, Косихинский район, с. Озеро-Красилово, ул. Пушкина д.1',
            region: 'Алтайский край',
            freeZones: 5,
            image: 'images/location/2.jpg'
        },
        {
            id: 2,
            name: 'Парк-отель Чайка',
            address: 'Красноярский край, Ачинский район, с. Ключи, Пионерская долина, 5',
            region: 'Красноярский край',
            freeZones: 3,
            image: 'images/location/6.jpg'
        },
        {
            id: 3,
            name: 'Локация 3',
            address: 'Московская область, Дмитровский район',
            region: 'Московская область',
            freeZones: 2,
            image: 'https://placehold.co/600x400/48DBFB/ffffff?text=Location+3'
        },
        {
            id: 4,
            name: 'Локация 4',
            address: 'Ленинградская область, Всеволожский район',
            region: 'Ленинградская область',
            freeZones: 7,
            image: 'https://placehold.co/600x400/a29bfe/ffffff?text=Location+4'
        }
    ];

    function getSearchParams() {
        const urlParams = new URLSearchParams(window.location.search);
        return {
            region: urlParams.get('region') || 'Алтайский край',
            date: urlParams.get('event-date') || '',
            adults: urlParams.get('adults') || '2',
            children: urlParams.get('children') || '5'
        };
    }

    function displaySearchParams() {
        const params = getSearchParams();
        const container = document.getElementById('searchParams');
        if (container) {
            container.innerHTML = `
                <div class="search-params__card">
                    <p><strong>Регион:</strong> ${params.region}</p>
                    <p><strong>Дата:</strong> ${params.date || 'не указана'}</p>
                    <p><strong>Взрослых:</strong> ${params.adults}, <strong>Детей:</strong> ${params.children}</p>
                </div>
            `;
        }
    }

    function renderResults() {
        const params = getSearchParams();
        const grid = document.getElementById('searchResultsGrid');
        if (!grid) return;

        const filtered = mockLocations.filter(loc => loc.region === params.region);
        
        if (filtered.length === 0) {
            grid.innerHTML = '<p class="no-results">По вашему запросу ничего не найдено в этом регионе.</p>';
            return;
        }

        grid.innerHTML = filtered.map(loc => `
            <div class="location-card reveal">
                <img src="${loc.image}" alt="${loc.name}" loading="lazy" style="width:100%; height:200px; object-fit:cover; border-radius:20px;">
                <h3>${loc.name}</h3>
                <p class="location-address">${loc.address}</p>
                <p><strong>Дата мероприятия:</strong> ${params.date || 'не выбрана'}</p>
                <p><strong>Свободных зон:</strong> ${loc.freeZones}</p>
                <button class="btn btn--primary select-location-btn" 
                        data-id="${loc.id}" 
                        data-name="${loc.name}" 
                        data-address="${loc.address}"
                        data-region="${loc.region}">Выбрать</button>
            </div>
        `).join('');

        grid.addEventListener('click', (e) => {
            const btn = e.target.closest('.select-location-btn');
            if (btn) {
                e.preventDefault();
                const locationData = {
                    id: btn.dataset.id,
                    name: btn.dataset.name,
                    address: btn.dataset.address,
                    region: btn.dataset.region,
                    date: params.date,
                    guests: parseInt(params.adults || 0) + parseInt(params.children || 0)
                };
                sessionStorage.setItem('selectedLocation', JSON.stringify(locationData));
                window.location.href = 'seat.html';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('searchParams') || document.getElementById('searchResultsGrid')) {
            displaySearchParams();
            renderResults();
        }
    });
})();