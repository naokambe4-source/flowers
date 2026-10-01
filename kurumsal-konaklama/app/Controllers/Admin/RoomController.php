<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Services\AuditService;
use App\Services\ImageService;

final class RoomController extends AdminController
{
    public function index(): Response
    {
        $hotelId = $this->request->int('otel');
        $p = [];
        $where = '1=1';
        if ($hotelId) {
            $where = 'r.hotel_id = :h';
            $p['h'] = $hotelId;
        }
        $data = $this->paginate(
            'r.*, h.name AS hotel_name, (SELECT COUNT(*) FROM rate_plans rp WHERE rp.room_id = r.id AND rp.is_active = 1) AS plans, (SELECT COUNT(*) FROM room_images ri WHERE ri.room_id = r.id) AS images',
            "FROM rooms r JOIN hotels h ON h.id = r.hotel_id WHERE $where",
            $p,
            'h.name, r.sort, r.id',
            50,
        );
        return $this->admin('rooms', $data + ['title' => 'Odalar', 'hotelId' => $hotelId, 'hotels' => options($this->db()->fetchAll('SELECT id, name FROM hotels ORDER BY name')), 'query' => $this->queryWithout()]);
    }

    private function data(): array
    {
        $d = $this->validate([
            'name' => 'required|min:2|max:150', 'description' => 'nullable|max:3000', 'size_m2' => 'nullable|int|max:2000',
            'bed_type' => 'nullable|max:100', 'view_type' => 'nullable|max:100', 'max_adults' => 'required|int|min:1|max:10',
            'max_children' => 'required|int|min:0|max:6', 'max_occupancy' => 'required|int|min:1|max:12', 'is_active' => 'bool', 'sort' => 'nullable|int',
        ], ['name' => 'Oda adı', 'max_adults' => 'En fazla yetişkin', 'max_children' => 'En fazla çocuk', 'max_occupancy' => 'Toplam kişi']);
        if ($d['max_occupancy'] < $d['max_adults']) {
            throw new ValidationException(['max_occupancy' => 'Toplam kişi, yetişkin sayısından az olamaz.']);
        }
        $d['sort'] = (int) ($d['sort'] ?? 0);
        return $d;
    }

    private function syncAmenities(int $roomId): void
    {
        $ids = array_unique(array_map('intval', $this->request->arr('amenities')));
        $this->db()->delete('room_amenity', ['room_id' => $roomId]);
        foreach ($ids as $a) {
            $this->db()->query('INSERT IGNORE INTO room_amenity (room_id, amenity_id) SELECT ?, id FROM amenities WHERE id = ?', [$roomId, $a]);
        }
    }

    public function store(string $id): Response
    {
        $hotelId = (int) $id;
        $this->notFoundUnless($this->db()->value('SELECT 1 FROM hotels WHERE id = ?', [$hotelId]));
        $d = $this->data();
        $roomId = $this->db()->transaction(function () use ($d, $hotelId) {
            $rid = $this->db()->insert('rooms', $d + ['hotel_id' => $hotelId]);
            $this->syncAmenities($rid);
            return $rid;
        });
        $this->db()->query('UPDATE hotels SET wizard_step = GREATEST(wizard_step, 5) WHERE id = ?', [$hotelId]);
        AuditService::log('room.create', 'room', $roomId, null, $d);
        $this->flash('success', 'Oda eklendi.');
        return $this->redirect('/yonetim/oteller/' . $hotelId . '/adim/4');
    }

    private function find(int $id): array
    {
        $r = $this->db()->fetch('SELECT * FROM rooms WHERE id = ?', [$id]);
        $this->notFoundUnless($r);
        return $r;
    }

    public function update(string $id): Response
    {
        $r = $this->find((int) $id);
        $d = $this->data();
        $this->db()->transaction(function () use ($d, $r): void {
            $this->db()->update('rooms', $d, ['id' => $r['id']]);
            $this->syncAmenities((int) $r['id']);
        });
        AuditService::log('room.update', 'room', (int) $r['id'], $r, $d);
        $this->flash('success', 'Oda güncellendi.');
        return $this->redirect('/yonetim/oteller/' . $r['hotel_id'] . '/adim/4');
    }

    public function uploadImages(string $id): Response
    {
        $r = $this->find((int) $id);
        if (!$this->request->bool('owner_confirm')) {
            throw new DomainException('Fotoğrafların bu tesise ait olduğunu onaylayın.');
        }
        $sort = (int) $this->db()->value('SELECT COALESCE(MAX(sort), 0) FROM room_images WHERE room_id = ?', [$r['id']]);
        $n = 0;
        foreach (array_slice($this->request->fileList('photos'), 0, 10) as $f) {
            try {
                $img = ImageService::store($f, 'rooms');
                $this->db()->insert('room_images', ['room_id' => $r['id'], 'storage_key' => $img['key'], 'original_name' => $img['original'], 'mime' => $img['mime'], 'width' => $img['width'], 'height' => $img['height'], 'bytes' => $img['bytes'], 'sort' => ++$sort]);
                $n++;
            } catch (DomainException $e) {
                $this->flash('error', $f['name'] . ': ' . $e->getMessage());
            }
        }
        if ($n) {
            $this->flash('success', "$n oda fotoğrafı yüklendi.");
        }
        return $this->redirect('/yonetim/oteller/' . $r['hotel_id'] . '/adim/4');
    }

    public function deleteImage(string $id, string $imageId): Response
    {
        $r = $this->find((int) $id);
        $img = $this->db()->fetch('SELECT * FROM room_images WHERE id = ? AND room_id = ?', [(int) $imageId, $r['id']]);
        $this->notFoundUnless($img);
        $this->db()->delete('room_images', ['id' => $img['id']]);
        ImageService::delete('rooms', $img['storage_key']);
        $this->flash('success', 'Fotoğraf silindi.');
        return $this->redirect('/yonetim/oteller/' . $r['hotel_id'] . '/adim/4');
    }
}
