<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Auth;
use App\Core\Response;
use App\Core\Validator;
use App\Exceptions\DomainException;
use App\Services\MembershipService;
use App\Services\RateLimiter;
use App\Services\SettingsService;

/** Açık kayıt modu: yönetim “Açık kayıt” seçtiğinde kullanıcı kendi hesabını oluşturur. */
final class RegisterController extends Controller
{
    private function ensureOpen(): ?Response
    {
        if (MembershipService::registrationMode() !== 'open') {
            $this->flash('info', MembershipService::registrationMode() === 'application'
                ? 'Üyelik, erişim talebi ve yönetici onayıyla açılmaktadır.'
                : 'Üyelik kaydı şu anda kapalıdır. Hesap için kurum yetkilinizle iletişime geçin.');
            return $this->redirect(MembershipService::registrationMode() === 'application' ? '/erisim-talebi' : '/giris');
        }
        return null;
    }

    public function show(): Response
    {
        if ($r = $this->ensureOpen()) {
            return $r;
        }
        return $this->view('auth/register', [
            'title' => 'Hesap oluştur', 'split' => true,
            'institutions' => App::db()->fetchAll('SELECT id, name FROM institutions WHERE is_active = 1 ORDER BY name'),
            'domains' => MembershipService::allowedDomains(),
            'requireInstitution' => SettingsService::bool('membership.require_institution'),
        ], 'guest');
    }

    public function store(): Response
    {
        if ($r = $this->ensureOpen()) {
            return $r;
        }
        if ((string) ($this->request->post['website'] ?? '') !== '') {
            return $this->redirect('/giris'); // bot tuzağı
        }
        if (!RateLimiter::attempt('register:' . $this->request->ip(), 5, 3600)) {
            throw new DomainException('Çok fazla kayıt denemesi. Lütfen daha sonra tekrar deneyin.');
        }
        $d = Validator::make($this->request->post, [
            'first_name' => 'required|min:2|max:80', 'last_name' => 'required|min:2|max:80', 'email' => 'required|email|max:190',
            'phone' => 'required|phone', 'institution_id' => 'nullable|int', 'department' => 'nullable|max:150',
            'password' => 'required|password|max:200', 'password_confirmation' => 'required|same:password', 'kvkk' => 'accepted',
        ], ['kvkk' => 'KVKK aydınlatma metni', 'department' => 'Birim', 'password_confirmation' => 'Parola tekrarı'])->validate();
        $user = (new MembershipService(App::db()))->register($d, $this->request->ip());
        Auth::login($user);
        $this->flash('success', 'Hoş geldiniz ' . $user['first_name'] . '! Hesabınız oluşturuldu.');
        return $this->redirect('/panel');
    }
}
