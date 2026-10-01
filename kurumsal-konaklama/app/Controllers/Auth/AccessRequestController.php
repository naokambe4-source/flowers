<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Response;
use App\Core\Validator;
use App\Exceptions\DomainException;
use App\Services\MembershipService;
use App\Services\RateLimiter;

final class AccessRequestController extends Controller
{
    public function show(): Response
    {
        $institutions = App::db()->fetchAll('SELECT id, name FROM institutions WHERE is_active = 1 ORDER BY name');
        return $this->view('auth/access_request', ['title' => 'Erişim talebi', 'split' => true, 'institutions' => $institutions], 'guest');
    }

    public function store(): Response
    {
        if ((string) ($this->request->post['website'] ?? '') !== '') {
            return $this->redirect('/giris'); // bot tuzağı
        }
        if (!RateLimiter::attempt('apply:' . $this->request->ip(), 5, 3600)) {
            throw new DomainException('Çok fazla başvuru denemesi. Lütfen daha sonra tekrar deneyin.');
        }
        $d = Validator::make($this->request->post, [
            'first_name' => 'required|min:2|max:80', 'last_name' => 'required|min:2|max:80', 'email' => 'required|email|max:190',
            'phone' => 'required|phone', 'institution_id' => 'nullable|int', 'institution_text' => 'nullable|max:200',
            'department' => 'nullable|max:150', 'note' => 'nullable|max:1000', 'kvkk' => 'accepted',
        ], ['kvkk' => 'KVKK aydınlatma metni', 'institution_text' => 'Kurum adı', 'department' => 'Departman', 'note' => 'Not'])->validate();
        if (empty($d['institution_id']) && trim((string) $d['institution_text']) === '') {
            throw new \App\Exceptions\ValidationException(['institution_id' => 'Kurumunuzu seçin veya kurum adını yazın.']);
        }
        (new MembershipService(App::db()))->apply($d, $this->request->ip());
        $this->flash('success', 'Erişim talebiniz alındı. Kurum yetkilisi ve yönetim onayı sonrası e-posta ile bilgilendirileceksiniz.');
        return $this->redirect('/giris');
    }
}
