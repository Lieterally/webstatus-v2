# Webstatus V2

Website monitoring system for Institut Teknologi Kalimantan (ITK). Performs automated HTTP health checks on institutional websites at configurable intervals, displays real-time status on a web dashboard, and sends Telegram notifications when outages are detected.

## Features

- Automated monitoring cycles with configurable interval (5–1440 minutes)
- Concurrent HTTP checks with configurable timeouts and concurrency (batch-based)
- Automatic retry of down sites within each cycle to reduce false positives
- Real-time dashboard with live countdown, charts, and status cards (card + table views)
- Live checking log drawer showing per-page results in real-time during cycles
- Telegram bot notifications (consolidated down alerts, recovery alerts, system health alerts)
- Per-site and bulk refresh with async background processing (non-blocking `popen`)
- Role-based access (Admin, Super Admin) with configurable session timeout
- Site detail view with response time and downtime charts (7 time filters: 1D/3D/7D/1M/3M/6M/1Y)
- Overview charts with per-site filtering via Tom Select
- Category management (CRUD) for organizing monitored sites
- Downtime history log showing outage windows per site (last 30 days)
- System self-health monitoring (alerts on 3 consecutive cycle failures)
- Pagination and search/filter on dashboard site list

## Tech Stack

- **Backend**: Laravel 13, PHP 8.3+
- **Frontend**: Blade, Livewire 4, Alpine.js, Tailwind CSS v4, DaisyUI
- **Icons**: Font Awesome Pro (all variants)
- **Charts**: Chart.js
- **Select UI**: Tom Select
- **Database**: SQLite (dev) / MySQL (production)
- **Notifications**: Telegram Bot API
- **Testing**: Pest PHP 4 (unit, feature, property-based tests)
- **Dev Tools**: Laravel Telescope, Laravel Pail, Laravel Pint

## Requirements

- PHP 8.3+
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

Or use the all-in-one dev script (serves app, queue, logs, and Vite concurrently):

```bash
composer dev
```

## Configuration

Edit `.env` for:

```env
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite

TELEGRAM_BOT_TOKEN=your-bot-token-here
TELEGRAM_WEBHOOK_SECRET=your-random-secret
```

### System Config (Admin Panel)

Health check and notification settings are configurable from the System Config page (Super Admin only):

| Setting | Default (seeder) | Range |
|---------|------------------|-------|
| Cycle Interval | 10 minutes | 5–1440 min |
| Notification Cycle Threshold | 6 cycles | 1–100 |
| False Positive Threshold | 3 cycles | Fixed |
| Session Timeout | 30 minutes | 5–480 min |
| Connection Timeout | 10 seconds | 1–60 sec |
| Response Timeout | 25 seconds | 5–120 sec |
| Concurrency Limit | 30 requests | 5–100 |

## Running the Monitoring Cycle

### Local Development (Windows/Laragon)

The dashboard auto-triggers cycles via Livewire polling — just open the dashboard. When the countdown reaches 0, a background process is spawned automatically via `popen` (no cron needed).

Or run manually:

```bash
php artisan app:run-monitoring-cycle
```

Or use the scheduler daemon:

```bash
php artisan schedule:daemon
```

### Production (Linux VPS)

