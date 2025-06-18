.PHONY: help build up down restart logs shell composer artisan test

# Default goal
.DEFAULT_GOAL := help

# Variables
DOCKER_COMPOSE = docker-compose
PHP_CONTAINER = marketplace_php
NGINX_CONTAINER = marketplace_nginx
MYSQL_CONTAINER = marketplace_mysql

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

build: ## Build all docker images
	$(DOCKER_COMPOSE) build

up: ## Start all containers in detached mode
	$(DOCKER_COMPOSE) up -d

down: ## Stop and remove all containers
	$(DOCKER_COMPOSE) down

restart: ## Restart all containers
	$(DOCKER_COMPOSE) restart

logs: ## Show logs for all containers
	$(DOCKER_COMPOSE) logs -f

logs-php: ## Show PHP container logs
	$(DOCKER_COMPOSE) logs -f php

logs-horizon: ## Show Horizon container logs
	$(DOCKER_COMPOSE) logs -f horizon

shell: ## Access PHP container shell
	docker exec -it $(PHP_CONTAINER) bash

shell-mysql: ## Access MySQL container shell
	docker exec -it $(MYSQL_CONTAINER) mysql -u${DB_USERNAME:-marketplace} -p${DB_PASSWORD:-secret}

composer: ## Run composer commands (usage: make composer cmd="install")
	docker exec -it $(PHP_CONTAINER) composer $(cmd)

artisan: ## Run artisan commands (usage: make artisan cmd="migrate")
	docker exec -it $(PHP_CONTAINER) php artisan $(cmd)

test: ## Run tests
	docker exec -it $(PHP_CONTAINER) php artisan test

pest: ## Run Pest tests
	docker exec -it $(PHP_CONTAINER) ./vendor/bin/pest

phpunit: ## Run PHPUnit tests
	docker exec -it $(PHP_CONTAINER) ./vendor/bin/phpunit

migrate: ## Run database migrations
	docker exec -it $(PHP_CONTAINER) php artisan migrate

seed: ## Run database seeders
	docker exec -it $(PHP_CONTAINER) php artisan db:seed

fresh: ## Fresh migration with seeders
	docker exec -it $(PHP_CONTAINER) php artisan migrate:fresh --seed

horizon: ## Start Horizon
	docker exec -it $(PHP_CONTAINER) php artisan horizon

queue-work: ## Start queue worker
	docker exec -it $(PHP_CONTAINER) php artisan queue:work

scout-import: ## Import all Scout searchable models
	docker exec -it $(PHP_CONTAINER) php artisan scout:import

cache-clear: ## Clear all cache
	docker exec -it $(PHP_CONTAINER) php artisan cache:clear
	docker exec -it $(PHP_CONTAINER) php artisan config:clear
	docker exec -it $(PHP_CONTAINER) php artisan route:clear
	docker exec -it $(PHP_CONTAINER) php artisan view:clear

optimize: ## Optimize application
	docker exec -it $(PHP_CONTAINER) php artisan optimize
	docker exec -it $(PHP_CONTAINER) php artisan config:cache
	docker exec -it $(PHP_CONTAINER) php artisan route:cache

install: ## Initial setup - build, up, composer install, migrations
	$(DOCKER_COMPOSE) build
	$(DOCKER_COMPOSE) up -d
	sleep 10
	docker exec -it $(PHP_CONTAINER) composer install
	docker exec -it $(PHP_CONTAINER) cp .env.example .env
	docker exec -it $(PHP_CONTAINER) php artisan key:generate
	docker exec -it $(PHP_CONTAINER) php artisan migrate
	docker exec -it $(PHP_CONTAINER) php artisan passport:install
	docker exec -it $(PHP_CONTAINER) php artisan storage:link

stop: ## Stop all containers
	$(DOCKER_COMPOSE) stop

start: ## Start all stopped containers
	$(DOCKER_COMPOSE) start

ps: ## List all containers
	$(DOCKER_COMPOSE) ps