# Symfony App Store Integration

A Symfony-based application for integrating with the Shoper platform using iframe authentication.

## Overview

This application provides integration with the Shoper AppStore, handling:

- App installation and uninstallation events
- Secure iframe authentication
- App interface rendering within the Shoper admin panel

## Architecture

### Authentication Flow

https://developers.shoper.pl/developers/

The application uses a custom authentication system for iframe integration:

1. Shoper sends requests with shop ID, timestamp, and hash parameters
2. `IframeAuthentication` authenticator validates the hash using `HashValidator`
3. If valid, the user is authenticated with the shop ID

### AppStore Events

The application handles lifecycle events:

- **Install**: Stores authorization code for API access
- **Uninstall**: Cleans up application data

### Routes

- `/app-store/view/hello-world` - Main application interface loaded in Shoper iframe which displays a view for the merchant
- `/app-store/event` - Endpoint for installation/uninstallation events (webhooks)

## Security

- SHA-512 HMAC hash verification with request parameters
- Secure user session management
- Access control for all application endpoints


- **IframeAuthentication**: A Symfony authenticator that:
    - Validates incoming requests from Shoper iframes
    - Extracts shop ID and hash parameters
    - Verifies request authenticity using HMAC SHA-512
    - Creates user authentication successful validation

- **HashValidator**: Handles security verification by:
    - Sorting parameters alphabetically by key
    - Generating a SHA-512 HMAC hash using the app store secret
    - Comparing the generated hash with the one provided in the request

- **AppstoreUser**: Simple user implementation that:
    - Stores the shop identifier
    - Implements Symfony's UserInterface
    - Provides minimal required user functionality

- **AppstoreUserProvider**: User management service that:
    - Creates AppstoreUser instances based on shop identifiers
    - Could be extended to load users from a persistence layer

The application uses Symfony's security system with a custom authenticator chain:

- Configured in `config/packages/security.yaml`
- No standard login form - authentication happens via iframe parameters
- All view endpoints are protected by default, requiring valid Shoper authentication

## Installation

### Prerequisites

- PHP 8.4+
- Composer
- Docker and Docker Compose

### Setup

1. Clone the repository
2. Install dependencies:
   ```bash
   cd app && composer install
   ```

3. Copy `.env.example` and set required configuration:
   ```
   APP_STORE_SECRET=your_secret_key
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

