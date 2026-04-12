## Django AI Service

هذا المشروع يجب تشغيله من داخل البيئة الافتراضية `.venv`.

### المفسر الصحيح

استخدمي هذا المفسر دائمًا:

```powershell
.\.venv\Scripts\python.exe
```

ولا تستخدمي:

```powershell
python
```

إلا إذا كانت البيئة `.venv` مفعلة فعليًا في الطرفية.

### تثبيت المكتبات

إذا نقصت مكتبات التحليل أو الـ workers:

```powershell
.\.venv\Scripts\python.exe -m pip install djongo pandas scikit-learn openai-whisper requests pika pymongo opencv-python
```

أو ثبتيها مباشرة من الملف:

```powershell
.\.venv\Scripts\python.exe -m pip install -r requirements.txt
```

### أوامر Django الأساسية

فحص المشروع:

```powershell
.\.venv\Scripts\python.exe manage.py check
```

إنشاء migrations:

```powershell
.\.venv\Scripts\python.exe manage.py makemigrations
```

تطبيق migrations:

```powershell
.\.venv\Scripts\python.exe manage.py migrate
```

### تشغيل الووركرات

تشغيل OCR worker:

```powershell
.\.venv\Scripts\python.exe manage.py run_ocr_worker
```

تشغيل STT worker:

```powershell
.\.venv\Scripts\python.exe manage.py run_stt_worker
```

تشغيل Heatmap worker:

```powershell
.\.venv\Scripts\python.exe manage.py run_heatmap_worker
```

### بنية المشروع

`core/ocr`
منطق OCR الداخلي.

`core/stt`
منطق STT الداخلي.

`core/heatmap`
منطق التحليل الحراري.

`core/utils`
مساعدات مشتركة.

`core/ocr_worker.py`
مدخل تشغيل OCR.

`core/stt_worker.py`
مدخل تشغيل STT.

`core/heatmap_worker.py`
مدخل تشغيل Heatmap.

### ملاحظات مهمة

`core/stt_rabbit_worker_whisper.py`
ملف قديم للتوافق فقط.

`core/llm_extract_ollama.py`
و
`core/lookups_loader.py`
و
`core/mapping.py`
ملفات توافق قديمة.

النسخ المنظمة موجودة داخل:

`core/utils`

### ملاحظات تشغيل Heatmap

خدمة Heatmap تحتاج:

`pandas`

و

`scikit-learn`

بدونهما لن يعمل تحليل KDE فعليًا.
