# ⚡ ColdForge — Domain Sales Engine & ML Spam Defense

A full-stack Laravel web application that generates highly optimized, **anti-spam cold emails** using the **Google Gemini API**, and rigorously verifies them using a **custom Python Machine Learning Microservice**. Built with a premium dark UI, adaptive ML scoring, and full email history tracking.

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)
![Gemini](https://img.shields.io/badge/Gemini_API-2.0_Flash-4285F4?style=flat-square&logo=google&logoColor=white)
![Python](https://img.shields.io/badge/Python-Flask_ML-3776AB?style=flat-square&logo=python&logoColor=white)
![Scikit-Learn](https://img.shields.io/badge/Scikit--Learn-Model-F7931E?style=flat-square&logo=scikit-learn&logoColor=white)

---

## ✨ Features

### 🛡️ ML-Powered Spam Defense & Correction
- **Local AI Classification**: A `MultinomialNB` model (trained on 136k emails) analyzes Gemini's drafts and assigns a Spam Probability score (0-100%).
- **Feature-Weighted Dynamic Correction**: If the score exceeds 70%, the Flask microservice analyzes which *specific words* contributed most to the spam classification and automatically replaces them with grammatically correct B2B synonyms (e.g., `guaranteed` → `well-positioned`, `purchase` → `explore`).
- **Gmail RETVec Protection**: Uses a 5-layer pipeline to fix excessive punctuation (`!!!` → `.`), kill URL shorteners, correct SHOUTING caps, and repair broken merge tags before the email is finalized.
- **Protected Vocabulary**: Core business terminology (`domain`, `company`, `acquire`, `strategy`) is strictly protected from deletion to preserve message integrity.

### 🎯 Domain Sales & Variant Tracking
- **Multi-Variant Generation**: Generate 1, 5, 10, or 20 distinct permutations of an email in a single click.
- **Diff Comparison**: The UI visualizes the Before/After Spam Score (e.g., `Gemini 94% ➔ Fixed 32%`) and lets you toggle a "View Original" diff to see exactly what the ML engine altered.

### 📊 History & Testing
- Every generated email is saved with full metadata (Was Spam?, Before Score, After Score).
- Copy-to-Clipboard integration for immediate sending.

### 🎨 Premium Dark UI
- Built with **Tailwind CSS 4** + **Alpine.js**.
- Custom probability meters and status badges (ML Verified vs ML Corrected).
- Glassmorphism effects with ambient gradient glows.

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend API** | Laravel 13 (PHP 8.2+) |
| **Frontend** | Blade Templates + Tailwind CSS 4 + Alpine.js |
| **ML Microservice** | Python 3 + Flask + Scikit-Learn |
| **AI Generation**| Google Gemini API (gemini-2.0-flash) |
| **Database** | SQLite (default) / MySQL |

---

## 🚀 Quick Setup

### Prerequisites

Make sure you have installed:
- **PHP 8.2+** with Composer
- **Node.js 18+** with npm
- **Python 3.10+** (For the ML microservice)

### Step 1: Clone the Repository

```bash
git clone https://github.com/malsalih/cold-email-generator.git
cd cold-email-generator
```

### Step 2: Setup Laravel (PHP/Node)

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Open `.env` and add your Gemini API key:
```env
GEMINI_API_KEY=your-api-key-here
GEMINI_MODEL=gemini-2.0-flash
```

### Step 3: Setup ML Microservice (Python)

The core spam classification logic runs on a dedicated Flask port.

```bash
cd ml_service
python -m venv .venv

# Windows
.\.venv\Scripts\activate
# Mac/Linux
source .venv/bin/activate

pip install flask scikit-learn pandas requests joblib
```

---

## 🚦 Starting the Services

You need **three terminal windows** to run the complete stack:

**Terminal 1 — Vite (Frontend Assets):**
```bash
npm run dev
```

**Terminal 2 — Laravel Server:**
```bash
php artisan serve
```

**Terminal 3 — ML Microservice:**
```bash
cd ml_service
.\.venv\Scripts\activate
python app.py
```
*(The Flask server must be running on port 5000 for Laravel to perform spam corrections).*

Navigate to: **http://127.0.0.1:8000**

---

## 📝 How It Works

1. **User Prompt**: You input a Target Domain and instructions.
2. **LLM Generation**: Laravel sends an anti-spam engineered prompt to the Gemini API, requesting multiple unique variants.
3. **ML Interception**: Laravel catches Gemini's response and forwards the drafts to the complete `http://127.0.0.1:5000/correct` Flask endpoint.
4. **Scoring & Rewriting**: The Python script transforms text into TF-IDF vectors, runs it through the Naive Bayes model, scores the spam probability, and structurally rewrites problematic words if the score exceeds 70%.
5. **UI Delivery**: The final, sanitized emails are saved to the database and presented in the UI alongside visual meters indicating how much intervention was required.

---

## 📁 Project Structure

```
cold-email-generator/
├── app/
│   ├── Http/Controllers/
│   │   └── EmailGeneratorController.php   # Handles Form/Max Variants logic
│   └── Services/
│       └── GeminiService.php              # Communicates with Gemini API & Python API
├── ml_service/
│   ├── app.py                             # Flask ML API (Correction Pipeline)
│   ├── spam_model.pkl                     # Pre-trained Scikit-learn MultinomialNB Model
│   ├── vectorizer.pkl                     # TF-IDF Vector Vocabulary
│   └── spam_dataset.csv                   # Curated Ham/Spam Training Data
├── resources/
│   └── views/generator/
│       ├── result.blade.php               # Result UI with Diff/Score features
│       └── index.blade.php                # Master Generator Form
└── README.md
```
