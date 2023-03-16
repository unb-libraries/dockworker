<?php

namespace Dockworker\Markdown;

/**
 * Provides methods to render Markdown elements.
 */
trait MarkdownRenderTrait
{
    /**
     * Renders a data table in Markdown.
     *
     * @param array $headers
     *   The headers for the table.
     * @param array $rows
     *   The rows for the table.
     *
     * @return string
     *   The rendered Markdown table.
     */
    public static function renderMarkdownTable(
        array $headers,
        array $rows
    ): string {
        $table = '| ' . implode(' | ', $headers) . " |\n";
        $table .= '| ' .
            implode(
                ' | ',
                array_fill(
                    0,
                    count($headers),
                    '---'
                )
            ) .
            " |\n";
        foreach ($rows as $row) {
            $table .= '| ' . implode(' | ', $row) . " |\n";
        }
        return $table;
    }
}
