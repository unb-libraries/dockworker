#!/usr/bin/env bash
set -e

# Docker-compose
DOCKER_COMPOSE_VERSION='1.22.0'
curl -L https://github.com/docker/compose/releases/download/${DOCKER_COMPOSE_VERSION}/docker-compose-`uname -s`-`uname -m` > docker-compose
chmod +x docker-compose
sudo mv docker-compose /usr/local/bin
