#!/bin/bash

set -e

# Symfony AppStore Dev Environment

echo "== Symfony AppStore Dev Environment =="

# 1. Copy .env if not exists
if [ ! -f .env ]; then
  echo "Copying .env.example to .env"
  cp .env.example .env
fi

# 2. Setting path to .env file
ENV_FILE="../app/.env"
if [ ! -f "$ENV_FILE" ]; then
  echo "The $ENV_FILE file does not exist Copy .env.example to .env."
  exit 1
fi

# 3. Start Docker if .docker/docker-compose.yaml exists
if [ -f "../.docker/docker-compose.yaml" ]; then
  echo "Starting Docker containers..."
  docker compose --env-file $ENV_FILE -f ../.docker/docker-compose.yaml up -d
  echo "App running at http://localhost:8080 (Docker)"
else
  echo ".docker/docker-compose.yaml not found. Please start your environment manually."
  exit 1
fi

echo "== Done! =="
