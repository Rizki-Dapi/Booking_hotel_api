<?php

namespace App\Helpers;

class ProfanityFilter
{

    private static array $bannedWords = [
        'fuck',
        'shit',
        'bitch',
        'asshole',
    ];

    public static function censor(string $text): string
    {
        foreach (self::$bannedWords as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/iu';
            $text = preg_replace_callback($pattern, function ($matches) {
                return str_repeat('*', strlen($matches[0]));
            }, $text);
        }

        return $text;
    }

    public static function contains(string $text): bool
    {
        foreach (self::$bannedWords as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/iu', $text)) {
                return true;
            }
        }

        return false;
    }
}
