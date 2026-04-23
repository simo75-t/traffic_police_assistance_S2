# Traffic Police Assistance S2

## نظرة عامة

مشروع لإدارة مخالفات المرور باستخدام AI. يتكون من 3 مكونات رئيسية:
- `Triffic_Police_assistant`: واجهة Laravel للإدارة والمستخدمين.
- `django_ai_service`: خادم Django لمعالجة STT وOCR وخرائط الحرارة.
- `POLICEapp`: تطبيق Flutter للهاتف المحمول.

## إعداد المشروع

### Python / Django
```powershell
cd django_ai_service
python -m venv .venv
.\.venv\Scriptsctivate
pip install -r requirements.txt
```

### Laravel
```powershell
cd ..\Traffic_Police_assistant
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
```

### Flutter
```powershell
cd ..\POLICEapp
flutter pub get
```

## تشغيل الاختبارات

### Django
```powershell
cd django_ai_service
python manage.py test core --settings=config.test_settings -v 2
```

### Laravel
```powershell
cd Traffic_Police_assistant
php artisan test tests/Feature
```

### Flutter
```powershell
cd POLICEapp
flutter test
```

## الملاحظات

- يتم استخدام MongoDB عبر Djongo في Django.
- إذا أردت الاعتماد على قاعدة البيانات الأساسية، تأكد من إعداد MongoDB وتحديث `config/settings.py`.
- بالنسبة لـ Laravel، تأكد من إعداد `.env` وقاعدة البيانات قبل التشغيل.
- بالنسبة لـ Flutter، تأكد من وجود Flutter SDK في PATH.

## الحالة الحالية

- Django: اختبارات `core` تعمل مع `config.test_settings`.
- Laravel: اختبارات `tests/Feature` متاحة.
- Flutter: اختبارات جاهزة للتشغيل عند تثبيت SDK.

## ملفات المشروع الرئيسية

- `django_ai_service/`
- `POLICEapp/`
- `Traffic_Police_assistant/`
