<?php

namespace Dockworker\Formatter;

/**
 * Provides methods to generate JIRAML from structured data.
 */
trait OutputFormatterTrait
{
    protected $outputFormatter;

    public function setOutputFormatter(string $formatter)
    {
        // Check if the formatter is valid.
        $output_formatters = [
            'plain' => new PlainTextFormatter(),
            'jira' => new JiraFormatter(),
        ];

        if (!in_array($formatter, array_keys($output_formatters))) {
            $valid_formatters = implode(
                ', ',
                array_keys($output_formatters)
            );
            throw new \Exception("Invalid formatter: $formatter. Valid formatters are: $valid_formatters");
        }
        $this->outputFormatter = $formatter;
        return $output_formatters[$formatter];
    }
}
