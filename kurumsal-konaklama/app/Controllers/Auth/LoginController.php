<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Core\Validator;
use App\Exceptions\DomainException;
use App\Services\AuthService;

final class LoginController extends Controller
{
    public function home(): Response
    {
        return $this->redirect(Auth::check() ? '/panel' : '/giris');
    }

    public function show(): Response
    {
        return $this->view('auth/login', ['title' => 'Giriş', 'split' => true], 'guest');
    }

    public function login(): Response
    {
        $data = Validator::make($this->request->post, ['email' => 'required|email|max:190', 'password' => 'required|max:200'], ['password' => 'Parola'])->validate();
        try {
            AuthService::attempt($data['email'], (string) $this->request->post['password'], $this->request->ip());
        } catch (DomainException $e) {
            Session::flashInput(['email' => $data['email']], ['password' => $e->getMessage()]);
            $this->flash('error', $e->getMessage());
            return $this->redirect('/giris');
        }
        $intended = Session::pull('intended');
        return Response::redirect(Url::safeRedirectTarget(is_string($intended) ? $intended : null, '/panel'));
    }

    public function logout(): Response
    {
        Auth::logout();
        Session::start($this->request->isSecure());
        $this->flash('success', 'Güvenli şekilde çıkış yaptınız.');
        return $this->redirect('/giris');
    }
}
