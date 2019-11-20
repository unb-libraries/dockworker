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

## Author / Licensing
- Developed by [![UNB Libraries](https://github.com/unb-libraries/assets/raw/master/unblibbadge.png "UNB Libraries")](https://lib.unb.ca/)
- This work is published through our strong commitment to making as much of our development/workflow as possible freely available.
- Consequently, the contents of this repository [unb-libraries/dockworker] are licensed under the [MIT License](http://opensource.org/licenses/mit-license.html). This license explicitly excludes:
   - Any website content, which remains the exclusive property of its author(s).
   - The UNB logo and any of the associated suite of visual identity assets, which remains the exclusive property of the University of New Brunswick.
