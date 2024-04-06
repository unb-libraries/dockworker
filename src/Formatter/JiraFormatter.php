<?php

namespace Dockworker\Formatter;

/**
 * Provides methods to generate JIRAML.
 */
Class JiraFormatter extends PlainTextFormatter
{
    public static function generateTable(array $headers, array $rows): string
    {
        $table_code = '';
        $table_code .= "||" . implode("||", $headers) . "||\n";
        foreach ($rows as $row) {
            $table_code .= "|" . implode("|", $row) . "|\n";
        }
        return $table_code;
    }

    public static function generateLink(string $url, string $text): string
    {
        return "[$text|$url]";
    }
}
