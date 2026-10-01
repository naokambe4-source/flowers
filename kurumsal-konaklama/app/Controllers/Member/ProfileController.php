<?php
declare(strict_types=1);

namespace App\Controllers\Member;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Auth;
use App\Core\Response;
use App\Core\Session;
use App\Exceptions\ValidationException;
use App\Services\AuthService;
use App\Services\RateLimiter;

final class ProfileController extends Controller
{
    public function show(): Response
    {
        return $this->view('member/profile', ['title' => 'Profilim', 'u' => $this->user()]);
    }

    public function update(): Response
    {
        $u = $this->user();
        $d = $this->validate(['first_name' => 'required|min:2|max:80', 'last_name' => 'required|min:2|max:80', 'phone' => 'nullable|phone', 'department' => 'nullable|max:150'], ['department' => 'Departman']);
        App::db()->update('users', ['first_name' => $d['first_name'], 'last_name' => $d['last_name'], 'phone' => $d['phone'], 'department' => $d['department']], ['id' => $u['id']]);
        $this->flash('success', 'Profil bilgileriniz güncellendi.');
        return $this->redirect('/profilim');
    }

    public function password(): Response
    {
        return $this->view('member/password', ['title' => 'Şifre değiştir']);
    }

    public function updatePassword(): Response
    {
        $u = $this->user();
        if (!RateLimiter::attempt('pwchange:' . $u['id'], 10, 900)) {
            throw new \App\Exceptions\DomainException('Çok fazla deneme yapıldı. Lütfen 15 dakika sonra tekrar deneyin.');
        }
        $this->validate(['current_password' => 'required', 'password' => 'required|password|max:200', 'password_confirmation' => 'required|same:password'], ['current_password' => 'Mevcut parola', 'password_confirmation' => 'Parola tekrarı']);
        $hash = (string) App::db()->value('SELECT password_hash FROM users WHERE id = ?', [$u['id']]);
        if (!password_verify((string) $this->request->post['current_password'], $hash)) {
            throw new ValidationException(['current_password' => 'Mevcut parolanız hatalı.']);
        }
        AuthService::setPassword((int) $u['id'], (string) $this->request->post['password']);
        $fresh = App::db()->fetch('SELECT * FROM users WHERE id = ?', [$u['id']]);
        Auth::login($fresh);
        Session::flash('success', 'Parolanız değiştirildi.');
        return $this->redirect('/profilim');
    }
}
