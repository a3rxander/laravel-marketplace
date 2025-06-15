.PHONY: help build up down restart logs shell composer artisan test pint

# Default target
help:
	@echo "Available commands:"
	@echo "  make setup     - Initial project setup"
	@echo "  make build     - Build Docker containers"
	@echo "  make up        - Start all services"
	@echo "  make down      - Stop all services"
	@echo "  make restart   - Restart all services"
	@echo "  make logs      - Show container logs"
	@echo "  make shell     - Enter app container shell"
	@echo "  make composer  - Run composer install"
	@echo "  make artisan   - Run artisan commands (make artisan COMMAND='migrate')"
	@echo "  make test      - Run tests"
	@echo "  make pint      - Run code style fixer"
	@echo "  make fresh     - Fresh installation"
	@echo "  make horizon   - Monitor Horizon dashboard"

# Initial setup
setup: build composer-install artisan-setup

# Build containers
build:
	docker-compose build --no-cache

# Start services
up:
	docker-compose up -d

# Stop services
down:
	docker-compose down

# Restart services
restart: down up

# Show logs
logs:
	docker-compose logs -f

# Enter container shell
shell:
	docker-compose exec app bash

# Install composer dependencies
composer-install:
	docker-compose exec app composer install --optimize-autoloader

# Update composer dependencies
composer-update:
	docker-compose exec app composer update

# Run artisan commands
artisan:
	docker-compose exec app php artisan $(COMMAND)

# Initial Laravel setup
artisan-setup:
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan storage:link
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache

# Database setup
db-setup:
	docker-compose exec app php artisan migrate:fresh --seed
	docker-compose exec app php artisan passport:install
	docker-compose exec app php artisan scout:import "App\Models\Product"

# Run tests
test:
	docker-compose exec app php artisan test

# Run Pest tests
pest:
	docker-compose exec app ./vendor/bin/pest

# Run code style fixer
pint:
	docker-compose exec app ./vendor/bin/pint

# Fresh installation
fresh: down up composer-install artisan-setup db-setup

# Monitor Horizon
horizon:
	@echo "Horizon dashboard available at: http://localhost:8000/admin/horizon"

# Clear all caches
clear-cache:
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

# Generate IDE helper files
ide-helper:
	docker-compose exec app php artisan ide-helper:generate
	docker-compose exec app php artisan ide-helper:meta
	docker-compose exec app php artisan ide-helper:models --nowrite

# Backup database
backup:
	docker-compose exec mysql mysqldump -u marketplace -psecret marketplace > backup-$(shell date +%Y%m%d_%H%M%S).sql

# Production deployment
deploy:
	@echo "Deploying to production..."
	git pull origin main
	docker-compose -f docker-compose.prod.yml up -d --build
	docker-compose exec app php artisan migrate --force
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache
	docker-compose exec app php artisan queue:restart

# Load testing with JMeter
load-test:
	@echo "Running load tests..."
	jmeter -n -t jmeter/marketplace-load-test.jmx -l results/load-test-results.jtl

# Development helpers
dev-up: up
	@echo "Development environment started"
	@echo "Application: http://localhost:8000"
	@echo "Mailhog: http://localhost:8025"
	@echo "Horizon: http://localhost:8000/admin/horizon"

# Check container status
status:
	docker-compose ps

# Clean up everything
clean:
	docker-compose down -v --remove-orphans
	docker system prune -f