See the [VPS Deployment Guide](#vps-deployment-guide) section below.

## Telegram Bot Commands

| Command | Description |
|---------|-------------|
| `/start` | Welcome message with bot info and command list |
| `/help` | List all available commands |
| `/chat_id` | Get your Telegram chat ID |
| `/recepient` | Self-register as notification recipient |
| `/subscribe` | Activate notifications |
| `/unsubscribe` | Deactivate notifications |
| `/down` | List currently down sites |
| `/refresh` | Trigger manual refresh cycle |

## Default Credentials

After seeding:

- **Super Admin**: `super_admin` / `password`

## Running Tests

```bash
php artisan test
```

Tests include unit tests, feature tests, and property-based tests covering all correctness properties.

## License

Proprietary — Institut Teknologi Kalimantan.

---

## VPS Deployment Guide

Complete guide for deploying Webstatus to a Debian/Ubuntu VPS.

### 1. Server Requirements

Install the required stack:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install nginx mysql-server supervisor unzip redis-server -y
```

#### PHP 8.4 (via Sury PPA)

```bash
sudo apt install software-properties-common -y
curl -sSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb
sudo dpkg -i /tmp/debsuryorg-archive-keyring.deb
sudo sh -c 'echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ bookworm main" > /etc/apt/sources.list.d/php.list'
sudo apt update
sudo apt install php8.4-fpm php8.4-cli php8.4-mbstring php8.4-xml php8.4-curl php8.4-mysql php8.4-zip php8.4-bcmath php8.4-gd php8.4-redis -y
```

#### Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### Node.js 20

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs -y
```

### 2. Create Database

```bash
sudo mysql -u root
```

```sql
CREATE DATABASE webstatus;
CREATE USER 'webstatus'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON webstatus.* TO 'webstatus'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

> If `CREATE USER` fails with "Operation CREATE USER failed", the user already exists. Use `ALTER USER` instead:
> ```sql
> ALTER USER 'webstatus'@'localhost' IDENTIFIED BY 'your_strong_password';
> ```

### 3. Deploy Code

```bash
cd /var/www
sudo git clone <your-repo-url> webstatus
sudo chown -R www-data:www-data webstatus
cd webstatus
```

#### Set up SSH key for GitHub (recommended)

```bash
ssh-keygen -t ed25519 -C "webstatus-vps"
cat ~/.ssh/id_ed25519.pub
```

Add the public key to GitHub → Settings → SSH keys, then:

```bash
git remote set-url origin git@github.com:yourusername/webstatus-v2.git
```

### 4. Install Dependencies & Build

```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### 5. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with production values:

```env
APP_NAME="Webstatus"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://webstatus.itk.ac.id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=webstatus
DB_USERNAME=webstatus
DB_PASSWORD=your_strong_password

TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_WEBHOOK_SECRET=your_random_secret

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

> Generate a webhook secret: `php -r "echo bin2hex(random_bytes(32));"`

### 6. Run Migrations & Seed

```bash
php artisan migrate --force
php artisan db:seed --force
```

### 7. Set Permissions

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 8. Nginx Configuration

Create `/etc/nginx/sites-available/webstatus`:

```nginx
# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name webstatus.itk.ac.id;
    return 301 https://$host$request_uri;
}

# HTTPS server
server {
    listen 443 ssl;
    server_name webstatus.itk.ac.id;
    root /var/www/webstatus/public;

    ssl_certificate /etc/nginx/ssl/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### SSL Certificate Setup

If you have existing certificates (e.g., from `/certs/`):

```bash
sudo mkdir -p /etc/nginx/ssl
cat /certs/ServerCertificate-2026.crt /certs/CAIntermediate-2026.crt > /etc/nginx/ssl/fullchain.pem
cp /certs/itk.ac.id-2026.key /etc/nginx/ssl/privkey.pem
```

Or use Let's Encrypt:

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d webstatus.itk.ac.id
```

#### Enable and start Nginx

```bash
sudo ln -s /etc/nginx/sites-available/webstatus /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl enable nginx
sudo systemctl start nginx
```

### 9. Queue Worker (Supervisor)

Create `/etc/supervisor/conf.d/webstatus-worker.conf`:

```ini
[program:webstatus-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/webstatus/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/webstatus/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start webstatus-worker:*
```

### 10. Scheduler (Cron)

```bash
sudo crontab -u www-data -e
```

Add:

```
* * * * * cd /var/www/webstatus && php artisan schedule:run >> /dev/null 2>&1
```

### 11. Telegram Webhook

If your domain has a **public DNS record** (resolvable from the internet):

```bash
php artisan app:set-telegram-webhook
```

If your VPS is behind NAT or DNS is internal-only, use **long polling** instead. Create `/etc/supervisor/conf.d/webstatus-telegram.conf`:

```ini
[program:webstatus-telegram]
command=php /var/www/webstatus/artisan telegram:poll
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/webstatus/storage/logs/telegram-poll.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start webstatus-telegram:*
```

### 12. Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 13. DNS Configuration

Add a public DNS A record (via Cloudflare or your DNS provider):

```
Type: A
Name: webstatus
Content: <your-vps-public-ip>
Proxy: DNS only (gray cloud)
TTL: Auto
```

Verify: `dig @8.8.8.8 webstatus.itk.ac.id`

---

### Redeployment

Pull latest changes and rebuild:

```bash
cd /var/www/webstatus
git pull origin main
composer install --optimize-autoloader --no-dev
npm install
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
sudo supervisorctl restart webstatus-worker:*
```

### Restart Services

Restart all app-related services:

```bash
sudo systemctl restart nginx
sudo systemctl restart php8.4-fpm
sudo systemctl restart supervisor
sudo systemctl restart redis
sudo systemctl restart mysql
```

Full system reboot:

```bash
sudo reboot
```
