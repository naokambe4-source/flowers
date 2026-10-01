<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Services\DemoDataService;
use App\Services\SettingsService;

/** Demo / Canlı mod: kurgusal demo otelleri yükler veya canlıya geçişte tamamen kaldırır. */
final class DemoController extends AdminController
{
    private function svc(): DemoDataService
    {
        return new DemoDataService($this->db());
    }

    public function index(): Response
    {
        $svc = $this->svc();
        return $this->admin('demo', [
            'title' => 'Demo / Canlı mod',
            'stats' => $svc->stats(),
            'available' => $svc->available(),
            'loadedAt' => SettingsService::get('demo.loaded_at'),
            'realHotels' => (int) $this->db()->value('SELECT COUNT(*) FROM hotels WHERE is_demo = 0'),
            'mode' => SettingsService::get('membership.registration_mode', 'application'),
        ]);
    }

    public function install(): Response
    {
        $n = $this->svc()->install($this->uid());
        $this->flash('success', "$n demo otel; odaları, 12 aylık fiyatları, kontenjanları ve temsili görselleriyle yüklendi. Üye ekranından arama yapabilirsiniz.");
        return $this->redirect('/yonetim/demo-mod');
    }

    public function remove(): Response
    {
        if (($this->request->post['onay'] ?? '') !== 'CANLI') {
            throw new \App\Exceptions\ValidationException(['onay' => 'Onaylamak için kutuya büyük harflerle CANLI yazın.']);
        }
        $r = $this->svc()->remove($this->uid());
        $this->flash('success', 'Canlı moda geçildi. ' . $r['hotels'] . ' demo otel ve bunlara yapılmış ' . $r['bookings'] . ' deneme rezervasyonu silindi. Gerçek kayıtlarınız korunmuştur.');
        return $this->redirect('/yonetim/demo-mod');
    }
}
