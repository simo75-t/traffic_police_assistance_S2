"""Model and core component tests for the Django backend."""

from django.test import SimpleTestCase, TransactionTestCase
from django.utils import timezone
from datetime import datetime, timedelta
import json

from core.models import Officer, STTJob, HeatmapCache, AnalyticsJob


class OfficerModelTests(TransactionTestCase):
    """Test the Officer model for identity and badge management."""

    def test_officer_creation_with_badge(self):
        """Test creating an officer with badge number."""
        officer = Officer.objects.create(
            full_name="أحمد محمد علي",
            badge_number="2026-001",
        )
        self.assertEqual(officer.full_name, "أحمد محمد علي")
        self.assertEqual(officer.badge_number, "2026-001")
        self.assertIsNotNone(officer.created_at)

    def test_officer_badge_uniqueness(self):
        """Test that badge numbers are unique."""
        Officer.objects.create(
            full_name="Officer 1",
            badge_number="2026-001",
        )
        with self.assertRaises(Exception):
            Officer.objects.create(
                full_name="Officer 2",
                badge_number="2026-001",
            )

    def test_officer_str_representation(self):
        """Test the string representation of an officer."""
        officer = Officer.objects.create(
            full_name="محمد علي",
            badge_number="2026-042",
        )
        self.assertEqual(str(officer), "محمد علي (2026-042)")


class STTJobModelTests(TransactionTestCase):
    """Test the STTJob model for speech-to-text processing."""

    def setUp(self):
        """Create test officer for foreign key relationship."""
        self.officer = Officer.objects.create(
            full_name="Test Officer",
            badge_number="2026-test",
        )

    def test_stt_job_creation(self):
        """Test creating an STT job."""
        job = STTJob.objects.create(
            officer=self.officer,
            audio_path="/media/audio/test.wav",
            language="ar",
            status=STTJob.STATUS_QUEUED,
        )
        self.assertEqual(job.status, STTJob.STATUS_QUEUED)
        self.assertEqual(job.officer.badge_number, "2026-test")
        self.assertEqual(job.transcript, "")

    def test_stt_job_status_transitions(self):
        """Test valid status transitions for an STT job."""
        job = STTJob.objects.create(
            officer=self.officer,
            audio_path="/media/audio/test.wav",
            status=STTJob.STATUS_QUEUED,
        )
        job.status = STTJob.STATUS_PROCESSING
        job.save()
        job.refresh_from_db()
        self.assertEqual(job.status, STTJob.STATUS_PROCESSING)

        job.status = STTJob.STATUS_DONE
        job.transcript = "نص مستخرج من الصوت"
        job.save()
        job.refresh_from_db()
        self.assertEqual(job.status, STTJob.STATUS_DONE)
        self.assertEqual(job.transcript, "نص مستخرج من الصوت")

    def test_stt_job_error_status(self):
        """Test STT job failure status with error message."""
        job = STTJob.objects.create(
            officer=self.officer,
            audio_path="/media/audio/corrupted.wav",
            status=STTJob.STATUS_FAILED,
            error_message="Audio file corrupted or unsupported format",
        )
        self.assertEqual(job.status, STTJob.STATUS_FAILED)
        self.assertIn("corrupted", job.error_message)

    def test_stt_job_str_representation(self):
        """Test the string representation of an STT job."""
        job = STTJob.objects.create(
            officer=self.officer,
            audio_path="/media/audio/test.wav",
            status=STTJob.STATUS_PROCESSING,
        )
        self.assertIn(str(job.pk), str(job))
        self.assertIn("processing", str(job))

    def test_stt_job_without_officer(self):
        """Test creating an STT job without an associated officer."""
        job = STTJob.objects.create(
            audio_path="/media/audio/anonymous.wav",
            language="ar",
        )
        self.assertIsNone(job.officer)
        self.assertEqual(job.language, "ar")


