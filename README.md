# TelScout — Job Discovery & Outreach Platform

A production-ready personal job discovery and outreach automation platform. Automatically discovers relevant software engineering jobs, scores them against your profile, generates personalized outreach emails, and sends them through your Gmail account.

## Architecture

```
TelScout/
├── app/
│   ├── Console/Commands/     # Artisan commands (discovery, follow-ups)
│   ├── Http/Controllers/Api/ # REST API controllers
│   ├── Jobs/                 # Queue jobs (send email, discover jobs, score, etc.)
│   ├── Models/               # Eloquent models
│   ├── Policies/             # Authorization policies
│   └── Services/             # Match scoring, AI provider abstraction
├── database/
│   ├── migrations/           # 24 migrations
│   └── seeders/              # Default profile seeder
├── resources/
│   ├── css/app.css           # Tailwind CSS v4
│   ├── js/                   # React + TypeScript SPA
│   │   ├── components/       # Reusable UI components
│   │   ├── hooks/            # React hooks
│   │   ├── lib/              # API client, utilities
│   │   └── pages/            # All page components
│   └── views/app.blade.php   # SPA entry point
├── routes/
│   ├── api.php               # All REST API routes
│   ├── web.php               # Catch-all → React SPA
│   └── console.php           # Scheduler
└── tests/Feature/            # PHPUnit tests
```

**Backend:** Laravel 11, PHP 8.2, MySQL, Laravel Sanctum, Queues, Scheduler  
**Frontend:** React 19, TypeScript, Vite, Tailwind CSS v4, TanStack Query, Recharts  
**Single-folder:** React lives inside `resources/js/` — one project, one server command

## Requirements

- PHP 8.2+
- MySQL 8+ (via XAMPP or standalone)
- Node.js 18+
- Composer 2+
- XAMPP (or equivalent local server)

## Installation

```bash
# 1. Clone / open the project
cd C:\path\to\TelScout

# 2. Install PHP dependencies
composer install

# 3. Install JS dependencies
npm install

# 4. Copy environment file
copy .env.example .env

# 5. Generate app key
php artisan key:generate

# 6. Configure database (see Database Setup below)

# 7. Run migrations + seed default profile
php artisan migrate --seed

# 8. Build frontend assets
npm run build
```

## Environment Configuration

Edit `.env`:

```
APP_NAME=TelScout
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=telscout
DB_USERNAME=root
DB_PASSWORD=          # your XAMPP MySQL password (blank by default)

FRONTEND_URL=http://localhost:5173
```

## Database Setup (XAMPP)

1. Start XAMPP → Start **Apache** and **MySQL**
2. Open `http://localhost/phpmyadmin`
3. Create database: `telscout`
4. Run: `php artisan migrate --seed`

Default login after seeding:
- **Email:** `timileyin@telscout.local`
- **Password:** `password`

## Gmail OAuth Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Create a project → Enable **Gmail API**
3. Create OAuth 2.0 credentials (Web application)
4. Add authorized redirect URI: `http://localhost:8000/api/gmail/callback`
5. Copy Client ID and Secret into `.env`:

```
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/gmail/callback
```

6. In the app: **Settings → Gmail → Connect Gmail Account**

## AI Provider Setup

Currently supports **OpenAI** (gpt-4o-mini by default).

```
AI_PROVIDER=openai
AI_API_KEY=sk-...your-key...
AI_MODEL=gpt-4o-mini
AI_TEMPERATURE=0.7
AI_MAX_TOKENS=1000
```

Get an API key at [platform.openai.com](https://platform.openai.com).  
The AI is used for email generation and job analysis only. API key is never exposed to the frontend.

## Running the Application

### Option 1 — Single command (starts both servers)
```bash
php artisan serve:full
```
- Laravel API → `http://localhost:8000`
- Vite dev server → `http://localhost:5173`
- Open your browser at `http://localhost:5173`

### Option 2 — Separate terminals
```bash
# Terminal 1 — Laravel API
php artisan serve

# Terminal 2 — Vite dev server (hot reload)
npm run dev
```

## Running Queues

Queues handle email sending, job discovery, and AI generation asynchronously.

```bash
php artisan queue:work --queue=default --tries=3
```

For development, use:
```bash
php artisan queue:listen
```

## Running the Scheduler

```bash
# Run once (for testing)
php artisan schedule:run

# Run continuously (development)
php artisan schedule:work
```

Scheduled tasks:
- `jobs:discover` — daily at 08:00 (Africa/Lagos)
- `outreach:process-followups` — every hour

## Running Tests

```bash
php artisan test
```

Or with coverage:
```bash
php artisan test --coverage
```

## Production Deployment

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Run `npm run build` to compile assets
3. Run `php artisan config:cache && php artisan route:cache`
4. Set up a cron job for the scheduler:
   ```
   * * * * * cd /path/to/TelScout && php artisan schedule:run >> /dev/null 2>&1
   ```
5. Run queue workers with a process manager (Supervisor recommended)

## Key Workflow

1. Log in → configure your candidate profile
2. Connect Gmail via OAuth
3. Set job preferences in Settings
4. Run a search (discovers jobs from RemoteOK)
5. Review opportunities with match scores
6. Open an opportunity → generate a personalized email
7. Edit → Approve → Send through Gmail
8. Track status in Applications (Kanban)
9. Follow-ups are scheduled automatically
