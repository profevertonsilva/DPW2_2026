<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Formatação de datas da API (RNF #09): ISO 8601 com deslocamento (-03:00)
 * e também data e hora prontas para exibir, sem conversão no aparelho.
 */
final class Datas
{
    public static function local(CarbonInterface $momento): CarbonInterface
    {
        return $momento->copy()->setTimezone(config('app.timezone'));
    }

    public static function iso(?CarbonInterface $momento): ?string
    {
        return $momento ? self::local($momento)->toIso8601String() : null;
    }

    public static function data(?CarbonInterface $momento): ?string
    {
        return $momento ? self::local($momento)->format('d/m/Y') : null;
    }

    public static function hora(?CarbonInterface $momento): ?string
    {
        return $momento ? self::local($momento)->format('H:i') : null;
    }

    /** '09:00:00' -> '09:00' */
    public static function horaCurta(?string $hora): ?string
    {
        return $hora === null ? null : substr($hora, 0, 5);
    }
}
