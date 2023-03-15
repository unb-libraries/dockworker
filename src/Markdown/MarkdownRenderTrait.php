<?php

namespace Dockworker\Markdown;

/**
 * Provides methods to interact with a local filesystem.
 */
trait MarkdownRenderTrait
{
  static function renderMarkdownTable(array $headers, array $rows): string
  {
    $table = '';
    $table .= '| ' . implode(' | ', $headers) . " |\n";
    foreach ($rows as $row) {
      $table .= '| ' . implode(' | ', $row) . " |\n";
    }
    return $table;
  }
}
