# ColdForge AI — Cold Email & Warming System v4.0

> **100% Offline** AI-powered cold email generation and email warming system with safety-first bot architecture.

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────┐
│                  Laravel (PHP 8.3)                   │
│  Dashboard ← Controllers ← Models ← Database (SQLite)│
│     ↕ API                                            │
│  Warming Bot (Node.js/Puppeteer)                     │
│     ↕ POST/GET                                       │
│  ML Service (Python/Flask)                           │
│     • Spam Filter (joblib model)                     │
│     • Text Generator (Markovify chains)              │
└─────────────────────────────────────────────────────┘
```

## 🚀 Quick Start

```bash
# 1. Install dependencies
composer install
npm install
cd warming_bot && npm install && cd ..
pip install flask joblib scikit-learn markovify unidecode

# 2. Setup database
cp .env.example .env
php artisan key:generate
php artisan migrate

# 3. Start all services
start_servers.bat
```

## 📂 Project Structure

```
cold-email-generator/
├── app/
│   ├── Console/Commands/
│   │   └── GenerateWarmingTemplates.php  # Local ML template generator
│   ├── Http/Controllers/
│   │   ├── WarmingController.php         # Dashboard, accounts, daily rounds
│   │   ├── WarmingApiController.php      # Bot API (jobs, reports, monitoring)
│   │   ├── CampaignController.php        # Campaign management
│   │   └── EmailGeneratorController.php  # Cold email generator
│   └── Models/
│       ├── WarmingAccount.php    # Email accounts with session tracking
│       ├── WarmingLog.php        # Send logs (pending→processing→sent/failed)
│       ├── WarmingTemplate.php   # AI-generated email templates (single-use)
│       ├── WarmingStrategy.php   # Gradual warm-up schedules
│       ├── WarmingRecipient.php  # Saved warming recipients
│       ├── WarmingSetting.php    # System settings (send_mode, etc.)
│       └── BotLog.php           # Real-time bot activity tracking
├── warming_bot/
│   └── bot.js                   # Puppeteer bot v4.0 (safety-first)
├── ml_service/
│   ├── app.py                   # Flask API (spam filter + text generator)
│   ├── spam_dataset.csv         # Training data (HAM/SPAM emails)
│   └── model/                   # Trained joblib model files
├── resources/views/warming/
│   ├── dashboard.blade.php      # Main dashboard with live bot monitor
│   ├── accounts.blade.php       # Account management
│   ├── templates.blade.php      # Template management
│   ├── strategies.blade.php     # Strategy configuration
│   ├── logs.blade.php           # Send log viewer
│   └── settings.blade.php       # System settings
└── start_servers.bat            # One-click startup script
```

## 🔥 Email Warming System

### How It Works

1. **Add Accounts** — Add your Zoho Mail accounts and login via browser
2. **Save Recipients** — Store warming target emails for reuse
3. **Start Daily Round** — One click to schedule today's warming emails
4. **Bot Executes** — Puppeteer bot opens Zoho Mail and fills compose
5. **You Review** — In manual mode, you click Send for each email
6. **Track Progress** — Real-time monitoring on the dashboard

### Warming Strategy

The default strategy gradually increases sends over 30 days:

| Days | Daily Sends |
|------|-------------|
| 1-3  | 2           |
| 4-7  | 5           |
| 8-14 | 10          |
| 15-21| 15          |
| 22-30| 20          |
| 31+  | 25          |

### Safety Guards

| Guard | Description |
|-------|-------------|
| **Manual Mode** | Bot fills fields but never clicks Send without you |
| **Field Verification** | Verifies all fields are filled before proceeding |
| **Job Verification** | Double-checks with server before every send |
| **Processing Lock** | Jobs marked `processing` can't be picked twice |
| **Stale Recovery** | Stuck jobs auto-reset after 10 minutes |
| **Failure Limit** | 5 consecutive failures → emergency stop |
| **Session Limit** | Max 50 emails per bot session |
| **Daily Limit** | Max 100 emails per account per day |
| **Duplicate Prevention** | Can't schedule same recipient twice if pending |

## 🤖 ML Service (Port 5050)

### Endpoints

| Method | Path | Description |
|--------|------|-------------|
| POST | `/predict` | Check if text is spam (returns probability) |
| POST | `/generate` | Generate clean email text (pre-filtered) |

### How Generation Works

1. Builds Markov chain model from HAM emails in `spam_dataset.csv`
2. Generates random subject + body using the chain
3. Checks spam probability using the trained classifier
4. Only returns text with spam probability ≤ 50%
5. Retries up to 30 times internally for clean output

## 📡 Bot Live Monitor

The dashboard shows real-time bot status:
- **Online indicator** (green/gray dot with pulse animation)
- **Session stats** (sent count, failed count)
- **Detailed log** button showing every bot action
- **Auto-refresh** every 5 seconds via AJAX

## 🛠️ API Endpoints

### Bot API (`/api/warming/`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/next-job` | Get next pending job (marks as processing) |
| POST | `/report` | Report send result (sent/failed) |
| GET | `/verify-job/{id}` | Verify job validity before send |
| POST | `/bot-log` | Push real-time bot event |
| GET | `/bot-status` | Get bot status for dashboard |
| GET | `/settings` | Get current send mode |
| POST | `/mark-logged-in` | Mark account as logged in |

## ⚙️ Send Modes

| Mode | Behavior |
|------|----------|
| `auto` | Bot fills fields and clicks Send automatically |
| `manual_send` | Bot fills fields, waits for you to click Send |
| `full_manual` | Bot opens compose only, you do everything |

> **Note:** Quick Start and Daily Round always use `manual_send` for safety.

## 📋 Commands

```bash
# Generate warming templates locally
php artisan warming:generate-templates 10

# Reset daily counters (runs automatically via scheduler)
php artisan warming:reset-daily
```

## 🔐 Environment

No external API keys required for warming. The system is 100% offline.

```env
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=sqlite
```
