from django.db import models
import json


# Stores the officer identity linked to an STT job when available.
class Officer(models.Model):
    id = models.AutoField(primary_key=True)
    full_name = models.CharField(max_length=150)
    badge_number = models.CharField(max_length=50, unique=True)
    created_at = models.DateTimeField(auto_now_add=True)

    # Return a readable label in admin and logs.
    def __str__(self) -> str:
        return f"{self.full_name} ({self.badge_number})"


# Stores the minimal STT job schema expected by the original migrations.
class STTJob(models.Model):
    STATUS_QUEUED = "queued"
    STATUS_PROCESSING = "processing"
    STATUS_DONE = "done"
    STATUS_FAILED = "failed"

    STATUS_CHOICES = [
        (STATUS_QUEUED, "Queued"),
        (STATUS_PROCESSING, "Processing"),
        (STATUS_DONE, "Done"),
        (STATUS_FAILED, "Failed"),
    ]

    id = models.AutoField(primary_key=True)
    officer = models.ForeignKey(
        Officer,
        on_delete=models.SET_NULL,
        blank=True,
        null=True,
    )
    audio_path = models.CharField(max_length=500)
    language = models.CharField(max_length=20, default="ar")
    status = models.CharField(
        max_length=20,
        choices=STATUS_CHOICES,
        default=STATUS_QUEUED,
    )
    transcript = models.TextField(blank=True, default="")
    error_message = models.TextField(blank=True, default="")
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    # Return a compact job label for admin lists and debugging.
    def __str__(self) -> str:
        return f"STTJob#{self.pk} [{self.status}]"


class HeatmapCache(models.Model):
    STATUS_PENDING = "pending"
    STATUS_COMPLETED = "completed"
    STATUS_FAILED = "failed"

    STATUS_CHOICES = [
        (STATUS_PENDING, "Pending"),
        (STATUS_COMPLETED, "Completed"),
        (STATUS_FAILED, "Failed"),
    ]

    cache_key = models.CharField(max_length=255, unique=True)
    city = models.CharField(max_length=120)
    date_from = models.DateField()
    date_to = models.DateField()
    violation_type_id = models.IntegerField(blank=True, null=True)
    time_bucket = models.CharField(max_length=30, blank=True, default="")
    grid_size_meters = models.IntegerField()
    include_synthetic = models.BooleanField(default=False)
    comparison_mode = models.CharField(max_length=40, blank=True, default="")
    heatmap_json = models.TextField(default="[]")
    ranking_json = models.TextField(default="[]")
    trend_json = models.TextField(default="[]")
    total_violations = models.IntegerField(default=0)
    status = models.CharField(max_length=20, choices=STATUS_CHOICES, default=STATUS_PENDING)
    expires_at = models.DateTimeField()
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self) -> str:
        return f"HeatmapCache[{self.cache_key}]"

    @property
    def heatmap(self):
        return json.loads(self.heatmap_json or "[]")

    @property
    def ranking(self):
        return json.loads(self.ranking_json or "[]")

    @property
    def trend(self):
        return json.loads(self.trend_json or "[]")


class AnalyticsJob(models.Model):
    STATUS_PENDING = "pending"
    STATUS_PROCESSING = "processing"
    STATUS_COMPLETED = "completed"
    STATUS_FAILED = "failed"

    STATUS_CHOICES = [
        (STATUS_PENDING, "Pending"),
        (STATUS_PROCESSING, "Processing"),
        (STATUS_COMPLETED, "Completed"),
        (STATUS_FAILED, "Failed"),
    ]

    request_id = models.CharField(max_length=120, unique=True)
    job_type = models.CharField(max_length=80)
    status = models.CharField(max_length=20, choices=STATUS_CHOICES, default=STATUS_PENDING)
    error_message = models.TextField(blank=True, default="")
    result_reference = models.CharField(max_length=255, blank=True, default="")
    payload_json = models.TextField(default="{}")
    result_json = models.TextField(default="{}")
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self) -> str:
        return f"AnalyticsJob[{self.request_id}] {self.status}"
