<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Services\AuditService;
use App\Services\ImageService;
use App\Services\SettingsService;

/** İçerik yönetimi: marka, giriş sayfası, ana sayfa bölümleri, iletişim, sosyal medya, yasal metinler. */
final class ContentController extends AdminController
{
    public const TEXT_KEYS = [
        'site.name' => ['Site adı', 'text'], 'site.tagline' => ['Alt başlık / slogan', 'text'],
        'login.title' => ['Giriş sayfası başlığı', 'text'], 'login.text' => ['Giriş sayfası metni', 'textarea'],
        'home.hero_title' => ['Üye ana sayfa başlığı', 'text'], 'home.hero_text' => ['Üye ana sayfa metni', 'textarea'],
        'contact.phone' => ['Destek telefonu', 'tel'], 'contact.whatsapp' => ['WhatsApp numarası', 'tel'], 'contact.email' => ['Destek e-postası', 'email'],
        'contact.address' => ['Adres', 'text'], 'contact.hours' => ['Çalışma saatleri', 'text'],
        'social.instagram' => ['Instagram', 'url'], 'social.facebook' => ['Facebook', 'url'], 'social.x' => ['X', 'url'], 'social.linkedin' => ['LinkedIn', 'url'], 'social.youtube' => ['YouTube', 'url'],
        'booking.terms_text' => ['Rezervasyon koşulları metni', 'textarea'], 'booking.cancel_text' => ['Genel iptal metni', 'textarea'], 'cookie.text' => ['Çerez bilgilendirme metni', 'textarea'],
    ];
    public const IMAGE_KEYS = ['site.logo' => 'Logo', 'login.image' => 'Giriş sayfası görseli', 'home.hero_image' => 'Ana sayfa karşılama görseli', 'support.image' => 'Destek bölümü görseli'];

    public function index(): Response
    {
        $values = [];
        foreach (array_keys(self::TEXT_KEYS + self::IMAGE_KEYS) as $k) {
            $values[$k] = SettingsService::get($k);
        }
        return $this->admin('content', [
            'title' => 'İçerik Yönetimi', 'values' => $values,
            'pages' => $this->db()->fetchAll('SELECT * FROM pages ORDER BY id'),
            'sections' => $this->db()->fetchAll('SELECT * FROM home_sections ORDER BY sort'),
        ]);
    }

    public function saveSettings(): Response
    {
        $errors = [];
        $old = SettingsService::snapshot(array_keys(self::TEXT_KEYS + self::IMAGE_KEYS));
        foreach (self::TEXT_KEYS as $k => [$label, $type]) {
            $field = str_replace('.', '__', $k);
            if (!array_key_exists($field, $this->request->post)) {
                continue;
            }
            $v = trim((string) $this->request->post[$field]);
            if ($v !== '' && $type === 'email' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "$label geçerli bir e-posta olmalıdır.";
                continue;
            }
            if ($v !== '' && $type === 'url' && !preg_match('#^https://#', $v)) {
                $errors[$field] = "$label https:// ile başlamalıdır.";
                continue;
            }
            if ($v !== '' && $type === 'tel' && strlen(preg_replace('/\D+/', '', $v) ?? '') < 10) {
                $errors[$field] = "$label geçerli bir telefon olmalıdır.";
                continue;
            }
            if ($k === 'site.name' && $v === '') {
                $errors[$field] = 'Site adı boş olamaz.';
                continue;
            }
            SettingsService::set($k, mb_substr($v, 0, 5000), false, $this->uid());
        }
        foreach (self::IMAGE_KEYS as $k => $label) {
            $field = str_replace('.', '__', $k);
            if ($this->request->bool('remove_' . $field)) {
                SettingsService::set($k, '', false, $this->uid());
            }
            if ($f = $this->request->file($field)) {
                try {
                    SettingsService::set($k, ImageService::store($f, 'site')['key'], false, $this->uid());
                } catch (\App\Exceptions\DomainException $e) {
                    $errors[$field] = $label . ': ' . $e->getMessage();
                }
            }
        }
        AuditService::log('content.settings', 'settings', null, $old, SettingsService::snapshot(array_keys(self::TEXT_KEYS + self::IMAGE_KEYS)));
        if ($errors) {
            throw new \App\Exceptions\ValidationException($errors, 'Bazı alanlar kaydedilemedi.');
        }
        $this->flash('success', 'İçerik ayarları kaydedildi.');
        return $this->redirect('/yonetim/icerik');
    }

    public function savePage(string $id): Response
    {
        $page = $this->db()->fetch('SELECT * FROM pages WHERE id = ?', [(int) $id]);
        $this->notFoundUnless($page);
        $d = $this->validate(['title' => 'required|max:200', 'body' => 'nullable|max:100000']);
        $this->db()->update('pages', ['title' => $d['title'], 'body' => $d['body'], 'updated_by' => $this->uid()], ['id' => $page['id']]);
        AuditService::log('page.update', 'page', $page['slug'], ['title' => $page['title'], 'length' => mb_strlen((string) $page['body'])], ['title' => $d['title'], 'length' => mb_strlen((string) $d['body'])]);
        $this->flash('success', $d['title'] . ' kaydedildi.');
        return $this->redirect('/yonetim/icerik#sayfalar');
    }

    public function saveSections(): Response
    {
        $order = $this->request->arr('order');
        $enabled = array_flip($this->request->arr('enabled'));
        foreach (array_values($order) as $i => $key) {
            $this->db()->update('home_sections', ['sort' => $i + 1, 'is_enabled' => isset($enabled[$key]) ? 1 : 0], ['key' => (string) $key]);
        }
        AuditService::log('home.sections', 'home_sections', null, null, ['order' => $order, 'enabled' => array_keys($enabled)]);
        $this->flash('success', 'Ana sayfa bölümleri güncellendi.');
        return $this->redirect('/yonetim/icerik#bolumler');
    }
}
