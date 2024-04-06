<?php

namespace Dockworker\Formatter;

/**
 * Provides methods to generate textual representations of data.
 */
class PlainTextFormatter {
    public static function generateTable(array $headers, array $rows): string
    {
        $table_code = '';
        $table_code .= implode("\t", $headers) . "\n";
        $table_code .= '---' . "\n";
        foreach ($rows as $row) {
            $table_code .= implode("\t", $row) . "\n";
        }
        return $table_code;
    }

    public static function generateLink(string $url, string $text): string
    {
        return "$text($url)";
    }
}
