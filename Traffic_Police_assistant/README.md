# Traffic Police Assistance S2

## Team Members
- tasnim dakak
- maryam almasri

## Project Description
A system enhanced with AI for managing traffic violations. It enables officers to record violations using voice input, automatically extract license plate numbers from images, and generate structured reports. The system improves accuracy, reduces manual work, and streamlines violation and appeal management.

---

## System Map / خريطة النظام

### Structure / الهيكلية
- `Triffic_Police_assistant`
  - Laravel PHP web application for police managers, admin, and citizen workflows.
  - Contains web UI, API routes, database seeders, and business logic.
- `django_ai_service`
  - Django backend for AI services: heatmap generation, OCR, STT, and worker execution.
  - Provides data extraction, event processing, and asynchronous job workers.
- `POLICEapp`
  - Flutter mobile application for cross-platform police/citizen client usage.
  - Contains mobile UI and native integration logic.

### Workflow / سير البيانات
1. Users interact with the mobile app or web application.
2. The Laravel app handles authentication, violation records, and UI flows.
3. The AI backend processes images, voice data, and heatmap jobs.
4. Results are stored and displayed through the web/mobile interfaces.

---

## Requirements / متطلبات التنفيذ

### Backend / خادم بايثون
- Python 3.11 or later
- virtualenv or venv
- `pip install -r django_ai_service/requirements.txt`
- SQL databases such as MySQL, PostgreSQL, or SQLite

### Laravel / PHP
- PHP 8.x
- Composer
- Web server such as Apache or Nginx (optional)
- MySQL or MariaDB

### Flutter / Mobile
- Flutter SDK
- Android SDK and/or Xcode based on the target platform
- `flutter pub get`

---

## Setup Steps / خطوات التنفيذ

### 1. Clone the repository
```bash
git clone https://github.com/simo75-t/traffic_police_assistance_S2.git
cd "TPA -2-"
```

### 2. Confirm branch
```bash
git checkout main
```

### 3. Prepare `django_ai_service`
```bash
cd django_ai_service
python -m venv .venv
.\.venv\Scripts\activate
pip install -r requirements.txt
```

Set local values in `.env`.

### 4. Prepare `Triffic_Police_assistant`
```bash
cd ..\Triffic_Police_assistant
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Update database and connection settings in `.env`.

### 5. Prepare `POLICEapp`
```bash
cd ..\POLICEapp
flutter pub get
```

Run the app with `flutter run` or open it in your Flutter editor.

### 6. Run background services
- Heatmap worker:
```bash
cd ..\django_ai_service
.\.venv\Scripts\activate
python manage.py run_heatmap_worker
```
- OCR worker:
```bash
python manage.py run_ocr_worker
```
- STT worker:
```bash
python manage.py run_stt_worker
```
