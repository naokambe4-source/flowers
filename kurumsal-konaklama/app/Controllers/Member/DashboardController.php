<?php
declare(strict_types=1);

namespace App\Controllers\Member;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Response;
use App\Repositories\HotelRepository;

final class DashboardController extends Controller
{
    public function index(): Response
    {
        $u = $this->user();
        $db = App::db();
        $sections = $db->fetchAll('SELECT `key`, title FROM home_sections WHERE is_enabled = 1 ORDER BY sort');
        $keys = array_column($sections, 'key');
        $data = ['title' => 'Ana Sayfa', 'sections' => $sections, 'fullBleed' => false, 'user' => $u];
        $data['regions'] = $db->fetchAll('SELECT * FROM regions WHERE is_active = 1 ORDER BY sort, name');
        if (in_array('featured', $keys, true) && can('member.book')) {
            $data['featured'] = (new HotelRepository($db))->featured(8);
        }
        if (in_array('campaigns', $keys, true) && can('member.book')) {
            $data['campaigns'] = $db->fetchAll(
                "SELECT c.*, h.name AS hotel_name, h.slug AS hotel_slug, r.name AS region_name FROM campaigns c
                 LEFT JOIN hotels h ON h.id = c.hotel_id LEFT JOIN regions r ON r.id = c.region_id
                 LEFT JOIN rate_rules rr ON rr.id = c.rate_rule_id
                 WHERE c.is_active = 1 AND c.show_on_home = 1 AND c.valid_from <= NOW() AND c.valid_to >= NOW()
                   AND (c.hotel_id IS NULL OR h.status = 'published')
                   AND (c.rate_rule_id IS NULL OR rr.is_active = 1)
                 ORDER BY c.sort, c.valid_to LIMIT 6"
            );
        }
        if (in_array('summary', $keys, true)) {
            $data['pendingOffers'] = $db->fetchAll(
                "SELECT ar.code, ar.check_in, ar.check_out, o.total_minor, o.valid_until, h.name AS hotel_name
                 FROM accommodation_requests ar JOIN offers o ON o.request_id = ar.id AND o.status = 'sent' JOIN hotels h ON h.id = o.hotel_id
                 WHERE ar.user_id = ? AND ar.status = 'offered' ORDER BY o.valid_until LIMIT 3",
                [$u['id']],
            );
            $data['openRequests'] = (int) $db->value("SELECT COUNT(*) FROM accommodation_requests WHERE user_id = ? AND status = 'new'", [$u['id']]);
            $data['upcoming'] = $db->fetchAll(
                "SELECT b.code, b.status, b.check_in, b.check_out, b.total_minor, b.currency, h.name AS hotel_name
                 FROM bookings b JOIN hotels h ON h.id = b.hotel_id
                 WHERE b.user_id = ? AND b.status IN ('requested','pending','confirmed') AND b.check_out >= CURDATE()
                 ORDER BY b.check_in LIMIT 3",
                [$u['id']],
            );
        }
        $data['favCount'] = (int) $db->value('SELECT COUNT(*) FROM favorites f JOIN hotels h ON h.id = f.hotel_id WHERE f.user_id = ? AND h.status = ?', [$u['id'], 'published']);
        return $this->view('member/dashboard', $data);
    }
}
