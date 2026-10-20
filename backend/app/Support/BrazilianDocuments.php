<?php

namespace App\Support;

final class BrazilianDocuments
{
    public static function cnpj(string $value): string
    {
        $raw = strtoupper(preg_replace('/[.\/\s-]/', '', trim($value)));

        return strlen($raw) === 14
            ? substr($raw, 0, 2).'.'.substr($raw, 2, 3).'.'.substr($raw, 5, 3).'/'.substr($raw, 8, 4).'-'.substr($raw, 12)
            : strtoupper(trim($value));
    }

    public static function validCnpj(string $value): bool
    {
        $raw = str_replace(['.', '/', '-'], '', self::cnpj($value));
        if (! preg_match('/^[A-Z0-9]{12}[0-9]{2}$/D', $raw) || preg_match('/^(.)\1{13}$/', $raw)) {
            return false;
        }
        $base = substr($raw, 0, 12);
        for ($digit = 0; $digit < 2; $digit++) {
            $sum = 0;
            $weight = 2;
            for ($i = strlen($base) - 1; $i >= 0; $i--) {
                $sum += (ord($base[$i]) - 48) * $weight;
                $weight = $weight === 9 ? 2 : $weight + 1;
            }
            $remainder = $sum % 11;
            $base .= (string) ($remainder < 2 ? 0 : 11 - $remainder);
        }

        return hash_equals($base, $raw);
    }

    public static function phone(string $value): string
    {
        if (! preg_match('/^[0-9()\s-]*$/D', $value)) {
            return $value;
        }
        $raw = preg_replace('/\D/', '', $value);
        if (! in_array(strlen($raw), [10, 11], true)) {
            return $value;
        }

        return '('.substr($raw, 0, 2).') '.substr($raw, 2, -4).'-'.substr($raw, -4);
    }
}
