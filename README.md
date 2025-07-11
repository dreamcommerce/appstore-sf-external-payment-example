# Symfony App Store Integration

Symfony application for integrating with the Shoper platform, enabling external payment handling and managing the application lifecycle in the Shoper AppStore.

> **Based on**: https://github.com/dreamcommerce/appstore-sf-mvc-example

## Purpose

This application is designed to integrate external payments into Shoper stores. After installation, it allows creating new payments and managing payment settings via the Shoper admin panel.

## Features

- Handles installation and uninstallation events (AppStore Events)
- Secure iframe authentication (hash, shopId, timestamp)
- Creation and configuration of external payments for the store
- User interface loaded in the Shoper admin panel (iframe)

## Architecture

### Authentication Flow

https://developers.shoper.pl/developers/

The application uses a custom authentication system for iframe integration:

1. Shoper sends requests with shop ID, timestamp, and hash parameters
2. `IframeAuthentication` verifies the hash using `HashValidator`
3. Upon successful verification, the user is authenticated based on the shop ID

### AppStore Events

The application handles lifecycle events:

- **Install**: stores authorization code for API, creates a shop entry in the database, and initiates payment integration
- **Uninstall**: removes application data related to the shop
- **Other events**: can be extended with additional webhooks

### Routes

- `/app-store/view/hello-world` – main application interface loaded in the Shoper admin panel iframe
- `/app-store/event` – endpoint for handling installation/uninstallation events (AppStore webhooks)

## Extending

The codebase is ready for further extension with new event types, API integrations, and payment features.

## Requirements

- PHP 8.2+
- Symfony 6+
- Composer

## Installation

### 1. Clone the repository

```bash
git clone <repo-url>
cd appstore-sf-external-payment-example/app
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

Copy the `.env.example` file to `.env` and set the required environment variables:

```bash
cp .env.example .env
# Edit the .env file and set:
# APPSTORE_APP_SECRET=your_appstore_secret_key
# APP_CLIENT=your_client_key
# APP_SECRET=your_secret_key
# DATABASE_URL="mysql://user:pass@localhost:3306/dbname"
# MESSENGER_TRANSPORT_DSN=doctrine://default
```

Configure the database connection in the `.env` file as well.

### 4. Run the application (Docker)

```bash
docker compose -f .docker/docker-compose.yaml up -d
```

The application will be available at http://localhost:8080.


### 5. Configure in Shoper AppTools

Configure the application in Shoper AppTools and set the URLs to your local or production environment.

---

### Local development with admin panel

If you want to see the view in your devshop then you have to expose your local machine to the internet using ngrok or cloudflare tunnel.
Example configuration for cloudflare tunnel and linux
```shell
  curl -L --output cloudflared.deb \
  https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
```
```shell
 sudo dpkg -i cloudflared.deb
```

```shell
 sudo cloudflared tunnel run --token <token>
```

#### Exposing your local environment with ngrok

If you want to expose your local Symfony app to the internet (e.g. for Shoper AppTools integration), you can use [ngrok](https://ngrok.com/):

```bash
# Download and install ngrok (if you don't have it)
# For Linux:
wget https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-linux-amd64.zip
unzip ngrok-stable-linux-amd64.zip
sudo mv ngrok /usr/local/bin

# For Mac (Homebrew):
brew install ngrok/ngrok/ngrok

# Start ngrok tunnel for your local app (default Docker port 8080)
ngrok http 8080
```

After running ngrok, you will get a public HTTPS URL (e.g. `https://abc123.ngrok.io`).  
Use this URL in Shoper AppTools as your app's endpoint.

## Development environment

For local development and onboarding, you can use the provided `dev.sh` script. It automates environment setup, dependency installation, database migrations, and Docker startup. Simply run:

```bash
./app/dev.sh
```

This will ensure your environment is ready and the application is available at http://localhost:8080 (via Docker).

## Usage: Development vs Production/VM

### Local development (recommended for contributors)
- By default, the `docker-compose.yaml` mounts the entire `app/` directory (including `vendor/`) as a volume into the PHP container.
- This allows you to run `composer install` locally (e.g. via `./app/dev.sh`), so your IDE and tools have full access to dependencies.
- Any changes to dependencies (e.g. `composer require`) are immediately reflected in the container.

### Production/VM/CI
- For production, VM, or CI environments, **remove or comment out the `volumes:` line** in the PHP service in `.docker/docker-compose.yaml`:
  ```yaml
    # volumes:
    #   - ../app:/var/www/html
  ```
- This ensures the container uses dependencies installed during the Docker image build (`composer install` in the Dockerfile), making the image self-contained and portable.
- You do not need PHP or Composer on the host machine.

---

## Note on wait-for-it.sh and database readiness

- The `wait-for-it.sh` script is used only in the `messenger-worker` container to ensure that the worker waits for the database to be ready before starting message consumption.
- The main PHP application container does not use `wait-for-it.sh` and starts immediately, as it does not require an immediate database connection on startup.
- This setup prevents connection errors in the worker and speeds up the main application startup.
