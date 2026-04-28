# Traffic Police Assistance S2

Integrated traffic violation management system with three connected parts:

- `Traffic_Police_assistant`: Laravel backend and web dashboard for admin, police manager, citizen reports, dispatch, violations, appeals, and PDF generation.
- `django_ai_service`: Django AI service for OCR, STT, and heatmap processing through RabbitMQ workers.
- `POLICEapp`: Flutter mobile app for police officers, including violation entry, OCR/STT flow, dispatch assignments, notifications, and PDF preview.

## Current Main Features

- Officer login and profile management
- Violation creation from the mobile app
- Plate OCR request and result polling
- Speech-to-text request and result polling
- Violation PDF generation and preview
- Dispatch assignment flow and officer live location updates
- Citizen report intake from the backend
- Heatmap generation for police manager analytics
- Automated test suites in Laravel, Django, and Flutter

## Repository Layout

```text
.
|-- Traffic_Police_assistant/
|-- django_ai_service/
|-- POLICEapp/
|-- CHAPTER_8_TESTING.md
`-- README.md
```

## Required Software

Install these before starting:

- Git
- PHP 8.2+
- Composer
- Node.js 18+ and npm
- Python 3.11+
- Flutter SDK
- MySQL or MariaDB
- MongoDB
- RabbitMQ

Optional but expected for full AI behavior:

- Ollama for OCR model inference
- LM Studio for STT text structuring

## Architecture Summary

1. `POLICEapp` sends officer actions to Laravel API endpoints.
2. Laravel stores core data in MySQL and publishes OCR, STT, and heatmap jobs to RabbitMQ.
3. Django workers consume those jobs, process AI tasks, and publish results back to RabbitMQ.
4. Laravel consumes AI results and updates business data.
5. Web and mobile clients read the updated data from Laravel.

## Environment Setup

Create local environment files before running the system.

### Laravel environment

Inside `Traffic_Police_assistant`, create `.env` from `.env.example` and configure at least:

```env
APP_NAME="Traffic Police Assistance"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=traffic_police_assistant
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

QUEUE_CONNECTION=rabbitmq
RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_USER=your_rabbit_user
RABBITMQ_PASSWORD=your_rabbit_password
RABBITMQ_VHOST=/

AI_RMQ_EXCHANGE=ai.exchange
AI_RMQ_OCR_QUEUE=ai.ocr.jobs
AI_RMQ_STT_QUEUE=ai.stt.jobs
AI_RMQ_HEATMAP_QUEUE=ai.heatmap.jobs
AI_RMQ_RESULTS_QUEUE=ai.results
AI_RMQ_OCR_ROUTING_KEY=job.ocr.create
AI_RMQ_STT_ROUTING_KEY=job.stt.create
AI_RMQ_HEATMAP_ROUTING_KEY=analytics.generate_heatmap
AI_RMQ_RESULTS_ROUTING_KEY=job.result
```

### Django environment

Inside `django_ai_service`, create `.env` from `.env.example` and configure at least:

```env
SECRET_KEY=change-me
DEBUG=True

RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_USER=your_rabbit_user
RABBITMQ_PASSWORD=your_rabbit_password
RABBITMQ_VHOST=/

AI_RMQ_EXCHANGE=ai.exchange
AI_RMQ_RESULTS_ROUTING_KEY=job.result
AI_RMQ_STT_QUEUE=ai.stt.jobs
AI_RMQ_STT_ROUTING_KEY=job.stt.create
AI_RMQ_OCR_QUEUE=ai.ocr.jobs
AI_RMQ_OCR_ROUTING_KEY=job.ocr.create
AI_RMQ_HEATMAP_QUEUE=ai.heatmap.jobs
AI_RMQ_HEATMAP_ROUTING_KEY=analytics.generate_heatmap

LARAVEL_BASE_URL=http://127.0.0.1:8000
LARAVEL_API_PREFIX=/api

MONGO_URL=mongodb://127.0.0.1:27017
MONGO_DB=ai_service
MONGO_OCR_COLLECTION=plate_ocr_results

OLLAMA_URL=http://127.0.0.1:11434/api/generate
OLLAMA_MODEL=qwen2.5vl:3b

