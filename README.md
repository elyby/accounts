# Accounts Ely.by

Ely.by's accounts management service, which provides a single entry point for our own services, as well as for external 
projects via the OAuth2 protocol. It also implements authorization server for Minecraft game servers and partially 
implements Mojang-compatible API for obtaining accounts' information.

**Warning**: this project is not intended for use outside of the ecosystem of Ely.by's services.

## Development

The project is designed to work in a Docker environment. Installation links:
- [Docker](https://docs.docker.com/install/)
- [docker-compose](https://docs.docker.com/compose/install/)

First you need to [fork](https://help.github.com/en/github/getting-started-with-github/fork-a-repo) the repository and 
[clone](https://help.github.com/en/github/creating-cloning-and-archiving-repositories/cloning-a-repository) it.
After that you need to create local `.env` and `docker-compose.yml` files:

```sh
cp .env.dist .env
cp docker-compose.dist.yml docker-compose.yml
```

The copied files can be adjusted for your local environment, but generally they are ready to use without any
intervention.

Containers will not install dependencies automatically, so you have to install them manually. If you have `php` and 
`composer` installed on your system, you can install dependencies with the `composer install` command. You can also
install dependencies using the container:

```sh
docker-compose run --rm --no-deps app composer install
```

At the first start, all base images will be loaded and built (it can take a long time for the application's image),
after which you will be able to begin the development.

To start all containers, use the following command:

```sh
docker-compose up -d
```

By default, `docker-compose.yml` specifies `80` port for the `web` service and `8080` port for `phpMyAdmin`. If these
services emits ports-related errors, it is necessary to make the required ports available or change them, and then run
the `docker-compose up -d` command again.

### User interface

This repository contains only the source code for the backend API while the interface is in the
[separate repository](https://github.com/elyby/accounts-frontend). Linux and Mac users can use the following script
to get the latest version of the UI:

```bash
curl -s https://api.github.com/repos/elyby/accounts-frontend/releases/latest \
 | grep "browser_download_url.*tar.gz" \
 | cut -d : -f 2,3 \
 | tr -d \" \
 | xargs curl -sLo /tmp/accounts-frontend.tar.gz \
&& rm -rf frontend \
&& mkdir -p frontend \
&& tar -zxf /tmp/accounts-frontend.tar.gz -C frontend \
&& rm -f /tmp/accounts-frontend.tar.gz
```

If this script didn't work for you, you can manually go to the
[releases page](https://github.com/elyby/accounts-frontend/releases), download the suitable `build`-archive and extract
all its contents into the `frontend` folder.

### How to enter into a working container

Using the [`docker-compose exec`](https://docs.docker.com/compose/reference/exec/) command you can easily execute any
desired command in a running container by using its service's name from the `docker-compose.yml` file. For example,
to enter the shell of the `app` container, use the following command:

```
docker-compose exec app bash
```

# License

This library is released under the [Apache 2.0](LICENSE).
