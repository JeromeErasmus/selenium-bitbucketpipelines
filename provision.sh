--update && apk update
apk add openrc --no-cache
ifconfig
# Add bash
apk add --no-cache py-pip bash 

# Add docker compose
pip install --no-cache-dir docker-compose
ls -la

# install composer
composer install

# create docker network
docker network create bandhosting

# install docker 
apk update
apk add docker
rc-update add docker boot
DOCKER_HOST=:2375
service docker start

# compose
docker-compose -v
docker-compose up -d

# run the tests
# ./bin/behat -p firefox
