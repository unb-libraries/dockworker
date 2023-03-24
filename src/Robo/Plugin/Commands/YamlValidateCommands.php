<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerCommands;
use Dockworker\Git\GitRepoTrait;
use Dockworker\IO\DockworkerIOTrait;
use Dockworker\Yaml\YamlLintTrait;

/**
 * Provides commands for validating YAML within an application.
 */
class YamlValidateCommands extends DockworkerCommands
{
    use DockworkerIOTrait;
    use GitRepoTrait;
    use YamlLintTrait;

    private const YAML_EXTENSIONS = [
        'yaml',
        'yml',
    ];

    /**
     * Validates YAML files.
     *
     * @command validate:yaml
     */
    public function validateYaml(
        array $options = [
            'staged' => false,
            'changed' => false,
        ]
    ): void {
        if ($options['staged'] && $options['changed']) {
            $this->dockworkerIO->error('Cannot use both --staged and --changed');
            exit(1);
        }
        if ($options['staged']) {
            $title = 'Validating Staged YAML';
            $files = $this->getApplicationGitRepoStagedFiles(
                '/.*\.{' .
                implode('|', self::YAML_EXTENSIONS) .
                '}/'
            );
        } elseif ($options['changed']) {
            $title = 'Validating Changed YAML';
            $files = $this->getApplicationGitRepoChangedFiles(
                '/.*\.{' .
                implode('|', self::YAML_EXTENSIONS) .
                '}/'
            );
        } else {
            $title = 'Validating YAML';
            $files = ['.'];
        }

        if (!empty($files)) {
            $this->dockworkerIO->title($title);
            $process = $this->validateYamlFiles(
                $this->dockworkerIO,
                $files
            );
            exit(
                $process->getExitCode()
            );
        }
        $this->say('No YAML files found to validate');
    }
}
