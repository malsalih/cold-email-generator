# ColdForge AI — Cold Email & Warming System v5.0

> **Premium AI-Powered Solution** — High-precision cold email generation, advanced campaign scheduling, and industrial-grade email warming.

## 🌟 Key Features

-   **🌍 Fully Multilingual**: Native support for **English** and **Arabic** (RTL/LTR) across the entire interface.
-   **🌓 Dynamic Theming**: Premium **Light and Dark mode** support using a semantic CSS design system.
-   **🚀 Advanced Campaigns**: Smart campaign management featuring **Zoho Send Later** integration for professional delivery.
-   **🤖 Gemini AI Integration**: State-of-the-art cold email generation and automated follow-up creation.
-   **🔥 Industrial Warming**: Gradual, safety-first email warming with real-time Puppeteer bot monitoring.
-   **💎 Premium UI/UX**: Modern glassmorphism design with fluid animations and responsive layouts.

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────┐
│                  Laravel (PHP 8.3)                   │
│  Dashboard ← Controllers ← Models ← Database (SQLite)│
│     ↕ API                                            │
│  Warming Bot (Node.js/Puppeteer)                     │
│     ↕ POST/GET                                       │
│  Gemini AI / ML Service (Python/Flask)               │
│     • Spam Filter (joblib model)                     │
│     • AI Generation (Google Gemini)                  │
└─────────────────────────────────────────────────────┘
```

## 🚀 Quick Start

```bash
# 1. Install dependencies
composer install
npm install
cd warming_bot && npm install && cd ..
pip install flask joblib scikit-learn markovify unidecode

# 2. Setup database & Environment
cp .env.example .env
php artisan key:generate
php artisan migrate

# 3. Start all services
start_servers.bat
```

## 📂 Features Overview

### 📧 Email Warming System
1. **Account Management**: Add Zoho Mail accounts and login via secure Puppeteer browser.
2. **Dynamic Strategies**: 30-day gradual warming plans (2 → 25+ emails/day).
3. **Live Monitor**: Watch the bot work in real-time with detailed event logs.
4. **Safety Guards**: Manual/Auto modes, failure limits, and daily send caps.

### 📈 Marketing Campaigns
- **Send Later Integration**: Campaigns leverage Zoho's "Schedule Send" to bypass aggressive filters.
- **Load Balancing**: Distribute campaign emails across multiple warming accounts.
- **Timeline Tracking**: Visual progress for sent, pending, and failed deliveries.
- **Smart Follow-Ups**: 1-click AI generation for follow-up sequences.

### 🤖 AI Email Generator
- **Personalization**: Bulk generate emails for different domains/targets in one go.
- **Tone Control**: Choose from Professional, Friendly, Curious, and more.
- **Anti-Spam Shield**: Real-time filtering for 50+ spam-trigger words.

## 📋 Commands

```bash
# Generate warming templates locally
php artisan warming:generate-templates 10

# Reset daily counters (runs automatically via scheduler)
php artisan warming:reset-daily
```

## 🔐 System Requirements
- PHP 8.3+
- Node.js 18+
- Python 3.10+
- Zoho Mail Account (for warming/sending)

---
*Built with ❤️ for precision-focused cold outreach.*
