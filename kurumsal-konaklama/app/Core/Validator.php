<?php
declare(strict_types=1);

namespace App\Core;

use App\Exceptions\ValidationException;

/**
 * Kural tabanlı doğrulayıcı.
 * Örnek: Validator::make($data, ['email' => 'required|email|max:190'])->validate();
 */
final class Validator
{
    private array $errors = [];
    private array $clean = [];

    private const LABELS = [
        'first_name' => 'Ad', 'last_name' => 'Soyad', 'email' => 'E-posta', 'phone' => 'Telefon',
        'password' => 'Parola', 'name' => 'Ad', 'title' => 'Başlık', 'check_in' => 'Giriş tarihi',
        'check_out' => 'Çıkış tarihi', 'slug' => 'Kısa adres', 'message' => 'Mesaj', 'subject' => 'Konu',
        'institution_id' => 'Kurum', 'region_id' => 'Bölge', 'stars' => 'Yıldız',
    ];

    public function __construct(private readonly array $data, private readonly array $rules, private readonly array $labels = [])
    {
    }

    public static function make(array $data, array $rules, array $labels = []): self
    {
        return new self($data, $rules, $labels);
    }

    public function fails(): bool
    {
        $this->run();
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    /** @throws ValidationException */
    public function validate(): array
    {
        if ($this->fails()) {
            throw new ValidationException($this->errors);
        }
        return $this->clean;
    }

    private function label(string $field): string
    {
        return $this->labels[$field] ?? self::LABELS[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    private function run(): void
    {
        if ($this->clean !== [] || $this->errors !== []) {
            return;
        }
        foreach ($this->rules as $field => $ruleString) {
            $rules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $this->data[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $nullable = in_array('nullable', $rules, true);
            $empty = $value === null || $value === '' || $value === [];
            if ($empty) {
                if (in_array('required', $rules, true)) {
                    $this->errors[$field] = $this->label($field) . ' alanı zorunludur.';
                } elseif (in_array('accepted', $rules, true)) {
                    $this->errors[$field] = $this->label($field) . ' onaylanmalıdır.';
                } else {
                    $this->clean[$field] = $nullable ? null : ($value ?? null);
                }
                continue;
            }
            foreach ($rules as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $error = $this->check($name, $arg, $value, $field);
                if ($error !== null) {
                    $this->errors[$field] = $error;
                    continue 2;
                }
            }
            $this->clean[$field] = $value;
        }
    }

    private function check(string $rule, ?string $arg, mixed &$value, string $field): ?string
    {
        $l = $this->label($field);
        switch ($rule) {
            case 'required':
            case 'nullable':
            case 'string':
                if ($rule === 'string' && !is_string($value)) {
                    return "$l metin olmalıdır.";
                }
                return null;
            case 'accepted':
                return in_array($value, ['1', 1, 'on', true, 'yes', 'evet'], true) ? null : "$l onaylanmalıdır.";
            case 'email':
                $value = mb_strtolower((string) $value);
                return filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "$l geçerli bir e-posta adresi olmalıdır.";
            case 'phone':
                $digits = preg_replace('/\D+/', '', (string) $value) ?? '';
                if (strlen($digits) < 10 || strlen($digits) > 15) {
                    return "$l geçerli bir telefon numarası olmalıdır.";
                }
                $value = preg_replace('/[^\d+ ()-]/', '', (string) $value);
                return null;
            case 'int':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    return "$l tam sayı olmalıdır.";
                }
                $value = (int) $value;
                return null;
            case 'numeric':
                return is_numeric(str_replace(',', '.', (string) $value)) ? null : "$l sayı olmalıdır.";
            case 'min':
                if (is_int($value)) {
                    return $value < (int) $arg ? "$l en az $arg olmalıdır." : null;
                }
                return mb_strlen((string) $value) < (int) $arg ? "$l en az $arg karakter olmalıdır." : null;
            case 'max':
                if (is_int($value)) {
                    return $value > (int) $arg ? "$l en fazla $arg olabilir." : null;
                }
                return mb_strlen((string) $value) > (int) $arg ? "$l en fazla $arg karakter olabilir." : null;
            case 'in':
                return in_array((string) $value, explode(',', (string) $arg), true) ? null : "$l için geçersiz seçim.";
            case 'date':
                $d = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $value);
                return $d && $d->format('Y-m-d') === $value ? null : "$l geçerli bir tarih olmalıdır (GG.AA.YYYY).";
            case 'datetime':
                $v = str_replace('T', ' ', (string) $value);
                $d = \DateTimeImmutable::createFromFormat('Y-m-d H:i', substr($v, 0, 16));
                if (!$d) {
                    return "$l geçerli bir tarih ve saat olmalıdır.";
                }
                $value = $d->format('Y-m-d H:i:s');
                return null;
            case 'time':
                return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $value) ? null : "$l SS:DD biçiminde olmalıdır.";
            case 'slug':
                return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $value) ? null : "$l yalnız küçük harf, rakam ve tire içerebilir.";
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', (string) $value) ? null : "$l geçerli bir web adresi olmalıdır.";
            case 'password':
                $v = (string) $value;
                if (mb_strlen($v) < 10 || !preg_match('/[A-Za-zÇĞİÖŞÜçğıöşü]/u', $v) || !preg_match('/\d/', $v)) {
                    return 'Parola en az 10 karakter olmalı, harf ve rakam içermelidir.';
                }
                return null;
            case 'same':
                return ($this->data[$arg] ?? null) === $value ? null : "$l eşleşmiyor.";
            case 'money':
                $minor = Money::parse((string) $value);
                if ($minor === null || $minor < 0) {
                    return "$l geçerli bir tutar olmalıdır (örn. 12.500,00).";
                }
                $value = $minor;
                return null;
            case 'percent':
                $bp = Money::parsePercent((string) $value);
                if ($bp === null || $bp < 0 || $bp > 10000) {
                    return "$l 0 ile 100 arasında bir yüzde olmalıdır.";
                }
                $value = $bp;
                return null;
            case 'decimal_coord':
                return is_numeric($value) && abs((float) $value) <= 180 ? null : "$l geçerli bir koordinat olmalıdır.";
            case 'bool':
                $value = in_array($value, ['1', 1, 'on', true, 'true'], true) ? 1 : 0;
                return null;
            default:
                throw new \InvalidArgumentException('Bilinmeyen kural: ' . $rule);
        }
    }
}
