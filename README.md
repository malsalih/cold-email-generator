# ⚡ ColdForge — AI Cold Email Generator

A full-stack Laravel web application that generates highly optimized, **anti-spam cold emails** using the **Google Gemini API**. Built with a premium dark UI, comprehensive prompt engineering, and full email history tracking.

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)
![Gemini](https://img.shields.io/badge/Gemini_API-2.0_Flash-4285F4?style=flat-square&logo=google&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php&logoColor=white)

---

## ✨ Features

### 🎯 Target Domain Processing
- Input any company domain (e.g., `eagnt.com`, `oyanx.com`)
- Auto-generates 3–10 professional email prefixes across **7 categories**: executive, general, sales, marketing, support, HR, technical
- Ensures address diversity by always including general category

### 🛡️ Anti-Spam Engine
The system prompt explicitly instructs Gemini to:
- **Block 30+ spam trigger words** (Free, Act Now, 100% Guarantee, $$$, etc.)
- Keep subject lines to **4–8 words**, lowercase, no emojis
- Limit email body to **under 120 words**
- Use **soft, low-friction CTAs** ("Would it make sense to chat for 15 minutes?")
- Output **plain text only** — no HTML, no bold, no images
- Personalize based on the target domain's inferred industry

### 📊 History & A/B Testing
- Every generation is saved with full metadata
- Search by domain, subject, or content
- View the exact prompt sent to Gemini for each email
- Track tokens used and generation time

### 🎨 Premium Dark UI
- Built with **Tailwind CSS 4** + **Alpine.js**
- Glassmorphism effects with ambient gradient glows
- Smooth animations and micro-interactions
- Inter + JetBrains Mono typography
- Fully responsive design

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | Laravel 13 (PHP 8.2+) |
| **Frontend** | Blade Templates + Tailwind CSS 4 + Alpine.js |
| **Database** | SQLite (default) / MySQL / PostgreSQL |
| **AI Engine** | Google Gemini API (gemini-2.0-flash) |
| **Build Tool** | Vite 8 |

---

## 🚀 Quick Setup

### Prerequisites

Make sure you have installed:
- **PHP 8.2+** with extensions: `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `curl`
- **Composer** (PHP dependency manager)
- **Node.js 18+** and **npm**
- **Git**

### Step 1: Clone the Repository

```bash
git clone https://github.com/malsalih/cold-email-generator.git
cd cold-email-generator
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

### Step 3: Install Node Dependencies

```bash
npm install
```

### Step 4: Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### Step 5: Configure Your Gemini API Key

Open the `.env` file and add your Gemini API key:

```env
GEMINI_API_KEY=your-api-key-here
GEMINI_MODEL=gemini-2.0-flash
```

> 🔑 **Get a free Gemini API key** from [Google AI Studio](https://aistudio.google.com/apikey)

### Step 6: Database Setup

**Option A: SQLite (Default — Zero Config)**

The default configuration uses SQLite. Just run:

```bash
php artisan migrate
```

**Option B: MySQL**

Update your `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coldforge
DB_USERNAME=root
DB_PASSWORD=your_password
```

Create the database, then migrate:
```bash
mysql -u root -p -e "CREATE DATABASE coldforge;"
php artisan migrate
```

**Option C: PostgreSQL**

Update your `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=coldforge
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

Then migrate:
```bash
php artisan migrate
```

### Step 7: Start the Application

You need **two terminal windows**:

**Terminal 1 — Vite (Frontend Assets):**
```bash
npm run dev
```

**Terminal 2 — Laravel Server:**
```bash
php artisan serve
```

### Step 8: Open in Browser

Navigate to: **http://127.0.0.1:8000**

---

## 📁 Project Structure

```
cold-email-generator/
├── app/
│   ├── Http/Controllers/
│   │   └── EmailGeneratorController.php   # Main controller (6 actions)
│   ├── Models/
│   │   └── GeneratedEmail.php             # Eloquent model with scopes
│   └── Services/
│       └── GeminiService.php              # Gemini API + anti-spam engine
├── config/
│   └── services.php                       # Gemini config (api_key, model)
├── database/
│   └── migrations/
│       └── ...create_generated_emails...  # Email storage schema
├── resources/
│   ├── css/
│   │   └── app.css                        # Tailwind 4 + custom styles
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php              # Base layout (nav, footer, flash)
│       └── generator/
│           ├── index.blade.php            # Generator form page
│           ├── result.blade.php           # Generated email result
│           ├── history.blade.php          # Email history with search
│           └── show.blade.php             # Single email detail view
├── routes/
│   └── web.php                            # 6 route definitions
├── .env.example                           # Environment template
└── README.md                              # This file
```

---

## 🔌 Routes

| Method | URI | Action | Description |
|--------|-----|--------|-------------|
| `GET` | `/` | `index` | Generator form |
| `POST` | `/generate` | `generate` | Generate email via Gemini |
| `GET` | `/result/{id}` | `result` | View generated email |
| `GET` | `/history` | `history` | Browse all emails |
| `GET` | `/history/{id}` | `show` | View email details |
| `DELETE` | `/history/{id}` | `destroy` | Delete email record |

---

## 🔧 Configuration

All configuration is done via the `.env` file:

| Variable | Default | Description |
|----------|---------|-------------|
| `GEMINI_API_KEY` | *(empty)* | Your Google Gemini API key |
| `GEMINI_MODEL` | `gemini-2.0-flash` | Gemini model to use |
| `DB_CONNECTION` | `sqlite` | Database driver |

---

## 📝 How It Works

1. **User inputs** a target domain and instructions
2. **GeminiService** generates random professional email prefixes for that domain
3. The service constructs a **comprehensive anti-spam system prompt** with 30+ blocked trigger words
4. A **user prompt** is built with domain context, product/service info, and tone preference
5. The Gemini API is called with `responseMimeType: application/json` for structured output
6. The response is parsed and the email (subject + body) is **saved to the database**
7. The user sees the result with **Copy to Clipboard** buttons

---

## 📄 License

This project is open-sourced for personal use.
