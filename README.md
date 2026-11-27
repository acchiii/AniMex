# AniMex

Premium Anime Streaming Platform built with Laravel 11.

## Features

- Browse and search anime library
- Episode streaming with multiple video sources
- User authentication and profiles
- Watch history and continue watching
- Favorites and watchlists
- Rating and commenting system
- Genre, studio, and schedule browsing
- Admin dashboard
- Ad management system
- Premium subscriptions

## Requirements

- PHP >= 8.2 with pdo_pgsql
- Composer
- Node.js & NPM
- Neon PostgreSQL (mandatory)

## Installation

```bash
# Clone the repository
git clone <repository-url>
cd AniMex

# Install PHP dependencies
composer install

# Install JS dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Set DB_HOST, DB_PASSWORD, etc. in .env to your Neon credentials
# (DB_CONNECTION is fixed to pgsql in config/database.php)

# Run migrations and seed sample data
php artisan migrate:fresh --seed

# Build assets
npm run build

# Start the development server
php artisan serve
```

Visit `http://127.0.0.1:8000`

## Project Structure

```
├── app/
│   ├── Http/Controllers/   # Controllers
│   └── Models/             # Eloquent models
├── database/
│   ├── migrations/         # Database migrations
│   └── seeders/            # Seed sample data
├── resources/
│   └── views/              # Blade templates
├── routes/
│   └── web.php             # Web routes
└── public/                 # Public assets
```


## License

MIT