LMSTUDIO_BASE_URL=http://127.0.0.1:1234
LMSTUDIO_MODEL=mistralai/mistral-7b-instruct-v0.3
```

### Flutter environment

Update the API base URL in the Flutter app to point to your Laravel server. Check the files under `POLICEapp/lib/`, especially configuration and API service files, before running on a real device.

If you run the app on a physical phone, `127.0.0.1` will not point to your PC. Use your machine's LAN IP instead.

## Full System Startup

Use PowerShell from the repository root unless noted otherwise.

### 1. Start infrastructure services

Make sure these are running:

- MySQL or MariaDB
- MongoDB
- RabbitMQ
- Ollama
- LM Studio

### 2. Setup and run Laravel

```powershell
cd Traffic_Police_assistant
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
```

Run the backend:

```powershell
php artisan serve
```

In a second terminal for Laravel, start the AI result consumer:

```powershell
cd Traffic_Police_assistant
php artisan ai:consume-results
```

Optional frontend assets during development:

```powershell
cd Traffic_Police_assistant
npm run dev
```

### 3. Setup and run Django AI service

```powershell
cd django_ai_service
python -m venv .venv
.\.venv\Scripts\activate
pip install -r requirements.txt
python manage.py migrate
```

Start the workers in separate terminals:

```powershell
cd django_ai_service
.\.venv\Scripts\activate
python manage.py run_ocr_worker
```

```powershell
cd django_ai_service
.\.venv\Scripts\activate
python manage.py run_stt_worker
```

```powershell
cd django_ai_service
.\.venv\Scripts\activate
python manage.py run_heatmap_worker
```

### 4. Setup and run Flutter app

```powershell
cd POLICEapp
flutter pub get
flutter run
```

## Recommended Run Order

For a complete local session, start things in this order:

1. MySQL / MariaDB
2. MongoDB
3. RabbitMQ
4. Ollama
5. LM Studio
6. Laravel `php artisan serve`
7. Laravel `php artisan ai:consume-results`
8. Django OCR worker
9. Django STT worker
10. Django heatmap worker
11. Flutter app

## Main API Flows

Important Laravel API endpoints currently exposed from `Traffic_Police_assistant/routes/api.php`:

- `POST /api/login`
- `GET /api/profile`
- `POST /api/profile/update`
- `POST /api/fcm-token`
- `POST /api/create`
- `GET /api/violations`
- `GET /api/search-violations`
- `GET /api/cities`
- `GET /api/violation-types`
- `POST /api/ocr/plate`
- `GET /api/ocr/result/{job_id}`
- `POST /api/stt/transcribe`
- `GET /api/stt/result/{job_id}`
- `POST /api/officers/live-location`
- `GET /api/officers/assignments`
- `POST /api/officers/assignments/{assignment}/start`
- `POST /api/officers/assignments/{assignment}/complete`
- `POST /api/citizen/reports`
- `GET /api/ai_violations`
- `GET /api/ai_cities`
- `GET /api/ai_violation-types`

## Testing

### Laravel tests

```powershell
cd Traffic_Police_assistant
php artisan test
```

### Django tests

```powershell
cd django_ai_service
.\.venv\Scripts\activate
python manage.py test core --settings=config.test_settings -v 2
```

Alternative helper script:

```powershell
cd django_ai_service
.\.venv\Scripts\activate
python run_tests.py
```

### Flutter tests

```powershell
cd POLICEapp
flutter test
```

Additional testing notes are documented in `CHAPTER_8_TESTING.md`.

## Common Local Issues

- If OCR or STT requests stay pending, verify that RabbitMQ is running and both Laravel and Django use the same exchange, queue, and routing key values.
- If the mobile app cannot connect on a real device, replace localhost with your machine IP in both Laravel and Flutter configuration.
- If heatmap generation fails, verify that Laravel is reachable from Django through `LARAVEL_BASE_URL`.
- If OCR fails, verify that Ollama is running and the configured model is installed.
- If STT structuring fails, verify that LM Studio is running and accessible.
- If database tables are missing, rerun `php artisan migrate` and `python manage.py migrate`.

## Notes

- The correct backend directory name is `Traffic_Police_assistant`.
- Some older documentation may still mention `Triffic_Police_assistant`; that is outdated.
- Do not commit local `.env`, virtual environments, or generated temporary files.

