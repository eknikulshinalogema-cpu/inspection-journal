<?php

namespace App;

class SummaryService
{
    private const STOP_WORDS = ['и', 'в', 'на', 'с', 'а', 'но', 'по', 'к', 'у', 'о', 'от', 'из', 'за', 'не', 'до', 'же', 'то', 'это'];

    private const NORM_FAULTY = 'Исправен';
    private const NORM_LIGHTING = 'Отключено';
    private const NORM_MIN_SCORE = 4;

    /** Отклонения для одной строки: пусто, если строка соответствует норме. */
    private static function rowDeviations(array $item): array
    {
        $deviations = [];

        $faulty = $item['is_faulty'] ?? null;
        if ($faulty !== null && $faulty !== '' && $faulty !== self::NORM_FAULTY) {
            $deviations[] = 'исправность: ' . $faulty;
        }

        $lighting = $item['lighting'] ?? null;
        if ($lighting !== null && $lighting !== '' && $lighting !== self::NORM_LIGHTING) {
            $deviations[] = 'освещение: ' . $lighting;
        }

        $score = $item['sanitary_score'];
        if ($score !== null && $score !== '' && (int) $score < self::NORM_MIN_SCORE) {
            $deviations[] = 'санитарное состояние: ' . (int) $score;
        }

        return $deviations;
    }

    /** Полная выжимка по журналу: название строки + что не так, либо "Норма". */
    public static function summarizeItems(array $items): string
    {
        $lines = [];
        foreach ($items as $item) {
            $deviations = self::rowDeviations($item);
            if (!empty($deviations)) {
                $lines[] = $item['title'] . ' — ' . implode(', ', $deviations);
            }
        }

        return empty($lines) ? 'Норма' : implode('; ', $lines);
    }

    public static function hasAnyViolation(array $items): bool
    {
        foreach ($items as $item) {
            if (!empty(self::rowDeviations($item))) {
                return true;
            }
        }
        return false;
    }

    public static function tokenize(string $text): array
    {
        $text = mb_strtolower($text);
        $words = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($words, function ($w) {
            return mb_strlen($w) > 1 && !in_array($w, self::STOP_WORDS, true);
        }));
    }

    public static function topWords(array $comments): array
    {
        $freq = [];
        foreach ($comments as $comment) {
            foreach (self::tokenize((string) $comment) as $word) {
                $freq[$word] = ($freq[$word] ?? 0) + 1;
            }
        }

        arsort($freq);
        $top = array_slice($freq, 0, 3, true);

        $result = [];
        foreach ($top as $word => $count) {
            $result[] = ['word' => $word, 'count' => $count];
        }
        return $result;
    }

    public static function topWordsLabel(array $comments): string
    {
        $top = self::topWords($comments);
        if (empty($top)) {
            return 'Замечаний за период не найдено.';
        }

        $parts = [];
        $i = 0;
        foreach ($top as $entry) {
            $i++;
            $wordDisplay = mb_convert_case($entry['word'], MB_CASE_TITLE, 'UTF-8');
            $times = self::pluralizeTimes((int) $entry['count']);
            $parts[] = "{$i}. {$wordDisplay} ({$entry['count']} {$times})";
        }

        return 'Топ слов за период: ' . implode(', ', $parts);
    }

    private static function pluralizeTimes(int $n): string
    {
        $n10 = $n % 10;
        $n100 = $n % 100;
        if ($n10 === 1 && $n100 !== 11) {
            return 'раз';
        }
        if (in_array($n10, [2, 3, 4], true) && !in_array($n100, [12, 13, 14], true)) {
            return 'раза';
        }
        return 'раз';
    }

    public static function findIncompleteRows(array $items): array
    {
        $incomplete = [];
        foreach ($items as $item) {
            $missing = ($item['is_faulty'] ?? '') === '' || $item['is_faulty'] === null
                || $item['sanitary_score'] === null || $item['sanitary_score'] === ''
                || ($item['lighting'] ?? '') === '' || $item['lighting'] === null;
            if ($missing) {
                $incomplete[] = $item['id'];
            }
        }
        return $incomplete;
    }
}
