<?php

namespace Dockworker;

use Consolidation\AnnotatedCommand\AnnotationData;
use Dockworker\Core\RoboConfigTrait;
use Dockworker\RepoFinder;
use Dockworker\System\FileSystemOperationsTrait;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Contract\IOAwareInterface;
use Robo\Robo;
use Robo\Tasks;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Defines a base abstract class for all Dockworker commands.
 *
 * This is not a command class. It should not contain any hooks or commands.
 */
abstract class DockworkerCommands extends Tasks implements
    ConfigAwareInterface,
    ContainerAwareInterface,
    IOAwareInterface,
    LoggerAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use FileSystemOperationsTrait;
    use LoggerAwareTrait;
    use RoboConfigTrait;

    protected const DOCKWORKER_CONFIG_FILE = '.dockworker/dockworker.yml';

    /**
     * The application's GitHub repository owner.
     *
     * @var string
     */
    protected string $applicationGitHubRepoOwner = '';

    /**
     * The application's GitHub repository name.
     *
     * @var string
     */
    protected string $applicationGitHubRepoName = '';

    /**
     * The name of the application.
     *
     * @var string
     */
    protected string $applicationName;

    /**
     * The path to the application's git repository.
     *
     * @var string
     */
    protected string $applicationRoot;

    /**
     * The shortened slug of the application.
     *
     * @var string
     */
    protected string $applicationShortSlug;

    /**
     * The 'slug' of the application.
     *
     * @var string
     */
    protected string $applicationSlug;

    /**
     * The full path to the application's dockworker configuration file.
     *
     * @var string
     */
    protected string $configFile;

    /**
     * Is TTY output supported on the current terminal?
     *
     * @var bool
     */
    protected bool $ttySupported = true;

    /**
     * The current user's operating system home directory.
     *
     * @var string
     */
    protected string $userHomeDir;

    /**
     * The current user's primary gid.
     *
     * @var string
     */
    protected string $userGid;

    /**
     * The current user's operating system username.
     *
     * @var string
     */
    protected string $userName;

    /**
     * @hook pre-init
     */
    public function initOptions()
    {
        $this->applicationRoot = RepoFinder::findRepoRoot();
        $this->configFile = $this->getPathFromPathElements(
            [
                $this->applicationRoot,
                self::DOCKWORKER_CONFIG_FILE,
            ]
        );
        Robo::loadConfiguration(
            [$this->configFile],
            $this->config
        );
        $this->userName = get_current_user();
        $this->userGid = posix_getgid();
        $this->userHomeDir = $_SERVER['HOME'];
        $this->ttySupported = !$this->outputSupportsTty();
        $this->setCoreProperties();
    }

    private function outputSupportsTty(): bool
    {
        return empty(getenv("CI"));
    }

    /**
     * Initializes the application's core properties.
     *
     * @throws \Dockworker\DockworkerException
     */
    public function setCoreProperties(): void
    {
        $config = Robo::config();
        $this->setPropertyFromConfigKey(
            $config,
            'applicationName',
            'dockworker.application.identifiers.id'
        );
        $this->setPropertyFromConfigKey(
            $config,
            'applicationSlug',
            'dockworker.application.identifiers.slug'
        );
        $this->setPropertyFromConfigKey(
            $config,
            'applicationShortSlug',
            'dockworker.application.identifiers.short_slug'
        );
        if (
            $this->getConfigItem(
                $config,
                'dockworker.workflows.vcs.type'
            ) != 'github'
        ) {
            throw new DockworkerException(sprintf(
                'Error! Dockworker only supports GitHub as a VCS. The VCS type [%s] is not currently supported.',
                $this->getConfigItem(
                    $config,
                    'dockworker.workflows.vcs.type'
                )
            ));
        } else {
            $this->setPropertyFromConfigKey(
                $config,
                'applicationGitHubRepoOwner',
                'dockworker.workflows.vcs.owner'
            );
            $this->setPropertyFromConfigKey(
                $config,
                'applicationGitHubRepoName',
                'dockworker.workflows.vcs.name'
            );
        }
    }
}
