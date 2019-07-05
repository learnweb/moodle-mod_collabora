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

*Here are the example settings for a local test setup:*

Alternative 1: configure the local Moodle installation with a Apache server *via https*:

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

Alternative 2: configure the local Moodle installation with a Apache server *via http*:

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

Please note: replace the string `my-local-ipaddress` with your local IP address! Each `.` of the address must be accompanied by the double-backslash `\\`, thus avoiding misinterpretations.
