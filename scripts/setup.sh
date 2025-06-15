#!/bin/bash

# Marketplace Setup Script
echo "🚀 Setting up Marketplace Multi-vendor Platform..."

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Create src directory if it doesn't exist
if [ ! -d "src" ]; then
    echo "📁 Creating Laravel project..."
    composer create-project laravel/laravel src
    cd src
else
    echo "📁 Laravel project already exists"
    cd src
fi

# Copy environment file
if [ ! -f ".env" ]; then
    echo "📝 Setting up environment configuration..."
    cp ../.env.docker .env
fi

# Install additional dependencies
echo "📦 Installing additional dependencies..."
composer require laravel/horizon laravel/passport laravel/scout spatie/laravel-permission jeroen-g/explorer

# Go back to root directory
cd ..

# Build and start containers
echo "🐳 Building Docker containers..."
docker-compose build

echo "🚀 Starting services..."
docker-compose up -d

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 30

# Setup Laravel
echo "🔧 Setting up Laravel..."
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan storage:link

# Run migrations and seeders
echo "🗃️ Setting up database..."
docker-compose exec app php artisan migrate:fresh --seed

# Install Passport
echo "🔐 Setting up Laravel Passport..."
docker-compose exec app php artisan passport:install

# Setup Elasticsearch indices
echo "🔍 Setting up search indices..."
docker-compose exec app php artisan scout:import "App\Models\Product"

# Cache optimization
echo "⚡ Optimizing caches..."
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

echo ""
echo "✅ Setup completed successfully!"
echo ""
echo "🌐 Your marketplace is now available at:"
echo "   Application: http://localhost:8000"
echo "   Admin Panel: http://localhost:8000/admin"
echo "   Horizon: http://localhost:8000/admin/horizon"
echo "   Mailhog: http://localhost:8025"
echo ""
echo "📚 Useful commands:"
echo "   make logs      - View container logs"
echo "   make shell     - Enter application container"
echo "   make test      - Run tests"
echo "   make down      - Stop all services"
echo ""
echo "🔑 Default credentials:"
echo "   Admin: admin@marketplace.com / password"
echo "   Vendor: vendor@example.com / password"
echo "   Customer: customer@example.com / password"
echo ""

# Make the script executable
chmod +x scripts/setup.sh