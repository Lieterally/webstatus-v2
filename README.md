# Webstatus V2

Website monitoring system for Institut Teknologi Kalimantan (ITK). Performs automated HTTP health checks on institutional websites at configurable intervals, displays real-time status on a web dashboard, and sends Telegram notifications when outages are detected.

## Features

- Automated monitoring cycles with configurable interval (5–1440 minutes)
- Concurrent HTTP checks with configurable timeouts and concurrency
- Real-time dashboard with live countdown, charts, and status cards
- Live checking log drawer showing per-page results in real-time
- Telegram bot notifications (consolidated down alerts, recovery alerts)
- Per-site and bulk refresh with async background processing
- Role-based access (Admin, Super Admin)
- Site detail view with response time and downtime charts
- Searchable site filtering with Tom Select
- System self-health monitoring (alerts on consecutive cycle failures)

## Tech Stack

- **Backend**: Laravel 13, PHP 8.3+
- **Frontend**: Blade, Livewire, Alpine.js, Tailwind CSS v4, DaisyUI
- **Charts**: Chart.js
- **Select UI**: Tom Select
- **Database**: SQLite (dev) / MySQL (production)
- **Notifications**: Telegram Bot API

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+
- SQLite or MySQL

## Setup

```bash
# Install PHP dependencies
composer install

# Install JS dependencies and build assets
npm install
npm run build

# Copy environment file and configure
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations and seed data
php artisan migrate --seed

# Serve the application
php artisan serve
```

## Configuration

Edit `.env` for:

```env
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite

TELEGRAM_BOT_TOKEN=your-bot-token-here
```

Health check settings (connection timeout, response timeout, concurrency) are configurable from the System Config page in the admin panel.

## Running the Monitoring Cycle

### Local Development (Windows/Laragon)

The dashboard auto-triggers cycles via Livewire polling — just open the dashboard. Or run manually:

```bash
php artisan app:run-monitoring-cycle
```

Or use the scheduler daemon:

```bash
php artisan schedule:daemon
```

### Production (Linux)

Add a single cron entry:

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Run the queue worker as a service:

```bash
php artisan queue:work
```

## Telegram Bot Commands

| Command | Description |
|---------|-------------|
| `/start` | Welcome message |
| `/help` | List commands |
| `/chat_id` | Get your chat ID |
| `/recepient` | Register as notification recipient |
| `/subscribe` | Activate notifications |
| `/unsubscribe` | Deactivate notifications |
| `/down` | List currently down sites |
| `/refresh` | Trigger manual refresh |

## Default Credentials

After seeding:

- **Super Admin**: `super_admin` / `password`

## License

Proprietary — Institut Teknologi Kalimantan.
