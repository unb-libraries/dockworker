<?php

namespace Dockworker\Yaml;

use Dockworker\Cli\CliCommand;
use Dockworker\Cli\CliCommandTrait;
use Dockworker\IO\DockworkerIO;

/**
 * Provides methods to validate YAML vs yaml-lint.
 */
trait YamlLintTrait
{
    use CliCommandTrait;

    /**
     * Validates files using yaml-lint.
     *
     * @param string $files
     *   The files to validate.
     *
     * @return \Dockworker\Cli\CliCommand|null
     *   The CLI command object.
     */
    protected function validateYamlFiles(
        DockworkerIO $io,
        array $files
    ): CliCommand|null {
        if (!empty($files)) {
            $cmd = [
                'vendor/bin/yaml-lint',
                '--',
            ];
            $cmd = array_merge($cmd, $files);
            return $this->executeCliCommand(
                $cmd,
                $io,
                null,
                '',
                '',
                true
            );
        }
    }
}