class HeatmapCacheModelTests(TransactionTestCase):
    """Test the HeatmapCache model for caching heatmap data."""

    def test_heatmap_cache_creation(self):
        """Test creating a heatmap cache entry."""
        expires = timezone.now() + timedelta(hours=24)
        cache = HeatmapCache.objects.create(
            cache_key="heatmap_damascus_2026-04-20_morning",
            city="دمشق",
            date_from="2026-04-20",
            date_to="2026-04-20",
            time_bucket="morning",
            grid_size_meters=500,
            expires_at=expires,
        )
        self.assertEqual(cache.city, "دمشق")
        self.assertEqual(cache.status, HeatmapCache.STATUS_PENDING)
        self.assertEqual(cache.total_violations, 0)

    def test_heatmap_cache_json_properties(self):
        """Test JSON property parsing for heatmap data."""
        expires = timezone.now() + timedelta(hours=24)
        heatmap_data = [
            {"cell_id": "1", "lat": 33.5138, "lng": 36.2765, "intensity": 0.85},
            {"cell_id": "2", "lat": 33.5140, "lng": 36.2770, "intensity": 0.72},
        ]
        cache = HeatmapCache.objects.create(
            cache_key="test_heatmap",
            city="دمشق",
            date_from="2026-04-20",
            date_to="2026-04-20",
            grid_size_meters=300,
            heatmap_json=json.dumps(heatmap_data),
            status=HeatmapCache.STATUS_COMPLETED,
            expires_at=expires,
        )
        self.assertEqual(len(cache.heatmap), 2)
        self.assertEqual(cache.heatmap[0]["intensity"], 0.85)

    def test_heatmap_cache_status_pending_to_completed(self):
        """Test heatmap cache status transition from pending to completed."""
        expires = timezone.now() + timedelta(hours=24)
        cache = HeatmapCache.objects.create(
            cache_key="test_heatmap_pending",
            city="حلب",
            date_from="2026-04-20",
            date_to="2026-04-20",
            grid_size_meters=400,
            status=HeatmapCache.STATUS_PENDING,
            expires_at=expires,
        )
        self.assertEqual(cache.status, HeatmapCache.STATUS_PENDING)

        cache.status = HeatmapCache.STATUS_COMPLETED
        cache.total_violations = 1250
        cache.save()
        cache.refresh_from_db()
        self.assertEqual(cache.status, HeatmapCache.STATUS_COMPLETED)
        self.assertEqual(cache.total_violations, 1250)

    def test_heatmap_cache_expiration(self):
        """Test heatmap cache expiration timestamp."""
        past_time = timezone.now() - timedelta(hours=1)
        cache = HeatmapCache.objects.create(
            cache_key="expired_heatmap",
            city="حمص",
            date_from="2026-04-19",
            date_to="2026-04-19",
            grid_size_meters=250,
            expires_at=past_time,
        )
        self.assertLess(cache.expires_at, timezone.now())


class AnalyticsJobModelTests(TransactionTestCase):
    """Test the AnalyticsJob model for async job processing."""

    def test_analytics_job_creation(self):
        """Test creating an analytics job."""
        job = AnalyticsJob.objects.create(
            request_id="analytics-2026-0001",
            job_type="generate_heatmap",
        )
        self.assertEqual(job.request_id, "analytics-2026-0001")
        self.assertEqual(job.status, AnalyticsJob.STATUS_PENDING)
        self.assertEqual(job.payload_json, "{}")

    def test_analytics_job_with_payload(self):
        """Test analytics job with payload data."""
        payload = {
            "city": "damascus",
            "date_from": "2026-04-01",
            "date_to": "2026-04-30",
            "violation_type_id": 3,
        }
        job = AnalyticsJob.objects.create(
            request_id="analytics-2026-0002",
            job_type="generate_heatmap",
            payload_json=json.dumps(payload),
        )
        loaded_payload = json.loads(job.payload_json)
        self.assertEqual(loaded_payload["city"], "damascus")
        self.assertEqual(loaded_payload["violation_type_id"], 3)

    def test_analytics_job_status_progression(self):
        """Test analytics job status progression through lifecycle."""
        job = AnalyticsJob.objects.create(
            request_id="analytics-2026-0003",
            job_type="generate_heatmap",
        )
        self.assertEqual(job.status, AnalyticsJob.STATUS_PENDING)

        job.status = AnalyticsJob.STATUS_PROCESSING
        job.save()
        job.refresh_from_db()
        self.assertEqual(job.status, AnalyticsJob.STATUS_PROCESSING)

        result = {"heatmap": [], "ranking": [], "trend": []}
        job.status = AnalyticsJob.STATUS_COMPLETED
        job.result_json = json.dumps(result)
        job.result_reference = "heatmap-cache-key-12345"
        job.save()
        job.refresh_from_db()
        self.assertEqual(job.status, AnalyticsJob.STATUS_COMPLETED)
        self.assertIn("heatmap", job.result_json)

    def test_analytics_job_failure_with_error_message(self):
        """Test analytics job failure with error tracking."""
        job = AnalyticsJob.objects.create(
            request_id="analytics-2026-failed",
            job_type="generate_heatmap",
            status=AnalyticsJob.STATUS_FAILED,
            error_message="Database connection timeout during heatmap generation",
        )
        self.assertEqual(job.status, AnalyticsJob.STATUS_FAILED)
        self.assertIn("timeout", job.error_message)

    def test_analytics_job_request_id_uniqueness(self):
        """Test that request IDs are unique across analytics jobs."""
        AnalyticsJob.objects.create(
            request_id="unique-req-001",
            job_type="generate_heatmap",
        )
        with self.assertRaises(Exception):
            AnalyticsJob.objects.create(
                request_id="unique-req-001",
                job_type="generate_report",
            )
