<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Exceptions\HttpException;

abstract class Controller
{
    public function __construct(protected readonly Request $request)
    {
        static $sharedDone = false;
        if (!$sharedDone) {
            View::share('_old', Session::pull('_old', []));
            View::share('_errors', Session::pull('_errors', []));
            View::share('request', $request);
            $sharedDone = true;
        }
    }

    protected function view(string $template, array $data = [], string $layout = 'member'): Response
    {
        return Response::html(View::render($template, $data, $layout));
    }

    protected function redirect(string $path, array $query = []): Response
    {
        return Response::to($path, $query);
    }

    protected function back(string $fallback = '/panel'): Response
    {
        return App::back($this->request, $fallback);
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }

    protected function validate(array $rules, array $labels = [], ?array $data = null): array
    {
        return Validator::make($data ?? array_merge($this->request->query, $this->request->post), $rules, $labels)->validate();
    }

    protected function authorize(string $permission): void
    {
        if (!Auth::can($permission)) {
            throw new HttpException(403);
        }
    }

    protected function user(): array
    {
        $u = Auth::user();
        if ($u === null) {
            throw new HttpException(401);
        }
        return $u;
    }

    protected function notFoundUnless(mixed $value): void
    {
        if (!$value) {
            throw new HttpException(404);
        }
    }

    /** Sayfalama yardımcıları */
    protected function page(): int
    {
        return max(1, $this->request->int('sayfa', 1));
    }
}
