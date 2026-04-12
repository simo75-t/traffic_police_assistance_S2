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
- Python 3.11 أو أعلى
- virtualenv أو venv
- `pip install -r django_ai_service/requirements.txt`
- قواعد بيانات SQL مثل MySQL أو PostgreSQL أو SQLite

### Laravel / PHP
- PHP 8.x
- Composer
- خادم ويب مثل Apache أو Nginx (اختياري)
- قواعد بيانات MySQL أو MariaDB

### Flutter / Mobile
- Flutter SDK
- Android SDK و/أو Xcode (حسب المنصة المستهدفة)
- `flutter pub get`

---

## Setup Steps / خطوات التنفيذ

### 1. فك المشروع / Clone the repository
```bash
git clone https://github.com/simo75-t/traffic_police_assistance_S2.git
cd "TPA -2-"
```

### 2. تأكد من الفرع الرئيسي / Confirm branch
```bash
git checkout main
```

### 3. إعداد `django_ai_service`
```bash
cd django_ai_service
python -m venv .venv
.\.venv\Scripts\activate
pip install -r requirements.txt
```

إعداد المتغيرات في ملف `.env` حسب القيم المحلية.

### 4. إعداد `Triffic_Police_assistant`
```bash
cd ..\Triffic_Police_assistant
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

اضبط إعدادات قاعدة البيانات وبيانات الاتصال داخل ملف `.env`.

### 5. إعداد `POLICEapp`
```bash
cd ..\POLICEapp
flutter pub get
```

افتح المشروع في محرر Flutter أو شغّل التطبيق باستخدام `flutter run`.

### 6. تشغيل الخدمات الأساسية
- لتشغيل عامل الخرائط الحرارية:
```bash
cd ..\django_ai_service
.\.venv\Scripts\activate
python manage.py run_heatmap_worker
```
- لتشغيل عامل التعرف على النصوص:
```bash
python manage.py run_ocr_worker
```
- لتشغيل عامل تحويل الصوت إلى نص:
```bash
python manage.py run_stt_worker
```

---


