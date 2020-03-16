#!/usr/bin/env bash
set -e

# Install docker-compose.
DOCKER_COMPOSE_VERSION='1.22.0'
curl -L https://github.com/docker/compose/releases/download/${DOCKER_COMPOSE_VERSION}/docker-compose-`uname -s`-`uname -m` > docker-compose
chmod +x docker-compose
sudo mv docker-compose /usr/local/bin

# Set a reasonable memory limit for PHP.
echo "memory_limit=2G" >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini

# Add PHP extensions.
pecl channel-update pecl.php.net
yes "" | pecl install yaml
