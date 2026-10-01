<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Response;
use App\Core\Validator;
use App\Services\AuthService;
use App\Services\RateLimiter;

final class PasswordController extends Controller
{
    public function forgot(): Response
    {
        return $this->view('auth/forgot', ['title' => 'Şifremi unuttum', 'split' => true], 'guest');
    }

    public function sendLink(): Response
    {
        $data = Validator::make($this->request->post, ['email' => 'required|email|max:190'])->validate();
        if (RateLimiter::attempt('forgot:' . $this->request->ip(), 5, 900)) {
            $user = App::db()->fetch("SELECT * FROM users WHERE email = ? AND status = 'active'", [$data['email']]);
            if ($user) {
                AuthService::sendPasswordLink($user, 'reset');
            }
        }
        // Hesap varlığı ifşa edilmez
        $this->flash('success', 'Bu e-posta adresi sistemde kayıtlı ve aktifse parola sıfırlama bağlantısı gönderildi. Gelen kutunuzu ve istenmeyen klasörünü kontrol edin.');
        return $this->redirect('/giris');
    }

    public function showReset(string $token): Response
    {
        $t = AuthService::findToken($token);
        return $this->view('auth/reset', ['title' => 'Parola oluştur', 'split' => true, 'token' => $token, 'valid' => $t !== null && $t['status'] === 'active', 'purpose' => $t['purpose'] ?? 'reset'], 'guest');
    }

    public function reset(string $token): Response
    {
        $t = AuthService::findToken($token);
        if (!$t || $t['status'] !== 'active') {
            $this->flash('error', 'Bağlantı geçersiz veya süresi dolmuş. Yeni bağlantı isteyin.');
            return $this->redirect('/sifremi-unuttum');
        }
        Validator::make($this->request->post, ['password' => 'required|password|max:200', 'password_confirmation' => 'required|same:password'], ['password_confirmation' => 'Parola tekrarı'])->validate();
        App::db()->transaction(function () use ($t): void {
            AuthService::setPassword((int) $t['user_id'], (string) $this->request->post['password']);
            App::db()->update('password_tokens', ['used_at' => date('Y-m-d H:i:s')], ['id' => $t['id']]);
        });
        $this->flash('success', 'Parolanız oluşturuldu. Şimdi giriş yapabilirsiniz.');
        return $this->redirect('/giris');
    }
}
