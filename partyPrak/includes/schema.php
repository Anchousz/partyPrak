<?php
/**
 * schema.php — структура базы и стартовые данные.
 * Используется установщиком (install.php); в обычной работе сайта не нужен.
 */

declare(strict_types=1);

/** DDL всех таблиц. Порядок важен: bookings ссылается на locations. */
function schema_tables(): array
{
    return [
        'locations' => "
            CREATE TABLE IF NOT EXISTS locations (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug        VARCHAR(40)  NOT NULL UNIQUE,
                name        VARCHAR(120) NOT NULL,
                region      VARCHAR(120) NOT NULL,
                address     VARCHAR(255) NOT NULL,
                image       VARCHAR(255) NOT NULL,
                plan        VARCHAR(255) DEFAULT NULL,
                summary     TEXT         NOT NULL,
                tags        TEXT         NOT NULL,
                gallery     TEXT         NOT NULL,
                free_zones  TINYINT UNSIGNED NOT NULL DEFAULT 0,
                price_from  INT UNSIGNED NOT NULL DEFAULT 0,
                capacity    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                is_active   TINYINT(1)   NOT NULL DEFAULT 1,
                sort_order  SMALLINT     NOT NULL DEFAULT 0,
                INDEX idx_region (region),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'zones' => "
            CREATE TABLE IF NOT EXISTS zones (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug       VARCHAR(40)  NOT NULL UNIQUE,
                name       VARCHAR(120) NOT NULL,
                price      INT UNSIGNED NOT NULL,
                capacity   SMALLINT UNSIGNED NOT NULL,
                per_person TINYINT(1)   NOT NULL DEFAULT 0,
                is_booked  TINYINT(1)   NOT NULL DEFAULT 0,
                sort_order SMALLINT     NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'services' => "
            CREATE TABLE IF NOT EXISTS services (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug       VARCHAR(40)  NOT NULL UNIQUE,
                name       VARCHAR(120) NOT NULL,
                price      INT UNSIGNED NOT NULL,
                unit       ENUM('guest','hour','fixed') NOT NULL DEFAULT 'fixed',
                hours      TINYINT UNSIGNED NOT NULL DEFAULT 1,
                note       VARCHAR(255) NOT NULL DEFAULT '',
                is_active  TINYINT(1)   NOT NULL DEFAULT 1,
                sort_order SMALLINT     NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'promos' => "
            CREATE TABLE IF NOT EXISTS promos (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug        VARCHAR(40)  NOT NULL UNIQUE,
                discount    VARCHAR(20)  NOT NULL,
                title       VARCHAR(160) NOT NULL,
                summary     TEXT         NOT NULL,
                image       VARCHAR(255) NOT NULL,
                location_id INT UNSIGNED DEFAULT NULL,
                details     TEXT         NOT NULL,
                fineprint   VARCHAR(255) NOT NULL DEFAULT '',
                is_active   TINYINT(1)   NOT NULL DEFAULT 1,
                sort_order  SMALLINT     NOT NULL DEFAULT 0,
                CONSTRAINT fk_promo_location FOREIGN KEY (location_id)
                    REFERENCES locations(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'price_list' => "
            CREATE TABLE IF NOT EXISTS price_list (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                service    VARCHAR(160) NOT NULL,
                note       VARCHAR(255) NOT NULL DEFAULT '',
                value      VARCHAR(60)  NOT NULL,
                sort_order SMALLINT     NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'bookings' => "
            CREATE TABLE IF NOT EXISTS bookings (
                id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                status       ENUM('new','confirmed','done','cancelled') NOT NULL DEFAULT 'new',
                source       ENUM('form','quick') NOT NULL DEFAULT 'form',
                location_id  INT UNSIGNED DEFAULT NULL,
                event_date   DATE         DEFAULT NULL,
                adults       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                children     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                guests       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                zones        TEXT         NOT NULL,
                services     TEXT         NOT NULL,
                total        INT UNSIGNED NOT NULL DEFAULT 0,
                customer     VARCHAR(160) NOT NULL DEFAULT '',
                phone        VARCHAR(40)  NOT NULL DEFAULT '',
                email        VARCHAR(160) NOT NULL DEFAULT '',
                comment      TEXT         NOT NULL,
                INDEX idx_status (status),
                INDEX idx_created (created_at),
                CONSTRAINT fk_booking_location FOREIGN KEY (location_id)
                    REFERENCES locations(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
}

/** Стартовый каталог — тот же, что раньше жил в js/data.js. */
function schema_seed(): array
{
    return [
        'locations' => [
            [
                'slug' => 'krasilovo', 'name' => 'Озеро Красилово',
                'region' => 'Алтайский край',
                'address' => 'Косихинский р-н, с. Озеро-Красилово, ул. Пушкина, 1',
                'image' => 'images/location/2.jpg', 'plan' => 'images/plan/1.jpg',
                'summary' => 'Берег озера, сосны и открытая поляна для активных игр. Территория обработана от клещей.',
                'tags' => ['На природе', 'До 50 гостей'],
                'gallery' => ['images/location/2.jpg', 'images/location/3.jpg', 'images/location/4.jpg', 'images/location/5.jpg'],
                'free_zones' => 5, 'price_from' => 2000, 'capacity' => 50, 'sort_order' => 1,
            ],
            [
                'slug' => 'chaika', 'name' => 'Парк-отель «Чайка»',
                'region' => 'Красноярский край',
                'address' => 'Ачинский р-н, с. Ключи, Пионерская долина, 5',
                'image' => 'images/location/6.jpg', 'plan' => 'images/plan/2.jpg',
                'summary' => 'Закрытая охраняемая территория с бассейном, беседками и крытым павильоном на случай дождя.',
                'tags' => ['Крытый павильон', 'Бассейн'],
                'gallery' => ['images/location/6.jpg', 'images/location/7.png', 'images/location/8.jpg', 'images/location/10.jpg'],
                'free_zones' => 3, 'price_from' => 3500, 'capacity' => 40, 'sort_order' => 2,
            ],
            [
                'slug' => 'les', 'name' => 'Волшебный лес',
                'region' => 'Алтайский край',
                'address' => 'Первомайский р-н, Лесная поляна, 12',
                'image' => 'images/location/17.jpeg', 'plan' => 'images/plan/3.jpeg',
                'summary' => 'Тематическая площадка для квестов и приключений: тропы, шалаши и настоящий лагерь искателей.',
                'tags' => ['Квесты', 'Тематическая'],
                'gallery' => ['images/location/17.jpeg', 'images/location/18.jpg', 'images/location/19.jpg', 'images/location/20.jpg'],
                'free_zones' => 4, 'price_from' => 2500, 'capacity' => 30, 'sort_order' => 3,
            ],
            [
                'slug' => 'zhemchuzhina', 'name' => 'Чёрная жемчужина',
                'region' => 'Московская область',
                'address' => 'Дмитровский р-н, Пиратская бухта, 3',
                'image' => 'images/location/13.jpg', 'plan' => 'images/plan/5.jpg',
                'summary' => 'Пиратская шхуна у воды, палуба под навесом и сцена для шоу. Лучшая площадка для больших компаний.',
                'tags' => ['Пиратская тема', 'До 50 гостей'],
                'gallery' => ['images/location/13.jpg', 'images/location/14.jpg', 'images/location/15.jpg', 'images/location/16.jpg'],
                'free_zones' => 2, 'price_from' => 4000, 'capacity' => 50, 'sort_order' => 4,
            ],
            [
                'slug' => 'polyana', 'name' => 'Поляна сказок',
                'region' => 'Московская область',
                'address' => 'Одинцовский р-н, дер. Хлюпино, 7',
                'image' => 'images/location/21.jpg', 'plan' => 'images/plan/6.jpg',
                'summary' => 'Уютные 200 кв. м на берегу озера: места на открытом воздухе и под навесом, частная территория.',
                'tags' => ['Частная территория', 'До 25 гостей'],
                'gallery' => ['images/location/21.jpg', 'images/location/22.jpg', 'images/location/23.jpg', 'images/location/24.jpg'],
                'free_zones' => 6, 'price_from' => 2200, 'capacity' => 25, 'sort_order' => 5,
            ],
            [
                'slug' => 'zaliv', 'name' => 'Тихий залив',
                'region' => 'Ленинградская область',
                'address' => 'Всеволожский р-н, пос. Юкки, Озёрная, 4',
                'image' => 'images/location/9.jpg', 'plan' => 'images/plan/7.jpg',
                'summary' => 'Песчаный пляж, мангальная зона и большой шатёр. Парковка на 20 машин прямо у входа.',
                'tags' => ['Пляж', 'Парковка'],
                'gallery' => ['images/location/9.jpg', 'images/location/11.jpg', 'images/location/12.jpg', 'images/location/25.jpg'],
                'free_zones' => 7, 'price_from' => 3000, 'capacity' => 45, 'sort_order' => 6,
            ],
        ],

        'zones' => [
            ['slug' => 'tent',     'name' => 'Шатёр',               'price' => 5000, 'capacity' => 50, 'per_person' => 0, 'is_booked' => 0, 'sort_order' => 1],
            ['slug' => 'house',    'name' => 'Летний домик',        'price' => 3000, 'capacity' => 10, 'per_person' => 0, 'is_booked' => 1, 'sort_order' => 2],
            ['slug' => 'gazebo',   'name' => 'Беседка',             'price' => 2000, 'capacity' => 15, 'per_person' => 0, 'is_booked' => 0, 'sort_order' => 3],
            ['slug' => 'playroom', 'name' => 'Игровая комната',     'price' => 300,  'capacity' => 10, 'per_person' => 1, 'is_booked' => 0, 'sort_order' => 4],
            ['slug' => 'sport',    'name' => 'Спортивная площадка', 'price' => 200,  'capacity' => 15, 'per_person' => 1, 'is_booked' => 0, 'sort_order' => 5],
            ['slug' => 'terrace',  'name' => 'Веранда у воды',      'price' => 2800, 'capacity' => 20, 'per_person' => 0, 'is_booked' => 0, 'sort_order' => 6],
        ],

        'services' => [
            ['slug' => 'catering',  'name' => 'Питание',         'price' => 500,  'unit' => 'guest', 'hours' => 1, 'note' => 'Детское меню и напитки',        'sort_order' => 1],
            ['slug' => 'animators', 'name' => 'Аниматоры и шоу', 'price' => 5000, 'unit' => 'fixed', 'hours' => 1, 'note' => 'Программа на 2 часа',           'sort_order' => 2],
            ['slug' => 'host',      'name' => 'Ведущий',         'price' => 500,  'unit' => 'hour',  'hours' => 2, 'note' => 'Минимальный заказ — 2 часа',    'sort_order' => 3],
            ['slug' => 'photo',     'name' => 'Фотограф',        'price' => 3500, 'unit' => 'fixed', 'hours' => 1, 'note' => 'Съёмка и обработка 50 кадров',  'sort_order' => 4],
            ['slug' => 'taxi',      'name' => 'Детское такси',   'price' => 1200, 'unit' => 'fixed', 'hours' => 1, 'note' => 'Подача и сопровождение',        'sort_order' => 5],
        ],

        'promos' => [
            [
                'slug' => 'pearl', 'discount' => '−60%', 'title' => 'Аренда «Чёрной жемчужины»',
                'summary' => 'Скидка на аренду в будний день при заказе зоны от 25 человек.',
                'image' => 'images/promotions/promotion1.jpg', 'location_slug' => 'zhemchuzhina',
                'details' => [
                    'От 25 до 30 гостей — скидка 40% на аренду в будний день',
                    'От 31 до 50 гостей — скидка 55% на аренду в будний день',
                    'При полной предоплате за 14 дней — дополнительно 5%',
                ],
                'fineprint' => 'Действует в будние дни при наличии свободных зон.', 'sort_order' => 1,
            ],
            [
                'slug' => 'fairy', 'discount' => '−51%', 'title' => 'Поляна сказок с аниматорами',
                'summary' => 'Половина стоимости аренды зоны при заказе анимационной программы.',
                'image' => 'images/promotions/promotion2.jpg', 'location_slug' => 'polyana',
                'details' => [
                    'Частная охраняемая территория площадью 200 кв. м',
                    'Территория обработана от клещей и насекомых',
                    'Места на открытом воздухе и под навесом',
                    'Обустроенный тематический лагерь',
                ],
                'fineprint' => 'Обязательна предварительная запись по телефону.', 'sort_order' => 2,
            ],
            [
                'slug' => 'turnkey', 'discount' => '−25%', 'title' => 'Праздник «под ключ»',
                'summary' => 'Комплексный заказ локации и всех дополнительных услуг со скидкой.',
                'image' => 'images/promotions/promotion3.jpg', 'location_slug' => null,
                'details' => [
                    'Группы от 25 человек — скидка 25% на весь заказ',
                    'Группы от 15 человек — скидка 10% на весь заказ',
                    'Скидка суммируется с сезонными предложениями',
                ],
                'fineprint' => 'Обязательна предварительная запись по телефону.', 'sort_order' => 3,
            ],
        ],

        'price_list' => [
            ['service' => 'Аренда зоны, будни',    'note' => 'Беседка или веранда, до 6 часов', 'value' => 'от 2 000 ₽', 'sort_order' => 1],
            ['service' => 'Аренда зоны, выходные', 'note' => 'Беседка или веранда, до 6 часов', 'value' => 'от 3 500 ₽', 'sort_order' => 2],
            ['service' => 'Шатёр на 50 гостей',    'note' => 'Полный день',                     'value' => '5 000 ₽',    'sort_order' => 3],
            ['service' => 'Услуги аниматора',      'note' => '1 час, костюм и реквизит',        'value' => '2 500 ₽',    'sort_order' => 4],
            ['service' => 'Шоу мыльных пузырей',   'note' => '40 минут',                        'value' => '5 000 ₽',    'sort_order' => 5],
            ['service' => 'Детское меню',          'note' => 'За одного гостя',                 'value' => '500 ₽',      'sort_order' => 6],
        ],
    ];
}
