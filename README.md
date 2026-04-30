# Traffic Police Assistance S2

Integrated traffic management platform composed of three connected applications:

- `Traffic_Police_assistant`: Laravel 12 backend, web dashboards, API, RabbitMQ publisher, and AI result consumer.
- `django_ai_service`: Django-based AI workers for OCR, STT, heatmap analytics, and heatmap prediction.
- `POLICEapp`: Flutter mobile app used by police officers for field operations.

## What The System Does Now

- Police officers log in from the mobile app and manage their profile.
- Officers create traffic violations manually or with OCR and STT assistance.
- The app can generate and preview violation PDFs.
- Police managers review violations, maps, heatmaps, and appeals from the web dashboard.
- Citizen reports can be submitted from the public web flow and assigned to officers.
- Officer live location and dispatch assignments are supported.
- Heatmap analytics can be generated from violation data.
- AI-based heatmap prediction requests can be generated and polled by request ID.

## Repository Layout

```text
.
|-- Traffic_Police_assistant/
|-- django_ai_service/
|-- POLICEapp/
|-- CHAPTER_8_TESTING.md
`-- README.md
```

## High-Level Architecture

1. `POLICEapp` sends authenticated officer requests to the Laravel API.
2. Laravel stores core operational data in MySQL and exposes admin, police manager, and citizen web flows.
3. Laravel publishes OCR, STT, heatmap, and heatmap prediction jobs to RabbitMQ.
4. Django workers consume those jobs, run AI or analytics processing, and publish results back to RabbitMQ.
5. Laravel consumes the result messages through `php artisan ai:consume-results` and updates system state.
6. Web and mobile clients poll or fetch the final results from Laravel.

## Main Components

### Laravel web and API

The `Traffic_Police_assistant` application currently includes:

- Admin authentication and dashboard
- Police manager authentication and dashboard
- Public citizen violation lookup and appeal submission
- Mobile API for police officers
- RabbitMQ-based AI job publishing
- AI results consumer command
- Violation PDF generation
- Heatmap generation and heatmap prediction orchestration

### Django AI service

The `django_ai_service` application currently includes:

- OCR worker
- STT worker
- Heatmap analytics worker
- Heatmap prediction worker
- RabbitMQ consumers and result publishing
- MongoDB storage for OCR-related data

### Flutter mobile app

The `POLICEapp` application currently includes:

- Officer login and profile screens
- Violation creation and search
- OCR and STT request flow
- Dispatch assignments
- Notification and officer presence services
- Violation PDF preview
- Arabic and English localization assets

## Required Software

Install these before running the full system:

- Git
- PHP 8.2+
- Composer
- Node.js 18+ and npm
- Python 3.11+ recommended
- Flutter SDK
- MySQL or MariaDB
- MongoDB
- RabbitMQ

Needed for the current AI flow:

- Ollama for OCR inference
- LM Studio for STT structuring
- A configured LLM provider for heatmap prediction if you want prediction generation to work

## Local Configuration

This repository does not document or commit local `.env` contents, secrets, tokens, or machine-specific service URLs.

- Create the required local configuration files manually on each machine.
- Keep API keys and credentials out of Git history.
- Adjust local backend or device URLs in your private setup before testing.

## Setup And Run

Use PowerShell from the repository root unless noted otherwise.

### 1. Start infrastructure

Make sure these services are running first:

- MySQL or MariaDB
- MongoDB
- RabbitMQ
- Ollama
- LM Studio

### 2. Setup Laravel

```powershell
cd Traffic_Police_assistant
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
```

Run the backend:

```powershell
cd Traffic_Police_assistant
php artisan serve
```

Run the AI result consumer in another terminal:

```powershell
cd Traffic_Police_assistant
php artisan ai:consume-results
```

Run frontend assets during development if you need the web UI:

```powershell
cd Traffic_Police_assistant
npm run dev
```

### 3. Setup Django AI service

Create or use the local virtual environment and install dependencies:

```powershell
cd django_ai_service
python -m venv .venv
.\.venv\Scripts\python.exe -m pip install -r requirements.txt
.\.venv\Scripts\python.exe manage.py migrate
```

Start each worker in its own terminal:

```powershell
cd django_ai_service
.\.venv\Scripts\python.exe manage.py run_ocr_worker
```

```powershell
cd django_ai_service
.\.venv\Scripts\python.exe manage.py run_stt_worker
```

```powershell
cd django_ai_service
.\.venv\Scripts\python.exe manage.py run_heatmap_worker
```

```powershell
cd django_ai_service
.\.venv\Scripts\python.exe manage.py run_heatmap_prediction_worker
```

### 4. Setup Flutter app

```powershell
cd POLICEapp
flutter pub get
flutter run
```

## Recommended Startup Order

1. MySQL or MariaDB
2. MongoDB
3. RabbitMQ
4. Ollama
5. LM Studio
6. Laravel `php artisan serve`
7. Laravel `php artisan ai:consume-results`
8. Django OCR worker
9. Django STT worker
10. Django heatmap worker
11. Django heatmap prediction worker
12. Flutter app

## Main Laravel API Endpoints

Current API routes defined in `Traffic_Police_assistant/routes/api.php` include:

- `POST /api/login`
- `POST /api/citizen/reports`
- `GET /api/profile`
- `POST /api/profile/update`
- `POST /api/fcm-token`
- `POST /api/create`
- `GET /api/violations`
- `GET /api/search-violations`
- `GET /api/cities`
- `GET /api/violation-types`
- `POST /api/logout`
- `POST /api/ocr/plate`
- `GET /api/ocr/result/{job_id}`
- `POST /api/stt/transcribe`
- `GET /api/stt/result/{job_id}`
- `POST /api/officers/live-location`
- `GET /api/officers/assignments`
- `POST /api/officers/assignments/{assignment}/start`
- `POST /api/officers/assignments/{assignment}/complete`
- `GET /api/ai_cities`
- `GET /api/ai_violation-types`
- `GET /api/ai_violations`
- `GET /api/heatmap-predictions/{request_id}`

## Main Web Routes

The Laravel web app also exposes:

- Public citizen violation lookup and appeal submission
- Admin login and management pages under `/admin`
- Police manager login, dashboard, violations, maps, heatmaps, predictions, and appeals under `/policemanager`

## Testing

### Laravel tests

```powershell
cd Traffic_Police_assistant
php artisan test
```

### Django tests

```powershell
cd django_ai_service
.\.venv\Scripts\python.exe manage.py test core --settings=config.test_settings -v 2
```

Alternative helper:

```powershell
cd django_ai_service
.\.venv\Scripts\python.exe run_tests.py
```

### Flutter tests

```powershell
cd POLICEapp
flutter test
```

Additional testing notes are documented in `CHAPTER_8_TESTING.md`.

## Common Local Issues

- If OCR or STT jobs stay pending, verify that Laravel and Django use the same RabbitMQ exchange, queues, and routing keys.
- If heatmap prediction requests stay pending, make sure `run_heatmap_prediction_worker` is running in addition to the standard heatmap worker.
- If the mobile app cannot connect, update `POLICEapp/lib/config.dart` to a reachable LAN IP or emulator-safe address.
- If AI results never arrive in Laravel, make sure `php artisan ai:consume-results` is running.
- If OCR fails, verify that Ollama is reachable and the configured model is installed.
- If STT extraction fails, verify that LM Studio is reachable.
- If database tables are missing, rerun Laravel and Django migrations.

## Notes

- The backend folder name is `Traffic_Police_assistant`.
- There is also a nested Git repository inside `POLICEapp`; treat it carefully if you are committing from the repository root.
- Do not commit local `.env` files, virtual environments, caches, or temporary generated files unless intentionally needed.
