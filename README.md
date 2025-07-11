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
- `/app-store/view/payment-details` – endpoint for viewing details of a specific payment in their own iframe

## Extending

The codebase is ready for further extension with new event types, API integrations, and payment features.

## Requirements

- PHP 8.2+
- Symfony 7+
- Composer

> **Note:** You do NOT need to install PHP or Composer locally if you use the `dev.sh` script or Docker Compose. These are only required for manual setup or if you want to run the app without Docker.

## Installation

You can set up the project in two ways:

### Option 1: Quick start for developers (recommended)

1. Clone the repository and enter the app directory:

```bash
git clone <repo-url>
cd appstore-sf-external-payment-example/app
```

2. Then use the provided `dev.sh` script to automate the entire setup (environment, dependencies, Docker, migrations):

```bash
chmod +x ./dev.sh  # only once, if needed
./dev.sh
```

3. The application will be available at http://localhost:8080.

---

### Option 2: Manual setup with Docker Compose

1. Clone the repository and enter the app directory:

```bash
git clone <repo-url>
cd appstore-sf-external-payment-example/app
```

2. Install dependencies:
   ```bash
   composer install
   ```
3. Copy the `.env.example` file to `.env` and set the required environment variables:
   ```bash
   cp .env.example .env
   # Edit the .env file and set your secrets and database connection
   ```
4. Start the application:
   ```bash
   docker compose -f .docker/docker-compose.yaml up -d
   ```
5. The application will be available at http://localhost:8080.

---

###  Configure in Shoper AppTools

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
