<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Auth;
use App\Core\Response;
use App\Core\Validator;
use App\Exceptions\DomainException;
use App\Services\NotificationService;
use App\Services\RateLimiter;

final class PageController extends Controller
{
    private function showPage(string $slug): Response
    {
        $page = App::db()->fetch('SELECT * FROM pages WHERE slug = ? AND is_public = 1', [$slug]);
        $this->notFoundUnless($page);
        return $this->view('site/page', ['title' => $page['title'], 'page' => $page], Auth::check() ? 'member' : 'guest');
    }

    public function kvkk(): Response { return $this->showPage('kvkk'); }
    public function privacy(): Response { return $this->showPage('gizlilik'); }
    public function terms(): Response { return $this->showPage('kullanim-kosullari'); }
    public function cookies(): Response { return $this->showPage('cerez-politikasi'); }

    public function contact(): Response
    {
        $page = App::db()->fetch("SELECT * FROM pages WHERE slug = 'iletisim'");
        return $this->view('site/contact', ['title' => 'İletişim', 'page' => $page], Auth::check() ? 'member' : 'guest');
    }

    public function sendContact(): Response
    {
        if ((string) ($this->request->post['website'] ?? '') !== '') {
            return $this->redirect('/iletisim');
        }
        if (!RateLimiter::attempt('contact:' . $this->request->ip(), 5, 3600)) {
            throw new DomainException('Çok fazla mesaj gönderildi. Lütfen daha sonra tekrar deneyin.');
        }
        $d = Validator::make($this->request->post, [
            'name' => 'required|min:3|max:160', 'email' => 'required|email|max:190', 'phone' => 'nullable|phone',
            'subject' => 'required|min:3|max:200', 'message' => 'required|min:10|max:3000', 'kvkk' => 'accepted',
        ], ['name' => 'Ad soyad', 'kvkk' => 'KVKK aydınlatma metni'])->validate();
        $id = App::db()->insert('support_requests', [
            'user_id' => Auth::id(), 'name' => $d['name'], 'email' => $d['email'], 'phone' => $d['phone'],
            'subject' => $d['subject'], 'message' => $d['message'], 'ip' => $this->request->ip(),
        ]);
        NotificationService::notifyStaff('support.manage', 'Yeni iletişim mesajı', $d['subject'], '/yonetim/destek');
        $this->flash('success', 'Mesajınız alındı. En kısa sürede dönüş yapılacaktır.');
        return $this->redirect('/iletisim');
    }
}
