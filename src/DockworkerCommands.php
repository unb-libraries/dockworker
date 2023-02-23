<?php

namespace Dockworker;

use Dockworker\Core\RoboConfigTrait;
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

/**
 * Defines a base abstract class for all Dockworker commands.
 *
 * This is not a command class. It should not contain any hooks or commands.
 */
abstract class DockworkerCommands extends Tasks implements ConfigAwareInterface, ContainerAwareInterface, IOAwareInterface, LoggerAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use FileSystemOperationsTrait;
    use LoggerAwareTrait;
    use RoboConfigTrait;

    protected const DOCKWORKER_CONFIG_FILE = '.dockworker/dockworker.yml';
    protected const ERROR_CONFIG_ELEMENT_UNSET = 'Error! A required configuration element [%s] does not exist in %s.';

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
     * The UNB Libraries application uuid for the application.
     *
     * @link https://systems.lib.unb.ca/wiki/systems:docker:unique-site-uuids UNB Libraries UUIDs
     * @var string
     */
    protected string $applicationUuid;

    /**
     * The full path to the application's dockworker configuration file.
     *
     * @var string
     */
    protected string $configFile;

    /**
     * The current user's operating system home directory.
     *
     * @var string
     */
    protected string $userHomeDir;

    /**
     * The current user's operating system username.
     *
     * @var string
     */
    protected string $userName;



    /**
     * DockworkerCommands constructor.
     *
     * @throws \Dockworker\DockworkerException
     */
    public function __construct()
    {
        $this->applicationRoot = realpath(__DIR__ . "/../../../../");
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
        $this->userHomeDir = $_SERVER['HOME'];
        $this->setCoreProperties();
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
        $this->setPropertyFromConfigKey(
            $config,
            'applicationUuid',
            'dockworker.application.identifiers.uuid'
        );
        if ($this->getConfigItem(
          $config,
          'dockworker.application.workflows.vcs.type'
          ) != 'github') {
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
              'dockworker.application.workflows.vcs.owner'
            );
            $this->setPropertyFromConfigKey(
              $config,
              'applicationGitHubRepoName',
              'dockworker.application.workflows.vcs.name'
            );
        }
    }
}
