# License API

A Laravel-based REST API for managing mining licenses, clients, renewals, and reports for Motimose Metals.

## Table of Contents

- [Technologies Used](#technologies-used)
- [System Requirements](#system-requirements)
- [Features](#features)
- [Local Development Setup](#local-development-setup)
- [Configuration](#configuration)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Project Structure](#project-structure)

## Technologies Used

### Backend Framework & Core
- **Laravel 10.x** - PHP web application framework
- **PHP 8.1+** - Programming language
- **Laravel Sanctum 3.2** - API authentication system for SPA and mobile apps
- **Laravel Tinker** - Interactive REPL for Laravel

### Database
- **MySQL** - Primary database (configurable to PostgreSQL, SQLite, or SQL Server)
- **Eloquent ORM** - Laravel's database abstraction layer

### Authorization & Permissions
- **Laratrust 8.2** - Role-based permissions and authorization package

### Email & Notifications
- **Laravel Mail** - Email sending (SMTP, Mailgun, SES, Postmark supported)
- **Queued Jobs** - Asynchronous email sending via Laravel queues
  - 2FA codes
  - Email confirmations
  - Password reset emails

### Real-time & Broadcasting
- **Pusher PHP Server 7.2** - WebSocket broadcasting support
- **Laravel Broadcasting** - Event broadcasting system

### Frontend Build Tools
- **Vite 4.0** - Modern frontend build tool and development server
- **Laravel Vite Plugin** - Laravel integration for Vite
- **Axios** - HTTP client for API requests

### Testing
- **PHPUnit 10.1** - PHP testing framework
- **Faker** - Fake data generator for testing
- **Mockery** - Mocking framework for unit tests

### Development Tools
- **Laravel Pint** - Opinionated PHP code style fixer
- **Laravel Sail** - Docker-powered local development environment
- **Collision** - Beautiful error reporting for console
- **Spatie Laravel Ignition** - Beautiful error page for Laravel

### HTTP Client
- **Guzzle 7.2** - PHP HTTP client for external API requests

## System Requirements

- PHP >= 8.1
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- Node.js 16+ and npm (for frontend assets)
- Web server (Apache/Nginx) or use Laravel's built-in server

## Features

- **User Authentication & Authorization**
  - User registration with email confirmation
  - Login with two-factor authentication (2FA)
  - Password reset functionality
  - Role-based access control (RBAC)
  - Permission management

- **License Management**
  - Create, read, update, and delete mining licenses
  - License type categorization
  - Mineral association
  - Client tracking

- **Client Management**
  - Client registration
  - Subsidiary management
  - Geographic data (States, Local Government Areas)

- **Renewals & Reports**
  - License renewal tracking
  - Report submissions
  - File uploads for reports

- **Audit Trail**
  - Event logging and audit trails
  - Activity monitoring

## Local Development Setup

### 1. Clone the Repository

```bash
git clone <repository-url>
cd License-api
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node Dependencies

```bash
npm install
```

### 4. Environment Configuration

Create a `.env` file from the example:

```bash
cp .env.example .env
```

Generate an application key:

```bash
php artisan key:generate
```

### 5. Database Setup

Configure your database credentials in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=license_api
DB_USERNAME=root
DB_PASSWORD=your_password
```

Create the database:

```bash
mysql -u root -p -e "CREATE DATABASE license_api;"
```

Run migrations and seeders:

```bash
php artisan migrate --seed
```

This will create all necessary tables and seed the database with:
- Default roles and permissions
- Nigerian states
- Local Government Areas (LGAs)

### 6. Start the Development Server

**Backend:**
```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

**Frontend Assets (if needed):**
```bash
npm run dev
```

### 7. Queue Worker (Optional)

For processing queued jobs (emails, notifications):

```bash
php artisan queue:work
```

## Configuration

### Mail Configuration

Configure email settings in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@motimosemetals.com
MAIL_FROM_NAME="${APP_NAME}"
```

For local development, consider using [Mailpit](https://github.com/axllent/mailpit) or [Mailtrap](https://mailtrap.io/).

### Broadcasting (Optional)

For real-time features using Pusher:

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1
```

### Queue Configuration

For production, configure a queue driver:

```env
QUEUE_CONNECTION=database
# or use redis, sqs, beanstalkd
```

## API Documentation

### Base URL

```
http://localhost:8000/api
```

### Authentication Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/auth/register` | User registration | No |
| POST | `/auth/login` | User login | No |
| POST | `/auth/logout` | User logout | Yes |
| GET | `/auth/confirm-registration` | Confirm email registration | No |
| POST | `/auth/recover-password` | Request password reset | No |
| POST | `/auth/reset-password` | Reset password with token | No |
| PUT | `/auth/sent-2fa-code/{user}` | Send 2FA code | No |
| PUT | `/auth/confirm-2fa-code/{user}` | Verify 2FA code | No |
| GET | `/auth/user` | Get authenticated user | Yes |

### Authentication

The API uses Laravel Sanctum for authentication. After login, include the bearer token in subsequent requests:

```
Authorization: Bearer {your-token}
```

### Resource Endpoints

Protected endpoints for managing:
- **Users** - User management
- **Licenses** - License operations
- **Clients** - Client management
- **Reports** - Report submissions

## Testing

Run the test suite:

```bash
php artisan test
```

Or using PHPUnit directly:

```bash
vendor/bin/phpunit
```

Run specific test suites:

```bash
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

## Project Structure

```
├── app/
│   ├── Console/         # Artisan commands
│   ├── Events/          # Event classes
│   ├── Exceptions/      # Exception handlers
│   ├── Http/
│   │   ├── Controllers/ # API controllers
│   │   ├── Middleware/  # HTTP middleware
│   │   └── Resources/   # API resources
│   ├── Jobs/            # Queued jobs
│   ├── Listeners/       # Event listeners
│   ├── Mail/            # Email classes
│   ├── Models/          # Eloquent models
│   ├── Notifications/   # Notification classes
│   └── Providers/       # Service providers
├── config/              # Configuration files
├── database/
│   ├── factories/       # Model factories
│   ├── migrations/      # Database migrations
│   └── seeders/         # Database seeders
├── public/              # Web server document root
├── resources/
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   └── views/           # Blade templates
├── routes/
│   ├── api.php          # API routes
│   ├── web.php          # Web routes
│   ├── channels.php     # Broadcasting channels
│   └── console.php      # Console commands
├── storage/             # Application storage
└── tests/               # Automated tests
    ├── Feature/         # Feature tests
    └── Unit/            # Unit tests
```

## License

This project is proprietary software for Motimose Metals.
