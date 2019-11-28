# Moodle & Collabora Docker Configuration

A local test setup works with a local running [Moodle installation](https://download.moodle.org/) and a [collabora/code Docker image](https://www.collaboraoffice.com/code/docker/) if:

 * https is enabled for both (you have to accept the local ssl certificate in your browser) &
 * https is disabled for both.

If one setup has https enabled and the other not, it will not work.
In the configuration file of the Apache server for the ssl-site (e.g.: /etc/apache2/sites-enabled/default-ssl.conf) you have to add:

```
AllowEncodedSlashes On
```

then please restart the apache server with:

```
systemctl restart apache2.service
```

## Example settings for a local test setup

### Alternative 1: configure the local Moodle installation with a Apache server *via https*

add in the Moodle config file ~/moodle/config.php

```
wwwroot: https://my-local-ipaddress/moodle
```

and in the plugin settings at `Site administration ► Plugins ► Activity modules ► Collaborative document` add:

```
url: https://localhost:9980/
```

start the local Collabora Online Server (CODE) installation with https via docker:

```
docker run -t -d -p 127.0.0.1:9980:9980 -p [::1]:9980:9980 -e 'domain=my\\.-local\\.-ip\\.address' -e "username=admin" -e "password=secret" --restart always --cap-add MKNOD --name=code collabora/code
{code}
```

(note that starting the docker container for the first time will take a while! `docker run` returns quickly, but you will not be able to actually use it for the next 5 minutes or so -- look at  `docker logs -f code` for details ;)

### Alternative 2: configure the local Moodle installation with a Apache server *via http*

add in the Moodle config file ~/moodle/config.php

```
wwwroot: http://my-local-ipaddress/moodle
```

and in the plugin settings at `Site administration ► Plugins ► Activity modules ► Collaborative document` add:

```
url: http://localhost:9980/
```

start the local Collabora Online Server (CODE) installation with http via docker:

```
docker run -t -d -p 127.0.0.1:9980:9980 -p [::1]:9980:9980 -e 'domain=my\\.-local\\.-ip\\.address' -e "username=admin" -e "password=secret" --restart always --cap-add MKNOD -e "extra_params=--o:ssl.enable=false" --name=code collabora/code
```
(note that starting the docker container for the first time will take a while! `docker run` returns quickly, but you will not be able to actually use it for the next 5 minutes or so -- look at  `docker logs -f code` for details)

Please note: replace the string `my-local-ipaddress` with your local IP address! Each `.` of the address must be accompanied by the double-backslash `\\`, thus avoiding misinterpretations.

# How to run collabora and moodle in docker containers

This guide describes how to run collabora and moodle in docker containers.

## Setup collabora container

```
pull collabora image
docker pull collabora/code
```

start collabora container:

```
docker run -t -d -p 0.0.0.0:9980:9980 -e 'domain=my\\.ip\\.address' -e "extra_params=-o:ssl.enable=false" -e "username=admin" -e "password=secret" --restart always --cap-add MKNOD --name=collabora_http collabora/code
```

## Setup moodle container

Pull "Docker Containers for Moodle"
```
git pull https://github.com/moodlehq/moodle-docker.git
cd moodle-docker
```

(Have a look at the [Quick start](https://github.com/moodlehq/moodle-docker/blob/master/README.md) for additional reference)

Set up path to Moodle code:
```
export MOODLE_DOCKER_WWWROOT=/path/to/moodle/code
```

Choose a db server (Currently supported: pgsql, mariadb, mysql, mssql, oracle):
```
export MOODLE_DOCKER_DB=pgsql
```

Set host:
```
export MOODLE_DOCKER_WEB_HOST=my.ip.address
```

Ensure customized config.php for the Docker containers is in place:
```
cp config.docker-template.php $MOODLE_DOCKER_WWWROOT/config.php
```

Start up containers:
```
bin/moodle-docker-compose up -d
```

Stop containers:
```
bin/moodle-docker-compose stop
```

Restart containers:
```
bin/moodle-docker-compose start
```

## Admin settings in moodle
Navigate to: Site administration ► Plugins ► Activity modules ► Collaborative document
 and set Collabora URL:
 
 ```
 url: http://my.ip.address:9980/
 ```
