# Dockworker
## Develop, Test and Deploy into Kubernetes Docker-Based Web Applications
Dockworker is a framework of (mostly) Robo commands that makes local development of docker-based web applications a breeze. It also tests and deploys these applications into kubernetes. Stand-alone, this package offers very little benefit, but to see how we use dockworker: check out [an example of how we use dockworker to deploy lib.unb.ca](https://github.com/unb-libraries/lib.unb.ca).

![Dockworker Startup](https://github.com/unb-libraries/dockworker/raw/3.x/img/dockworker-startup.gif "Dockworker Startup")

## Getting Started
### Requirements
The following packages are required to be globally installed on your development instance:

* [PHP7](https://php.org/) with mbstring and xml extensions - Install instructions [are here for OSX](https://gist.github.com/JacobSanford/52ad35b83bcde5c113072d5591eb89bd).
* [Composer](https://getcomposer.org/)
* [docker](https://www.docker.com)/[docker-compose](https://docs.docker.com/compose/) - An installation HowTo for OSX and Linux [is located here, in section 2.](https://github.com/unb-libraries/docker-drupal/wiki/2.-Setting-Up-Prerequisites).

## Commands
```
 deployment
  deployment:apply                       Updates the application's k8s deployment definition.
  deployment:image:update                Updates the application's k8s deployment image.
  deployment:logs                        Displays the application's k8s deployed pod(s) logs.
  deployment:logs:check                  Checks the application's deployed k8s pod(s) logs for errors.
  deployment:restart                     Restarts the k8s deployment rollout.
  deployment:shell                       Open a shell into the k8s deployment.
  deployment:status                      Checks the application's k8s deployment rollout status.
 dockworker
  dockworker:docker:cleanup              Clean up unused local docker assets.
  dockworker:git:setup-hooks             Set up the required git hooks for dockworker.
  dockworker:permissions:fix             [pfix] Sets the correct repository file permissions. Requires sudo.
  dockworker:update                      [update] Self-updates the dockworker application.
 image
  image:build                            Builds the application's docker image.
  image:build-push                       Builds the application's docker image and pushes it to the deployment repository.
  image:deploy                           Builds, tags, pushes and deploys the application's docker image.
 local
  local:build                            [build] Builds the local application's docker image.
  local:build-test                       Builds the application image, starts a local container, and runs all tests.
  local:destroy                          Halts the local application and removes any persistent data.
  local:halt                             Halts the local application without removing any persistent data.
  local:hard-reset                       Destroys the local application, and removes any uncommitted repo changes.
  local:logs                             Displays the local application's container logs.
  local:logs:check                       Checks the local application's container logs for errors.
  local:logs:tail                        [logs] Display previous local application container logs and monitor for new ones.
  local:pull-upstream                    Pulls any upstream images used in building the local application image.
  local:rebuild                          [rebuild] Stops the local container and re-starts it, preserving persistent data.
  local:rm                               [rm] Removes removes all persistent data from the local docker application.
  local:shell                            [shell] Opens the local application container's shell.
  local:start                            [start] Brings up the local application container, displays the application logs.
  local:start-over                       [start-over|deploy] Kills the local container, removes persistent data, and rebuilds/restarts.
  local:up                               [up] Brings up the local application container.
  local:update-hostfile                  Updates the local system hostfile for the local application. Requires sudo.
 tests
  tests:all                              [test] Tests the local application using all testing frameworks.
  tests:behat                            [behat] Runs the Behat tests defined for the application.
  tests:phpunit                          [phpunit] Runs the PHPUnit tests defined for the application.
 theme
  theme:build-all                        [build-themes] Builds the local application's deployable theme assets from source.
 travis
  travis:id:latest                       Retrieves the latest travis build ID for the application.
  travis:info:latest                     Retrieves the latest travis build details for the application.
  travis:logs                            Retrieves logs for a travis build for the application.
  travis:logs:latest                     Retrieves logs for the latest travis build for the application.
  travis:restart                         Restarts a travis build for the application.
  travis:restart:latest                  Restarts the latest travis build for the application.
 validate
  validate:git:commit-msg                Validates a git commit message against project standards.
```

## Author / Licensing
- Developed by [![UNB Libraries](https://github.com/unb-libraries/assets/raw/master/unblibbadge.png "UNB Libraries")](https://lib.unb.ca/)
- This work is published through our strong commitment to making as much of our development/workflow as possible freely available.
- Consequently, the contents of this repository [unb-libraries/dockworker] are licensed under the [MIT License](http://opensource.org/licenses/mit-license.html). This license explicitly excludes:
   - Any website content, which remains the exclusive property of its author(s).
   - The UNB logo and any of the associated suite of visual identity assets, which remains the exclusive property of the University of New Brunswick.
