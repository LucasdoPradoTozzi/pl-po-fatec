# Laravel Docker Starter

A Laravel 12 (PHP 8.4) starter template with Docker, featuring PostgreSQL, Redis, RabbitMQ, and Nginx. This is a base setup ready for you to start building your application.

## Operations Research Lab (Livewire)

This project now includes an educational Operations Research web app at `/dashboard` with:

- Natural language problem input
- AI-generated LP model (JSON schema validated)
- Editable mathematical model form
- Academic mode (step-by-step simplex)
- Industrial mode (FastAPI + OR-Tools)
- Modal-based results without losing page state

### Core Architecture

- `app/Livewire/Operations/Lab.php`: orchestrates screen state and UX flow
- `app/Actions/Operations/GenerateLinearModelFromDescription.php`: AI generation use case
- `app/Services/AI/*`: LLM client + safe parser
- `app/Services/Solvers/Academic/*`: educational simplex engine + limits
- `app/Services/Solvers/Industrial/OrToolsClient.php`: HTTP client to FastAPI
- `app/DTO/LinearProblem/*`: model DTOs
- `app/Support/LinearProblemSchemaValidator.php`: strict schema validation
- `microservices/ortools/*`: industrial solver microservice

### State Invalidation Rules

When any problem field changes, the app automatically:

- invalidates previous results
- clears academic and industrial cache
- resets simplex step history navigation
- closes active result modal

### LLM Environment

Set these variables in `.env`:

- `GITHUBAI_API_KEY`
- `GITHUBAI_ENDPOINT`
- `GITHUBAI_TIMEOUT`
- `LLM_PRIMARY_MODEL`
- retry/circuit/quota `LLM_*` variables (already documented in `.env.example`)

### OR-Tools Microservice

The `ortools-api` service is included in `docker-compose.yml` and exposes:

- `POST /solve`
- `GET /health`

Laravel calls `ORTOOLS_SOLVER_ENDPOINT` (default: `http://ortools-api:8001/solve`).

## Tech Stack

- **Laravel** - PHP Framework
- **Docker** - Containerization
- **PostgreSQL** - Database
- **Redis** - Cache & Session Storage
- **RabbitMQ** - Message Queue
- **Nginx** - Web Server
- **PHP-FPM** - PHP Process Manager

## Prerequisites

- Docker
- Docker Compose

## Installation

1. Clone the repository
```bash
git clone git@github.com:LucasdoPradoTozzi/docker-laravel.git
cd docker-laravel
```

2. Copy the environment file
```bash
cp .env.example .env
```

3. Build and start the Docker containers
```bash
docker-compose up --build -d
```

4. Install PHP dependencies
```bash
docker exec laravelapp-php composer install
```

5. Generate application key
```bash
docker exec laravelapp-php php artisan key:generate
```

6. Run database migrations
```bash
docker exec laravelapp-php php artisan migrate
```

7. Create storage symlink
```bash
docker exec laravelapp-php php artisan storage:link
```

8. Install Node.js dependencies
```bash
docker exec laravelapp-php npm install
```

9. Build frontend assets
```bash
docker exec laravelapp-php npm run build
```

## Access the Application

- **Application**: http://localhost:8000
- **RabbitMQ Management**: http://localhost:15672 (guest/guest)

## Available Commands

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker-compose logs -f

# Access PHP container
docker exec -it laravelapp-php sh

# Run artisan commands
docker exec laravelapp-php php artisan <command>

# Run composer commands
docker exec laravelapp-php composer <command>
```

## Configuration

Make sure to update the following in your `.env` file:

- `DB_HOST=postgres` (use service name, not 127.0.0.1)
- `REDIS_HOST=redis`
- `RABBITMQ_HOST=rabbitmq`

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
