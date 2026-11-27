<?php

namespace App\Support;

class EpisodeTitle
{
    public static function title(?string $title, int $number): string
    {
        $cleaned = self::stripPrefix($title);

        return $cleaned !== '' ? $cleaned : 'Episode '.$number;
    }

    public static function label(?string $title, int $number): string
    {
        $cleaned = self::stripPrefix($title);

        return $cleaned !== '' ? 'Episode '.$number.': '.$cleaned : 'Episode '.$number;
    }

    private static function stripPrefix(?string $title): string
    {
        $value = html_entity_decode(strip_tags((string) $title), ENT_QUOTES, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?: '';
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $pattern = '/^(?:ep(?:isode)?\.?\s*#?\d+|第\s*\d+\s*[話话])\s*(?:[-:.\x{2013}\x{2014}]\s*)?/iu';

        for ($i = 0; $i < 2; $i++) {
            $next = preg_replace($pattern, '', $value) ?: $value;
            if ($next === $value) {
                break;
            }
            $value = $next;
        }

        $value = preg_replace('/^[\s\-:.\x{2013}\x{2014}]+/u', '', $value) ?: '';

        return trim($value);
    }
}
