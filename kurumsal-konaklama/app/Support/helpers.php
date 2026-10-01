<?php
declare(strict_types=1);

use \App\Core\App;
use \App\Core\Auth;
use \App\Core\Csrf;
use \App\Core\Money;
use \App\Core\Session;
use \App\Core\Url;
use \App\Core\View;

/** Kurulum yoluna göre mutlak adres: base_url('/oteller') → https://alanadi.com/kurumsal/konaklama/oteller */
function base_url(string $path = '/', array $query = []): string
{
    return Url::absolute($path, $query);
}

/** Site içi yol: url('/oteller') → /kurumsal/konaklama/oteller */
function url(string $path = '/', array $query = []): string
{
    return Url::to($path, $query);
}

function asset_url(string $path): string
{
    return Url::asset($path);
}

/** İsimli rota: route_url('hotel.show', ['slug' => 'x']) */
function route_url(string $name, array $params = [], array $query = []): string
{
    return App::router()->url($name, $params, $query);
}

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function csrf_token(): string
{
    return Csrf::token();
}

function old(string $key, mixed $default = ''): mixed
{
    $old = View::shared('_old', []);
    return is_array($old) && array_key_exists($key, $old) ? $old[$key] : $default;
}

function field_error(string $key): string
{
    $errors = View::shared('_errors', []);
    if (!is_array($errors) || empty($errors[$key])) {
        return '';
    }
    return '<p class="field-error" id="err-' . e($key) . '" role="alert"><span aria-hidden="true">⚠</span> ' . e($errors[$key]) . '</p>';
}

function has_error(string $key): bool
{
    $errors = View::shared('_errors', []);
    return is_array($errors) && !empty($errors[$key]);
}

function aria_error(string $key): string
{
    return has_error($key) ? ' aria-invalid="true" aria-describedby="err-' . e($key) . '"' : '';
}

function money(?int $minor, string $currency = 'TRY'): string
{
    return Money::format($minor, $currency);
}

function percent(int $bp): string
{
    return Money::percent($bp);
}

function can(string $permission): bool
{
    return Auth::can($permission);
}

function auth_user(): ?array
{
    return Auth::user();
}

const TR_MONTHS = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
const TR_MONTHS_SHORT = ['', 'Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
const TR_DAYS = ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
const TR_DAYS_SHORT = ['', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];

/** 2026-10-12 → "12 Ekim 2026" */
function tr_date(?string $date, bool $withDay = false): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return '—';
    }
    $s = date('j', $ts) . ' ' . TR_MONTHS[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    return $withDay ? $s . ', ' . TR_DAYS[(int) date('N', $ts)] : $s;
}

function tr_date_short(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts === false ? '—' : date('j', $ts) . ' ' . TR_MONTHS_SHORT[(int) date('n', $ts)];
}

function tr_datetime(?string $dt): string
{
    if (!$dt) {
        return '—';
    }
    $ts = strtotime($dt);
    return $ts === false ? '—' : date('d.m.Y H:i', $ts);
}

function flashes(): array
{
    return Session::takeFlashes();
}

function guest_summary(int $adults, int $children, int $rooms): string
{
    $parts = [$adults . ' yetişkin'];
    if ($children > 0) {
        $parts[] = $children . ' çocuk';
    }
    $parts[] = $rooms . ' oda';
    return implode(' · ', $parts);
}

function str_slug(string $text): string
{
    $map = ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'İ' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', 'Ç' => 'c', 'Ğ' => 'g', 'Ö' => 'o', 'Ş' => 's', 'Ü' => 'u', 'â' => 'a', 'î' => 'i', 'û' => 'u'];
    $text = strtr($text, $map);
    $text = mb_strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

/** Yönetici tarafından girilen düz metni güvenli paragraflara çevirir (HTML kabul edilmez). */
function nl2p(?string $text): string
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }
    $out = '';
    foreach (preg_split('/\R{2,}/', $text) ?: [] as $para) {
        $lines = array_map('e', preg_split('/\R/', trim($para)) ?: []);
        $isList = $lines && count(array_filter($lines, static fn ($l) => str_starts_with($l, '- '))) === count($lines);
        if ($isList) {
            $out .= '<ul>' . implode('', array_map(static fn ($l) => '<li>' . substr($l, 2) . '</li>', $lines)) . '</ul>';
        } elseif (str_starts_with($lines[0] ?? '', '## ')) {
            $out .= '<h3>' . substr(array_shift($lines), 3) . '</h3>' . ($lines ? '<p>' . implode('<br>', $lines) . '</p>' : '');
        } else {
            $out .= '<p>' . implode('<br>', $lines) . '</p>';
        }
    }
    return $out;
}

