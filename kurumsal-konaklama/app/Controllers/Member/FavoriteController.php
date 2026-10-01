<?php
declare(strict_types=1);

namespace App\Controllers\Member;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Response;
use App\Repositories\HotelRepository;

final class FavoriteController extends Controller
{
    public function toggle(string $hotelId): Response
    {
        $u = $this->user();
        $db = App::db();
        $id = (int) $hotelId;
        $this->notFoundUnless($db->value("SELECT 1 FROM hotels WHERE id = ? AND status = 'published'", [$id]));
        $exists = (bool) $db->value('SELECT 1 FROM favorites WHERE user_id = ? AND hotel_id = ?', [$u['id'], $id]);
        if ($exists) {
            $db->delete('favorites', ['user_id' => $u['id'], 'hotel_id' => $id]);
        } else {
            $db->query('INSERT IGNORE INTO favorites (user_id, hotel_id) VALUES (?, ?)', [$u['id'], $id]);
        }
        if ($this->request->wantsJson()) {
            return $this->json(['ok' => true, 'favorite' => !$exists]);
        }
        $this->flash('success', $exists ? 'Otel favorilerinizden çıkarıldı.' : 'Otel favorilerinize eklendi.');
        return $this->back('/favorilerim');
    }

    public function index(): Response
    {
        $u = $this->user();
        $db = App::db();
        $ids = array_map('intval', $db->column('SELECT hotel_id FROM favorites WHERE user_id = ? ORDER BY created_at DESC', [$u['id']]));
        $hotels = $ids ? (new HotelRepository($db))->publishedCandidates(['ids' => $ids]) : [];
        $amen = (new HotelRepository($db))->amenityNames($ids);
        foreach ($hotels as &$h) {
            $h['amenities'] = $amen[(int) $h['id']] ?? [];
            $h['is_favorite'] = true;
            $h['quote'] = null;
        }
        unset($h);
        return $this->view('member/favorites', ['title' => 'Favorilerim', 'hotels' => $hotels]);
    }
}
