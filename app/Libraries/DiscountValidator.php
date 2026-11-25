<?php

declare(strict_types=1);

namespace App\Libraries;

final class DiscountValidator
{
    private const POSITIVE_NUMBER = '[0-9]+(?:[.,][0-9]+)?';
    private const SIGNED_NUMBER = '[+\-\x{2212}]?' . self::POSITIVE_NUMBER;

    public const MESSAGES = [
        'zero' => 'Diskon 0% tidak diperbolehkan. Jika tidak terdapat program diskon, kosongkan kolom Diskon.',
        'negative' => 'Diskon harus lebih dari 0% dan kurang dari 100%.',
        'out_of_range' => 'Diskon harus lebih dari 0% dan kurang dari 100%.',
        'fraction' => 'Diskon harus berupa persentase bulat antara 1% dan 99%.',
        'malformed' => 'Format diskon tidak valid. Masukkan persentase bulat antara 1% dan 99%.',
    ];

    /** @return array{valid: bool, value: ?int, reason: string, message: ?string} */
    public static function forStorage(mixed $input): array
    {
        return self::validate($input, false);
    }

    /** @return array{valid: bool, value: ?int, reason: string, message: ?string} */
    public static function forPrinting(mixed $input): array
    {
        return self::validate($input, true);
    }

    /** A program without a percent sign follows the caller's nominal-price parser. */
    public static function fromPromotionText(string $input): ?array
    {
        if (!str_contains($input, '%')) {
            return null;
        }

        if (preg_match('/\A\s*(?:[\p{L}][\p{L}.\s]*?)?(' . self::SIGNED_NUMBER . ')\s*%(?:\s*[\p{L}][^%]*)?\s*\z/u', $input, $matches)) {
            return self::forStorage($matches[1]);
        }

        return self::forStorage($input);
    }

    private static function validate(mixed $input, bool $allowLegacyZero): array
    {
        [$reason, $value] = self::classify($input);
        $valid = $reason === 'empty' || $reason === 'integer' || ($allowLegacyZero && $reason === 'zero');

        return [
            'valid' => $valid,
            'value' => $value,
            'reason' => $reason,
            'message' => $valid ? null : self::MESSAGES[$reason],
        ];
    }

    /** @return array{string, ?int} */
    private static function classify(mixed $input): array
    {
        if ($input === null || (is_string($input) && preg_match('/\A\s*\z/u', $input))) {
            return ['empty', null];
        }

        if (is_int($input) || (is_float($input) && is_finite($input))) {
            return match (true) {
                $input < 0 => ['negative', null],
                (float) $input === 0.0 => ['zero', null],
                $input >= 100 => ['out_of_range', null],
                floor($input) !== (float) $input => ['fraction', null],
                default => ['integer', (int) $input],
            };
        }

        if (!is_string($input) || !preg_match('/\A\s*([+\-\x{2212}]?)([0-9]+)(?:[.,]([0-9]+))?\s*%?\s*\z/u', $input, $matches)) {
            return ['malformed', null];
        }

        $integer = ltrim($matches[2], '0');
        $fraction = rtrim($matches[3] ?? '', '0');
        return match (true) {
            $integer === '' && $fraction === '' => ['zero', null],
            $matches[1] === '-' || $matches[1] === '−' => ['negative', null],
            strlen($integer) > 2 => ['out_of_range', null],
            $fraction !== '' => ['fraction', null],
            default => ['integer', (int) $integer],
        };
    }
}
