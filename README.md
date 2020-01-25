# Accounts Ely.by

Сервис управления Аккаунтами Ely.by, предоставляющий единую точку входа для наших и внешних сервисов по протоколу
OAuth2, а также реализующий сервер авторизации для серверов Minecraft и частично реализующий совместимое с API Mojang
для получения информации об аккаунтах.

**Предупреждение**: этот проект не предназначен для использования вне экосистемы сервисов Ely.by.

## Разработка

Проект ориентирован на работу в Docker окружении, так что для полноценной работы проекта запускать его следует именно в
окружении Docker-контейнера. Ссылки на установки:
- [Docker](https://docs.docker.com/install/)
- [docker-compose](https://docs.docker.com/compose/install/)

Далее необходимо создать форк репозитория, а после клонировать его:

```sh
git clone git@github.com:<your_username>/accounts.git
cd accounts
```

Затем необходимо создать локальные файлы `.env` и `docker-compose.yml`:

```sh
cp .env.dist .env
cp docker-compose.dist.yml docker-compose.yml
```

Скопированные файлы можно изменить под условия локальной среды разработки, но в общем случае они пригодны для
использования без вашего вмешательства.

Контейнеры не умеют автоматически устанавливать зависимости, так что их нужно установить самостоятельно. Если у вас в
системе установлен `php` и `composer`, то можно установить зависимости через команду `composer install`. Вы также всегда
можете установить зависимости с помощью контейнера:

```sh
docker-compose run --rm --no-deps app composer install
```

При первом запуске произойдёт процесс загрузки и построения необходимых образов, после чего все контейнеры начнут
свою работу, и вы сможете приступить к разработке.

Для запуска всех контейнеров, используйте следующую команду:

```sh
docker-compose up -d
```

По умолчанию, в `docker-compose.yml` указан `80` порт для самого сервиса, а также `8080` порт для подключения
к phpMyAdmin. Если сервисы `web` и `phpmyadmin` выбросят ошибку, связанную с занятостью портов, то необходимо или
освободить необходимые порты (`80` и `8080`), или же изменить их, после чего заново выполнить команду
`docker-compose up -d`.

### Пользовательский интерфейс

Этот репозиторий содержит в себе только код для API бекенда, в то время как интерфейс находится в
[соседнем репозитории](https://github.com/elyby/accounts-frontend). Пользователи Linux и Mac могут использовать
следующий скрипт, чтобы получить последнюю версию пользовательского интерфейса:

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

Если этот скрипт не сработал для вас, то вы можете самостоятельно перейти на
[страницу релизов](https://github.com/elyby/accounts-frontend/releases), скачать подходящий `build`-архив и
разархивировать всё его содержимое в папку `frontend`. 

### Как войти в работающий контейнер

Начиная с версии `docker-compose` 1.9.0, была добавлена команда `docker-compose exec`, которая позволяет выполнить
на работающем контейнере произвольную команду, основываясь на имени сервиса в compose-файле. Так, например, чтобы
войти в shell контейнера `app`, используйте следующую команду:

```
docker-compose exec app bash
```
