<?php

namespace App;

class SummaryService
{
    private const STOP_WORDS = ['и', 'в', 'на', 'с', 'а', 'но', 'по', 'к', 'у', 'о', 'от', 'из', 'за', 'не', 'до', 'же', 'то', 'это'];

    /** Short summary text for one journal's rows, used in the main list. */
    public static function summarizeItems(array $items): string
    {
        $hasSanitaryViolation = false;
        $hasFault = false;
        $hasComments = false;

        foreach ($items as $item) {
            $score = $item['sanitary_score'];
            if ($score !== null && $score !== '' && (int) $score >= 1 && (int) $score <= 3) {
                $hasSanitaryViolation = true;
            }
            if (($item['is_faulty'] ?? '') && $item['is_faulty'] !== 'Ок') {
                $hasFault = true;
            }
            if (($item['lighting'] ?? '') && $item['lighting'] !== 'Ок') {
                $hasFault = true;
            }
            if (trim((string) ($item['comment'] ?? '')) !== '') {
                $hasComments = true;
            }
        }

        if (!$hasSanitaryViolation && !$hasFault && !$hasComments) {
            return '✅ Без нарушений';
        }

        $parts = [];
        if ($hasSanitaryViolation) {
            $parts[] = '⚠ Нарушение санитарных норм';
        }
        if ($hasFault) {
            $parts[] = '⚠ Есть неисправности';
        }
        if ($hasComments) {
            $parts[] = 'Есть замечания';
        }

        return implode('; ', $parts);
    }

    /** true if the row set for a journal has any warning-level condition. */
    public static function hasAnyViolation(array $items): bool
    {
        return self::summarizeItems($items) !== '✅ Без нарушений';
    }

    /**
     * Splits Russian/Latin text into normalized words, stripping punctuation
     * and stop-words.
     */
    public static function tokenize(string $text): array
    {
        $text = mb_strtolower($text);
        // Keep Cyrillic/Latin letters and digits, split everything else.
        $words = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($words, function ($w) {
            return mb_strlen($w) > 1 && !in_array($w, self::STOP_WORDS, true);
        }));
    }

    /**
     * Top-3 most frequent words across a set of comments.
     * @param string[] $comments
     * @return array [['word' => ..., 'count' => ...], ...] up to 3 entries
     */
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

    /** Human-readable "Топ слов за период: 1. Пол (упомянуто 4 раза), ..." string. */
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

    /** Validation for the "Завершить" action: every row needs faulty/score/lighting set. */
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
