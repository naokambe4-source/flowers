<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Para hesapları kuruş (minor unit, int) ile yapılır; float kullanılmaz.
 * Yüzdeler baz puan (bp) olarak tutulur: %10 = 1000 bp.
 */
final class Money
{
    /** Tutarın baz puan kadar yüzdesi; yarım kuruş yukarı yuvarlanır. */
    public static function percentOf(int $minor, int $bp): int
    {
        return intdiv($minor * $bp + ($minor >= 0 ? 5000 : -5000), 10000);
    }

    public static function applyDiscount(int $minor, int $bp): int
    {
        return $minor - self::percentOf($minor, $bp);
    }

    /** "12.500,50", "12500.50", "12500" → 1250050 */
    public static function parse(string $input): ?int
    {
        $s = str_replace([' ', 'TL', '₺', "\u{00A0}"], '', trim($input));
        if ($s === '') {
            return null;
        }
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $s) || preg_match('/^\d+,\d{1,2}$/', $s)) {
            $s = str_replace(['.', ','], ['', '.'], $s);
        } elseif (preg_match('/^\d{1,3}(,\d{3})+(\.\d{1,2})?$/', $s)) {
            $s = str_replace(',', '', $s);
        }
        if (!preg_match('/^-?(\d+)(?:\.(\d{1,2}))?$/', $s, $m)) {
            return null;
        }
        $neg = str_starts_with($s, '-');
        $minor = (int) $m[1] * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
        return $neg ? -$minor : $minor;
    }

    /** "10", "10,5", "%12.25" → bp */
    public static function parsePercent(string $input): ?int
    {
        $s = str_replace(['%', ' '], '', trim($input));
        $s = str_replace(',', '.', $s);
        if (!preg_match('/^(\d{1,3})(?:\.(\d{1,2}))?$/', $s, $m)) {
            return null;
        }
        return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
    }

    public static function format(?int $minor, string $currency = 'TRY', bool $decimals = true): string
    {
        if ($minor === null) {
            return '—';
        }
        $neg = $minor < 0;
        $abs = abs($minor);
        $major = intdiv($abs, 100);
        $cents = $abs % 100;
        $txt = number_format($major, 0, ',', '.');
        if ($decimals && $cents > 0) {
            $txt .= ',' . str_pad((string) $cents, 2, '0', STR_PAD_LEFT);
        }
        $symbol = match ($currency) {
            'TRY' => 'TL',
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            default => $currency,
        };
        return ($neg ? '-' : '') . $txt . ' ' . $symbol;
    }

    /** Form alanlarında gösterim: 1250050 → "12.500,50" */
    public static function input(?int $minor): string
    {
        if ($minor === null) {
            return '';
        }
        $major = intdiv(abs($minor), 100);
        $cents = abs($minor) % 100;
        return ($minor < 0 ? '-' : '') . number_format($major, 0, ',', '.') . ',' . str_pad((string) $cents, 2, '0', STR_PAD_LEFT);
    }

    public static function percent(int $bp): string
    {
        $whole = intdiv($bp, 100);
        $frac = $bp % 100;
        return '%' . $whole . ($frac ? ',' . rtrim(str_pad((string) $frac, 2, '0', STR_PAD_LEFT), '0') : '');
    }

    /** Tutarı oranına göre böler (KDV dahil tutardan vergi payını ayırmak için). */
    public static function taxPortion(int $grossMinor, int $taxBp): int
    {
        if ($taxBp <= 0) {
            return 0;
        }
        $net = intdiv($grossMinor * 10000 + intdiv(10000 + $taxBp, 2), 10000 + $taxBp);
        return $grossMinor - $net;
    }
}
