from django.test import SimpleTestCase

from core.heatmap.cache_keys import build_cache_key
from core.heatmap.normalization import normalize_scores
from core.heatmap.payloads import PayloadValidationError, validate_payload
from core.heatmap.temporal_bucket_service import resolve_time_bucket
from core.heatmap.trend_service import compare_heatmaps


class HeatmapSupportTests(SimpleTestCase):
    def test_validate_payload_accepts_valid_message(self):
        payload = validate_payload(
            {
                "job_type": "generate_heatmap",
                "request_id": "abc-123",
                "city": "Damascus",
                "date_from": "2026-02-01",
                "date_to": "2026-02-28",
                "violation_type_id": 3,
                "time_bucket": "morning",
                "grid_size_meters": 300,
                "include_ranking": True,
                "include_trend": True,
                "include_synthetic": True,
                "comparison_mode": "week_over_week",
            }
        )
        self.assertEqual(payload.city, "Damascus")
        self.assertEqual(payload.grid_size_meters, 300)

    def test_validate_payload_rejects_bad_bucket(self):
        with self.assertRaises(PayloadValidationError):
            validate_payload(
                {
                    "job_type": "generate_heatmap",
                    "request_id": "abc-123",
                    "city": "Damascus",
                    "date_from": "2026-02-01",
                    "date_to": "2026-02-28",
                    "time_bucket": "invalid",
                }
            )

    def test_cache_key_is_stable(self):
        left = build_cache_key({"a": 1, "b": 2})
        right = build_cache_key({"b": 2, "a": 1})
        self.assertEqual(left, right)

    def test_normalize_scores_handles_identical_values(self):
        self.assertEqual(normalize_scores([5.0, 5.0]), [1.0, 1.0])
        self.assertEqual(normalize_scores([0.0, 0.0]), [0.0, 0.0])

    def test_resolve_time_bucket(self):
        import datetime as dt

        self.assertEqual(resolve_time_bucket(dt.datetime(2026, 1, 1, 7, 0)), "morning")
        self.assertEqual(resolve_time_bucket(dt.datetime(2026, 1, 1, 13, 0)), "afternoon")
        self.assertEqual(resolve_time_bucket(dt.datetime(2026, 1, 1, 20, 0)), "evening")
        self.assertEqual(resolve_time_bucket(dt.datetime(2026, 1, 1, 3, 0)), "night")

    def test_compare_heatmaps_labels_trend(self):
        trend = compare_heatmaps(
            current_points=[{"cell_id": "1", "lat": 1, "lng": 1, "intensity": 0.9}],
            previous_points=[{"cell_id": "1", "lat": 1, "lng": 1, "intensity": 0.1}],
        )
        self.assertEqual(trend[0]["trend"], "up")