function status_label(string $status): string
{
    return [
        'draft' => 'Taslak', 'requested' => 'Talep Alındı', 'pending' => 'Onay Bekliyor', 'confirmed' => 'Onaylandı',
        'cancelled' => 'İptal Edildi', 'completed' => 'Tamamlandı',
        'new' => 'Yeni Talep', 'offered' => 'Teklif Gönderildi', 'accepted' => 'Teklif Kabul Edildi', 'declined' => 'Reddedildi',
        'expired' => 'Süresi Doldu', 'converted' => 'Rezervasyona Dönüştü',
        'sent' => 'Geçerli Teklif', 'superseded' => 'Eski Sürüm', 'withdrawn' => 'Geri Çekildi',
        'pending_user' => 'Onay Bekliyor', 'active' => 'Aktif', 'passive' => 'Pasif', 'suspended' => 'Askıda', 'rejected' => 'Reddedildi',
        'approved' => 'Onaylandı', 'open' => 'Açık', 'answered' => 'Yanıtlandı', 'closed' => 'Kapandı',
        'published' => 'Yayında', 'unpublished' => 'Yayından Kaldırıldı',
    ][$status] ?? $status;
}

function status_tone(string $status): string
{
    return match ($status) {
        'confirmed', 'completed', 'active', 'approved', 'accepted', 'published', 'answered', 'sent', 'converted' => 'success',
        'requested', 'pending', 'new', 'offered', 'open' => 'warning',
        'cancelled', 'rejected', 'declined', 'suspended', 'expired', 'withdrawn' => 'danger',
        default => 'neutral',
    };
}

function icon(string $name, string $class = 'icon'): string
{
    return \App\Support\Icons::svg($name, $class);
}

function pagination_links(array $page, array $query = []): string
{
    return View::partial('partials/pagination', ['page' => $page, 'query' => $query]);
}

function setting(string $key, string $default = ''): string
{
    try {
        return \App\Services\SettingsService::get($key, $default);
    } catch (\Throwable) {
        return $default;
    }
}

/** Yönetimden yüklenen site görseli; yoksa temsili illüstrasyon. */
function site_image(string $settingKey, string $illustration): array
{
    $key = setting($settingKey);
    if ($key !== '') {
        return ['url' => url('/medya/site/' . str_replace('.', '_', $settingKey)), 'representative' => false];
    }
    return ['url' => asset_url('img/illustrations/' . $illustration . '.svg'), 'representative' => true];
}

function region_image(array $region): array
{
    if (!empty($region['image_path'])) {
        return ['url' => url('/medya/bolge/' . (int) $region['id']), 'representative' => false];
    }
    return ['url' => asset_url('img/illustrations/' . ($region['illustration'] ?: 'coast') . '.svg'), 'representative' => true];
}

function hotel_image_url(?int $imageId, string $size = 'medium'): ?string
{
    return $imageId ? url('/medya/otel/' . $imageId . '/' . $size) : null;
}

function wa_link(string $number): string
{
    $digits = preg_replace('/\D+/', '', $number) ?? '';
    if (str_starts_with($digits, '0')) {
        $digits = '90' . substr($digits, 1);
    }
    return 'https://wa.me/' . $digits;
}

function tel_link(string $number): string
{
    return 'tel:' . preg_replace('/[^\d+]/', '', $number);
}

function initials(string $first, string $last): string
{
    return mb_strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));
}

function is_active_path(string $prefix): bool
{
    $req = \App\Core\App::request();
    if (!$req) {
        return false;
    }
    return $prefix === '/' ? $req->path === '/' : ($req->path === $prefix || str_starts_with($req->path, rtrim($prefix, '/') . '/'));
}

