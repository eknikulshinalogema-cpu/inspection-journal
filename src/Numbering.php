<?php

namespace App;

class Numbering
{
    /**
     * Assigns display numbers to a sorted list of rows: 1, 1.1, 2, 2.1, 3 ...
     * The seed data (config/default_rows.php) alternates section-header rows
     * with their equipment-detail rows, so positionally every second row
     * (0-based index 1, 3, 5 ...) is the ".1" of the row before it. Rows
     * added later via the admin panel simply become their own new top-level
     * number, keeping numbering stable even as the row set changes.
     *
     * @param array $rows rows ordered by sort_order ASC, any array keys
     * @return array [$key => 'N' or 'N.1', ...] in the same key order as $rows
     */
    public static function assign(array $rows): array
    {
        $numbers = [];
        $topLevel = 0;
        $expectSub = false;

        foreach ($rows as $key => $row) {
            if ($expectSub) {
                $numbers[$key] = $topLevel . '.1';
                $expectSub = false;
                continue;
            }

            $topLevel++;
            $numbers[$key] = (string) $topLevel;
            $expectSub = true;
        }

        return $numbers;
    }
}
