init: down build up composer-install

up:
	docker-compose up -d

build:
	docker-compose build --pull

down:
	docker-compose down --remove-orphans

app-cli:
	docker-compose exec app-cli bash

composer-install:
	docker-compose exec app-cli composer install
