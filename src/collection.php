<?php
declare(strict_types=1);

function listNomNomFavorites(PDO $db, string $identityKey): array
{
    $statement = $db->prepare("SELECT d.id AS dish_id, d.name AS dish_name, d.description AS dish_description,
        d.is_vegetarian, d.spice_level, mi.price_vnd, r.id AS restaurant_id, r.name AS restaurant_name,
        r.address, r.latitude, r.longitude, r.is_demo,
        CASE WHEN r.review_count=0 THEN 0.0 ELSE ROUND(1.0*r.rating_sum/r.review_count,1) END AS rating,
        c.name AS cuisine
        FROM nomnom_taste_actions ta JOIN dishes d ON d.id=ta.dish_id
        JOIN cuisines c ON c.id=d.cuisine_id
        JOIN menu_items mi ON mi.dish_id=d.id JOIN restaurants r ON r.id=mi.restaurant_id
        WHERE ta.session_key=? AND ta.action='like' AND mi.id=(
            SELECT best.id FROM menu_items best JOIN restaurants br ON br.id=best.restaurant_id
            WHERE best.dish_id=d.id AND best.is_available=1 AND br.status='active'
            ORDER BY best.price_vnd, best.id LIMIT 1
        ) ORDER BY ta.created_at DESC, d.id LIMIT 200");
    $statement->execute([$identityKey]);
    return array_map('mapDiscoveryRow', $statement->fetchAll());
}

function removeNomNomFavorite(PDO $db, string $identityKey, int $dishId): void
{
    $statement = $db->prepare("DELETE FROM nomnom_taste_actions WHERE session_key=? AND dish_id=? AND action='like'");
    $statement->execute([$identityKey, $dishId]);
}

function cancelNomNomBooking(PDO $db, string $identityKey, int $bookingId): array
{
    $now = (new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d H:i');
    $statement = $db->prepare("UPDATE nomnom_bookings SET status='cancelled'
        WHERE id=? AND session_key=? AND status IN ('pending','confirmed') AND booking_date>?");
    $statement->execute([$bookingId, $identityKey, $now]);
    if ($statement->rowCount() !== 1) {
        throw new InvalidArgumentException('Không thể hủy lịch này. Lịch không tồn tại, đã hủy hoặc đã qua giờ hẹn.');
    }
    return ['id' => $bookingId, 'status' => 'cancelled'];
}
