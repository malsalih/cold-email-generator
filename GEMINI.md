# Project Context: ColdForge AI

ColdForge AI is a high-precision, AI-powered cold email generation and industrial-grade email warming system. The architecture is a multi-service stack comprising a Laravel backend (PHP 8.3), a Node.js/Puppeteer warming bot, and a Python/Flask service for ML-based spam classification and Gemini-powered email generation.

## 🏗️ Architecture
- **Backend**: Laravel (PHP 8.3) - Handles business logic, dashboard, models, and campaign management.
- **Warming Bot**: Node.js/Puppeteer - Automates email interaction and warming processes in Zoho Mail.
- **AI/ML Service**: Python/Flask - Provides email generation using Google Gemini and spam classification models.

## 🚀 Key Commands
- **Install Dependencies**: 
  - `composer install`, `npm install`, `cd warming_bot && npm install`
  - `pip install flask joblib scikit-learn markovify unidecode`
- **Application Startup**: Run `start_servers.bat` to initiate the system.
- **Warming System**: 
  - `php artisan warming:generate-templates [count]`
  - `php artisan warming:reset-daily`

## 📂 Project Structure
- `app/`: Laravel application logic (Commands, Http, Models, Services).
- `database/`: Migrations and seeders.
- `ml_service/`: Python-based AI generation and spam classification service.
- `resources/`: Frontend assets (Blade views, CSS, JS).
- `routes/`: Web and console route definitions.
- `warming_bot/`: Node.js/Puppeteer automation for email warming.

## 🛠️ Development Conventions
- **Language**: PHP 8.3 (Laravel), JavaScript/Node.js, Python 3.10+.
- **Database**: SQLite (managed via Laravel Migrations).
- **Styling**: Vanilla CSS with a focus on modern, responsive UI/UX (Glassmorphism).
- **Localization**: Full support for English and Arabic (RTL/LTR).
- **Communication**: Inter-service communication via local APIs.

---
*Refer to README.md for comprehensive setup and feature details.*
