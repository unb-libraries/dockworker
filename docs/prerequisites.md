# Working With Dockworker
## General requirements
Although dockworker applications can be deployed and developed on OSX, the only officially supported operating system is Linux.

## Software Prerequisites
You must have the following tools available for use from the command line:

* [docker](https://www.docker.com): Installation steps [are located here](https://docs.docker.com/install/).
* [docker-compose](https://docs.docker.com/compose/): Installation steps [are located here](https://docs.docker.com/compose/install/).
* [PHP8.0+](https://php.org/): Install via ```apt-get install php-cli```
* Various PHP Extensions: Install via ```apt-get install php-curl php-ctype php-dom php-gd php-mbstring php-posix php-yaml php-zip```
* [composer](https://getcomposer.org/): Installation steps [are located here](https://getcomposer.org/download/).
* [jq](https://stedolan.github.io/jq/): Installation steps [are located here](https://stedolan.github.io/jq/download/).

## Networking
Building applications requires your local development instance to make HTTP and HTTPS requests. These requests must not be blocked. If you use a proxy server to connect to the web, you must also configure git to use that proxy to deploy applications with dockworker.
