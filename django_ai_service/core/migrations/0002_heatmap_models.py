from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ("core", "0001_initial"),
    ]

    operations = [
        migrations.CreateModel(
            name="AnalyticsJob",
            fields=[
                ("id", models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name="ID")),
                ("request_id", models.CharField(max_length=120, unique=True)),
                ("job_type", models.CharField(max_length=80)),
                ("status", models.CharField(choices=[("pending", "Pending"), ("processing", "Processing"), ("completed", "Completed"), ("failed", "Failed")], default="pending", max_length=20)),
                ("error_message", models.TextField(blank=True, default="")),
                ("result_reference", models.CharField(blank=True, default="", max_length=255)),
                ("payload_json", models.TextField(default="{}")),
                ("result_json", models.TextField(default="{}")),
                ("created_at", models.DateTimeField(auto_now_add=True)),
                ("updated_at", models.DateTimeField(auto_now=True)),
            ],
        ),
        migrations.CreateModel(
            name="HeatmapCache",
            fields=[
                ("id", models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name="ID")),
                ("cache_key", models.CharField(max_length=255, unique=True)),
                ("city", models.CharField(max_length=120)),
                ("date_from", models.DateField()),
                ("date_to", models.DateField()),
                ("violation_type_id", models.IntegerField(blank=True, null=True)),
                ("time_bucket", models.CharField(blank=True, default="", max_length=30)),
                ("grid_size_meters", models.IntegerField()),
                ("include_synthetic", models.BooleanField(default=False)),
                ("comparison_mode", models.CharField(blank=True, default="", max_length=40)),
                ("heatmap_json", models.TextField(default="[]")),
                ("ranking_json", models.TextField(default="[]")),
                ("trend_json", models.TextField(default="[]")),
                ("total_violations", models.IntegerField(default=0)),
                ("status", models.CharField(choices=[("pending", "Pending"), ("completed", "Completed"), ("failed", "Failed")], default="pending", max_length=20)),
                ("expires_at", models.DateTimeField()),
                ("created_at", models.DateTimeField(auto_now_add=True)),
                ("updated_at", models.DateTimeField(auto_now=True)),
            ],
        ),
    ]
