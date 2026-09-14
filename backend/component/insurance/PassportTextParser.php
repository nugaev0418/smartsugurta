<?php

namespace backend\component\insurance;

/**
 * Free-text passport parsing rules, extracted verbatim from
 * BotController::checkPassport()/parsePassportData(). Only the bot's
 * chat-based flow needs this — the Mini App collects seria/number/birthDate
 * as separate form fields already.
 */
class PassportTextParser
{
    public function looksLikePassport(string $value): bool
    {
        $value = strtoupper(trim($value));

        return (bool) preg_match('/^[A-Z]{2}\d{7}$/', $value);
    }

    /**
     * Splits a validated 9-character "AB1234567"-shaped passport string
     * into seria/number. Matches BotController's original substr() calls
     * exactly, including that they read $raw as given rather than the
     * trimmed/uppercased value looksLikePassport() validates against.
     */
    public function splitPassport(string $raw): array
    {
        return [
            'seria' => strtoupper(substr($raw, 0, 2)),
            'number' => substr($raw, 2, 7),
        ];
    }

    /**
     * Extracts seria+number+birthdate from a longer free-text message
     * (used when the bot asks for a driver's full passport line in one go).
     */
    public function parse(string $text): array
    {
        $text = strtoupper(trim($text));

        $pattern = '/
            (?P<series>[A-Z]{2})      # AD, AF, AG
            \s*                       # bo\'sh joy bo\'lishi mumkin
            (?P<number>\d{7})         # 1234567
            \s+                       # kamida 1 bo\'sh joy
            (?P<date>
                (?:\d{2}[\s.,]\d{2}[\s.,]\d{4}|\d{8})
            )
        /x';

        if (!preg_match($pattern, $text, $m)) {
            return ['success' => false];
        }

        $rawDate = preg_replace('/[^0-9]/', '', $m['date']);

        $day   = substr($rawDate, 0, 2);
        $month = substr($rawDate, 2, 2);
        $year  = substr($rawDate, 4, 4);

        $birthDate = "$day.$month.$year";

        return [
            'success' => true,
            'series'  => $m['series'],
            'number'  => $m['number'],
            'birth'   => $birthDate,
        ];
    }
}
