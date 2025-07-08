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
- `/app-store/view/payments-configuration` – payment configuration list view for all created payments from current application
- `/app-store/view/payment-details/{paymentId}` – endpoint for viewing details of a specific payment in their own iframe

## Extending

The codebase is ready for further extension with new event types, API integrations, and payment features.

## Requirements

- PHP 8.2+
- Symfony 6+
- Composer

## Installation

1. Clone the repository
2. Install dependencies: `bash cd app && composer install`
3. Configure the database connection in the `.env` file
4. Run migrations: `php bin/console doctrine:migrations:migrate`
5. Configure the application in the Shoper AppTools and point application URLs to your local or production environment

## Environment variables

6. Copy `.env.example` and set other required configuration:
   ```
    APPSTORE_APP_SECRET=your_appstore_secret_key
    APP_CLIENT=your_client_key
    APP_SECRET=your_secret_key
   ```

### Docker Setup

Build and start the Docker containers:

```bash
 docker compose -f .docker/docker-compose.yaml up
```

The application will be available at http://localhost:8080

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
---
