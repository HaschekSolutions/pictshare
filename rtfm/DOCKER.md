# Docker
The fastest way to deploy PictShare is via the [official Docker repo](https://hub.docker.com/r/hascheksolutions/pictshare/)
- [Source code & more examples](https://github.com/HaschekSolutions/PictShare-Docker)

```bash
docker run -d -p 80:80 -e "TITLE=My own PictShare" hascheksolutions/pictshare
```

[![Docker setup](http://www.pictshare.net/b65dea2117.gif)](https://www.pictshare.net/8a1dec0973.mp4)

### Docker Compose With Prebuild Image by hascheksolutions

Run container by docker-compose:
- First, install docker compose:
[Docker official docs](https://docs.docker.com/compose/install/)
- Pull docker-compose file:
```bash
wget https://raw.githubusercontent.com/chrisiaut/pictshare/master/docker-compose.yml
```
- Edit docker-compose file:
```bash
vi docker-compose.yml
```
- Run container by docker-compose:
```bash
docker-compose up
```

By using this compose file, you should know that:
- Will make a directory "volumes" in the same directory where compose file is.
- Change `AUTOUPDATE` to false from true by defalt.
- And...it is highly recommended to build your own image.