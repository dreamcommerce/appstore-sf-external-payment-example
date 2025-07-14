#!/bin/bash

set -e

# Symfony AppStore Dev Environment

echo "== Symfony AppStore Dev Environment =="

# 1. Copy .env if not exists
if [ ! -f .env ]; then
  echo "Copying .env.example to .env"
  cp .env.example .env
fi

# 2. Install dependencies
if [ ! -d "vendor" ]; then
  echo "Installing Composer dependencies..."
  composer install
else
  echo "Updating Composer dependencies..."
  composer update
fi

# 3. Start Docker if .docker/docker-compose.yaml exists
if [ -f "../.docker/docker-compose.yaml" ]; then
  echo "Starting Docker containers..."
  docker compose -f ../.docker/docker-compose.yaml up -d
  echo "App running at http://localhost:8080 (Docker)"
else
  echo ".docker/docker-compose.yaml not found. Please start your environment manually."
  exit 1
fi

# 4. Check if PHP container is running before running migrations
if ! docker compose -f ../.docker/docker-compose.yaml ps --format '{{.Names}}' | grep -q "php"; then
  echo "PHP container is not running. Please check your Docker setup."
  exit 1
fi

echo "== Done! =="