function aria_current(string $prefix, bool $exact = false): string
{
    $req = \App\Core\App::request();
    $hit = $exact ? ($req && $req->path === $prefix) : is_active_path($prefix);
    return $hit ? ' aria-current="page"' : '';
}

/** Yönetim formları için etiketli alan üreticileri (hata mesajı ve erişilebilirlik dahil). */
function f_input(string $name, string $label, mixed $value = '', string $type = 'text', array $attrs = [], string $hint = ''): string
{
    $id = 'f_' . preg_replace('/[^a-z0-9_]/i', '_', $name);
    $val = old($name, $value);
    $a = '';
    foreach ($attrs as $k => $v) {
        $a .= $v === true ? ' ' . e($k) : ' ' . e($k) . '="' . e($v) . '"';
    }
    $req = isset($attrs['required']) ? ' <span aria-hidden="true" style="color:var(--c-danger)">*</span>' : '';
    return '<div class="field' . (isset($attrs['data-span']) ? ' span-2' : '') . '"><label for="' . $id . '">' . e($label) . $req . '</label>'
        . '<input type="' . e($type) . '" id="' . $id . '" name="' . e($name) . '" value="' . e(is_scalar($val) ? $val : '') . '"' . $a . aria_error($name) . '>'
        . ($hint !== '' ? '<p class="hint">' . e($hint) . '</p>' : '') . field_error($name) . '</div>';
}

function f_textarea(string $name, string $label, mixed $value = '', int $rows = 4, string $hint = '', bool $span = true): string
{
    $id = 'f_' . preg_replace('/[^a-z0-9_]/i', '_', $name);
    return '<div class="field' . ($span ? ' span-2' : '') . '"><label for="' . $id . '">' . e($label) . '</label>'
        . '<textarea id="' . $id . '" name="' . e($name) . '" rows="' . $rows . '"' . aria_error($name) . '>' . e(old($name, $value)) . '</textarea>'
        . ($hint !== '' ? '<p class="hint">' . e($hint) . '</p>' : '') . field_error($name) . '</div>';
}

/** @param array<string|int,string> $options */
function f_select(string $name, string $label, array $options, mixed $value = '', string $empty = '', string $hint = '', bool $required = false): string
{
    $id = 'f_' . preg_replace('/[^a-z0-9_]/i', '_', $name);
    $val = (string) old($name, $value ?? '');
    $h = '<div class="field"><label for="' . $id . '">' . e($label) . ($required ? ' <span aria-hidden="true" style="color:var(--c-danger)">*</span>' : '') . '</label><select id="' . $id . '" name="' . e($name) . '"' . ($required ? ' required' : '') . aria_error($name) . '>';
    if ($empty !== '') {
        $h .= '<option value="">' . e($empty) . '</option>';
    }
    foreach ($options as $k => $l) {
        $h .= '<option value="' . e($k) . '"' . ((string) $k === $val ? ' selected' : '') . '>' . e($l) . '</option>';
    }
    return $h . '</select>' . ($hint !== '' ? '<p class="hint">' . e($hint) . '</p>' : '') . field_error($name) . '</div>';
}

function f_check(string $name, string $label, bool $checked, string $hint = ''): string
{
    $o = old($name, null);
    $on = $o === null ? $checked : (bool) $o;
    return '<input type="hidden" name="' . e($name) . '" value="0"><label class="check"><input type="checkbox" name="' . e($name) . '" value="1"' . ($on ? ' checked' : '') . '><span>' . e($label) . ($hint !== '' ? '<br><small class="muted">' . e($hint) . '</small>' : '') . '</span></label>';
}

function options(array $rows, string $key = 'id', string $label = 'name'): array
{
    $o = [];
    foreach ($rows as $r) {
        $o[$r[$key]] = $r[$label];
    }
    return $o;
}

function money_input(?int $minor): string
{
    return \App\Core\Money::input($minor);
}

function bp_input(?int $bp): string
{
    if ($bp === null) {
        return '';
    }
    return rtrim(rtrim(number_format($bp / 100, 2, ',', ''), '0'), ',');
}
