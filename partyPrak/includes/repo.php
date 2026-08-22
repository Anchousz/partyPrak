<?php
/**
 * repo.php — все запросы к базе. Страницы обращаются только сюда,
 * SQL в шаблонах не встречается.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/** Приводит строку локации из БД к виду, удобному шаблонам и JS. */
function map_location(array $row): array
{
    return [
        'id'        => (int) $row['id'],
        'slug'      => $row['slug'],
        'name'      => $row['name'],
        'region'    => $row['region'],
        'address'   => $row['address'],
        'image'     => $row['image'],
        'plan'      => $row['plan'],
        'summary'   => $row['summary'],
        'tags'      => json_decode($row['tags'], true) ?: [],
        'gallery'   => json_decode($row['gallery'], true) ?: [],
        'freeZones' => (int) $row['free_zones'],
        'priceFrom' => (int) $row['price_from'],
        'capacity'  => (int) $row['capacity'],
    ];
}

/**
 * Локации. Активные и отсортированные.
 * @param string $region пустая строка — без фильтра по региону
 */
function get_locations(string $region = '', int $limit = 0): array
{
    $sql = 'SELECT * FROM locations WHERE is_active = 1';
    $params = [];
    if ($region !== '') {
        $sql .= ' AND region = :region';
        $params['region'] = $region;
    }
    $sql .= ' ORDER BY sort_order, id';
    if ($limit > 0) {
        // LIMIT нельзя передать плейсхолдером при отключённой эмуляции,
        // поэтому приводим к int сами — значение приходит из кода, не от юзера.
        $sql .= ' LIMIT ' . (int) $limit;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return array_map('map_location', $stmt->fetchAll());
}

/** Одна локация по слагу либо null. */
function get_location(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM locations WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ? map_location($row) : null;
}

/** Список регионов, где есть активные площадки. */
function get_regions(): array
{
    return db()
        ->query('SELECT DISTINCT region FROM locations WHERE is_active = 1 ORDER BY region')
        ->fetchAll(PDO::FETCH_COLUMN);
}

/** Зоны площадки. */
function get_zones(): array
{
    $rows = db()->query('SELECT * FROM zones ORDER BY sort_order, id')->fetchAll();
    return array_map(static fn(array $r): array => [
        'id'        => $r['slug'],
        'name'      => $r['name'],
        'price'     => (int) $r['price'],
        'capacity'  => (int) $r['capacity'],
        'perPerson' => (bool) $r['per_person'],
        'booked'    => (bool) $r['is_booked'],
    ], $rows);
}

/** Дополнительные услуги. */
function get_services(): array
{
    $rows = db()->query('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();
    return array_map(static fn(array $r): array => [
        'id'    => $r['slug'],
        'name'  => $r['name'],
        'price' => (int) $r['price'],
        'unit'  => $r['unit'],
        'hours' => (int) $r['hours'],
        'note'  => $r['note'],
    ], $rows);
}

/** Акции вместе со слагом связанной локации. */
function get_promos(): array
{
    $rows = db()->query(
        'SELECT p.*, l.slug AS location_slug
           FROM promos p
           LEFT JOIN locations l ON l.id = p.location_id
          WHERE p.is_active = 1
          ORDER BY p.sort_order, p.id'
    )->fetchAll();

    return array_map(static fn(array $r): array => [
        'id'         => $r['slug'],
        'discount'   => $r['discount'],
        'title'      => $r['title'],
        'summary'    => $r['summary'],
        'image'      => $r['image'],
        'locationId' => $r['location_slug'],
        'details'    => json_decode($r['details'], true) ?: [],
        'fineprint'  => $r['fineprint'],
    ], $rows);
}

/** Строки прайс-листа. */
function get_price_list(): array
{
    return db()->query('SELECT * FROM price_list ORDER BY sort_order, id')->fetchAll();
}

/**
 * Сохраняет заявку.
 * @param array $data уже провалидированные значения
 * @return int id созданной записи
 */
function create_booking(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO bookings
            (status, source, location_id, event_date, adults, children, guests,
             zones, services, total, customer, phone, email, comment)
         VALUES
            (:status, :source, :location_id, :event_date, :adults, :children, :guests,
             :zones, :services, :total, :customer, :phone, :email, :comment)'
    );

    $stmt->execute([
        'status'      => 'new',
        'source'      => $data['source'] ?? 'form',
        'location_id' => $data['location_id'] ?? null,
        'event_date'  => $data['event_date'] ?: null,
        'adults'      => $data['adults'] ?? 0,
        'children'    => $data['children'] ?? 0,
        'guests'      => $data['guests'] ?? 0,
        'zones'       => json_encode($data['zones'] ?? [], JSON_UNESCAPED_UNICODE),
        'services'    => json_encode($data['services'] ?? [], JSON_UNESCAPED_UNICODE),
        'total'       => $data['total'] ?? 0,
        'customer'    => $data['customer'] ?? '',
        'phone'       => $data['phone'] ?? '',
        'email'       => $data['email'] ?? '',
        'comment'     => $data['comment'] ?? '',
    ]);

    return (int) db()->lastInsertId();
}

/** id локации по слагу (или null). Нужен при сохранении заявки. */
function location_id_by_slug(?string $slug): ?int
{
    if (!$slug) {
        return null;
    }
    $stmt = db()->prepare('SELECT id FROM locations WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int) $id;
}

/* ==========================================================================
   Функции ниже используются только админ-панелью.
   ========================================================================== */

/** Заявки с названием локации, новые сверху. */
function admin_get_bookings(string $status = ''): array
{
    $sql = 'SELECT b.*, l.name AS location_name
              FROM bookings b
              LEFT JOIN locations l ON l.id = b.location_id';
    $params = [];
    if ($status !== '') {
        $sql .= ' WHERE b.status = :status';
        $params['status'] = $status;
    }
    $sql .= ' ORDER BY b.created_at DESC, b.id DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Меняет статус заявки. Возвращает false, если статус недопустим. */
function admin_set_booking_status(int $id, string $status): bool
{
    $allowed = ['new', 'confirmed', 'done', 'cancelled'];
    if (!in_array($status, $allowed, true)) {
        return false;
    }
    $stmt = db()->prepare('UPDATE bookings SET status = ? WHERE id = ?');
    return $stmt->execute([$status, $id]);
}

/** Удаляет заявку. */
function admin_delete_booking(int $id): bool
{
    return db()->prepare('DELETE FROM bookings WHERE id = ?')->execute([$id]);
}

/** Сводка для дашборда. */
function admin_stats(): array
{
    $db = db();
    return [
        'bookings_total' => (int) $db->query('SELECT COUNT(*) FROM bookings')->fetchColumn(),
        'bookings_new'   => (int) $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'new'")->fetchColumn(),
        'revenue'        => (int) $db->query("SELECT COALESCE(SUM(total),0) FROM bookings WHERE status IN ('confirmed','done')")->fetchColumn(),
        'locations'      => (int) $db->query('SELECT COUNT(*) FROM locations WHERE is_active = 1')->fetchColumn(),
    ];
}

/** Все локации, включая скрытые — для таблицы в админке. */
function admin_get_locations(): array
{
    return db()->query('SELECT * FROM locations ORDER BY sort_order, id')->fetchAll();
}

/** Обновляет поля локации, доступные для правки из админки. */
function admin_update_location(int $id, array $data): bool
{
    $stmt = db()->prepare(
        'UPDATE locations
            SET name = :name, region = :region, address = :address,
                summary = :summary, free_zones = :free_zones,
                price_from = :price_from, capacity = :capacity, is_active = :is_active
          WHERE id = :id'
    );
    return $stmt->execute([
        'name'       => $data['name'],
        'region'     => $data['region'],
        'address'    => $data['address'],
        'summary'    => $data['summary'],
        'free_zones' => $data['free_zones'],
        'price_from' => $data['price_from'],
        'capacity'   => $data['capacity'],
        'is_active'  => $data['is_active'],
        'id'         => $id,
    ]);
}

/** Одна локация по id — для формы редактирования. */
function admin_get_location(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM locations WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Занятость зоны переключается прямо из админки. */
function admin_toggle_zone(int $id): bool
{
    return db()->prepare('UPDATE zones SET is_booked = 1 - is_booked WHERE id = ?')->execute([$id]);
}

/** Зоны для админки (в исходном виде). */
function admin_get_zones(): array
{
    return db()->query('SELECT * FROM zones ORDER BY sort_order, id')->fetchAll();
}
