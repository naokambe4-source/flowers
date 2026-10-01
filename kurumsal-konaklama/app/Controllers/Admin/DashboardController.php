<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Services\Mailer;
use App\Services\SettingsService;

final class DashboardController extends AdminController
{
    public function index(): Response
    {
        $db = $this->db();
        $monthStart = date('Y-m-01');
        $stats = [
            'members' => (int) $db->value("SELECT COUNT(*) FROM users WHERE status = 'active'"),
            'institutions' => (int) $db->value('SELECT COUNT(*) FROM institutions WHERE is_active = 1'),
            'hotels' => (int) $db->value("SELECT COUNT(*) FROM hotels WHERE status = 'published'"),
            'hotels_draft' => (int) $db->value("SELECT COUNT(*) FROM hotels WHERE status = 'draft'"),
            'pending_requests' => (int) $db->value("SELECT COUNT(*) FROM accommodation_requests WHERE status = 'new'"),
            'awaiting' => (int) $db->value("SELECT COUNT(*) FROM bookings WHERE status IN ('requested','pending')"),
            'confirmed' => (int) $db->value("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed' AND check_out >= CURDATE()"),
            'month_nights' => (int) $db->value("SELECT COALESCE(SUM(nights * rooms_count), 0) FROM bookings WHERE status IN ('confirmed','completed') AND check_in >= ? AND check_in < DATE_ADD(?, INTERVAL 1 MONTH)", [$monthStart, $monthStart]),
            'month_total' => (int) $db->value("SELECT COALESCE(SUM(total_minor), 0) FROM bookings WHERE status IN ('confirmed','completed') AND check_in >= ? AND check_in < DATE_ADD(?, INTERVAL 1 MONTH)", [$monthStart, $monthStart]),
            'applications' => (int) $db->value("SELECT COUNT(*) FROM membership_requests WHERE status = 'pending'"),
        ];
        $months = $db->fetchAll(
            "SELECT DATE_FORMAT(check_in, '%Y-%m') AS ym, COUNT(*) AS c, SUM(total_minor) AS t FROM bookings
             WHERE status IN ('confirmed','completed') AND check_in >= DATE_SUB(?, INTERVAL 5 MONTH) GROUP BY ym ORDER BY ym",
            [$monthStart],
        );
        $recent = $db->fetchAll(
            "SELECT b.id, b.code, b.status, b.check_in, b.check_out, b.total_minor, b.currency, b.created_at, h.name AS hotel_name, CONCAT(u.first_name, ' ', u.last_name) AS member
             FROM bookings b JOIN hotels h ON h.id = b.hotel_id JOIN users u ON u.id = b.user_id WHERE b.status <> 'draft' ORDER BY b.created_at DESC LIMIT 8"
        );
        $providers = $db->fetchAll('SELECT id, name, code, is_enabled, status, last_check_at, last_error, quota_remaining FROM providers ORDER BY id');
        $checklist = [];
        if (SettingsService::get('contact.phone') === '' && SettingsService::get('contact.whatsapp') === '') {
            $checklist[] = ['Destek telefonu / WhatsApp girilmedi', '/yonetim/icerik'];
        }
        foreach ($db->fetchAll("SELECT slug, title FROM pages WHERE slug IN ('kvkk','gizlilik','kullanim-kosullari') AND (body IS NULL OR body = '')") as $p) {
            $checklist[] = [$p['title'] . ' metni boş', '/yonetim/icerik'];
        }
        if (!Mailer::configured()) {
            $checklist[] = ['E-posta (SMTP) yapılandırılmadı — bildirimler yalnız sistem içinde', '/yonetim/ayarlar'];
        }
        $lastCron = SettingsService::get('cron.last_run');
        if ($lastCron === '' || strtotime($lastCron) < time() - 3600) {
            $checklist[] = ['Cron görevi son 1 saatte çalışmadı (kuyruk ve otomatik işlemler bekliyor)', '/yonetim/bildirimler'];
        }
        return $this->admin('dashboard', ['title' => 'Dashboard', 'stats' => $stats, 'months' => $months, 'recent' => $recent, 'providers' => $providers, 'checklist' => $checklist, 'lastCron' => $lastCron]);
    }

    public function search(): Response
    {
        $q = mb_substr(trim((string) ($this->request->query['q'] ?? '')), 0, 100);
        $res = ['bookings' => [], 'users' => [], 'institutions' => [], 'hotels' => [], 'requests' => []];
        if (mb_strlen($q) >= 2) {
            $like = '%' . $q . '%';
            $digits = preg_replace('/\D+/', '', $q) ?? '';
            $db = $this->db();
            if (can('bookings.view')) {
                $res['bookings'] = $db->fetchAll("SELECT b.id, b.code, b.status, b.check_in, h.name AS hotel_name, b.contact_name FROM bookings b JOIN hotels h ON h.id = b.hotel_id WHERE b.status <> 'draft' AND (b.code LIKE :q OR b.contact_name LIKE :q2 OR b.contact_email LIKE :q3 OR b.hotel_confirmation_no LIKE :q4" . ($digits !== '' && strlen($digits) >= 4 ? ' OR REPLACE(REPLACE(b.contact_phone, " ", ""), "-", "") LIKE :ph' : '') . ') ORDER BY b.created_at DESC LIMIT 20', ['q' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like] + ($digits !== '' && strlen($digits) >= 4 ? ['ph' => '%' . $digits . '%'] : []));
            }
            if (can('requests.manage')) {
                $res['requests'] = $db->fetchAll('SELECT id, code, status, check_in FROM accommodation_requests WHERE code LIKE ? ORDER BY id DESC LIMIT 10', [$like]);
            }
            if (can('users.view')) {
                $res['users'] = $db->fetchAll("SELECT id, first_name, last_name, email, phone, status FROM users WHERE CONCAT(first_name, ' ', last_name) LIKE :q OR email LIKE :q2" . ($digits !== '' && strlen($digits) >= 4 ? ' OR REPLACE(REPLACE(phone, " ", ""), "-", "") LIKE :ph' : '') . ' LIMIT 20', ['q' => $like, 'q2' => $like] + ($digits !== '' && strlen($digits) >= 4 ? ['ph' => '%' . $digits . '%'] : []));
            }
            if (can('institutions.view')) {
                $res['institutions'] = $db->fetchAll('SELECT id, name, is_active FROM institutions WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? LIMIT 20', [$like, $like, $like]);
            }
            if (can('hotels.view')) {
                $res['hotels'] = $db->fetchAll('SELECT id, name, status FROM hotels WHERE name LIKE ? LIMIT 20', [$like]);
            }
        }
        return $this->admin('search', ['title' => 'Arama', 'q' => $q, 'res' => $res]);
    }
}
