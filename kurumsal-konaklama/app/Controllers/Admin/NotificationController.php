<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Exceptions\DomainException;
use App\Services\AuditService;
use App\Services\Mailer;
use App\Services\QueueService;
use App\Services\SettingsService;

final class NotificationController extends AdminController
{
    public function index(): Response
    {
        $db = $this->db();
        return $this->admin('notifications', [
            'title' => 'Bildirimler',
            'templates' => $db->fetchAll('SELECT * FROM notification_templates ORDER BY name'),
            'jobs' => $db->fetchAll("SELECT * FROM jobs WHERE status IN ('queued','running','failed') ORDER BY id DESC LIMIT 50"),
            'stats' => $db->fetchAll('SELECT status, COUNT(*) AS c FROM jobs GROUP BY status'),
            'mailOk' => Mailer::configured(), 'lastCron' => SettingsService::get('cron.last_run'),
        ]);
    }

    public function saveTemplate(): Response
    {
        $d = $this->validate(['key' => 'required|max:60', 'subject' => 'required|max:200', 'body' => 'required|max:5000', 'send_email' => 'bool'], ['subject' => 'Konu', 'body' => 'Metin']);
        $old = $this->db()->fetch('SELECT * FROM notification_templates WHERE `key` = ?', [$d['key']]);
        $this->notFoundUnless($old);
        $this->db()->update('notification_templates', ['subject' => $d['subject'], 'body' => $d['body'], 'send_email' => $d['send_email']], ['key' => $d['key']]);
        AuditService::log('template.update', 'notification_template', $d['key'], $old, $d);
        $this->flash('success', 'Şablon kaydedildi.');
        return $this->redirect('/yonetim/bildirimler');
    }

    public function retryJob(string $id): Response
    {
        QueueService::retry((int) $id);
        $this->flash('success', 'İş yeniden kuyruğa alındı. Bir sonraki cron çalışmasında işlenecek.');
        return $this->redirect('/yonetim/bildirimler');
    }

    public function testMail(): Response
    {
        if (!Mailer::configured()) {
            throw new DomainException('Önce Sistem Ayarları’ndan SMTP bilgilerini girin ve e-postayı etkinleştirin.');
        }
        $to = (string) $this->user()['email'];
        try {
            Mailer::send($to, 'Test e-postası', "Bu e-posta SMTP ayarlarının doğru çalıştığını doğrulamak için gönderildi.\n\n" . date('d.m.Y H:i'));
        } catch (\Throwable $e) {
            throw new DomainException('E-posta gönderilemedi: ' . mb_substr($e->getMessage(), 0, 200));
        }
        $this->flash('success', 'Test e-postası gönderildi: ' . $to);
        return $this->redirect('/yonetim/bildirimler');
    }
}
