# Chimera Fox Platform Mark2
Chimera Fox is a universal framework for quickly creating web applications. The back is written in PHP, and provides basic functions. Front on JS. Interaction via REST.

# How to run in docker
```
version: "2"

networks:
  interlink:

services:

fox-web-mk2:
  restart: always
  image: mxfox/chimera-mk2-basic:lastest
  container_name: fox-web-mk2
  volumes:
   - ./fox-log-web/logs:/var/log/apache2
   - ./fox-log-cron/logs:/var/log/fox

  networks:
   - interlink

  environment:
   - "FOX_SQLSERVER=XXXXXX"
   - "FOX_SQLUSER=XXXXX"
   - "FOX_SQLPASSWD=XXXX"
   - "FOX_SQLDB=XXXXX"
   - "FOX_CACHEHOST=memcached"
   - "FOX_TITLE=Mark2"
   - "FOX_SITEPREFIX=https://mark2.fox.local"
   - "FOX_MASTERSECRET=SuperSecretPassword"
   - "FOX_INIT_PASSWORD=AnotherSuperSecretPassword"
   ```
